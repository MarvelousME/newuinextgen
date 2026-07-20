<?php
/**
 * Fraud / abuse signal engine — configurable rules, cases, approval-gated holds.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects red-flag signals and opens reviewable fraud cases.
 */
final class NGC_Fraud_Engine {

	public const OPTION_RULES = 'ngc_fraud_rules';
	public const DB_VERSION   = '1.1.0';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_install' ], 6 );
		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 20, 1 );
		add_action( 'wp_login_failed', [ __CLASS__, 'on_login_failed' ], 20, 1 );
		add_action( 'updated_user_meta', [ __CLASS__, 'on_user_meta' ], 20, 4 );
		add_action( 'ngc_booking_created', [ __CLASS__, 'on_booking_created' ], 20, 1 );
		add_action( 'ngc_review_submitted', [ __CLASS__, 'on_review_submitted' ], 20, 1 );
		add_action( 'ngc_referral_converted', [ __CLASS__, 'on_referral' ], 20, 2 );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow' ], 20, 2 );
		add_action( 'ngt_automation_event_fired', [ __CLASS__, 'on_hub_event' ], 25, 2 );
		add_action( 'woocommerce_order_status_refunded', [ __CLASS__, 'on_refund' ], 20, 1 );
	}

	public static function maybe_install() {
		$ver = get_option( 'ngc_fraud_db_version', '' );
		if ( version_compare( (string) $ver, self::DB_VERSION, '<' ) ) {
			self::install();
			update_option( 'ngc_fraud_db_version', self::DB_VERSION, false );
		}
		$existing = get_option( self::OPTION_RULES, false );
		if ( false === $existing || ! is_array( $existing ) ) {
			update_option( self::OPTION_RULES, self::default_rules(), false );
		} else {
			update_option( self::OPTION_RULES, array_merge( self::default_rules(), $existing ), false );
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$signals = $wpdb->prefix . 'ngc_fraud_signals';
		$cases   = $wpdb->prefix . 'ngc_fraud_cases';

		dbDelta(
			"CREATE TABLE {$signals} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				signal_key varchar(64) NOT NULL DEFAULT '',
				entity_type varchar(64) NOT NULL DEFAULT '',
				entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
				severity decimal(5,2) NOT NULL DEFAULT 0.00,
				evidence longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY signal_key (signal_key),
				KEY entity (entity_type, entity_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$cases} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				status varchar(32) NOT NULL DEFAULT 'open',
				severity varchar(16) NOT NULL DEFAULT 'medium',
				title varchar(191) NOT NULL DEFAULT '',
				entity_type varchar(64) NOT NULL DEFAULT '',
				entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
				score decimal(5,2) NOT NULL DEFAULT 0.00,
				evidence longtext NULL,
				resolution varchar(64) NOT NULL DEFAULT '',
				assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
				notes longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY status (status)
			) {$charset};"
		);
	}

	/**
	 * Master-directive fraud rule set (thresholds tunable via option).
	 *
	 * @return array<string, array{threshold: float, action: string, weight: float}>
	 */
	public static function default_rules() {
		return [
			'registration_velocity'   => [ 'threshold' => 5, 'action' => 'require_verification', 'weight' => 25 ],
			'duplicate_email_domain'  => [ 'threshold' => 3, 'action' => 'log_only', 'weight' => 15 ],
			'duplicate_user'          => [ 'threshold' => 1, 'action' => 'require_verification', 'weight' => 35 ],
			'payment_failure_spike'   => [ 'threshold' => 3, 'action' => 'hold_booking', 'weight' => 40 ],
			'payout_detail_change'    => [ 'threshold' => 1, 'action' => 'hold_payout', 'weight' => 50 ],
			'booking_velocity'        => [ 'threshold' => 10, 'action' => 'warn', 'weight' => 30 ],
			'login_anomaly'           => [ 'threshold' => 8, 'action' => 'require_mfa', 'weight' => 35 ],
			'ip_anomaly'              => [ 'threshold' => 5, 'action' => 'warn', 'weight' => 20 ],
			'refund_abuse'            => [ 'threshold' => 3, 'action' => 'hold_booking', 'weight' => 45 ],
			'referral_abuse'          => [ 'threshold' => 5, 'action' => 'warn', 'weight' => 25 ],
			'fake_review_ring'        => [ 'threshold' => 4, 'action' => 'log_only', 'weight' => 30 ],
			'completion_manipulation' => [ 'threshold' => 8, 'action' => 'hold_payout', 'weight' => 45 ],
			'device_reuse'            => [ 'threshold' => 4, 'action' => 'require_verification', 'weight' => 30 ],
			'off_platform_payment'    => [ 'threshold' => 1, 'action' => 'escalate_compliance', 'weight' => 55 ],
			'harassment_signal'       => [ 'threshold' => 1, 'action' => 'escalate_safeguarding', 'weight' => 60 ],
		];
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function rules() {
		$rules = get_option( self::OPTION_RULES, [] );
		return is_array( $rules ) ? array_merge( self::default_rules(), $rules ) : self::default_rules();
	}

	/**
	 * Record a signal and optionally open a case.
	 *
	 * @param string               $signal_key Signal.
	 * @param string               $entity_type Entity type.
	 * @param int                  $entity_id Entity ID.
	 * @param array<string, mixed> $evidence Evidence.
	 * @return int Signal ID.
	 */
	public static function raise_signal( $signal_key, $entity_type, $entity_id, array $evidence = [] ) {
		global $wpdb;
		$rules  = self::rules();
		$weight = isset( $rules[ $signal_key ]['weight'] ) ? (float) $rules[ $signal_key ]['weight'] : 10.0;

		$wpdb->insert(
			$wpdb->prefix . 'ngc_fraud_signals',
			[
				'signal_key'  => sanitize_key( $signal_key ),
				'entity_type' => sanitize_key( $entity_type ),
				'entity_id'   => (int) $entity_id,
				'severity'   => $weight,
				'evidence'    => wp_json_encode( $evidence ),
			],
			[ '%s', '%s', '%d', '%f', '%s' ]
		);
		$signal_id = (int) $wpdb->insert_id;

		if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit(
				'FraudSignalRaised',
				'fraud',
				(string) $entity_id,
				[
					'signal_key' => $signal_key,
					'severity'  => $weight,
					'evidence'   => $evidence,
				]
			);
		}

		$action = $rules[ $signal_key ]['action'] ?? 'log_only';
		if ( in_array( $action, [ 'hold_payout', 'hold_booking', 'require_verification', 'require_mfa', 'escalate_compliance', 'escalate_safeguarding' ], true ) || $weight >= 40 ) {
			self::create_case(
				[
					'title'       => sprintf( 'Fraud signal: %s', $signal_key ),
					'entity_type' => $entity_type,
					'entity_id'   => $entity_id,
					'score'       => $weight,
					'severity'   => $weight >= 40 ? 'high' : 'medium',
					'evidence'    => array_merge( $evidence, [ 'signal_id' => $signal_id, 'recommended_action' => $action ] ),
					'fraud'       => true,
				]
			);

			if ( 'escalate_safeguarding' === $action && class_exists( 'NGC_Safeguarding' ) ) {
				NGC_Safeguarding::create_case(
					[
						'summary'         => sprintf( 'Fraud→safeguarding: %s', $signal_key ),
						'priority'        => 'high',
						'subject_user_id' => (int) $entity_id,
						'ai_signal'       => true,
						'source'          => 'fraud_engine',
						'details'         => wp_json_encode( $evidence ),
					]
				);
			}

			if ( in_array( $action, [ 'hold_payout', 'hold_booking', 'escalate_compliance' ], true ) && class_exists( 'NGC_Agent_Control_Plane' ) ) {
				$policy_action = 'hold_payout' === $action ? 'finance.payout.release' : 'agent.case.create';
				NGC_Agent_Control_Plane::request_action(
					'fraud-detection',
					$policy_action,
					[
						'fraud'            => true,
						'recommended_hold' => $action,
						'signal_id'        => $signal_id,
						'entity_id'        => $entity_id,
					]
				);
			}
		}

		return $signal_id;
	}

	/**
	 * @param array<string, mixed> $data Case data.
	 * @return int Case ID.
	 */
	public static function create_case( array $data ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'ngc_fraud_cases',
			[
				'status'      => 'open',
				'severity'    => sanitize_key( $data['severity'] ?? 'medium' ),
				'title'       => sanitize_text_field( $data['title'] ?? 'Fraud case' ),
				'entity_type' => sanitize_key( $data['entity_type'] ?? '' ),
				'entity_id'   => (int) ( $data['entity_id'] ?? 0 ),
				'score'       => (float) ( $data['score'] ?? 0 ),
				'evidence'    => wp_json_encode( $data['evidence'] ?? $data ),
				'updated_at'  => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s' ]
		);
		$id = (int) $wpdb->insert_id;
		if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit( 'FraudCaseOpened', 'fraud_case', (string) $id, [ 'title' => $data['title'] ?? '' ] );
		}
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'fraud_case_created', 'fraud', $id, $data, get_current_user_id() );
		}
		return $id;
	}

	/**
	 * @param int    $case_id    Case.
	 * @param string $resolution Resolution.
	 * @param string $note       Note.
	 * @return bool
	 */
	public static function resolve_case( $case_id, $resolution = 'false_positive', $note = '' ) {
		global $wpdb;
		$ok = false !== $wpdb->update(
			$wpdb->prefix . 'ngc_fraud_cases',
			[
				'status'     => 'resolved',
				'resolution' => sanitize_key( $resolution ),
				'notes'      => sanitize_textarea_field( $note ),
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $case_id ],
			[ '%s', '%s', '%s', '%s' ],
			[ '%d' ]
		);
		if ( $ok && class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit( 'FraudCaseResolved', 'fraud_case', (string) $case_id, [ 'resolution' => $resolution ] );
		}
		if ( $ok && class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'fraud_case_resolved', 'fraud', (int) $case_id, [ 'resolution' => $resolution ], get_current_user_id() );
		}
		return (bool) $ok;
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, object>
	 */
	public static function query_cases( array $args = [] ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'ngc_fraud_cases';
		$limit  = min( 100, max( 1, (int) ( $args['limit'] ?? 50 ) ) );
		$status = sanitize_key( $args['status'] ?? 'open' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT {$limit}", $status ) );
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Increment a velocity counter; raise signal when threshold hit.
	 *
	 * @param string $signal_key Rule key.
	 * @param string $bucket     Transient bucket.
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id Entity.
	 * @param array  $evidence Evidence.
	 * @param int    $ttl TTL seconds.
	 */
	private static function bump_velocity( $signal_key, $bucket, $entity_type, $entity_id, array $evidence = [], $ttl = HOUR_IN_SECONDS ) {
		$count = (int) get_transient( $bucket );
		set_transient( $bucket, $count + 1, $ttl );
		$thr = (float) ( self::rules()[ $signal_key ]['threshold'] ?? 5 );
		if ( ( $count + 1 ) >= $thr ) {
			self::raise_signal( $signal_key, $entity_type, (int) $entity_id, array_merge( $evidence, [ 'count' => $count + 1 ] ) );
		}
	}

	/**
	 * @param int $user_id New user.
	 */
	public static function on_user_register( $user_id ) {
		self::bump_velocity( 'registration_velocity', 'ngc_fraud_reg_' . gmdate( 'YmdH' ), 'user', (int) $user_id, [ 'hourly' => true ] );

		$user = get_userdata( (int) $user_id );
		if ( ! $user || ! $user->user_email ) {
			return;
		}
		$parts = explode( '@', strtolower( $user->user_email ) );
		$domain = $parts[1] ?? '';
		if ( $domain && ! in_array( $domain, [ 'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com' ], true ) ) {
			self::bump_velocity( 'duplicate_email_domain', 'ngc_fraud_dom_' . md5( $domain ) . '_' . gmdate( 'Ymd' ), 'user', (int) $user_id, [ 'domain' => $domain ], DAY_IN_SECONDS );
		}

		// Near-duplicate display names / emails in last hour.
		$similar = get_users(
			[
				'search'         => substr( $user->user_login, 0, 5 ) . '*',
				'search_columns' => [ 'user_login' ],
				'number'         => 10,
				'exclude'        => [ (int) $user_id ],
			]
		);
		if ( count( $similar ) >= (int) ( self::rules()['duplicate_user']['threshold'] ?? 1 ) ) {
			self::raise_signal( 'duplicate_user', 'user', (int) $user_id, [ 'similar_logins' => count( $similar ) ] );
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip ) {
			self::bump_velocity( 'ip_anomaly', 'ngc_fraud_ip_' . md5( $ip ) . '_' . gmdate( 'YmdH' ), 'user', (int) $user_id, [ 'ip' => $ip ] );
			$fp = isset( $_SERVER['HTTP_USER_AGENT'] ) ? md5( $ip . '|' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
			if ( $fp ) {
				self::bump_velocity( 'device_reuse', 'ngc_fraud_dev_' . $fp . '_' . gmdate( 'Ymd' ), 'user', (int) $user_id, [ 'fingerprint' => $fp ], DAY_IN_SECONDS );
			}
		}
	}

	/**
	 * @param string $username Failed login.
	 */
	public static function on_login_failed( $username ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		self::bump_velocity( 'login_anomaly', 'ngc_fraud_login_' . md5( $ip . '|' . $username ), 'user', 0, [ 'username' => $username, 'ip' => $ip ] );
	}

	/**
	 * @param int $user_id User.
	 * @param string $meta_key Key.
	 * @param mixed  $meta_value Value.
	 * @param int    $meta_id Meta ID (unused; signature for hook).
	 */
	public static function on_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
		unset( $meta_id, $meta_value );
		$key = strtolower( (string) $meta_key );
		if ( ! preg_match( '/(bank|payout|iban|account_number|payfast|wallet)/', $key ) ) {
			return;
		}
		self::raise_signal( 'payout_detail_change', 'user', (int) $user_id, [ 'meta_key' => $meta_key ] );
	}

	/**
	 * @param int|array<string,mixed> $booking Booking id or context.
	 */
	public static function on_booking_created( $booking ) {
		$booking_id = is_array( $booking ) ? (int) ( $booking['booking_id'] ?? $booking['id'] ?? 0 ) : (int) $booking;
		$user_id    = get_current_user_id();
		self::bump_velocity( 'booking_velocity', 'ngc_fraud_book_' . $user_id . '_' . gmdate( 'YmdH' ), 'booking', $booking_id ?: $user_id, [ 'user_id' => $user_id ] );
	}

	/**
	 * @param array<string,mixed>|int $review Review payload.
	 */
	public static function on_review_submitted( $review ) {
		$user_id = get_current_user_id();
		self::bump_velocity( 'fake_review_ring', 'ngc_fraud_rev_' . $user_id . '_' . gmdate( 'Ymd' ), 'review', $user_id, is_array( $review ) ? $review : [], DAY_IN_SECONDS );
	}

	/**
	 * @param int $referrer_id Referrer.
	 * @param int $referred_id Referred.
	 */
	public static function on_referral( $referrer_id, $referred_id ) {
		self::bump_velocity( 'referral_abuse', 'ngc_fraud_ref_' . (int) $referrer_id . '_' . gmdate( 'Ymd' ), 'user', (int) $referrer_id, [ 'referred' => (int) $referred_id ], DAY_IN_SECONDS );
	}

	/**
	 * @param int $order_id Order.
	 */
	public static function on_refund( $order_id ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
		$user  = $order ? (int) $order->get_user_id() : 0;
		self::bump_velocity( 'refund_abuse', 'ngc_fraud_refund_' . $user . '_' . gmdate( 'Ymd' ), 'order', (int) $order_id, [ 'user_id' => $user ], DAY_IN_SECONDS );
	}

	/**
	 * @param string               $event Event.
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function on_workflow( $event, $payload ) {
		if ( false !== strpos( (string) $event, 'payment' ) && ! empty( $payload['failed'] ) ) {
			self::raise_signal( 'payment_failure_spike', 'payment', (int) ( $payload['order_id'] ?? 0 ), $payload );
		}
		if ( false !== stripos( (string) wp_json_encode( $payload ), 'whatsapp' ) || false !== stripos( (string) wp_json_encode( $payload ), 'eft outside' ) ) {
			self::raise_signal( 'off_platform_payment', 'payment', (int) ( $payload['order_id'] ?? 0 ), $payload );
		}
	}

	/**
	 * @param string               $event Hub event.
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function on_hub_event( $event, $payload ) {
		if ( 'ngt.payment.overdue' === $event ) {
			self::raise_signal( 'payment_failure_spike', 'payment', (int) ( $payload['booking_id'] ?? 0 ), $payload );
		}
		if ( 'ngt.lesson.completed' === $event ) {
			$tutor = (int) ( $payload['tutor_user_id'] ?? 0 );
			self::bump_velocity( 'completion_manipulation', 'ngc_fraud_comp_' . $tutor . '_' . gmdate( 'YmdH' ), 'lesson', (int) ( $payload['lesson_id'] ?? 0 ), $payload );
		}
	}

	/**
	 * @return array{open: int, high: int}
	 */
	public static function stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'ngc_fraud_cases';
		return [
			'open' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'open'" ),
			'high' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'open' AND severity = 'high'" ),
		];
	}
}
