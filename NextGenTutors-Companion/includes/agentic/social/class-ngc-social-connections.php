<?php
/**
 * Social connection store — OAuth tokens via secret vault only.
 *
 * PROHIBITED: platform username/password fields.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connected social accounts.
 */
final class NGC_Social_Connections {

	const OPTION = 'ngc_social_connections';

	/**
	 * Supported platforms (official OAuth/API only).
	 *
	 * @return string[]
	 */
	public static function platforms() {
		return [ 'facebook_pages', 'instagram_professional', 'x', 'linkedin' ];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		$rows = get_option( self::OPTION, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}

	/**
	 * Browser-safe list (no tokens).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_public() {
		$out = [];
		foreach ( self::all() as $row ) {
			$out[] = self::public_row( $row );
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function public_row( array $row ) {
		return [
			'id'                 => (string) ( $row['id'] ?? '' ),
			'platform'           => (string) ( $row['platform'] ?? '' ),
			'label'              => (string) ( $row['label'] ?? '' ),
			'external_id'        => (string) ( $row['external_id'] ?? '' ),
			'account_type'       => (string) ( $row['account_type'] ?? '' ),
			'display_name'       => (string) ( $row['display_name'] ?? '' ),
			'status'             => (string) ( $row['status'] ?? 'disconnected' ),
			'scopes'             => array_values( (array) ( $row['scopes'] ?? [] ) ),
			'capabilities'       => array_values( (array) ( $row['capabilities'] ?? [] ) ),
			'token_expires_at'   => (string) ( $row['token_expires_at'] ?? '' ),
			'last_health'        => $row['last_health'] ?? null,
			'connected_by'       => (int) ( $row['connected_by'] ?? 0 ),
			'created_at'         => (string) ( $row['created_at'] ?? '' ),
			'updated_at'         => (string) ( $row['updated_at'] ?? '' ),
			'enabled'            => ! empty( $row['enabled'] ),
			'approval_policy'    => (string) ( $row['approval_policy'] ?? 'human' ),
			'timezone'           => (string) ( $row['timezone'] ?? 'Africa/Johannesburg' ),
			'has_token'          => ! empty( $row['token_secret_ref'] ),
			// Explicitly never expose tokens.
		];
	}

	/**
	 * Persist connection after OAuth exchange (server-side only).
	 *
	 * @param array<string, mixed> $input Input including access_token (consumed, not stored plaintext).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function save_from_oauth( array $input ) {
		$platform = sanitize_key( (string) ( $input['platform'] ?? '' ) );
		if ( ! in_array( $platform, self::platforms(), true ) ) {
			return new WP_Error( 'ngc_social_platform', __( 'Unsupported social platform.', 'nextgencompanion' ) );
		}
		// Reject any accidental password fields.
		foreach ( [ 'password', 'username', 'user_pass', 'login_password' ] as $banned ) {
			if ( isset( $input[ $banned ] ) && '' !== (string) $input[ $banned ] ) {
				return new WP_Error( 'ngc_social_password_forbidden', __( 'Social connections must use OAuth — passwords are not accepted.', 'nextgencompanion' ) );
			}
		}
		$token = (string) ( $input['access_token'] ?? '' );
		if ( '' === $token ) {
			return new WP_Error( 'ngc_social_token', __( 'OAuth access token missing.', 'nextgencompanion' ) );
		}
		$ref = NGC_Secret_Vault::store( $token, $platform . '_access_token' );
		if ( is_wp_error( $ref ) ) {
			return $ref;
		}
		unset( $input['access_token'], $input['refresh_token'] );
		if ( ! empty( $input['refresh_token_raw'] ) ) {
			$rref = NGC_Secret_Vault::store( (string) $input['refresh_token_raw'], $platform . '_refresh_token' );
			if ( ! is_wp_error( $rref ) ) {
				$input['refresh_secret_ref'] = $rref;
			}
			unset( $input['refresh_token_raw'] );
		}

		$id  = sanitize_key( (string) ( $input['id'] ?? ( 'soc_' . wp_generate_password( 12, false, false ) ) ) );
		$row = [
			'id'               => $id,
			'platform'         => $platform,
			'label'            => sanitize_text_field( (string) ( $input['label'] ?? $platform ) ),
			'external_id'      => sanitize_text_field( (string) ( $input['external_id'] ?? '' ) ),
			'account_type'     => sanitize_key( (string) ( $input['account_type'] ?? 'page' ) ),
			'display_name'     => sanitize_text_field( (string) ( $input['display_name'] ?? '' ) ),
			'status'           => 'connected',
			'scopes'           => array_values( array_map( 'sanitize_text_field', (array) ( $input['scopes'] ?? [] ) ) ),
			'capabilities'     => array_values( array_map( 'sanitize_key', (array) ( $input['capabilities'] ?? [ 'publish' ] ) ) ),
			'token_secret_ref' => $ref,
			'refresh_secret_ref' => sanitize_key( (string) ( $input['refresh_secret_ref'] ?? '' ) ),
			'token_expires_at' => sanitize_text_field( (string) ( $input['token_expires_at'] ?? '' ) ),
			'connected_by'     => get_current_user_id(),
			'created_at'       => gmdate( 'c' ),
			'updated_at'       => gmdate( 'c' ),
			'enabled'          => 1,
			'approval_policy'  => 'human',
			'timezone'         => 'Africa/Johannesburg',
		];
		$all = self::all();
		$all[] = $row;
		update_option( self::OPTION, $all, false );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'social_connection_saved', 'social', 0, [ 'id' => $id, 'platform' => $platform ] );
		}
		return self::public_row( $row );
	}

	/**
	 * Start OAuth — returns authorization URL template note when app credentials absent.
	 *
	 * @param string $platform Platform.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function begin_connect( $platform ) {
		$platform = sanitize_key( (string) $platform );
		if ( ! in_array( $platform, self::platforms(), true ) ) {
			return new WP_Error( 'ngc_social_platform', __( 'Unsupported social platform.', 'nextgencompanion' ) );
		}
		$state = NGC_Social_Oauth::begin( $platform );
		$apps  = self::app_credentials( $platform );
		if ( is_wp_error( $apps ) ) {
			return [
				'ok'           => false,
				'status'       => 'INPUTS_REQUIRED',
				'platform'     => $platform,
				'state'        => $state,
				'callback'     => NGC_Social_Oauth::callback_url( $platform ),
				'message'      => $apps->get_error_message(),
				'inputs'       => [ 'client_id_ref', 'client_secret_ref' ],
			];
		}
		$url = self::authorization_url( $platform, $state, $apps );
		return [
			'ok'       => true,
			'platform' => $platform,
			'state'    => $state,
			'auth_url' => $url,
			'callback' => NGC_Social_Oauth::callback_url( $platform ),
		];
	}

	/**
	 * @param string $platform Platform.
	 * @return array<string, string>|WP_Error
	 */
	private static function app_credentials( $platform ) {
		$map = [
			'facebook_pages'         => [ 'NGC_META_APP_ID', 'NGC_META_APP_SECRET' ],
			'instagram_professional' => [ 'NGC_META_APP_ID', 'NGC_META_APP_SECRET' ],
			'x'                      => [ 'NGC_X_CLIENT_ID', 'NGC_X_CLIENT_SECRET' ],
			'linkedin'               => [ 'NGC_LINKEDIN_CLIENT_ID', 'NGC_LINKEDIN_CLIENT_SECRET' ],
		];
		$pair = $map[ $platform ] ?? null;
		if ( ! $pair ) {
			return new WP_Error( 'ngc_social_platform', __( 'Unsupported social platform.', 'nextgencompanion' ) );
		}
		$id = defined( $pair[0] ) ? (string) constant( $pair[0] ) : '';
		$secret = defined( $pair[1] ) ? (string) constant( $pair[1] ) : '';
		if ( '' === $id || '' === $secret ) {
			return new WP_Error(
				'ngc_social_app_credentials',
				sprintf(
					/* translators: %s: constant names */
					__( 'Define %s in wp-config (or vault) before connecting. Never paste passwords into this screen.', 'nextgencompanion' ),
					implode( ' / ', $pair )
				)
			);
		}
		return [ 'client_id' => $id, 'client_secret' => $secret ];
	}

	/**
	 * @param string               $platform Platform.
	 * @param string               $state    State.
	 * @param array<string,string> $apps     App credentials.
	 * @return string
	 */
	private static function authorization_url( $platform, $state, array $apps ) {
		$callback = NGC_Social_Oauth::callback_url( $platform );
		$pkce     = get_transient( NGC_Social_Oauth::TRANSIENT_PREFIX . $state );
		$challenge = is_array( $pkce ) ? (string) ( $pkce['pkce_challenge'] ?? '' ) : '';
		switch ( $platform ) {
			case 'facebook_pages':
			case 'instagram_professional':
				return add_query_arg(
					[
						'client_id'     => $apps['client_id'],
						'redirect_uri'  => $callback,
						'state'         => $state,
						'scope'         => 'pages_manage_posts,pages_read_engagement,instagram_basic,instagram_content_publish',
						'response_type' => 'code',
					],
					'https://www.facebook.com/v19.0/dialog/oauth'
				);
			case 'x':
				return add_query_arg(
					[
						'response_type'         => 'code',
						'client_id'             => $apps['client_id'],
						'redirect_uri'          => $callback,
						'scope'                 => 'tweet.read tweet.write users.read offline.access',
						'state'                 => $state,
						'code_challenge'        => $challenge,
						'code_challenge_method' => 'S256',
					],
					'https://twitter.com/i/oauth2/authorize'
				);
			case 'linkedin':
				return add_query_arg(
					[
						'response_type' => 'code',
						'client_id'     => $apps['client_id'],
						'redirect_uri'  => $callback,
						'state'         => $state,
						'scope'         => 'openid profile w_member_social',
					],
					'https://www.linkedin.com/oauth/v2/authorization'
				);
			default:
				return '';
		}
	}
}
