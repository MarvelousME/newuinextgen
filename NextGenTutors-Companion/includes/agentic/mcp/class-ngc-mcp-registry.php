<?php
/**
 * Dynamic MCP server registry — no unverified public servers enabled by default.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MCP server configuration and capability allowlists.
 */
final class NGC_Mcp_Registry {

	const OPTION = 'ngc_mcp_servers';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		$rows = get_option( self::OPTION, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}

	/**
	 * Public redacted view for admin UI / exports.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_public() {
		$out = [];
		foreach ( self::all() as $row ) {
			$out[] = self::redact( $row );
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $row Server.
	 * @return array<string, mixed>
	 */
	public static function redact( array $row ) {
		unset( $row['secret_ref_plaintext'], $row['client_secret'] );
		if ( ! empty( $row['secret_ref'] ) ) {
			$row['secret_meta'] = class_exists( 'NGC_Secret_Vault' ) ? NGC_Secret_Vault::meta( (string) $row['secret_ref'] ) : null;
		}
		$row['secret_ref'] = ! empty( $row['secret_ref'] ) ? (string) $row['secret_ref'] : '';
		return $row;
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function upsert( array $input ) {
		$id = sanitize_key( (string) ( $input['id'] ?? '' ) );
		if ( '' === $id ) {
			$id = 'mcp_' . wp_generate_password( 10, false, false );
		}
		$endpoint = esc_url_raw( (string) ( $input['endpoint'] ?? '' ) );
		$local    = ! empty( $input['allow_local'] ) && ( defined( 'WP_DEBUG' ) && WP_DEBUG );
		$safe     = NGC_Mcp_Ssrf::assert_safe_url( $endpoint, (bool) $local );
		if ( is_wp_error( $safe ) ) {
			return $safe;
		}

		$enabled = ! empty( $input['enabled'] );
		if ( $enabled && empty( $input['capabilities_approved'] ) ) {
			return new WP_Error( 'ngc_mcp_cap_approval', __( 'Discover and approve capabilities before enabling a server.', 'nextgencompanion' ) );
		}

		$row = [
			'id'                     => $id,
			'display_name'           => sanitize_text_field( (string) ( $input['display_name'] ?? $id ) ),
			'description'            => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
			'environment'            => sanitize_key( (string) ( $input['environment'] ?? 'staging' ) ),
			'enabled'                => $enabled ? 1 : 0,
			'ownership'              => sanitize_key( (string) ( $input['ownership'] ?? 'first_party' ) ),
			'transport'              => sanitize_key( (string) ( $input['transport'] ?? 'streamable_http' ) ),
			'endpoint'               => $endpoint,
			'auth_type'              => sanitize_key( (string) ( $input['auth_type'] ?? 'none' ) ),
			'allowed_tools'          => array_values( array_map( 'sanitize_text_field', (array) ( $input['allowed_tools'] ?? [] ) ) ),
			'denied_tools'           => array_values( array_map( 'sanitize_text_field', (array) ( $input['denied_tools'] ?? [] ) ) ),
			'allowed_resources'      => array_values( array_map( 'sanitize_text_field', (array) ( $input['allowed_resources'] ?? [] ) ) ),
			'allowed_prompts'        => array_values( array_map( 'sanitize_text_field', (array) ( $input['allowed_prompts'] ?? [] ) ) ),
			'data_classifications'   => array_values( array_map( 'sanitize_key', (array) ( $input['data_classifications'] ?? [ 'public', 'internal' ] ) ) ),
			'timeout'                => max( 2, min( 60, (int) ( $input['timeout'] ?? 15 ) ) ),
			'retry_limit'            => max( 0, min( 10, (int) ( $input['retry_limit'] ?? 2 ) ) ),
			'rate_limit'             => max( 1, (int) ( $input['rate_limit'] ?? 60 ) ),
			'health_interval'        => max( 60, (int) ( $input['health_interval'] ?? 300 ) ),
			'require_tls'            => empty( $input['require_tls'] ) ? 0 : 1,
			'version'                => sanitize_text_field( (string) ( $input['version'] ?? '' ) ),
			'approval_policy'        => sanitize_key( (string) ( $input['approval_policy'] ?? 'human' ) ),
			'capabilities_approved'  => empty( $input['capabilities_approved'] ) ? 0 : 1,
			'discovered_capabilities'=> is_array( $input['discovered_capabilities'] ?? null ) ? $input['discovered_capabilities'] : [],
			'last_health'            => is_array( $input['last_health'] ?? null ) ? $input['last_health'] : null,
			'secret_ref'             => sanitize_key( (string) ( $input['secret_ref'] ?? '' ) ),
			'kill_switch'            => empty( $input['kill_switch'] ) ? 0 : 1,
			'updated_at'             => gmdate( 'c' ),
			'updated_by'             => get_current_user_id(),
		];

		$all = self::all();
		$found = false;
		foreach ( $all as $i => $existing ) {
			if ( ( $existing['id'] ?? '' ) === $id ) {
				$all[ $i ] = array_merge( $existing, $row );
				$found     = true;
				break;
			}
		}
		if ( ! $found ) {
			$row['created_at'] = gmdate( 'c' );
			$all[]             = $row;
		}
		update_option( self::OPTION, $all, false );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'mcp_server_upsert', 'mcp', 0, [ 'id' => $id, 'enabled' => $row['enabled'] ] );
		}
		return self::redact( $row );
	}

	/**
	 * Non-mutating health probe (HEAD/GET) after SSRF checks.
	 *
	 * @param string $id Server id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function health_check( $id ) {
		$id  = sanitize_key( (string) $id );
		$row = null;
		foreach ( self::all() as $candidate ) {
			if ( ( $candidate['id'] ?? '' ) === $id ) {
				$row = $candidate;
				break;
			}
		}
		if ( ! $row ) {
			return new WP_Error( 'ngc_mcp_missing', __( 'MCP server not found.', 'nextgencompanion' ) );
		}
		if ( ! empty( $row['kill_switch'] ) ) {
			return new WP_Error( 'ngc_mcp_killed', __( 'Kill switch engaged for this server.', 'nextgencompanion' ) );
		}
		$endpoint = (string) ( $row['endpoint'] ?? '' );
		$safe     = NGC_Mcp_Ssrf::assert_safe_url( $endpoint, 'staging' === ( $row['environment'] ?? '' ) );
		if ( is_wp_error( $safe ) ) {
			return $safe;
		}
		$response = wp_remote_get(
			$endpoint,
			[
				'timeout'     => (int) ( $row['timeout'] ?? 15 ),
				'redirection' => 0,
				'headers'     => [ 'Accept' => 'application/json' ],
			]
		);
		$status = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$result = [
			'ok'         => $status >= 200 && $status < 500,
			'status'     => $status,
			'checked_at' => gmdate( 'c' ),
			'error'      => is_wp_error( $response ) ? $response->get_error_message() : '',
		];
		$all = self::all();
		foreach ( $all as $i => $candidate ) {
			if ( ( $candidate['id'] ?? '' ) === $id ) {
				$all[ $i ]['last_health'] = $result;
			}
		}
		update_option( self::OPTION, $all, false );
		return $result;
	}

	/**
	 * Capability discovery must be reviewed before enable — stores draft discovery only.
	 *
	 * @param string               $id   Server id.
	 * @param array<string, mixed> $caps Discovered payload (caller-validated).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function store_discovery( $id, array $caps ) {
		$id = sanitize_key( (string) $id );
		$all = self::all();
		foreach ( $all as $i => $row ) {
			if ( ( $row['id'] ?? '' ) !== $id ) {
				continue;
			}
			$all[ $i ]['discovered_capabilities'] = [
				'tools'     => array_values( (array) ( $caps['tools'] ?? [] ) ),
				'resources' => array_values( (array) ( $caps['resources'] ?? [] ) ),
				'prompts'   => array_values( (array) ( $caps['prompts'] ?? [] ) ),
				'at'        => gmdate( 'c' ),
			];
			$all[ $i ]['capabilities_approved'] = 0;
			$all[ $i ]['enabled']               = 0;
			update_option( self::OPTION, $all, false );
			return self::redact( $all[ $i ] );
		}
		return new WP_Error( 'ngc_mcp_missing', __( 'MCP server not found.', 'nextgencompanion' ) );
	}
}
