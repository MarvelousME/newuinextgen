<?php
/**
 * Companion event → versioned envelope mapping with redaction and allowlists.
 */

$envelope = NGTAI_Event_Mapper::map(
	[
		'event_type'     => 'MatchRequested',
		'entity_type'    => 'match',
		'entity_id'      => 'MATCH-500',
		'correlation_id' => 'map-corr-0001',
		'timestamp'      => '2026-07-21T08:00:00Z',
		'payload'        => [
			'match_id'   => 'MATCH-500',
			'learner_id' => 'raw-learner-1',
			'subjects'   => [ 'maths' ],
			'grade'      => 9,
			'sa_id'      => '9901015800088',
			'internal'   => 'drop-me',
			'candidates' => [
				[ 'tutor_id' => 11, 'display_name' => 'T', 'verified' => true, 'eligible' => true, 'score' => 0.9 ],
				[ 'tutor_id' => 12, 'verified' => false, 'eligible' => true ],
			],
		],
	]
);

ngtai_assert( 'MatchRequested maps to match.requested', $envelope instanceof NGTAI_Event_Envelope && 'match.requested' === $envelope->get( 'event_type' ) );
ngtai_assert( 'schema version is 1', 1 === $envelope->get( 'schema_version' ) );
ngtai_assert( 'tenant id populated', 'nextgentutors' === $envelope->get( 'tenant_id' ) );
ngtai_assert( 'source identifies companion', 'nextgentutors-companion' === $envelope->get( 'source' ) );
ngtai_assert( 'subject id carried over', 'MATCH-500' === $envelope->get( 'subject_id' ) );
ngtai_assert( 'correlation id carried over', 'map-corr-0001' === $envelope->get( 'correlation_id' ) );
ngtai_assert( 'classification from schema', 'restricted' === $envelope->get( 'data_classification' ) );
ngtai_assert( 'event id generated and valid', 1 === preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', (string) $envelope->get( 'event_id' ) ) );

$payload = $envelope->get( 'payload' );
ngtai_assert( 'allowlisted payload fields kept', isset( $payload['match_id'], $payload['subjects'], $payload['grade'] ) );
ngtai_assert( 'non-allowlisted field dropped', ! array_key_exists( 'internal', $payload ) );
ngtai_assert( 'never-send minor field dropped', ! array_key_exists( 'sa_id', $payload ) );
ngtai_assert( 'learner id pseudonymized by minor profile', 0 === strpos( (string) ( $payload['learner_id'] ?? '' ), 'learner_' ) );
ngtai_assert( 'unverified candidate filtered out', 1 === count( $payload['candidates'] ) && 11 === $payload['candidates'][0]['tutor_id'] );

$booking = NGTAI_Event_Mapper::map(
	[
		'event_type'  => 'BookingCreated',
		'entity_type' => 'booking',
		'entity_id'   => 'BOOK-9',
		'payload'     => [ 'booking_id' => 'BOOK-9', 'tutor_id' => 3, 'starts_at' => '2026-08-01T10:00:00Z', 'status' => 'created' ],
	]
);
ngtai_assert( 'BookingCreated maps to booking.created', $booking instanceof NGTAI_Event_Envelope && 'booking.created' === $booking->get( 'event_type' ) );
ngtai_assert( 'generated correlation id when absent', '' !== (string) $booking->get( 'correlation_id' ) );

ngtai_assert( 'contract-native type passes through', 'match.accepted' === NGTAI_Event_Mapper::to_contract_type( 'match.accepted' ) );
ngtai_assert( 'unsupported type is null', null === NGTAI_Event_Mapper::to_contract_type( 'TotallyUnknownEvent' ) );
ngtai_assert( 'unsupported source event maps to null', null === NGTAI_Event_Mapper::map( [ 'event_type' => 'TotallyUnknownEvent', 'payload' => [] ] ) );
ngtai_assert( 'is_supported true for alias', NGTAI_Event_Mapper::is_supported( 'PaymentSettled' ) );
ngtai_assert( 'is_supported false for junk', ! NGTAI_Event_Mapper::is_supported( 'NotAnEvent' ) );

$invalid = NGTAI_Event_Mapper::map( [ 'event_type' => 'MatchRequested', 'payload' => [] ] );
ngtai_assert( 'envelope validation failure yields null', null === $invalid );
