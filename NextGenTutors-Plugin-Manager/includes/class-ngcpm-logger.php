<?php
/**
 * Action logging.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores latest plugin manager actions.
 */
class NGCPM_Logger {

	const OPTION = 'ngcpm_action_log';

	/**
	 * @param string               $type    Event type.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function log( $type, $message, $context = [] ) {
		$entry = [
			'id'      => wp_generate_uuid4(),
			'type'    => sanitize_key( $type ),
			'message' => sanitize_text_field( $message ),
			'context' => self::sanitize_context( $context ),
			'user_id' => get_current_user_id(),
			'time'    => gmdate( 'c' ),
		];

		$log = get_option( self::OPTION, [] );
		if ( ! is_array( $log ) ) {
			$log = [];
		}
		array_unshift( $log, $entry );
		$log = array_slice( $log, 0, (int) NGCPM_LOG_LIMIT );
		update_option( self::OPTION, $log, false );

		do_action( 'ngcpm_action_logged', $type, $message, $context );
	}

	/**
	 * @param mixed $context Raw context.
	 * @return array<string, string>
	 */
	private static function sanitize_context( $context ) {
		if ( ! is_array( $context ) ) {
			return [];
		}
		$clean = [];
		foreach ( $context as $key => $value ) {
			$clean[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
		}
		return $clean;
	}

	/**
	 * @param int $limit Max entries.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( $limit = 100 ) {
		$log = get_option( self::OPTION, [] );
		if ( ! is_array( $log ) ) {
			return [];
		}
		return array_slice( $log, 0, max( 1, (int) $limit ) );
	}

	/**
	 * Clear all logs.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}

	/**
	 * Export logs as JSON download.
	 */
	public static function export_json() {
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ngcpm-log-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( self::recent( NGCPM_LOG_LIMIT ), JSON_PRETTY_PRINT );
		exit;
	}
}
