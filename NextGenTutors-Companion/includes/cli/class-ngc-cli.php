<?php
/**
 * WP-CLI commands for NextGen Companion.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * wp ngc verify — run verification checks.
 */
class NGC_CLI {

	/**
	 * Run platform verification checks.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format: table or json.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * [--integrations]
	 * : Include integration/booking/payment/POPIA checks in output (always in JSON).
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc verify
	 *     wp ngc verify --format=json
	 *     wp ngc verify --integrations
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, mixed>  $assoc_args Flags.
	 */
	public function verify( $args, $assoc_args ) {
		$checks = NGC_Verification::run_checks();
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		$show_integrations = isset( $assoc_args['integrations'] );

		$integration_keys = [
			'bookings_engine', 'payments_engine', 'amelia_integration', 'fluentcrm_integration',
			'masterstudy_integration', 'gamipress_integration', 'popia_consent_config', 'rate_limiter',
		];

		if ( 'json' === $format ) {
			$checks['rest_permissions'] = NGC_Verification::verify_rest_permissions();
			WP_CLI::line( wp_json_encode( $checks, JSON_PRETTY_PRINT ) );
		} else {
			$rows = [];
			foreach ( $checks as $key => $value ) {
				if ( 'ok' === $key ) {
					continue;
				}
				if ( ! $show_integrations && in_array( $key, $integration_keys, true ) ) {
					continue;
				}
				$rows[] = self::format_row( $key, $value );
			}
			WP_CLI\Utils\format_items( 'table', $rows, [ 'check', 'status', 'required', 'message' ] );
			WP_CLI::line( '' );
			WP_CLI::line( 'Aggregate ok: ' . ( ! empty( $checks['ok'] ) ? 'true' : 'false' ) );
			if ( ! $show_integrations ) {
				WP_CLI::line( 'Tip: wp ngc verify --integrations  |  wp ngc verify-rest' );
			}
		}

		if ( empty( $checks['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Smoke-test REST route permission callbacks.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table or json
	 * ---
	 * default: table
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc verify-rest
	 *
	 * @param array<int, string>   $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function verify_rest( $args, $assoc_args ) {
		$report = NGC_Verification::verify_rest_permissions();
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';

		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( $report, JSON_PRETTY_PRINT ) );
		} else {
			WP_CLI\Utils\format_items( 'table', $report['routes'], [ 'route', 'methods', 'callback', 'status' ] );
			WP_CLI::line( '' );
			WP_CLI::line( 'REST permission ok: ' . ( ! empty( $report['ok'] ) ? 'true' : 'false' ) );
			WP_CLI::line( 'Failures: ' . (int) ( $report['failures'] ?? 0 ) );
		}

		if ( empty( $report['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Sync WP-user tutors into Tutor CPT posts.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing.
	 *
	 * [--link-integrations]
	 * : Copy/provision Amelia + MasterStudy links for each tutor user.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc sync-tutors
	 *     wp ngc sync-tutors --dry-run
	 *     wp ngc sync-tutors --link-integrations
	 *
	 * @param array<int, string>   $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function sync_tutors( $args, $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] );
		$result  = NGC_Tutor_Cpt_Source::sync_user_tutors_to_cpt( $dry_run );

		if ( isset( $assoc_args['link-integrations'] ) && ! $dry_run ) {
			$users = get_users( [ 'role' => 'tutor', 'number' => 200 ] );
			$integrations = [];
			foreach ( $users as $user ) {
				$integrations[ $user->ID ] = NGC_Tutor_Cpt_Source::provision_integrations_for_user( (int) $user->ID, false );
			}
			$result['integrations'] = $integrations;
		}

		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Remove demo-seeded tutor CPT posts from the directory.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc purge-demo-tutors
	 */
	public function purge_demo_tutors() {
		if ( ! class_exists( 'NGC_Tutor_Seeder' ) ) {
			WP_CLI::error( 'Tutor seeder not loaded.' );
		}
		$deleted = NGC_Tutor_Seeder::purge_demo_tutors();
		WP_CLI::success( sprintf( 'Removed %d demo tutor post(s).', $deleted ) );
	}

	/**
	 * Verify integrate/ workflow pack is loaded.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc integrate_status
	 */
	public function integrate_status() {
		if ( ! class_exists( 'NGC_Integrate_Runtime' ) ) {
			WP_CLI::error( 'Integrate runtime not loaded.' );
		}
		WP_CLI::line( wp_json_encode( NGC_Integrate_Runtime::status(), JSON_PRETTY_PRINT ) );
	}

	/**
	 * Import WooCommerce products from integrate CSV.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without creating products.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc import_woocommerce_products
	 *     wp ngc import_woocommerce_products --dry-run
	 */
	public function import_woocommerce_products( $args, $assoc_args ) {
		if ( ! class_exists( 'NGC_WooCommerce_Catalog' ) ) {
			WP_CLI::error( 'WooCommerce catalog module not loaded.' );
		}
		$dry_run = isset( $assoc_args['dry-run'] );
		$result  = NGC_WooCommerce_Catalog::import_from_csv( $dry_run );
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
		if ( ! empty( $result['errors'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Run monthly payout batch manually.
	 *
	 * ## OPTIONS
	 *
	 * [--confirm]
	 * : Immediately confirm payouts after batch (default: pending for PayFast export).
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc run_payout_batch
	 *     wp ngc run_payout_batch --confirm
	 *     wp ngc run_biweekly_payout_batch
	 */
	public function run_payout_batch( $args, $assoc_args ) {
		$this->execute_payout_batch( $assoc_args );
	}

	/**
	 * Run bi-weekly payout batch manually (same processor as monthly).
	 *
	 * ## OPTIONS
	 *
	 * [--confirm]
	 * : Immediately confirm payouts after batch.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc run_biweekly_payout_batch
	 */
	public function run_biweekly_payout_batch( $args, $assoc_args ) {
		$this->execute_payout_batch( $assoc_args );
	}

	/**
	 * @param array<string, mixed> $assoc_args CLI flags.
	 */
	private function execute_payout_batch( $assoc_args ) {
		if ( ! class_exists( 'NGC_Payout_Scheduler' ) ) {
			WP_CLI::error( 'Payout scheduler not loaded.' );
		}
		if ( ! empty( $assoc_args['confirm'] ) ) {
			add_filter( 'ngc_payout_auto_confirm', '__return_true', 99 );
		}
		$result = NGC_Payout_Scheduler::run_batch();
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Export PayFast-compatible payout CSV.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : pending (default) or preview (pending earnings).
	 *
	 * [--output=<path>]
	 * : Output file path (default: uploads/ngc-exports/).
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc export_payouts
	 *     wp ngc export_payouts --status=preview --output=/tmp/preview.csv
	 */
	public function export_payouts( $args, $assoc_args ) {
		if ( ! class_exists( 'NGC_Payout_Export' ) ) {
			WP_CLI::error( 'Payout export module not loaded.' );
		}
		$status = isset( $assoc_args['status'] ) ? sanitize_key( (string) $assoc_args['status'] ) : 'pending';
		$output = isset( $assoc_args['output'] ) ? (string) $assoc_args['output'] : '';
		$result = NGC_Payout_Export::write_file( $status, $output );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( sprintf( 'Exported %d rows to %s', (int) $result['rows'], $result['path'] ) );
	}

	/**
	 * Confirm a pending payout after PayFast/EFT transfer.
	 *
	 * ## OPTIONS
	 *
	 * <payout_id>
	 * : Payout record ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc confirm_payout 42
	 */
	public function confirm_payout( $args ) {
		$id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $id || ! class_exists( 'NGC_Reviews' ) ) {
			WP_CLI::error( 'Payout ID required.' );
		}
		$result = NGC_Reviews::confirm_payout( $id );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( 'Payout ' . $id . ' confirmed.' );
	}

	/**
	 * Process due session reminders now.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc process_reminders
	 */
	public function process_reminders() {
		if ( ! class_exists( 'NGC_Session_Reminders' ) ) {
			WP_CLI::error( 'Session reminders not loaded.' );
		}
		NGC_Session_Reminders::process_queue();
		WP_CLI::success( 'Reminder queue processed.' );
	}

	/**
	 * Import integrate/ workflow JSON into the spec store.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc workflow_import
	 */
	public function workflow_import() {
		if ( ! class_exists( 'NGC_Workflow_Spec_Registry' ) ) {
			WP_CLI::error( 'Workflow spec registry not loaded.' );
		}
		$result = NGC_Workflow_Spec_Registry::import_from_integrate_dir( true );
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
		if ( empty( $result['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * List loaded workflow specs and events.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table or json
	 * ---
	 * default: table
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc workflow_list
	 */
	public function workflow_list( $args, $assoc_args ) {
		if ( ! class_exists( 'NGC_Workflow_Spec_Registry' ) ) {
			WP_CLI::error( 'Workflow spec registry not loaded.' );
		}
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		$rows   = [];
		foreach ( NGC_Workflow_Spec_Registry::all() as $spec ) {
			$rows[] = [
				'id'     => $spec['id'] ?? '',
				'name'   => $spec['name'] ?? '',
				'events' => implode( ', ', (array) ( $spec['events'] ?? [] ) ),
			];
		}
		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( $rows, JSON_PRETTY_PRINT ) );
			return;
		}
		WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'name', 'events' ] );
	}

	/**
	 * Execute an integrate event via NGC_Workflow_Orchestrator.
	 *
	 * ## OPTIONS
	 *
	 * <event>
	 * : Integrate event slug e.g. tutor.approved
	 *
	 * [--user_id=<id>]
	 * : Optional user id for orchestrator context.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc workflow_execute tutor.approved --user_id=5
	 */
	public function workflow_execute( $args, $assoc_args ) {
		$event = isset( $args[0] ) ? (string) $args[0] : '';
		if ( ! $event ) {
			WP_CLI::error( 'Event slug required.' );
		}
		$context = [ 'source' => 'wp-cli' ];
		if ( ! empty( $assoc_args['user_id'] ) ) {
			$context['user_id'] = (int) $assoc_args['user_id'];
		}
		$result = NGC_Workflow_Orchestrator::execute_integrate_event( $event, $context );
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
		if ( empty( $result['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Delete a stored workflow spec (bundled file remains).
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Spec id e.g. workflow-01-tutor-onboarding
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc workflow_delete workflow-01-tutor-onboarding
	 */
	public function workflow_delete( $args ) {
		$id = isset( $args[0] ) ? sanitize_key( (string) $args[0] ) : '';
		if ( ! $id ) {
			WP_CLI::error( 'Spec id required.' );
		}
		$result = NGC_Workflow_Spec_Registry::delete( $id );
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
		if ( empty( $result['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Seed research-normalized homepage CMS copy.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Overwrite existing section rows.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc ui_seed_cms
	 *     wp ngc ui_seed_cms --force
	 *
	 * @param array<int, string>   $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function ui_seed_cms( $args, $assoc_args ) {
		if ( ! class_exists( 'NGC_Section_CMS' ) ) {
			WP_CLI::error( 'NGC_Section_CMS not loaded.' );
		}
		$force  = isset( $assoc_args['force'] );
		$result = NGC_Section_CMS::seed_research_copy( $force );
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
		WP_CLI::success( sprintf( 'CMS seed complete — imported %d, skipped %d.', (int) $result['imported'], (int) $result['skipped'] ) );
	}

	/**
	 * Run UI library verification (providers + import scan summary).
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc ui_verify
	 */
	public function ui_verify() {
		if ( ! class_exists( 'NGC_UI_Import_Scanner' ) ) {
			WP_CLI::error( 'UI Library not loaded.' );
		}
		$scan = NGC_UI_Import_Scanner::scan();
		WP_CLI::line( wp_json_encode( $scan, JSON_PRETTY_PRINT ) );
	}

	/**
	 * @param string $key   Check key.
	 * @param mixed  $value Check value.
	 * @return array{check:string,status:string,required:string,message:string}
	 */
	private static function format_row( $key, $value ) {
		if ( is_array( $value ) && isset( $value['status'] ) ) {
			return [
				'check'    => $key,
				'status'   => $value['status'],
				'required' => ! empty( $value['required'] ) ? 'yes' : 'no',
				'message'  => $value['message'] ?? '',
			];
		}
		if ( 'tutor_counts' === $key && is_array( $value ) ) {
			return [
				'check'    => $key,
				'status'   => 'META',
				'required' => 'no',
				'message'  => sprintf( 'real=%d demo=%d total=%d', (int) ( $value['real'] ?? 0 ), (int) ( $value['demo'] ?? 0 ), (int) ( $value['total'] ?? 0 ) ),
			];
		}
		return [
			'check'    => $key,
			'status'   => is_bool( $value ) ? ( $value ? 'PASS' : 'FAIL' ) : 'INFO',
			'required' => 'yes',
			'message'  => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ),
		];
	}
}

WP_CLI::add_command( 'ngc', 'NGC_CLI' );
