<?php
/**
 * Domain event envelope for agent-traceable workflows.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emits versioned event envelopes with correlation IDs.
 */
final class NGC_Agent_Event_Envelope {

	/**
	 * Emit a domain event.
	 *
	 * @param string               $type Event type (e.g. FraudSignalRaised).
	 * @param string               $entity_type Entity type.
	 * @param string               $entity_id Entity ID.
	 * @param array<string, mixed> $payload Payload.
	 * @param array<string, mixed> $meta Optional meta (correlation_id, causation_id, classification).
	 * @return array<string, mixed> Envelope.
	 */
	public static function emit( $type, $entity_type, $entity_id, array $payload = [], array $meta = [] ) {
		$envelope = [
			'event_id'          => self::uuid(),
			'event_type'        => sanitize_text_field( $type ),
			'event_version'     => '1.0.0',
			'correlation_id'    => sanitize_text_field( $meta['correlation_id'] ?? self::uuid() ),
			'causation_id'      => sanitize_text_field( $meta['causation_id'] ?? '' ),
			'entity_type'       => sanitize_key( $entity_type ),
			'entity_id'         => (string) $entity_id,
			'actor'             => (int) ( $meta['actor'] ?? get_current_user_id() ),
			'source'            => sanitize_key( $meta['source'] ?? 'companion' ),
			'timestamp'         => gmdate( 'c' ),
			'data_classification'=> sanitize_key( $meta['data_classification'] ?? 'internal' ),
			'payload'           => $payload,
			'processing_status' => 'emitted',
			'retry_count'       => 0,
			'failure_reason'    => '',
		];

		/**
		 * Fires when a governed domain event is emitted.
		 *
		 * @param array<string, mixed> $envelope Event envelope.
		 */
		do_action( 'ngc_domain_event', $envelope );
		do_action( 'ngc_domain_event_' . sanitize_key( $type ), $envelope );

		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info( 'domain_event', $type, [ 'event_id' => $envelope['event_id'], 'entity' => $entity_type . ':' . $entity_id ] );
		}

		// Lightweight outbox for observability (best-effort).
		self::persist_outbox( $envelope );

		return $envelope;
	}

	/**
	 * @param array<string, mixed> $envelope Envelope.
	 */
	private static function persist_outbox( array $envelope ) {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! $wpdb->prefix ) {
			return;
		}
		$table = $wpdb->prefix . 'ngc_event_outbox';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'event_id'   => $envelope['event_id'],
				'event_type' => $envelope['event_type'],
				'payload'    => wp_json_encode( $envelope ),
				'status'     => 'emitted',
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Ensure outbox table exists (called from bridge init).
	 */
	public static function maybe_install_outbox() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'ngc_event_outbox';
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_id varchar(64) NOT NULL DEFAULT '',
				event_type varchar(128) NOT NULL DEFAULT '',
				payload longtext NULL,
				status varchar(32) NOT NULL DEFAULT 'emitted',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY event_id (event_id),
				KEY event_type (event_type)
			) {$charset};"
		);
	}

	/**
	 * @return string
	 */
	private static function uuid() {
		if ( class_exists( 'NGC_Uuid' ) ) {
			return NGC_Uuid::generate();
		}
		return wp_generate_uuid4();
	}
}
