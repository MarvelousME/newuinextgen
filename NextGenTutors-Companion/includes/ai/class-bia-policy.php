<?php
/**
 * AI policy chokepoint — capabilities, egress allowlist, PII redaction, audit.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BeyondInfinity AI Suite policy layer.
 */
final class BIA_Policy {

	public const OPTION = 'bia_policy';

	/**
	 * @var array<string,string>
	 */
	private const CAP_MAP = [
		'db.read'         => 'bia_db_ops',
		'db.write'        => 'bia_db_ops',
		'db.destructive'  => 'bia_db_ops',
		'db.maintenance'  => 'bia_db_ops',
		'ai.model.manage' => 'bia_ai_admin',
		'ai.agent.manage' => 'bia_ai_admin',
		'ai.chat'         => 'bia_ai_use',
		'ai.tool.invoke'  => 'bia_ai_use',
		'terminal.run'    => 'bia_terminal',
		'terminal.raw'    => 'bia_terminal',
	];

	/**
	 * @var array<int,string>
	 */
	private const SUPER_ADMIN_OPS = [ 'db.write', 'db.destructive', 'terminal.run', 'terminal.raw' ];

	/** @var array<int,string> */
	public const ALL_CAPS = [ 'bia_db_ops', 'bia_ai_admin', 'bia_ai_use', 'bia_terminal' ];

	/**
	 * Install default policy options and administrator capabilities.
	 */
	public static function install() {
		$defaults = [
			'enabled'           => true,
			'egress_allowlist'  => [ self::site_host() ],
			'approval_required' => [ 'db.write', 'db.destructive', 'ai.tool.invoke', 'terminal.raw' ],
		];
		if ( false === get_option( self::OPTION, false ) ) {
			update_option( self::OPTION, $defaults, false );
		}

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::ALL_CAPS as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function config() {
		$stored = get_option( self::OPTION, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		return array_replace(
			[
				'enabled'           => true,
				'egress_allowlist'  => [ self::site_host() ],
				'approval_required' => [ 'db.write', 'db.destructive', 'ai.tool.invoke', 'terminal.raw' ],
			],
			$stored
		);
	}

	/**
	 * Central authorization gate.
	 *
	 * @param string               $operation Operation slug.
	 * @param array<string,mixed>  $context   Optional context.
	 * @return true|WP_Error
	 */
	public static function can( $operation, $context = [] ) {
		$config = self::config();

		if ( empty( $config['enabled'] ) ) {
			return new WP_Error( 'bia_disabled', __( 'The AI policy layer is disabled.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$cap = self::CAP_MAP[ $operation ] ?? null;
		if ( null === $cap ) {
			return new WP_Error( 'bia_unknown_op', __( 'Unknown operation.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}

		if ( ! current_user_can( $cap ) ) {
			self::audit( $operation, 'denied', [ 'reason' => 'capability', 'cap' => $cap ] );
			return new WP_Error( 'bia_cap', __( 'You do not have permission for this operation.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		if ( in_array( $operation, self::SUPER_ADMIN_OPS, true ) && ! is_super_admin() ) {
			self::audit( $operation, 'denied', [ 'reason' => 'not_super_admin' ] );
			return new WP_Error( 'bia_super', __( 'This operation requires a super administrator.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		if ( 'terminal.raw' === $operation && ! ( defined( 'BIA_ALLOW_RAW_SHELL' ) && BIA_ALLOW_RAW_SHELL ) ) {
			self::audit( $operation, 'denied', [ 'reason' => 'raw_shell_disabled' ] );
			return new WP_Error( 'bia_raw', __( 'Raw shell is disabled. Set BIA_ALLOW_RAW_SHELL in wp-config.php (staging only).', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$limit  = (int) ( $context['rate_limit'] ?? 60 );
		$window = (int) ( $context['rate_window'] ?? 60 );
		if ( ! self::rate_ok( $operation, $limit, $window ) ) {
			self::audit( $operation, 'denied', [ 'reason' => 'rate_limit' ] );
			return new WP_Error( 'bia_rate', __( 'Rate limit exceeded for this operation.', 'nextgencompanion' ), [ 'status' => 429 ] );
		}

		return true;
	}

	/**
	 * @param string $operation Operation slug.
	 * @return bool
	 */
	public static function requires_approval( $operation ) {
		$config = self::config();
		return in_array( $operation, (array) $config['approval_required'], true );
	}

	/**
	 * @param string $url Outbound URL.
	 * @return bool
	 */
	public static function host_allowed( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$allow = (array) self::config()['egress_allowlist'];
		return in_array( strtolower( $host ), array_map( 'strtolower', $allow ), true );
	}

	/**
	 * @param string $url Endpoint URL whose host should be allowlisted.
	 */
	public static function allow_host( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return;
		}
		$config = self::config();
		$list   = (array) $config['egress_allowlist'];
		if ( ! in_array( $host, $list, true ) ) {
			$list[]                     = $host;
			$config['egress_allowlist'] = array_values( $list );
			update_option( self::OPTION, $config, false );
			self::audit( 'egress.allow', 'success', [ 'host' => $host ] );
		}
	}

	/**
	 * Redact PII before AI provider egress.
	 *
	 * @param string $text User content.
	 * @return string
	 */
	public static function redact( $text ) {
		$text = preg_replace( '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[redacted-email]', $text ) ?? $text;
		$text = preg_replace( '/\b\d{13}\b/', '[redacted-id]', $text ) ?? $text;
		$text = preg_replace( '/(\+?27|0)[\s\-]?\d{2}[\s\-]?\d{3}[\s\-]?\d{4}/', '[redacted-phone]', $text ) ?? $text;
		return $text;
	}

	/**
	 * @param string $bucket Rate limit bucket.
	 * @param int    $limit  Max requests.
	 * @param int    $window Window seconds.
	 * @return bool
	 */
	private static function rate_ok( $bucket, $limit, $window ) {
		$key   = 'bia_rl_' . md5( $bucket . '|' . get_current_user_id() );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, max( 1, $window ) );
		return true;
	}

	/**
	 * @param string               $operation Operation slug.
	 * @param string               $status    Result status.
	 * @param array<string,mixed>  $data      Context payload.
	 */
	public static function audit( $operation, $status, $data = [] ) {
		if ( ! class_exists( 'NGC_Audit' ) ) {
			return;
		}
		NGC_Audit::log(
			'bia_' . sanitize_key( $operation ),
			'bia',
			0,
			$data,
			0,
			[ 'result' => $status ]
		);
	}

	/**
	 * @return string
	 */
	private static function site_host() {
		return (string) ( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ?: 'localhost' );
	}
}
