<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Single point of truth for authentication and authorisation. Accepts
 * either a logged-in WP administrator session, or an API key with the
 * right scope, and rate-limits every keyed request.
 */
class NUAPI_Security {

	const HEADER_KEY = 'X-NUAPI-Key';
	const CAP        = 'manage_options';

	public static function init() {
		// Reserved for future cron-based rate-limit transient cleanup.
	}

	public static function can_read( WP_REST_Request $request ) {
		if ( current_user_can( self::CAP ) ) {
			return true;
		}
		$key = self::extract_key( $request );
		if ( ! $key ) {
			return new WP_Error( 'nuapi_unauthorized', __( 'Missing credentials. Provide a valid API key via the X-NUAPI-Key header, or log in as an administrator.', 'nuapi' ), array( 'status' => 401 ) );
		}
		$record = self::validate_key( $key, 'read' );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		return self::check_rate_limit( $key );
	}

	public static function can_write( WP_REST_Request $request ) {
		$table    = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $request->get_param( 'table' ) );
		$settings = get_option( 'nuapi_settings', array() );
		$write_tables = isset( $settings['write_tables'] ) ? (array) $settings['write_tables'] : array();

		if ( $table && ! in_array( $table, $write_tables, true ) ) {
			return new WP_Error( 'nuapi_write_disabled', __( 'Write access for this table is disabled. Enable it from NextGen Universal API → Tables & Permissions.', 'nuapi' ), array( 'status' => 403 ) );
		}

		if ( current_user_can( self::CAP ) ) {
			return true;
		}

		$key = self::extract_key( $request );
		if ( ! $key ) {
			return new WP_Error( 'nuapi_unauthorized', __( 'Missing credentials.', 'nuapi' ), array( 'status' => 401 ) );
		}
		$record = self::validate_key( $key, 'write' );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		return self::check_rate_limit( $key );
	}

	private static function extract_key( WP_REST_Request $request ) {
		$header = $request->get_header( self::HEADER_KEY );
		if ( $header ) {
			return trim( $header );
		}
		$param = $request->get_param( 'api_key' );
		return $param ? trim( $param ) : null;
	}

	public static function validate_key( $raw_key, $required_scope = 'read' ) {
		$keys = get_option( 'nuapi_api_keys', array() );
		$hash = self::hash_key( $raw_key );

		foreach ( $keys as $entry ) {
			if ( ! hash_equals( $entry['hash'], $hash ) ) {
				continue;
			}
			if ( ! empty( $entry['revoked'] ) ) {
				return new WP_Error( 'nuapi_key_revoked', __( 'This API key has been revoked.', 'nuapi' ), array( 'status' => 401 ) );
			}
			if ( 'write' === $required_scope && 'write' !== $entry['scope'] ) {
				return new WP_Error( 'nuapi_insufficient_scope', __( 'This API key only has read access.', 'nuapi' ), array( 'status' => 403 ) );
			}
			return $entry;
		}
		return new WP_Error( 'nuapi_invalid_key', __( 'Invalid API key.', 'nuapi' ), array( 'status' => 401 ) );
	}

	public static function hash_key( $raw_key ) {
		return hash( 'sha256', $raw_key . wp_salt( 'auth' ) );
	}

	public static function generate_key( $label, $scope = 'read' ) {
		$raw  = 'nuapi_' . wp_generate_password( 40, false, false );
		$keys = get_option( 'nuapi_api_keys', array() );
		$keys[] = array(
			'id'      => wp_generate_uuid4(),
			'label'   => sanitize_text_field( $label ),
			'hash'    => self::hash_key( $raw ),
			'scope'   => in_array( $scope, array( 'read', 'write' ), true ) ? $scope : 'read',
			'created' => current_time( 'mysql' ),
			'revoked' => false,
		);
		update_option( 'nuapi_api_keys', $keys );
		return $raw;
	}

	public static function revoke_key( $id ) {
		$keys = get_option( 'nuapi_api_keys', array() );
		foreach ( $keys as &$entry ) {
			if ( $entry['id'] === $id ) {
				$entry['revoked'] = true;
			}
		}
		update_option( 'nuapi_api_keys', $keys );
	}

	private static function check_rate_limit( $raw_key ) {
		$settings = get_option( 'nuapi_settings', array() );
		$limit    = isset( $settings['rate_limit'] ) ? (int) $settings['rate_limit'] : 120;
		$bucket   = 'nuapi_rl_' . md5( $raw_key ) . '_' . floor( time() / 60 );
		$count    = (int) get_transient( $bucket );

		if ( $count >= $limit ) {
			return new WP_Error( 'nuapi_rate_limited', __( 'Rate limit exceeded. Try again in a minute.', 'nuapi' ), array( 'status' => 429 ) );
		}
		set_transient( $bucket, $count + 1, 70 );
		return true;
	}
}
