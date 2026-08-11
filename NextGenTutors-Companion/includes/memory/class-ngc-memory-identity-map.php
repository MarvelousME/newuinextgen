<?php
/**
 * Bridge ↔ Tencent identity mapping (no plaintext user_key).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent mapping store. Secrets live in NGC_Secret_Vault via remote_meta.user_key_ref.
 */
final class NGC_Memory_Identity_Map {

	public const TABLE_KEY = 'memory_identity_map';

	/**
	 * @return string
	 */
	public static function table() {
		return NGC_Database::table( self::TABLE_KEY );
	}

	/**
	 * Resolve or create a mapping row.
	 *
	 * @param string               $bridge_type tenant|user|agent|team|task|session.
	 * @param string               $bridge_id   Bridge stable id.
	 * @param string               $remote_id   Provider remote id.
	 * @param array<string,mixed>  $remote_meta Non-secret meta (may include user_key_ref).
	 * @param string               $provider    Provider slug.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function upsert( $bridge_type, $bridge_id, $remote_id, array $remote_meta = [], $provider = 'tencentdb' ) {
		global $wpdb;
		$table = self::table();
		if ( '' === $table ) {
			return new WP_Error( 'ngc_memory_map', 'Identity map table missing.' );
		}

		$tenant_id   = class_exists( 'NGC_Tenant_Context' ) ? (string) NGC_Tenant_Context::id() : '1';
		$bridge_type = sanitize_key( (string) $bridge_type );
		$bridge_id   = sanitize_text_field( (string) $bridge_id );
		$remote_id   = sanitize_text_field( (string) $remote_id );
		$provider    = sanitize_key( (string) $provider );
		$now         = current_time( 'mysql', true );

		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE provider = %s AND bridge_type = %s AND bridge_id = %s AND tenant_id = %s LIMIT 1",
				$provider,
				$bridge_type,
				$bridge_id,
				$tenant_id
			),
			ARRAY_A
		);

		$meta_json = wp_json_encode( self::scrub_meta( $remote_meta ) );
		if ( $existing ) {
			$wpdb->update(
				$table,
				[
					'remote_id'   => $remote_id !== '' ? $remote_id : (string) $existing['remote_id'],
					'remote_meta' => $meta_json,
					'updated_at'  => $now,
				],
				[ 'id' => (int) $existing['id'] ],
				[ '%s', '%s', '%s' ],
				[ '%d' ]
			);
			return self::get( $bridge_type, $bridge_id, $provider );
		}

		$ok = $wpdb->insert(
			$table,
			[
				'bridge_type' => $bridge_type,
				'bridge_id'   => $bridge_id,
				'tenant_id'   => $tenant_id,
				'provider'    => $provider,
				'remote_id'   => $remote_id,
				'remote_meta' => $meta_json,
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
		if ( false === $ok ) {
			return new WP_Error( 'ngc_memory_map_insert', 'Failed to insert identity map row.' );
		}
		return self::get( $bridge_type, $bridge_id, $provider );
	}

	/**
	 * @param string $bridge_type Type.
	 * @param string $bridge_id   Id.
	 * @param string $provider    Provider.
	 * @return array<string,mixed>|null
	 */
	public static function get( $bridge_type, $bridge_id, $provider = 'tencentdb' ) {
		global $wpdb;
		$table = self::table();
		if ( '' === $table ) {
			return null;
		}
		$tenant_id = class_exists( 'NGC_Tenant_Context' ) ? (string) NGC_Tenant_Context::id() : '1';
		$row       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE provider = %s AND bridge_type = %s AND bridge_id = %s AND tenant_id = %s LIMIT 1",
				sanitize_key( (string) $provider ),
				sanitize_key( (string) $bridge_type ),
				sanitize_text_field( (string) $bridge_id ),
				$tenant_id
			),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$meta = json_decode( (string) ( $row['remote_meta'] ?? '{}' ), true );
		$row['remote_meta'] = is_array( $meta ) ? $meta : [];
		return $row;
	}

	/**
	 * Resolve vaulted user_key for a mapped user — never log return value.
	 *
	 * @param string $bridge_user_id WP user id as string.
	 * @return string Empty if missing.
	 */
	public static function user_key_for( $bridge_user_id ) {
		$row = self::get( 'user', (string) $bridge_user_id );
		if ( ! $row ) {
			return '';
		}
		$ref = (string) ( $row['remote_meta']['user_key_ref'] ?? '' );
		return NGC_Memory_Settings::reveal_user_key( $ref );
	}

	/**
	 * Tenant → remote service_id.
	 *
	 * @return string
	 */
	public static function service_id_for_tenant() {
		$tenant = class_exists( 'NGC_Tenant_Context' ) ? (string) NGC_Tenant_Context::id() : '1';
		$row    = self::get( 'tenant', $tenant );
		if ( $row && ! empty( $row['remote_id'] ) ) {
			return (string) $row['remote_id'];
		}
		$strategy = (string) ( NGC_Memory_Settings::get()['service_id_strategy'] ?? 'tenant' );
		if ( 'fixed_default' === $strategy ) {
			return 'default';
		}
		return 'bridge-tenant-' . $tenant;
	}

	/**
	 * Strip forbidden secret fields from meta before persist.
	 *
	 * @param array<string,mixed> $meta Meta.
	 * @return array<string,mixed>
	 */
	private static function scrub_meta( array $meta ) {
		unset( $meta['user_key'], $meta['api_key'], $meta['bearer'], $meta['password'] );
		return $meta;
	}
}
