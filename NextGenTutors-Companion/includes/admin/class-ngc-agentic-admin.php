<?php
/**
 * Agentic Control Centre — MCP, A2A, Social OAuth, Content Studio, Leads, Schedule.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screens for governed agentic marketing / recruitment.
 */
final class NGC_Agentic_Admin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 28 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_wysiwyg' ], 40 );
		add_action( 'admin_post_ngc_mcp_upsert', [ __CLASS__, 'handle_mcp_upsert' ] );
		add_action( 'admin_post_ngc_social_begin', [ __CLASS__, 'handle_social_begin' ] );
		add_action( 'admin_post_ngc_social_oauth_callback', [ __CLASS__, 'handle_oauth_callback' ] );
		add_action( 'admin_post_ngc_content_draft', [ __CLASS__, 'handle_content_draft' ] );
		add_action( 'admin_post_ngc_content_update', [ __CLASS__, 'handle_content_update' ] );
		add_action( 'admin_post_ngc_content_delete', [ __CLASS__, 'handle_content_delete' ] );
		add_action( 'admin_post_ngc_content_approve', [ __CLASS__, 'handle_content_approve' ] );
		add_action( 'wp_ajax_ngc_content_ai_enhance', [ __CLASS__, 'ajax_content_ai_enhance' ] );
		add_action( 'admin_post_ngc_schedule_preview', [ __CLASS__, 'handle_schedule_preview' ] );
		add_action( 'admin_post_ngc_lead_create', [ __CLASS__, 'handle_lead_create' ] );
		add_action( 'admin_post_ngc_lead_sync', [ __CLASS__, 'handle_lead_sync' ] );
		add_action( 'admin_post_ngc_a2a_pin', [ __CLASS__, 'handle_a2a_pin' ] );
	}

	/**
	 * Content Studio assets (WYSIWYG + AI toggle).
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ngc-content-studio' !== $page && false === strpos( (string) $hook, 'ngc-content-studio' ) ) {
			return;
		}
		wp_enqueue_editor();
		wp_enqueue_style( 'ngc-content-studio', NGC_PLUGIN_URL . 'assets/css/ngc-content-studio.css', [], NGC_VERSION );
		wp_enqueue_script( 'ngc-content-studio', NGC_PLUGIN_URL . 'assets/js/ngc-content-studio.js', [ 'jquery', 'editor' ], NGC_VERSION, true );
		wp_localize_script(
			'ngc-content-studio',
			'NGC_CONTENT_STUDIO',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ngc_content_ai_enhance' ),
				'i18n'    => [
					'on'            => __( 'On — AI enhancing copy', 'nextgencompanion' ),
					'off'           => __( 'Off', 'nextgencompanion' ),
					'working'       => __( 'AI Agent enhancing…', 'nextgencompanion' ),
					'ok'            => __( 'Enhanced (lawful, non-discriminatory).', 'nextgencompanion' ),
					'fail'          => __( 'AI enhancement failed.', 'nextgencompanion' ),
					'empty'         => __( 'Enter audience, post text, or alt text first.', 'nextgencompanion' ),
					'restored'      => __( 'Restored previous text.', 'nextgencompanion' ),
					'confirmDelete' => __( 'Delete this post permanently?', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * Public site: TinyMCE for multiline form textareas.
	 */
	public static function enqueue_public_wysiwyg() {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_editor();
		wp_enqueue_script( 'ngc-wysiwyg', NGC_PLUGIN_URL . 'assets/js/ngc-wysiwyg.js', [ 'jquery', 'editor' ], NGC_VERSION, true );
		wp_localize_script( 'ngc-wysiwyg', 'NGC_WYSIWYG', [ 'debug' => false ] );
	}

	/**
	 * Submenus under unified NGT parent.
	 */
	public static function register_menus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$parent = function_exists( 'ngt_admin_parent' ) ? ngt_admin_parent() : 'ngt-admin';
		$pages  = [
			[ 'ngc-agentic-hub', __( 'Agentic Hub', 'nextgencompanion' ), [ __CLASS__, 'render_hub' ] ],
			[ 'ngc-mcp-servers', __( 'MCP Servers', 'nextgencompanion' ), [ __CLASS__, 'render_mcp' ] ],
			[ 'ngc-a2a-agents', __( 'A2A Agents', 'nextgencompanion' ), [ __CLASS__, 'render_a2a' ] ],
			[ 'ngc-social-connections', __( 'Social Connections', 'nextgencompanion' ), [ __CLASS__, 'render_social' ] ],
			[ 'ngc-content-studio', __( 'Content Studio', 'nextgencompanion' ), [ __CLASS__, 'render_content' ] ],
			[ 'ngc-content-calendar', __( 'Content Calendar', 'nextgencompanion' ), [ __CLASS__, 'render_calendar' ] ],
			[ 'ngc-tutor-leads', __( 'Tutor Leads', 'nextgencompanion' ), [ __CLASS__, 'render_leads' ] ],
			[ 'ngc-lead-sources', __( 'Lead Sources', 'nextgencompanion' ), [ __CLASS__, 'render_sources' ] ],
		];
		foreach ( $pages as $p ) {
			add_submenu_page( $parent, $p[1], $p[1], 'manage_options', $p[0], $p[2] );
		}
	}

	/**
	 * Hub dashboard — live counts only.
	 */
	public static function render_hub() {
		self::guard();
		$accounts = class_exists( 'NGC_Social_Connections' ) ? count( NGC_Social_Connections::all() ) : 0;
		$posts    = class_exists( 'NGC_Content_Studio' ) ? count( NGC_Content_Studio::all() ) : 0;
		$leads    = class_exists( 'NGC_Tutor_Leads' ) ? count( NGC_Tutor_Leads::all() ) : 0;
		$mcp      = class_exists( 'NGC_Mcp_Registry' ) ? count( NGC_Mcp_Registry::all() ) : 0;
		$a2a      = class_exists( 'NGC_A2a_Gateway' ) ? count( NGC_A2a_Gateway::agents() ) : 0;
		$pending  = 0;
		if ( class_exists( 'NGC_Content_Studio' ) ) {
			foreach ( NGC_Content_Studio::all() as $row ) {
				if ( in_array( (string) ( $row['status'] ?? '' ), [ 'DRAFT', 'HUMAN_REVIEW', 'POLICY_REVIEW' ], true ) ) {
					++$pending;
				}
			}
		}
		ob_start();
		?>
		<div class="ngt-admin-card" data-ngt-motion>
			<p><?php esc_html_e( 'Governed agentic marketing and tutor recruitment. Social connections use official OAuth only — never platform passwords. Lead discovery uses job-relevant criteria only (no ethnicity, gender, or age targeting). Scraping and browser-login automation are blocked.', 'nextgencompanion' ); ?></p>
		</div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:16px 0">
			<?php
			self::metric( $mcp, __( 'MCP servers', 'nextgencompanion' ) );
			self::metric( $a2a, __( 'Pinned A2A agents', 'nextgencompanion' ) );
			self::metric( $accounts, __( 'Social connections', 'nextgencompanion' ) );
			self::metric( $posts, __( 'Content drafts', 'nextgencompanion' ) );
			self::metric( $pending, __( 'Awaiting approval', 'nextgencompanion' ) );
			self::metric( $leads, __( 'Tutor leads', 'nextgencompanion' ) );
			?>
		</div>
		<ul>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-mcp-servers' ) ); ?>"><?php esc_html_e( 'MCP Server Registry', 'nextgencompanion' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-social-connections' ) ); ?>"><?php esc_html_e( 'Social Connections (OAuth)', 'nextgencompanion' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-content-studio' ) ); ?>"><?php esc_html_e( 'Content Studio', 'nextgencompanion' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-tutor-leads' ) ); ?>"><?php esc_html_e( 'Tutor Leads', 'nextgencompanion' ); ?></a></li>
		</ul>
		<?php
		self::page( __( 'Agentic Hub', 'nextgencompanion' ), __( 'Control centre for MCP, A2A, social publishing, and ethical tutor lead generation.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * MCP registry UI.
	 */
	public static function render_mcp() {
		self::guard();
		$rows = class_exists( 'NGC_Mcp_Registry' ) ? NGC_Mcp_Registry::list_public() : [];
		ob_start();
		self::flash();
		?>
		<div class="ngt-admin-card">
			<p><strong><?php esc_html_e( 'Policy:', 'nextgencompanion' ); ?></strong> <?php esc_html_e( 'No unverified public MCP servers are enabled by default. HTTPS required. Private/metadata endpoints blocked unless explicitly approved for local debug.', 'nextgencompanion' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt-admin-card" style="padding:16px;margin:16px 0">
			<?php wp_nonce_field( 'ngc_mcp_upsert' ); ?>
			<input type="hidden" name="action" value="ngc_mcp_upsert" />
			<h2><?php esc_html_e( 'Add / update MCP server', 'nextgencompanion' ); ?></h2>
			<p><label><?php esc_html_e( 'Display name', 'nextgencompanion' ); ?> <input type="text" name="display_name" class="regular-text" required /></label></p>
			<p><label><?php esc_html_e( 'Endpoint URI (HTTPS)', 'nextgencompanion' ); ?> <input type="url" name="endpoint" class="regular-text" required placeholder="https://" /></label></p>
			<p><label><?php esc_html_e( 'Environment', 'nextgencompanion' ); ?>
				<select name="environment"><option value="staging">staging</option><option value="production">production</option><option value="local">local</option></select>
			</label></p>
			<p><label><input type="checkbox" name="capabilities_approved" value="1" /> <?php esc_html_e( 'I have reviewed and approve the capability allowlist for this server', 'nextgencompanion' ); ?></label></p>
			<p><label><input type="checkbox" name="enabled" value="1" /> <?php esc_html_e( 'Enable (requires capability approval)', 'nextgencompanion' ); ?></label></p>
			<?php submit_button( __( 'Save server (disabled until approved)', 'nextgencompanion' ) ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Name', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Endpoint', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Enabled', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Caps approved', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No MCP servers registered.', 'nextgencompanion' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( (string) ( $row['id'] ?? '' ) ); ?></code></td>
						<td><?php echo esc_html( (string) ( $row['display_name'] ?? '' ) ); ?></td>
						<td><code><?php echo esc_html( (string) ( $row['endpoint'] ?? '' ) ); ?></code></td>
						<td><?php echo ! empty( $row['enabled'] ) ? esc_html__( 'Yes', 'nextgencompanion' ) : esc_html__( 'No', 'nextgencompanion' ); ?></td>
						<td><?php echo ! empty( $row['capabilities_approved'] ) ? esc_html__( 'Yes', 'nextgencompanion' ) : esc_html__( 'No', 'nextgencompanion' ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		self::page( __( 'MCP Servers', 'nextgencompanion' ), __( 'Dynamic MCP registry with SSRF guards and capability approval.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * A2A agents.
	 */
	public static function render_a2a() {
		self::guard();
		$agents = class_exists( 'NGC_A2a_Gateway' ) ? NGC_A2a_Gateway::agents() : [];
		$tasks  = class_exists( 'NGC_A2a_Gateway' ) ? NGC_A2a_Gateway::tasks() : [];
		ob_start();
		self::flash();
		?>
		<div class="ngt-admin-card">
			<p><?php esc_html_e( 'A2A uses pinned Agent Cards and durable tasks. External agents are untrusted. Full protocol execution runs via a separate Agent Gateway with an official SDK (a2a-js recommended) — WordPress holds registry, auth boundary, and task state.', 'nextgencompanion' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt-admin-card" style="padding:16px;margin:16px 0">
			<?php wp_nonce_field( 'ngc_a2a_pin' ); ?>
			<input type="hidden" name="action" value="ngc_a2a_pin" />
			<h2><?php esc_html_e( 'Pin Agent Card', 'nextgencompanion' ); ?></h2>
			<p><label><?php esc_html_e( 'Agent ID', 'nextgencompanion' ); ?> <input name="agent_id" class="regular-text" required /></label></p>
			<p><label><?php esc_html_e( 'Name', 'nextgencompanion' ); ?> <input name="name" class="regular-text" required /></label></p>
			<p><label><?php esc_html_e( 'Card URL (HTTPS)', 'nextgencompanion' ); ?> <input type="url" name="card_url" class="regular-text" placeholder="https://" /></label></p>
			<p><label><?php esc_html_e( 'Allowed skills (comma-separated)', 'nextgencompanion' ); ?> <input name="skills" class="large-text" /></label></p>
			<?php submit_button( __( 'Pin agent', 'nextgencompanion' ) ); ?>
		</form>
		<h2><?php esc_html_e( 'Pinned agents', 'nextgencompanion' ); ?></h2>
		<pre style="max-height:240px;overflow:auto;background:#f6f7f7;padding:12px"><?php echo esc_html( wp_json_encode( $agents, JSON_PRETTY_PRINT ) ); ?></pre>
		<h2><?php esc_html_e( 'Recent tasks', 'nextgencompanion' ); ?></h2>
		<pre style="max-height:240px;overflow:auto;background:#f6f7f7;padding:12px"><?php echo esc_html( wp_json_encode( array_slice( $tasks, -20 ), JSON_PRETTY_PRINT ) ); ?></pre>
		<?php
		self::page( __( 'A2A Agents', 'nextgencompanion' ), __( 'Agent2Agent registry and durable task store.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Social OAuth connections — no password fields.
	 */
	public static function render_social() {
		self::guard();
		$accounts = class_exists( 'NGC_Social_Connections' ) ? NGC_Social_Connections::list_public() : [];
		ob_start();
		self::flash();
		?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Username/password login for Facebook, Instagram, X, or LinkedIn is prohibited. Connect only via official OAuth. Tokens are encrypted server-side and never returned to the browser.', 'nextgencompanion' ); ?></p></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt-admin-card" style="padding:16px;margin:16px 0">
			<?php wp_nonce_field( 'ngc_social_begin' ); ?>
			<input type="hidden" name="action" value="ngc_social_begin" />
			<p><label><?php esc_html_e( 'Platform', 'nextgencompanion' ); ?>
				<select name="platform">
					<option value="facebook_pages">Facebook Pages</option>
					<option value="instagram_professional">Instagram professional</option>
					<option value="x">X</option>
					<option value="linkedin">LinkedIn</option>
				</select>
			</label></p>
			<?php submit_button( __( 'Start OAuth connect', 'nextgencompanion' ) ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Label', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Platform', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Scopes', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Approval', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $accounts ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No connected accounts. App credentials (wp-config constants) are required before OAuth can complete.', 'nextgencompanion' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $accounts as $a ) : ?>
					<tr>
						<td><?php echo esc_html( (string) ( $a['display_name'] ?: $a['label'] ) ); ?></td>
						<td><?php echo esc_html( (string) $a['platform'] ); ?></td>
						<td><?php echo esc_html( (string) $a['status'] ); ?></td>
						<td><small><?php echo esc_html( implode( ', ', (array) $a['scopes'] ) ); ?></small></td>
						<td><?php echo esc_html( (string) $a['approval_policy'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		self::page( __( 'Social Connections', 'nextgencompanion' ), __( 'Official OAuth connectors for Meta, X, and LinkedIn.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Content studio.
	 */
	public static function render_content() {
		self::guard();
		$posts = class_exists( 'NGC_Content_Studio' ) ? NGC_Content_Studio::all() : [];
		ob_start();
		self::flash();
		$editor_settings = [
			'textarea_rows' => 8,
			'media_buttons' => false,
			'teeny'         => false,
			'quicktags'     => true,
		];
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt-admin-card ngc-cs-draft-form" style="padding:16px;margin:16px 0">
			<?php wp_nonce_field( 'ngc_content_draft' ); ?>
			<input type="hidden" name="action" value="ngc_content_draft" />
			<input type="hidden" name="ai_enhanced" value="0" />
			<input type="hidden" name="ai_original_json" value="" />
			<h2><?php esc_html_e( 'New draft', 'nextgencompanion' ); ?></h2>

			<div class="ngc-cs-ai-bar" role="group" aria-label="<?php esc_attr_e( 'AI Agent', 'nextgencompanion' ); ?>">
				<button type="button" class="ngc-cs-ai-toggle" aria-pressed="false" title="<?php esc_attr_e( 'Toggle AI Agent enhancement', 'nextgencompanion' ); ?>">
					<svg class="ngc-cs-ai-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path fill="currentColor" d="M12 2a2 2 0 0 1 2 2v1.1a7 7 0 0 1 4.9 4.9H21a2 2 0 1 1 0 4h-2.1a7 7 0 0 1-4.9 4.9V21a2 2 0 1 1-4 0v-2.1A7 7 0 0 1 5.1 14H3a2 2 0 1 1 0-4h2.1A7 7 0 0 1 10 5.1V4a2 2 0 0 1 2-2Zm0 6a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
					</svg>
				</button>
				<div class="ngc-cs-ai-label">
					<strong><?php esc_html_e( 'AI Agent', 'nextgencompanion' ); ?></strong>
					<span class="ngc-cs-ai-state"><?php esc_html_e( 'Off', 'nextgencompanion' ); ?></span>
					<span><?php esc_html_e( 'When on, required enhancement of Audience, post text, and alt text (lawful, non-discriminatory). Toggle off to restore previous text.', 'nextgencompanion' ); ?></span>
				</div>
			</div>
			<p class="ngc-cs-status" role="status" aria-live="polite"></p>

			<p class="ngc-cs-field"><label for="ngc_cs_campaign"><?php esc_html_e( 'Campaign', 'nextgencompanion' ); ?></label>
				<input id="ngc_cs_campaign" name="campaign" class="regular-text" /></p>
			<p class="ngc-cs-field"><label for="ngc_content_audience"><?php esc_html_e( 'Audience (lawful, non-discriminatory)', 'nextgencompanion' ); ?></label>
				<textarea id="ngc_content_audience" name="audience" rows="3" class="large-text ngc-wysiwyg" placeholder="<?php esc_attr_e( 'e.g. Maths tutors in Gauteng seeking online hours', 'nextgencompanion' ); ?>"></textarea></p>
			<div class="ngc-cs-field">
				<label for="ngc_content_text"><?php esc_html_e( 'Post text', 'nextgencompanion' ); ?></label>
				<?php
				wp_editor(
					'',
					'ngc_content_text',
					array_merge(
						$editor_settings,
						[ 'textarea_name' => 'text' ]
					)
				);
				?>
			</div>
			<p class="ngc-cs-field"><label for="ngc_content_alt_text"><?php esc_html_e( 'Alt text', 'nextgencompanion' ); ?></label>
				<textarea id="ngc_content_alt_text" name="alt_text" rows="3" class="large-text ngc-wysiwyg"></textarea></p>
			<?php submit_button( __( 'Save draft', 'nextgencompanion' ) ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Text', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $posts ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No drafts yet.', 'nextgencompanion' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( array_reverse( $posts ) as $p ) : ?>
					<?php
					$pid      = (string) ( $p['id'] ?? '' );
					$status   = (string) ( $p['status'] ?? '' );
					$editable = ! in_array( $status, [ 'PUBLISHED', 'PUBLISHING' ], true );
					?>
					<tr>
						<td><code><?php echo esc_html( $pid ); ?></code></td>
						<td><?php echo esc_html( $status ); ?></td>
						<td><?php echo esc_html( wp_html_excerpt( wp_strip_all_tags( (string) ( $p['text'] ?? '' ) ), 80, '…' ) ); ?></td>
						<td>
							<div class="ngc-cs-actions">
								<?php if ( $editable ) : ?>
									<button type="button" class="button" data-ngc-cs-edit="<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Edit', 'nextgencompanion' ); ?></button>
									<button type="submit" class="button button-primary" form="ngc-cs-update-form-<?php echo esc_attr( $pid ); ?>" data-ngc-cs-edit="<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Update', 'nextgencompanion' ); ?></button>
								<?php endif; ?>
								<?php if ( in_array( $status, [ 'DRAFT', 'HUMAN_REVIEW', 'POLICY_REVIEW' ], true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'ngc_content_approve' ); ?>
									<input type="hidden" name="action" value="ngc_content_approve" />
									<input type="hidden" name="post_id" value="<?php echo esc_attr( $pid ); ?>" />
									<?php submit_button( __( 'Approve', 'nextgencompanion' ), 'secondary', 'submit', false ); ?>
								</form>
								<?php endif; ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngc-cs-delete-form" style="display:inline">
									<?php wp_nonce_field( 'ngc_content_delete' ); ?>
									<input type="hidden" name="action" value="ngc_content_delete" />
									<input type="hidden" name="post_id" value="<?php echo esc_attr( $pid ); ?>" />
									<?php submit_button( __( 'Delete', 'nextgencompanion' ), 'delete', 'submit', false ); ?>
								</form>
							</div>
						</td>
					</tr>
					<?php if ( $editable ) : ?>
					<tr id="ngc-cs-edit-<?php echo esc_attr( $pid ); ?>" class="ngc-cs-edit-row is-hidden">
						<td colspan="4">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngc-cs-update-form" id="ngc-cs-update-form-<?php echo esc_attr( $pid ); ?>">
								<?php wp_nonce_field( 'ngc_content_update' ); ?>
								<input type="hidden" name="action" value="ngc_content_update" />
								<input type="hidden" name="post_id" value="<?php echo esc_attr( $pid ); ?>" />
								<input type="hidden" name="ai_enhanced" value="<?php echo ! empty( $p['ai_enhanced'] ) ? '1' : '0'; ?>" />
								<input type="hidden" name="ai_original_json" value="<?php echo esc_attr( wp_json_encode( $p['ai_original'] ?? null ) ); ?>" />
								<p class="ngc-cs-field"><label><?php esc_html_e( 'Campaign', 'nextgencompanion' ); ?>
									<input name="campaign" class="regular-text" value="<?php echo esc_attr( (string) ( $p['campaign'] ?? '' ) ); ?>" /></label></p>
								<p class="ngc-cs-field"><label for="ngc_content_audience_<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Audience (lawful, non-discriminatory)', 'nextgencompanion' ); ?></label>
									<textarea id="ngc_content_audience_<?php echo esc_attr( $pid ); ?>" name="audience" rows="3" class="large-text"><?php echo esc_textarea( (string) ( $p['audience'] ?? '' ) ); ?></textarea></p>
								<div class="ngc-cs-field">
									<label for="ngc_content_text_<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Post text', 'nextgencompanion' ); ?></label>
									<?php
									wp_editor(
										(string) ( $p['text'] ?? '' ),
										'ngc_content_text_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $pid ),
										array_merge(
											$editor_settings,
											[
												'textarea_name' => 'text',
												'textarea_rows' => 6,
											]
										)
									);
									?>
								</div>
								<p class="ngc-cs-field"><label for="ngc_content_alt_<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Alt text', 'nextgencompanion' ); ?></label>
									<textarea id="ngc_content_alt_<?php echo esc_attr( $pid ); ?>" name="alt_text" rows="3" class="large-text"><?php echo esc_textarea( (string) ( $p['alt_text'] ?? '' ) ); ?></textarea></p>
								<?php submit_button( __( 'Update', 'nextgencompanion' ), 'primary', 'submit', false ); ?>
							</form>
						</td>
					</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		self::page( __( 'Content Studio', 'nextgencompanion' ), __( 'Draft → policy → human approval → publish. AI may draft; humans approve.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Calendar / RRULE preview.
	 */
	public static function render_calendar() {
		self::guard();
		$preview = get_transient( 'ngc_schedule_preview_' . get_current_user_id() );
		ob_start();
		self::flash();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt-admin-card" style="padding:16px;margin:16px 0">
			<?php wp_nonce_field( 'ngc_schedule_preview' ); ?>
			<input type="hidden" name="action" value="ngc_schedule_preview" />
			<p><label><?php esc_html_e( 'Timezone', 'nextgencompanion' ); ?> <input name="timezone" value="Africa/Johannesburg" class="regular-text" /></label></p>
			<p><label><?php esc_html_e( 'DTSTART', 'nextgencompanion' ); ?> <input name="dtstart" value="2026-12-25 09:00:00" class="regular-text" /></label></p>
			<p><label><?php esc_html_e( 'RRULE', 'nextgencompanion' ); ?> <input name="rrule" value="FREQ=DAILY;COUNT=5" class="large-text" /></label></p>
			<p><label><?php esc_html_e( 'Times (comma-separated HH:MM)', 'nextgencompanion' ); ?> <input name="times" value="09:00,16:00" class="regular-text" /></label></p>
			<p class="description"><?php esc_html_e( 'Example: 25 Dec 2026 at 09:00 and 16:00 means two occurrences that day when times=09:00,16:00 and DTSTART is that date — preview lists each explicitly.', 'nextgencompanion' ); ?></p>
			<?php submit_button( __( 'Preview next 20 occurrences', 'nextgencompanion' ) ); ?>
		</form>
		<?php if ( is_array( $preview ) ) : ?>
			<pre style="background:#f6f7f7;padding:12px;max-height:400px;overflow:auto"><?php echo esc_html( wp_json_encode( $preview, JSON_PRETTY_PRINT ) ); ?></pre>
		<?php endif; ?>
		<?php
		self::page( __( 'Content Calendar', 'nextgencompanion' ), __( 'RFC 5545 RRULE preview with timezone and multi-time support.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Tutor leads.
	 */
	public static function render_leads() {
		self::guard();
		$leads = class_exists( 'NGC_Tutor_Leads' ) ? NGC_Tutor_Leads::all() : [];
		ob_start();
		self::flash();
		?>
		<div class="notice notice-info"><p><?php esc_html_e( 'Allowed criteria: subject, qualification, experience, location, delivery mode, grade level, language (when required), speciality. Forbidden: ethnicity, gender, age, and other protected traits — including AI inference.', 'nextgencompanion' ); ?></p></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt-admin-card" style="padding:16px;margin:16px 0">
			<?php wp_nonce_field( 'ngc_lead_create' ); ?>
			<input type="hidden" name="action" value="ngc_lead_create" />
			<p><label><?php esc_html_e( 'Source', 'nextgencompanion' ); ?>
				<select name="source">
					<option value="manual_entry">manual_entry</option>
					<option value="first_party_referral">first_party_referral</option>
					<option value="consented_import">consented_import</option>
					<option value="linkedin_official_api">linkedin_official_api</option>
					<option value="job_board_api">job_board_api</option>
				</select>
			</label></p>
			<p><label><?php esc_html_e( 'Subject', 'nextgencompanion' ); ?> <input name="subject" class="regular-text" required /></label></p>
			<p><label><?php esc_html_e( 'Display name', 'nextgencompanion' ); ?> <input name="display_name" class="regular-text" /></label></p>
			<p><label><?php esc_html_e( 'Public email', 'nextgencompanion' ); ?> <input type="email" name="public_email" class="regular-text" /></label></p>
			<p><label><?php esc_html_e( 'Service area', 'nextgencompanion' ); ?> <input name="service_area" class="regular-text" /></label></p>
			<p><label><?php esc_html_e( 'Lawful basis', 'nextgencompanion' ); ?> <input name="lawful_basis" value="consent" class="regular-text" /></label></p>
			<?php submit_button( __( 'Create lead', 'nextgencompanion' ) ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Subject', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Source', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'CRM', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $leads ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No leads yet.', 'nextgencompanion' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( array_reverse( $leads ) as $l ) : ?>
					<tr>
						<td><code><?php echo esc_html( (string) $l['id'] ); ?></code></td>
						<td><?php echo esc_html( (string) ( $l['subject'] ?? '' ) ); ?> — <?php echo esc_html( (string) ( $l['display_name'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $l['source'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $l['crm_sync_status'] ?? '' ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
								<?php wp_nonce_field( 'ngc_lead_sync' ); ?>
								<input type="hidden" name="action" value="ngc_lead_sync" />
								<input type="hidden" name="lead_id" value="<?php echo esc_attr( (string) $l['id'] ); ?>" />
								<?php submit_button( __( 'Sync FluentCRM', 'nextgencompanion' ), 'secondary', 'submit', false ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		self::page( __( 'Tutor Leads', 'nextgencompanion' ), __( 'Ethical tutor recruitment leads with FluentCRM tutor-leads sync.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Source compliance matrix.
	 */
	public static function render_sources() {
		self::guard();
		$policy = class_exists( 'NGC_Tutor_Leads' ) ? NGC_Tutor_Leads::source_policy() : [];
		ob_start();
		?>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Source', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Allowed', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Method', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Notes', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $policy as $slug => $row ) : ?>
				<tr>
					<td><code><?php echo esc_html( $slug ); ?></code></td>
					<td><?php echo ! empty( $row['allowed'] ) ? esc_html__( 'Yes', 'nextgencompanion' ) : esc_html__( 'No', 'nextgencompanion' ); ?></td>
					<td><?php echo esc_html( (string) ( $row['method'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( (string) ( $row['notes'] ?? '' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><?php esc_html_e( 'Bing Search APIs were retired 11 August 2025 — not used. LinkedIn/Google/Maps scraping and browser-login harvest are blocked.', 'nextgencompanion' ); ?></p>
		<?php
		self::page( __( 'Lead Sources', 'nextgencompanion' ), __( 'Compliance matrix for tutor lead discovery sources.', 'nextgencompanion' ), ob_get_clean() );
	}

	/** Handlers */

	public static function handle_mcp_upsert() {
		self::post_guard( 'ngc_mcp_upsert' );
		$input = [
			'display_name'           => sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) ),
			'endpoint'               => esc_url_raw( wp_unslash( $_POST['endpoint'] ?? '' ) ),
			'environment'            => sanitize_key( wp_unslash( $_POST['environment'] ?? 'staging' ) ),
			'capabilities_approved'  => ! empty( $_POST['capabilities_approved'] ),
			'enabled'                => ! empty( $_POST['enabled'] ),
			'allowed_tools'          => [],
		];
		$res = NGC_Mcp_Registry::upsert( $input );
		self::redirect( 'ngc-mcp-servers', is_wp_error( $res ) ? $res->get_error_message() : __( 'MCP server saved.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_social_begin() {
		self::post_guard( 'ngc_social_begin' );
		$platform = sanitize_key( wp_unslash( $_POST['platform'] ?? '' ) );
		$res      = NGC_Social_Connections::begin_connect( $platform );
		if ( is_wp_error( $res ) ) {
			self::redirect( 'ngc-social-connections', $res->get_error_message(), false );
		}
		if ( ! empty( $res['auth_url'] ) ) {
			wp_safe_redirect( $res['auth_url'] );
			exit;
		}
		$msg = (string) ( $res['message'] ?? __( 'OAuth cannot start until app credentials are configured.', 'nextgencompanion' ) );
		self::redirect( 'ngc-social-connections', $msg, false );
	}

	public static function handle_oauth_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$row   = NGC_Social_Oauth::consume( $state );
		if ( is_wp_error( $row ) ) {
			self::redirect( 'ngc-social-connections', $row->get_error_message(), false );
		}
		if ( '' === $code ) {
			self::redirect( 'ngc-social-connections', __( 'OAuth code missing — connection cancelled or denied.', 'nextgencompanion' ), false );
		}
		// Token exchange requires live app secrets; record pending authorization evidence without inventing tokens.
		self::redirect(
			'ngc-social-connections',
			sprintf(
				/* translators: %s: platform */
				__( 'OAuth callback received for %s. Complete token exchange via Agent Gateway with configured client credentials (not stored in this request).', 'nextgencompanion' ),
				sanitize_key( (string) ( $row['platform'] ?? '' ) )
			),
			true
		);
	}

	public static function handle_content_draft() {
		self::post_guard( 'ngc_content_draft' );
		$ai_on  = ! empty( $_POST['ai_enhanced'] );
		$orig   = self::parse_ai_original();
		$input  = [
			'campaign'    => sanitize_text_field( wp_unslash( $_POST['campaign'] ?? '' ) ),
			'audience'    => sanitize_textarea_field( wp_unslash( $_POST['audience'] ?? '' ) ),
			'text'        => wp_kses_post( wp_unslash( $_POST['text'] ?? '' ) ),
			'alt_text'    => sanitize_textarea_field( wp_unslash( $_POST['alt_text'] ?? '' ) ),
			'ai_enhanced' => $ai_on ? 1 : 0,
			'ai_original' => $ai_on ? $orig : null,
		];
		if ( $ai_on ) {
			$enhanced = NGC_Content_Studio::enhance_fields(
				[
					'audience' => $orig['audience'] ?? $input['audience'],
					'text'     => $orig['text'] ?? $input['text'],
					'alt_text' => $orig['alt_text'] ?? $input['alt_text'],
				]
			);
			if ( is_wp_error( $enhanced ) ) {
				self::redirect( 'ngc-content-studio', $enhanced->get_error_message(), false );
			}
			$input['audience']    = $enhanced['audience'];
			$input['text']        = $enhanced['text'];
			$input['alt_text']    = $enhanced['alt_text'];
			$input['ai_original'] = [
				'audience' => (string) ( $orig['audience'] ?? '' ),
				'text'     => (string) ( $orig['text'] ?? '' ),
				'alt_text' => (string) ( $orig['alt_text'] ?? '' ),
			];
		}
		$res = NGC_Content_Studio::create_draft( $input );
		self::redirect( 'ngc-content-studio', is_wp_error( $res ) ? $res->get_error_message() : __( 'Draft saved.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_content_update() {
		self::post_guard( 'ngc_content_update' );
		$id = sanitize_key( wp_unslash( $_POST['post_id'] ?? '' ) );
		$res = NGC_Content_Studio::update(
			$id,
			[
				'campaign' => sanitize_text_field( wp_unslash( $_POST['campaign'] ?? '' ) ),
				'audience' => sanitize_textarea_field( wp_unslash( $_POST['audience'] ?? '' ) ),
				'text'     => wp_kses_post( wp_unslash( $_POST['text'] ?? '' ) ),
				'alt_text' => sanitize_textarea_field( wp_unslash( $_POST['alt_text'] ?? '' ) ),
			]
		);
		self::redirect( 'ngc-content-studio', is_wp_error( $res ) ? $res->get_error_message() : __( 'Post updated.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_content_delete() {
		self::post_guard( 'ngc_content_delete' );
		$id  = sanitize_key( wp_unslash( $_POST['post_id'] ?? '' ) );
		$res = NGC_Content_Studio::delete( $id );
		self::redirect( 'ngc-content-studio', is_wp_error( $res ) ? $res->get_error_message() : __( 'Post deleted.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	/**
	 * AJAX: enhance audience / text / alt via AI Agent.
	 */
	public static function ajax_content_ai_enhance() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'nextgencompanion' ) ], 403 );
		}
		check_ajax_referer( 'ngc_content_ai_enhance', 'nonce' );
		$res = NGC_Content_Studio::enhance_fields(
			[
				'audience' => sanitize_textarea_field( wp_unslash( $_POST['audience'] ?? '' ) ),
				'text'     => wp_kses_post( wp_unslash( $_POST['text'] ?? '' ) ),
				'alt_text' => sanitize_textarea_field( wp_unslash( $_POST['alt_text'] ?? '' ) ),
			]
		);
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( [ 'message' => $res->get_error_message() ] );
		}
		wp_send_json_success( $res );
	}

	/**
	 * @return array{audience?:string,text?:string,alt_text?:string}
	 */
	private static function parse_ai_original() {
		$raw = wp_unslash( $_POST['ai_original_json'] ?? '' );
		if ( '' === $raw ) {
			return [];
		}
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) ) {
			return [];
		}
		return [
			'audience' => sanitize_textarea_field( (string) ( $data['audience'] ?? '' ) ),
			'text'     => wp_kses_post( (string) ( $data['text'] ?? '' ) ),
			'alt_text' => sanitize_textarea_field( (string) ( $data['alt_text'] ?? '' ) ),
		];
	}

	public static function handle_content_approve() {
		self::post_guard( 'ngc_content_approve' );
		$id  = sanitize_key( wp_unslash( $_POST['post_id'] ?? '' ) );
		$res = NGC_Content_Studio::approve( $id );
		self::redirect( 'ngc-content-studio', is_wp_error( $res ) ? $res->get_error_message() : __( 'Post approved.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_schedule_preview() {
		self::post_guard( 'ngc_schedule_preview' );
		$times = array_filter( array_map( 'trim', explode( ',', (string) wp_unslash( $_POST['times'] ?? '' ) ) ) );
		$res   = NGC_Schedule_Rrule::preview(
			[
				'timezone' => sanitize_text_field( wp_unslash( $_POST['timezone'] ?? 'Africa/Johannesburg' ) ),
				'dtstart'  => sanitize_text_field( wp_unslash( $_POST['dtstart'] ?? '' ) ),
				'rrule'    => sanitize_text_field( wp_unslash( $_POST['rrule'] ?? '' ) ),
				'times'    => $times,
			]
		);
		if ( ! is_wp_error( $res ) ) {
			set_transient( 'ngc_schedule_preview_' . get_current_user_id(), $res, 10 * MINUTE_IN_SECONDS );
		}
		self::redirect( 'ngc-content-calendar', is_wp_error( $res ) ? $res->get_error_message() : __( 'Preview updated.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_lead_create() {
		self::post_guard( 'ngc_lead_create' );
		$res = NGC_Tutor_Leads::create(
			[
				'source'       => sanitize_key( wp_unslash( $_POST['source'] ?? 'manual_entry' ) ),
				'subject'      => sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ),
				'display_name' => sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) ),
				'public_email' => sanitize_email( wp_unslash( $_POST['public_email'] ?? '' ) ),
				'service_area' => sanitize_text_field( wp_unslash( $_POST['service_area'] ?? '' ) ),
				'lawful_basis' => sanitize_key( wp_unslash( $_POST['lawful_basis'] ?? 'consent' ) ),
				'consent_status' => 'recorded',
				'discovery_query' => [
					'subject' => sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ),
				],
			]
		);
		self::redirect( 'ngc-tutor-leads', is_wp_error( $res ) ? $res->get_error_message() : __( 'Lead created.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_lead_sync() {
		self::post_guard( 'ngc_lead_sync' );
		$id  = sanitize_key( wp_unslash( $_POST['lead_id'] ?? '' ) );
		$res = NGC_Tutor_Leads::sync_fluentcrm( $id );
		self::redirect( 'ngc-tutor-leads', is_wp_error( $res ) ? $res->get_error_message() : __( 'FluentCRM sync completed.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	public static function handle_a2a_pin() {
		self::post_guard( 'ngc_a2a_pin' );
		$skills = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) wp_unslash( $_POST['skills'] ?? '' ) ) ) ) );
		$res    = NGC_A2a_Gateway::pin_agent(
			[
				'id'       => sanitize_key( wp_unslash( $_POST['agent_id'] ?? '' ) ),
				'name'     => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'url'      => esc_url_raw( wp_unslash( $_POST['card_url'] ?? '' ) ),
				'skills'   => $skills,
				'approved' => 1,
			]
		);
		self::redirect( 'ngc-a2a-agents', is_wp_error( $res ) ? $res->get_error_message() : __( 'Agent pinned.', 'nextgencompanion' ), ! is_wp_error( $res ) );
	}

	/** Helpers */

	private static function guard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
	}

	/**
	 * @param string $nonce Nonce action.
	 */
	private static function post_guard( $nonce ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( $nonce );
	}

	/**
	 * @param string $page    Page slug.
	 * @param string $message Message.
	 * @param bool   $ok      Success.
	 */
	private static function redirect( $page, $message, $ok ) {
		set_transient(
			'ngc_agentic_flash_' . get_current_user_id(),
			[ 'ok' => $ok, 'message' => $message ],
			60
		);
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
		exit;
	}

	private static function flash() {
		$flash = get_transient( 'ngc_agentic_flash_' . get_current_user_id() );
		delete_transient( 'ngc_agentic_flash_' . get_current_user_id() );
		if ( ! is_array( $flash ) ) {
			return;
		}
		$class = ! empty( $flash['ok'] ) ? 'notice-success' : 'notice-error';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( (string) $flash['message'] ) . '</p></div>';
	}

	/**
	 * @param int    $n     Number.
	 * @param string $label Label.
	 */
	private static function metric( $n, $label ) {
		echo '<div class="ngt-admin-card" style="padding:12px"><strong>' . esc_html( (string) (int) $n ) . '</strong><br>' . esc_html( $label ) . '</div>';
	}

	/**
	 * @param string $title   Title.
	 * @param string $summary Summary.
	 * @param string $content HTML.
	 */
	private static function page( $title, $summary, $content ) {
		if ( class_exists( 'NGC_Admin_Layout' ) ) {
			NGC_Admin_Layout::render_page(
				[
					'title'   => $title,
					'summary' => $summary,
					'content' => $content,
				]
			);
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $summary ) . '</p>' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
