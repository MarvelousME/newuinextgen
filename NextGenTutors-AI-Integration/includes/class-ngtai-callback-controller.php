<?php
/**
 * Governed callback processing.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Callback_Controller {
	/**
	 * Backward-compatible alias retained for existing callback consumers.
	 *
	 * @param array<string,mixed>  $body    Payload.
	 * @param array<string,string> $headers Headers.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function apply_agent_result( array $body, array $headers ) {
		return self::handle_agent_result( $body, $headers );
	}

	/**
	 * Validate, gate, store, and optionally publish an agent result.
	 *
	 * @param array<string,mixed>  $body    Payload.
	 * @param array<string,string> $headers Headers.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function handle_agent_result( array $body, array $headers ) {
		$correlation = sanitize_text_field( (string) ( $body['correlation_id'] ?? $headers['x-ngt-correlation-id'] ?? '' ) );
		try {
			$contract = new NGTAI_Agent_Result( $body );
			$data     = $contract->to_array();
			$payload  = $data['result'];
		} catch ( InvalidArgumentException $exception ) {
			NGTAI_Logger::bump( 'ngtai_callback_failure_total' );
			NGTAI_Audit::log( 'ngtai_callback_received', [ 'outcome' => 'invalid', 'error_code' => 'invalid_result' ], $correlation );
			return new WP_Error( 'ngtai_invalid_result', $exception->getMessage(), [ 'status' => 422 ] );
		}

		$action  = self::sanitize_action_name( (string) $data['action_name'] );
		$context = [
			'agent_name'    => sanitize_text_field( (string) ( $body['agent_name'] ?? '' ) ),
			'agent_run_id'  => sanitize_text_field( (string) ( $body['agent_run_id'] ?? '' ) ),
			'subject_id'    => sanitize_text_field( (string) ( $body['subject_id'] ?? $body['match_id'] ?? ( is_array( $payload ) ? ( $payload['subject_id'] ?? '' ) : '' ) ) ),
			'correlation_id'=> $correlation,
			'payload'       => $payload,
		];
		$policy_event = 'match.recommendation' === $action ? 'match.requested' : $action;
		$policy       = NGTAI_Policy_Gate::evaluate( $policy_event, array_merge( $context, [ 'action' => $action ] ) );
		$decision = strtoupper( (string) ( $policy['decision'] ?? 'DENY' ) );

		if ( 'DENY' === $decision || 'ESCALATE' === $decision ) {
			NGTAI_Logger::bump( 'ngtai_policy_denied_total' );
			NGTAI_Audit::log( 'ngtai_policy_denied', [ 'action_name' => $action, 'reason' => $policy['reason'] ?? '' ], $correlation );
			return new WP_Error( 'ngtai_policy_denied', __( 'Policy denied this action.', 'nextgentutors-ai-integration' ), [ 'status' => 403 ] );
		}

		$ranking = $payload['ranking'] ?? $payload['candidates'] ?? [];
		if ( 'match.recommendation' === $action ) {
			if ( ! is_array( $ranking ) ) {
				return new WP_Error( 'ngtai_invalid_ranking', __( 'Ranking must be an array.', 'nextgentutors-ai-integration' ), [ 'status' => 422 ] );
			}
			foreach ( $ranking as $candidate ) {
				if ( ! is_array( $candidate ) || ( array_key_exists( 'eligible', $candidate ) && ! $candidate['eligible'] ) ) {
					return new WP_Error( 'ngtai_ineligible_candidate', __( 'Rankings may contain only eligible candidates.', 'nextgentutors-ai-integration' ), [ 'status' => 422 ] );
				}
			}
			if ( $ranking && '' === trim( (string) ( $payload['explanation'] ?? '' ) ) ) {
				return new WP_Error( 'ngtai_explanation_required', __( 'A non-empty ranking requires an explanation.', 'nextgentutors-ai-integration' ), [ 'status' => 422 ] );
			}
		}

		$approval_id       = sanitize_text_field( (string) ( $body['approval_id'] ?? '' ) );
		$requires_approval = 'REQUIRE_APPROVAL' === $decision || ! empty( $policy['requires_approval'] );
		if ( $requires_approval && '' === $approval_id ) {
			$approval_id = NGTAI_Signature::uuid();
		}
		$data['policy_decision'] = $decision;
		$data['approval_id']     = $approval_id ?: null;
		$stored_contract         = new NGTAI_Agent_Result( $data );
		$result_id               = NGTAI_Result_Repository::store( $stored_contract );
		if ( 'duplicate' === $result_id ) {
			return [ 'success' => true, 'idempotent' => true ];
		}
		if ( ! is_int( $result_id ) ) {
			return new WP_Error( 'ngtai_result_storage_failed', __( 'Unable to store result.', 'nextgentutors-ai-integration' ), [ 'status' => 500 ] );
		}

		if ( $requires_approval ) {
			self::set_result_status( $result_id, 'pending_approval' );
			$request = self::store_approval(
				[
					'approval_id' => $approval_id,
					'agent_run_id'=> $body['agent_run_id'] ?? '',
					'action_name' => $action,
					'requested_by'=> $body['agent_name'] ?? 'agents-api',
					'payload'     => [ 'result_id' => $result_id, 'subject_id' => $context['subject_id'], 'result' => $payload, 'risk' => $body['risk'] ?? '' ],
				]
			);
			if ( is_wp_error( $request ) ) {
				return $request;
			}
			NGTAI_Audit::log( 'ngtai_approval_requested', [ 'approval_id' => $approval_id, 'action_name' => $action ], $correlation );
			return [ 'success' => true, 'status' => 'pending_approval', 'approval_id' => $approval_id ];
		}

		if ( 'match.recommendation' === $action ) {
			NGTAI_Result_Repository::mark_applied( $result_id );
			self::set_result_status( $result_id, 'applied' );
			do_action( 'ngtai_agent_result_applied', $context['subject_id'], $payload );
			NGTAI_Audit::log( 'ngtai_result_applied', [ 'result_id' => $result_id, 'subject_id' => $context['subject_id'] ], $correlation );
		}
		NGTAI_Audit::log( 'ngtai_callback_received', [ 'outcome' => 'accepted', 'action_name' => $action ], $correlation );
		return [ 'success' => true, 'result_id' => $result_id, 'status' => 'applied' ];
	}

	/**
	 * Set bridge workflow status after repository insertion.
	 *
	 * @param int    $result_id Result ID.
	 * @param string $status    Workflow status.
	 * @return void
	 */
	private static function set_result_status( $result_id, $status ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			NGTAI_Database::table( 'agent_results' ),
			[ 'status' => sanitize_key( $status ), 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => (int) $result_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Persist an approval request idempotently.
	 *
	 * @param array<string,mixed>  $body    Body.
	 * @param array<string,string> $headers Headers.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function handle_approval_request( array $body, array $headers ) {
		$result = self::store_approval( $body );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$correlation = (string) ( $body['correlation_id'] ?? $headers['x-ngt-correlation-id'] ?? '' );
		NGTAI_Audit::log( 'ngtai_approval_requested', [ 'approval_id' => $result['approval_id'] ], $correlation );
		return $result;
	}

	private static function store_approval( array $body ) {
		global $wpdb;
		$table       = NGTAI_Database::table( 'approvals' );
		$approval_id = sanitize_text_field( (string) ( $body['approval_id'] ?? NGTAI_Signature::uuid() ) );
		$now         = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			[
				'approval_id' => $approval_id,
				'agent_run_id'=> sanitize_text_field( (string) ( $body['agent_run_id'] ?? '' ) ),
				'action_name' => self::sanitize_action_name( (string) ( $body['action_name'] ?? '' ) ),
				'status'      => 'pending',
				'requested_by'=> sanitize_text_field( (string) ( $body['requested_by'] ?? $body['agent_name'] ?? 'agents-api' ) ),
				'payload_json'=> wp_json_encode( NGTAI_Logger::scrub( $body['payload'] ?? $body ) ),
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
		if ( false === $inserted ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE approval_id=%s", $approval_id ) );
			return $exists ? [ 'success' => true, 'idempotent' => true, 'approval_id' => $approval_id ] : new WP_Error( 'ngtai_approval_storage_failed', __( 'Unable to store approval.', 'nextgentutors-ai-integration' ), [ 'status' => 500 ] );
		}
		return [ 'success' => true, 'approval_id' => $approval_id ];
	}

	/**
	 * Human approval decision.
	 *
	 * @param string $approval_id Approval ID.
	 * @param bool   $approve     Whether approved.
	 * @param string $reason      Required reason.
	 * @param int    $user_id     Human user ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function decide_approval( $approval_id, $approve, $reason, $user_id ) {
		if ( ! NGTAI_Access::can_approve() || $user_id <= 0 ) {
			return new WP_Error( 'ngtai_forbidden', __( 'A human approver is required.', 'nextgentutors-ai-integration' ), [ 'status' => 403 ] );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'ngtai_reason_required', __( 'A decision reason is required.', 'nextgentutors-ai-integration' ), [ 'status' => 422 ] );
		}
		global $wpdb;
		$table = NGTAI_Database::table( 'approvals' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE approval_id=%s", sanitize_text_field( $approval_id ) ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ngtai_approval_not_found', __( 'Approval not found.', 'nextgentutors-ai-integration' ), [ 'status' => 404 ] );
		}
		if ( (string) $user_id === (string) $row['requested_by'] ) {
			return new WP_Error( 'ngtai_self_approval', __( 'Requesters cannot approve their own action.', 'nextgentutors-ai-integration' ), [ 'status' => 403 ] );
		}
		$payload = json_decode( (string) $row['payload_json'], true );
		$payload = is_array( $payload ) ? $payload : [];
		$policy  = NGTAI_Policy_Gate::evaluate( (string) $row['action_name'], $payload );
		if ( $approve && in_array( strtoupper( (string) ( $policy['decision'] ?? 'DENY' ) ), [ 'DENY', 'ESCALATE' ], true ) ) {
			return new WP_Error( 'ngtai_policy_denied', __( 'Current policy no longer permits approval.', 'nextgentutors-ai-integration' ), [ 'status' => 403 ] );
		}
		$status = $approve ? 'approved' : 'denied';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->update(
			$table,
			[ 'status' => $status, 'decided_by' => $user_id, 'decision_reason' => $reason, 'decided_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => (int) $row['id'], 'status' => 'pending' ],
			[ '%s', '%d', '%s', '%s', '%s' ],
			[ '%d', '%s' ]
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'ngtai_approval_conflict', __( 'Approval was already decided.', 'nextgentutors-ai-integration' ), [ 'status' => 409 ] );
		}
		if ( $approve && ! empty( $payload['result_id'] ) ) {
			NGTAI_Result_Repository::mark_applied( (int) $payload['result_id'] );
			self::set_result_status( (int) $payload['result_id'], 'applied' );
			do_action( 'ngtai_agent_result_applied', (string) ( $payload['subject_id'] ?? '' ), $payload['result'] ?? [] );
			NGTAI_Audit::log( 'ngtai_result_applied', [ 'result_id' => (int) $payload['result_id'] ] );
		}
		if ( method_exists( 'NGTAI_Api_Client', 'post_task' ) ) {
			$response = NGTAI_Api_Client::post_task(
				[
					'task_id'      => 'approval:' . $approval_id,
					'action_name'  => 'approval.decision',
					'approval_id'  => $approval_id,
					'decision'     => $status,
					'reason'       => $reason,
				]
			);
			if ( empty( $response['ok'] ) ) {
				NGTAI_Logger::log( 'warning', 'callback_controller', 'approval_decision_callback', [ 'outcome' => 'failed', 'http_status' => $response['status'] ?? 0, 'error_code' => $response['error'] ?? '' ] );
			}
		}
		NGTAI_Audit::log( $approve ? 'ngtai_approval_approved' : 'ngtai_approval_denied', [ 'approval_id' => $approval_id, 'reason' => $reason ] );
		return [ 'success' => true, 'status' => $status ];
	}

	/**
	 * Preserve dotted action identifiers used by agents-api contracts.
	 *
	 * @param string $value Action name.
	 * @return string
	 */
	private static function sanitize_action_name( $value ) {
		$value = strtolower( sanitize_text_field( (string) $value ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $value );
	}
}
