<?php
/**
 * Internal module registry — maps domain modules to lazy-load bootstrap stubs.
 *
 * Does not move or re-home domain classes; bootstraps are no-op until Agents G–J.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists Companion domain modules and loads each bootstrap once.
 */
final class NGC_Module_Registry {

	/**
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Registered modules (id → metadata).
	 *
	 * @return array<string, array{id:string,path:string,bootstrap:string}>
	 */
	public static function modules() {
		$base = NGC_PLUGIN_DIR . 'includes/';

		return [
			'matching'     => [
				'id'        => 'matching',
				'path'      => $base . 'matching/',
				'bootstrap' => $base . 'matching/bootstrap.php',
			],
			'payments'     => [
				'id'        => 'payments',
				'path'      => $base . 'payments/',
				'bootstrap' => $base . 'payments/bootstrap.php',
			],
			'ai'           => [
				'id'        => 'ai',
				'path'      => $base . 'ai/',
				'bootstrap' => $base . 'ai/bootstrap.php',
			],
			'integrations' => [
				'id'        => 'integrations',
				'path'      => $base . 'integrations/',
				'bootstrap' => $base . 'integrations/bootstrap.php',
			],
			'platform'     => [
				'id'        => 'platform',
				'path'      => $base . 'platform/',
				'bootstrap' => $base . 'platform/module-bootstrap.php',
			],
		];
	}

	/**
	 * Lazy-load each module bootstrap (idempotent; no class-load behavior change).
	 *
	 * @return bool True when boot has completed (including subsequent calls).
	 */
	public static function boot() {
		if ( self::$booted ) {
			return true;
		}

		self::$booted = true;

		foreach ( self::modules() as $module ) {
			$file = $module['bootstrap'];
			if ( ! is_string( $file ) || ! is_readable( $file ) ) {
				continue;
			}
			require_once $file;
		}

		return true;
	}

	/**
	 * Whether boot() has already run.
	 *
	 * @return bool
	 */
	public static function is_booted() {
		return self::$booted;
	}

	/**
	 * Lookup a single module by id.
	 *
	 * @param string $id Module id.
	 * @return array{id:string,path:string,bootstrap:string}|null
	 */
	public static function get( $id ) {
		$all = self::modules();
		return $all[ $id ] ?? null;
	}
}
