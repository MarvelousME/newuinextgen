<?php
/**
 * Companion event mapper.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps Companion event names into versioned integration envelopes.
 */
final class NGTAI_Event_Mapper {

	/** @var array<string,string> */
	private static $map = [
		'MatchRequested'            => 'match.requested',
		'FindTutorSubmitted'        => 'match.requested',
		'MatchCreated'              => 'match.proposed',
		'MatchAccepted'             => 'match.accepted',
		'BookingCreated'            => 'booking.created',
		'PaymentSettled'            => 'payment.succeeded',
		'LessonCompleted'           => 'lesson.completed',
		'ReviewSubmitted'           => 'review.submitted',
		'UserRegistered'            => 'parent.registered',
		'TutorApplicationSubmitted' => 'tutor.application.submitted',
		'FraudSignalRaised'         => 'fraud.signal.raised',
		'SafeguardingAlertRaised'   => 'safeguarding.alert.raised',
		'SecurityAlertRaised'       => 'security.alert.raised',
		'ConsentRecorded'           => 'consent.recorded',
	];

	/**
	 * @param array<string,mixed> $src Source envelope.
	 * @return NGTAI_Event_Envelope|null
	 */
	public static function map( array $src ) {
		$type = self::to_contract_type( (string) ( $src['event_type'] ?? $src['type'] ?? '' ) );
		if ( null === $type ) {
			self::log_failure( 'unsupported_event_type', (string) ( $src['event_type'] ?? '' ) );
			return null;
		}

		$schemas = self::schemas();
		$schema  = $schemas['events'][ $type ];
		$payload = isset( $src['payload'] ) && is_array( $src['payload'] ) ? $src['payload'] : [];
		$payload = NGTAI_Redactor::redact( $payload, (string) $schema['redaction_profile'] );
		$payload = NGTAI_Redactor::apply_allowlist( $type, $payload );

		$occurred_at   = (string) ( $src['occurred_at'] ?? $src['timestamp'] ?? gmdate( 'c' ) );
		$subject_id    = (string) ( $src['subject_id'] ?? $src['entity_id'] ?? $payload['id'] ?? '' );
		$subject_type  = (string) ( $src['subject_type'] ?? $src['entity_type'] ?? 'unknown' );
		$correlation   = (string) ( $src['correlation_id'] ?? self::uuid() );
		$event_id_seed = $type . '|' . $subject_id . '|' . $occurred_at . '|' . $correlation;
		$event_id      = (string) ( $src['event_id'] ?? ( 'evt-' . sha1( $event_id_seed ) ) );

		$data = [
			'event_id'            => substr( $event_id, 0, 191 ),
			'event_type'          => $type,
			'schema_version'      => (int) $schema['schema_version'],
			'occurred_at'         => $occurred_at,
			'tenant_id'           => (string) NGTAI_Config::tenant(),
			'source'              => 'nextgentutors-companion',
			'subject_type'        => $subject_type,
			'subject_id'          => $subject_id,
			'correlation_id'      => $correlation,
			'causation_id'        => (string) ( $src['causation_id'] ?? '' ),
			'data_classification' => (string) $schema['data_classification'],
			'consent_context'     => isset( $src['consent_context'] ) && is_array( $src['consent_context'] ) ? $src['consent_context'] : null,
			'payload'             => $payload,
		];

		try {
			return new NGTAI_Event_Envelope( $data );
		} catch ( InvalidArgumentException $error ) {
			self::log_failure( $error->getMessage(), $type );
			return null;
		}
	}

	/** @param array<string,mixed> $source Source. @return NGTAI_Event_Envelope|null */
	public static function build_envelope( array $source ) {
		return self::map( $source );
	}

	/** @param string $type Source type. @return string|null */
	public static function to_contract_type( $type ) {
		$type = trim( $type );
		if ( isset( self::$map[ $type ] ) ) {
			return self::$map[ $type ];
		}
		$events = self::schemas()['events'];
		return isset( $events[ $type ] ) ? $type : null;
	}

	/** @param string $type Type. @return bool */
	public static function is_supported( $type ) {
		return null !== self::to_contract_type( $type );
	}

	/** @return array<string,mixed> */
	private static function schemas() {
		if ( method_exists( 'NGTAI_Config', 'event_schemas' ) ) {
			return (array) NGTAI_Config::event_schemas();
		}
		$path = dirname( __DIR__ ) . '/config/event-schemas.php';
		return (array) include $path;
	}

	/** @return string */
	private static function uuid() {
		return class_exists( 'NGTAI_Signature' ) ? NGTAI_Signature::uuid() : 'corr-' . sha1( uniqid( '', true ) );
	}

	/** @param string $reason Reason. @param string $type Type. @return void */
	private static function log_failure( $reason, $type ) {
		if ( class_exists( 'NGTAI_Logger' ) ) {
			NGTAI_Logger::log( 'warning', 'event_mapper', 'map_failed', [ 'event_type' => $type, 'reason' => $reason ] );
		}
	}
}
