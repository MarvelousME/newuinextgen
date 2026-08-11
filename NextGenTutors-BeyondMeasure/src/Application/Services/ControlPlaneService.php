<?php
declare(strict_types=1);

namespace NGTBM\Application\Services;

use NGTBM\Domain\Audit\AuditLog;
use NGTBM\Domain\Authorization\PolicyGate;
use NGTBM\Domain\Authorization\RoleCatalog;
use NGTBM\Domain\Configuration\ConfigurationService;
use NGTBM\Domain\Health\HealthAggregator;
use NGTBM\Domain\Notification\NotificationStore;
use NGTBM\Domain\Resource\ResourceCatalog;
use NGTBM\Infrastructure\Integrations\ArchitectureLoader;
use NGTBM\Infrastructure\Integrations\CompanionClient;
use NGTBM\Infrastructure\REST\Envelope;
use NGTBM\Infrastructure\WordPress\Plugin;

/**
 * Application services behind REST.
 */
final class ControlPlaneService {

	public static function subsystems(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_subsystem_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( Plugin::instance()->subsystems()->to_list() );
	}

	public static function subsystem( string $id ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_subsystem_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		$item = Plugin::instance()->subsystems()->get( $id );
		if ( ! $item ) {
			return Envelope::error( 'SUBSYSTEM_NOT_FOUND', 'Subsystem not found.', 404 );
		}
		return Envelope::success( $item->to_array() );
	}

	public static function set_enabled( string $id, bool $enabled, \WP_REST_Request $request ): \WP_REST_Response {
		$cap  = $enabled ? 'ngt_subsystem_enable' : 'ngt_subsystem_disable';
		$gate = PolicyGate::authorize( $cap );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		$registry = Plugin::instance()->subsystems();
		$current  = $registry->get( $id );
		if ( ! $current ) {
			return Envelope::error( 'SUBSYSTEM_NOT_FOUND', 'Subsystem not found.', 404 );
		}
		$impact = self::disable_impact( $id );
		$json   = $request->get_json_params();
		$mode   = sanitize_key( (string) ( is_array( $json ) ? ( $json['mode'] ?? '' ) : '' ) );
		if ( $mode === '' ) {
			$mode = sanitize_key( (string) $request->get_param( 'mode' ) );
		}
		if ( ! $enabled && $mode === '' ) {
			return Envelope::success(
				[
					'requiresConfirmation' => true,
					'impact'               => $impact,
				]
			);
		}
		$updated = $registry->set_enabled( $id, $enabled );
		AuditLog::write( $enabled ? 'subsystem.enable' : 'subsystem.disable', 'subsystem', $id, [ 'mode' => $mode, 'impact' => $impact ] );
		return Envelope::success( [ 'subsystem' => $updated?->to_array(), 'impact' => $impact ] );
	}

	/**
	 * @return array{affected:list<string>,notAffected:list<string>,running:int}
	 */
	private static function disable_impact( string $id ): array {
		$affected = [];
		$all      = Plugin::instance()->subsystems()->all();
		foreach ( $all as $sub ) {
			if ( in_array( $id, $sub->depends_on, true ) || in_array( $id, $sub->provides, true ) ) {
				$affected[] = $sub->name;
			}
			foreach ( $sub->depends_on as $dep ) {
				if ( $dep === $id ) {
					$affected[] = $sub->name;
				}
			}
		}
		$affected = array_values( array_unique( $affected ) );
		$safe     = [ 'Tutor registration', 'Tutor approval', 'Booking', 'Lessons', 'Payments' ];
		return [
			'affected'    => $affected,
			'notAffected' => $safe,
			'running'     => 0,
		];
	}

	public static function health(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_health_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( HealthAggregator::snapshot( Plugin::instance()->subsystems() ) );
	}

	public static function graph(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_subsystem_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( ArchitectureLoader::dependency_graph( Plugin::instance()->subsystems() ) );
	}

	public static function access_matrix(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_access_matrix_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( RoleCatalog::access_matrix() );
	}

	public static function notifications( \WP_REST_Request $request ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_cp_access' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( NotificationStore::list( (int) $request->get_param( 'limit' ) ?: 50 ) );
	}

	public static function ack_notification( int $id ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_notifications_manage' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		NotificationStore::acknowledge( $id );
		return Envelope::success( [ 'id' => $id, 'status' => 'acked' ] );
	}

	public static function get_config( string $subsystem_id ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_subsystem_configure' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( ConfigurationService::get( $subsystem_id ) );
	}

	public static function save_config( string $subsystem_id, \WP_REST_Request $request ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_config_manage' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			return Envelope::error( 'INVALID_CONFIG', 'Configuration payload required.' );
		}
		$result = ConfigurationService::save_transactional( $subsystem_id, $payload );
		if ( is_wp_error( $result ) ) {
			return Envelope::from_wp_error( $result );
		}
		return Envelope::success( $result );
	}

	public static function resources_list( string $resource_id, \WP_REST_Request $request ): \WP_REST_Response {
		$schema = ResourceCatalog::get( $resource_id );
		if ( ! $schema ) {
			return Envelope::error( 'RESOURCE_NOT_FOUND', 'Unknown resource.', 404 );
		}
		$gate = PolicyGate::authorize( (string) $schema['permissions']['read'] );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		if ( $resource_id === 'talent-evaluation' ) {
			return Envelope::success( CompanionClient::talent_evaluations( $request ) );
		}
		return Envelope::success( [ 'items' => [], 'total' => 0 ] );
	}

	public static function talent_explain( string $id ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_talent_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		$data = CompanionClient::talent_explain( $id );
		if ( is_wp_error( $data ) ) {
			return Envelope::from_wp_error( $data );
		}
		return Envelope::success( $data );
	}

	public static function talent_stats(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_talent_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( CompanionClient::talent_stats() );
	}

	public static function audit( \WP_REST_Request $request ): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_audit_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( AuditLog::list( (int) $request->get_param( 'limit' ) ?: 50 ) );
	}

	public static function nav(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_cp_access' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( NavigationCatalog::tree() );
	}

	public static function queues(): \WP_REST_Response {
		$gate = PolicyGate::authorize( 'ngt_health_read' );
		if ( is_wp_error( $gate ) ) {
			return Envelope::from_wp_error( $gate );
		}
		return Envelope::success( CompanionClient::queue_snapshot() );
	}
}
