<?php
/**
 * Admin Setup Wizard for versioned provisioning.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup Wizard admin page.
 */
class NGC_Provisioning_Admin {

	/**
	 * Register.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 65 );
		add_action( 'admin_post_ngc_provision_run', [ __CLASS__, 'handle_run' ] );
		add_action( 'admin_post_ngc_provision_clear_lock', [ __CLASS__, 'handle_clear_lock' ] );
	}

	/**
	 * Menu under Companion.
	 */
	public static function menu() {
		add_submenu_page(
			'ngc-platform',
			__( 'Setup Wizard', 'nextgencompanion' ),
			__( 'Setup Wizard', 'nextgencompanion' ),
			'manage_options',
			'ngc-setup-wizard',
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Handle run.
	 */
	public static function handle_run() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_provision_run' );

		require_once dirname( __DIR__ ) . '/provisioning/class-ngc-provisioning-engine.php';

		$dry    = ! empty( $_POST['dry_run'] );
		$force  = ! empty( $_POST['force_safe'] );
		$demo   = ! empty( $_POST['allow_demo'] );
		$from   = isset( $_POST['from_step'] ) ? sanitize_key( wp_unslash( $_POST['from_step'] ) ) : '';
		$only   = isset( $_POST['only_step'] ) ? sanitize_key( wp_unslash( $_POST['only_step'] ) ) : '';

		$context = new NGC_Provision_Context(
			[
				'dry_run'     => $dry,
				'force_safe'  => $force,
				'allow_demo'  => $demo,
				'actor_id'    => get_current_user_id(),
			]
		);

		$report = NGC_Provisioning_Engine::run(
			$context,
			$from ? $from : null,
			$only ? $only : null
		);

		set_transient( 'ngc_provision_last_flash', $report, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-setup-wizard&ran=1' ) );
		exit;
	}

	/**
	 * Clear lock.
	 */
	public static function handle_clear_lock() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_provision_clear_lock' );
		require_once dirname( __DIR__ ) . '/provisioning/class-ngc-provisioning-engine.php';
		NGC_Provisioning_Engine::release_lock();
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-setup-wizard&lock=cleared' ) );
		exit;
	}

	/**
	 * Render wizard.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once dirname( __DIR__ ) . '/provisioning/class-ngc-provisioning-engine.php';

		$catalogue = NGC_Provisioning_Engine::catalogue();
		$state     = NGC_Provisioning_Engine::state();
		$lock      = get_option( NGC_Provisioning_Engine::LOCK_OPTION );
		$flash     = get_transient( 'ngc_provision_last_flash' );
		if ( $flash ) {
			delete_transient( 'ngc_provision_last_flash' );
		}

		echo '<div class="wrap" data-testid="ngc-setup-wizard">';
		echo '<h1>' . esc_html__( 'NextGen Tutors Setup Wizard', 'nextgencompanion' ) . '</h1>';
		echo '<p>' . esc_html__( 'Versioned, idempotent provisioning (32 steps). Secrets are never written by this wizard — enter payment/SMTP/AI keys in their respective settings screens.', 'nextgencompanion' ) . '</p>';

		if ( is_array( $flash ) ) {
			$class = ! empty( $flash['ok'] ) ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . esc_attr( $class ) . '"><p><strong>' . esc_html( (string) ( $flash['status'] ?? '' ) ) . '</strong> ';
			echo esc_html( 'correlation=' . ( $flash['correlation_id'] ?? '' ) );
			if ( ! empty( $flash['evidence_path'] ) ) {
				echo ' — ' . esc_html( (string) $flash['evidence_path'] );
			}
			echo '</p></div>';
		}

		if ( is_array( $lock ) && ! empty( $lock['until'] ) && time() < (int) $lock['until'] ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Provisioning lock is active.', 'nextgencompanion' ) . '</p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'ngc_provision_clear_lock' );
			echo '<input type="hidden" name="action" value="ngc_provision_clear_lock" />';
			submit_button( __( 'Clear lock', 'nextgencompanion' ), 'secondary', 'submit', false );
			echo '</form></div>';
		}

		echo '<h2>' . esc_html__( 'Run', 'nextgencompanion' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ngc_provision_run' );
		echo '<input type="hidden" name="action" value="ngc_provision_run" />';
		echo '<label><input type="checkbox" name="dry_run" value="1" /> ' . esc_html__( 'Dry-run (plan only)', 'nextgencompanion' ) . '</label><br />';
		echo '<label><input type="checkbox" name="force_safe" value="1" /> ' . esc_html__( 'Force-safe business profile overwrite', 'nextgencompanion' ) . '</label><br />';
		echo '<label><input type="checkbox" name="allow_demo" value="1" /> ' . esc_html__( 'Allow demo seed (non-production)', 'nextgencompanion' ) . '</label><br />';
		echo '<p><label>' . esc_html__( 'Resume from step id', 'nextgencompanion' ) . ' <input type="text" name="from_step" class="regular-text" placeholder="e.g. migrations" /></label></p>';
		echo '<p><label>' . esc_html__( 'Only step id', 'nextgencompanion' ) . ' <input type="text" name="only_step" class="regular-text" /></label></p>';
		submit_button( __( 'Run provisioning', 'nextgencompanion' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'Step catalogue', 'nextgencompanion' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>#</th><th>ID</th><th>Label</th><th>Critical</th><th>Last status</th></tr></thead><tbody>';
		foreach ( $catalogue as $row ) {
			$last = is_array( $row['last'] ?? null ) ? (string) ( $row['last']['status'] ?? '' ) : '—';
			echo '<tr>';
			echo '<td>' . esc_html( (string) $row['order'] ) . '</td>';
			echo '<td><code>' . esc_html( (string) $row['id'] ) . '</code></td>';
			echo '<td>' . esc_html( (string) $row['label'] ) . '</td>';
			echo '<td>' . ( $row['critical'] ? 'yes' : 'no' ) . '</td>';
			echo '<td>' . esc_html( $last ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'State', 'nextgencompanion' ) . '</h2>';
		echo '<pre style="max-height:320px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px;">';
		echo esc_html( wp_json_encode( $state, JSON_PRETTY_PRINT ) );
		echo '</pre>';

		echo '<p><strong>' . esc_html__( 'CLI', 'nextgencompanion' ) . ':</strong> <code>wp ngt provision run [--dry-run] [--force-safe] [--allow-demo] [--from=&lt;id&gt;] [--only=&lt;id&gt;]</code></p>';
		echo '</div>';
	}
}
