<?php
/**
 * Rate limiting, honeypot, and form validation.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Security {

	public static function register_hooks(): void {
		add_filter( 'ngt_hub_validate_form', [ __CLASS__, 'validate_submission' ], 10, 2 );
	}

	/**
	 * @param true|WP_Error $result Current result.
	 * @param array<string, mixed> $context Form context.
	 * @return true|WP_Error
	 */
	public static function validate_submission( $result, array $context ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$form = sanitize_key( $context['form'] ?? '' );
		$data = $context['data'] ?? [];

		if ( ! empty( $data['ngt_hp_field'] ) ) {
			return new WP_Error( 'ngt_spam', __( 'Submission rejected.', 'nextgen-automation-hub' ), [ 'status' => 400 ] );
		}

		$ip = self::client_ip();
		if ( ! self::check_rate_limit( $form . ':' . $ip, 5, 600 ) ) {
			return new WP_Error( 'ngt_rate_limit', __( 'Too many submissions. Please wait and try again.', 'nextgen-automation-hub' ), [ 'status' => 429 ] );
		}

		if ( 'register' === $form && ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
			return new WP_Error( 'ngt_invalid_email', __( 'Please enter a valid email address.', 'nextgen-automation-hub' ) );
		}

		if ( in_array( $form, [ 'find_tutor', 'become_tutor' ], true ) ) {
			foreach ( [ 'name', 'email' ] as $required ) {
				if ( empty( $data[ $required ] ) ) {
					return new WP_Error( 'ngt_missing_field', sprintf( __( '%s is required.', 'nextgen-automation-hub' ), ucfirst( $required ) ) );
				}
			}
			if ( ! is_email( $data['email'] ) ) {
				return new WP_Error( 'ngt_invalid_email', __( 'Please enter a valid email address.', 'nextgen-automation-hub' ) );
			}
		}

		return true;
	}

	public static function check_rate_limit( string $key, int $max, int $window_seconds ): bool {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'rate_limits' );
		$now   = current_time( 'mysql', true );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE rate_key = %s LIMIT 1", $key ),
			ARRAY_A
		);

		if ( ! $row ) {
			$wpdb->insert(
				$table,
				[
					'rate_key'     => $key,
					'hits'         => 1,
					'window_start' => $now,
				],
				[ '%s', '%d', '%s' ]
			);
			return true;
		}

		$window_start = strtotime( $row['window_start'] . ' UTC' );
		if ( ( time() - $window_start ) > $window_seconds ) {
			$wpdb->update(
				$table,
				[ 'hits' => 1, 'window_start' => $now ],
				[ 'rate_key' => $key ],
				[ '%d', '%s' ],
				[ '%s' ]
			);
			return true;
		}

		if ( (int) $row['hits'] >= $max ) {
			return false;
		}

		$wpdb->update(
			$table,
			[ 'hits' => (int) $row['hits'] + 1 ],
			[ 'rate_key' => $key ],
			[ '%d' ],
			[ '%s' ]
		);

		return true;
	}

	public static function honeypot_field(): string {
		return '<div class="ngt-hp" aria-hidden="true" style="position:absolute;left:-9999px;height:0;overflow:hidden;">'
			. '<label for="ngt_hp_field">' . esc_html__( 'Leave blank', 'nextgen-automation-hub' ) . '</label>'
			. '<input type="text" name="ngt_hp_field" id="ngt_hp_field" tabindex="-1" autocomplete="off" />'
			. '</div>';
	}

	public static function client_ip(): string {
		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER[ $header ] ) );
			if ( false !== strpos( $ip, ',' ) ) {
				$ip = trim( explode( ',', $ip )[0] );
			}
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '0.0.0.0';
	}

	/**
	 * @param array<string, mixed> $file $_FILES entry.
	 * @return int|WP_Error Attachment ID.
	 */
	public static function handle_upload( array $file, array $allowed = [ 'pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx' ] ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'ngt_no_file', __( 'No file uploaded.', 'nextgen-automation-hub' ) );
		}

		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed, true ) ) {
			return new WP_Error( 'ngt_invalid_file', __( 'File type not allowed.', 'nextgen-automation-hub' ) );
		}

		if ( (int) $file['size'] > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'ngt_file_too_large', __( 'File exceeds 5 MB limit.', 'nextgen-automation-hub' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload( $file, [ 'test_form' => false ] );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'ngt_upload_error', $upload['error'] );
		}

		$attachment = [
			'post_mime_type' => $upload['type'],
			'post_title'     => sanitize_file_name( $file['name'] ),
			'post_content'   => '',
			'post_status'    => 'private',
		];

		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		return (int) $attach_id;
	}
}
