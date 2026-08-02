<?php
/**
 * Audit log persistence.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit trail.
 */
class NGC_Audit {

	/**
	 * Write an audit log entry.
	 *
	 * @param string               $action      Action slug.
	 * @param string               $object_type Object type.
	 * @param int                  $object_id   Object ID.
	 * @param array<string, mixed> $context     Extra context.
	 * @param int                  $actor_id    Actor user ID (0 = current).
	 * @param array<string, mixed> $meta        Extended fields.
	 */
	public static function log( $action, $object_type, $object_id, $context = [], $actor_id = 0, $meta = [] ) {
		global $wpdb;
		$table = NGC_Database::table( 'audit_log' );
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		$device = '';
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$device = sanitize_text_field( wp_unslash( substr( $_SERVER['HTTP_USER_AGENT'], 0, 191 ) ) );
		}
		$session_id = sanitize_text_field( (string) ( $_COOKIE['session_id'] ?? '' ) );

		$wpdb->insert(
			$table,
			[
				'uuid'           => class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4(),
				'event_id'       => sanitize_text_field( (string) ( $meta['event_id'] ?? wp_generate_uuid4() ) ),
				'actor_user_id'  => (int) $actor_id,
				'actor_type'     => sanitize_key( (string) ( $meta['actor_type'] ?? ( $actor_id ? 'user' : 'system' ) ) ),
				'action'         => sanitize_key( $action ),
				'object_type'    => sanitize_key( $object_type ),
				'object_id'      => (int) $object_id,
				'workflow_key'   => sanitize_key( (string) ( $meta['workflow_key'] ?? '' ) ),
				'old_values'     => wp_json_encode( $meta['old_values'] ?? [] ),
				'new_values'     => wp_json_encode( $meta['new_values'] ?? [] ),
				'result'         => sanitize_key( (string) ( $meta['result'] ?? 'success' ) ),
				'correlation_id' => sanitize_text_field( (string) ( $meta['correlation_id'] ?? '' ) ),
				'context'        => wp_json_encode( $context ),
				'ip_address'     => $ip,
				'device'         => $device,
				'session_id'     => $session_id,
				'created_at'     => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		do_action( 'ngc_audit_logged', $action, $object_type, $object_id, $context, (int) $actor_id, $meta );
		if ( class_exists( 'NGC_Immutable_Audit' ) ) {
			NGC_Immutable_Audit::append(
				(string) $action,
				(string) $object_type,
				(int) $object_id,
				[
					'context'  => $context,
					'actor_id' => (int) $actor_id,
					'meta'     => $meta,
				]
			);
		}
	}

	/**
	 * @param int $limit Max rows.
	 * @return array<int, object>
	 */
	public static function recent( $limit = 50 ) {
		if ( class_exists( 'NGC_Audit_Presenter' ) ) {
			return NGC_Audit_Presenter::unified_recent( $limit );
		}
		return NGC_Audit_Service::search( [ 'limit' => $limit ] );
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Auth hooks registered by NGC_Audit_Service.
	}
}
