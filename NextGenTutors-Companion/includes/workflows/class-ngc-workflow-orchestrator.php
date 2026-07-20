<?php
/**
 * Registration workflow orchestrator.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes WF-TUTOR/PARENT/STUDENT/CHILD workflows across adapters.
 */
class NGC_Workflow_Orchestrator {

	/** @var array<string, NGC_Integration_Adapter>|null */
	private static $adapters = null;

	/**
	 * @return array<string, NGC_Integration_Adapter>
	 */
	public static function adapters() {
		if ( null === self::$adapters ) {
			self::$adapters = [
				'fluentcrm'   => new NGC_Fluentcrm_Adapter(),
				'amelia'      => new NGC_Amelia_Adapter(),
				'masterstudy' => new NGC_Masterstudy_Adapter(),
				'email'       => new NGC_Email_Adapter(),
				'audit'       => new NGC_Audit_Adapter(),
				'verification'=> new NGC_Verification_Adapter(),
			];
		}
		return self::$adapters;
	}

	/**
	 * Bootstrap hooks.
	 */
	public static function init() {
		NGC_Workflow_Retry_Queue::init();
		add_action( 'ngc_workflow_run', [ __CLASS__, 'run' ], 10, 2 );
	}

	/**
	 * Execute an integrate/ JSON event via bindings and adapters.
	 *
	 * @param string               $event   Integrate event slug e.g. tutor.approved.
	 * @param array<string, mixed> $context Execution context.
	 * @return array<string, mixed>
	 */
	public static function execute_integrate_event( $event, $context = [] ) {
		if ( ! class_exists( 'NGC_Workflow_Integrate_Executor' ) ) {
			return [
				'ok'      => false,
				'message' => __( 'Integrate executor not loaded.', 'nextgencompanion' ),
			];
		}
		return NGC_Workflow_Integrate_Executor::run_event( $event, $context );
	}

	/**
	 * Execute a workflow.
	 *
	 * @param string               $workflow Workflow key e.g. TUTOR_REGISTERED.
	 * @param array<string, mixed> $context  Context.
	 * @param bool                 $is_retry Retry flag.
	 * @return array<string, mixed>
	 */
	public static function run( $workflow, $context, $is_retry = false ) {
		$workflow = strtoupper( sanitize_key( $workflow ) );
		$context  = apply_filters( 'ngc_workflow_context', $context, $workflow );
		$run_id   = self::log_run_start( $workflow, $context );

		if ( class_exists( 'NGC_Amelia_Bootstrap' ) ) {
			NGC_Amelia_Bootstrap::begin_trusted_sync();
		}

		try {
			$handlers = [
				'TUTOR_REGISTERED'   => 'wf_tutor_registered',
				'TUTOR_APPROVED'       => 'wf_tutor_approved',
				'TUTOR_REJECTED'       => 'wf_tutor_rejected',
				'TUTOR_RESUBMITTED'    => 'wf_tutor_resubmitted',
				'PARENT_REGISTERED'    => 'wf_parent_registered',
				'STUDENT_REGISTERED'   => 'wf_student_registered',
				'CHILD_REGISTERED'     => 'wf_child_registered',
			];

			if ( empty( $handlers[ $workflow ] ) || ! method_exists( __CLASS__, $handlers[ $workflow ] ) ) {
				$result = [ 'ok' => false, 'message' => __( 'Unknown workflow.', 'nextgencompanion' ) ];
				self::log_run_end( $run_id, $workflow, $result );
				return $result;
			}

			$method = $handlers[ $workflow ];
			$result = self::$method( $context, $is_retry );
			self::log_run_end( $run_id, $workflow, $result );

			if ( empty( $result['ok'] ) && ! $is_retry ) {
				NGC_Workflow_Retry_Queue::enqueue( $workflow, $context, 'workflow', $result['message'] ?? 'failed' );
			}

			do_action( 'ngc_workflow_completed', $workflow, $context, $result );
			return $result;
		} finally {
			if ( class_exists( 'NGC_Amelia_Bootstrap' ) ) {
				NGC_Amelia_Bootstrap::end_trusted_sync();
			}
		}
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_tutor_registered( $context, $retry = false ) {
		$crm    = self::adapters()['fluentcrm'];
		$email  = self::adapters()['email'];
		$audit  = self::adapters()['audit'];
		$mapped = $crm->map_payload( 'WF-TUTOR-REGISTERED', $context );
		$mapped['role'] = 'tutor_applicant';
		$mapped['tutor_status'] = 'submitted';
		$mapped['workflow_status'] = 'submitted';

		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( ! $user_id && ! empty( $mapped['email'] ) ) {
			$user_id = self::ensure_user_role( $mapped['email'], $mapped['first_name'] . ' ' . $mapped['last_name'], 'tutor_applicant', $context );
			$mapped['user_id'] = $user_id;
		}
		if ( $user_id ) {
			update_user_meta( $user_id, 'ngc_tutor_status', 'submitted' );
			self::store_tutor_profile_meta( $user_id, $mapped );
		}

		$crm_result = $crm->create_or_update(
			'sync',
			array_merge(
				$mapped,
				[
					'lists' => [ 'Tutor' ],
					'tags'  => [ 'Tutor Applicant' ],
				]
			)
		);
		self::maybe_alert_sync_failure( 'crm', $crm_result, $mapped );

		$email->create_or_update( 'send_template', [ 'template_key' => 'tutor_registration_received', 'to' => $mapped['email'], 'context' => $mapped ] );
		$email->send_admin( 'admin_new_tutor_application', $mapped );

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'TUTOR_REGISTERED',
				'object_type' => 'tutor_application',
				'object_id'   => (int) ( $context['application_id'] ?? 0 ),
				'context'     => [ 'user_id' => $user_id, 'crm' => $crm_result ],
				'actor_id'    => $user_id,
			]
		);

