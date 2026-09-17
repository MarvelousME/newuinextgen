<?php
/**
 * Notification adapter for session lifecycle.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workflow/email notifications keyed by correlation + event.
 */
class NGC_Session_Notification_Adapter implements NGC_Notification_Provider_Interface {

	/**
	 * @param string               $event   Event.
	 * @param array<string, mixed> $session Session.
	 * @param array<string, mixed> $context Extra.
	 * @return true|WP_Error
	 */
	public function notify( $event, array $session, array $context = [] ) {
		$key = 'notify:' . sanitize_key( $event ) . ':' . (string) ( $session['correlation_id'] ?? $session['id'] ?? '' );
		if ( class_exists( 'NGC_Idempotency' ) ) {
			$begun = NGC_Idempotency::begin( $key, $key, 'session_notify' );
			if ( is_wp_error( $begun ) ) {
				return $begun;
			}
			if ( 'replay' === ( $begun['status'] ?? '' ) ) {
				NGC_Session_Observability::success( 'duplicate_event_suppressed_total', $session + [ 'operation' => 'notify' ] );
				return true;
			}
		}
		if ( class_exists( 'NGC_Workflows' ) ) {
			NGC_Workflows::dispatch(
				$event,
				array_merge(
					[
						'session_id'      => (string) ( $session['id'] ?? '' ),
						'correlation_id'  => (string) ( $session['correlation_id'] ?? '' ),
						'booking_id'      => (string) ( $session['booking_id'] ?? '' ),
						'order_id'        => (string) ( $session['order_id'] ?? '' ),
						'student_user_id' => (string) ( $session['student_user_id'] ?? '' ),
						'tutor_user_id'   => (string) ( $session['tutor_user_id'] ?? '' ),
						'parent_user_id'  => (string) ( $session['parent_user_id'] ?? '' ),
					],
					$context
				)
			);
		}
		if ( class_exists( 'NGC_Idempotency' ) ) {
			NGC_Idempotency::commit( $key, [ 'ok' => true ] );
		}
		return true;
	}
}
