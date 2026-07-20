<?php
/**
 * Executes integrate/ JSON workflow events via NGC_Workflow_Orchestrator.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bridges integrate event catalog to orchestrator workflows and companion dispatch.
 */
class NGC_Workflow_Integrate_Executor {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow_dispatched' ], 8, 2 );
	}

	/**
	 * Integrate event → runtime handler map.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function event_bindings() {
		$bindings = [
			'tutor.application.submitted'  => [ 'type' => 'orchestrator', 'workflow' => 'TUTOR_REGISTERED' ],
			'tutor_application.submitted'  => [ 'type' => 'orchestrator', 'workflow' => 'TUTOR_REGISTERED' ],
			'tutor.approved'               => [ 'type' => 'orchestrator', 'workflow' => 'TUTOR_APPROVED' ],
			'tutor.rejected'               => [ 'type' => 'orchestrator', 'workflow' => 'TUTOR_REJECTED' ],
			'tutor.more_info_requested'    => [ 'type' => 'orchestrator', 'workflow' => 'TUTOR_RESUBMITTED' ],
			'parent_register.submitted'    => [ 'type' => 'orchestrator', 'workflow' => 'PARENT_REGISTERED' ],
			'student_register.submitted'   => [ 'type' => 'orchestrator', 'workflow' => 'STUDENT_REGISTERED' ],
			'payment.completed'            => [ 'type' => 'dispatch', 'event' => 'payment.received' ],
			'payment.failed'               => [ 'type' => 'dispatch', 'event' => 'payment.failed' ],
			'booking.created'              => [ 'type' => 'dispatch', 'event' => 'booking.created' ],
			'booking.requested'            => [ 'type' => 'dispatch', 'event' => 'booking.created' ],
			'review.submitted'             => [ 'type' => 'dispatch', 'event' => 'review.submitted' ],
			'session.scheduled'            => [ 'type' => 'module', 'handler' => 'queue_reminders' ],
			'reminders.queued'             => [ 'type' => 'dispatch', 'event' => 'reminders.queued' ],
			'payout.calculated'            => [ 'type' => 'dispatch', 'event' => 'payout.calculated' ],
		];

		return apply_filters( 'ngc_integrate_event_bindings', $bindings );
	}

	/**
	 * Execute an integrate-pack event through the orchestrator stack.
	 *
	 * @param string               $event   Dot-notation event from JSON specs.
	 * @param array<string, mixed> $context Execution context.
	 * @return array<string, mixed>
	 */
	public static function run_event( $event, $context = [] ) {
		$event   = sanitize_text_field( (string) $event );
		$context = is_array( $context ) ? $context : [];
		$spec    = NGC_Workflow_Spec_Registry::spec_for_event( $event );
		$bind    = self::event_bindings()[ $event ] ?? null;

		$result = [
			'ok'      => false,
			'event'   => $event,
			'spec_id' => $spec['id'] ?? null,
			'message' => __( 'No handler registered for event.', 'nextgencompanion' ),
		];

		if ( $bind ) {
			if ( 'orchestrator' === $bind['type'] ) {
				$result = NGC_Workflow_Orchestrator::run( $bind['workflow'], $context );
				$result['event']   = $event;
				$result['spec_id'] = $spec['id'] ?? null;
			} elseif ( 'dispatch' === $bind['type'] ) {
				NGC_Workflows::dispatch( $bind['event'], $context );
				$result = [
					'ok'         => true,
					'event'      => $event,
					'spec_id'    => $spec['id'] ?? null,
					'dispatched' => $bind['event'],
				];
			} elseif ( 'module' === $bind['type'] ) {
				$result = self::run_module_handler( $bind['handler'], $context, $event, $spec );
			}
		} else {
			NGC_Workflows::dispatch( $event, $context );
			$result = [
				'ok'         => true,
				'event'      => $event,
				'spec_id'    => $spec['id'] ?? null,
				'dispatched' => $event,
				'fallback'   => true,
			];
		}

		self::log_integrate_execution( $event, $context, $result, $spec );

		/**
		 * Fires after an integrate JSON event is executed.
		 *
		 * @param string               $event   Event slug.
		 * @param array<string, mixed> $context Context.
		 * @param array<string, mixed> $result  Result payload.
		 */
		do_action( 'ngc_integrate_event_executed', $event, $context, $result );

		return $result;
	}

	/**
	 * Import integrate JSON files into the persisted spec store.
	 *
	 * @param bool $overwrite Replace stored specs with file versions.
	 * @return array<string, mixed>
	 */
	public static function import_specs( $overwrite = true ) {
		return NGC_Workflow_Spec_Registry::import_from_integrate_dir( $overwrite );
	}

	/**
	 * Mirror companion dispatch back to integrate event names for auditing.
	 *
	 * @param string               $full Full dispatched event.
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_workflow_dispatched( $full, $vars ) {
		$reverse = [
			'ngt.tutor_application.submitted' => 'tutor.application.submitted',
			'ngt.tutor.approved'                => 'tutor.approved',
			'ngt.tutor.rejected'                => 'tutor.rejected',
			'ngt.payment.received'              => 'payment.completed',
			'ngt.booking.created'               => 'booking.created',
			'amelia.booking.created'            => 'booking.created',
			'ngt.review.submitted'              => 'review.submitted',
			'ngt.reminders.queued'              => 'reminders.queued',
			'ngt.payout.calculated'             => 'payout.calculated',
		];

		if ( empty( $reverse[ $full ] ) ) {
			return;
		}

		$integrate_event = $reverse[ $full ];
		$spec            = NGC_Workflow_Spec_Registry::spec_for_event( $integrate_event );
		if ( ! $spec ) {
			return;
		}

		update_option(
			'ngc_integrate_last_event',
			[
				'event'      => $integrate_event,
				'spec_id'    => $spec['id'],
				'full'       => $full,
				'at'         => current_time( 'mysql', true ),
			],
			false
		);
	}

	/**
	 * @param string                    $handler Module handler key.
	 * @param array<string, mixed>      $context Context.
	 * @param string                    $event   Event slug.
	 * @param array<string, mixed>|null $spec    Matched spec.
	 * @return array<string, mixed>
	 */
	private static function run_module_handler( $handler, $context, $event, $spec ) {
		if ( 'queue_reminders' === $handler && class_exists( 'NGC_Session_Reminders' ) ) {
			NGC_Session_Reminders::queue_for_booking_context( $context );
			return [
				'ok'      => true,
				'event'   => $event,
				'spec_id' => $spec['id'] ?? null,
				'module'  => 'NGC_Session_Reminders',
			];
		}

		return [
			'ok'      => false,
			'event'   => $event,
			'spec_id' => $spec['id'] ?? null,
			'message' => __( 'Module handler unavailable.', 'nextgencompanion' ),
		];
	}

	/**
	 * @param string                    $event   Event.
	 * @param array<string, mixed>      $context Context.
	 * @param array<string, mixed>      $result  Result.
	 * @param array<string, mixed>|null $spec    Spec.
	 */
	private static function log_integrate_execution( $event, $context, $result, $spec ) {
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return;
		}
		$adapters = NGC_Workflow_Orchestrator::adapters();
		if ( empty( $adapters['audit'] ) ) {
			return;
		}
		$adapters['audit']->create_or_update(
			'log_event',
			[
				'event'       => 'integrate.' . $event,
				'object_type' => 'workflow_spec',
				'object_id'   => isset( $spec['id'] ) ? (int) crc32( (string) $spec['id'] ) : 0,
				'context'     => [
					'spec_id' => $spec['id'] ?? null,
					'ok'      => ! empty( $result['ok'] ),
					'result'  => $result,
					'payload' => $context,
				],
			]
		);
	}
}
