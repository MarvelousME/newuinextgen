<?php
/**
 * Asynchronous outbox bridge.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps hooks to durable deliveries and flushes them out of band.
 */
final class NGTAI_Outbox_Bridge {

	/** @return void */
	public static function init() {
		add_action( 'ngc_domain_event', [ __CLASS__, 'on_domain_event' ], 20, 1 );
		add_action( 'ngc_match_requested', [ __CLASS__, 'on_match_requested' ], 20, 2 );
		add_action( 'ngtai_outbox_flush', [ __CLASS__, 'dispatch_batch' ] );
	}

	/** @param array<string,mixed> $companion_envelope Companion envelope. @return void */
	public static function on_domain_event( $companion_envelope ) {
		if ( ! is_array( $companion_envelope ) || ! NGTAI_Config::enabled() ) {
			return;
		}
		$event = NGTAI_Event_Mapper::map( $companion_envelope );
		if ( ! $event ) {
			return;
		}
		$policy = NGTAI_Policy_Gate::evaluate(
			(string) $event->get( 'event_type' ),
			[ 'action' => 'agent.recommend', 'event_type' => $event->get( 'event_type' ), 'correlation_id' => $event->get( 'correlation_id' ) ]
		);
		if ( 'DENY' === $policy['decision'] ) {
			self::audit( 'ngtai_policy_denied', $event, [ 'reason' => $policy['reason'] ] );
			return;
		}
		if ( 'REQUIRE_APPROVAL' === $policy['decision'] && 'match.requested' !== $event->get( 'event_type' ) ) {
			self::create_approval( $event, $policy );
			return;
		}
		NGTAI_Delivery_Repository::insert_pending( $event );
	}

	/** @param int|string $match_id Match ID. @param array<string,mixed> $context Context. @return void */
	public static function on_match_requested( $match_id, $context = [] ) {
		$context     = is_array( $context ) ? $context : [];
		$correlation = (string) ( $context['correlation_id'] ?? 'match-' . $match_id );
		$subject     = (string) $match_id;
		self::on_domain_event(
			[
				'event_id'       => 'match-req-' . $match_id . '-' . md5( $subject . $correlation ),
				'event_type'     => 'MatchRequested',
				'entity_type'    => 'match',
				'entity_id'      => $subject,
				'correlation_id' => $correlation,
				'causation_id'   => (string) ( $context['causation_id'] ?? '' ),
				'consent_context'=> $context['consent_context'] ?? null,
				'payload'        => $context,
				'timestamp'      => (string) ( $context['timestamp'] ?? gmdate( 'c' ) ),
			]
		);
	}

	/** @param int $limit Limit. @return array{processed:int,delivered:int,retried:int,dead:int,failed:int} */
	public static function dispatch_batch( $limit = 10 ) {
		$stats = [ 'processed' => 0, 'delivered' => 0, 'retried' => 0, 'dead' => 0, 'failed' => 0 ];
		foreach ( NGTAI_Delivery_Repository::claim_due( $limit ) as $row ) {
			++$stats['processed'];
			$data = json_decode( (string) $row['payload_json'], true );
			try {
				$event = new NGTAI_Event_Envelope( is_array( $data ) ? $data : [] );
			} catch ( InvalidArgumentException $error ) {
				NGTAI_Delivery_Repository::mark_failed( (int) $row['id'], 'invalid_envelope', 0 );
				++$stats['failed'];
				self::audit_row( 'ngtai_event_failed', $row, [ 'error' => 'invalid_envelope' ] );
				continue;
			}
			$result = NGTAI_Api_Client::post_event( $event, 'evt:' . $row['event_id'], (string) $row['correlation_id'] );
			$status = (int) ( $result['status'] ?? 0 );
			$error  = (string) ( $result['error'] ?? ( 'http_' . $status ) );
			if ( ! empty( $result['ok'] ) ) {
				$response_json = wp_json_encode( $result['body'] );
				NGTAI_Delivery_Repository::mark_delivered( (int) $row['id'], $status, hash( 'sha256', false === $response_json ? '' : $response_json ) );
				++$stats['delivered'];
				self::audit_row( 'ngtai_event_delivered', $row, [ 'http_status' => $status ] );
			} elseif ( ! empty( $result['retryable'] ) ) {
				$attempt = (int) $row['attempt_count'] + 1;
				NGTAI_Delivery_Repository::schedule_retry( (int) $row['id'], $attempt, $error, $status, $result['retry_after'] ?? null );
				if ( $attempt >= 5 ) {
					++$stats['dead'];
					self::audit_row( 'ngtai_event_dead_lettered', $row, [ 'error' => $error, 'http_status' => $status ] );
				} else {
					++$stats['retried'];
					self::audit_row( 'ngtai_event_failed', $row, [ 'error' => $error, 'retrying' => true ] );
				}
			} else {
				NGTAI_Delivery_Repository::mark_failed( (int) $row['id'], $error, $status );
				++$stats['failed'];
				self::audit_row( 'ngtai_event_failed', $row, [ 'error' => $error, 'http_status' => $status ] );
			}
		}
		return $stats;
	}

	/** @param NGTAI_Event_Envelope $event Event. @param array<string,mixed> $policy Policy. @return void */
	private static function create_approval( NGTAI_Event_Envelope $event, array $policy ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return;
		}
		$payload = NGTAI_Redactor::redact( $event->to_array(), NGTAI_Redactor::last_profile() );
		$now     = gmdate( 'Y-m-d H:i:s' );
		$wpdb->insert(
			NGTAI_Database::table( 'approvals' ),
			[
				'approval_id'   => NGTAI_Signature::uuid(),
				'event_id'      => $event->get( 'event_id' ),
				'action_name'   => 'agent.recommend',
				'status'        => 'pending',
				'risk'          => 'high',
				'payload_json'  => wp_json_encode( $payload ),
				'correlation_id'=> $event->get( 'correlation_id' ),
				'created_at'    => $now,
				'updated_at'    => $now,
			]
		);
		self::audit( 'ngtai_approval_requested', $event, [ 'reason' => $policy['reason'] ] );
	}

	/** @param string $action Action. @param NGTAI_Event_Envelope $event Event. @param array<string,mixed> $detail Detail. @return void */
	private static function audit( $action, NGTAI_Event_Envelope $event, array $detail ) {
		self::audit_row( $action, $event->to_array(), $detail );
	}

	/** @param string $action Action. @param array<string,mixed> $row Row. @param array<string,mixed> $detail Detail. @return void */
	private static function audit_row( $action, array $row, array $detail ) {
		if ( class_exists( 'NGTAI_Audit' ) ) {
			NGTAI_Audit::log( $action, array_merge( [ 'event_id' => (string) ( $row['event_id'] ?? '' ), 'event_type' => (string) ( $row['event_type'] ?? '' ) ], $detail ), (string) ( $row['correlation_id'] ?? '' ) );
		}
	}
}
