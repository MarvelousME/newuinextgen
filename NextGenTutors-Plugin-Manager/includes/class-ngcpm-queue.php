<?php
/**
 * Sequential install/activate queue plan.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds ordered queue items for UI batch processing.
 */
class NGCPM_Queue {

	/**
	 * @param array<string, array<string, mixed>>|null $scan Optional scan.
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_plan( $scan = null ) {
		$scan  = $scan ?: NGCPM_Scanner::scan( false );
		$items = [];

		foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
			$row = $scan[ $slug ] ?? array_merge( $def, [ 'registry_key' => $slug ] );

			if ( ! empty( $row['is_skipped'] ) && empty( $row['installed'] ) ) {
				continue;
			}

			if ( ! empty( $row['active'] ) ) {
				continue;
			}

			if ( empty( $row['installed'] ) ) {
			if ( empty( $row['can_auto_install'] ) ) {
				$local = NGCPM_Installer::resolve_local_path( array_merge( $def, [ 'registry_key' => $slug ] ) );
				$manual_msg = $local
					? sprintf(
						/* translators: %s: zip filename */
						__( 'Local zip detected (%s) — run Install Queue or click Install missing.', 'nextgentutors-plugin-manager' ),
						basename( $local )
					)
					: ( empty( $def['required'] )
						? __( 'Optional — premium/manual. Place zip in local packages directory, upload, or search WordPress.org.', 'nextgentutors-plugin-manager' )
						: sprintf(
							/* translators: %s: local zip directory */
							__( 'Place plugin zip in %s then run Install Queue.', 'nextgentutors-plugin-manager' ),
							NGCPM_Settings::local_zip_dir()
						) );
				$items[] = [
					'slug'     => $slug,
					'name'     => (string) ( $row['name'] ?? $slug ),
					'action'   => 'manual',
					'status'   => 'MANUAL_REQUIRED',
					'message'  => $manual_msg,
					'optional' => empty( $def['required'] ),
				];
					continue;
				}
				$items[] = [
					'slug'     => $slug,
					'name'     => (string) ( $row['name'] ?? $slug ),
					'action'   => 'install',
					'status'   => 'queued',
					'optional' => empty( $def['required'] ),
					'message'  => ! empty( $row['local_zip_path'] )
						? sprintf(
							/* translators: %s: zip filename */
							__( 'Will install from local zip: %s', 'nextgentutors-plugin-manager' ),
							basename( (string) $row['local_zip_path'] )
						)
						: '',
				];
				continue;
			}

			if ( ! empty( $row['installed'] ) && empty( $row['active'] ) ) {
				$items[] = [
					'slug'   => $slug,
					'name'   => (string) ( $row['name'] ?? $slug ),
					'action' => 'activate',
					'status' => 'queued',
					'optional' => empty( $def['required'] ),
				];
			}
		}

		return $items;
	}
}
