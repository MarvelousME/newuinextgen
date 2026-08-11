<?php
/**
 * Memory Center admin — flags, health, HA acknowledgement.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI under NextGen → Memory Center.
 */
class NGC_Memory_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 66 );
		add_action( 'admin_post_ngc_memory_save', [ __CLASS__, 'handle_save' ] );
	}

	/**
	 * Submenu.
	 */
	public static function register_menu() {
		$cap = 'manage_options';
		if ( current_user_can( 'ngc_manage_platform' ) ) {
			$cap = 'ngc_manage_platform';
		}
		if ( ! current_user_can( $cap ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$parent = function_exists( 'ngt_admin_parent' ) ? ngt_admin_parent() : 'ngt-admin';
		add_submenu_page(
			$parent,
			__( 'Memory Center', 'nextgencompanion' ),
			__( 'Memory Center', 'nextgencompanion' ),
			current_user_can( 'manage_options' ) ? 'manage_options' : $cap,
			'ngc-memory-center',
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * Persist settings from admin form.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ngc_manage_platform' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_memory_save' );
		$patch = [
			'enabled'                => ! empty( $_POST['enabled'] ),
			'retrieve_enabled'       => ! empty( $_POST['retrieve_enabled'] ),
			'write_enabled'          => ! empty( $_POST['write_enabled'] ),
			'skills_enabled'         => ! empty( $_POST['skills_enabled'] ),
			'wiki_enabled'           => ! empty( $_POST['wiki_enabled'] ),
			'codegraph_enabled'      => ! empty( $_POST['codegraph_enabled'] ),
			'allow_long_term_minors' => ! empty( $_POST['allow_long_term_minors'] ),
			'sqlite_ha_acknowledged' => ! empty( $_POST['sqlite_ha_acknowledged'] ),
			'proxy_enabled'          => false,
			'mode'                   => sanitize_text_field( wp_unslash( (string) ( $_POST['mode'] ?? 'DISABLED' ) ) ),
			'core_base_url'          => esc_url_raw( wp_unslash( (string) ( $_POST['core_base_url'] ?? '' ) ) ),
			'knowledge_base_url'     => esc_url_raw( wp_unslash( (string) ( $_POST['knowledge_base_url'] ?? '' ) ) ),
			'service_id_strategy'    => sanitize_key( wp_unslash( (string) ( $_POST['service_id_strategy'] ?? 'tenant' ) ) ),
		];
		if ( ! empty( $_POST['gateway_bearer'] ) && class_exists( 'NGC_Secret_Vault' ) ) {
			$ref = NGC_Secret_Vault::store( (string) wp_unslash( $_POST['gateway_bearer'] ), 'memory_gateway_bearer' );
			if ( ! is_wp_error( $ref ) ) {
				$patch['gateway_bearer_ref'] = $ref;
			}
		}
		if ( ! empty( $_POST['admin_user_key'] ) && class_exists( 'NGC_Secret_Vault' ) ) {
			$ref = NGC_Secret_Vault::store( (string) wp_unslash( $_POST['admin_user_key'] ), 'memory_admin_user_key' );
			if ( ! is_wp_error( $ref ) ) {
				$patch['admin_user_key_ref'] = $ref;
			}
		}
		NGC_Memory_Settings::update( $patch );
		NGC_Memory_Service::reset_provider();
		wp_safe_redirect( add_query_arg( [ 'page' => 'ngc-memory-center', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ngc_manage_platform' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		$cfg    = NGC_Memory_Settings::get();
		$health = NGC_Memory_Service::health();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Memory Center', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Optional TencentDB Agent Memory provider. Disabled by default. Proxy is never used as the Bridge LLM gateway. Bookings and payments must not depend on memory.', 'nextgencompanion' ); ?></p>
			<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'nextgencompanion' ); ?></p></div>
			<?php endif; ?>

			<div class="card" style="max-width:720px;padding:1em;margin:1em 0;">
				<h2><?php esc_html_e( 'Health', 'nextgencompanion' ); ?></h2>
				<p><strong><?php esc_html_e( 'Mode:', 'nextgencompanion' ); ?></strong> <?php echo esc_html( (string) ( $health['mode'] ?? '' ) ); ?></p>
				<p><strong><?php esc_html_e( 'OK:', 'nextgencompanion' ); ?></strong> <?php echo ! empty( $health['ok'] ) ? 'yes' : 'no'; ?></p>
				<p><?php echo esc_html( (string) ( $health['message'] ?? '' ) ); ?></p>
				<p><em><?php esc_html_e( 'Default Core persistence is SQLite — not multi-node HA without an approved persistence plan.', 'nextgencompanion' ); ?></em></p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngc_memory_save" />
				<?php wp_nonce_field( 'ngc_memory_save' ); ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th><?php esc_html_e( 'Enable memory', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $cfg['enabled'] ) ); ?> /> <?php esc_html_e( 'Master switch', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Mode', 'nextgencompanion' ); ?></th>
						<td>
							<select name="mode">
								<?php foreach ( [ 'DISABLED', 'LOCAL', 'REMOTE', 'DEGRADED', 'HEALTHY', 'MAINTENANCE' ] as $m ) : ?>
									<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $cfg['mode'], $m ); ?>><?php echo esc_html( $m ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
					<tr><th><?php esc_html_e( 'Core base URL', 'nextgencompanion' ); ?></th>
						<td><input type="url" class="regular-text" name="core_base_url" value="<?php echo esc_attr( (string) $cfg['core_base_url'] ); ?>" placeholder="http://memory-core:8420" /></td></tr>
					<tr><th><?php esc_html_e( 'Retrieve', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="retrieve_enabled" value="1" <?php checked( ! empty( $cfg['retrieve_enabled'] ) ); ?> /> <?php esc_html_e( 'Enable Core retrieve → prompt inject', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Write', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="write_enabled" value="1" <?php checked( ! empty( $cfg['write_enabled'] ) ); ?> /> <?php esc_html_e( 'Enable async Core write', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Skills / Wiki / CodeGraph', 'nextgencompanion' ); ?></th>
						<td>
							<label><input type="checkbox" name="skills_enabled" value="1" <?php checked( ! empty( $cfg['skills_enabled'] ) ); ?> /> Skills</label><br />
							<label><input type="checkbox" name="wiki_enabled" value="1" <?php checked( ! empty( $cfg['wiki_enabled'] ) ); ?> /> Wiki</label><br />
							<label><input type="checkbox" name="codegraph_enabled" value="1" <?php checked( ! empty( $cfg['codegraph_enabled'] ) ); ?> /> CodeGraph</label>
							<p class="description"><?php esc_html_e( 'Behind separate flags; not implemented as runtime adapters in Stage 2 Core path.', 'nextgencompanion' ); ?></p>
						</td></tr>
					<tr><th><?php esc_html_e( 'Minors / PII', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="allow_long_term_minors" value="1" <?php checked( ! empty( $cfg['allow_long_term_minors'] ) ); ?> /> <?php esc_html_e( 'Allow long-term memory when content is minor-linked (denied by default)', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'SQLite HA acknowledgement', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="sqlite_ha_acknowledged" value="1" <?php checked( ! empty( $cfg['sqlite_ha_acknowledged'] ) ); ?> /> <?php esc_html_e( 'I acknowledge default SQLite is not multi-node HA', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Gateway bearer', 'nextgencompanion' ); ?></th>
						<td><input type="password" class="regular-text" name="gateway_bearer" autocomplete="new-password" placeholder="<?php echo ! empty( $cfg['gateway_bearer_ref'] ) ? '•••• stored' : ''; ?>" />
							<p class="description"><?php esc_html_e( 'Stored in secrets vault. Leave blank to keep existing.', 'nextgencompanion' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Admin user_key', 'nextgencompanion' ); ?></th>
						<td><input type="password" class="regular-text" name="admin_user_key" autocomplete="new-password" placeholder="<?php echo ! empty( $cfg['admin_user_key_ref'] ) ? '•••• mapped' : ''; ?>" />
							<p class="description"><?php esc_html_e( 'Hidden behind mapping + vault; never shown again.', 'nextgencompanion' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Proxy as LLM gateway', 'nextgencompanion' ); ?></th>
						<td><strong><?php esc_html_e( 'Forbidden / permanently disabled', 'nextgencompanion' ); ?></strong></td></tr>
				</tbody></table>
				<?php submit_button( __( 'Save memory settings', 'nextgencompanion' ) ); ?>
			</form>
		</div>
		<?php
	}
}
