<?php
/**
 * WP-CLI: durable queue worker, DLQ replay, audit verify.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * wp ngc queue *
 */
class NGC_Queue_CLI {

	/**
	 * Process durable queue messages.
	 *
	 * ## OPTIONS
	 *
	 * [--max=<n>]
	 * : Max messages (default 25).
	 *
	 * [--queue=<name>]
	 * : Single queue name (default all bulkheads).
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc queue work
	 *     wp ngc queue work --max=50 --queue=workflow
	 *
	 * @param array $args       Positional.
	 * @param array $assoc_args Flags.
	 */
	public function work( $args, $assoc_args ) {
		unset( $args );
		if ( ! class_exists( 'NGC_Queue_Worker' ) ) {
			WP_CLI::error( 'Queue worker not loaded.' );
		}
		NGC_Queue_Worker::mark_cli_alive( 180 );
		$opts = [
			'max_messages' => isset( $assoc_args['max'] ) ? (int) $assoc_args['max'] : 25,
		];
		if ( ! empty( $assoc_args['queue'] ) ) {
			$opts['queues'] = [ sanitize_key( (string) $assoc_args['queue'] ) ];
		}
		$result = NGC_Queue_Worker::work( $opts );
		WP_CLI::success( sprintf( 'processed=%d acked=%d nacked=%d', $result['processed'], $result['acked'], $result['nacked'] ) );
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Show queue + DLQ stats.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc queue stats
	 */
	public function stats( $args, $assoc_args ) {
		unset( $args, $assoc_args );
		if ( ! class_exists( 'NGC_Durable_Queue' ) ) {
			WP_CLI::error( 'Durable queue not loaded.' );
		}
		WP_CLI::line( wp_json_encode( NGC_Durable_Queue::stats(), JSON_PRETTY_PRINT ) );
	}

	/**
	 * Replay a DLQ entry.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : DLQ row id.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc queue dlq-replay 12
	 *
	 * @subcommand dlq-replay
	 *
	 * @param array $args Positional.
	 */
	public function dlq_replay( $args ) {
		$id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $id <= 0 ) {
			WP_CLI::error( 'Provide DLQ id.' );
		}
		$mid = NGC_Queue_DLQ::replay( $id );
		if ( is_wp_error( $mid ) ) {
			WP_CLI::error( $mid->get_error_message() );
		}
		WP_CLI::success( 'Replayed as message ' . $mid );
	}
}

/**
 * wp ngc audit *
 */
class NGC_Audit_CLI {

	/**
	 * Verify immutable audit hash chain.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc audit verify
	 */
	public function verify( $args, $assoc_args ) {
		unset( $args, $assoc_args );
		if ( ! class_exists( 'NGC_Immutable_Audit' ) ) {
			WP_CLI::error( 'Immutable audit not loaded.' );
		}
		$result = NGC_Immutable_Audit::verify();
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
		if ( empty( $result['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
		WP_CLI::success( 'Audit chain OK (' . (int) $result['checked'] . ' events)' );
	}

	/**
	 * Export WORM evidence bundle.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ngc audit worm-export
	 *
	 * @subcommand worm-export
	 */
	public function worm_export( $args, $assoc_args ) {
		unset( $args );
		$out = NGC_Worm_Export::export(
			[
				'legal_hold' => ! empty( $assoc_args['legal-hold'] ),
				'label'      => isset( $assoc_args['label'] ) ? (string) $assoc_args['label'] : null,
			]
		);
		if ( is_wp_error( $out ) ) {
			WP_CLI::error( $out->get_error_message() );
		}
		WP_CLI::success( 'WORM export written' );
		WP_CLI::line( wp_json_encode( $out, JSON_PRETTY_PRINT ) );
	}
}

WP_CLI::add_command( 'ngc queue', 'NGC_Queue_CLI' );
WP_CLI::add_command( 'ngc audit', 'NGC_Audit_CLI' );