		NGC_Workflows::dispatch( 'tutor_application.submitted', NGC_Workflows::vars_from_payload( $context ) );

		return self::workflow_result( 'TUTOR_REGISTERED', $crm_result, [ 'user_id' => $user_id ] );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_tutor_approved( $context, $retry = false ) {
		$crm         = self::adapters()['fluentcrm'];
		$amelia      = self::adapters()['amelia'];
		$masterstudy = self::adapters()['masterstudy'];
		$email       = self::adapters()['email'];
		$audit       = self::adapters()['audit'];
		$verify      = self::adapters()['verification'];

		$mapped = $crm->map_payload( 'WF-TUTOR-APPROVED', $context );
		$mapped['role'] = 'tutor';
		$mapped['tutor_status'] = 'approved';
		$mapped['approval_status'] = 'approved';
		$mapped['dashboard_url'] = home_url( '/tutor-dashboard' );

		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( $user_id ) {
			self::set_user_role_exclusive( $user_id, 'tutor', [ 'tutor_applicant' ] );
			update_user_meta( $user_id, 'ngc_tutor_status', 'approved' );
			update_user_meta( $user_id, 'ngc_tutor_verified', 1 );
			NGC_Post_Types::ensure_tutor_post( $user_id );
			$post = NGC_Post_Types::get_tutor_post_by_user_id( $user_id );
			if ( $post ) {
				wp_update_post( [ 'ID' => $post->ID, 'post_status' => 'publish' ] );
			}
		}

		$crm_result = $crm->create_or_update(
			'sync',
			array_merge(
				$mapped,
				[
					'lists'       => [ 'Tutor' ],
					'tags'        => [ 'Tutor Approved' ],
					'detach_tags' => [ 'Tutor Applicant', 'Tutor Rejected', 'Tutor Resubmitted' ],
				]
			)
		);
		self::maybe_alert_sync_failure( 'crm', $crm_result, $mapped );

		$amelia_result = $amelia->create_or_update( 'create_employee', $mapped );
		self::maybe_alert_sync_failure( 'amelia', $amelia_result, $mapped );

		$lms_result = $masterstudy->create_or_update( 'create_instructor', $mapped );
		self::maybe_alert_sync_failure( 'masterstudy', $lms_result, $mapped );

		$email->create_or_update( 'send_template', [ 'template_key' => 'tutor_approved', 'to' => $mapped['email'], 'context' => $mapped ] );
		$email->create_or_update( 'send_template', [ 'template_key' => 'tutor_onboarding_next_steps', 'to' => $mapped['email'], 'context' => $mapped ] );
		$email->send_admin( 'admin_tutor_approval_completed', $mapped );

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'TUTOR_APPROVED',
				'object_type' => 'user',
				'object_id'   => $user_id,
				'context'     => compact( 'crm_result', 'amelia_result', 'lms_result' ),
			]
		);

		$verification = $verify->create_or_update( 'verify_workflow', array_merge( $mapped, [ 'workflow' => 'TUTOR_APPROVED' ] ) );
		if ( empty( $verification['checks']['ok'] ) ) {
			$email->send_admin( 'workflow_verification_failed', array_merge( $mapped, [ 'workflow_status' => 'TUTOR_APPROVED' ] ) );
		}

		if ( function_exists( 'bi_workflow_emit_tutor_approved' ) && $user_id ) {
			bi_workflow_emit_tutor_approved( $user_id );
		} else {
			NGC_Workflows::dispatch( 'tutor.approved', [ 'user_id' => (string) $user_id, 'email' => $mapped['email'] ] );
		}

		return self::workflow_result( 'TUTOR_APPROVED', $crm_result, [
			'user_id'      => $user_id,
			'amelia'       => $amelia_result,
			'masterstudy'  => $lms_result,
			'verification' => $verification,
		] );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_tutor_rejected( $context, $retry = false ) {
		$crm   = self::adapters()['fluentcrm'];
		$email = self::adapters()['email'];
		$audit = self::adapters()['audit'];

		$mapped = $crm->map_payload( 'WF-TUTOR-REJECTED', $context );
		$mapped['tutor_status'] = 'rejected';
		$mapped['rejection_reason'] = $context['review_notes'] ?? $context['rejection_reason'] ?? '';

		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( $user_id ) {
			update_user_meta( $user_id, 'ngc_tutor_status', 'rejected' );
			update_user_meta( $user_id, 'ngc_tutor_verified', 0 );
			$post = NGC_Post_Types::get_tutor_post_by_user_id( $user_id );
			if ( $post ) {
				wp_update_post( [ 'ID' => $post->ID, 'post_status' => 'draft' ] );
			}
		}

		$crm_result = $crm->create_or_update(
			'sync',
			array_merge(
				$mapped,
				[
					'lists'       => [ 'Tutor' ],
					'tags'        => [ 'Tutor Rejected' ],
					'detach_tags' => [ 'Tutor Applicant', 'Tutor Approved' ],
				]
			)
		);

		$email->create_or_update( 'send_template', [ 'template_key' => 'tutor_application_not_approved', 'to' => $mapped['email'], 'context' => $mapped ] );
		if ( ! empty( $context['allow_resubmit'] ) ) {
			$email->create_or_update( 'send_template', [ 'template_key' => 'tutor_resubmission_invitation', 'to' => $mapped['email'], 'context' => $mapped ] );
		}

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'TUTOR_REJECTED',
				'object_type' => 'tutor_application',
				'object_id'   => (int) ( $context['application_id'] ?? 0 ),
				'context'     => [ 'reason' => $mapped['rejection_reason'] ],
			]
		);

		NGC_Workflows::dispatch(
			'tutor.rejected',
			[
				'user_id'        => (string) $user_id,
				'application_id' => (string) ( $context['application_id'] ?? 0 ),
				'email'          => $mapped['email'],
				'review_notes'   => $mapped['rejection_reason'],
			]
		);

		return self::workflow_result( 'TUTOR_REJECTED', $crm_result, [ 'user_id' => $user_id ] );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_tutor_resubmitted( $context, $retry = false ) {
		$crm   = self::adapters()['fluentcrm'];
		$email = self::adapters()['email'];
		$audit = self::adapters()['audit'];

		$mapped = $crm->map_payload( 'WF-TUTOR-RESUBMITTED', $context );
		$mapped['tutor_status'] = 'resubmitted';

		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( $user_id ) {
			update_user_meta( $user_id, 'ngc_tutor_status', 'resubmitted' );
		}

		$crm_result = $crm->create_or_update(
			'sync',
			array_merge(
				$mapped,
				[
					'lists'       => [ 'Tutor' ],
					'tags'        => [ 'Tutor Resubmitted', 'Tutor Applicant' ],
					'detach_tags' => [ 'Tutor Rejected' ],
				]
			)
		);

		$email->create_or_update( 'send_template', [ 'template_key' => 'tutor_resubmission_received', 'to' => $mapped['email'], 'context' => $mapped ] );
		$email->send_admin( 'admin_tutor_resubmission_review', $mapped );

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'TUTOR_RESUBMITTED',
				'object_type' => 'tutor_application',
				'object_id'   => (int) ( $context['application_id'] ?? 0 ),
				'context'     => $context,
			]
		);

		NGC_Workflows::dispatch( 'tutor_application.submitted', NGC_Workflows::vars_from_payload( $context ) );

		return self::workflow_result( 'TUTOR_RESUBMITTED', $crm_result, [ 'user_id' => $user_id ] );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_parent_registered( $context, $retry = false ) {
		$crm   = self::adapters()['fluentcrm'];
		$email = self::adapters()['email'];
		$audit = self::adapters()['audit'];

		$mapped = $crm->map_payload( 'WF-PARENT-REGISTERED', $context );
		$mapped['role'] = 'parent';
		$mapped['dashboard_url'] = home_url( '/parent-dashboard' );

		$user_id = (int) ( $context['user_id'] ?? 0 );
		$crm_result = $crm->create_or_update(
			'sync',
			array_merge(
				$mapped,
				[
					'lists' => [ 'Parent' ],
					'tags'  => [ 'Parent Registered' ],
				]
			)
		);

		if ( $mapped['email'] ) {
			$email->create_or_update( 'send_template', [ 'template_key' => 'parent_welcome', 'to' => $mapped['email'], 'context' => $mapped ] );
		}
		$email->send_admin( 'admin_new_parent_registration', $mapped );

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'PARENT_REGISTERED',
				'object_type' => 'user',
				'object_id'   => $user_id,
				'context'     => $context,
			]
		);

		NGC_Workflows::dispatch( 'parent_register.submitted', NGC_Workflows::vars_from_payload( $context ) );

		// Child learner profile on same form.
		if ( ! empty( $context['child_name'] ) ) {
			self::run(
				'CHILD_REGISTERED',
				array_merge(
					$context,
					[
						'parent_user_id' => $user_id,
						'student_name'   => $context['child_name'],
					]
				)
			);
		}

		return self::workflow_result( 'PARENT_REGISTERED', $crm_result, [ 'user_id' => $user_id ] );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_student_registered( $context, $retry = false ) {
		$crm         = self::adapters()['fluentcrm'];
		$masterstudy = self::adapters()['masterstudy'];
		$email       = self::adapters()['email'];
		$audit       = self::adapters()['audit'];

		$mapped = $crm->map_payload( 'WF-STUDENT-REGISTERED', $context );
		$mapped['role'] = 'student';
		$mapped['dashboard_url'] = home_url( '/student-dashboard' );

		$user_id = (int) ( $context['user_id'] ?? 0 );
		$crm_result = $crm->create_or_update(
			'sync',
			array_merge(
				$mapped,
				[
					'lists' => [ 'Student' ],
					'tags'  => [ 'Student Registered', 'LMS Student' ],
				]
			)
		);

		$lms_result = $masterstudy->create_or_update( 'create_student', array_merge( $mapped, [ 'user_id' => $user_id ] ) );
		self::maybe_alert_sync_failure( 'masterstudy', $lms_result, $mapped );

		if ( $mapped['email'] ) {
			$email->create_or_update( 'send_template', [ 'template_key' => 'student_welcome', 'to' => $mapped['email'], 'context' => $mapped ] );
		}

		$parent_email = sanitize_email( $context['parent_email'] ?? '' );
		if ( $parent_email ) {
			$email->create_or_update(
				'send_template',
				[
					'template_key' => 'parent_student_profile_created',
					'to'           => $parent_email,
					'context'      => $mapped,
				]
			);
		}

		$email->send_admin( 'admin_new_student_registration', $mapped );

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'STUDENT_REGISTERED',
				'object_type' => 'user',
				'object_id'   => $user_id,
				'context'     => [ 'lms' => $lms_result ],
			]
		);

		NGC_Workflows::dispatch( 'student_register.submitted', NGC_Workflows::vars_from_payload( $context ) );

		return self::workflow_result( 'STUDENT_REGISTERED', $crm_result, [ 'user_id' => $user_id, 'masterstudy' => $lms_result ] );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @param bool                 $retry   Retry.
	 * @return array<string, mixed>
	 */
	private static function wf_child_registered( $context, $retry = false ) {
		$crm         = self::adapters()['fluentcrm'];
		$masterstudy = self::adapters()['masterstudy'];
		$email       = self::adapters()['email'];
		$audit       = self::adapters()['audit'];

		$mapped = $crm->map_payload( 'WF-CHILD-REGISTERED', $context );
		$mapped['student_name'] = $context['student_name'] ?? $context['child_name'] ?? '';
		$parent_id = (int) ( $context['parent_user_id'] ?? 0 );
		$child_email = sanitize_email( $context['child_email'] ?? '' );
		$student_user_id = (int) ( $context['student_user_id'] ?? 0 );

		$crm_result = [ 'ok' => true, 'skipped' => true ];
		if ( $child_email ) {
			$mapped['email'] = $child_email;
			$crm_result = $crm->create_or_update(
				'sync',
				array_merge(
					$mapped,
					[
						'lists' => [ 'Student' ],
						'tags'  => [ 'Child Learner', 'Student Registered' ],
					]
				)
			);
		} else {
			$audit->create_or_update(
				'log_event',
				[
					'event'       => 'CRM_SKIPPED_NO_EMAIL',
					'object_type' => 'learner_profile',
					'object_id'   => $parent_id,
					'context'     => $mapped,
				]
			);
		}

		$lms_result = [ 'ok' => true, 'skipped' => true ];
		if ( ! empty( $context['lms_access'] ) && $student_user_id ) {
			$lms_result = $masterstudy->create_or_update( 'create_student', array_merge( $mapped, [ 'user_id' => $student_user_id ] ) );
			self::maybe_alert_sync_failure( 'masterstudy', $lms_result, $mapped );
		}

		$parent = $parent_id ? get_user_by( 'id', $parent_id ) : null;
		if ( $parent ) {
			$mapped['parent_name'] = $parent->display_name;
			$email->create_or_update(
				'send_template',
				[
					'template_key' => 'child_learner_profile_created',
					'to'           => $parent->user_email,
					'context'      => $mapped,
				]
			);
		}
		$email->send_admin( 'admin_child_learner_created', $mapped );

		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'CHILD_REGISTERED',
				'object_type' => 'learner_profile',
				'object_id'   => $parent_id,
				'context'     => $context,
			]
		);

		return self::workflow_result( 'CHILD_REGISTERED', $crm_result, [ 'parent_user_id' => $parent_id ] );
	}

	/**
	 * @param string               $workflow Workflow.
	 * @param array<string, mixed> $primary  Primary adapter result.
	 * @param array<string, mixed> $extra    Extra data.
	 * @return array<string, mixed>
	 */
	private static function workflow_result( $workflow, $primary, $extra = [] ) {
		$partial = ! empty( $primary['partial'] );
		return array_merge(
			[
				'ok'       => ! empty( $primary['ok'] ) || $partial,
				'partial'  => $partial,
				'workflow' => $workflow,
				'message'  => $primary['message'] ?? '',
			],
			$extra
		);
	}

	/**
	 * @param string $adapter Adapter slug.
	 * @param array<string, mixed> $result Result.
	 * @param array<string, mixed> $mapped Context.
	 */
	private static function maybe_alert_sync_failure( $adapter, $result, $mapped ) {
		if ( ! empty( $result['ok'] ) ) {
			return;
		}
		$templates = [
			'crm'         => 'crm_sync_failed',
			'amelia'      => 'amelia_sync_failed',
			'masterstudy' => 'masterstudy_sync_failed',
		];
		if ( isset( $templates[ $adapter ] ) ) {
			self::adapters()['email']->send_admin( $templates[ $adapter ], $mapped );
		}
	}

	/**
	 * @param string $email Email.
	 * @param string $name  Name.
	 * @param string $role  Role.
	 * @param array<string, mixed> $meta Meta.
	 * @return int
	 */
	public static function ensure_user_role( $email, $name, $role, $meta = [] ) {
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			if ( ! in_array( $role, (array) $existing->roles, true ) ) {
				$existing->add_role( $role );
			}
			return (int) $existing->ID;
		}

		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( username_exists( $username ) ) {
			$username = sanitize_user( $username . wp_rand( 100, 999 ), true );
		}

		$user_id = wp_create_user( $username, wp_generate_password( 16, true, true ), $email );
		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		wp_update_user(
			[
				'ID'           => $user_id,
				'display_name' => trim( $name ) ?: $username,
				'first_name'   => trim( $name ),
			]
		);

		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			$user->set_role( $role );
		}

		wp_new_user_notification( $user_id, null, 'user' );
		return (int) $user_id;
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $role    New primary role.
	 * @param string[] $remove Roles to remove.
	 */
	private static function set_user_role_exclusive( $user_id, $role, $remove = [] ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		foreach ( $remove as $old ) {
			if ( in_array( $old, (array) $user->roles, true ) ) {
				$user->remove_role( $old );
			}
		}
		if ( ! in_array( $role, (array) $user->roles, true ) ) {
			$user->add_role( $role );
		}
	}

	/**
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $mapped  Data.
	 */
	private static function store_tutor_profile_meta( $user_id, $mapped ) {
		if ( ! empty( $mapped['phone'] ) ) {
			update_user_meta( $user_id, 'ngc_phone', $mapped['phone'] );
		}
		if ( ! empty( $mapped['subjects'] ) ) {
			update_user_meta( $user_id, 'ngc_subjects', $mapped['subjects'] );
		}
		if ( ! empty( $mapped['location'] ) ) {
			update_user_meta( $user_id, 'ngc_province', $mapped['location'] );
		}
		if ( ! empty( $mapped['bio'] ) ) {
			update_user_meta( $user_id, 'ngc_bio', $mapped['bio'] );
		}
	}

	/**
	 * @param string               $workflow Workflow.
	 * @param array<string, mixed> $context  Context.
	 * @return int Run ID.
	 */
	private static function log_run_start( $workflow, $context ) {
		global $wpdb;
		$table = NGC_Database::table( 'workflow_runs' );
		if ( ! $table ) {
			return 0;
		}
		$wpdb->insert(
			$table,
			[
				'workflow_key' => $workflow,
				'status'       => 'running',
				'context'      => wp_json_encode( $context ),
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param int                  $run_id   Run ID.
	 * @param string               $workflow Workflow.
	 * @param array<string, mixed> $result   Result.
	 */
	private static function log_run_end( $run_id, $workflow, $result ) {
		if ( ! $run_id ) {
			return;
		}
		global $wpdb;
		$table = NGC_Database::table( 'workflow_runs' );
		$wpdb->update(
			$table,
			[
				'status'     => ! empty( $result['ok'] ) ? 'completed' : 'failed',
				'results'    => wp_json_encode( $result ),
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $run_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		$status = ! empty( $result['ok'] ) ? 'completed' : 'failed';
		do_action( 'ngc_workflow_logged', $workflow, $status, [ 'run_id' => $run_id, 'result' => $result ] );
	}

	/**
	 * Stats for admin dashboard.
	 *
	 * @return array<string, int>
	 */
	public static function stats() {
		global $wpdb;
		$table = NGC_Database::table( 'workflow_runs' );
		if ( ! $table ) {
			return [ 'total' => 0, 'failed' => 0, 'completed' => 0 ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return [
			'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
			'failed'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'" ),
			'completed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'completed'" ),
		];
	}
}
