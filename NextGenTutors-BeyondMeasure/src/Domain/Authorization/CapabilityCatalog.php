<?php
declare(strict_types=1);

namespace NGTBM\Domain\Authorization;

/**
 * Granular capability catalog (authority atoms).
 */
final class CapabilityCatalog {

	public const ALL = [
		'ngt_cp_access',
		'ngt_talent_read',
		'ngt_talent_create',
		'ngt_talent_update',
		'ngt_talent_delete',
		'ngt_talent_evaluate',
		'ngt_talent_rank',
		'ngt_talent_configure',
		'ngt_talent_override',
		'ngt_talent_export',
		'ngt_subsystem_read',
		'ngt_subsystem_configure',
		'ngt_subsystem_enable',
		'ngt_subsystem_disable',
		'ngt_audit_read',
		'ngt_health_read',
		'ngt_dlq_replay',
		'ngt_access_matrix_read',
		'ngt_access_matrix_manage',
		'ngt_notifications_manage',
		'ngt_config_manage',
	];

	public static function register_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::ALL as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * @return list<string>
	 */
	public static function all(): array {
		return self::ALL;
	}
}
