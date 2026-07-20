<?php
/**
 * Export format writers — CSV, JSON, PDF, Excel.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format-specific export writers.
 */
class NGC_Export_Formats {

	/**
	 * @param array<int, array<string, mixed>> $rows    Data rows.
	 * @param string[]                         $columns Column keys.
	 * @param string                           $format  Format slug.
	 * @return string File contents.
	 */
	public static function render( $rows, $columns, $format ) {
		switch ( strtolower( $format ) ) {
			case 'json':
				return self::to_json( $rows, $columns );
			case 'pdf':
				return self::to_pdf( $rows, $columns );
			case 'excel':
			case 'xlsx':
				return self::to_excel( $rows, $columns );
			case 'csv':
			default:
				return self::to_csv( $rows, $columns );
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $rows    Rows.
	 * @param string[]                         $columns Columns.
	 * @return string
	 */
	public static function to_csv( $rows, $columns ) {
		$fh = fopen( 'php://temp', 'r+' );
		if ( ! $fh ) {
			return '';
		}
		fputcsv( $fh, $columns );
		foreach ( $rows as $row ) {
			$line = [];
			foreach ( $columns as $col ) {
				$line[] = self::cell_value( $row, $col );
			}
			fputcsv( $fh, $line );
		}
		rewind( $fh );
		$out = stream_get_contents( $fh );
		fclose( $fh );
		return (string) $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $rows    Rows.
	 * @param string[]                         $columns Columns.
	 * @return string
	 */
	public static function to_json( $rows, $columns ) {
		$filtered = [];
		foreach ( $rows as $row ) {
			$item = [];
			foreach ( $columns as $col ) {
				$item[ $col ] = self::cell_value( $row, $col );
			}
			$filtered[] = $item;
		}
		return wp_json_encode( $filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Minimal PDF table export.
	 *
	 * @param array<int, array<string, mixed>> $rows    Rows.
	 * @param string[]                         $columns Columns.
	 * @return string
	 */
	public static function to_pdf( $rows, $columns ) {
		$lines   = [ 'NextGen Platform Export', 'Generated: ' . gmdate( 'c' ), '' ];
		$lines[] = implode( ' | ', $columns );
		$lines[] = str_repeat( '-', 80 );
		foreach ( $rows as $row ) {
			$cells = [];
			foreach ( $columns as $col ) {
				$cells[] = self::cell_value( $row, $col );
			}
			$lines[] = implode( ' | ', $cells );
		}
		$text = implode( "\n", $lines );
		return self::simple_pdf( $text );
	}

	/**
	 * Excel 2003 XML (SpreadsheetML).
	 *
	 * @param array<int, array<string, mixed>> $rows    Rows.
	 * @param string[]                         $columns Columns.
	 * @return string
	 */
	public static function to_excel( $rows, $columns ) {
		$xml  = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
		$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
		$xml .= '<Worksheet ss:Name="Export"><Table>';
		$xml .= '<Row>';
		foreach ( $columns as $col ) {
			$xml .= '<Cell><Data ss:Type="String">' . esc_xml( $col ) . '</Data></Cell>';
		}
		$xml .= '</Row>';
		foreach ( $rows as $row ) {
			$xml .= '<Row>';
			foreach ( $columns as $col ) {
				$val  = self::cell_value( $row, $col );
				$type = is_numeric( $val ) ? 'Number' : 'String';
				$xml .= '<Cell><Data ss:Type="' . $type . '">' . esc_xml( (string) $val ) . '</Data></Cell>';
			}
			$xml .= '</Row>';
		}
		$xml .= '</Table></Worksheet></Workbook>';
		return $xml;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $col Column.
	 * @return string
	 */
	private static function cell_value( $row, $col ) {
		$val = $row[ $col ] ?? '';
		if ( is_array( $val ) || is_object( $val ) ) {
			return wp_json_encode( $val );
		}
		return (string) $val;
	}

	/**
	 * @param string $text Plain text.
	 * @return string
	 */
	private static function simple_pdf( $text ) {
		$lines = explode( "\n", $text );
		$y     = 750;
		$stream = "BT\n/F1 10 Tf\n";
		foreach ( $lines as $line ) {
			$safe = str_replace( [ '\\', '(', ')' ], [ '\\\\', '\\(', '\\)' ], substr( $line, 0, 120 ) );
			$stream .= "50 {$y} Td ({$safe}) Tj\n0 -14 Td\n";
			$y -= 14;
			if ( $y < 50 ) {
				break;
			}
		}
		$stream .= "ET";
		$len = strlen( $stream );
		return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>>>>>>>endobj\n4 0 obj<</Length {$len}>>stream\n{$stream}\nendstream endobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000270 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n" . ( 350 + $len ) . "\n%%EOF";
	}

	/**
	 * @param string $format Format slug.
	 * @return string
	 */
	public static function mime_type( $format ) {
		$map = [
			'csv'   => 'text/csv',
			'json'  => 'application/json',
			'pdf'   => 'application/pdf',
			'excel' => 'application/vnd.ms-excel',
			'xlsx'  => 'application/vnd.ms-excel',
		];
		return $map[ strtolower( $format ) ] ?? 'application/octet-stream';
	}

	/**
	 * @param string $format Format slug.
	 * @return string
	 */
	public static function extension( $format ) {
		$map = [
			'csv'   => 'csv',
			'json'  => 'json',
			'pdf'   => 'pdf',
			'excel' => 'xls',
			'xlsx'  => 'xls',
		];
		return $map[ strtolower( $format ) ] ?? 'dat';
	}
}
