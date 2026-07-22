<?php
/**
 * Delivery operations screen.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Events_Page {
	public static function render() {
		if ( ! NGTAI_Access::can_manage() ) {
			wp_die( esc_html__( 'Insufficient permission.', 'nextgentutors-ai-integration' ) );
		}
		if ( isset( $_POST['ngtai_event_action'] ) ) {
			check_admin_referer( 'ngtai_event_action' );
			$id     = absint( $_POST['delivery_id'] ?? 0 );
			$action = sanitize_key( wp_unslash( $_POST['ngtai_event_action'] ) );
			if ( 'retry' === $action ) {
				$row = NGTAI_Delivery_Repository::get( $id );
				NGTAI_Delivery_Repository::schedule_retry( $id, (int) ( $row['attempt_count'] ?? 0 ) + 1, 'manual_retry', 0, 0 );
			} elseif ( 'cancel' === $action ) {
				NGTAI_Delivery_Repository::mark_cancelled( $id );
			}
		}
		$status = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) );
		$type   = sanitize_text_field( wp_unslash( $_GET['event_type'] ?? '' ) );
		$rows   = NGTAI_Delivery_Repository::list_recent( [ 'status' => $status, 'event_type' => $type, 'limit' => 100 ] );
		echo '<div class="wrap ngtai-admin" data-testid="ngtai-events-page"><h1>' . esc_html__( 'AI Event Deliveries', 'nextgentutors-ai-integration' ) . '</h1>';
		echo '<form method="get"><input type="hidden" name="page" value="ngtai-events"><input name="status" value="' . esc_attr( $status ) . '" placeholder="' . esc_attr__( 'Status', 'nextgentutors-ai-integration' ) . '"> <input name="event_type" value="' . esc_attr( $type ) . '" placeholder="' . esc_attr__( 'Event type', 'nextgentutors-ai-integration' ) . '"> <button class="button">' . esc_html__( 'Filter', 'nextgentutors-ai-integration' ) . '</button></form>';
		echo '<table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__( 'Event', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Status', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Payload', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Actions', 'nextgentutors-ai-integration' ) . '</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			$payload = json_decode( (string) ( $row['payload_json'] ?? '' ), true );
			echo '<tr><td>' . absint( $row['id'] ?? 0 ) . '</td><td>' . esc_html( (string) ( $row['event_type'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $row['status'] ?? '' ) ) . '</td><td><details><summary>' . esc_html__( 'Inspect', 'nextgentutors-ai-integration' ) . '</summary><pre>' . esc_html( wp_json_encode( NGTAI_Logger::scrub( is_array( $payload ) ? $payload : [] ), JSON_PRETTY_PRINT ) ) . '</pre></details></td><td><form method="post">';
			wp_nonce_field( 'ngtai_event_action' );
			echo '<input type="hidden" name="delivery_id" value="' . absint( $row['id'] ?? 0 ) . '"><button class="button" name="ngtai_event_action" value="retry">' . esc_html__( 'Retry', 'nextgentutors-ai-integration' ) . '</button> <button class="button" name="ngtai_event_action" value="cancel">' . esc_html__( 'Cancel', 'nextgentutors-ai-integration' ) . '</button></form></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
