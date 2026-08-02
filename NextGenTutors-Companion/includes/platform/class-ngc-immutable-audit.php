<?php
/**
 * Hash-chain immutable audit log.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Append-only audit chain with HMAC.
 */
final class NGC_Immutable_Audit {

	/**
	 * Dual-write is handled from NGC_Audit::log; keep filter for custom callers.
	 */
	public static function init() {
		add_action( 'ngc_audit_logged', [ __CLASS__, 'on_audit_logged' ], 10, 6 );
		add_filter( 'ngc_audit_log_result', [ __CLASS__, 'after_classic_log' ], 10, 2 );
	}

	/**
	 * @param string $action      Unused when called via classic hook shape.
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object id.
	 * @param array  $context     Context.
	 * @param int    $actor_id    Actor.
	 * @param array  $meta        Meta.
	 */
	public static function on_audit_logged( $action, $object_type = '', $object_id = 0, $context = [], $actor_id = 0, $meta = [] ) {
		if ( is_array( $action ) ) {
			self::append(
				(string) ( $action['action'] ?? 'audit' ),
				(string) ( $action['object_type'] ?? '' ),
				(int) ( $action['object_id'] ?? 0 ),
				$action
			);
			return;
		}
		self::append(
			(string) $action,
			(string) $object_type,
			(int) $object_id,
			[
				'context'  => is_array( $context ) ? $context : [],
				'actor_id' => (int) $actor_id,
				'meta'     => is_array( $meta ) ? $meta : [],
			]
		);
	}

	/**
	 * Append a chain event.
	 *
	 * @param string $action      Action.
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object id.
	 * @param array  $payload     Payload.
	 * @return int|WP_Error Chain row id.
	 */
	public static function append( $action, $object_type = '', $object_id = 0, array $payload = [] ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'audit_chain' );
		$tenant = NGC_Tenant_Context::id();
		$wpdb->query( 'START TRANSACTION' );

		$prev = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT seq, event_hash FROM {$table} WHERE tenant_id = %d ORDER BY seq DESC LIMIT 1 FOR UPDATE",
				$tenant
			)
		);
		$seq       = $prev ? ( (int) $prev->seq + 1 ) : 1;
		$prev_hash = $prev ? (string) $prev->event_hash : str_repeat( '0', 64 );
		$uuid      = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'aud_', true );
		$payload_h = hash( 'sha256', wp_json_encode( $payload ) );
		$material  = implode(
			'|',
			[
				$tenant,
				$seq,
				$uuid,
				(string) $action,
				(string) $object_type,
				(int) $object_id,
				$payload_h,
				$prev_hash,
			]
		);
		$event_hash = hash( 'sha256', $material );
		$key        = (string) get_option( 'ngc_audit_hmac_key', '' );
		$hmac       = hash_hmac( 'sha256', $event_hash, $key );

		$ok = $wpdb->insert(
			$table,
			[
				'tenant_id'    => $tenant,
				'seq'          => $seq,
				'event_uuid'   => $uuid,
				'action'       => sanitize_key( (string) $action ),
				'object_type'  => sanitize_key( (string) $object_type ),
				'object_id'    => (int) $object_id,
				'actor_id'     => get_current_user_id(),
				'payload_hash' => $payload_h,
				'prev_hash'    => $prev_hash,
				'event_hash'   => $event_hash,
				'hmac'         => $hmac,
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
		if ( ! $ok ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ngc_audit_append_failed', 'Failed to append audit chain.' );
		}
		$id = (int) $wpdb->insert_id;
		$wpdb->query( 'COMMIT' );
		return $id;
	}

	/**
	 * Verify chain integrity.
	 *
	 * @param int|null $tenant Tenant override.
	 * @return array{ok:bool,checked:int,error?:string}
	 */
	public static function verify( $tenant = null ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'audit_chain' );
		$tenant = null !== $tenant ? (int) $tenant : NGC_Tenant_Context::id();
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE tenant_id = %d ORDER BY seq ASC",
				$tenant
			)
		);
		$key       = (string) get_option( 'ngc_audit_hmac_key', '' );
		$prev_hash = str_repeat( '0', 64 );
		$checked   = 0;
		foreach ( (array) $rows as $row ) {
			$checked++;
			if ( (string) $row->prev_hash !== $prev_hash ) {
				return [ 'ok' => false, 'checked' => $checked, 'error' => 'prev_hash_mismatch@' . $row->seq ];
			}
			$material = implode(
				'|',
				[
					$tenant,
					(int) $row->seq,
					(string) $row->event_uuid,
					(string) $row->action,
					(string) $row->object_type,
					(int) $row->object_id,
					(string) $row->payload_hash,
					(string) $row->prev_hash,
				]
			);
			$expect = hash( 'sha256', $material );
			if ( ! hash_equals( $expect, (string) $row->event_hash ) ) {
				return [ 'ok' => false, 'checked' => $checked, 'error' => 'event_hash_mismatch@' . $row->seq ];
			}
			$hmac = hash_hmac( 'sha256', (string) $row->event_hash, $key );
			if ( ! hash_equals( $hmac, (string) $row->hmac ) ) {
				return [ 'ok' => false, 'checked' => $checked, 'error' => 'hmac_mismatch@' . $row->seq ];
			}
			$prev_hash = (string) $row->event_hash;
		}
		return [ 'ok' => true, 'checked' => $checked ];
	}

	/**
	 * Reject mutations (service guard).
	 *
	 * @param mixed $null Unused.
	 * @return false
	 */
	public static function reject_mutation() {
		return false;
	}

	/**
	 * @param mixed                $result Classic result.
	 * @param array<string,mixed>  $args   Args.
	 * @return mixed
	 */
	public static function after_classic_log( $result, $args ) {
		if ( is_array( $args ) ) {
			self::on_audit_logged(
				(string) ( $args['action'] ?? 'audit' ),
				(string) ( $args['object_type'] ?? '' ),
				(int) ( $args['object_id'] ?? 0 ),
				(array) ( $args['context'] ?? $args ),
				(int) ( $args['actor_id'] ?? 0 ),
				(array) ( $args['meta'] ?? [] )
			);
		}
		return $result;
	}
}
