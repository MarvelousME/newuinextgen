<?php
/**
 * NGCPM smoke validation (run outside WordPress).
 *
 * Usage: php scripts/validate.php
 *
 * @package NextGenCorePluginManager
 */

$root   = dirname( __DIR__ );
$errors = 0;

echo "NGCPM validate\n";

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

foreach ( $files as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}
	$path = $file->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$out  = [];
	$code = 0;
	exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1', $out, $code );
	if ( 0 !== $code ) {
		echo "FAIL lint: {$path}\n";
		echo implode( "\n", $out ) . "\n";
		++$errors;
	}
}

$required = [
	'NextGenTutors-Plugin-Manager.php',
	'includes/class-ngcpm-ajax.php',
	'includes/class-ngcpm-scanner.php',
	'includes/class-ngcpm-assets.php',
	'includes/class-ngcpm-rate-limiter.php',
	'includes/class-ngcpm-queue.php',
	'includes/class-ngcpm-diagnostics.php',
	'includes/class-ngcpm-cookies.php',
	'includes/class-ngcpm-notifications.php',
	'includes/class-ngcpm-buttons.php',
	'includes/class-ngcpm-cli.php',
	'includes/class-ngcpm-repair.php',
	'includes/class-ngcpm-view-model.php',
	'templates/app.php',
	'templates/partials/views-extended.php',
	'assets/js/admin-ui.js',
	'assets/js/modules/ngcpm-core.js',
	'assets/js/modules/ngcpm-ui-feedback.js',
	'assets/js/modules/ngcpm-navigation.js',
	'assets/js/modules/ngcpm-queue.js',
	'assets/js/modules/ngcpm-repair.js',
	'assets/js/modules/ngcpm-diagnostics.js',
	'assets/js/modules/ngcpm-command.js',
	'assets/js/modules/ngcpm-actions.js',
	'assets/js/modules/ngcpm-notifications.js',
	'assets/css/admin-ui.css',
	'scripts/button-audit.php',
];

foreach ( $required as $rel ) {
	if ( ! is_file( $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel ) ) ) {
		echo "FAIL missing: {$rel}\n";
		++$errors;
	}
}

// Static source assertions.
$ajax = file_get_contents( $root . '/includes/class-ngcpm-ajax.php' );
if ( false === strpos( $ajax, 'send_action_result' ) || false === strpos( $ajax, 'wp_send_json_error' ) ) {
	echo "FAIL ajax must use send_action_result with wp_send_json_error on failure\n";
	++$errors;
}
if ( false === strpos( $ajax, 'ngcpm_queue_plan' ) || false === strpos( $ajax, 'NGCPM_Rate_Limiter::enforce' ) ) {
	echo "FAIL ajax must register queue_plan and rate limiting\n";
	++$errors;
}

$scanner = file_get_contents( $root . '/includes/class-ngcpm-scanner.php' );
if ( false === strpos( $scanner, 'registry_key' ) || false === strpos( $scanner, 'can_auto_install( $row_def )' ) ) {
	echo "FAIL scanner must pass registry_key to can_auto_install\n";
	++$errors;
}

$assets = file_get_contents( $root . '/includes/class-ngcpm-assets.php' );
if ( false !== strpos( $assets, 'fonts.googleapis.com' ) ) {
	echo "FAIL assets must not load Google Fonts CDN\n";
	++$errors;
}

if ( false === strpos( $ajax, 'reject_legacy_batch' ) ) {
	echo "FAIL ajax must block legacy batch endpoints\n";
	++$errors;
}
if ( false === strpos( $ajax, "verify( 'activate_plugins' )" ) || false === strpos( $ajax, "'activate' === \$strategy" ) ) {
	echo "FAIL repair must branch capability by strategy\n";
	++$errors;
}
if ( false !== strpos( $ajax, 'clear_cache' ) && false !== strpos( $ajax, 'handle_queue_plan' ) ) {
	$qp_start = strpos( $ajax, 'function handle_queue_plan' );
	$qp_chunk = $qp_start ? substr( $ajax, $qp_start, 400 ) : '';
	if ( false !== strpos( $qp_chunk, 'clear_cache' ) ) {
		echo "FAIL queue_plan must not clear cache on every request\n";
		++$errors;
	}
}

if ( false === strpos( $ajax, 'handle_dismiss_notification' ) || false === strpos( $ajax, 'handle_cookie_probe' ) ) {
	echo "FAIL ajax must register dismiss_notification and cookie_probe\n";
	++$errors;
}
if ( false === strpos( $ajax, 'handle_activate_all' ) || false === strpos( $ajax, 'install_missing' ) ) {
	echo "FAIL ajax must implement real activate_all and install_missing\n";
	++$errors;
}

$admin = file_get_contents( $root . '/includes/class-ngcpm-admin.php' );
if ( false !== strpos( $admin, 'NGCPM_Diagnostics::run_all()' ) ) {
	echo "FAIL admin render must not run diagnostics on page load\n";
	++$errors;
}

