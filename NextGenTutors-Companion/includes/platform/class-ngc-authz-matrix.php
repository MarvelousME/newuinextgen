<?php
/**
 * Authorization matrix + decision audit.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role → capability matrix for platform resources.
 */
final class NGC_Authz_Matrix {

	/**
	 * Capability map by logical role.
	 *
	 * @return array<string,string[]>
	 */
	public static function matrix() {
		return [
			'Student'     => [ 'read', 'ngc_book_session', 'ngc_view_own' ],
			'Parent'      => [ 'read', 'ngc_book_session', 'ngc_view_own', 'ngc_manage_children' ],
			'Tutor'       => [ 'read', 'ngc_tutor_dashboard', 'ngc_view_own', 'ngc_manage_availability' ],
			'Admin'       => [ 'manage_options', 'ngc_manage_platform', 'ngc_manage_safeguarding', 'ngc_manage_fraud', 'ngc_view_finance' ],
			'Finance'     => [ 'ngc_view_finance', 'ngc_manage_payouts', 'ngc_view_ledger' ],
			'Support'     => [ 'ngc_manage_support', 'ngc_view_own' ],
			'Moderator'   => [ 'ngc_manage_safeguarding', 'ngc_manage_fraud', 'ngc_moderate_content' ],
			'Affiliate'   => [ 'ngc_view_affiliate', 'read' ],
			'Franchise'   => [ 'ngc_view_franchise', 'ngc_view_finance' ],
			'SuperAdmin'  => [ 'manage_options', 'ngc_manage_platform', 'ngc_manage_safeguarding', 'ngc_manage_fraud', 'ngc_view_finance', 'ngc_manage_payouts', 'ngc_view_ledger' ],
		];
	}

	/**
	 * WP role slug mapping.
	 *
	 * @return array<string,string>
	 */
	public static function wp_role_map() {
		return [
			'Student'    => 'ngc_student',
			'Parent'     => 'ngc_parent',
			'Tutor'      => 'ngc_tutor',
			'Admin'      => 'administrator',
			'Finance'    => 'ngc_finance',
			'Support'    => 'ngc_support',
			'Moderator'  => 'ngc_moderator',
			'Affiliate'  => 'ngc_affiliate',
			'Franchise'  => 'ngc_franchise',
			'SuperAdmin' => 'administrator',
		];
	}

	/**
	 * Init — ensure caps on roles.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'ensure_roles_caps' ], 30 );
	}

	/**
	 * Ensure platform caps exist on mapped roles.
	 */
	public static function ensure_roles_caps() {
		$map = self::wp_role_map();
		$mx  = self::matrix();
		foreach ( $mx as $logical => $caps ) {
			$slug = $map[ $logical ] ?? '';
			if ( $slug === '' ) {
				continue;
			}
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
		// Always grant platform caps to administrator.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( [ 'ngc_manage_platform', 'ngc_manage_safeguarding', 'ngc_manage_fraud', 'ngc_view_finance', 'ngc_manage_payouts', 'ngc_view_ledger' ] as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Authorize and audit.
	 *
	 * @param string $capability Cap.
	 * @param string $resource   Resource.
	 * @param int    $resource_id Id.
	 * @return bool
	 */
	public static function can( $capability, $resource = '', $resource_id = 0 ) {
		$user_id = get_current_user_id();
		$allowed = user_can( $user_id, $capability ) || user_can( $user_id, 'manage_options' );
		self::audit( $user_id, $resource, $resource_id, $capability, $allowed ? 'allow' : 'deny' );
		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( 'authz_decisions_total', 1, [ 'decision' => $allowed ? 'allow' : 'deny' ] );
		}
		return $allowed;
	}

	/**
	 * Record authz decision.
	 *
	 * @param int    $actor_id Actor.
	 * @param string $resource Resource.
	 * @param int    $resource_id Id.
	 * @param string $capability Cap.
	 * @param string $decision allow|deny.
	 * @param string $reason Reason.
	 */
	public static function audit( $actor_id, $resource, $resource_id, $capability, $decision, $reason = '' ) {
		global $wpdb;
		$wpdb->insert(
			NGC_Platform_Schema::table( 'authz_audit' ),
			[
				'tenant_id'   => NGC_Tenant_Context::id(),
				'actor_id'    => (int) $actor_id,
				'resource'    => sanitize_key( (string) $resource ),
				'resource_id' => (int) $resource_id,
				'capability'  => sanitize_key( (string) $capability ),
				'decision'    => 'allow' === $decision ? 'allow' : 'deny',
				'reason'      => substr( (string) $reason, 0, 191 ),
				'created_at'  => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Guard: resource belongs to current tenant.
	 *
	 * @param int $row_tenant_id Tenant on row.
	 * @return bool
	 */
	public static function same_tenant( $row_tenant_id ) {
		$ok = (int) $row_tenant_id === NGC_Tenant_Context::id();
		if ( ! $ok ) {
			self::audit( get_current_user_id(), 'tenant', (int) $row_tenant_id, 'ngc_tenant_scope', 'deny', 'cross_tenant' );
		}
		return $ok;
	}
}
