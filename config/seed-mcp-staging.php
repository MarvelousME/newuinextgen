<?php
/**
 * Seed NGC MCP registry from /var/www/config/mcp-staging-servers.json
 * Run inside wordpress container: php /var/www/config/../... or mounted path.
 */
$paths = [
	'/var/www/config/mcp-staging-servers.json',
	'/var/www/html/wp-content/../config/mcp-staging-servers.json',
];
$raw = null;
foreach ( $paths as $p ) {
	if ( is_readable( $p ) ) {
		$raw = file_get_contents( $p );
		break;
	}
}
if ( ! $raw ) {
	fwrite( STDERR, "missing mcp-staging-servers.json\n" );
	exit( 1 );
}
$data = json_decode( $raw, true );
if ( ! is_array( $data ) || empty( $data['servers'] ) ) {
	fwrite( STDERR, "bad seed json\n" );
	exit( 1 );
}

require '/var/www/html/wp-load.php';

$out = [];
foreach ( $data['servers'] as $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}
	$id = sanitize_key( (string) ( $row['id'] ?? '' ) );
	if ( '' === $id ) {
		continue;
	}
	$caps = $row['discovered_capabilities'] ?? [];
	$out[] = [
		'id'                      => $id,
		'display_name'            => sanitize_text_field( (string) ( $row['display_name'] ?? $id ) ),
		'description'             => sanitize_textarea_field( (string) ( $row['description'] ?? '' ) ),
		'environment'             => sanitize_key( (string) ( $row['environment'] ?? 'staging' ) ),
		'enabled'                 => ! empty( $row['enabled'] ) ? 1 : 0,
		'ownership'               => sanitize_key( (string) ( $row['ownership'] ?? 'first_party' ) ),
		'transport'               => sanitize_key( (string) ( $row['transport'] ?? 'streamable_http' ) ),
		'endpoint'                => esc_url_raw( (string) ( $row['endpoint'] ?? '' ) ),
		'auth_type'               => sanitize_key( (string) ( $row['auth_type'] ?? 'none' ) ),
		'allowed_tools'           => array_values( array_map( 'sanitize_text_field', (array) ( $row['allowed_tools'] ?? [] ) ) ),
		'denied_tools'            => array_values( array_map( 'sanitize_text_field', (array) ( $row['denied_tools'] ?? [] ) ) ),
		'allowed_resources'       => array_values( array_map( 'sanitize_text_field', (array) ( $row['allowed_resources'] ?? [] ) ) ),
		'allowed_prompts'         => array_values( array_map( 'sanitize_text_field', (array) ( $row['allowed_prompts'] ?? [] ) ) ),
		'data_classifications'    => array_values( array_map( 'sanitize_key', (array) ( $row['data_classifications'] ?? [ 'public', 'internal' ] ) ) ),
		'timeout'                 => 15,
		'retry_limit'             => 2,
		'rate_limit'              => 60,
		'health_interval'         => 300,
		'require_tls'             => ! empty( $row['require_tls'] ) ? 1 : 0,
		'version'                 => '1.0.0',
		'approval_policy'         => sanitize_key( (string) ( $row['approval_policy'] ?? 'human' ) ),
		'capabilities_approved'   => ! empty( $row['capabilities_approved'] ) ? 1 : 0,
		'discovered_capabilities' => is_array( $caps ) ? $caps : [],
		'last_health'             => null,
		'secret_ref'              => '',
		'kill_switch'             => ! empty( $row['kill_switch'] ) ? 1 : 0,
		'updated_at'              => gmdate( 'c' ),
		'updated_by'              => 0,
		'created_at'              => gmdate( 'c' ),
	];
}

update_option( 'ngc_mcp_servers', $out, false );
echo 'seeded=' . count( $out ) . PHP_EOL;

if ( class_exists( 'NGC_Agent_Gateway_Client' ) ) {
	$h = NGC_Agent_Gateway_Client::health();
	if ( is_wp_error( $h ) ) {
		echo 'gateway_health=FAIL ' . $h->get_error_message() . PHP_EOL;
		exit( 2 );
	}
	echo 'gateway_health=OK agent=' . ( $h['agent_card'] ?? '?' ) . PHP_EOL;
} else {
	echo 'gateway_client=missing' . PHP_EOL;
}
