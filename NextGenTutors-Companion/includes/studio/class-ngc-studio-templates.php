<?php
/**
 * Prebuilt workflow templates for the automation studio.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ships canonical tutoring workflow templates.
 */
class NGC_Studio_Templates {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		$templates = [
			'parent_registration'    => self::template_parent_registration(),
			'student_registration'   => self::template_student_registration(),
			'child_registration'     => self::template_child_registration(),
			'tutor_registration'     => self::template_tutor_registration(),
			'tutor_approval'         => self::template_tutor_approval(),
			'tutor_rejection'        => self::template_tutor_rejection(),
			'tutor_resubmission'     => self::template_tutor_resubmission(),
			'tutor_matching'         => self::template_generic( 'tutor_matching', 'Tutor Matching', 'MATCH_CREATED' ),
			'booking'                => self::template_booking(),
			'payment'                => self::template_payment(),
			'invoice'                => self::template_generic( 'invoice', 'Invoice Workflow', 'INVOICE_CREATED' ),
			'refund'                 => self::template_generic( 'refund', 'Refund Workflow', 'PAYMENT_REFUNDED' ),
			'cancellation'           => self::template_generic( 'cancellation', 'Cancellation', 'BOOKING_CANCELLED' ),
			'session_completion'     => self::template_generic( 'session_completion', 'Session Completion', 'LESSON_COMPLETED' ),
			'tutor_payout'           => self::template_payout(),
			'parent_review'          => self::template_review(),
			'tutor_rating'           => self::template_generic( 'tutor_rating', 'Tutor Rating', 'RATING_CREATED' ),
			'crm_sync'               => self::template_generic( 'crm_sync', 'CRM Sync', 'CRM_CONTACT_CREATED', [ 'CRM', 'AUDIT', 'END' ] ),
			'lms_sync'               => self::template_generic( 'lms_sync', 'LMS Sync', 'LMS_STUDENT_CREATED', [ 'LMS', 'AUDIT', 'END' ] ),
			'affiliate_tracking'     => self::template_generic( 'affiliate_tracking', 'Affiliate Tracking', 'CUSTOM_EVENT' ),
			'escalation'             => self::template_generic( 'escalation', 'Support Escalation', 'CUSTOM_EVENT', [ 'NOTIFICATION', 'EMAIL', 'AUDIT', 'END' ] ),
			'support'                => self::template_generic( 'support', 'Support Workflow', 'CUSTOM_EVENT', [ 'EMAIL', 'AUDIT', 'END' ] ),
			'verification'           => self::template_generic( 'verification', 'Verification', 'CUSTOM_EVENT', [ 'APPROVAL', 'AUDIT', 'END' ] ),
			'self_healing'           => self::template_generic( 'self_healing', 'Self Healing', 'CRON', [ 'API', 'AUDIT', 'END' ] ),
		];

