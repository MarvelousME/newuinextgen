<?php
/**
 * WORM evidence export — signed JSON + SHA256 manifest.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export signed evidence bundles to uploads.
 */
final class NGC_Worm_Export {

	/**
	 * Init.
	 */
	public static function init() {}

	/**
	 * Export audit chain slice + optional related evidence.
	 *
	 * @param array $args from_seq, to_seq, label, legal_hold.
	 * @return array|WP_Error {path, manifest_sha256, count}
	 */
	public static function export( array $args = [] ) {
		global $wpdb;
		$tenant = NGC_Tenant_Context::id();
		$from   = isset( $args['from_seq'] ) ? (int) $args['from_seq'] : 1;
		$to     = isset( $args['to_seq'] ) ? (int) $args['to_seq'] : PHP_INT_MAX;
		$label  = sanitize_file_name( (string) ( $args['label'] ?? ( 'worm-' . gmdate( 'Ymd-His' ) ) ) );
		$hold   = ! empty( $args['legal_hold'] );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . NGC_Platform_Schema::table( 'audit_chain' ) . ' WHERE tenant_id = %d AND seq >= %d AND seq <= %d ORDER BY seq ASC',
				$tenant,
				$from,
				$to
			),
			ARRAY_A
		);

		$verify = NGC_Immutable_Audit::verify( $tenant );
		$bundle = [
			'tenant_id'   => $tenant,
			'exported_at' => gmdate( 'c' ),
			'label'       => $label,
			'legal_hold'  => $hold,
			'verify'      => $verify,
			'events'      => $rows,
		];
		$json = wp_json_encode( $bundle, JSON_PRETTY_PRINT );
		$sha  = hash( 'sha256', (string) $json );
		$key  = (string) get_option( 'ngc_audit_hmac_key', '' );
		$sig  = hash_hmac( 'sha256', $sha, $key );

		$manifest = [
			'bundle_sha256' => $sha,
			'signature'     => $sig,
			'algorithm'     => 'HMAC-SHA256',
			'count'         => count( (array) $rows ),
			'exported_at'   => gmdate( 'c' ),
			'legal_hold'    => $hold,
		];

		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'ngc-worm';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$base = $dir . '/' . $label;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $base . '.json', $json );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $base . '.manifest.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT ) );

		update_option(
			'ngc_worm_last_export',
			[
				'path'            => $base . '.json',
				'manifest'        => $base . '.manifest.json',
				'manifest_sha256' => $sha,
				'count'           => count( (array) $rows ),
				'legal_hold'      => $hold,
			],
			false
		);

		return [
			'path'            => $base . '.json',
			'manifest'        => $base . '.manifest.json',
			'manifest_sha256' => $sha,
			'count'           => count( (array) $rows ),
		];
	}
}
