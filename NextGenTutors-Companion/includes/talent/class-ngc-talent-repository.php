<?php
/**
 * Persist talent evaluations (not CSV).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evaluation repository.
 */
final class NGC_Talent_Repository {

	/**
	 * Ensure schema tables exist.
	 */
	public static function ensure_schema() {
		if ( class_exists( 'NGC_Database' ) && method_exists( 'NGC_Database', 'ensure_talent_tables' ) ) {
			NGC_Database::ensure_talent_tables();
		}
	}

	/**
	 * @param array<string,mixed> $row Evaluation row.
	 * @return int|WP_Error Insert id.
	 */
	public static function save_evaluation( array $row ) {
		global $wpdb;
		self::ensure_schema();
		$table = NGC_Database::table( 'talent_evaluations' );
		if ( ! $table ) {
			return new WP_Error( 'ngc_talent_schema', 'Evaluations table missing' );
		}

		$idem = (string) ( $row['idempotency_key'] ?? '' );
		if ( '' === $idem ) {
			$idem = 'eval-' . wp_generate_uuid4();
		} else {
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE idempotency_key = %s LIMIT 1", $idem ) );
			if ( $existing ) {
				return (int) $existing;
			}
		}

		$now = current_time( 'mysql', true );
		$data = [
			'uuid'                  => class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4(),
			'tenant_id'             => class_exists( 'NGC_Tenant_Context' ) ? (string) NGC_Tenant_Context::id() : '1',
			'candidate_type'        => sanitize_key( (string) ( $row['candidate_type'] ?? 'application' ) ),
			'candidate_id'          => sanitize_text_field( (string) ( $row['candidate_id'] ?? '' ) ),
			'requirement_id'        => sanitize_text_field( (string) ( $row['requirement_id'] ?? '' ) ),
			'score'                 => null === ( $row['score'] ?? null ) ? null : (float) $row['score'],
			'recommendation'        => sanitize_text_field( (string) ( $row['recommendation'] ?? '' ) ),
			'model_version'         => sanitize_text_field( (string) ( $row['model_version'] ?? NGC_Talent_Settings::MODEL_VERSION ) ),
			'weight_config_version' => sanitize_text_field( (string) ( $row['weight_config_version'] ?? NGC_Talent_Settings::WEIGHTS_VERSION ) ),
			'input_snapshot_hash'   => sanitize_text_field( (string) ( $row['input_snapshot_hash'] ?? '' ) ),
			'result_json'           => wp_json_encode( $row['result'] ?? [] ),
			'idempotency_key'       => $idem,
			'correlation_id'        => sanitize_text_field( (string) ( $row['correlation_id'] ?? '' ) ),
			'created_by'            => (int) ( $row['created_by'] ?? get_current_user_id() ),
			'created_at'            => $now,
			'updated_at'            => $now,
		];
		$ok = $wpdb->insert( $table, $data );
		if ( false === $ok ) {
			return new WP_Error( 'ngc_talent_insert', 'Failed to save evaluation' );
		}
		$eid = (int) $wpdb->insert_id;
		self::save_components( $eid, (array) ( $row['result']['components'] ?? [] ) );
		return $eid;
	}

	/**
	 * @param int                  $evaluation_id Evaluation id.
	 * @param array<int,array>     $components Components.
	 */
	public static function save_components( $evaluation_id, array $components ) {
		global $wpdb;
		$table = NGC_Database::table( 'talent_evaluation_components' );
		if ( ! $table || $evaluation_id <= 0 ) {
			return;
		}
		foreach ( $components as $c ) {
			if ( ! is_array( $c ) ) {
				continue;
			}
			$wpdb->insert(
				$table,
				[
					'evaluation_id' => (int) $evaluation_id,
					'component_key' => sanitize_key( (string) ( $c['key'] ?? '' ) ),
					'score'         => isset( $c['score'] ) ? (float) $c['score'] : null,
					'weight'        => isset( $c['weight'] ) ? (float) $c['weight'] : null,
					'status'        => sanitize_text_field( (string) ( $c['status'] ?? '' ) ),
					'meta_json'     => wp_json_encode( $c ),
				]
			);
		}
	}

	/**
	 * @param int $id Evaluation id.
	 * @return array<string,mixed>|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'talent_evaluations' );
		if ( ! $table ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
		if ( ! $row ) {
			return null;
		}
		$result = json_decode( (string) ( $row['result_json'] ?? '{}' ), true );
		$row['result'] = is_array( $result ) ? $result : [];
		return $row;
	}

	/**
	 * @param array<string,mixed> $args Query args.
	 * @return array<int,array<string,mixed>>
	 */
	public static function query( array $args = [] ) {
		global $wpdb;
		$table = NGC_Database::table( 'talent_evaluations' );
		if ( ! $table ) {
			return [];
		}
		$limit = max( 1, min( 100, (int) ( $args['limit'] ?? 25 ) ) );
		$sql   = "SELECT id, uuid, candidate_type, candidate_id, score, recommendation, model_version, created_at FROM {$table} ORDER BY id DESC LIMIT %d";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Upsert a simple requirement profile (JSON body).
	 *
	 * @param string              $profile_key Key.
	 * @param array<string,mixed> $profile Profile.
	 * @return int|WP_Error
	 */
	public static function save_requirement_profile( $profile_key, array $profile ) {
		global $wpdb;
		self::ensure_schema();
		$table = NGC_Database::table( 'talent_requirement_profiles' );
		if ( ! $table ) {
			return new WP_Error( 'ngc_talent_schema', 'Requirement profiles table missing' );
		}
		$key = sanitize_key( (string) $profile_key );
		$now = current_time( 'mysql', true );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE profile_key = %s LIMIT 1", $key ) );
		$payload = [
			'title'        => sanitize_text_field( (string) ( $profile['title'] ?? $key ) ),
			'profile_json' => wp_json_encode( $profile ),
			'version'      => sanitize_text_field( (string) ( $profile['version'] ?? '1' ) ),
			'updated_at'   => $now,
		];
		if ( $existing ) {
			$wpdb->update( $table, $payload, [ 'id' => (int) $existing ] );
			return (int) $existing;
		}
		$payload['profile_key'] = $key;
		$payload['created_at']  = $now;
		$wpdb->insert( $table, $payload );
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param string $profile_key Key.
	 * @return array<string,mixed>|null
	 */
	public static function get_requirement_profile( $profile_key ) {
		global $wpdb;
		$table = NGC_Database::table( 'talent_requirement_profiles' );
		if ( ! $table ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE profile_key = %s LIMIT 1", sanitize_key( (string) $profile_key ) ),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$json = json_decode( (string) ( $row['profile_json'] ?? '{}' ), true );
		$row['profile'] = is_array( $json ) ? $json : [];
		return $row;
	}
}