$js_files = [
	$root . '/assets/js/admin-ui.js',
	$root . '/assets/js/modules/ngcpm-core.js',
	$root . '/assets/js/modules/ngcpm-ui-feedback.js',
	$root . '/assets/js/modules/ngcpm-navigation.js',
	$root . '/assets/js/modules/ngcpm-queue.js',
	$root . '/assets/js/modules/ngcpm-repair.js',
	$root . '/assets/js/modules/ngcpm-diagnostics.js',
	$root . '/assets/js/modules/ngcpm-command.js',
	$root . '/assets/js/modules/ngcpm-actions.js',
];
$js = '';
foreach ( $js_files as $js_path ) {
	if ( is_file( $js_path ) ) {
		$js .= file_get_contents( $js_path );
	}
}
$installer = file_get_contents( $root . '/includes/class-ngcpm-installer.php' );
if ( false === strpos( $installer, 'boot_upgrader' ) || false === strpos( $installer, 'class-plugin-upgrader.php' ) ) {
	echo "FAIL installer must load Plugin_Upgrader dependencies\n";
	++$errors;
}
if ( false === strpos( $installer, 'install_direct_zip' ) || false === strpos( $installer, 'install_from_download_url' ) ) {
	echo "FAIL installer must use direct zip download without plugins_api\n";
	++$errors;
}
if ( false === strpos( $installer, 'WP_Filesystem' ) ) {
	echo "FAIL installer must initialize WP_Filesystem for AJAX installs\n";
	++$errors;
}

$assets = file_get_contents( $root . '/includes/class-ngcpm-assets.php' );
if ( false === strpos( $assets, 'array_key_first' ) || false === strpos( $assets, 'ngcpm-core' ) ) {
	echo "FAIL assets must localize NGCPM on first script module\n";
	++$errors;
}
if ( false === strpos( $assets, 'script_modules' ) || false === strpos( $assets, 'ngcpm-queue.js' ) ) {
	echo "FAIL assets must enqueue modular JS scripts\n";
	++$errors;
}
if ( false === strpos( $js, 'ngcpm_queue_plan' ) || false === strpos( $js, 'runSequential' ) ) {
	echo "FAIL JS must use sequential queue via ngcpm_queue_plan\n";
	++$errors;
}
if ( false !== strpos( $js, "runBatch('ngcpm_install_activate_all')" ) ) {
	echo "FAIL JS must not use atomic install_activate_all batch\n";
	++$errors;
}
if ( false === strpos( $js, 'showManual' ) || false === strpos( $js, 'show-manual' ) ) {
	echo "FAIL JS must implement show-manual handler\n";
	++$errors;
}
if ( false !== strpos( $js, 'commandList.innerHTML' ) ) {
	echo "FAIL command palette must not use innerHTML\n";
	++$errors;
}
if ( false === strpos( $js, 'maybeLoad' ) && false === strpos( $js, 'maybeLoadDiagnostics' ) ) {
	echo "FAIL JS must lazy-load diagnostics on view navigation\n";
	++$errors;
}

$queue = file_get_contents( $root . '/includes/class-ngcpm-queue.php' );
if ( false === strpos( $queue, 'build_plan' ) ) {
	echo "FAIL queue class must expose build_plan\n";
	++$errors;
}

if ( is_file( $root . '/templates/dashboard.php' ) ) {
	echo "FAIL legacy templates/dashboard.php should be removed\n";
	++$errors;
}

// Pure PHP unit-style checks for queue ordering logic (no WordPress).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		return $default;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', $root . '/tests-stub/wp-content' );
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}

require_once $root . '/includes/class-ngcpm-registry.php';
require_once $root . '/includes/class-ngcpm-settings.php';
require_once $root . '/includes/class-ngcpm-queue.php';

$stub_scan = [];
foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
	$stub_scan[ $slug ] = array_merge(
		$def,
		[
			'installed'     => true,
			'active'        => true,
			'health_status' => 'READY',
		]
	);
}
$stub_scan['woocommerce'] = array_merge(
	$stub_scan['woocommerce'],
	[
		'installed'        => false,
		'active'           => false,
		'can_auto_install' => true,
		'health_status'    => 'MISSING',
	]
);
$stub_scan['elementor'] = array_merge(
	$stub_scan['elementor'],
	[
		'installed'        => true,
		'active'           => false,
		'can_auto_install' => true,
		'health_status'    => 'INACTIVE',
	]
);
$stub_scan['ameliabooking'] = array_merge(
	$stub_scan['ameliabooking'],
	[
		'installed'        => false,
		'active'           => false,
		'can_auto_install' => false,
		'health_status'    => 'MANUAL_REQUIRED',
	]
);

$plan = NGCPM_Queue::build_plan( $stub_scan );
if ( count( $plan ) !== 3 ) {
	echo "FAIL queue plan should contain 3 items, got " . count( $plan ) . "\n";
	++$errors;
}
$actions = array_column( $plan, 'action' );
if ( ! in_array( 'install', $actions, true ) || ! in_array( 'activate', $actions, true ) || ! in_array( 'manual', $actions, true ) ) {
	echo "FAIL queue plan must include install, activate, and manual actions\n";
	++$errors;
}

if ( $errors > 0 ) {
	echo "\n{$errors} error(s)\n";
	exit( 1 );
}

// Run button audit.
$audit_out = [];
$audit_code = 0;
exec( 'php ' . escapeshellarg( $root . '/scripts/button-audit.php' ) . ' 2>&1', $audit_out, $audit_code );
if ( 0 !== $audit_code ) {
	echo implode( "\n", $audit_out ) . "\n";
	exit( 1 );
}

// Run unit test suite.
$test_out = [];
$test_code = 0;
exec( 'php ' . escapeshellarg( $root . '/tests/run.php' ) . ' 2>&1', $test_out, $test_code );
if ( 0 !== $test_code ) {
	echo implode( "\n", $test_out ) . "\n";
	exit( 1 );
}

echo "OK - all checks passed\n";
exit( 0 );
