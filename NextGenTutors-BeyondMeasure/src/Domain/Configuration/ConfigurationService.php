<?php
declare(strict_types=1);

namespace NGTBM\Domain\Configuration;

use NGTBM\Domain\Audit\AuditLog;

/**
 * Transactional configuration store (no partial saves).
 */
final class ConfigurationService {

	/**
	 * @return array<string,mixed>
	 */
	public static function get( string $subsystem_id ): array {
		$key  = 'ngtbm_config_' . sanitize_key( $subsystem_id );
		$data = get_option( $key, null );
		if ( ! is_array( $data ) ) {
			$data = self::defaults( $subsystem_id );
		}
		return [
			'subsystemId' => $subsystem_id,
			'values'      => $data,
			'schema'      => self::schema( $subsystem_id ),
		];
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function save_transactional( string $subsystem_id, array $payload ) {
		$values = isset( $payload['values'] ) && is_array( $payload['values'] ) ? $payload['values'] : $payload;
		$valid  = self::validate( $subsystem_id, $values );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'ngtbm_config_revisions';
		$json  = wp_json_encode( $values );
		if ( ! is_string( $json ) ) {
			return new \WP_Error( 'CONFIG_ENCODE_FAILED', __( 'Could not encode configuration.', 'nextgentutors-beyond-measure' ), [ 'status' => 500 ] );
		}

		$ok = $wpdb->insert(
			$table,
			[
				'subsystem_id' => sanitize_key( $subsystem_id ),
				'payload'      => $json,
				'actor_id'     => get_current_user_id(),
				'status'       => 'published',
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%d', '%s', '%s' ]
		);
		if ( false === $ok ) {
			return new \WP_Error( 'CONFIG_PERSIST_FAILED', __( 'Configuration save failed; no partial write applied.', 'nextgentutors-beyond-measure' ), [ 'status' => 500 ] );
		}

		$key = 'ngtbm_config_' . sanitize_key( $subsystem_id );
		update_option( $key, $values, false );
		AuditLog::write( 'configuration.publish', 'subsystem', $subsystem_id, [ 'revisionId' => (int) $wpdb->insert_id ] );
		do_action( 'ngt_control_plane/configuration_changed', $subsystem_id, $values );

		return [
			'subsystemId' => $subsystem_id,
			'values'      => $values,
			'revisionId'  => (int) $wpdb->insert_id,
		];
	}

	/**
	 * @param array<string,mixed> $values
	 * @return true|\WP_Error
	 */
	private static function validate( string $subsystem_id, array $values ) {
		if ( $subsystem_id === 'bridge-talent-intelligence' || $subsystem_id === 'talent' ) {
			$weights = 0;
			foreach ( [ 'subject', 'curriculum', 'qualification', 'experience', 'availability', 'language' ] as $k ) {
				if ( isset( $values['scoring'][ $k ] ) ) {
					$weights += (float) $values['scoring'][ $k ];
				}
			}
			if ( $weights > 0 && abs( $weights - 100 ) > 0.5 ) {
				return new \WP_Error( 'CONFIG_INVALID', __( 'Scoring weights must sum to 100.', 'nextgentutors-beyond-measure' ), [ 'status' => 422 ] );
			}
		}
		return true;
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function defaults( string $subsystem_id ): array {
		if ( str_contains( $subsystem_id, 'talent' ) ) {
			return [
				'general'    => [
					'enabled'          => false,
					'provider'         => 'ngt-talent-suitability-v1',
					'timeoutSeconds'   => 10,
					'asyncEvaluation'  => true,
				],
				'scoring'    => [
					'subject'         => 25,
					'curriculum'      => 15,
					'qualification'   => 20,
					'experience'      => 15,
					'availability'    => 15,
					'language'        => 10,
				],
				'thresholds' => [
					'strong' => 85,
					'review' => 65,
				],
			];
		}
		return [ 'enabled' => true ];
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function schema( string $subsystem_id ): array {
		if ( str_contains( $subsystem_id, 'talent' ) ) {
			return [
				'sections' => [
					[
						'id'     => 'general',
						'title'  => 'General',
						'fields' => [
							[ 'key' => 'enabled', 'label' => 'Enabled', 'type' => 'boolean' ],
							[ 'key' => 'provider', 'label' => 'Provider', 'type' => 'select', 'options' => [ 'ngt-talent-suitability-v1', 'noop' ] ],
							[ 'key' => 'timeoutSeconds', 'label' => 'Timeout', 'type' => 'number' ],
							[ 'key' => 'asyncEvaluation', 'label' => 'Async evaluation', 'type' => 'boolean' ],
						],
					],
					[
						'id'     => 'scoring',
						'title'  => 'Scoring',
						'fields' => [
							[ 'key' => 'subject', 'label' => 'Subject', 'type' => 'percent' ],
							[ 'key' => 'curriculum', 'label' => 'Curriculum', 'type' => 'percent' ],
							[ 'key' => 'qualification', 'label' => 'Qualification', 'type' => 'percent' ],
							[ 'key' => 'experience', 'label' => 'Experience', 'type' => 'percent' ],
							[ 'key' => 'availability', 'label' => 'Availability', 'type' => 'percent' ],
							[ 'key' => 'language', 'label' => 'Language', 'type' => 'percent' ],
						],
					],
					[
						'id'     => 'thresholds',
						'title'  => 'Thresholds',
						'fields' => [
							[ 'key' => 'strong', 'label' => 'Strong match', 'type' => 'percent' ],
							[ 'key' => 'review', 'label' => 'Review', 'type' => 'percent' ],
						],
					],
				],
			];
		}
		return [ 'sections' => [] ];
	}
}
