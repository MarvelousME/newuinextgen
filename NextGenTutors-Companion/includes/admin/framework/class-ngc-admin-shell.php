<?php
/**
 * Unified WordPress admin shell — single parent menu NEXT GEN TUTORS v1.0.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the sole NextGen top-level admin menu and bootstraps the framework.
 */
final class NGC_Admin_Shell {

	public const PARENT_SLUG = 'ngt-admin';
	public const CAPABILITY  = 'manage_options';

	/**
	 * Display title (dynamic via version provider).
	 *
	 * @return string
	 */
	public static function menu_title() {
		return class_exists( 'NGC_Platform_Version' )
			? NGC_Platform_Version::display_title()
			: 'NEXT GEN TUTORS v1.0';
	}

	/**
	 * @deprecated Use menu_title().
	 */
	public const MENU_TITLE = 'NEXT GEN TUTORS v1.0';

	/**
	 * Boot early so other plugins can resolve the parent slug.
	 */
	public static function init() {
		$helpers = dirname( __FILE__ ) . '/class-ngc-admin-helpers.php';
		if ( is_readable( $helpers ) ) {
			require_once $helpers;
		}
		add_action( 'admin_menu', [ __CLASS__, 'register_parent' ], 1 );
		add_action( 'admin_menu', [ 'NGC_Admin_Navigation', 'build' ], 999 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar' ], 80 );
		add_filter( 'login_redirect', [ __CLASS__, 'login_redirect' ], 20, 3 );
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'dashboard_widget' ] );

		NGC_Admin_Catalog::register_defaults();
		NGC_Admin_Registry::init();
		NGC_Admin_Breadcrumbs::init();
		NGC_Admin_Search::init();
		NGC_Admin_Badges::init();
		NGC_Admin_Layout::init();
		NGC_Admin_Theme::init();
		NGC_Admin_Nav_UI::init();
		NGC_Admin_Notifications::init();
		NGC_Admin_Prefs::init();
		NGC_Admin_Entity_Registry::init();
	}

	/**
	 * Single top-level entry.
	 */
	public static function register_parent() {
		$title = self::menu_title();
		add_menu_page(
			$title,
			$title,
			self::CAPABILITY,
			self::PARENT_SLUG,
			[ __CLASS__, 'render_landing' ],
			'dashicons-welcome-learn-more',
			2
		);
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Mission Control', 'nextgencompanion' ),
			__( 'Mission Control', 'nextgencompanion' ),
			self::CAPABILITY,
			self::PARENT_SLUG,
			[ __CLASS__, 'render_landing' ]
		);
	}

	/**
	 * Default landing — Mission Control when available, else operations overview.
	 */
	public static function render_landing() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		if ( class_exists( 'NGTMC_Admin' ) && method_exists( 'NGTMC_Admin', 'render' ) ) {
			NGTMC_Admin::render();
			return;
		}
		if ( class_exists( 'NGC_Admin' ) && method_exists( 'NGC_Admin', 'render_dashboard' ) ) {
			NGC_Admin::render_dashboard();
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( self::menu_title() ) . '</h1>';
		echo '<p>' . esc_html__( 'Install Mission Control for the operational command centre.', 'nextgencompanion' ) . '</p></div>';
	}

	/**
	 * Shared chrome assets on all NGT admin screens.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		if ( ! self::is_ngt_screen() ) {
			return;
		}
		$ver = NGC_VERSION;
		wp_enqueue_style( 'ngt-admin-tokens', NGC_PLUGIN_URL . 'assets/css/admin-tokens.css', [], $ver );
		wp_enqueue_style( 'ngt-admin-shell', NGC_PLUGIN_URL . 'assets/css/admin-shell.css', [ 'ngt-admin-tokens' ], $ver );
		wp_enqueue_script( 'ngt-admin-shell', NGC_PLUGIN_URL . 'assets/js/admin-shell.js', [], $ver, true );
		wp_enqueue_script( 'ngt-admin-motion', NGC_PLUGIN_URL . 'assets/js/admin-motion.js', [ 'ngt-admin-shell' ], $ver, true );
		wp_enqueue_script( 'ngt-admin-theme-designer', NGC_PLUGIN_URL . 'assets/js/admin-theme-designer.js', [ 'ngt-admin-shell' ], $ver, true );

		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_localize_script(
			'ngt-admin-shell',
			'ngtAdminShell',
			[
				'restRoot' => esc_url_raw( rest_url( 'ngc/v1/admin' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'parent'   => self::PARENT_SLUG,
				'title'    => self::menu_title(),
				'version'  => class_exists( 'NGC_Platform_Version' ) ? NGC_Platform_Version::bundle() : [],
				'page'     => $page,
				'searchUrl'=> admin_url( 'admin.php?page=' . self::PARENT_SLUG . '&ngt_search=' ),
				'i18n'     => [
					'searchPlaceholder' => __( 'Search NextGen administration…', 'nextgencompanion' ),
					'noResults'         => __( 'No matching screens.', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function is_ngt_screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $page ) {
			return false;
		}
		if ( self::PARENT_SLUG === $page || 0 === strpos( $page, 'ngt' ) || 0 === strpos( $page, 'ngc' ) || 0 === strpos( $page, 'ngtai' ) || 0 === strpos( $page, 'ui-ux-pro-max' ) ) {
			return true;
		}
		return (bool) NGC_Admin_Registry::get_screen( $page );
	}

	/**
	 * Admin bar quick access.
	 *
	 * @param WP_Admin_Bar $bar Bar.
	 */
	public static function admin_bar( $bar ) {
		if ( ! current_user_can( self::CAPABILITY ) || ! is_admin_bar_showing() ) {
			return;
		}
		$bar->add_node(
			[
				'id'    => 'ngt-admin',
				'title' => self::menu_title(),
				'href'  => admin_url( 'admin.php?page=' . self::PARENT_SLUG ),
				'meta'  => [ 'title' => __( 'Open NextGen Mission Control', 'nextgencompanion' ) ],
			]
		);
	}

	/**
	 * @param string           $redirect Redirect.
	 * @param string           $request  Requested.
	 * @param WP_User|WP_Error $user     User.
	 * @return string
	 */
	public static function login_redirect( $redirect, $request, $user ) {
		unset( $request );
		if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
			return $redirect;
		}
		if ( user_can( $user, self::CAPABILITY ) ) {
			$prefs = class_exists( 'NGC_Admin_Prefs' ) ? NGC_Admin_Prefs::get( (int) $user->ID ) : [];
			$land  = sanitize_key( (string) ( $prefs['landing'] ?? self::PARENT_SLUG ) );
			return admin_url( 'admin.php?page=' . ( $land ?: self::PARENT_SLUG ) );
		}
		return $redirect;
	}

	/**
	 * WP dashboard widget.
	 */
	public static function dashboard_widget() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'ngt_admin_shell',
			self::menu_title(),
			static function () {
				$url = admin_url( 'admin.php?page=' . self::PARENT_SLUG );
				echo '<p>' . esc_html__( 'Open the unified administration experience.', 'nextgencompanion' ) . '</p>';
				echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Mission Control', 'nextgencompanion' ) . '</a></p>';
			}
		);
	}

	/**
	 * @return string
	 */
	public static function parent() {
		return self::PARENT_SLUG;
	}
}
