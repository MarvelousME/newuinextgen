<?php
/**
 * Bridge memory façade — policy, classification, optional provider.
 *
 * DISABLED/DEGRADED never throws into booking/payment paths.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability-facing memory service.
 */
final class NGC_Memory_Service {

	/** @var NGC_Memory_Provider_Interface|null */
	private static $provider = null;

	/**
	 * Bootstrap hooks.
	 */
	public static function init() {
		self::ensure_interface();
		if ( class_exists( 'NGC_Database' ) && method_exists( 'NGC_Database', 'ensure_memory_identity_map' ) ) {
			NGC_Database::ensure_memory_identity_map();
		}
		add_filter( 'ngc_queue_handle_memoryingest', [ 'NGC_Memory_Ingestion_Worker', 'handle' ], 10, 3 );
	}

	/**
	 * Load interface file (autoloader uses class- prefix).
	 */
	private static function ensure_interface() {
		if ( interface_exists( 'NGC_Memory_Provider_Interface', false ) ) {
			return;
		}
		$path = NGC_PLUGIN_DIR . 'includes/memory/interface-ngc-memory-provider.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Resolve active provider (noop when inactive).
	 *
	 * @return NGC_Memory_Provider_Interface
	 */
	public static function provider() {
		self::ensure_interface();
		if ( self::$provider instanceof NGC_Memory_Provider_Interface ) {
			return self::$provider;
		}
		if ( ! NGC_Memory_Settings::is_active() ) {
			self::$provider = new NGC_Memory_Noop_Provider();
			return self::$provider;
		}
		$cfg = NGC_Memory_Settings::get();
		if ( 'tencentdb' === ( $cfg['provider'] ?? '' ) && ! empty( $cfg['core_base_url'] ) ) {
			self::$provider = new NGC_Tencent_Memory_Adapter();
		} else {
			self::$provider = new NGC_Memory_Noop_Provider();
		}
		return self::$provider;
	}

	/**
	 * Reset cached provider (tests / settings change).
	 */
	public static function reset_provider() {
		self::$provider = null;
	}

	/**
	 * Health capability.
	 *
	 * @return array<string,mixed>
	 */
	public static function health() {
		try {
			$h = self::provider()->health();
			$h['settings'] = [
				'enabled'          => (bool) NGC_Memory_Settings::get()['enabled'],
				'mode'             => (string) NGC_Memory_Settings::get()['mode'],
				'retrieve_enabled' => NGC_Memory_Settings::retrieve_allowed(),
				'write_enabled'    => NGC_Memory_Settings::write_allowed(),
				'proxy_enabled'    => false,
				'skills_enabled'   => ! empty( NGC_Memory_Settings::get()['skills_enabled'] ),
				'wiki_enabled'     => ! empty( NGC_Memory_Settings::get()['wiki_enabled'] ),
				'codegraph_enabled'=> ! empty( NGC_Memory_Settings::get()['codegraph_enabled'] ),
				'sqlite_ha_acknowledged' => ! empty( NGC_Memory_Settings::get()['sqlite_ha_acknowledged'] ),
			];
			return $h;
		} catch ( Exception $e ) {
			return [
				'ok'      => false,
				'mode'    => NGC_Memory_Settings::MODE_DEGRADED,
				'message' => 'Memory health exception (degraded)',
			];
		}
	}

	/**
	 * Budgeted retrieve for prompt enrichment — never fails chat.
	 *
	 * @param array<string,mixed> $context Context.
	 * @return array{ok:bool,context_text:string,items:array,degraded?:bool}
	 */
	public static function retrieve_safe( array $context ) {
		if ( ! NGC_Memory_Settings::retrieve_allowed() ) {
			return [ 'ok' => true, 'context_text' => '', 'items' => [], 'degraded' => false ];
		}
		$decision = self::policy( 'memory.retrieve', $context );
		$dec      = (string) ( $decision['decision'] ?? 'DENY' );
		if ( ! in_array( $dec, [ 'ALLOW', 'ALLOW_WITH_LIMITS' ], true ) ) {
			return [ 'ok' => true, 'context_text' => '', 'items' => [], 'degraded' => false, 'denied' => true ];
		}
		try {
			$result = self::provider()->retrieve( $context );
			if ( is_wp_error( $result ) ) {
				self::metric( 'memory_degraded_total', [ 'op' => 'retrieve' ] );
				return [ 'ok' => true, 'context_text' => '', 'items' => [], 'degraded' => true ];
			}
			self::metric( 'memory_retrieve_total', [ 'op' => 'retrieve' ] );
			return [
				'ok'           => true,
				'context_text' => (string) ( $result['context_text'] ?? '' ),
				'items'        => (array) ( $result['items'] ?? [] ),
				'degraded'     => false,
			];
		} catch ( Exception $e ) {
			self::metric( 'memory_degraded_total', [ 'op' => 'retrieve' ] );
			return [ 'ok' => true, 'context_text' => '', 'items' => [], 'degraded' => true ];
		}
	}

	/**
	 * Classify + enqueue or sync write. Never blocks critical commerce paths.
	 *
	 * @param array<string,mixed> $context Write payload.
	 * @return array<string,mixed>
	 */
	public static function write_safe( array $context ) {
		if ( ! NGC_Memory_Settings::write_allowed() ) {
			return [ 'ok' => true, 'written' => false, 'reason' => 'write_disabled' ];
		}
		$classification = self::classify( $context );
		$gate           = self::write_policy_gate( $classification, $context );
		if ( ! $gate['allow'] ) {
			return [ 'ok' => true, 'written' => false, 'reason' => $gate['reason'], 'classification' => $classification ];
		}
		$decision = self::policy( 'memory.write', $context );
		$dec      = (string) ( $decision['decision'] ?? 'DENY' );
		if ( ! in_array( $dec, [ 'ALLOW', 'ALLOW_WITH_LIMITS' ], true ) ) {
			return [ 'ok' => true, 'written' => false, 'reason' => 'policy_deny', 'classification' => $classification ];
		}

		$async = ! empty( $context['async'] ) || ! isset( $context['async'] );
		if ( $async && class_exists( 'NGC_Durable_Queue' ) ) {
			$mid = NGC_Durable_Queue::enqueue(
				'memory',
				[
					'type'           => 'memory.ingest',
					'classification' => $classification,
					'payload'        => $context,
				],
				[
					'idempotency_key' => (string) ( $context['idempotency_key'] ?? '' ),
					'priority'        => 50,
				]
			);
			if ( is_wp_error( $mid ) ) {
				self::metric( 'memory_degraded_total', [ 'op' => 'enqueue' ] );
				return [ 'ok' => true, 'written' => false, 'reason' => 'enqueue_failed', 'degraded' => true ];
			}
			self::metric( 'memory_write_total', [ 'op' => 'enqueue' ] );
			return [ 'ok' => true, 'written' => false, 'queued' => true, 'message_id' => $mid ];
		}

		try {
			$result = self::provider()->write( $context );
			if ( is_wp_error( $result ) ) {
				self::metric( 'memory_degraded_total', [ 'op' => 'write' ] );
				return [ 'ok' => true, 'written' => false, 'reason' => $result->get_error_message(), 'degraded' => true ];
			}
			self::metric( 'memory_write_total', [ 'op' => 'write' ] );
			return is_array( $result ) ? $result : [ 'ok' => true, 'written' => true ];
		} catch ( Exception $e ) {
			self::metric( 'memory_degraded_total', [ 'op' => 'write' ] );
			return [ 'ok' => true, 'written' => false, 'degraded' => true ];
		}
	}

	/**
	 * Sync provider write used by queue worker.
	 *
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function write_now( array $context ) {
		if ( ! NGC_Memory_Settings::write_allowed() ) {
			return [ 'ok' => true, 'written' => false, 'reason' => 'write_disabled' ];
		}
		return self::provider()->write( $context );
	}

	/**
	 * Content classification for write policy.
	 *
	 * @param array<string,mixed> $context Context.
	 * @return string FORBIDDEN|SENSITIVE|ROUTINE|MINOR_LINKED
	 */
	public static function classify( array $context ) {
		$text = strtolower( (string) ( $context['text'] ?? '' ) );
		if ( '' === $text && ! empty( $context['messages'] ) && is_array( $context['messages'] ) ) {
			foreach ( $context['messages'] as $m ) {
				$text .= ' ' . strtolower( (string) ( is_array( $m ) ? ( $m['content'] ?? '' ) : $m ) );
			}
		}
		if ( preg_match( '/\b(password|api[_-]?key|secret|sk-[a-z0-9]+|bearer\s+[a-z0-9\.\-_]+)\b/i', $text ) ) {
			return 'FORBIDDEN';
		}
		if ( ! empty( $context['minor_linked'] ) || ! empty( $context['involves_minor'] ) ) {
			return 'MINOR_LINKED';
		}
		if ( preg_match( '/\b(id number|passport|credit card|ssn|medical|diagnosis)\b/i', $text ) ) {
			return 'SENSITIVE';
		}
		return 'ROUTINE';
	}

	/**
	 * Explicit write policy before long-term memory on tutoring/PII/minors.
	 *
	 * @param string              $classification Class.
	 * @param array<string,mixed> $context        Context.
	 * @return array{allow:bool,reason:string}
	 */
	public static function write_policy_gate( $classification, array $context = [] ) {
		$cfg = NGC_Memory_Settings::get();
		if ( 'FORBIDDEN' === $classification ) {
			return [ 'allow' => false, 'reason' => 'never_persist' ];
		}
		if ( 'MINOR_LINKED' === $classification && empty( $cfg['allow_long_term_minors'] ) ) {
			return [ 'allow' => false, 'reason' => 'deny_long_term_minors' ];
		}
		if ( 'SENSITIVE' === $classification && empty( $context['admin_override'] ) ) {
			return [ 'allow' => false, 'reason' => 'deny_long_term_unless_admin' ];
		}
		// Tutoring session transcripts require explicit long_term flag.
		if ( ! empty( $context['tutoring_data'] ) && empty( $context['allow_long_term'] ) ) {
			return [ 'allow' => false, 'reason' => 'tutoring_long_term_requires_explicit_allow' ];
		}
		return [ 'allow' => true, 'reason' => 'ok' ];
	}

	/**
	 * @param string              $capability Capability id.
	 * @param array<string,mixed> $context    Context.
	 * @return array{decision:string,reason:string}
	 */
	private static function policy( $capability, array $context ) {
		if ( ! class_exists( 'NGC_Policy_Bridge' ) ) {
			// Soft allow when bridge missing; feature flags already gate retrieve/write.
			return [ 'decision' => 'ALLOW', 'reason' => 'no_policy_bridge' ];
		}
		return NGC_Policy_Bridge::decide(
			$capability,
			array_merge(
				[
					'actor_type' => (string) ( $context['actor_type'] ?? 'human' ),
					'operation'  => (string) ( $context['operation'] ?? 'invoke' ),
				],
				$context
			)
		);
	}

	/**
	 * @param string              $name Metric.
	 * @param array<string,mixed> $tags Tags.
	 */
	private static function metric( $name, array $tags = [] ) {
		if ( class_exists( 'NGC_Metrics' ) && method_exists( 'NGC_Metrics', 'inc' ) ) {
			NGC_Metrics::inc( $name, 1, $tags );
		}
	}
}
