<?php
/**
 * Parse and surface accurate AJAX / fatal error messages.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Human-readable errors for Plugin Manager AJAX.
 */
class NGCPM_Errors {

	const OPTION_LAST_FATAL = 'ngcpm_last_fatal_error';

	/**
	 * Strip HTML and normalize whitespace.
	 *
	 * @param string $message Raw message.
	 * @return string
	 */
	public static function clean( $message ) {
		$message = wp_strip_all_tags( html_entity_decode( (string) $message, ENT_QUOTES, 'UTF-8' ) );
		return trim( preg_replace( '/\s+/', ' ', $message ) );
	}

	/**
	 * Extract a useful message from a non-JSON admin-ajax body.
	 *
	 * @param string $text Response body.
	 * @return string
	 */
	public static function parse_html_response( $text ) {
		$text = (string) $text;
		if ( '' === $text ) {
			return __( 'Empty server response — the request may have timed out or PHP crashed.', 'nextgentutors-plugin-manager' );
		}

		if ( preg_match( '/Fatal error:\s*.+? on line \d+/i', $text, $m ) ) {
			return self::clean( $m[0] );
		}

		if ( preg_match( '/Uncaught [^:]+: .+? in .+? on line \d+/i', $text, $m ) ) {
			return self::clean( $m[0] );
		}

		if ( preg_match( '/Parse error:\s*.+? on line \d+/i', $text, $m ) ) {
			return self::clean( $m[0] );
		}

		if ( false !== stripos( $text, 'critical error' ) ) {
			$stored = get_option( self::OPTION_LAST_FATAL, [] );
			if ( is_array( $stored ) && ! empty( $stored['message'] ) ) {
				$plugin = ! empty( $stored['plugin'] ) ? ' (' . $stored['plugin'] . ')' : '';
				return self::clean( $stored['message'] . $plugin );
			}
			return __( 'WordPress fatal error during this request. Open Diagnostics → Exception Logs or wp-content/debug.log for the stack trace.', 'nextgentutors-plugin-manager' );
		}

		if ( preg_match( '/"message"\s*:\s*"([^"]+)"/', $text, $m ) ) {
			return self::clean( stripcslashes( $m[1] ) );
		}

		$snippet = self::clean( mb_substr( $text, 0, 220 ) );
		return sprintf(
			/* translators: %s: response snippet */
			__( 'Unexpected server response: %s', 'nextgentutors-plugin-manager' ),
			$snippet
		);
	}

	/**
	 * Register shutdown handler for fatals during plugin operations.
	 *
	 * @param string $context install|activate|uninstall.
	 * @param string $slug    Registry slug when known.
	 */
	public static function begin_guard( $context = '', $slug = '' ) {
		$GLOBALS['ngcpm_error_guard'] = [
			'context' => $context,
			'slug'    => $slug,
		];
		if ( ! has_action( 'shutdown', [ __CLASS__, 'shutdown_guard' ] ) ) {
			add_action( 'shutdown', [ __CLASS__, 'shutdown_guard' ], 0 );
		}
	}

	/**
	 * Persist last fatal for later UI display.
	 */
	public static function shutdown_guard() {
		$err = error_get_last();
		if ( ! $err || ! in_array( (int) $err['type'], [ E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR ], true ) ) {
			return;
		}

		$guard  = $GLOBALS['ngcpm_error_guard'] ?? [];
		$record = [
			'time'    => gmdate( 'c' ),
			'message' => self::clean( $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line'] ),
			'context' => (string) ( $guard['context'] ?? '' ),
			'plugin'  => (string) ( $guard['slug'] ?? '' ),
		];
		update_option( self::OPTION_LAST_FATAL, $record, false );
		NGCPM_Logger::log( 'fatal_error', $record['message'], $record );
	}

	/**
	 * Build activation failure message with dependency hints.
	 *
	 * @param string         $slug Registry slug.
	 * @param string|WP_Error $error Error.
	 * @return string
	 */
	public static function activation_message( $slug, $error ) {
		$def  = NGCPM_Registry::get( $slug );
		$name = (string) ( $def['name'] ?? $slug );
		$msg  = is_wp_error( $error ) ? $error->get_error_message() : (string) $error;
		$msg  = self::clean( $msg );

		if ( empty( $def['required'] ) ) {
			$msg .= ' ' . __( 'This plugin is optional — deactivate or uninstall it from Plugin Discovery if you do not need it.', 'nextgentutors-plugin-manager' );
		}

		$deps = NGCPM_Registry::depends_on( $slug );
		$deps = array_filter( $deps, static function ( $d ) {
			return 'core' !== $d;
		} );
		if ( $deps ) {
			$msg .= ' ' . sprintf(
				/* translators: %s: comma-separated plugin slugs */
				__( 'Required dependencies: %s.', 'nextgentutors-plugin-manager' ),
				implode( ', ', $deps )
			);
		}

		return sprintf(
			/* translators: 1: plugin name 2: error detail */
			__( 'Could not activate %1$s: %2$s', 'nextgentutors-plugin-manager' ),
			$name,
			$msg
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function last_fatal() {
		$row = get_option( self::OPTION_LAST_FATAL, [] );
		return is_array( $row ) ? $row : [];
	}
}
