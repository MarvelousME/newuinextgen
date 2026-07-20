<?php
/**
 * Export engine — datasets, filtering, compression, delivery.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enterprise export service.
 */
class NGC_Export_Engine {

	/**
	 * Supported datasets.
	 *
	 * @return string[]
	 */
	public static function datasets() {
		return [
			'tutors', 'students', 'parents', 'bookings', 'lessons', 'payments',
			'invoices', 'payouts', 'reviews', 'ratings', 'analytics', 'audit_logs',
			'system_logs',
			'crm_contacts', 'affiliate_tracking', 'user_profiles', 'session_tracking',
			'dashboard_metrics', 'verification_results', 'self_healing_reports', 'system_health',
		];
	}

	/**
	 * @param string               $dataset Dataset key.
	 * @param array<string, mixed> $args    Filters.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fetch_dataset( $dataset, $args = [] ) {
		$dataset = sanitize_key( $dataset );
		$from    = sanitize_text_field( $args['from'] ?? '' );
		$to      = sanitize_text_field( $args['to'] ?? '' );

		switch ( $dataset ) {
			case 'tutors':
				return self::export_users_by_role( 'tutor', $from, $to );
			case 'students':
				return self::export_users_by_role( 'student', $from, $to );
			case 'parents':
				return self::export_users_by_role( 'parent', $from, $to );
			case 'bookings':
				return self::export_entity( 'bookings', $from, $to );
			case 'lessons':
				return self::export_entity( 'sessions', $from, $to, 'session_logs' );
			case 'payments':
			case 'invoices':
			case 'payouts':
			case 'reviews':
			case 'ratings':
				return self::export_entity( rtrim( $dataset, 's' ) . 's', $from, $to );
			case 'analytics':
				return self::export_entity( 'analytics', $from, $to );
			case 'audit_logs':
				return NGC_Audit_Service::search( array_merge( $args, [ 'limit' => 5000 ] ) );
			case 'system_logs':
				return NGC_System_Log_Service::flatten_for_export(
					NGC_System_Log_Service::search( array_merge( $args, [ 'limit' => 5000 ] ) )
				);
			case 'crm_contacts':
				return self::export_crm_contacts( $from, $to );
			case 'affiliate_tracking':
				return self::export_entity( 'affiliates', $from, $to );
			case 'user_profiles':
				return self::export_entity( 'user_profiles', $from, $to );
			case 'session_tracking':
				return self::export_entity( 'sessions', $from, $to );
			case 'dashboard_metrics':
				return [ NGC_Platform_Analytics::snapshot() ];
			case 'verification_results':
				return [ NGC_Verification::run_checks() ];
			case 'self_healing_reports':
				return [ NGC_Repair_Engine::last_report() ];
			case 'system_health':
				return [ NGC_Health_Scanner::full_scan() ];
			default:
				return [];
		}
	}

	/**
	 * Run an export job.
	 *
	 * @param array<string, mixed> $job Job config.
	 * @return array<string, mixed>
	 */
	public static function run_export( $job ) {
		$dataset = sanitize_key( $job['dataset'] ?? '' );
		$format  = sanitize_key( $job['format'] ?? 'csv' );
		$filters = is_array( $job['filters'] ?? null ) ? $job['filters'] : [];
		$columns = is_array( $job['columns'] ?? null ) ? $job['columns'] : [];

		if ( ! in_array( $dataset, self::datasets(), true ) ) {
			return [ 'success' => false, 'error' => 'Invalid dataset.' ];
		}

		$rows = self::fetch_dataset( $dataset, $filters );
		if ( empty( $columns ) && ! empty( $rows ) ) {
			$columns = array_keys( $rows[0] );
		}
		if ( empty( $columns ) ) {
			$columns = [ 'id' ];
		}

		$content  = NGC_Export_Formats::render( $rows, $columns, $format );
		$upload   = wp_upload_dir();
		$dir      = trailingslashit( $upload['basedir'] ) . 'ngc-exports';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$filename = sprintf( 'ngc-%s-%s.%s', $dataset, gmdate( 'Ymd-His' ), NGC_Export_Formats::extension( $format ) );
		$path     = trailingslashit( $dir ) . $filename;
		file_put_contents( $path, $content );

		if ( ! empty( $job['compress'] ) && class_exists( 'ZipArchive' ) ) {
			$zip_path = $path . '.zip';
			$zip      = new ZipArchive();
			if ( true === $zip->open( $zip_path, ZipArchive::CREATE ) ) {
				$zip->addFile( $path, $filename );
				$zip->close();
				unlink( $path );
				$path     = $zip_path;
				$filename = $filename . '.zip';
			}
		}

		$url = trailingslashit( $upload['baseurl'] ) . 'ngc-exports/' . $filename;

		NGC_Audit::log( 'export_generated', 'export', 0, [
			'dataset'  => $dataset,
			'format'   => $format,
			'filename' => $filename,
			'rows'     => count( $rows ),
		], 0, [
			'workflow_key'   => 'export.engine',
			'correlation_id' => wp_generate_uuid4(),
		] );

		return [
			'success'  => true,
			'path'     => $path,
			'url'      => $url,
			'filename' => $filename,
			'rows'     => count( $rows ),
			'mime'     => NGC_Export_Formats::mime_type( $format ),
		];
	}

