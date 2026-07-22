<?php
/**
 * Payload redaction and minimization.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes secrets and minimizes personal data before external delivery.
 */
final class NGTAI_Redactor {

	/** @var string */
	private static $last_profile = 'default';

	/**
	 * @param mixed  $value Value.
	 * @param string $profile Redaction profile.
	 * @return mixed
	 */
	public static function redact( $value, $profile = 'default' ) {
		$config  = self::config();
		$profile = isset( $config['profiles'][ $profile ] ) ? $profile : 'default';
		self::$last_profile = $profile;
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$rules = $config['profiles'][ $profile ];
		$out   = [];
		foreach ( $value as $key => $item ) {
			$normalized = strtolower( (string) $key );
			if ( self::matches( $normalized, (array) $rules['blocked_key_patterns'] ) ) {
				$out[ $key ] = '[REDACTED]';
			} elseif ( in_array( $normalized, (array) $rules['minimized_keys'], true ) && is_scalar( $item ) ) {
				$out[ $key ] = self::minimize_string( (string) $item );
			} elseif ( 'minor' === $profile && in_array( $normalized, (array) ( $rules['learner_identifier_keys'] ?? [] ), true ) && is_scalar( $item ) ) {
				$out[ $key ] = self::learner_hash( (string) $item );
			} else {
				$out[ $key ] = self::redact( $item, $profile );
			}
		}
		return 'minor' === $profile ? self::minimize_minor( $out ) : $out;
	}

	/**
	 * Apply the event payload allowlist.
	 *
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>
	 */
	public static function apply_allowlist( $event_type, array $payload ) {
		$schemas = self::schemas();
		$allowed = (array) ( $schemas['events'][ $event_type ]['allowed_payload_fields'] ?? [] );
		$result  = [];
		foreach ( $payload as $key => $value ) {
			if ( in_array( $key, $allowed, true ) ) {
				$result[ $key ] = $value;
			} elseif ( class_exists( 'NGTAI_Logger' ) ) {
				NGTAI_Logger::log( 'debug', 'redactor', 'payload_field_dropped', [ 'event_type' => $event_type, 'field' => (string) $key ] );
			}
		}

		if ( 'match.requested' === $event_type ) {
			$result = self::filter_candidates( $result );
		}
		return $result;
	}

	/**
	 * Remove prohibited minor fields and hash learner identifiers.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>
	 */
	public static function minimize_minor( array $payload ) {
		$rules = self::config()['profiles']['minor'];
		$out   = [];
		foreach ( $payload as $key => $value ) {
			$normalized = strtolower( (string) $key );
			if ( self::matches( $normalized, (array) $rules['never_send'] ) ) {
				continue;
			}
			if ( in_array( $normalized, (array) $rules['learner_identifier_keys'], true ) && is_scalar( $value ) ) {
				$out[ $key ] = self::learner_hash( (string) $value );
			} elseif ( is_array( $value ) ) {
				$out[ $key ] = self::minimize_minor( $value );
			} else {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	/** @return string */
	public static function last_profile() {
		return self::$last_profile;
	}

	/**
	 * Compatibility helper.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>
	 */
	public static function minimize_match_requested( array $payload ) {
		return self::filter_candidates( self::minimize_minor( self::redact( $payload, 'minor' ) ) );
	}

	/** @param array<string,mixed> $payload Payload. @return array<string,mixed> */
	private static function filter_candidates( array $payload ) {
		$allowed = self::config()['event_allowlists']['match.requested']['candidates'];
		$rows    = [];
		foreach ( (array) ( $payload['candidates'] ?? [] ) as $candidate ) {
			if ( ! is_array( $candidate ) || ! self::candidate_is_eligible( $candidate ) ) {
				continue;
			}
			$row = array_intersect_key( $candidate, array_flip( $allowed ) );
			$row['verified'] = true;
			$row['eligible'] = true;
			$rows[]          = $row;
		}
		if ( array_key_exists( 'candidates', $payload ) ) {
			$payload['candidates'] = $rows;
		}
		return $payload;
	}

	/** @param array<string,mixed> $candidate Candidate. @return bool */
	private static function candidate_is_eligible( array $candidate ) {
		if ( empty( $candidate['verified'] ) || empty( $candidate['eligible'] ) ) {
			return false;
		}
		if ( ! function_exists( 'get_user_by' ) || ! function_exists( 'get_user_meta' ) ) {
			return true;
		}
		$id   = (int) ( $candidate['user_id'] ?? $candidate['tutor_id'] ?? 0 );
		$user = $id > 0 ? get_user_by( 'id', $id ) : false;
		if ( ! $user || ! in_array( 'tutor', (array) $user->roles, true ) ) {
			return false;
		}
		$verified  = (bool) get_user_meta( $id, 'ngc_tutor_verified', true ) || (bool) get_user_meta( $id, 'ngt_tutor_verified', true );
		$suspended = (bool) get_user_meta( $id, 'ngc_tutor_suspended', true ) || (bool) get_user_meta( $id, 'ngt_tutor_suspended', true );
		$suspended = $suspended || 'suspended' === get_user_meta( $id, 'ngc_tutor_status', true ) || 'suspended' === get_user_meta( $id, 'ngt_tutor_status', true );
		return $verified && ! $suspended;
	}

	/** @param string $key Key. @param string[] $patterns Patterns. @return bool */
	private static function matches( $key, array $patterns ) {
		foreach ( $patterns as $pattern ) {
			if ( false !== strpos( $key, $pattern ) ) {
				return true;
			}
		}
		return false;
	}

	/** @param string $value Value. @return string */
	private static function minimize_string( $value ) {
		$value = trim( $value );
		if ( false !== strpos( $value, '@' ) ) {
			list( $local, $domain ) = array_pad( explode( '@', $value, 2 ), 2, '' );
			return ( '' !== $local ? substr( $local, 0, 1 ) : '*' ) . '***@' . $domain;
		}
		return strlen( $value ) > 1 ? substr( $value, 0, 1 ) . '***' . substr( $value, -1 ) : '*';
	}

	/** @param string $value Identifier. @return string */
	private static function learner_hash( $value ) {
		return 0 === strpos( $value, 'learner_' ) ? $value : 'learner_' . substr( hash( 'sha256', $value ), 0, 8 );
	}

	/** @return array<string,mixed> */
	private static function config() {
		$path = dirname( __DIR__ ) . '/config/payload-allowlists.php';
		return is_readable( $path ) ? (array) include $path : [ 'profiles' => [ 'default' => [ 'blocked_key_patterns' => [], 'minimized_keys' => [] ] ] ];
	}

	/** @return array<string,mixed> */
	private static function schemas() {
		if ( class_exists( 'NGTAI_Config' ) && method_exists( 'NGTAI_Config', 'event_schemas' ) ) {
			return (array) NGTAI_Config::event_schemas();
		}
		$path = dirname( __DIR__ ) . '/config/event-schemas.php';
		return is_readable( $path ) ? (array) include $path : [ 'events' => [] ];
	}
}
