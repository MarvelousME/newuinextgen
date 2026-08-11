<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\REST;

use NGTBM\Application\Services\ControlPlaneService;
use NGTBM\Domain\Authorization\PolicyGate;

/**
 * REST routes for nextgentutors-control/v1.
 */
final class RestKernel {

	public static function register(): void {
		$ns = NGTBM_REST_NAMESPACE;

		register_rest_route( $ns, '/nav', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::nav(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_cp_access' ),
		] );

		register_rest_route( $ns, '/subsystems', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::subsystems(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_read' ),
		] );

		register_rest_route( $ns, '/subsystems/(?P<id>[a-z0-9\-_]+)', [
			'methods'             => 'GET',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::subsystem( (string) $r['id'] ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_read' ),
		] );

		register_rest_route( $ns, '/subsystems/(?P<id>[a-z0-9\-_]+)/enable', [
			'methods'             => 'POST',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::set_enabled( (string) $r['id'], true, $r ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_enable' ),
		] );

		register_rest_route( $ns, '/subsystems/(?P<id>[a-z0-9\-_]+)/disable', [
			'methods'             => 'POST',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::set_enabled( (string) $r['id'], false, $r ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_disable' ),
		] );

		register_rest_route( $ns, '/health', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::health(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_health_read' ),
		] );

		register_rest_route( $ns, '/dependency-graph', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::graph(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_read' ),
		] );

		register_rest_route( $ns, '/access-matrix', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::access_matrix(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_access_matrix_read' ),
		] );

		register_rest_route( $ns, '/notifications', [
			'methods'             => 'GET',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::notifications( $r ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_cp_access' ),
		] );

		register_rest_route( $ns, '/notifications/(?P<id>\d+)/ack', [
			'methods'             => 'POST',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::ack_notification( (int) $r['id'] ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_notifications_manage' ),
		] );

		register_rest_route( $ns, '/configuration/(?P<id>[a-z0-9\-_]+)', [
			[
				'methods'             => 'GET',
				'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::get_config( (string) $r['id'] ),
				'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_configure' ),
			],
			[
				'methods'             => 'PUT',
				'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::save_config( (string) $r['id'], $r ),
				'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_config_manage' ),
			],
		] );

		register_rest_route( $ns, '/resources/(?P<resource>[a-z0-9\-_]+)', [
			'methods'             => 'GET',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::resources_list( (string) $r['resource'], $r ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_cp_access' ),
		] );

		register_rest_route( $ns, '/talent/stats', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::talent_stats(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_talent_read' ),
		] );

		register_rest_route( $ns, '/talent/evaluations/(?P<id>[A-Za-z0-9\-_]+)/explain', [
			'methods'             => 'GET',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::talent_explain( (string) $r['id'] ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_talent_read' ),
		] );

		register_rest_route( $ns, '/audit', [
			'methods'             => 'GET',
			'callback'            => static fn( \WP_REST_Request $r ) => ControlPlaneService::audit( $r ),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_audit_read' ),
		] );

		register_rest_route( $ns, '/queues', [
			'methods'             => 'GET',
			'callback'            => static fn() => ControlPlaneService::queues(),
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_health_read' ),
		] );

		register_rest_route( $ns, '/capabilities', [
			'methods'             => 'GET',
			'callback'            => static function () {
				$subs = \NGTBM\Infrastructure\WordPress\Plugin::instance()->subsystems()->to_list();
				$caps = [];
				foreach ( $subs as $s ) {
					foreach ( (array) ( $s['capabilities'] ?? [] ) as $c ) {
						$caps[] = [ 'id' => $c, 'subsystem' => $s['id'] ];
					}
				}
				return Envelope::success( $caps );
			},
			'permission_callback' => static fn() => PolicyGate::require_cap( 'ngt_subsystem_read' ),
		] );
	}
}