	/**
	 * @param string $role Role slug.
	 * @param string $from From date.
	 * @param string $to   To date.
	 * @return array<int, array<string, mixed>>
	 */
	private static function export_users_by_role( $role, $from, $to ) {
		$users = get_users( [ 'role' => $role, 'number' => 5000 ] );
		$rows  = [];
		foreach ( $users as $user ) {
			$registered = $user->user_registered;
			if ( $from && $registered < $from ) {
				continue;
			}
			if ( $to && $registered > $to ) {
				continue;
			}
			$rows[] = [
				'id'           => $user->ID,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'registered'   => $registered,
				'province'     => get_user_meta( $user->ID, 'ngc_province', true ),
			];
		}
		return $rows;
	}

	/**
	 * @param string $entity Entity key.
	 * @param string $from   From date.
	 * @param string $to     To date.
	 * @param string $table  Optional table override.
	 * @return array<int, array<string, mixed>>
	 */
	private static function export_entity( $entity, $from, $to, $table = '' ) {
		global $wpdb;
		if ( 'sessions' === $entity && 'session_logs' === $table ) {
			$table_name = NGC_Database::table( 'session_logs' );
		} else {
			$table_name = NGC_Platform_Repository::table_for_export( $entity );
		}
		if ( ! $table_name ) {
			return NGC_Platform_Repository::list( $entity, [ 'limit' => 5000 ] );
		}
		$where  = '1=1';
		$values = [];
		if ( $from ) {
			$where   .= ' AND created_at >= %s';
			$values[] = $from;
		}
		if ( $to ) {
			$where   .= ' AND created_at <= %s';
			$values[] = $to;
		}
		$values[] = 5000;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE {$where} ORDER BY id DESC LIMIT %d", $values ), ARRAY_A );
	}

	/**
	 * @param string $from From date.
	 * @param string $to   To date.
	 * @return array<int, array<string, mixed>>
	 */
	private static function export_crm_contacts( $from, $to ) {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return [ [ 'note' => 'FluentCRM not active' ] ];
		}
		try {
			$contacts = FluentCrmApi( 'contacts' )->getContacts( [ 'per_page' => 500 ] );
			return is_array( $contacts ) ? $contacts : [];
		} catch ( Exception $e ) {
			return [ [ 'error' => $e->getMessage() ] ];
		}
	}

	/**
	 * Save export template.
	 *
	 * @param array<string, mixed> $template Template data.
	 * @return int Template ID.
	 */
	public static function save_template( $template ) {
		global $wpdb;
		$wpdb->insert(
			NGC_Database::table( 'export_templates' ),
			[
				'name'       => sanitize_text_field( $template['name'] ?? 'Template' ),
				'dataset'    => sanitize_key( $template['dataset'] ?? '' ),
				'format'     => sanitize_key( $template['format'] ?? 'csv' ),
				'columns'    => wp_json_encode( $template['columns'] ?? [] ),
				'filters'    => wp_json_encode( $template['filters'] ?? [] ),
				'created_by' => get_current_user_id(),
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}
}
