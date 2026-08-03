<?php
/**
 * OAuth helpers — Authorization Code + PKCE. Never stores platform passwords.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuth state and PKCE utilities.
 */
final class NGC_Social_Oauth {

	const TRANSIENT_PREFIX = 'ngc_oauth_';

	/**
	 * @return array{verifier:string,challenge:string,method:string}
	 */
	public static function pkce_pair() {
		$verifier  = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
		$challenge = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
		return [
			'verifier'  => $verifier,
			'challenge' => $challenge,
			'method'    => 'S256',
		];
	}

	/**
	 * @param string               $platform Platform slug.
	 * @param array<string, mixed> $extra    Extra state.
	 * @return string State token.
	 */
	public static function begin( $platform, array $extra = [] ) {
		$platform = sanitize_key( (string) $platform );
		$pkce     = self::pkce_pair();
		$state    = wp_generate_password( 32, false, false );
		set_transient(
			self::TRANSIENT_PREFIX . $state,
			[
				'platform'       => $platform,
				'user_id'        => get_current_user_id(),
				'pkce_verifier'  => $pkce['verifier'],
				'pkce_challenge' => $pkce['challenge'],
				'created_at'     => time(),
				'extra'          => $extra,
			],
			20 * MINUTE_IN_SECONDS
		);
		return $state;
	}

	/**
	 * @param string $state State.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function consume( $state ) {
		$state = sanitize_text_field( (string) $state );
		$key   = self::TRANSIENT_PREFIX . $state;
		$row   = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $row ) ) {
			return new WP_Error( 'ngc_oauth_state', __( 'OAuth state invalid or expired.', 'nextgencompanion' ) );
		}
		if ( (int) ( $row['user_id'] ?? 0 ) !== get_current_user_id() && is_user_logged_in() ) {
			return new WP_Error( 'ngc_oauth_user', __( 'OAuth state user mismatch.', 'nextgencompanion' ) );
		}
		return $row;
	}

	/**
	 * Callback URL for WordPress admin-post.
	 *
	 * @param string $platform Platform.
	 * @return string
	 */
	public static function callback_url( $platform ) {
		return admin_url( 'admin-post.php?action=ngc_social_oauth_callback&platform=' . rawurlencode( sanitize_key( $platform ) ) );
	}
}
