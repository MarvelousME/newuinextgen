<?php
/**
 * WF-03 — session reminder scheduling and delivery.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queues and sends 24h / 1h / 15m session reminders.
 */
class NGC_Session_Reminders {

	const CRON_HOOK = 'ngc_process_reminder_queue';

	/** @var array<string, int> */
	private static $offsets = [
		'24h' => DAY_IN_SECONDS,
		'1h'  => HOUR_IN_SECONDS,
		'15m' => 15 * MINUTE_IN_SECONDS,
	];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedules' ] );
		add_action( 'init', [ __CLASS__, 'ensure_cron' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'process_queue' ] );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow_dispatched' ], 10, 2 );
		add_action( 'ngc_booking_created', [ __CLASS__, 'on_booking_created' ], 10, 1 );
	}

	/**
	 * @param array<string, mixed> $schedules Schedules.
	 * @return array<string, mixed>
	 */
	public static function add_cron_schedules( $schedules ) {
		if ( empty( $schedules['ngc_five_minutes'] ) ) {
			$schedules['ngc_five_minutes'] = [
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 5 minutes (NextGen reminders)', 'nextgencompanion' ),
			];
		}
		if ( empty( $schedules['ngc_monthly'] ) ) {
			$schedules['ngc_monthly'] = [
				'interval' => 28 * DAY_IN_SECONDS,
				'display'  => __( 'Monthly (NextGen payouts)', 'nextgencompanion' ),
			];
		}
		return $schedules;
	}

	/**
	 * Ensure reminder cron is scheduled.
	 */
	public static function ensure_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'ngc_five_minutes', self::CRON_HOOK );
		}
	}

	/**
	 * @param string               $full Full event key.
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_workflow_dispatched( $full, $vars ) {
		if ( 'amelia.booking.created' !== $full && 'ngt.booking.created' !== $full ) {
			return;
		}
		self::queue_for_booking_context( $vars );
	}

	/**
	 * @param array<string, mixed> $context Booking context.
	 */
	public static function on_booking_created( $context ) {
		self::queue_for_booking_context( $context );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 */
	public static function queue_for_booking_context( $context ) {
		$booking_id = (int) ( $context['booking_id'] ?? $context['internal_booking_id'] ?? 0 );
		$starts_at  = (string) ( $context['starts_at'] ?? $context['session_start'] ?? '' );

		if ( $booking_id && class_exists( 'NGC_Bookings' ) ) {
			$row = NGC_Bookings::get( $booking_id );
			if ( $row && empty( $starts_at ) && ! empty( $row->scheduled_at ) ) {
				$starts_at = (string) $row->scheduled_at;
			}
			if ( $row && ! $starts_at ) {
				$starts_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) );
			}
		}

		if ( ! $starts_at ) {
			$starts_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) );
		}

		$session_ts = strtotime( $starts_at );
		if ( ! $session_ts ) {
			return;
		}

		foreach ( self::$offsets as $label => $offset ) {
			$send_at = $session_ts - $offset;
			if ( $send_at <= time() ) {
				continue;
			}
			self::insert_schedule(
				[
					'booking_id'   => $booking_id,
					'reminder_key' => $label,
					'send_at'      => gmdate( 'Y-m-d H:i:s', $send_at ),
					'recipient'    => sanitize_email( (string) ( $context['student_email'] ?? $context['email'] ?? '' ) ),
					'payload'      => wp_json_encode( $context ),
				]
			);
		}

		NGC_Workflows::dispatch( 'reminders.queued', $context );
	}

	/**
	 * @param array<string, mixed> $row Schedule row data.
	 */
	private static function insert_schedule( $row ) {
		global $wpdb;
		$table = NGC_Database::table( 'reminder_schedules' );
		if ( ! $table ) {
			return;
		}
		$booking_id   = (int) ( $row['booking_id'] ?? 0 );
		$reminder_key = sanitize_key( (string) ( $row['reminder_key'] ?? '' ) );
		if ( $booking_id && $reminder_key ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE booking_id = %d AND reminder_key = %s AND status IN ('pending','sent') LIMIT 1",
					$booking_id,
					$reminder_key
				)
			);
			if ( $exists ) {
				return;
			}
		}
		$wpdb->insert(
			$table,
			[
				'booking_id'   => (int) ( $row['booking_id'] ?? 0 ),
				'reminder_key' => sanitize_key( (string) ( $row['reminder_key'] ?? '' ) ),
				'send_at'      => (string) ( $row['send_at'] ?? '' ),
				'recipient'    => sanitize_email( (string) ( $row['recipient'] ?? '' ) ),
				'payload'      => (string) ( $row['payload'] ?? '{}' ),
				'status'       => 'pending',
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Process due reminders.
	 */
	public static function process_queue() {
		global $wpdb;
		$table = NGC_Database::table( 'reminder_schedules' );
		if ( ! $table ) {
			return;
		}
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s AND send_at <= %s ORDER BY send_at ASC LIMIT 25",
				'pending',
				$now
			)
		);
		if ( empty( $rows ) ) {
			return;
		}
		foreach ( $rows as $row ) {
			self::send_reminder( $row );
		}
	}

	/**
	 * @param object $row DB row.
	 */
	private static function send_reminder( $row ) {
		global $wpdb;
		$table   = NGC_Database::table( 'reminder_schedules' );
		$payload = json_decode( (string) ( $row->payload ?? '{}' ), true );
		if ( ! is_array( $payload ) ) {
			$payload = [];
		}

		$recipient = sanitize_email( (string) ( $row->recipient ?? '' ) );
		if ( ! $recipient && ! empty( $payload['student_user_id'] ) ) {
			$user = get_userdata( (int) $payload['student_user_id'] );
			if ( $user ) {
				$recipient = $user->user_email;
			}
		}
		if ( ! $recipient ) {
			$recipient = sanitize_email( (string) get_option( 'admin_email' ) );
		}

		$key_map = [
			'24h' => 'session_reminder_24h',
			'1h'  => 'session_reminder_1h',
			'15m' => 'session_reminder_15m',
		];
		$template = $key_map[ (string) $row->reminder_key ] ?? 'session_reminder_24h';
		$context  = array_merge(
			$payload,
			[
				'reminder_key' => (string) $row->reminder_key,
				'booking_id'   => (string) $row->booking_id,
				'session_start'=> (string) ( $payload['starts_at'] ?? $payload['session_start'] ?? '' ),
			]
		);

		$sent = false;
		if ( class_exists( 'NGC_Email_Adapter' ) ) {
			$email  = new NGC_Email_Adapter();
			$result = $email->create_or_update(
				'send_template',
				[
					'template_key' => $template,
					'to'           => $recipient,
					'context'      => $context,
				]
			);
			$sent = ! empty( $result['ok'] );
		} else {
			$sent = wp_mail(
				$recipient,
				sprintf( __( '[NextGen Tutors] Session reminder (%s)', 'nextgencompanion' ), $row->reminder_key ),
				sprintf(
					__( "Your tutoring session is coming up.\n\nBooking: %s\nReminder: %s\n", 'nextgencompanion' ),
					$row->booking_id,
					$row->reminder_key
				)
			);
		}

		$status = $sent ? 'sent' : 'failed';
		$wpdb->update(
			$table,
			[
				'status'     => $status,
				'sent_at'    => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $row->id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		if ( $sent ) {
			NGC_Workflows::dispatch(
				'reminder.' . $row->reminder_key . '.sent',
				$context
			);
			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log( 'reminder_sent', 'reminder', (int) $row->id, [ 'key' => $row->reminder_key ], 0 );
			}
		} else {
			NGC_Workflows::dispatch( 'notification.failed', $context );
			if ( class_exists( 'NGC_Workflow_Retry_Queue' ) ) {
				NGC_Workflow_Retry_Queue::enqueue(
					'REMINDER_' . strtoupper( (string) $row->reminder_key ),
					$context,
					'reminder',
					'email_send_failed'
				);
			}
		}
	}
}
