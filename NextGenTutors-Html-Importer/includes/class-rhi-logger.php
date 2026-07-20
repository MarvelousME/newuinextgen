<?php
/**
 * Import action logger.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistent import log stored in options.
 */
class RHI_Logger {

	const OPTION_KEY = 'rhi_import_log';

	/**
	 * @param string               $level   info|warning|error.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function log( $level, $message, $context = [] ) {
		$entries   = get_option( self::OPTION_KEY, [] );
		$entries[] = [
			'time'    => gmdate( 'c' ),
			'level'   => sanitize_key( $level ),
			'message' => sanitize_text_field( $message ),
			'context' => $context,
		];
		if ( count( $entries ) > 500 ) {
			$entries = array_slice( $entries, -500 );
		}
		update_option( self::OPTION_KEY, $entries, false );
	}

	/** @return array<int, array<string, mixed>> */
	public static function get_log() {
		$log = get_option( self::OPTION_KEY, [] );
		return is_array( $log ) ? $log : [];
	}

	public static function clear() {
		delete_option( self::OPTION_KEY );
	}

	/**
	 * @param array<string, mixed> $report Report data.
	 */
	public static function save_report( $report ) {
		update_option( 'rhi_last_import_report', $report, false );
	}

	/** @return array<string, mixed> */
	public static function get_report() {
		$report = get_option( 'rhi_last_import_report', [] );
		return is_array( $report ) ? $report : [];
	}
}
