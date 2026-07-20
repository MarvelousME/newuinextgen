<?php
/**
 * Platform data/analytics/privacy admin screens.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin pages for real data verification, demo mode, analytics, tracking.
 */
class NGC_Platform_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 60 );
		add_action( 'admin_init', [ __CLASS__, 'handle_actions' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue analytics dashboard assets.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ngc-platform-analytics' !== $page && false === strpos( (string) $hook, 'ngc-platform-analytics' ) ) {
			return;
		}

		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			[],
			'4.4.1',
			true
		);
		wp_enqueue_style(
			'ngc-platform-analytics',
			NGC_PLUGIN_URL . 'assets/css/ngc-platform-analytics.css',
			[],
			NGC_VERSION
		);
		wp_enqueue_script(
			'ngc-platform-analytics',
			NGC_PLUGIN_URL . 'assets/js/ngc-platform-analytics.js',
			[ 'jquery', 'chartjs' ],
			NGC_VERSION,
			true
		);

		$snapshot = NGC_Platform_Analytics::snapshot();
		wp_localize_script(
			'ngc-platform-analytics',
			'ngcPlatformAnalytics',
			[
				'restUrl'  => rest_url( 'ngc/v1/platform/analytics' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'demo'     => NGC_Platform_Demo::is_enabled() && ! empty( $_GET['demo'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'currency' => 'R',
				'initial'  => $snapshot,
				'i18n'     => [
					'refresh' => __( 'Refresh', 'nextgencompanion' ),
					'live'    => __( 'Live updates', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * Register platform admin menus.
	 */
	public static function register_menus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$parent = 'ngc-platform';
		add_menu_page(
			__( 'Platform Layer', 'nextgencompanion' ),
			__( 'Platform', 'nextgencompanion' ),
			'manage_options',
			$parent,
			[ __CLASS__, 'render_data_source_verification' ],
			'dashicons-database-view',
			56
		);

		$pages = [
			[ 'ngc-platform-verify', __( 'Data Source Verification', 'nextgencompanion' ), 'render_data_source_verification' ],
			[ 'ngc-platform-demo', __( 'Demo Journey Manager', 'nextgencompanion' ), 'render_demo_journey_manager' ],
			[ 'ngc-platform-analytics', __( 'Analytics Dashboard', 'nextgencompanion' ), 'render_analytics_dashboard' ],
			[ 'ngc-platform-profiling', __( 'User Profiling Dashboard', 'nextgencompanion' ), 'render_user_profiling_dashboard' ],
			[ 'ngc-platform-acquisition', __( 'Acquisition Dashboard', 'nextgencompanion' ), 'render_acquisition_dashboard' ],
			[ 'ngc-platform-affiliates', __( 'Affiliate Tracking Dashboard', 'nextgencompanion' ), 'render_affiliate_tracking_dashboard' ],
			[ 'ngc-platform-cookies', __( 'Cookie Tracking Settings', 'nextgencompanion' ), 'render_cookie_tracking_settings' ],
			[ 'ngc-platform-privacy', __( 'Privacy/Consent Settings', 'nextgencompanion' ), 'render_privacy_consent_settings' ],
			[ 'ngc-platform-health', __( 'Data Health Checks', 'nextgencompanion' ), 'render_data_health_checks' ],
			[ 'ngc-platform-repair', __( 'Self-Healing Repair Tools', 'nextgencompanion' ), 'render_self_healing_tools' ],
		];
		foreach ( $pages as $page ) {
			add_submenu_page( $parent, $page[1], $page[1], 'manage_options', $page[0], [ __CLASS__, $page[2] ] );
		}
	}

	/**
	 * Handle admin actions.
	 */
	public static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['ngc_save_tracking'] ) && check_admin_referer( 'ngc_platform_settings' ) ) {
			update_option( 'ngc_tracking_disabled', ! empty( $_POST['ngc_tracking_disabled'] ) ? '1' : '0', false );
			update_option( 'ngc_require_cookie_consent', ! empty( $_POST['ngc_require_cookie_consent'] ) ? '1' : '0', false );
			update_option( 'ngc_cookie_retention_days', max( 1, (int) ( $_POST['ngc_cookie_retention_days'] ?? 365 ) ), false );
		}
		if ( isset( $_POST['ngc_seed_demo'] ) && check_admin_referer( 'ngc_platform_settings' ) ) {
			NGC_Platform_Demo::set_enabled( true );
			NGC_Platform_Demo::seed_demo_users();
		}
		if ( isset( $_POST['ngc_clear_demo'] ) && check_admin_referer( 'ngc_platform_settings' ) ) {
			NGC_Platform_Demo::clear_demo_data();
			NGC_Platform_Demo::set_enabled( false );
		}
		if ( isset( $_POST['ngc_reset_cookies'] ) && check_admin_referer( 'ngc_platform_settings' ) ) {
			NGC_Platform_Tracking::clear_tracking_cookies();
		}
	}

	/**
	 * Screen: Data Source Verification.
	 */
	public static function render_data_source_verification() {
		self::wrap_start( __( 'Data Source Verification', 'nextgencompanion' ) );
		$schema = NGC_Platform_Repository::verify_schema();
		?>
		<p><?php esc_html_e( 'Every dashboard/public/admin value must resolve from repository/service/plugin integration. Static fallback is allowed only as explicit EMPTY STATE.', 'nextgencompanion' ); ?></p>
		<pre><?php echo esc_html( wp_json_encode( $schema, JSON_PRETTY_PRINT ) ); ?></pre>
		<?php self::render_ui_data_source_table(); ?>
		<?php
		self::wrap_end();
	}

	/**
	 * Screen: Demo Journey Manager.
	 */
	public static function render_demo_journey_manager() {
		self::wrap_start( __( 'Demo Journey Manager', 'nextgencompanion' ) );
		$verify = NGC_Platform_Demo::verify_payloads();
		?>
		<p><?php echo esc_html( sprintf( 'Demo mode: %s', NGC_Platform_Demo::is_enabled() ? 'enabled' : 'disabled' ) ); ?></p>
		<pre><?php echo esc_html( wp_json_encode( $verify, JSON_PRETTY_PRINT ) ); ?></pre>
		<form method="post">
			<?php wp_nonce_field( 'ngc_platform_settings' ); ?>
			<p><button class="button button-primary" name="ngc_seed_demo" type="submit"><?php esc_html_e( 'Seed Demo Users & Data', 'nextgencompanion' ); ?></button></p>
			<p><button class="button" name="ngc_clear_demo" type="submit"><?php esc_html_e( 'Clear Demo Users & Data', 'nextgencompanion' ); ?></button></p>
		</form>
		<?php
		self::wrap_end();
	}

	/**
	 * Screen: Analytics Dashboard.
	 */
	public static function render_analytics_dashboard() {
		self::wrap_start( __( 'Analytics Dashboard', 'nextgencompanion' ) );
		?>
		<div id="ngc-pa-dashboard" class="ngc-pa-wrap">
			<p class="description"><?php esc_html_e( 'Realtime platform metrics with interactive drilldowns. Click any KPI or chart slice to inspect the breakdown.', 'nextgencompanion' ); ?></p>

			<div class="ngc-pa-toolbar">
				<span id="ngc-pa-status" class="ngc-pa-status"><?php esc_html_e( 'Loading…', 'nextgencompanion' ); ?></span>
				<label class="ngc-pa-live">
					<input type="checkbox" id="ngc-pa-live" checked />
					<?php esc_html_e( 'Live updates (30s)', 'nextgencompanion' ); ?>
				</label>
				<button type="button" class="button button-primary" id="ngc-pa-refresh"><?php esc_html_e( 'Refresh now', 'nextgencompanion' ); ?></button>
				<button type="button" class="button" id="ngc-pa-toggle-matrix" aria-expanded="false"><?php esc_html_e( 'Metric matrix', 'nextgencompanion' ); ?></button>
			</div>

			<div id="ngc-pa-kpis" class="ngc-pa-kpis" aria-live="polite"></div>

			<section id="ngc-pa-drill" class="ngc-pa-drill" hidden>
				<div class="ngc-pa-drill__head">
					<div>
						<h2 id="ngc-pa-drill-title"><?php esc_html_e( 'Drilldown', 'nextgencompanion' ); ?></h2>
						<p id="ngc-pa-drill-meta" class="ngc-pa-drill__meta"></p>
					</div>
					<button type="button" class="button" id="ngc-pa-drill-close"><?php esc_html_e( 'Close', 'nextgencompanion' ); ?></button>
				</div>
				<ul id="ngc-pa-drill-list" class="ngc-pa-drill__list"></ul>
			</section>

			<div class="ngc-pa-grid">
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Audience mix', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Parents, students, and tutors. Click a segment to drill down.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-audience" data-drill="ngc-pa-chart-audience"></canvas></div>
				</article>
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Tutor pipeline', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Applicants vs approved vs rejected.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-pipeline" data-drill="ngc-pa-chart-pipeline"></canvas></div>
				</article>
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Lessons', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Active, completed, and cancelled bookings.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-lessons" data-drill="ngc-pa-chart-lessons"></canvas></div>
				</article>
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Payments', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Paid, pending, failed, and refunds.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-payments" data-drill="ngc-pa-chart-payments"></canvas></div>
				</article>
				<article class="ngc-pa-card ngc-pa-card--wide">
					<h3><?php esc_html_e( 'Funnel drop-off', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Landing → lead → match → booking → payment.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-funnel" data-drill="ngc-pa-chart-funnel"></canvas></div>
				</article>
				<article class="ngc-pa-card ngc-pa-card--wide">
					<h3><?php esc_html_e( 'Lead sources', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Acquisition source performance.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-sources" data-drill="ngc-pa-chart-sources"></canvas></div>
				</article>
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Campaigns / query params', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Campaign key performance.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-campaigns" data-drill="ngc-pa-chart-campaigns"></canvas></div>
				</article>
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Devices', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Desktop / mobile / tablet mix.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-devices" data-drill="ngc-pa-chart-devices"></canvas></div>
				</article>
				<article class="ngc-pa-card">
					<h3><?php esc_html_e( 'Browsers', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Browser breakdown from analytics events.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-browsers" data-drill="ngc-pa-chart-browsers"></canvas></div>
				</article>
				<article class="ngc-pa-card ngc-pa-card--wide">
					<h3><?php esc_html_e( 'Locations', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Visitor country / region counts.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-locations" data-drill="ngc-pa-chart-locations"></canvas></div>
				</article>
				<article class="ngc-pa-card ngc-pa-card--wide">
					<h3><?php esc_html_e( 'Affiliates', 'nextgencompanion' ); ?></h3>
					<p><?php esc_html_e( 'Clicks by affiliate id.', 'nextgencompanion' ); ?></p>
					<div class="ngc-pa-chart-host"><canvas id="ngc-pa-chart-affiliates" data-drill="ngc-pa-chart-affiliates"></canvas></div>
				</article>
			</div>

			<div id="ngc-pa-matrix" class="ngc-pa-matrix-wrap" hidden>
				<?php self::render_metric_matrix(); ?>
			</div>
		</div>
		<?php
		self::wrap_end();
	}

	/**
	 * Screen: User Profiling Dashboard.
	 */
	public static function render_user_profiling_dashboard() {
		self::wrap_start( __( 'User Profiling Dashboard', 'nextgencompanion' ) );
		$profiles = NGC_Platform_Repository::list( 'user_profiles', [ 'limit' => 50, 'order_by' => 'updated_at' ] );
		?>
		<table class="widefat striped">
			<thead><tr><th>User</th><th>Journey</th><th>Completeness</th><th>Acquisition</th><th>Sessions</th><th>Updated</th></tr></thead>
			<tbody>
			<?php foreach ( $profiles as $row ) : ?>
				<tr>
					<td><?php echo (int) $row['user_id']; ?></td>
					<td><?php echo esc_html( $row['journey_state'] ?? '' ); ?></td>
					<td><?php echo (int) ( $row['profile_completeness'] ?? 0 ); ?>%</td>
					<td><?php echo esc_html( $row['acquisition_source'] ?? '' ); ?></td>
					<td><?php echo (int) ( $row['session_count'] ?? 0 ); ?></td>
					<td><?php echo esc_html( $row['updated_at'] ?? '' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		self::wrap_end();
	}

	/**
	 * Screen: Acquisition Dashboard.
	 */
	public static function render_acquisition_dashboard() {
		self::wrap_start( __( 'Acquisition Dashboard', 'nextgencompanion' ) );
		$rows = NGC_Platform_Repository::list( 'acquisition', [ 'limit' => 100, 'order_by' => 'id' ] );
		echo '<pre>' . esc_html( wp_json_encode( $rows, JSON_PRETTY_PRINT ) ) . '</pre>';
		self::wrap_end();
	}

	/**
	 * Screen: Affiliate Tracking Dashboard.
	 */
	public static function render_affiliate_tracking_dashboard() {
		self::wrap_start( __( 'Affiliate Tracking Dashboard', 'nextgencompanion' ) );
		$rows = NGC_Platform_Repository::list( 'affiliates', [ 'limit' => 100, 'order_by' => 'id' ] );
		echo '<pre>' . esc_html( wp_json_encode( $rows, JSON_PRETTY_PRINT ) ) . '</pre>';
		self::wrap_end();
	}

	/**
	 * Screen: Cookie Tracking Settings.
	 */
	public static function render_cookie_tracking_settings() {
		self::wrap_start( __( 'Cookie Tracking Settings', 'nextgencompanion' ) );
		?>
		<form method="post">
			<?php wp_nonce_field( 'ngc_platform_settings' ); ?>
			<table class="form-table">
				<tr><th><?php esc_html_e( 'Disable tracking', 'nextgencompanion' ); ?></th><td><input type="checkbox" name="ngc_tracking_disabled" value="1" <?php checked( get_option( 'ngc_tracking_disabled', '0' ), '1' ); ?> /></td></tr>
				<tr><th><?php esc_html_e( 'Require consent', 'nextgencompanion' ); ?></th><td><input type="checkbox" name="ngc_require_cookie_consent" value="1" <?php checked( get_option( 'ngc_require_cookie_consent', '1' ), '1' ); ?> /></td></tr>
				<tr><th><?php esc_html_e( 'Cookie retention days', 'nextgencompanion' ); ?></th><td><input type="number" name="ngc_cookie_retention_days" min="1" value="<?php echo esc_attr( get_option( 'ngc_cookie_retention_days', 365 ) ); ?>" /></td></tr>
			</table>
			<p><button class="button button-primary" name="ngc_save_tracking" type="submit"><?php esc_html_e( 'Save Tracking Settings', 'nextgencompanion' ); ?></button></p>
			<p><button class="button" name="ngc_reset_cookies" type="submit"><?php esc_html_e( 'Reset Tracking Cookies', 'nextgencompanion' ); ?></button></p>
		</form>
		<?php
		self::wrap_end();
	}

	/**
	 * Screen: Privacy/Consent Settings.
	 */
	public static function render_privacy_consent_settings() {
		self::wrap_start( __( 'Privacy/Consent Settings', 'nextgencompanion' ) );
		$consents = NGC_Platform_Repository::list( 'consent', [ 'limit' => 50, 'order_by' => 'id' ] );
		echo '<p>' . esc_html__( 'User data export/erasure is supported through native WordPress personal data tools. IPs are anonymized in consent context.', 'nextgencompanion' ) . '</p>';
		echo '<pre>' . esc_html( wp_json_encode( $consents, JSON_PRETTY_PRINT ) ) . '</pre>';
		self::wrap_end();
	}

	/**
	 * Screen: Data Health Checks.
	 */
	public static function render_data_health_checks() {
		self::wrap_start( __( 'Data Health Checks', 'nextgencompanion' ) );
		$checks = [
			'tables'          => NGC_Platform_Repository::verify_schema(),
			'demo_payloads'   => NGC_Platform_Demo::verify_payloads(),
			'metrics'         => [ 'ok' => ! empty( NGC_Platform_Analytics::snapshot() ) ],
			'roles'           => NGC_Verification::check_pass( NGC_Verification::run_checks(), 'roles' ),
			'rest_routes'     => isset( rest_get_server()->get_routes()['/ngc/v1/platform/analytics'] ),
			'tutor_calendar_service' => class_exists( 'NGC_Tutor_Calendar_Service', false ),
			'tutor_calendar_rest' => isset( rest_get_server()->get_routes()['/nextgen/v1/tutors/(?P<tutor_id>\d+)/calendar'] ),
			'tutor_calendar_shortcode' => shortcode_exists( 'nextgen_tutor_calendar' ),
			'tutor_calendar_demo' => ! empty( NGC_Platform_Demo::get_payload( 'demo_tutor_calendar' ) ),
		];
		$checks['ok'] = ! in_array( false, array_map( static function ( $item ) {
			return is_array( $item ) ? (bool) ( $item['ok'] ?? false ) : (bool) $item;
		}, $checks ), true );
		echo '<pre>' . esc_html( wp_json_encode( $checks, JSON_PRETTY_PRINT ) ) . '</pre>';
		self::wrap_end();
	}

	/**
	 * Screen: Self-Healing Repair Tools.
	 */
	public static function render_self_healing_tools() {
		self::wrap_start( __( 'Self-Healing Repair Tools', 'nextgencompanion' ) );
		if ( isset( $_GET['ngc_repair_all'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_repair_all' ) ) {
			$repair = NGC_Self_Healing::repair_all();
			NGC_Database::create_tables();
			NGC_Roles::install();
			echo '<pre>' . esc_html( wp_json_encode( $repair, JSON_PRETTY_PRINT ) ) . '</pre>';
		}
		?>
		<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-platform-repair&ngc_repair_all=1' ), 'ngc_repair_all' ) ); ?>"><?php esc_html_e( 'Run Full Repair', 'nextgencompanion' ); ?></a></p>
		<?php
		self::wrap_end();
	}

	/**
	 * @param string $title Title.
	 */
	private static function wrap_start( $title ) {
		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
	}

	/**
	 * End wrap.
	 */
	private static function wrap_end() {
		echo '</div>';
	}

	/**
	 * Render strict UI source mapping table.
	 */
	private static function render_ui_data_source_table() {
		$rows = [
			[ 'Tutor dashboard', 'monthEarnings', 'table', 'ngc_earnings', 'NGC_Platform_Analytics::snapshot()', 'EMPTY STATE: 0', 'platform/analytics endpoint' ],
			[ 'Parent dashboard', 'learnerCount', 'user meta', 'ngc_learners', 'NGC_Rest_Dashboard::parent()', 'EMPTY STATE: 0', 'dashboard endpoint response' ],
			[ 'Admin analytics', 'revenue', 'table', 'ngc_invoices', 'NGC_Platform_Analytics::snapshot()', 'EMPTY STATE: 0', 'platform/analytics endpoint' ],
			[ 'Profile timeline', 'events', 'table', 'ngc_analytics_events', 'NGC_Rest_Platform::profile()', 'EMPTY STATE: []', 'platform/profile endpoint' ],
		];
		echo '<table class="widefat striped"><thead><tr><th>UI Location</th><th>Field</th><th>Source Type</th><th>Source Name</th><th>Retrieval Method</th><th>Fallback</th><th>Verification</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr>';
			foreach ( $row as $cell ) {
				echo '<td>' . esc_html( $cell ) . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Render metric matrix.
	 */
	private static function render_metric_matrix() {
		$matrix = NGC_Platform_Analytics::metric_matrix();
		echo '<h2>Analytics metric matrix</h2>';
		echo '<table class="widefat striped"><thead><tr><th>Metric</th><th>Formula</th><th>Source Tables</th><th>Filters</th><th>Cache</th><th>Verification</th></tr></thead><tbody>';
		foreach ( $matrix as $metric => $meta ) {
			echo '<tr>';
			echo '<td>' . esc_html( $metric ) . '</td>';
			echo '<td>' . esc_html( $meta['formula'] ) . '</td>';
			echo '<td>' . esc_html( implode( ', ', (array) $meta['tables'] ) ) . '</td>';
			echo '<td>' . esc_html__( 'role/status/date filters', 'nextgencompanion' ) . '</td>';
			echo '<td>' . esc_html__( 'wp_cache 60s', 'nextgencompanion' ) . '</td>';
			echo '<td>' . esc_html__( 'platform/verify + admin checks', 'nextgencompanion' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}

