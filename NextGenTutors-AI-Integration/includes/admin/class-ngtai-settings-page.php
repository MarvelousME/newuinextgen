<?php
/**
 * Secure settings screen.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Settings_Page {
	private const FIELDS = [
		'ngtai_agents_api_url'          => 'url',
		'ngtai_agents_api_key_id'       => 'text',
		'ngtai_enabled'                 => 'bool',
		'ngtai_demo_mode'               => 'bool',
		'ngtai_timeout_seconds'         => 'int',
		'ngtai_max_attempts'            => 'int',
		'ngtai_retry_base_seconds'      => 'int',
		'ngtai_callback_skew_seconds'   => 'int',
		'ngtai_nonce_retention_days'    => 'int',
		'ngtai_global_pause'            => 'bool',
	];
	public static function render() {
		if ( ! NGTAI_Access::can_manage() ) {
			wp_die( esc_html__( 'Insufficient permission.', 'nextgentutors-ai-integration' ) );
		}
		$message = '';
		if ( isset( $_POST['ngtai_save'] ) ) {
			check_admin_referer( 'ngtai_settings' );
			$old_pause = (bool) get_option( 'ngtai_global_pause', false );
			foreach ( self::FIELDS as $option => $type ) {
				$raw   = wp_unslash( $_POST[ $option ] ?? ( 'bool' === $type ? '0' : '' ) );
				$value = 'bool' === $type ? (int) ( '1' === $raw ) : ( 'int' === $type ? absint( $raw ) : ( 'url' === $type ? esc_url_raw( $raw ) : sanitize_text_field( $raw ) ) );
				update_option( $option, $value, false );
			}
			$secret = (string) wp_unslash( $_POST['ngtai_secret'] ?? '' );
			if ( '' !== $secret ) {
				$stored = NGTAI_Config::store_secret( $secret );
				if ( is_wp_error( $stored ) ) {
					$message = $stored->get_error_message();
				} else {
					NGTAI_Audit::log( 'ngtai_secret_rotated' );
				}
			}
			if ( $old_pause !== (bool) get_option( 'ngtai_global_pause', false ) ) {
				NGTAI_Audit::log( 'ngtai_global_pause_changed', [ 'paused' => (bool) get_option( 'ngtai_global_pause', false ) ] );
			}
			if ( '' === $message ) {
				$message = __( 'Settings saved.', 'nextgentutors-ai-integration' );
			}
		} elseif ( isset( $_POST['ngtai_test'] ) ) {
			check_admin_referer( 'ngtai_settings' );
			$result  = NGTAI_Api_Client::health();
			$message = ! empty( $result['ok'] ) ? __( 'Connection succeeded.', 'nextgentutors-ai-integration' ) : sprintf( __( 'Connection failed (HTTP %d).', 'nextgentutors-ai-integration' ), (int) ( $result['status'] ?? 0 ) );
		}
		echo '<div class="wrap ngtai-admin"><h1>' . esc_html__( 'AI Integration Settings', 'nextgentutors-ai-integration' ) . '</h1>';
		if ( $message ) {
			echo '<div class="notice notice-info"><p>' . esc_html( $message ) . '</p></div>';
		}
		echo '<form method="post" data-testid="ngtai-settings-form">';
		wp_nonce_field( 'ngtai_settings' );
		echo '<table class="form-table"><tbody>';
		foreach ( self::FIELDS as $option => $type ) {
			$value = get_option( $option, '' );
			echo '<tr><th><label for="' . esc_attr( $option ) . '">' . esc_html( ucwords( str_replace( [ 'ngtai_', '_' ], [ '', ' ' ], $option ) ) ) . '</label></th><td>';
			if ( 'bool' === $type ) {
				echo '<input type="hidden" name="' . esc_attr( $option ) . '" value="0"><input id="' . esc_attr( $option ) . '" type="checkbox" name="' . esc_attr( $option ) . '" value="1" ' . checked( $value, 1, false ) . '>';
			} else {
				echo '<input class="regular-text" id="' . esc_attr( $option ) . '" type="' . ( 'int' === $type ? 'number' : 'text' ) . '" name="' . esc_attr( $option ) . '" value="' . esc_attr( (string) $value ) . '">';
			}
			echo '</td></tr>';
		}
		echo '<tr><th><label for="ngtai_secret">' . esc_html__( 'API secret', 'nextgentutors-ai-integration' ) . '</label></th><td><input class="regular-text" id="ngtai_secret" type="password" autocomplete="new-password" name="ngtai_secret" value=""><p class="description">' . esc_html__( 'Write-only; leave blank to keep the current secret.', 'nextgentutors-ai-integration' ) . '</p></td></tr>';
		echo '</tbody></table><p><button class="button button-primary" name="ngtai_save" value="1">' . esc_html__( 'Save settings', 'nextgentutors-ai-integration' ) . '</button> <button class="button" name="ngtai_test" value="1">' . esc_html__( 'Test connection', 'nextgentutors-ai-integration' ) . '</button></p></form></div>';
	}
}
