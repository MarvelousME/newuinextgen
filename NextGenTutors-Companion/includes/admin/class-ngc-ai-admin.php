<?php
/**
 * Admin UI for BYOK models, supervised agents, and test chat.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Suite admin screen under NextGen → AI Suite.
 */
class NGC_AI_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 21 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Register AI Suite submenu.
	 */
	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'AI Suite', 'nextgencompanion' ),
			__( 'AI Suite', 'nextgencompanion' ),
			'manage_options',
			'ngc-ai-suite',
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * @param string $hook Current admin hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'ngc-ai-suite' ) ) {
			return;
		}
		wp_enqueue_style( 'ngc-ai', NGC_PLUGIN_URL . 'assets/css/ngc-ai.css', [], NGC_VERSION );
		wp_enqueue_script( 'ngc-dialog', NGC_PLUGIN_URL . 'assets/js/ngc-dialog.js', [], NGC_VERSION, true );
		wp_enqueue_script( 'ngc-ai', NGC_PLUGIN_URL . 'assets/js/ngc-ai.js', [ 'ngc-dialog' ], NGC_VERSION, true );
		wp_localize_script(
			'ngc-ai',
			'NGC_ADMIN',
			[
				'rest'  => esc_url_raw( rest_url( NGC_Rest::NAMESPACE ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Render AI Suite control panel.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$crypto_ok = NGC_Crypto::available();
		?>
		<div class="wrap ngc-ai-wrap">
			<h1><?php esc_html_e( 'NextGenTutors AI Suite', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Multi-model BYOK registry and supervised agents. Mutating skills require human approval — autonomous tool loops are not executed.', 'nextgencompanion' ); ?></p>
			<?php if ( ! $crypto_ok ) : ?>
				<div class="notice notice-error inline"><p><?php esc_html_e( 'No encryption backend (sodium/openssl) is available — API keys cannot be stored securely on this server.', 'nextgencompanion' ); ?></p></div>
			<?php endif; ?>

			<div class="ngc-ai-tabs">
				<button type="button" class="ngc-ai-tab is-active" data-ai-tab="models"><?php esc_html_e( 'Models (BYOK)', 'nextgencompanion' ); ?></button>
				<button type="button" class="ngc-ai-tab" data-ai-tab="agents"><?php esc_html_e( 'Agents', 'nextgencompanion' ); ?></button>
				<button type="button" class="ngc-ai-tab" data-ai-tab="chat"><?php esc_html_e( 'Chat', 'nextgencompanion' ); ?></button>
			</div>
			<div class="ngc-ai-msg" role="status" aria-live="polite"></div>

			<div class="ngc-ai-panel is-active" data-ai-panel="models">
				<h2><?php esc_html_e( 'Add or update a model endpoint', 'nextgencompanion' ); ?></h2>
				<p class="description"><?php esc_html_e( 'OpenAI-compatible endpoint. Example base URL: https://api.openai.com/v1', 'nextgencompanion' ); ?></p>
				<table class="form-table"><tbody>
					<tr><th><?php esc_html_e( 'Label', 'nextgencompanion' ); ?></th><td><input type="text" class="regular-text" data-ai-model="label" /></td></tr>
					<tr><th><?php esc_html_e( 'Base URL', 'nextgencompanion' ); ?></th><td><input type="url" class="regular-text" data-ai-model="base_url" placeholder="https://api.openai.com/v1" /></td></tr>
					<tr><th><?php esc_html_e( 'Model id', 'nextgencompanion' ); ?></th><td><input type="text" class="regular-text" data-ai-model="model" placeholder="gpt-4o-mini" /></td></tr>
					<tr><th><?php esc_html_e( 'API key', 'nextgencompanion' ); ?></th><td><input type="password" class="regular-text" data-ai-model="api_key" autocomplete="new-password" /><p class="description"><?php esc_html_e( 'Stored encrypted; never shown again.', 'nextgencompanion' ); ?></p></td></tr>
				</tbody></table>
				<p><button type="button" class="button button-primary" data-ai-action="save-model"><?php esc_html_e( 'Save model', 'nextgencompanion' ); ?></button></p>
				<h2><?php esc_html_e( 'Configured models', 'nextgencompanion' ); ?></h2>
				<div id="ngc-ai-models"><em><?php esc_html_e( 'Loading…', 'nextgencompanion' ); ?></em></div>
			</div>

			<div class="ngc-ai-panel" data-ai-panel="agents" hidden>
				<h2><?php esc_html_e( 'Create or update an agent', 'nextgencompanion' ); ?></h2>
				<table class="form-table"><tbody>
					<tr><th><?php esc_html_e( 'Name', 'nextgencompanion' ); ?></th><td><input type="text" class="regular-text" data-ai-agent="name" /></td></tr>
					<tr><th><?php esc_html_e( 'Model', 'nextgencompanion' ); ?></th><td><select data-ai-agent="model_id"></select></td></tr>
					<tr><th><?php esc_html_e( 'Role', 'nextgencompanion' ); ?></th><td><select data-ai-agent="role"><option value="worker">worker</option><option value="orchestrator">orchestrator</option></select></td></tr>
					<tr><th><?php esc_html_e( 'Rules (system)', 'nextgencompanion' ); ?></th><td><textarea rows="4" class="large-text" data-ai-agent="rules"></textarea></td></tr>
					<tr><th><?php esc_html_e( 'Skills', 'nextgencompanion' ); ?></th><td><div id="ngc-ai-skills"></div></td></tr>
				</tbody></table>
				<p><button type="button" class="button button-primary" data-ai-action="save-agent"><?php esc_html_e( 'Save agent', 'nextgencompanion' ); ?></button></p>
				<h2><?php esc_html_e( 'Agents', 'nextgencompanion' ); ?></h2>
				<div id="ngc-ai-agents"><em><?php esc_html_e( 'Loading…', 'nextgencompanion' ); ?></em></div>
			</div>

			<div class="ngc-ai-panel" data-ai-panel="chat" hidden>
				<p>
					<label><?php esc_html_e( 'Talk to:', 'nextgencompanion' ); ?> <select id="ngc-ai-chat-agent"></select></label>
					<label><input type="checkbox" id="ngc-ai-swarm" /> <?php esc_html_e( 'Swarm (all agents)', 'nextgencompanion' ); ?></label>
				</p>
				<div id="ngc-ai-log" class="ngc-ai-log"></div>
				<p><textarea id="ngc-ai-input" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Type a message…', 'nextgencompanion' ); ?>"></textarea></p>
				<p><button type="button" class="button button-primary" data-ai-action="send"><?php esc_html_e( 'Send', 'nextgencompanion' ); ?></button></p>
			</div>
		</div>
		<?php
	}
}
