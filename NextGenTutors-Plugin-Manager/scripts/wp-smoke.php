<?php
/**
 * WordPress integration smoke (run inside Docker via WP-CLI).
 *
 * Usage (from host):
 *   docker run --rm --volumes-from nextgentutors-wordpress-1 \
 *     --network nextgentutors_default \
 *     -e WORDPRESS_DB_HOST=db -e WORDPRESS_DB_USER=wordpress \
 *     -e WORDPRESS_DB_PASSWORD=wordpress -e WORDPRESS_DB_NAME=wordpress \
 *     wordpress:cli-php8.2 wp eval-file \
 *     /var/www/html/wp-content/plugins/NextGenTutors-Plugin-Manager/scripts/wp-smoke.php \
 *     --path=/var/www/html --allow-root
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$errors = 0;

function ngcpm_wp_fail( $message ) {
	global $errors;
	echo "FAIL: {$message}\n";
	++$errors;
}

echo "NGCPM WP smoke\n";

if ( ! class_exists( 'NGCPM_Plugin' ) ) {
	ngcpm_wp_fail( 'NGCPM_Plugin not loaded' );
}

if ( '1.1.6' !== NGCPM_VERSION ) {
	ngcpm_wp_fail( 'unexpected version ' . NGCPM_VERSION );
}

$plan = NGCPM_Queue::build_plan();
if ( ! is_array( $plan ) || empty( $plan ) ) {
	ngcpm_wp_fail( 'queue plan empty' );
}

$vm = NGCPM_View_Model::for_app( true, 0 );
$required = [ 'scan', 'health', 'steps', 'readonly', 'diagnostics', 'repair', 'queue_plan', 'graph' ];
foreach ( $required as $key ) {
	if ( ! array_key_exists( $key, $vm ) ) {
		ngcpm_wp_fail( "view model missing {$key}" );
	}
}

if ( ! empty( $vm['diagnostics'] ) ) {
	ngcpm_wp_fail( 'diagnostics must be lazy (empty on render)' );
}

$menu_hook = has_action( 'wp_ajax_ngcpm_queue_plan' );
if ( ! $menu_hook ) {
	ngcpm_wp_fail( 'ngcpm_queue_plan ajax not registered' );
}

echo 'version=' . NGCPM_VERSION . "\n";
echo 'queue_items=' . count( $plan ) . "\n";
echo 'repair_issues=' . count( $vm['repair'] ) . "\n";

if ( $errors > 0 ) {
	echo "\n{$errors} failure(s)\n";
	exit( 1 );
}

echo "OK - WP smoke passed\n";
