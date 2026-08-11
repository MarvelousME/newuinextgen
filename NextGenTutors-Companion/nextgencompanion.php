<?php
/**
 * Plugin Name:       NextGenTutors-Companion
 * Plugin URI:        https://beyondinfinity.co.za/
 * Description:       Business logic, data layer, REST API, workflows, and multi-model BYOK AI suite for NextGen Tutors (BeyondInfinity theme).
 * Version:           1.9.19
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            BeyondInfinity
 * Text Domain:       nextgencompanion
 * Domain Path:       /languages
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NGC_VERSION', '1.9.19' );
define( 'NGC_PLUGIN_FILE', __FILE__ );
define( 'NGC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NGC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4 style autoloader for includes/.
 *
 * @param string $class Class name.
 */
function ngc_autoload( $class ) {
	$relative = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

	if ( 0 === strpos( $class, 'BIA_' ) ) {
		$bia = NGC_PLUGIN_DIR . 'includes/ai/' . $relative;
		if ( file_exists( $bia ) ) {
			require_once $bia;
		}
		return;
	}

	if ( 0 !== strpos( $class, 'NGC_' ) ) {
		return;
	}

	$paths = [
		NGC_PLUGIN_DIR . 'includes/' . $relative,
		NGC_PLUGIN_DIR . 'includes/ai/' . $relative,
		NGC_PLUGIN_DIR . 'includes/adapters/' . $relative,
		NGC_PLUGIN_DIR . 'includes/workflows/' . $relative,
		NGC_PLUGIN_DIR . 'includes/rest/' . $relative,
		NGC_PLUGIN_DIR . 'includes/integrations/' . $relative,
		NGC_PLUGIN_DIR . 'includes/studio/' . $relative,
		NGC_PLUGIN_DIR . 'includes/builder/' . $relative,
		NGC_PLUGIN_DIR . 'includes/shortcodes/' . $relative,
		NGC_PLUGIN_DIR . 'includes/admin/' . $relative,
		NGC_PLUGIN_DIR . 'includes/admin/framework/' . $relative,
		NGC_PLUGIN_DIR . 'includes/gamification/' . $relative,
		NGC_PLUGIN_DIR . 'includes/export/' . $relative,
		NGC_PLUGIN_DIR . 'includes/audit/' . $relative,
		NGC_PLUGIN_DIR . 'includes/diagnostics/' . $relative,
		NGC_PLUGIN_DIR . 'includes/matching/' . $relative,
		NGC_PLUGIN_DIR . 'includes/agents/' . $relative,
		NGC_PLUGIN_DIR . 'includes/cli/' . $relative,
		NGC_PLUGIN_DIR . 'includes/demo/' . $relative,
		NGC_PLUGIN_DIR . 'includes/provisioning/' . $relative,
		NGC_PLUGIN_DIR . 'includes/intelligence/' . $relative,
		NGC_PLUGIN_DIR . 'includes/platform/' . $relative,
		NGC_PLUGIN_DIR . 'includes/session/' . $relative,
		NGC_PLUGIN_DIR . 'includes/memory/' . $relative,
		NGC_PLUGIN_DIR . 'includes/talent/' . $relative,
		NGC_PLUGIN_DIR . 'includes/ui-library/' . $relative,
		NGC_PLUGIN_DIR . 'includes/ui-library/providers/' . $relative,
	];
	foreach ( $paths as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}

	// Interfaces: NGC_Foo_Interface → interface-ngc-foo.php (drop trailing -interface).
	if ( substr( $class, -10 ) === '_Interface' ) {
		$base = substr( $class, 0, -10 );
		$iface = 'interface-' . strtolower( str_replace( '_', '-', $base ) ) . '.php';
		foreach ( [ 'includes/memory/', 'includes/talent/', 'includes/adapters/' ] as $dir ) {
			$ipath = NGC_PLUGIN_DIR . $dir . $iface;
			if ( file_exists( $ipath ) ) {
				require_once $ipath;
				return;
			}
		}
	}
}
spl_autoload_register( 'ngc_autoload' );

require_once NGC_PLUGIN_DIR . 'includes/class-ngc-loader.php';
if ( ! NGC_Loader::boot() ) {
	return;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once NGC_PLUGIN_DIR . 'includes/cli/class-ngc-cli.php';
	require_once NGC_PLUGIN_DIR . 'includes/cli/class-ngc-system-cli.php';
	require_once NGC_PLUGIN_DIR . 'includes/cli/class-ngc-platform-cli.php';
}

