<?php
/**
 * Soft single-site tenant context.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tenant = current blog id (default 1). No Multisite rewrite.
 */
final class NGC_Tenant_Context {

	/** @var int|null */
	private static $override = null;

	/**
	 * Init.
	 */
	public static function init() {
		// No hooks required; helpers used by platform services.
	}

	/**
	 * Current tenant id.
	 *
	 * @return int
	 */
	public static function id() {
		if ( null !== self::$override ) {
			return (int) self::$override;
		}
		if ( function_exists( 'get_current_blog_id' ) ) {
			$id = (int) get_current_blog_id();
			return $id > 0 ? $id : 1;
		}
		return 1;
	}

	/**
	 * Run callback under a tenant override (tests).
	 *
	 * @param int      $tenant_id Tenant.
	 * @param callable $cb        Callback.
	 * @return mixed
	 */
	public static function run_as( $tenant_id, $cb ) {
		$prev           = self::$override;
		self::$override = (int) $tenant_id;
		try {
			return $cb();
		} finally {
			self::$override = $prev;
		}
	}

	/**
	 * SQL fragment AND tenant_id = N (prepared value separate).
	 *
	 * @return int
	 */
	public static function sql_id() {
		return self::id();
	}
}
