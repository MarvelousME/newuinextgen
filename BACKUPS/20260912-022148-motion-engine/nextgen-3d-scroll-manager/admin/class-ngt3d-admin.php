<?php
/**
 * Admin menu bootstrap — registers the "3D Scrolling" menu and sub-screens.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin {

	public static function boot(): void {
		add_action( 'admin_menu', [ self::class, 'register_menus' ] );
		add_action( 'admin_notices', [ self::class, 'maybe_show_activation_notice' ] );
		add_action( 'admin_post_ngt3d_save_rule',      [ self::class, 'handle_save_rule' ] );
		add_action( 'admin_post_ngt3d_bulk_action',    [ self::class, 'handle_bulk_action' ] );
		add_action( 'admin_post_ngt3d_save_settings',  [ self::class, 'handle_save_settings' ] );
		add_action( 'admin_post_ngt3d_clear_log',      [ self::class, 'handle_clear_log' ] );
		add_action( 'admin_post_ngt3d_import',         [ self::class, 'handle_import' ] );
	}

	public static function register_menus(): void {
		add_menu_page(
			__( '3D Scrolling', 'ngt-3d-scroll' ),
			__( '3D Scrolling', 'ngt-3d-scroll' ),
			'manage_options',
			'ngt-3d-scroll',
			[ self::class, 'render_rules_page' ],
			'dashicons-align-center',
			58
		);

		add_submenu_page( 'ngt-3d-scroll', __( 'Rules', 'ngt-3d-scroll' ),            __( 'Rules', 'ngt-3d-scroll' ),            'manage_options', 'ngt-3d-scroll',            [ self::class, 'render_rules_page' ] );
		add_submenu_page( 'ngt-3d-scroll', __( 'Add Rule', 'ngt-3d-scroll' ),         __( 'Add Rule', 'ngt-3d-scroll' ),         'manage_options', 'ngt-3d-scroll-add',        [ self::class, 'render_edit_page' ] );
		add_submenu_page( 'ngt-3d-scroll', __( 'Animation Library', 'ngt-3d-scroll' ),__( 'Animation Library', 'ngt-3d-scroll' ),'manage_options', 'ngt-3d-scroll-library',    [ self::class, 'render_library_page' ] );
		add_submenu_page( 'ngt-3d-scroll', __( 'Page Inspector', 'ngt-3d-scroll' ),   __( 'Page Inspector', 'ngt-3d-scroll' ),   'manage_options', 'ngt-3d-scroll-inspector',  [ self::class, 'render_inspector_page' ] );
		add_submenu_page( 'ngt-3d-scroll', __( 'Import / Export', 'ngt-3d-scroll' ),  __( 'Import / Export', 'ngt-3d-scroll' ),  'manage_options', 'ngt-3d-scroll-import',     [ self::class, 'render_import_page' ] );
		add_submenu_page( 'ngt-3d-scroll', __( 'Diagnostics', 'ngt-3d-scroll' ),      __( 'Diagnostics', 'ngt-3d-scroll' ),      'manage_options', 'ngt-3d-scroll-diagnostics',[ self::class, 'render_diagnostics_page' ] );
		add_submenu_page( 'ngt-3d-scroll', __( 'Settings', 'ngt-3d-scroll' ),         __( 'Settings', 'ngt-3d-scroll' ),         'manage_options', 'ngt-3d-scroll-settings',   [ self::class, 'render_settings_page' ] );
	}

	// ── Page renderers (delegate to separate classes) ───────────────────────────

	public static function render_rules_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-rules.php';
		( new NGT3D_Admin_Rules() )->render();
	}

	public static function render_edit_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-edit.php';
		( new NGT3D_Admin_Edit() )->render();
	}

	public static function render_library_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-library.php';
		( new NGT3D_Admin_Library() )->render();
	}

	public static function render_inspector_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-inspector.php';
		( new NGT3D_Admin_Inspector() )->render();
	}

	public static function render_import_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-import-export.php';
		( new NGT3D_Admin_Import_Export() )->render();
	}

	public static function render_diagnostics_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-diagnostics.php';
		( new NGT3D_Admin_Diagnostics() )->render();
	}

	public static function render_settings_page(): void {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin-settings.php';
		( new NGT3D_Admin_Settings() )->render();
	}

	// ── Form handlers ───────────────────────────────────────────────────────────

	public static function handle_save_rule(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}
		check_admin_referer( 'ngt3d_save_rule', 'ngt3d_nonce' );

		$raw    = wp_unslash( $_POST );
		$result = NGT3D_Validator::sanitize_rule( (array) $raw );

		$id = isset( $raw['rule_id'] ) && (int) $raw['rule_id'] > 0 ? (int) $raw['rule_id'] : 0;

		if ( ! $result['valid'] ) {
			wp_redirect( add_query_arg(
				[ 'page' => ( $id ? 'ngt-3d-scroll-add&edit=' . $id : 'ngt-3d-scroll-add' ), 'ngt3d_error' => urlencode( implode( ' | ', $result['errors'] ) ) ],
				admin_url( 'admin.php' )
			) );
			exit;
		}

		if ( $id ) {
			NGT3D_Rule_Repository::update( $id, $result['data'] );
		} else {
			$id = NGT3D_Rule_Repository::create( $result['data'] );
		}

		wp_redirect( add_query_arg(
			[ 'page' => 'ngt-3d-scroll', 'ngt3d_saved' => 1 ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public static function handle_bulk_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}
		check_admin_referer( 'ngt3d_bulk', 'ngt3d_nonce' );

		$action = sanitize_key( $_POST['bulk_action'] ?? '' );
		$ids    = array_map( 'absint', (array) ( $_POST['rule_ids'] ?? [] ) );

		if ( ! empty( $ids ) ) {
			match ( $action ) {
				'enable'  => NGT3D_Rule_Repository::set_enabled( $ids, true ),
				'disable' => NGT3D_Rule_Repository::set_enabled( $ids, false ),
				'delete'  => NGT3D_Rule_Repository::delete( $ids ),
				default   => null,
			};
		}

		wp_redirect( add_query_arg( [ 'page' => 'ngt-3d-scroll', 'ngt3d_bulk' => $action ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}
		check_admin_referer( 'ngt3d_settings', 'ngt3d_nonce' );
		NGT3D_Runtime_Config::save_settings( (array) wp_unslash( $_POST ) );
		wp_redirect( add_query_arg( [ 'page' => 'ngt-3d-scroll-settings', 'ngt3d_saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_clear_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}
		check_admin_referer( 'ngt3d_clear_log', 'ngt3d_nonce' );
		NGT3D_Diagnostics::clear_log();
		wp_redirect( add_query_arg( [ 'page' => 'ngt-3d-scroll-diagnostics', 'ngt3d_cleared' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}
		check_admin_referer( 'ngt3d_import', 'ngt3d_nonce' );

		$json = '';

		// File upload.
		if ( ! empty( $_FILES['ngt3d_import_file']['tmp_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$json = file_get_contents( $_FILES['ngt3d_import_file']['tmp_name'] );
		} elseif ( ! empty( $_POST['ngt3d_import_json'] ) ) {
			$json = wp_unslash( $_POST['ngt3d_import_json'] );
		}

		$result = NGT3D_Validator::validate_import_json( (string) $json );
		$created = 0;

		foreach ( $result['rules'] as $rule_data ) {
			$id = NGT3D_Rule_Repository::create( $rule_data );
			if ( ! is_wp_error( $id ) ) {
				$created++;
			}
		}

		wp_redirect( add_query_arg( [
			'page'           => 'ngt-3d-scroll-import',
			'ngt3d_imported' => $created,
			'ngt3d_errors'   => count( $result['errors'] ),
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Show a notice if the table hasn't been installed yet.
	 */
	public static function maybe_show_activation_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( NGT3D_Schema::table_exists() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %s: link to run install */
			esc_html__( 'NGT 3D Scroll Manager: database table not yet created. %s', 'ngt-3d-scroll' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=ngt-3d-scroll-diagnostics' ) ) . '">' . esc_html__( 'Run Setup', 'ngt-3d-scroll' ) . '</a>'
		);
		echo '</p></div>';
	}

	/**
	 * Helper: render a standard admin page header.
	 *
	 * @param string $title Page title.
	 * @param string $sub   Current sub-menu slug.
	 */
	public static function page_header( string $title, string $sub = 'ngt-3d-scroll' ): void {
		// Flash notices.
		if ( ! empty( $_GET['ngt3d_saved'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved successfully.', 'ngt-3d-scroll' ) . '</p></div>';
		}
		if ( ! empty( $_GET['ngt3d_error'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-error"><p>' . esc_html( urldecode( (string) $_GET['ngt3d_error'] ) ) . '</p></div>'; // phpcs:ignore
		}
		?>
		<div class="wrap ngt3d-wrap">
			<h1 class="wp-heading-inline ngt3d-heading">
				<span class="ngt3d-badge">3D</span>
				<?php echo esc_html( $title ); ?>
			</h1>
		<?php
	}

	/**
	 * Helper: close the wrap div.
	 */
	public static function page_footer(): void {
		echo '</div><!-- .ngt3d-wrap -->';
	}
}