		return apply_filters( 'ngc_studio_templates', $templates );
	}

	/**
	 * Instantiate template as a draft workflow.
	 *
	 * @param string $template_key Template key.
	 * @return array{ok:bool,workflow?:array<string,mixed>,message?:string}
	 */
	public static function instantiate( $template_key ) {
		$templates = self::all();
		$key       = sanitize_key( $template_key );
		if ( empty( $templates[ $key ] ) ) {
			return [ 'ok' => false, 'message' => __( 'Template not found.', 'nextgencompanion' ) ];
		}
		$tpl = $templates[ $key ];
		return NGC_Studio_Repository::create_workflow(
			[
				'workflow_key' => 'tpl_' . $key,
				'name'         => (string) ( $tpl['name'] ?? $key ),
				'description'  => (string) ( $tpl['description'] ?? '' ),
				'graph'        => $tpl['graph'] ?? [],
				'template_key' => $key,
			]
		);
	}

	/**
	 * Seed all templates if none exist.
	 */
	public static function seed_if_empty() {
		if ( NGC_Studio_Repository::list_workflows() ) {
			return;
		}
		foreach ( array_keys( self::all() ) as $key ) {
			self::instantiate( $key );
		}
	}

	/**
	 * @param string               $key     Template key.
	 * @param string               $name    Name.
	 * @param string               $trigger Trigger.
	 * @param array<int, string>   $steps   Middle steps.
	 * @return array<string, mixed>
	 */
	private static function template_generic( $key, $name, $trigger, $steps = [ 'EMAIL', 'NOTIFICATION', 'END' ] ) {
		return [
			'key'         => $key,
			'name'        => $name,
			'description' => sprintf( __( 'Prebuilt %s automation template.', 'nextgencompanion' ), $name ),
			'graph'       => self::build_graph( $trigger, $steps ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_parent_registration() {
		return self::template_generic( 'parent_registration', 'Parent Registration', 'PARENT_REGISTERED', [ 'CRM', 'EMAIL', 'ROLE', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_student_registration() {
		return self::template_generic( 'student_registration', 'Student Registration', 'STUDENT_REGISTERED', [ 'CRM', 'LMS', 'EMAIL', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_child_registration() {
		return self::template_generic( 'child_registration', 'Child Registration', 'CHILD_REGISTERED', [ 'CRM', 'EMAIL', 'ROLE', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_tutor_registration() {
		return self::template_generic( 'tutor_registration', 'Tutor Registration', 'TUTOR_REGISTERED', [ 'CRM', 'EMAIL', 'APPROVAL', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_tutor_approval() {
		return [
			'key'         => 'tutor_approval',
			'name'        => 'Tutor Approval',
			'description' => __( 'Approve tutor, sync CRM/LMS/Amelia, assign roles, send welcome email.', 'nextgencompanion' ),
			'graph'       => self::build_graph(
				'TUTOR_APPROVED',
				[ 'CONDITION', 'ROLE', 'CRM', 'LMS', 'BOOKING', 'EMAIL', 'NOTIFICATION', 'AUDIT', 'END' ],
				[ 'CONDITION' => [ 'field' => 'status', 'operator' => 'equals', 'value' => 'approved' ] ]
			),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_tutor_rejection() {
		return self::template_generic( 'tutor_rejection', 'Tutor Rejection', 'TUTOR_REJECTED', [ 'EMAIL', 'CRM', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_tutor_resubmission() {
		return self::template_generic( 'tutor_resubmission', 'Tutor Resubmission', 'TUTOR_RESUBMITTED', [ 'EMAIL', 'APPROVAL', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_booking() {
		return self::template_generic( 'booking', 'Booking Workflow', 'BOOKING_CREATED', [ 'BOOKING', 'CRM', 'EMAIL', 'NOTIFICATION', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_payment() {
		return self::template_generic( 'payment', 'Payment Workflow', 'PAYMENT_COMPLETED', [ 'PAYMENT', 'EMAIL', 'NOTIFICATION', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_payout() {
		return self::template_generic( 'tutor_payout', 'Tutor Payout', 'CRON', [ 'PAYMENT', 'EMAIL', 'AUDIT', 'END' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function template_review() {
		return self::template_generic( 'parent_review', 'Parent Review', 'REVIEW_CREATED', [ 'CRM', 'EMAIL', 'AUDIT', 'END' ] );
	}

	/**
	 * Public linear graph builder (used by importer + templates).
	 *
	 * @param string                           $trigger Trigger key.
	 * @param array<int, string>               $steps   Step types (include END).
	 * @param array<string, array<string, mixed>> $configs Per-type config.
	 * @return array{nodes:array,edges:array}
	 */
	public static function build_linear_graph( $trigger, $steps, $configs = [] ) {
		return self::build_graph( $trigger, $steps, $configs );
	}

	/**
	 * @param string                    $trigger Trigger key.
	 * @param array<int, string>        $steps   Step types.
	 * @param array<string, array<string, mixed>> $configs Per-type config.
	 * @return array{nodes:array,edges:array}
	 */
	private static function build_graph( $trigger, $steps, $configs = [] ) {
		$nodes   = [];
		$edges   = [];
		$x       = 80;
		$prev_id = 'start';

		$nodes[] = [
			'id'       => 'start',
			'type'     => 'START',
			'position' => [ 'x' => $x, 'y' => 120 ],
			'data'     => [ 'label' => 'Start' ],
		];
		$x += 200;

		$event_id = 'event-trigger';
		$nodes[]  = [
			'id'       => $event_id,
			'type'     => 'EVENT',
			'position' => [ 'x' => $x, 'y' => 120 ],
			'data'     => [ 'label' => $trigger, 'trigger' => $trigger, 'event' => $trigger ],
		];
		$edges[] = [ 'id' => 'e-start', 'source' => $prev_id, 'target' => $event_id ];
		$prev_id = $event_id;
		$x      += 200;

		foreach ( $steps as $i => $step ) {
			if ( 'END' === $step ) {
				$end_id = 'end';
				$nodes[] = [
					'id'       => $end_id,
					'type'     => 'END',
					'position' => [ 'x' => $x, 'y' => 120 ],
					'data'     => [ 'label' => 'End' ],
				];
				$edges[] = [ 'id' => 'e-' . $i, 'source' => $prev_id, 'target' => $end_id ];
				break;
			}
			$node_id = 'step-' . strtolower( $step ) . '-' . $i;
			$nodes[] = [
				'id'       => $node_id,
				'type'     => $step,
				'position' => [ 'x' => $x, 'y' => 120 ],
				'data'     => array_merge( [ 'label' => $step ], (array) ( $configs[ $step ] ?? [] ) ),
			];
			$edges[]  = [ 'id' => 'e-' . $i, 'source' => $prev_id, 'target' => $node_id ];
			$prev_id  = $node_id;
			$x       += 200;
		}

		return [ 'nodes' => $nodes, 'edges' => $edges ];
	}
}
