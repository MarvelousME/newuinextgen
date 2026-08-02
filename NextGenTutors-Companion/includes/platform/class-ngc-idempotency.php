<?php
/**
 * Idempotency store — begin / commit / reject duplicates.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tenant-scoped idempotency keys.
 */
final class NGC_Idempotency {

	/**
	 * Init — no hooks.
	 */
	public static function init() {}

	/**
	 * Begin an idempotent operation.
	 *
	 * @param string $key         Idempotency key.
	 * @param string $fingerprint Request fingerprint.
	 * @param string $scope       Scope label.
	 * @param int    $ttl_seconds TTL.
	 * @return array{status:string,result?:mixed}|WP_Error
	 *         status: started|replay|conflict
	 */
	public static function begin( $key, $fingerprint, $scope = 'default', $ttl_seconds = 86400 ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'idempotency_keys' );
		$tenant = NGC_Tenant_Context::id();
		$key    = substr( (string) $key, 0, 191 );
		$fp     = (string) $fingerprint;
		$scope  = sanitize_key( (string) $scope );
		$exp    = gmdate( 'Y-m-d H:i:s', time() + max( 60, (int) $ttl_seconds ) );

		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE tenant_id = %d AND idem_key = %s LIMIT 1",
				$tenant,
				$key
			)
		);

		if ( $existing ) {
			if ( (string) $existing->fingerprint !== '' && (string) $existing->fingerprint !== $fp ) {
				return new WP_Error( 'ngc_idem_conflict', 'Idempotency key reused with different payload.', [ 'status' => 409 ] );
			}
			if ( 'completed' === (string) $existing->status ) {
				$result = json_decode( (string) $existing->result_json, true );
				return [
					'status' => 'replay',
					'result' => is_array( $result ) ? $result : [ 'raw' => $existing->result_json ],
				];
			}
			if ( 'started' === (string) $existing->status ) {
				return new WP_Error( 'ngc_idem_in_progress', 'Operation already in progress.', [ 'status' => 409 ] );
			}
		}

		$now = current_time( 'mysql', true );
		if ( $existing ) {
			$wpdb->update(
				$table,
				[
					'fingerprint' => $fp,
					'scope'       => $scope,
					'status'      => 'started',
					'expires_at'  => $exp,
					'updated_at'  => $now,
				],
				[ 'id' => (int) $existing->id ],
				[ '%s', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			$ins = $wpdb->insert(
				$table,
				[
					'tenant_id'   => $tenant,
					'idem_key'    => $key,
					'fingerprint' => $fp,
					'scope'       => $scope,
					'status'      => 'started',
					'expires_at'  => $exp,
					'created_at'  => $now,
					'updated_at'  => $now,
				],
				[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);
			if ( ! $ins ) {
				// Race: another insert won.
				return self::begin( $key, $fingerprint, $scope, $ttl_seconds );
			}
		}

		return [ 'status' => 'started' ];
	}

	/**
	 * Commit successful result.
	 *
	 * @param string $key    Key.
	 * @param mixed  $result Result (json-serializable).
	 * @return bool
	 */
	public static function commit( $key, $result = [] ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'idempotency_keys' );
		$tenant = NGC_Tenant_Context::id();
		$n      = $wpdb->update(
			$table,
			[
				'status'      => 'completed',
				'result_json' => wp_json_encode( $result ),
				'updated_at'  => current_time( 'mysql', true ),
			],
			[
				'tenant_id' => $tenant,
				'idem_key'  => substr( (string) $key, 0, 191 ),
			],
			[ '%s', '%s', '%s' ],
			[ '%d', '%s' ]
		);
		return false !== $n;
	}

	/**
	 * Reject / clear started key so retries can proceed.
	 *
	 * @param string $key    Key.
	 * @param string $reason Reason.
	 * @return bool
	 */
	public static function reject( $key, $reason = '' ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'idempotency_keys' );
		$tenant = NGC_Tenant_Context::id();
		$n      = $wpdb->update(
			$table,
			[
				'status'      => 'rejected',
				'result_json' => wp_json_encode( [ 'error' => (string) $reason ] ),
				'updated_at'  => current_time( 'mysql', true ),
			],
			[
				'tenant_id' => $tenant,
				'idem_key'  => substr( (string) $key, 0, 191 ),
			],
			[ '%s', '%s', '%s' ],
			[ '%d', '%s' ]
		);
		return false !== $n;
	}

	/**
	 * Run callback once under idempotency key.
	 *
	 * @param string   $key         Key.
	 * @param callable $cb          Callback returning result array.
	 * @param string   $fingerprint Fingerprint (auto from key if empty).
	 * @param string   $scope       Scope.
	 * @return mixed|WP_Error
	 */
	public static function once( $key, $cb, $fingerprint = '', $scope = 'default' ) {
		$fp  = $fingerprint !== '' ? $fingerprint : hash( 'sha256', (string) $key );
		$beg = self::begin( $key, $fp, $scope );
		if ( is_wp_error( $beg ) ) {
			return $beg;
		}
		if ( 'replay' === $beg['status'] ) {
			if ( class_exists( 'NGC_Metrics' ) ) {
				NGC_Metrics::inc( 'idempotency_replay_total', 1, [ 'scope' => $scope ] );
			}
			return $beg['result'];
		}
		try {
			$result = $cb();
			if ( is_wp_error( $result ) ) {
				self::reject( $key, $result->get_error_message() );
				return $result;
			}
			self::commit( $key, is_array( $result ) ? $result : [ 'ok' => true, 'value' => $result ] );
			return $result;
		} catch ( Throwable $e ) {
			self::reject( $key, $e->getMessage() );
			return new WP_Error( 'ngc_idem_exception', $e->getMessage() );
		}
	}

	/**
	 * Fingerprint an array payload.
	 *
	 * @param array $payload Payload.
	 * @return string
	 */
	public static function fingerprint( array $payload ) {
		ksort( $payload );
		return hash( 'sha256', wp_json_encode( $payload ) );
	}
}
