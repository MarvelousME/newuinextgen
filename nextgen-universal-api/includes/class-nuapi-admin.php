<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NUAPI_Admin {

	const MENU_SLUG = 'nuapi-dashboard';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		add_action( 'wp_ajax_nuapi_scan', array( __CLASS__, 'ajax_scan' ) );
		add_action( 'wp_ajax_nuapi_toggle_table', array( __CLASS__, 'ajax_toggle_table' ) );
		add_action( 'wp_ajax_nuapi_toggle_write', array( __CLASS__, 'ajax_toggle_write' ) );
		add_action( 'wp_ajax_nuapi_generate_key', array( __CLASS__, 'ajax_generate_key' ) );
		add_action( 'wp_ajax_nuapi_revoke_key', array( __CLASS__, 'ajax_revoke_key' ) );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'Universal API', 'nuapi' ),
			__( 'Universal API', 'nuapi' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-rest-api',
			58
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::MENU_SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'nuapi-admin', NUAPI_URL . 'assets/admin.css', array(), NUAPI_VERSION );
		wp_enqueue_script( 'nuapi-admin', NUAPI_URL . 'assets/admin.js', array( 'jquery' ), NUAPI_VERSION, true );
		wp_localize_script( 'nuapi-admin', 'NUAPI', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'nuapi_admin' ),
			'restRoot'  => esc_url_raw( rest_url( 'nuapi/v1' ) ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
		) );
	}

	private static function verify_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		check_ajax_referer( 'nuapi_admin', 'nonce' );
	}

	public static function ajax_scan() {
		self::verify_ajax();
		wp_send_json_success( array( 'registry' => NUAPI_Scanner::get_registry( true ) ) );
	}

	public static function ajax_toggle_table() {
		self::verify_ajax();
		$table   = isset( $_POST['table'] ) ? preg_replace( '/[^a-zA-Z0-9_]/', '', wp_unslash( $_POST['table'] ) ) : '';
		$enabled = ! empty( $_POST['enabled'] ) && 'true' === $_POST['enabled'];
		if ( ! $table ) { wp_send_json_error( array( 'message' => 'Missing table' ), 400 ); }

		$settings = get_option( 'nuapi_settings', array() );
		$list     = array_diff( isset( $settings['enabled_tables'] ) ? (array) $settings['enabled_tables'] : array(), array( $table ) );
		if ( $enabled ) { $list[] = $table; }
		$settings['enabled_tables'] = array_values( array_unique( $list ) );

		if ( ! $enabled ) {
			$writeList = array_diff( isset( $settings['write_tables'] ) ? (array) $settings['write_tables'] : array(), array( $table ) );
			$settings['write_tables'] = array_values( array_unique( $writeList ) );
		}

		update_option( 'nuapi_settings', $settings );
		wp_send_json_success( array( 'table' => $table, 'enabled' => $enabled ) );
	}

	public static function ajax_toggle_write() {
		self::verify_ajax();
		$table   = isset( $_POST['table'] ) ? preg_replace( '/[^a-zA-Z0-9_]/', '', wp_unslash( $_POST['table'] ) ) : '';
		$enabled = ! empty( $_POST['enabled'] ) && 'true' === $_POST['enabled'];
		if ( ! $table ) { wp_send_json_error( array( 'message' => 'Missing table' ), 400 ); }

		$settings = get_option( 'nuapi_settings', array() );
		$list     = array_diff( isset( $settings['write_tables'] ) ? (array) $settings['write_tables'] : array(), array( $table ) );
		if ( $enabled ) { $list[] = $table; }
		$settings['write_tables'] = array_values( array_unique( $list ) );
		update_option( 'nuapi_settings', $settings );

		wp_send_json_success( array( 'table' => $table, 'enabled' => $enabled ) );
	}

	public static function ajax_generate_key() {
		self::verify_ajax();
		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : 'Untitled Key';
		$scope = ( isset( $_POST['scope'] ) && 'write' === $_POST['scope'] ) ? 'write' : 'read';
		$raw   = NUAPI_Security::generate_key( $label, $scope );
		wp_send_json_success( array( 'key' => $raw ) );
	}

	public static function ajax_revoke_key() {
		self::verify_ajax();
		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		if ( ! $id ) { wp_send_json_error( array( 'message' => 'Missing id' ), 400 ); }
		NUAPI_Security::revoke_key( $id );
		wp_send_json_success();
	}

	public static function render_page() {
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
		$registry = NUAPI_Scanner::get_registry();
		$settings = get_option( 'nuapi_settings', array() );
		$keys     = get_option( 'nuapi_api_keys', array() );
		?>
		<div class="wrap nuapi-wrap">
			<h1><span class="dashicons dashicons-rest-api"></span> NextGen Universal API</h1>
			<p class="nuapi-sub">Scans every active plugin, detects its native REST API where one is really live, and generates secure CRUD endpoints for the rest.</p>

			<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo esc_attr( self::MENU_SLUG ); ?>&tab=dashboard" class="nav-tab <?php echo 'dashboard' === $tab ? 'nav-tab-active' : ''; ?>">Dashboard</a>
				<a href="?page=<?php echo esc_attr( self::MENU_SLUG ); ?>&tab=tables" class="nav-tab <?php echo 'tables' === $tab ? 'nav-tab-active' : ''; ?>">Tables &amp; Permissions</a>
				<a href="?page=<?php echo esc_attr( self::MENU_SLUG ); ?>&tab=keys" class="nav-tab <?php echo 'keys' === $tab ? 'nav-tab-active' : ''; ?>">API Keys</a>
				<a href="?page=<?php echo esc_attr( self::MENU_SLUG ); ?>&tab=console" class="nav-tab <?php echo 'console' === $tab ? 'nav-tab-active' : ''; ?>">Test Console</a>
				<a href="?page=<?php echo esc_attr( self::MENU_SLUG ); ?>&tab=audit" class="nav-tab <?php echo 'audit' === $tab ? 'nav-tab-active' : ''; ?>">Audit Log</a>
			</h2>

			<div class="nuapi-tab-body">
				<?php
				switch ( $tab ) {
					case 'tables':  self::render_tables( $registry, $settings ); break;
					case 'keys':    self::render_keys( $keys ); break;
					case 'console': self::render_console( $settings ); break;
					case 'audit':   self::render_audit(); break;
					default:        self::render_dashboard( $registry ); break;
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function render_dashboard( array $registry ) {
		$native_count    = count( array_filter( $registry, function ( $p ) { return $p['has_native_api']; } ) );
		$generated_count = count( array_filter( $registry, function ( $p ) { return $p['needs_generated']; } ) );
		?>
		<div class="nuapi-stat-row">
			<div class="nuapi-stat"><strong><?php echo count( $registry ); ?></strong><span>Active Plugins Scanned</span></div>
			<div class="nuapi-stat nuapi-stat--ok"><strong><?php echo esc_html( $native_count ); ?></strong><span>Native REST APIs Detected</span></div>
			<div class="nuapi-stat nuapi-stat--warn"><strong><?php echo esc_html( $generated_count ); ?></strong><span>Plugins Needing Generated CRUD</span></div>
			<button class="button button-primary" id="nuapi-scan-btn">↻ Rescan Now</button>
		</div>

		<table class="widefat striped nuapi-table">
			<thead><tr><th>Plugin</th><th>Version</th><th>API Coverage</th><th>Tables Found</th><th>Action</th></tr></thead>
			<tbody>
			<?php foreach ( $registry as $slug => $plugin ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $plugin['name'] ); ?></strong><br><code><?php echo esc_html( $slug ); ?></code></td>
					<td><?php echo esc_html( $plugin['version'] ); ?></td>
					<td>
						<?php if ( $plugin['has_native_api'] ) : ?>
							<span class="nuapi-badge nuapi-badge--ok">Native API</span>
							<div class="nuapi-ns-list"><?php echo esc_html( implode( ', ', $plugin['native_api'] ) ); ?></div>
						<?php elseif ( ! empty( $plugin['tables'] ) ) : ?>
							<span class="nuapi-badge nuapi-badge--warn">Generated CRUD Available</span>
						<?php else : ?>
							<span class="nuapi-badge nuapi-badge--none">No API / No Tables</span>
						<?php endif; ?>
					</td>
					<td><?php echo count( $plugin['tables'] ); ?></td>
					<td>
						<?php if ( ! empty( $plugin['tables'] ) ) : ?>
							<a href="?page=<?php echo esc_attr( self::MENU_SLUG ); ?>&tab=tables#<?php echo esc_attr( $slug ); ?>" class="button button-small">Manage Tables</a>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private static function render_tables( array $registry, array $settings ) {
		$enabled_tables = isset( $settings['enabled_tables'] ) ? (array) $settings['enabled_tables'] : array();
		$write_tables   = isset( $settings['write_tables'] ) ? (array) $settings['write_tables'] : array();
		?>
		<div class="notice notice-warning inline"><p><strong>Security note:</strong> Enabling a table exposes it via <code>/wp-json/nuapi/v1/data/&lt;table&gt;</code> to anyone holding a valid API key, or an authenticated administrator session. Enable write access only for tables you understand well.</p></div>

		<?php foreach ( $registry as $slug => $plugin ) :
			if ( empty( $plugin['tables'] ) ) { continue; }
			?>
			<h3 id="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $plugin['name'] ); ?> <?php if ( $plugin['has_native_api'] ) : ?><span class="nuapi-badge nuapi-badge--ok">Also has native API</span><?php endif; ?></h3>
			<table class="widefat striped nuapi-table">
				<thead><tr><th>Table</th><th>Rows</th><th>Primary Key</th><th>Read Access</th><th>Write Access</th></tr></thead>
				<tbody>
				<?php foreach ( $plugin['tables'] as $table => $meta ) :
					$is_enabled = in_array( $table, $enabled_tables, true );
					$is_write   = in_array( $table, $write_tables, true );
					?>
					<tr>
						<td><code><?php echo esc_html( $table ); ?></code></td>
						<td><?php echo (int) $meta['row_count']; ?></td>
						<td><code><?php echo esc_html( $meta['primary_key'] ); ?></code></td>
						<td>
							<label class="nuapi-switch">
								<input type="checkbox" class="nuapi-toggle-table" data-table="<?php echo esc_attr( $table ); ?>" <?php checked( $is_enabled ); ?>>
								<span></span>
							</label>
						</td>
						<td>
							<label class="nuapi-switch nuapi-switch--warn">
								<input type="checkbox" class="nuapi-toggle-write" data-table="<?php echo esc_attr( $table ); ?>" <?php checked( $is_write ); ?> <?php disabled( ! $is_enabled ); ?>>
								<span></span>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
		<?php
	}

	private static function render_keys( array $keys ) {
		?>
		<div class="nuapi-card">
			<h3>Generate a new API key</h3>
			<div class="nuapi-key-form">
				<input type="text" id="nuapi-key-label" placeholder="Label, e.g. Mobile App" />
				<select id="nuapi-key-scope">
					<option value="read">Read only</option>
					<option value="write">Read &amp; Write</option>
				</select>
				<button class="button button-primary" id="nuapi-generate-key-btn">Generate Key</button>
			</div>
			<div id="nuapi-new-key-display" style="display:none" class="nuapi-key-reveal"></div>
		</div>

		<table class="widefat striped nuapi-table">
			<thead><tr><th>Label</th><th>Scope</th><th>Created</th><th>Status</th><th></th></tr></thead>
			<tbody>
			<?php if ( empty( $keys ) ) : ?>
				<tr><td colspan="5">No API keys yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $keys as $entry ) : ?>
				<tr>
					<td><?php echo esc_html( $entry['label'] ); ?></td>
					<td><span class="nuapi-badge <?php echo 'write' === $entry['scope'] ? 'nuapi-badge--warn' : 'nuapi-badge--ok'; ?>"><?php echo esc_html( ucfirst( $entry['scope'] ) ); ?></span></td>
					<td><?php echo esc_html( $entry['created'] ); ?></td>
					<td><?php echo ! empty( $entry['revoked'] ) ? '<span class="nuapi-badge nuapi-badge--none">Revoked</span>' : '<span class="nuapi-badge nuapi-badge--ok">Active</span>'; ?></td>
					<td>
						<?php if ( empty( $entry['revoked'] ) ) : ?>
							<button class="button button-small nuapi-revoke-key-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>">Revoke</button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">Send the key in the <code>X-NUAPI-Key</code> header on every request. Keys are stored as SHA-256 hashes — the raw key is shown only once, when generated.</p>
		<?php
	}

	private static function render_console( array $settings ) {
		$enabled_tables = isset( $settings['enabled_tables'] ) ? (array) $settings['enabled_tables'] : array();
		?>
		<div class="nuapi-card">
			<h3>Live Test Console</h3>
			<div class="nuapi-console-row">
				<select id="nuapi-console-method"><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select>
				<select id="nuapi-console-table">
					<option value="">— choose a table —</option>
					<?php foreach ( $enabled_tables as $table ) : ?>
						<option value="<?php echo esc_attr( $table ); ?>"><?php echo esc_html( $table ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" id="nuapi-console-id" placeholder="Row ID (GET one / PUT / DELETE)" style="width:180px" />
			</div>
			<textarea id="nuapi-console-body" placeholder='JSON body for POST / PUT, e.g. {"column":"value"}'></textarea>
			<button class="button button-primary" id="nuapi-console-send">▶ Send Request</button>
			<pre id="nuapi-console-response" class="nuapi-console-response">Response will appear here…</pre>
			<?php if ( empty( $enabled_tables ) ) : ?>
				<p class="description">No tables are enabled yet. Go to <strong>Tables &amp; Permissions</strong> and turn on read access for at least one table.</p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_audit() {
		$rows = NUAPI_Logger::get_recent( 100 );
		?>
		<table class="widefat striped nuapi-table">
			<thead><tr><th>Time</th><th>Action</th><th>Table</th><th>Row ID</th><th>Actor</th><th>IP</th></tr></thead>
			<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="6">No write operations logged yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row->created_at ); ?></td>
					<td><span class="nuapi-badge nuapi-badge--warn"><?php echo esc_html( strtoupper( $row->action ) ); ?></span></td>
					<td><code><?php echo esc_html( $row->target_table ); ?></code></td>
					<td><?php echo (int) $row->row_id; ?></td>
					<td><?php echo esc_html( $row->actor ); ?></td>
					<td><?php echo esc_html( $row->ip ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
