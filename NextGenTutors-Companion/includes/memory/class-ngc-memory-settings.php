<?php
/**
 * Memory settings — flags default OFF; Proxy-as-gateway forbidden.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configuration for Bridge memory subsystem.
 */
final class NGC_Memory_Settings {

	public const OPTION = 'ngc_memory_settings_v1';

	public const MODE_DISABLED    = 'DISABLED';
	public const MODE_LOCAL       = 'LOCAL';
	public const MODE_REMOTE      = 'REMOTE';
	public const MODE_DEGRADED    = 'DEGRADED';
	public const MODE_HEALTHY     = 'HEALTHY';
	public const MODE_MAINTENANCE = 'MAINTENANCE';

	/**
	 * Defaults — safe: memory off, proxy off, skills/wiki/codegraph off.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return [
			'enabled'                => false,
			'mode'                   => self::MODE_DISABLED,
			'provider'               => 'tencentdb',
			'core_base_url'          => '',
			'knowledge_base_url'     => '',
			'service_id_strategy'    => 'tenant',
			'gateway_bearer_ref'     => '',
			'admin_user_key_ref'     => '',
			'timeout_ms'             => 2500,
			'retry'                  => 1,
			'retrieve_enabled'       => false,
			'write_enabled'          => false,
			'skills_enabled'         => false,
			'wiki_enabled'           => false,
			'codegraph_enabled'      => false,
			'proxy_enabled'          => false,
			'allow_long_term_minors' => false,
			'max_retrieve_items'     => 8,
			'max_retrieve_chars'     => 4000,
			'sqlite_ha_acknowledged' => false,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		$cfg = array_merge( self::defaults(), $stored );
		// Hard invariant: Proxy must never be Bridge LLM gateway.
		$cfg['proxy_enabled'] = false;
		return $cfg;
	}

	/**
	 * @param array<string,mixed> $patch Partial settings.
	 * @return array<string,mixed>
	 */
	public static function update( array $patch ) {
		$cfg = self::get();
		foreach ( $patch as $k => $v ) {
			if ( array_key_exists( $k, self::defaults() ) ) {
				$cfg[ $k ] = $v;
			}
		}
		$cfg['proxy_enabled'] = false;
		update_option( self::OPTION, $cfg, false );
		return $cfg;
	}

	/**
	 * Whether memory subsystem may run (enabled and not DISABLED).
	 *
	 * @return bool
	 */
	public static function is_active() {
		$cfg = self::get();
		if ( empty( $cfg['enabled'] ) ) {
			return false;
		}
		$mode = (string) ( $cfg['mode'] ?? self::MODE_DISABLED );
		return ! in_array( $mode, [ self::MODE_DISABLED, self::MODE_MAINTENANCE ], true );
	}

	/**
	 * @return bool
	 */
	public static function retrieve_allowed() {
		$cfg = self::get();
		return self::is_active() && ! empty( $cfg['retrieve_enabled'] );
	}

	/**
	 * @return bool
	 */
	public static function write_allowed() {
		$cfg = self::get();
		return self::is_active() && ! empty( $cfg['write_enabled'] );
	}

	/**
	 * Resolve bearer secret from vault ref (never log).
	 *
	 * @return string
	 */
	public static function gateway_bearer() {
		$ref = (string) ( self::get()['gateway_bearer_ref'] ?? '' );
		if ( '' === $ref || ! class_exists( 'NGC_Secret_Vault' ) ) {
			return '';
		}
		$plain = NGC_Secret_Vault::reveal( $ref );
		return is_wp_error( $plain ) ? '' : (string) $plain;
	}

	/**
	 * Resolve mapped user_key from vault ref.
	 *
	 * @param string $ref Vault ref.
	 * @return string
	 */
	public static function reveal_user_key( $ref ) {
		$ref = (string) $ref;
		if ( '' === $ref || ! class_exists( 'NGC_Secret_Vault' ) ) {
			return '';
		}
		$plain = NGC_Secret_Vault::reveal( $ref );
		return is_wp_error( $plain ) ? '' : (string) $plain;
	}
}
