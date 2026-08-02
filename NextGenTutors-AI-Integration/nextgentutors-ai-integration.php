<?php
/**
 * Plugin Name:       NextGenTutors-AI-Integration
 * Description:       Governed asynchronous bridge between NextGenTutors Companion and agents-api.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Text Domain:       nextgentutors-ai-integration
 * License:           GPL-2.0-or-later
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'NextGenTutors AI Integration requires PHP 8.0 or newer.', 'nextgentutors-ai-integration' ) . '</p></div>';
		}
	);
	return;
}

define( 'NGTAI_VERSION', '1.1.0' );
define( 'NGTAI_PLUGIN_FILE', __FILE__ );
define( 'NGTAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGTAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NGTAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$ngtai_files = [
	'includes/class-ngtai-database.php',
	'includes/class-ngtai-migrator.php',
	'includes/class-ngtai-activator.php',
	'includes/class-ngtai-deactivator.php',
	'includes/class-ngtai-config.php',
	'includes/class-ngtai-crypto.php',
	'includes/class-ngtai-signature.php',
	'includes/class-ngtai-nonce-store.php',
	'includes/class-ngtai-idempotency-store.php',
	'includes/class-ngtai-access.php',
	'includes/class-ngtai-logger.php',
	'includes/class-ngtai-audit.php',
	'includes/contracts/class-ngtai-event-envelope.php',
	'includes/contracts/class-ngtai-agent-request.php',
	'includes/contracts/class-ngtai-agent-result.php',
	'includes/class-ngtai-event-mapper.php',
	'includes/class-ngtai-redactor.php',
	'includes/class-ngtai-policy-gate.php',
	'includes/class-ngtai-api-client.php',
	'includes/class-ngtai-delivery-repository.php',
	'includes/class-ngtai-result-repository.php',
	'includes/class-ngtai-outbox-bridge.php',
	'includes/class-ngtai-intelligence-bridge.php',
	'includes/class-ngtai-callback-controller.php',
	'includes/class-ngtai-health.php',
	'includes/class-ngtai-cron.php',
	'includes/rest/class-ngtai-rest.php',
	'includes/rest/class-ngtai-rest-health.php',
	'includes/rest/class-ngtai-rest-callbacks.php',
	'includes/rest/class-ngtai-rest-approvals.php',
	'includes/rest/class-ngtai-rest-outbox.php',
	'includes/class-ngtai-plugin.php',
];
if ( is_admin() ) {
	$ngtai_files = array_merge(
		$ngtai_files,
		[
			'includes/admin/class-ngtai-admin.php',
			'includes/admin/class-ngtai-settings-page.php',
			'includes/admin/class-ngtai-health-page.php',
			'includes/admin/class-ngtai-events-page.php',
			'includes/admin/class-ngtai-agent-ops-page.php',
			'includes/admin/class-ngtai-approvals-page.php',
		]
	);
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$ngtai_files[] = 'includes/class-ngtai-cli.php';
}
foreach ( $ngtai_files as $ngtai_file ) {
	$ngtai_path = NGTAI_PLUGIN_DIR . $ngtai_file;
	if ( is_readable( $ngtai_path ) ) {
		require_once $ngtai_path;
	}
}
unset( $ngtai_files, $ngtai_file, $ngtai_path );

if ( class_exists( 'NGTAI_Activator' ) ) {
	register_activation_hook( __FILE__, [ 'NGTAI_Activator', 'activate' ] );
}
if ( class_exists( 'NGTAI_Deactivator' ) ) {
	register_deactivation_hook( __FILE__, [ 'NGTAI_Deactivator', 'deactivate' ] );
}
if ( class_exists( 'NGTAI_Plugin' ) ) {
	add_action( 'plugins_loaded', [ 'NGTAI_Plugin', 'init' ], 20 );
}