/**
 * Main plugin singleton.
 */
final class NGC_Plugin {

	/**
	 * @var NGC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @return NGC_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wire activation hooks only; bootstrap deferred.
	 */
	private function __construct() {
		register_activation_hook( NGC_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( NGC_PLUGIN_FILE, [ $this, 'deactivate' ] );
		add_action( 'plugins_loaded', [ $this, 'bootstrap' ], 5 );
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		if ( class_exists( 'NGC_Database' ) ) {
			NGC_Database::create_tables();
		}
		if ( class_exists( 'NGC_Roles' ) ) {
			NGC_Roles::install();
		}
		if ( class_exists( 'NGC_Post_Types' ) ) {
			NGC_Post_Types::register();
		}
		if ( class_exists( 'NGC_Tutor_Seeder' ) ) {
			if ( NGC_Tutor_Seeder::demo_seed_allowed() ) {
				NGC_Tutor_Seeder::ensure_seeded();
			} else {
				NGC_Tutor_Seeder::purge_demo_tutors();
			}
		}
		if ( class_exists( 'NGC_Workflow_Email_Templates' ) ) {
			NGC_Workflow_Email_Templates::install_defaults();
		}
		if ( class_exists( 'BIA_Policy' ) ) {
			BIA_Policy::install();
		}
		if ( class_exists( 'NGC_Section_CMS' ) ) {
			NGC_Section_CMS::install_defaults();
		}
		do_action( 'ngc_fluentcrm_bootstrap' );
		flush_rewrite_rules();
		update_option( 'ngc_db_version', NGC_VERSION, false );
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Bootstrap all modules.
	 */
	public function bootstrap() {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		NGC_Plugin_Bootstrap::init();
	}

	/**
	 * Load translations at init or later.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'nextgencompanion', false, dirname( NGC_PLUGIN_BASENAME ) . '/languages' );
	}
}

NGC_Plugin::instance();

/**
 * NEXTGEN-BEYOND-INFINITY premium admin UI layer.
 * Safe: only loads on NextGen Companion admin pages and does not alter business logic.
 *
 * @param string $hook Current admin page hook.
 */
function ngc_beyond_infinity_admin_ui_assets( $hook ) {
	wp_enqueue_style( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/css/ngc-button-processing.css', [], NGC_VERSION );
	wp_enqueue_script( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/js/ngc-button-processing.js', [], NGC_VERSION, true );

	if ( false === strpos( (string) $hook, 'ngc' ) && false === strpos( (string) $hook, 'nextgen' ) ) {
		return;
	}
	wp_enqueue_style( 'ngc-beyond-infinity-admin-ui', NGC_PLUGIN_URL . 'assets/css/nextgen-beyond-infinity-admin.css', [ 'ngc-button-processing' ], NGC_VERSION );
	wp_enqueue_script( 'ngc-beyond-infinity-admin-ui', NGC_PLUGIN_URL . 'assets/js/nextgen-beyond-infinity-admin.js', [ 'ngc-button-processing' ], NGC_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'ngc_beyond_infinity_admin_ui_assets', 99 );

/**
 * Global admin button processing on all wp-admin screens.
 *
 * @param string $hook Current admin page hook.
 */
function ngc_enqueue_admin_button_processing( $hook ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	if ( wp_script_is( 'ngc-button-processing', 'enqueued' ) ) {
		return;
	}
	wp_enqueue_style( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/css/ngc-button-processing.css', [], NGC_VERSION );
	wp_enqueue_script( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/js/ngc-button-processing.js', [], NGC_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'ngc_enqueue_admin_button_processing', 5 );

/**
 * Themes offered by the front-end theme switch (stylesheet => label).
 *
 * Lives in the plugin (not a theme) so the control keeps working after the
 * active theme changes — including switching back from BeyondInfinity.
 *
 * @return array<string, string>
 */
function ngc_theme_switch_targets() {
	return apply_filters(
		'ngc_theme_switch_targets',
		[
			'agntix-child'                => __( 'Agntix', 'nextgencompanion' ),
			'nextgentutors-beyondinfinity' => __( 'BeyondInfinity', 'nextgencompanion' ),
		]
	);
}

/**
 * Resolve the theme the switch should target from the current stylesheet.
 *
 * @param string $current Current stylesheet.
 * @return array{stylesheet:string,label:string}|null
 */
function ngc_theme_switch_resolve_target( $current ) {
	$targets = ngc_theme_switch_targets();
	if ( count( $targets ) < 2 ) {
		return null;
	}

	// Prefer the "other" registered theme; default to BeyondInfinity.
	$target = '';
	if ( 'nextgentutors-beyondinfinity' === $current ) {
		$target = 'agntix-child';
	} elseif ( isset( $targets['nextgentutors-beyondinfinity'] ) ) {
		$target = 'nextgentutors-beyondinfinity';
	} else {
		foreach ( array_keys( $targets ) as $slug ) {
			if ( $slug !== $current ) {
				$target = $slug;
				break;
			}
		}
	}

	if ( ! $target || $target === $current ) {
		return null;
	}
	if ( ! wp_get_theme( $target )->exists() ) {
		return null;
	}

	return [
		'stylesheet' => $target,
		'label'      => $targets[ $target ] ?? $target,
	];
}

/**
 * Handle the front-end theme switch request (admin capability + nonce gated).
 */
function ngc_handle_theme_switch() {
	if ( ! current_user_can( 'switch_themes' ) ) {
		wp_die( esc_html__( 'You are not allowed to switch themes.', 'nextgencompanion' ), 403 );
	}

	$target = isset( $_GET['target'] ) ? sanitize_key( wp_unslash( $_GET['target'] ) ) : '';
	check_admin_referer( 'ngc_switch_theme_' . $target );

	$targets = ngc_theme_switch_targets();
	if ( ! isset( $targets[ $target ] ) || ! wp_get_theme( $target )->exists() ) {
		wp_die( esc_html__( 'Invalid theme target.', 'nextgencompanion' ), 400 );
	}

	switch_theme( $target );

	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_ngc_switch_theme', 'ngc_handle_theme_switch' );

/**
 * Render the floating theme switch (admins only) on every front-end page.
 */
function ngc_render_theme_switch_button() {
	// Public “Switch to Agntix” floating control removed from the website.
	return;

	if ( is_admin() || ! current_user_can( 'switch_themes' ) ) {
		return;
	}

	$current = get_stylesheet();
	$target  = ngc_theme_switch_resolve_target( $current );
	if ( ! $target ) {
		return;
	}

	$label = sprintf(
		/* translators: %s: theme name. */
		__( 'Switch to %s', 'nextgencompanion' ),
		$target['label']
	);
	$url = wp_nonce_url(
		add_query_arg(
			[
				'action' => 'ngc_switch_theme',
				'target' => $target['stylesheet'],
			],
			admin_url( 'admin-post.php' )
		),
		'ngc_switch_theme_' . $target['stylesheet']
	);
	?>
	<style id="ngc-theme-switch-css">
		.ngc-theme-switch{position:fixed;left:max(18px,env(safe-area-inset-left));bottom:max(22px,env(safe-area-inset-bottom));z-index:10061;display:inline-flex;align-items:center;gap:.6rem;min-height:48px;padding:.55rem 1rem .55rem .6rem;border:1px solid rgba(255,255,255,.28);border-radius:999px;color:#fff;background:linear-gradient(145deg,#3b2f6e,#241d47);box-shadow:0 14px 38px rgba(36,29,71,.4);font-family:"Source Sans 3","Segoe UI",system-ui,sans-serif;font-size:.86rem;font-weight:700;line-height:1;text-decoration:none;backdrop-filter:blur(12px);transition:transform .18s ease,box-shadow .18s ease}
		.ngc-theme-switch:hover,.ngc-theme-switch:focus-visible{color:#fff;transform:translateY(-3px);box-shadow:0 18px 44px rgba(36,29,71,.5)}
		.ngc-theme-switch:focus-visible{outline:3px solid rgba(196,163,90,.65);outline-offset:3px}
		.ngc-theme-switch__icon{width:34px;height:34px;display:grid;place-items:center;flex:0 0 auto;border-radius:50%;background:rgba(255,255,255,.14)}
		.ngc-theme-switch__icon svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
		@media (max-width:600px){.ngc-theme-switch{left:14px;bottom:max(14px,env(safe-area-inset-bottom));width:48px;padding:7px;justify-content:center}.ngc-theme-switch__label{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}}
	</style>
	<a class="ngc-theme-switch" href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
		<span class="ngc-theme-switch__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8.5"/><path d="M12 3.5v17M3.5 12h17"/></svg>
		</span>
		<span class="ngc-theme-switch__label"><?php echo esc_html( $label ); ?></span>
	</a>
	<?php
}
// Theme switch floating button disabled — keep handler for admin tooling only.
// add_action( 'wp_footer', 'ngc_render_theme_switch_button', 90 );
