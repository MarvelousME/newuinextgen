<?php
/**
 * Health admin page.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Health_Page {
	public static function render() {
		if ( ! NGTAI_Access::can_manage() ) {
			wp_die( esc_html__( 'Insufficient permission.', 'nextgentutors-ai-integration' ) );
		}
		echo '<div class="wrap ngtai-admin" data-testid="ngtai-health-page"><h1>' . esc_html__( 'AI Integration Health', 'nextgentutors-ai-integration' ) . '</h1><table class="widefat striped"><tbody>';
		foreach ( NGTAI_Health::snapshot() as $key => $value ) {
			echo '<tr><th>' . esc_html( ucwords( str_replace( '_', ' ', $key ) ) ) . '</th><td><code>' . esc_html( is_scalar( $value ) ? (string) $value : wp_json_encode( $value, JSON_PRETTY_PRINT ) ) . '</code></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
