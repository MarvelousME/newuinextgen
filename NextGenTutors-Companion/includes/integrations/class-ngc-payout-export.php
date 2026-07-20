<?php
/**
 * PayFast-compatible payout batch export for pending gateway payouts.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds CSV batches for PayFast mass payment / EFT reconciliation.
 */
class NGC_Payout_Export {

	const CSV_HEADERS = [ 'recipient_email', 'recipient_name', 'amount', 'currency', 'reference', 'payout_id' ];

	/**
	 * @param string $status Payout status filter.
	 * @return array<int, array<string, mixed>>
	 */
	public static function rows_for_status( $status = 'pending' ) {
		global $wpdb;
		$table = NGC_Database::table( 'payouts' );
		if ( ! $table ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s ORDER BY id ASC",
				sanitize_key( (string) $status )
			),
			ARRAY_A
		);
		$out = [];
		foreach ( (array) $rows as $row ) {
			$tutor_id = (int) ( $row['tutor_user_id'] ?? 0 );
			$user     = $tutor_id ? get_userdata( $tutor_id ) : null;
			if ( ! $user ) {
				continue;
			}
			$out[] = [
				'recipient_email' => (string) $user->user_email,
				'recipient_name'  => (string) $user->display_name,
				'amount'          => number_format( (float) ( $row['amount'] ?? 0 ), 2, '.', '' ),
				'currency'        => (string) ( $row['currency'] ?? 'ZAR' ),
				'reference'       => 'NGC-PAYOUT-' . (int) ( $row['id'] ?? 0 ),
				'payout_id'       => (int) ( $row['id'] ?? 0 ),
			];
		}
		return $out;
	}

	/**
	 * Preview rows from pending earnings (pre-batch planning).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function preview_from_pending_earnings() {
		global $wpdb;
		$table = NGC_Database::table( 'earnings' );
		if ( ! $table ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$tutor_ids = $wpdb->get_col( "SELECT DISTINCT tutor_user_id FROM {$table} WHERE status = 'pending' AND tutor_user_id > 0" );
		$out       = [];
		foreach ( array_map( 'intval', (array) $tutor_ids ) as $tutor_id ) {
			$pending = class_exists( 'NGC_Reviews' ) ? NGC_Reviews::pending_payout_for_tutor( $tutor_id ) : 0.0;
			if ( $pending <= 0 ) {
				continue;
			}
			$user = get_userdata( $tutor_id );
			if ( ! $user ) {
				continue;
			}
			$out[] = [
				'recipient_email' => (string) $user->user_email,
				'recipient_name'  => (string) $user->display_name,
				'amount'          => number_format( $pending, 2, '.', '' ),
				'currency'        => 'ZAR',
				'reference'       => 'NGC-EARNINGS-' . $tutor_id,
				'payout_id'       => 0,
			];
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Export rows.
	 * @return string CSV body.
	 */
	public static function to_csv( $rows ) {
		$lines = [ implode( ',', self::CSV_HEADERS ) ];
		foreach ( (array) $rows as $row ) {
			$cells = [];
			foreach ( self::CSV_HEADERS as $key ) {
				$cells[] = self::csv_cell( (string) ( $row[ $key ] ?? '' ) );
			}
			$lines[] = implode( ',', $cells );
		}
		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * @param string $value Cell value.
	 * @return string
	 */
	public static function csv_cell( $value ) {
		$value = str_replace( [ "\r", "\n" ], ' ', (string) $value );
		if ( false !== strpos( $value, ',' ) || false !== strpos( $value, '"' ) ) {
			return '"' . str_replace( '"', '""', $value ) . '"';
		}
		return $value;
	}

	/**
	 * @param string $status   Payout status or "preview" for pending earnings.
	 * @param string $filepath Output path.
	 * @return array{path:string,rows:int,bytes:int}|WP_Error
	 */
	public static function write_file( $status = 'pending', $filepath = '' ) {
		$rows = 'preview' === $status ? self::preview_from_pending_earnings() : self::rows_for_status( $status );
		if ( ! $rows ) {
			return new WP_Error( 'ngc_no_payout_rows', __( 'No payout rows to export.', 'nextgencompanion' ) );
		}
		$csv = self::to_csv( $rows );
		if ( ! $filepath ) {
			$upload = wp_upload_dir();
			$dir    = trailingslashit( $upload['basedir'] ) . 'ngc-exports';
			if ( ! wp_mkdir_p( $dir ) ) {
				return new WP_Error( 'ngc_export_dir', __( 'Could not create export directory.', 'nextgencompanion' ) );
			}
			$filepath = $dir . '/payfast-payouts-' . gmdate( 'Ymd-His' ) . '.csv';
		}
		$written = file_put_contents( $filepath, $csv );
		if ( false === $written ) {
			return new WP_Error( 'ngc_export_write', __( 'Could not write export file.', 'nextgencompanion' ) );
		}
		return [
			'path'  => $filepath,
			'rows'  => count( $rows ),
			'bytes' => (int) $written,
		];
	}

	/**
	 * Stream CSV download to browser (admin).
	 *
	 * @param string $status Payout status or preview.
	 */
	public static function send_download( $status = 'pending' ) {
		$rows = 'preview' === $status ? self::preview_from_pending_earnings() : self::rows_for_status( $status );
		if ( ! $rows ) {
			wp_die( esc_html__( 'No payout rows to export.', 'nextgencompanion' ) );
		}
		$filename = 'payfast-payouts-' . gmdate( 'Ymd-His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		echo self::to_csv( $rows );
		exit;
	}
}
