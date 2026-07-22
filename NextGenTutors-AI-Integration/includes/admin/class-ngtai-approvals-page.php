<?php
/**
 * Human approval queue.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Approvals_Page {
	public static function render() {
		if ( ! NGTAI_Access::can_approve() ) {
			wp_die( esc_html__( 'Insufficient permission.', 'nextgentutors-ai-integration' ) );
		}
		if ( isset( $_POST['ngtai_decision'] ) ) {
			check_admin_referer( 'ngtai_decide' );
			$decision = sanitize_key( wp_unslash( $_POST['ngtai_decision'] ) );
			$result   = NGTAI_Callback_Controller::decide_approval(
				sanitize_text_field( wp_unslash( $_POST['approval_id'] ?? '' ) ),
				'approve' === $decision,
				sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ),
				get_current_user_id()
			);
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			}
		}
		$status = sanitize_key( wp_unslash( $_GET['status'] ?? 'pending' ) );
		$status = in_array( $status, [ 'pending', 'approved', 'denied' ], true ) ? $status : 'pending';
		global $wpdb;
		$table = NGTAI_Database::table( 'approvals' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status=%s ORDER BY id DESC LIMIT 100", $status ), ARRAY_A );
		echo '<div class="wrap ngtai-admin" data-testid="ngtai-approvals-page"><h1>' . esc_html__( 'Agent Approvals', 'nextgentutors-ai-integration' ) . '</h1><p>';
		foreach ( [ 'pending', 'approved', 'denied' ] as $tab ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'page' => 'ngtai-approvals', 'status' => $tab ], admin_url( 'admin.php' ) ) ) . '">' . esc_html( ucfirst( $tab ) ) . '</a> ';
		}
		echo '</p><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Action', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Agent / subject / risk', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Requested', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Decision', 'nextgentutors-ai-integration' ) . '</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			$payload = json_decode( (string) $row['payload_json'], true );
			$payload = is_array( $payload ) ? NGTAI_Logger::scrub( $payload ) : [];
			echo '<tr><td><strong>' . esc_html( (string) $row['action_name'] ) . '</strong><br><code>' . esc_html( (string) $row['approval_id'] ) . '</code></td><td>' . esc_html( (string) $row['requested_by'] ) . '<pre>' . esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT ) ) . '</pre></td><td>' . esc_html( (string) $row['created_at'] ) . '</td><td>';
			if ( 'pending' === $status ) {
				echo '<form method="post">';
				wp_nonce_field( 'ngtai_decide' );
				echo '<input type="hidden" name="approval_id" value="' . esc_attr( (string) $row['approval_id'] ) . '"><textarea name="reason" required placeholder="' . esc_attr__( 'Required reason', 'nextgentutors-ai-integration' ) . '"></textarea><br><button class="button button-primary" data-testid="ngtai-approve-button" name="ngtai_decision" value="approve">' . esc_html__( 'Approve', 'nextgentutors-ai-integration' ) . '</button> <button class="button" name="ngtai_decision" value="deny">' . esc_html__( 'Deny', 'nextgentutors-ai-integration' ) . '</button></form>';
			} else {
				echo esc_html( (string) ( $row['decision_reason'] ?? '' ) );
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
