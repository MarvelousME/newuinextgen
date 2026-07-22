<?php
/**
 * Agent operations summary.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Agent_Ops_Page {
	public static function render() {
		if ( ! NGTAI_Access::can_manage() ) {
			wp_die( esc_html__( 'Insufficient permission.', 'nextgentutors-ai-integration' ) );
		}
		$results = class_exists( 'NGTAI_Result_Repository' ) ? NGTAI_Result_Repository::list_recent( [ 'limit' => 100 ] ) : [];
		$pauses  = class_exists( 'NGC_Agent_Control_Plane' ) && method_exists( 'NGC_Agent_Control_Plane', 'status' ) ? NGC_Agent_Control_Plane::status() : [ 'available' => false ];
		echo '<div class="wrap ngtai-admin" data-testid="ngtai-agent-ops-page"><h1>' . esc_html__( 'Agent Operations', 'nextgentutors-ai-integration' ) . '</h1><h2>' . esc_html__( 'Control plane', 'nextgentutors-ai-integration' ) . '</h2><pre>' . esc_html( wp_json_encode( NGTAI_Logger::scrub( $pauses ), JSON_PRETTY_PRINT ) ) . '</pre>';
		echo '<h2>' . esc_html__( 'Recent results', 'nextgentutors-ai-integration' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Agent', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Action', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Status', 'nextgentutors-ai-integration' ) . '</th><th>' . esc_html__( 'Correlation', 'nextgentutors-ai-integration' ) . '</th></tr></thead><tbody>';
		foreach ( (array) $results as $row ) {
			echo '<tr><td>' . esc_html( (string) ( $row['agent_name'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $row['action_name'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $row['status'] ?? '' ) ) . '</td><td><code>' . esc_html( (string) ( $row['correlation_id'] ?? '' ) ) . '</code></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
