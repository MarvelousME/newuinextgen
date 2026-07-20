<?php
/**
 * WP-CLI commands.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI integration.
 */
class NGCPM_CLI {

	/**
	 * Register commands when WP-CLI is available.
	 */
	public static function init() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		WP_CLI::add_command( 'ngcpm test-buttons', [ __CLASS__, 'test_buttons' ] );
	}

	/**
	 * Audit UI button wiring.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngcpm test-buttons
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, string> $assoc_args Associative args.
	 */
	public static function test_buttons( $args, $assoc_args ) {
		unset( $args, $assoc_args );

		$rows    = NGCPM_Buttons::audit();
		$broken  = 0;
		$headers = [ 'Label', 'Endpoint', 'Handler', 'Nonce', 'Capability', 'Status' ];

		$table = [];
		foreach ( $rows as $row ) {
			$table[] = [
				$row['label'],
				$row['endpoint'],
				$row['handler'] ?? 'N/A',
				$row['nonce'],
				$row['capability'],
				$row['status'],
			];
			if ( in_array( $row['status'], [ 'BROKEN', 'MISSING_HANDLER', 'MISSING_BACKEND', 'FAKE_RESPONSE' ], true ) ) {
				++$broken;
			}
		}

		WP_CLI\Utils\format_items( 'table', $table, $headers );

		if ( $broken > 0 ) {
			WP_CLI::error( sprintf( '%d required button(s) broken.', $broken ) );
		}
		WP_CLI::success( 'All required buttons wired.' );
	}
}
