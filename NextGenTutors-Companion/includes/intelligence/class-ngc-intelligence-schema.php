<?php
/**
 * Unified operational event schema (NextGen Intelligence Platform).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates and normalizes intelligence events.
 */
final class NGC_Intelligence_Schema {

	public const SEVERITIES = [ 'debug', 'info', 'notice', 'warning', 'error', 'critical' ];
	public const OUTCOMES   = [ 'success', 'failure', 'partial', 'unknown' ];

	/**
	 * @param array<string, mixed> $raw Raw event.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function normalize( array $raw ) {
		$event_key = sanitize_key( str_replace( '.', '_', (string) ( $raw['event_key'] ?? $raw['type'] ?? 'unknown' ) ) );
		if ( '' === $event_key ) {
			return new WP_Error( 'ngc_intel_invalid', 'event_key is required' );
		}

		$severity = sanitize_key( (string) ( $raw['severity'] ?? 'info' ) );
		if ( ! in_array( $severity, self::SEVERITIES, true ) ) {
			$severity = 'info';
		}

		$outcome = sanitize_key( (string) ( $raw['outcome'] ?? 'unknown' ) );
		if ( ! in_array( $outcome, self::OUTCOMES, true ) ) {
			$outcome = 'unknown';
		}

		$correlation = sanitize_text_field( (string) ( $raw['correlation_id'] ?? '' ) );
		if ( '' === $correlation && class_exists( 'NGC_Uuid' ) ) {
			$correlation = NGC_Uuid::generate();
		} elseif ( '' === $correlation ) {
			$correlation = wp_generate_uuid4();
		}

		$duration = isset( $raw['duration_ms'] ) ? max( 0, (int) $raw['duration_ms'] ) : null;

		return [
			'uuid'           => sanitize_text_field( (string) ( $raw['uuid'] ?? wp_generate_uuid4() ) ),
			'event_key'      => $event_key,
			'plugin_slug'    => sanitize_key( (string) ( $raw['plugin_slug'] ?? $raw['plugin'] ?? 'unknown' ) ),
			'module'         => sanitize_key( (string) ( $raw['module'] ?? 'core' ) ),
			'feature'        => sanitize_key( (string) ( $raw['feature'] ?? '' ) ),
			'domain'         => sanitize_key( (string) ( $raw['domain'] ?? self::infer_domain( $event_key ) ) ),
			'severity'       => $severity,
			'outcome'        => $outcome,
			'user_id'        => max( 0, (int) ( $raw['user_id'] ?? get_current_user_id() ) ),
			'correlation_id' => $correlation,
			'request_id'     => sanitize_text_field( (string) ( $raw['request_id'] ?? self::request_id() ) ),
			'duration_ms'    => $duration,
			'message'        => sanitize_text_field( (string) ( $raw['message'] ?? '' ) ),
			'payload'        => is_array( $raw['payload'] ?? null ) ? $raw['payload'] : [],
			'context'        => is_array( $raw['context'] ?? null ) ? $raw['context'] : [],
			'source'         => sanitize_key( (string) ( $raw['source'] ?? 'sdk' ) ),
			'recorded_at'    => gmdate( 'Y-m-d H:i:s' ),
		];
	}

	/**
	 * @param string $event_key Event key.
	 * @return string
	 */
	private static function infer_domain( $event_key ) {
		$prefix = strtok( $event_key, '.' );
		$map    = [
			'auth'       => 'authentication',
			'user'       => 'users',
			'booking'    => 'bookings',
			'payment'    => 'payments',
			'workflow'   => 'workflows',
			'api'        => 'apis',
			'ai'         => 'ai',
			'plugin'     => 'platform',
			'security'   => 'security',
			'cron'       => 'background',
			'queue'      => 'background',
			'email'      => 'notifications',
			'notification' => 'notifications',
		];
		return $map[ $prefix ] ?? 'general';
	}

	/**
	 * @return string
	 */
	private static function request_id() {
		if ( ! empty( $_SERVER['HTTP_X_REQUEST_ID'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUEST_ID'] ) ); // phpcs:ignore
		}
		static $rid = null;
		if ( null === $rid ) {
			$rid = substr( wp_generate_uuid4(), 0, 12 );
		}
		return $rid;
	}
}
