<?php
/**
 * Demo notification sandbox log (Phase 14 §14.16).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records sandbox notification deliveries with correlation metadata.
 */
final class NGC_Demo_Notifications {

	public const OPTION_LOG = 'ngc_demo_notification_log';

	/**
	 * @param array<string, mixed> $row Row.
	 * @return int Log size.
	 */
	public static function record( $row ) {
		$log = get_option( self::OPTION_LOG, [] );
		if ( ! is_array( $log ) ) {
			$log = [];
		}
		$entry = [
			'id'              => 'NGT-DEMO-N' . str_pad( (string) ( count( $log ) + 1 ), 4, '0', STR_PAD_LEFT ),
			'timestamp'       => gmdate( 'c', class_exists( 'NGC_Demo_Clock' ) ? NGC_Demo_Clock::now() : time() ),
			'channel'         => sanitize_key( (string) ( $row['channel'] ?? 'in_app' ) ),
			'template'        => sanitize_text_field( (string) ( $row['template'] ?? '' ) ),
			'template_version'=> (string) ( $row['template_version'] ?? '1' ),
			'recipient'       => sanitize_text_field( (string) ( $row['recipient'] ?? '' ) ),
			'subject'         => sanitize_text_field( (string) ( $row['subject'] ?? '' ) ),
			'status'          => sanitize_key( (string) ( $row['status'] ?? 'dispatched' ) ),
			'delivery_status' => sanitize_key( (string) ( $row['delivery_status'] ?? 'sandbox_ok' ) ),
			'source_event'    => sanitize_text_field( (string) ( $row['source_event'] ?? $row['source'] ?? '' ) ),
			'correlation_id'  => sanitize_text_field( (string) ( $row['correlation_id'] ?? wp_generate_uuid4() ) ),
			'consent_result'  => sanitize_key( (string) ( $row['consent_result'] ?? 'granted' ) ),
			'attempt_count'   => (int) ( $row['attempt_count'] ?? 1 ),
			'provider_ref'    => sanitize_text_field( (string) ( $row['provider_ref'] ?? 'sandbox' ) ),
			'variables'       => is_array( $row['variables'] ?? null ) ? $row['variables'] : [],
			'is_demo'         => true,
			'demo_seed_version'=> class_exists( 'NGC_Demo_Env' ) ? NGC_Demo_Env::SEED_VERSION : '',
		];
		$log[] = $entry;
		// Cap log size.
		if ( count( $log ) > 500 ) {
			$log = array_slice( $log, -500 );
		}
		update_option( self::OPTION_LOG, $log, false );
		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info(
				'demo',
				'notification',
				'Sandbox notification: ' . $entry['template'],
				$entry
			);
		}
		return count( $log );
	}

	/**
	 * Emit a notification from a domain event (called by seeder after real dispatch).
	 *
	 * @param string               $template Template key.
	 * @param string               $recipient Recipient.
	 * @param string               $source_event Source event.
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function emit( $template, $recipient, $source_event, $vars = [] ) {
		self::record(
			[
				'channel'      => 'email',
				'template'     => $template,
				'recipient'    => $recipient,
				'source_event' => $source_event,
				'variables'    => $vars,
				'status'       => 'dispatched',
				'subject'      => str_replace( '-', ' ', $template ),
			]
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		$log = get_option( self::OPTION_LOG, [] );
		return is_array( $log ) ? $log : [];
	}

	/**
	 * Clear demo notification log.
	 */
	public static function clear() {
		delete_option( self::OPTION_LOG );
	}
}
