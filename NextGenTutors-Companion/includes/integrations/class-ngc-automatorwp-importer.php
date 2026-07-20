<?php
/**
 * Seeds AutomatorWP automations from Command Center v2 workflow catalog.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds AutomatorWP recipe rows from integrate/catalog/v2 JSON definitions.
 */
class NGC_AutomatorWP_Importer {

	const OPTION_SEEDED = 'ngc_automatorwp_v2_seeded';

	/**
	 * v2 event → AutomatorWP trigger type (built-in integrations when possible).
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function trigger_map() {
		return apply_filters(
			'ngc_automatorwp_trigger_map',
			[
				'find_a_tutor_form_submitted'        => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.find_tutor.submitted' ],
				'tutor_application_submitted'        => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.tutor_application.submitted' ],
				'tutor_approved'                     => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.tutor.approved' ],
				'woocommerce_order_status_completed' => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.payment.received' ],
				'booking_created'                    => [ 'integration' => 'ameliabooking', 'type' => 'ameliabooking_user_books_appointment' ],
				'lesson_status_completed'            => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.lesson.completed' ],
				'progress_report_created'            => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.progress_report.submitted' ],
				'support_escalated'                  => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.support.escalated' ],
				'payout_approved'                    => [ 'integration' => 'nextgencompanion', 'type' => 'nextgencompanion_workflow_event', 'event' => 'ngt.payout.processed' ],
			]
		);
	}

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'ngc_integrate_runtime_ready', [ __CLASS__, 'maybe_auto_seed' ], 30 );
	}

	/**
	 * Auto-seed once when AutomatorWP + demo seed flag are active.
	 */
	public static function maybe_auto_seed() {
		if ( get_option( self::OPTION_SEEDED ) ) {
			return;
		}
		if ( ! ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) ) {
			return;
		}
		if ( ! function_exists( 'automatorwp_register_trigger' ) ) {
			return;
		}
		self::import_from_v2_catalog( false );
	}

	/**
	 * Import v2 catalog JSON as AutomatorWP automations.
	 *
	 * @param bool $force Re-seed even if previously imported.
	 * @return array{ok:bool,created:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_from_v2_catalog( $force = false ) {
		if ( ! function_exists( 'ct_insert_object' ) || ! function_exists( 'ct_setup_table' ) ) {
			return [
				'ok'      => false,
				'created' => 0,
				'skipped' => 0,
				'errors'  => [ __( 'AutomatorWP is not active.', 'nextgencompanion' ) ],
			];
		}

		if ( $force ) {
			delete_option( self::OPTION_SEEDED );
		} elseif ( get_option( self::OPTION_SEEDED ) ) {
			return [ 'ok' => true, 'created' => 0, 'skipped' => 0, 'errors' => [] ];
		}

		$dir = trailingslashit( NGC_PLUGIN_DIR ) . 'integrate/catalog/v2';
		if ( ! is_dir( $dir ) ) {
			return [
				'ok'      => false,
				'created' => 0,
				'skipped' => 0,
				'errors'  => [ __( 'v2 catalog directory missing.', 'nextgencompanion' ) ],
			];
		}

		$created = 0;
		$skipped = 0;
		$errors  = [];
		$map     = self::trigger_map();

		foreach ( (array) glob( $dir . '/*.json' ) as $path ) {
			$data = json_decode( (string) file_get_contents( $path ), true );
			if ( ! is_array( $data ) ) {
				$errors[] = basename( $path ) . ': invalid JSON';
				continue;
			}
			$event = is_array( $data['trigger'] ?? null ) ? (string) ( $data['trigger']['event'] ?? '' ) : (string) ( $data['trigger'] ?? '' );
			if ( ! $event || empty( $map[ $event ] ) ) {
				++$skipped;
				continue;
			}
			$title = (string) ( $data['name'] ?? $data['id'] ?? basename( $path, '.json' ) );
			if ( self::automation_exists( $title ) ) {
				++$skipped;
				continue;
			}
			$result = self::create_automation( $title, $map[ $event ], $data );
			if ( $result['ok'] ) {
				++$created;
			} else {
				$errors[] = $title . ': ' . ( $result['message'] ?? 'failed' );
			}
		}

		if ( $created > 0 || empty( $errors ) ) {
			update_option( self::OPTION_SEEDED, gmdate( 'c' ), false );
		}

		return [
			'ok'      => empty( $errors ),
			'created' => $created,
			'skipped' => $skipped,
			'errors'  => $errors,
		];
	}

	/**
	 * @param string $title Automation title.
	 * @return bool
	 */
	private static function automation_exists( $title ) {
		ct_setup_table( 'automatorwp_automations' );
		$existing = ct_get_objects(
			[
				'title'          => $title,
				'items_per_page' => 1,
			]
		);
		ct_reset_setup_table();
		return ! empty( $existing );
	}

	/**
	 * @param string               $title   Title.
	 * @param array<string,string> $trigger Trigger map row.
	 * @param array<string,mixed>  $spec    v2 JSON spec.
	 * @return array{ok:bool,message?:string,id?:int}
	 */
	private static function create_automation( $title, $trigger, $spec ) {
		ct_setup_table( 'automatorwp_automations' );
		$automation_id = ct_insert_object(
			[
				'title'  => $title,
				'status' => 'active',
				'type'   => 'user',
				'user_id'=> get_current_user_id() ?: 1,
				'date'   => current_time( 'mysql' ),
			]
		);
		ct_reset_setup_table();

		if ( ! $automation_id ) {
			return [ 'ok' => false, 'message' => 'automation_insert_failed' ];
		}

		ct_setup_table( 'automatorwp_triggers' );
		$trigger_id = ct_insert_object(
			[
				'automation_id' => $automation_id,
				'title'         => $title . ' trigger',
				'type'          => $trigger['type'],
				'status'        => 'active',
				'position'      => 0,
				'date'          => current_time( 'mysql' ),
			]
		);
		ct_reset_setup_table();

		if ( $trigger_id && ! empty( $trigger['event'] ) && function_exists( 'ct_update_object_meta' ) ) {
			ct_setup_table( 'automatorwp_triggers' );
			ct_update_object_meta( $trigger_id, 'event', $trigger['event'] );
			ct_reset_setup_table();
		}

		$action_type = 'wordpress_send_email';
		ct_setup_table( 'automatorwp_actions' );
		$action_id = ct_insert_object(
			[
				'automation_id' => $automation_id,
				'title'         => $title . ' notify admin',
				'type'          => $action_type,
				'status'        => 'active',
				'position'      => 0,
				'date'          => current_time( 'mysql' ),
			]
		);
		ct_reset_setup_table();

		if ( $action_id && function_exists( 'ct_update_object_meta' ) ) {
			ct_setup_table( 'automatorwp_actions' );
			ct_update_object_meta( $action_id, 'email', get_option( 'admin_email' ) );
			$body = sprintf(
				"NextGen workflow \"%s\" fired.\n\nSpec: %s\n\nActions planned:\n%s",
				$title,
				(string) ( $spec['id'] ?? '' ),
				implode( "\n", (array) ( $spec['steps'] ?? [] ) )
			);
			ct_update_object_meta( $action_id, 'content', $body );
			ct_update_object_meta( $action_id, 'subject', '[NextGen] ' . $title );
			ct_reset_setup_table();
		}

		return [ 'ok' => true, 'id' => (int) $automation_id ];
	}
}
