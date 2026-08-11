<?php
/**
 * RAD Capability Registry — merges architecture/capabilities with runtime catalogues.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central capability query API.
 */
final class NGC_Capability_Registry {

	/**
	 * @var array<string, array<string, mixed>>
	 */
	private static $capabilities = [];

	/**
	 * @var bool
	 */
	private static $loaded = false;

	/**
	 * Bootstrap.
	 */
	public static function init() {
		self::load();
	}

	/**
	 * Load declared capabilities + merge hooks.
	 */
	public static function load() {
		self::$capabilities = [];
		$dir = NGC_Subsystem_Registry::architecture_root() . DIRECTORY_SEPARATOR . 'capabilities';
		if ( is_dir( $dir ) ) {
			$files = glob( $dir . DIRECTORY_SEPARATOR . '*.json' ) ?: [];
			foreach ( $files as $file ) {
				$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( false === $raw ) {
					continue;
				}
				$data = json_decode( $raw, true );
				if ( ! is_array( $data ) ) {
					continue;
				}
				$list = [];
				if ( isset( $data['capabilities'] ) && is_array( $data['capabilities'] ) ) {
					$list = $data['capabilities'];
				} elseif ( isset( $data['capabilityId'] ) ) {
					$list = [ $data ];
				}
				foreach ( $list as $cap ) {
					if ( ! is_array( $cap ) || empty( $cap['capabilityId'] ) ) {
						continue;
					}
					if ( ! isset( $cap['requiredPermissions'] ) || ! is_array( $cap['requiredPermissions'] ) ) {
						continue; // ARCH-004 fail closed for undeclared perms.
					}
					self::$capabilities[ (string) $cap['capabilityId'] ] = $cap;
				}
			}
		}

		/**
		 * Allow runtime catalogues (tool gateway, authz) to register additional capabilities.
		 *
		 * @param array<string, array<string, mixed>> $capabilities Map by id.
		 */
		self::$capabilities = apply_filters( 'ngc_capability_registry', self::$capabilities );
		self::$loaded       = true;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		if ( ! self::$loaded ) {
			self::load();
		}
		return self::$capabilities;
	}

	/**
	 * @param string $id Capability id.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		$all = self::all();
		return $all[ $id ] ?? null;
	}

	/**
	 * @param string $id Capability id.
	 * @return bool
	 */
	public static function has( $id ) {
		return null !== self::get( $id );
	}

	/**
	 * Capabilities provided by a subsystem.
	 *
	 * @param string $subsystem_id Subsystem id.
	 * @return array<string, array<string, mixed>>
	 */
	public static function for_provider( $subsystem_id ) {
		$out = [];
		foreach ( self::all() as $id => $cap ) {
			if ( ( $cap['provider'] ?? '' ) === $subsystem_id ) {
				$out[ $id ] = $cap;
			}
		}
		return $out;
	}
}
