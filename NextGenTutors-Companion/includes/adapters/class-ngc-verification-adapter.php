<?php
/**
 * Workflow integration verification adapter.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cross-adapter verification checks.
 */
class NGC_Verification_Adapter extends NGC_Adapter_Base {

	/**
	 * @return string
	 */
	public function slug() {
		return 'verification';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		return $this->run_all_checks();
	}

	/**
	 * @param string               $action  run_checks|verify_workflow.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( 'verify_workflow' === $action ) {
			$key = sanitize_key( $payload['workflow'] ?? '' );
			return $this->verify_workflow( $key, $payload );
		}
		return $this->success( $this->run_all_checks() );
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function run_all_checks() {
		$adapters = NGC_Workflow_Orchestrator::adapters();
		$report   = [
			'fluentcrm'   => $adapters['fluentcrm']->verify(),
			'amelia'      => $adapters['amelia']->verify(),
			'masterstudy' => $adapters['masterstudy']->verify(),
			'email'       => $adapters['email']->verify(),
			'audit'       => $adapters['audit']->verify(),
			'templates'   => NGC_Workflow_Email_Templates::verify_all(),
		];
		$report['ok'] = true;
		foreach ( $report as $key => $check ) {
			if ( 'ok' === $key || ! is_array( $check ) ) {
				continue;
			}
			if ( empty( $check['ok'] ) && empty( $check['partial'] ) ) {
				// Partial integrations are acceptable; only hard failures mark ok false for core audit/email.
				if ( in_array( $key, [ 'audit', 'email', 'templates' ], true ) && empty( $check['ok'] ) ) {
					$report['ok'] = false;
				}
			}
		}
		return $report;
	}

	/**
	 * @param string               $workflow Workflow key.
	 * @param array<string, mixed> $context  Context.
	 * @return array<string, mixed>
	 */
	public function verify_workflow( $workflow, $context ) {
		$checks = [];
		switch ( $workflow ) {
			case 'TUTOR_APPROVED':
				$user_id = (int) ( $context['user_id'] ?? 0 );
				$checks['crm_contact'] = (bool) get_user_meta( $user_id, 'ngc_fluentcrm_contact_id', true );
				$checks['amelia_employee'] = (bool) get_user_meta( $user_id, 'ngc_amelia_employee_id', true );
				$checks['stm_instructor'] = (bool) get_user_meta( $user_id, 'ngc_stm_instructor_id', true );
				$checks['marketplace'] = (bool) NGC_Post_Types::get_tutor_post_by_user_id( $user_id );
				break;
			case 'STUDENT_REGISTERED':
			case 'CHILD_REGISTERED':
				$user_id = (int) ( $context['user_id'] ?? $context['student_user_id'] ?? 0 );
				$checks['stm_student'] = $user_id ? (bool) get_user_meta( $user_id, 'ngc_stm_student_id', true ) : false;
				break;
			default:
				$checks['note'] = 'No post-run verification required.';
		}
		$checks['ok'] = ! in_array( false, array_filter( $checks, 'is_bool' ), true );
		return $this->success( [ 'workflow' => $workflow, 'checks' => $checks ] );
	}
}
