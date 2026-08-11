<?php
declare(strict_types=1);

namespace NGTBM\Domain\Authorization;

/**
 * Role bundles (roles ≠ capabilities).
 */
final class RoleCatalog {

	/**
	 * @return array<string,array{label:string,caps:list<string>}>
	 */
	public static function definitions(): array {
		$all = CapabilityCatalog::ALL;
		return [
			'ngt_platform_admin' => [
				'label' => 'NGT Platform Administrator',
				'caps'  => $all,
			],
			'ngt_ops_manager' => [
				'label' => 'NGT Operations Manager',
				'caps'  => [
					'ngt_cp_access',
					'ngt_subsystem_read',
					'ngt_health_read',
					'ngt_audit_read',
					'ngt_dlq_replay',
					'ngt_notifications_manage',
				],
			],
			'ngt_tutor_manager' => [
				'label' => 'NGT Tutor Manager',
				'caps'  => [
					'ngt_cp_access',
					'ngt_talent_read',
					'ngt_talent_create',
					'ngt_talent_update',
					'ngt_talent_evaluate',
					'ngt_talent_rank',
					'ngt_talent_override',
					'ngt_talent_export',
					'ngt_subsystem_read',
					'ngt_health_read',
					'ngt_audit_read',
					'ngt_access_matrix_read',
				],
			],
			'ngt_safeguarding' => [
				'label' => 'NGT Safeguarding Officer',
				'caps'  => [
					'ngt_cp_access',
					'ngt_talent_read',
					'ngt_audit_read',
					'ngt_health_read',
				],
			],
			'ngt_finance_manager' => [
				'label' => 'NGT Finance Manager',
				'caps'  => [
					'ngt_cp_access',
					'ngt_subsystem_read',
					'ngt_health_read',
					'ngt_audit_read',
				],
			],
			'ngt_crm_manager' => [
				'label' => 'NGT CRM Manager',
				'caps'  => [
					'ngt_cp_access',
					'ngt_subsystem_read',
					'ngt_health_read',
					'ngt_notifications_manage',
				],
			],
			'ngt_ai_admin' => [
				'label' => 'NGT AI Administrator',
				'caps'  => [
					'ngt_cp_access',
					'ngt_talent_read',
					'ngt_talent_evaluate',
					'ngt_talent_rank',
					'ngt_talent_configure',
					'ngt_subsystem_read',
					'ngt_subsystem_configure',
					'ngt_health_read',
					'ngt_audit_read',
					'ngt_config_manage',
					'ngt_access_matrix_read',
				],
			],
			'ngt_auditor' => [
				'label' => 'NGT Auditor',
				'caps'  => [
					'ngt_cp_access',
					'ngt_talent_read',
					'ngt_subsystem_read',
					'ngt_audit_read',
					'ngt_health_read',
					'ngt_access_matrix_read',
				],
			],
			'ngt_support' => [
				'label' => 'NGT Support',
				'caps'  => [
					'ngt_cp_access',
					'ngt_subsystem_read',
					'ngt_health_read',
					'ngt_notifications_manage',
				],
			],
		];
	}

	public static function ensure_roles(): void {
		foreach ( self::definitions() as $slug => $def ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				add_role( $slug, $def['label'], [ 'read' => true ] );
				$role = get_role( $slug );
			}
			if ( ! $role ) {
				continue;
			}
			foreach ( $def['caps'] as $cap ) {
				$role->add_cap( $cap );
			}
		}
		CapabilityCatalog::register_caps();
	}

	/**
	 * Access matrix: role slug → cap → bool.
	 *
	 * @return array{roles:list<array{id:string,label:string}>,capabilities:list<string>,matrix:array<string,array<string,bool>>}
	 */
	public static function access_matrix(): array {
		$defs = self::definitions();
		$caps = CapabilityCatalog::ALL;
		$roles = [];
		$matrix = [];
		foreach ( $defs as $slug => $def ) {
			$roles[] = [ 'id' => $slug, 'label' => $def['label'] ];
			$row     = [];
			$set     = array_fill_keys( $def['caps'], true );
			foreach ( $caps as $cap ) {
				$row[ $cap ] = isset( $set[ $cap ] );
			}
			$matrix[ $slug ] = $row;
		}
		return [
			'roles'        => $roles,
			'capabilities' => $caps,
			'matrix'       => $matrix,
		];
	}
}
