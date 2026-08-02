<?php
/**
 * AI Integration → Companion intelligence bridge.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emits AI agent activity into the central intelligence bus.
 */
final class NGTAI_Intelligence_Bridge {

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'plugins_loaded', [ __CLASS__, 'boot' ], 25 );
	}

	/**
	 * @return void
	 */
	public static function boot() {
		if ( ! class_exists( 'NGC_Intelligence' ) ) {
			return;
		}
		add_action( 'ngtai_agent_result_applied', [ __CLASS__, 'on_result_applied' ], 10, 2 );
		add_action( 'ngtai_outbox_flush_complete', [ __CLASS__, 'on_outbox_flush' ], 10, 1 );
		add_filter( 'ngc_intelligence_register_plugins', [ __CLASS__, 'register_metadata' ] );
	}

	/**
	 * @param mixed $subject_id Subject ID.
	 * @param mixed $payload    Result payload.
	 * @return void
	 */
	public static function on_result_applied( $subject_id, $payload ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'ai.agent.result_applied',
				'plugin_slug' => 'ai-integration',
				'module'      => 'agents',
				'domain'      => 'ai',
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'AI agent result applied',
				'payload'     => [
					'subject_id' => $subject_id,
					'result'     => is_array( $payload ) ? $payload : [ 'raw' => $payload ],
				],
				'source'      => 'ngtai_intelligence_bridge',
			]
		);
	}

	/**
	 * @param array<string, mixed> $stats Flush stats.
	 * @return void
	 */
	public static function on_outbox_flush( $stats ) {
		if ( ! is_array( $stats ) ) {
			return;
		}
		$failed = (int) ( $stats['failed'] ?? 0 );
		NGC_Intelligence::emit(
			[
				'event_key'   => $failed > 0 ? 'ai.outbox.flush_partial' : 'ai.outbox.flush',
				'plugin_slug' => 'ai-integration',
				'module'      => 'outbox',
				'domain'      => 'ai',
				'severity'    => $failed > 0 ? 'warning' : 'info',
				'outcome'     => $failed > 0 ? 'partial' : 'success',
				'message'     => sprintf( 'Outbox flush: %d sent, %d failed', (int) ( $stats['sent'] ?? 0 ), $failed ),
				'payload'     => $stats,
				'source'      => 'ngtai_intelligence_bridge',
			]
		);
	}

	/**
	 * @param mixed $registry Registry class name.
	 * @return void
	 */
	public static function register_metadata( $registry ) {
		if ( ! class_exists( 'NGC_Intelligence' ) ) {
			return;
		}
		NGC_Intelligence::register_plugin(
			[
				'slug'        => 'ai-integration',
				'name'        => 'AI Integration',
				'version'     => defined( 'NGTAI_VERSION' ) ? NGTAI_VERSION : '',
				'description' => 'BYOK agents-api bridge',
				'features'    => [ 'outbox', 'approvals', 'forecasting', 'nl_insights' ],
				'kpis'        => [ 'ai_tasks', 'outbox_failures' ],
			]
		);
	}
}

NGTAI_Intelligence_Bridge::init();
