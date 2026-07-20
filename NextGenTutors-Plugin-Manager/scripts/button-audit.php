<?php
/**
 * Button wiring smoke test (no WordPress required).
 *
 * Usage: php scripts/button-audit.php
 *
 * @package NextGenCorePluginManager
 */

$root = dirname( __DIR__ );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}
if ( ! defined( 'NGCPM_PLUGIN_DIR' ) ) {
	define( 'NGCPM_PLUGIN_DIR', $root . '/' );
}
if ( ! defined( 'NGCPM_LOG_LIMIT' ) ) {
	define( 'NGCPM_LOG_LIMIT', 500 );
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}

require_once $root . '/includes/class-ngcpm-buttons.php';

$rows   = NGCPM_Buttons::audit();
$broken = 0;

echo "NGCPM button audit\n";
echo str_pad( 'Label', 28 ) . str_pad( 'Endpoint', 26 ) . str_pad( 'Status', 14 ) . "Handler\n";
echo str_repeat( '-', 90 ) . "\n";

foreach ( $rows as $row ) {
	echo str_pad( substr( $row['label'], 0, 26 ), 28 );
	echo str_pad( substr( $row['endpoint'], 0, 24 ), 26 );
	echo str_pad( $row['status'], 14 );
	echo ( $row['handler'] ?? 'N/A' ) . "\n";
	if ( in_array( $row['status'], [ 'BROKEN', 'MISSING_HANDLER', 'MISSING_BACKEND', 'FAKE_RESPONSE' ], true ) ) {
		++$broken;
	}
}

if ( $broken > 0 ) {
	echo "\n{$broken} broken button(s)\n";
	exit( 1 );
}

echo "\nOK - all required buttons wired\n";
exit( 0 );
