<?php
/**
 * WP-CLI operational commands.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Cli {
	public static function register() {
		WP_CLI::add_command( 'ngtai', __CLASS__ );
	}
	private function output( $value, $format = 'json' ) {
		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( $value, JSON_PRETTY_PRINT ) );
		} else {
			$rows = isset( $value[0] ) ? $value : [ $value ];
			WP_CLI\Utils\format_items( $format, $rows, array_keys( (array) reset( $rows ) ) );
		}
	}
	public function status( $args, $assoc ) {
		$this->output( [ 'config' => NGTAI_Config::public_status(), 'counts' => NGTAI_Delivery_Repository::counts() ], $assoc['format'] ?? 'table' );
	}
	public function verify() {
		$snapshot = NGTAI_Health::snapshot();
		$failures = [];
		foreach ( $snapshot['tables'] as $table => $exists ) {
			if ( ! $exists ) {
				$failures[] = 'missing table: ' . $table;
			}
		}
		foreach ( $snapshot['cron'] as $hook => $next ) {
			if ( ! $next ) {
				$failures[] = 'missing cron: ' . $hook;
			}
		}
		if ( ! NGTAI_Config::configured() ) {
			$failures[] = 'configuration incomplete';
		}
		if ( $failures ) {
			WP_CLI::error( implode( '; ', $failures ) );
		}
		WP_CLI::success( 'NGTAI verification passed.' );
	}
	public function health() {
		$this->output( NGTAI_Health::snapshot() );
	}
	/**
	 * Show public configuration.
	 *
	 * @subcommand config-show
	 */
	public function config_show() {
		$this->output( NGTAI_Config::public_status() );
	}
	public function outbox_list( $args, $assoc ) {
		$this->output( NGTAI_Delivery_Repository::list_recent( [ 'status' => sanitize_key( $assoc['status'] ?? '' ), 'limit' => min( 200, max( 1, absint( $assoc['limit'] ?? 25 ) ) ) ] ), $assoc['format'] ?? 'table' );
	}
	public function outbox_flush( $args, $assoc ) {
		$this->output( NGTAI_Outbox_Bridge::dispatch_batch( min( 200, max( 1, absint( $assoc['limit'] ?? 10 ) ) ) ) );
	}
	public function outbox_retry( $args, $assoc ) {
		$event_id = sanitize_text_field( $assoc['event-id'] ?? '' );
		if ( '' === $event_id ) {
			WP_CLI::error( '--event-id is required.' );
		}
		$row = NGTAI_Delivery_Repository::find_by_event_id( $event_id );
		if ( ! $row ) {
			WP_CLI::error( 'Event not found.' );
		}
		NGTAI_Delivery_Repository::schedule_retry( (int) $row['id'], (int) ( $row['attempt_count'] ?? 0 ) + 1, 'cli_retry', 0, 0 );
		WP_CLI::success( 'Event scheduled for retry.' );
	}
	public function outbox_dead_letter( $args, $assoc ) {
		$assoc['status'] = 'dead_letter';
		$this->outbox_list( $args, $assoc );
	}
	public function nonce_purge() {
		WP_CLI::success( sprintf( 'Purged %d nonce(s).', NGTAI_Nonce_Store::purge_expired() ) );
	}
	public function test_signature() {
		$raw     = '{"fixed":true}';
		$headers = NGTAI_Signature::build_headers(
			'POST',
			'/fixed-vector',
			$raw,
			[ 'idempotency_key' => 'fixed-idempotency', 'correlation_id' => 'fixed-correlation' ]
		);
		$result  = NGTAI_Signature::verify( 'POST', '/fixed-vector', $raw, NGTAI_Signature::normalize_headers( $headers ) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( 'FAIL: ' . $result->get_error_code() );
		}
		WP_CLI::success( 'PASS' );
	}
	public function test_event( $args, $assoc ) {
		$envelope = NGTAI_Event_Mapper::build_envelope(
			[
				'event_type' => 'match.requested',
				'subject_id' => 'cli-dry-run',
				'payload'    => [ 'match_id' => 'cli-dry-run', 'subject' => 'Mathematics', 'grade' => '10' ],
			]
		);
		if ( ! $envelope ) {
			WP_CLI::error( 'Unable to build sample envelope.' );
		}
		if ( empty( $assoc['send'] ) ) {
			$this->output( NGTAI_Logger::scrub( $envelope->to_array() ) );
			return;
		}
		$this->output( NGTAI_Api_Client::post_event( $envelope, 'cli-' . NGTAI_Signature::uuid() ) );
	}
	public function approvals_list( $args, $assoc ) {
		global $wpdb;
		$status = sanitize_key( $assoc['status'] ?? 'pending' );
		$table  = NGTAI_Database::table( 'approvals' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->output( $wpdb->get_results( $wpdb->prepare( "SELECT approval_id,agent_run_id,action_name,status,requested_by,created_at FROM {$table} WHERE status=%s ORDER BY id DESC LIMIT 100", $status ), ARRAY_A ), 'table' );
	}
}
