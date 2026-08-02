<?php
/**
 * Mission Control admin UI.
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Top-level Mission Control menu.
 */
final class NGTMC_Admin {

	public const PAGE = 'ngtmc-mission-control';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'admin_post_ngtmc_action', [ __CLASS__, 'handle_post' ] );
	}

	public static function menu() {
		// Owned by NEXT GEN TUTORS shell — page slug preserved for deep links.
		if ( class_exists( 'NGC_Admin_Shell' ) ) {
			return;
		}
		add_menu_page(
			__( 'Mission Control', 'nextgentutors-mission-control' ),
			__( 'Mission Control', 'nextgentutors-mission-control' ),
			'manage_options',
			self::PAGE,
			[ __CLASS__, 'render' ],
			'dashicons-superhero',
			2
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$on_mc = self::PAGE === $page
			|| ( class_exists( 'NGC_Admin_Shell' ) && NGC_Admin_Shell::PARENT_SLUG === $page )
			|| false !== strpos( (string) $hook, self::PAGE );
		if ( ! $on_mc ) {
			return;
		}
		$tab = sanitize_key( (string) ( $_GET['tab'] ?? 'status' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_enqueue_style(
			'ngtmc-admin',
			NGTMC_PLUGIN_URL . 'assets/css/admin.css',
			[],
			NGTMC_VERSION
		);
		wp_enqueue_script(
			'ngtmc-admin',
			NGTMC_PLUGIN_URL . 'assets/js/admin.js',
			[],
			NGTMC_VERSION,
			true
		);
		wp_localize_script(
			'ngtmc-admin',
			'ngtmcAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ngtmc_ajax' ),
				'page'    => self::PAGE,
			]
		);

		NGTMC_Intelligence::maybe_enqueue( $hook, $tab );
	}

	public static function handle_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgentutors-mission-control' ) );
		}
		check_admin_referer( 'ngtmc_action' );

		$op = sanitize_key( (string) ( $_POST['ngtmc_op'] ?? '' ) );
		$result = [ 'ok' => false, 'op' => $op ];

		switch ( $op ) {
			case 'configure':
				$result = NGTMC_Orchestrator::configure( true );
				break;
			case 'repair':
				$result = NGTMC_Orchestrator::repair();
				break;
			case 'seed':
				$result = NGTMC_Orchestrator::seed( true );
				break;
			case 'verify':
				$result = NGTMC_Orchestrator::verify();
				break;
			case 'pipeline':
				$result = NGTMC_Orchestrator::run_pipeline(
					[
						'force' => true,
						'seed'  => ! empty( $_POST['ngtmc_seed'] ),
					]
				);
				break;
			case 'export':
				$result = NGTMC_Orchestrator::export_report();
				break;
			case 'overrides':
				$result = [
					'ok'        => true,
					'overrides' => NGTMC_Overrides::save( wp_unslash( $_POST ) ),
				];
				break;
			default:
				$result['error'] = 'Unknown operation';
		}

		set_transient(
			'ngtmc_flash_' . get_current_user_id(),
			[
				'op'     => $op,
				'ok'     => ! empty( $result['ok'] ),
				'detail' => $result,
			],
			60
		);

		wp_safe_redirect(
			add_query_arg(
				[
					'page' => self::PAGE,
					'tab'  => sanitize_key( (string) ( $_POST['ngtmc_tab'] ?? 'status' ) ),
					'done' => $op,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgentutors-mission-control' ) );
		}

		$tab      = sanitize_key( (string) ( $_GET['tab'] ?? 'status' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$snapshot = NGTMC_Orchestrator::snapshot();
		$flash    = get_transient( 'ngtmc_flash_' . get_current_user_id() );
		if ( $flash ) {
			delete_transient( 'ngtmc_flash_' . get_current_user_id() );
		}
		$overrides = NGTMC_Overrides::get();
		$links     = self::control_map_links();

		include NGTMC_PLUGIN_DIR . 'templates/app.php';
	}

	/**
	 * Deep-links into existing control surfaces.
	 *
	 * @return array<int, array{label:string,desc:string,url:string,testid:string}>
	 */
	public static function control_map_links() {
		return [
			[
				'label'  => __( 'Business Profile', 'nextgentutors-mission-control' ),
				'desc'   => __( 'SSOT identity apply into theme & plugins', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngc-business-profile' ),
				'testid' => 'ngtmc-link-business',
			],
			[
				'label'  => __( 'Demo Control Centre', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Phase 14 seed, verify, journeys, evidence', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngc-demo-control' ),
				'testid' => 'ngtmc-link-demo',
			],
			[
				'label'  => __( 'Plugin Manager', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Install / activate / readiness fleet console', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ui-ux-pro-max' ),
				'testid' => 'ngtmc-link-ngcpm',
			],
			[
				'label'  => __( 'Companion Ops', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Matches, payouts, health, applications', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngc-operations' ),
				'testid' => 'ngtmc-link-ops',
			],
			[
				'label'  => __( 'Automation Hub', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Workflow toggles and system repair', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngt-hub' ),
				'testid' => 'ngtmc-link-hub',
			],
			[
				'label'  => __( 'AI Integration', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Agent bridge settings, pause, health', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngtai-settings' ),
				'testid' => 'ngtmc-link-ai',
			],
			[
				'label'  => __( 'Agent Ops', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Kill switches and approvals', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngc-agent-ops' ),
				'testid' => 'ngtmc-link-agents',
			],
			[
				'label'  => __( 'Page Registry', 'nextgentutors-mission-control' ),
				'desc'   => __( 'Verify and repair launch pages / forms', 'nextgentutors-mission-control' ),
				'url'    => admin_url( 'admin.php?page=ngc-page-registry' ),
				'testid' => 'ngtmc-link-pages',
			],
		];
	}
}
