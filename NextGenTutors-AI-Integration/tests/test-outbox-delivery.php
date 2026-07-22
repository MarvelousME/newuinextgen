<?php
/**
 * Outbox delivery lifecycle: insert, claim, deliver, retry, dead-letter,
 * lock recovery, and status counts — with HTTP mocked via pre_http_request.
 */

global $wpdb, $ngtai_test_transients;

$ngtai_test_transients = [];

$GLOBALS['ngtai_mock_http'] = [ 'code' => 200, 'body' => '{"accepted":true}' ];
add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		unset( $pre, $args, $url );
		return [
			'response' => [ 'code' => (int) $GLOBALS['ngtai_mock_http']['code'] ],
			'body'     => (string) $GLOBALS['ngtai_mock_http']['body'],
			'headers'  => [],
		];
	},
	10,
	3
);

$make_event = static function ( string $event_id ): NGTAI_Event_Envelope {
	return new NGTAI_Event_Envelope(
		[
			'event_id'            => $event_id,
			'event_type'          => 'match.proposed',
			'schema_version'      => 1,
			'occurred_at'         => gmdate( 'c' ),
			'tenant_id'           => 'nextgentutors',
			'source'              => 'nextgentutors-companion',
			'subject_type'        => 'match',
			'subject_id'          => 'MATCH-800',
			'correlation_id'      => 'outbox-corr-0001',
			'data_classification' => 'confidential',
			'payload'             => [ 'match_id' => 'MATCH-800', 'tutor_id' => 4, 'score' => 0.7, 'status' => 'proposed' ],
		]
	);
};

// Insert + duplicate suppression on unique event_id.
$id_a = NGTAI_Delivery_Repository::insert_pending( $make_event( 'evt-outbox-000A' ) );
ngtai_assert( 'pending delivery inserted', is_int( $id_a ) && $id_a > 0 );
ngtai_assert( 'duplicate event_id suppressed', 'duplicate' === NGTAI_Delivery_Repository::insert_pending( $make_event( 'evt-outbox-000A' ) ) );
ngtai_assert( 'inserted row is pending', 'pending' === ( NGTAI_Delivery_Repository::get( $id_a )['status'] ?? '' ) );

// Successful dispatch.
$stats = NGTAI_Outbox_Bridge::dispatch_batch( 10 );
ngtai_assert( 'dispatch processed one row', 1 === $stats['processed'] );
ngtai_assert( 'dispatch delivered one row', 1 === $stats['delivered'] );
$row_a = NGTAI_Delivery_Repository::get( $id_a );
ngtai_assert( 'delivered status persisted', 'delivered' === $row_a['status'] );
ngtai_assert( 'delivered http status persisted', 200 === (int) $row_a['http_status'] );
ngtai_assert( 'delivered row unlocked', null === $row_a['locked_at'] );

// Retryable failure schedules a retry, then succeeds when forced due.
$id_b = NGTAI_Delivery_Repository::insert_pending( $make_event( 'evt-outbox-000B' ) );
$GLOBALS['ngtai_mock_http'] = [ 'code' => 503, 'body' => '{"error":"unavailable"}' ];
$stats = NGTAI_Outbox_Bridge::dispatch_batch( 10 );
ngtai_assert( 'retryable failure counted as retried', 1 === $stats['retried'] && 0 === $stats['delivered'] );
$row_b = NGTAI_Delivery_Repository::get( $id_b );
ngtai_assert( 'row scheduled for retry', 'retry_pending' === $row_b['status'] && 1 === (int) $row_b['attempt_count'] );

$GLOBALS['ngtai_mock_http'] = [ 'code' => 200, 'body' => '{"accepted":true}' ];
$wpdb->force_delivery_due( (int) $id_b );
$stats = NGTAI_Outbox_Bridge::dispatch_batch( 10 );
ngtai_assert( 'retry attempt delivered', 1 === $stats['delivered'] );
ngtai_assert( 'retried row delivered', 'delivered' === NGTAI_Delivery_Repository::get( $id_b )['status'] );

// Non-retryable failure fails permanently.
$id_c = NGTAI_Delivery_Repository::insert_pending( $make_event( 'evt-outbox-000C' ) );
$GLOBALS['ngtai_mock_http'] = [ 'code' => 400, 'body' => '{"error":"bad_request"}' ];
$stats = NGTAI_Outbox_Bridge::dispatch_batch( 10 );
ngtai_assert( 'non-retryable failure counted as failed', 1 === $stats['failed'] );
ngtai_assert( 'row marked failed', 'failed' === NGTAI_Delivery_Repository::get( $id_c )['status'] );

// Exhausted attempts dead-letter.
$id_d = NGTAI_Delivery_Repository::insert_pending( $make_event( 'evt-outbox-000D' ) );
ngtai_assert( 'fifth attempt dead-letters', true === NGTAI_Delivery_Repository::schedule_retry( (int) $id_d, 5, 'still failing', 503 ) );
ngtai_assert( 'row marked dead_letter', 'dead_letter' === NGTAI_Delivery_Repository::get( $id_d )['status'] );

// Lock recovery for stuck processing rows.
$id_e = NGTAI_Delivery_Repository::insert_pending( $make_event( 'evt-outbox-000E' ) );
$claimed = NGTAI_Delivery_Repository::claim_due( 10 );
ngtai_assert( 'claim_due locks the pending row', 1 === count( $claimed ) && 'processing' === $claimed[0]['status'] );
ngtai_assert( 'recover_locks ignores fresh locks', 0 === NGTAI_Delivery_Repository::recover_locks( 300 ) );
$wpdb->age_delivery_lock( (int) $id_e, 600 );
ngtai_assert( 'recover_locks reclaims stale lock', 1 === NGTAI_Delivery_Repository::recover_locks( 300 ) );
ngtai_assert( 'recovered row back to retry_pending', 'retry_pending' === NGTAI_Delivery_Repository::get( $id_e )['status'] );

// Status counts.
$counts = NGTAI_Delivery_Repository::counts();
ngtai_assert( 'counts: delivered', 2 === $counts['delivered'] );
ngtai_assert( 'counts: failed', 1 === $counts['failed'] );
ngtai_assert( 'counts: dead_letter', 1 === $counts['dead_letter'] );
ngtai_assert( 'counts: retry_pending', 1 === $counts['retry_pending'] );
ngtai_assert( 'counts: pending drained', 0 === $counts['pending'] );

// Full bridge path from a Companion hook payload.
NGTAI_Outbox_Bridge::on_match_requested(
	'MATCH-900',
	[
		'match_id'   => 'MATCH-900',
		'learner_id' => 'raw-learner-9',
		'subjects'   => [ 'science' ],
		'grade'      => 10,
		'candidates' => [ [ 'tutor_id' => 21, 'verified' => true, 'eligible' => true, 'score' => 0.88 ] ],
	]
);
$bridge_row = null;
foreach ( NGTAI_Delivery_Repository::list_recent( [ 'limit' => 5 ] ) as $candidate_row ) {
	if ( 'match.requested' === $candidate_row['event_type'] ) {
		$bridge_row = $candidate_row;
		break;
	}
}
ngtai_assert( 'bridge hook enqueued match.requested delivery', null !== $bridge_row && 'pending' === $bridge_row['status'] );
$bridge_payload = json_decode( (string) $bridge_row['payload_json'], true );
ngtai_assert( 'bridge payload learner id pseudonymized', 0 === strpos( (string) ( $bridge_payload['payload']['learner_id'] ?? '' ), 'learner_' ) );

remove_all_filters( 'pre_http_request' );
unset( $GLOBALS['ngtai_mock_http'] );
