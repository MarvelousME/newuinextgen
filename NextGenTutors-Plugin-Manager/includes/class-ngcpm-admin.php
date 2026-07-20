<?php
/**
 * Admin menu and dashboard.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI at page=ui-ux-pro-max.
 */
class NGCPM_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_exports' ] );
	}

	/**
	 * Register admin menu.
	 */
	public static function menu() {
		add_menu_page(
			__( 'NextGenTutors Plugin Manager', 'nextgentutors-plugin-manager' ),
			__( 'NextGenTutors Plugins', 'nextgentutors-plugin-manager' ),
			'manage_options',
			NGCPM_ADMIN_PAGE,
			[ __CLASS__, 'render' ],
			'dashicons-admin-plugins',
			58
		);

		add_submenu_page(
			NGCPM_ADMIN_PAGE,
			__( 'Settings', 'nextgentutors-plugin-manager' ),
			__( 'Settings', 'nextgentutors-plugin-manager' ),
			'manage_options',
			NGCPM_ADMIN_PAGE . '-settings',
			[ __CLASS__, 'render_settings' ]
		);
	}

	/**
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, NGCPM_ADMIN_PAGE ) ) {
			return;
		}
		NGCPM_Assets::enqueue();
	}

	/**
	 * Handle log export download.
	 */
	public static function handle_exports() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( NGCPM_ADMIN_PAGE . '-settings' !== $page && NGCPM_ADMIN_PAGE !== $page ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ngcpm_export_logs'] ) ) {
			check_admin_referer( 'ngcpm_export_logs' );
			NGCPM_Logger::export_json();
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ngcpm_clear_cache'] ) ) {
			check_admin_referer( 'ngcpm_clear_cache' );
			NGCPM_Scanner::clear_cache();
			add_settings_error( 'ngcpm', 'cache_cleared', __( 'Scan cache cleared.', 'nextgentutors-plugin-manager' ), 'updated' );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ngcpm_clear_logs'] ) ) {
			check_admin_referer( 'ngcpm_clear_logs' );
			NGCPM_Logger::clear();
			add_settings_error( 'ngcpm', 'logs_cleared', __( 'Logs cleared.', 'nextgentutors-plugin-manager' ), 'updated' );
		}
	}

	/**
	 * Main dashboard.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgentutors-plugin-manager' ) );
		}

		echo '<div class="ngcpm-root">';
		NGCPM_View_Model::render( NGCPM_View_Model::for_app( false, 20 ) );
		echo '</div>';
	}

	/**
	 * Settings page.
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgentutors-plugin-manager' ) );
		}
		$remote_urls = get_option( NGCPM_Settings::OPTION_REMOTE_ZIPS, [] );
		?>
		<div class="wrap ngcpm-wrap">
			<h1><?php esc_html_e( 'NextGenTutors Plugin Manager — Settings', 'nextgentutors-plugin-manager' ); ?></h1>
			<?php settings_errors( 'ngcpm' ); ?>
			<form method="post" action="options.php" class="ngcpm-settings-form">
				<?php settings_fields( NGCPM_Settings::OPTION_GROUP ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Enable frontend /ui-page', 'nextgentutors-plugin-manager' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( NGCPM_Settings::OPTION_FRONTEND ); ?>" value="1" <?php checked( NGCPM_Settings::frontend_enabled() ); ?> /> <?php esc_html_e( 'Allow [ngc_plugin_manager] shortcode', 'nextgentutors-plugin-manager' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Enable remote zip sources', 'nextgentutors-plugin-manager' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( NGCPM_Settings::OPTION_REMOTE ); ?>" value="1" <?php checked( NGCPM_Settings::remote_zips_enabled() ); ?> /> <?php esc_html_e( 'Only whitelisted URLs below', 'nextgentutors-plugin-manager' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Local zip directory', 'nextgentutors-plugin-manager' ); ?></th>
						<td>
							<input type="text" class="large-text" name="<?php echo esc_attr( NGCPM_Settings::OPTION_LOCAL_DIR ); ?>" value="<?php echo esc_attr( NGCPM_Settings::local_zip_dir() ); ?>" />
							<p class="description">
								<?php
								esc_html_e( 'Place premium and offline plugin .zip files here. Override with NGCPM_LOCAL_ZIP_DIR env or wp-config constant.', 'nextgentutors-plugin-manager' );
								echo ' ';
								echo esc_html(
									sprintf(
										/* translators: %d: zip count */
										_n( '%d zip detected.', '%d zips detected.', count( NGCPM_Local_Packages::inventory() ), 'nextgentutors-plugin-manager' ),
										count( NGCPM_Local_Packages::inventory() )
									)
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Auto-install local zips', 'nextgentutors-plugin-manager' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( NGCPM_Settings::OPTION_AUTO_LOCAL ); ?>" value="1" <?php checked( NGCPM_Settings::auto_install_local_enabled() ); ?> />
								<?php esc_html_e( 'When opening Plugin Manager, automatically install registry plugins that have matching zips in the local directory.', 'nextgentutors-plugin-manager' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Whitelisted remote zip URLs', 'nextgentutors-plugin-manager' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Map registry slug to HTTPS zip URL. Admin-only configuration.', 'nextgentutors-plugin-manager' ); ?></p>
				<?php foreach ( NGCPM_Registry::get_all() as $slug => $def ) : ?>
					<p>
						<label><strong><?php echo esc_html( $def['name'] ); ?></strong>
						<input type="url" class="large-text" name="<?php echo esc_attr( NGCPM_Settings::OPTION_REMOTE_ZIPS ); ?>[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_url( $remote_urls[ $slug ] ?? '' ); ?>" placeholder="https://..." />
						</label>
					</p>
				<?php endforeach; ?>
				<?php submit_button(); ?>
			</form>
			<p>
				<button type="button" class="button" data-action="clear-cache" data-ngcpm-settings-btn><?php esc_html_e( 'Clear cache (AJAX)', 'nextgentutors-plugin-manager' ); ?></button>
				<button type="button" class="button" data-action="force-rescan" data-ngcpm-settings-btn><?php esc_html_e( 'Force rescan (AJAX)', 'nextgentutors-plugin-manager' ); ?></button>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings&ngcpm_clear_cache=1' ), 'ngcpm_clear_cache' ) ); ?>"><?php esc_html_e( 'Force rescan (clear cache)', 'nextgentutors-plugin-manager' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings&ngcpm_export_logs=1' ), 'ngcpm_export_logs' ) ); ?>"><?php esc_html_e( 'Export logs JSON', 'nextgentutors-plugin-manager' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings&ngcpm_clear_logs=1' ), 'ngcpm_clear_logs' ) ); ?>"><?php esc_html_e( 'Clear logs', 'nextgentutors-plugin-manager' ); ?></a>
			</p>
			<script>
			(function () {
				if (typeof NGCPM === 'undefined' || typeof NGCPM_UI === 'undefined') {
					return;
				}
				document.querySelectorAll('[data-ngcpm-settings-btn]').forEach(function (btn) {
					btn.addEventListener('click', function (e) {
						e.preventDefault();
						var action = btn.getAttribute('data-action');
						if (action && NGCPM_UI.actions && NGCPM_UI.actions.handle) {
							NGCPM_UI.actions.handle(action);
						}
					});
				});
			})();
			</script>
		</div>
		<?php
	}
}
