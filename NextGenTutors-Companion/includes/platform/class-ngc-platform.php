<?php
/**
 * Platform kernel bootstrap — durable queue, ledger, audit, tenant, authz.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers platform services and cron.
 */
final class NGC_Platform {

	public const OPTION_AUTHORITY   = 'ngc_workflow_authority_v1';
	public const OPTION_KILL_SWITCH = 'ngc_workflow_authority_kill';
	public const DB_VERSION         = '1.0.0';

	/**
	 * Hook registration.
	 */
	public static function init() {
		NGC_Platform_Schema::maybe_install();
		NGC_Tenant_Context::init();
		NGC_Durable_Queue::init();
		NGC_Queue_Worker::init();
		NGC_Idempotency::init();
		NGC_Workflow_Authority::init();
		NGC_Ledger::init();
		NGC_Reconciliation::init();
		NGC_Immutable_Audit::init();
		NGC_Worm_Export::init();
		NGC_Authz_Matrix::init();
		NGC_Platform_Observability::init();
		NGC_Subsystem_Registry::init();
		NGC_Capability_Registry::init();
		NGC_Policy_Bridge::init();
		NGC_Platform_Kernel_Admin::init();

		if ( ! get_option( self::OPTION_AUTHORITY, null ) ) {
			$default = ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) ? '1' : '1';
			update_option( self::OPTION_AUTHORITY, $default, false );
		}
	}

	/**
	 * Whether workflow authority is active (single executor).
	 *
	 * @return bool
	 */
	public static function authority_enabled() {
		if ( '1' === (string) get_option( self::OPTION_KILL_SWITCH, '' ) ) {
			return false;
		}
		return '1' === (string) get_option( self::OPTION_AUTHORITY, '1' );
	}
}
