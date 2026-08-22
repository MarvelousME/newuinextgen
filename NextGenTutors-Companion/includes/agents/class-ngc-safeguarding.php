<?php
/**
 * Safeguarding case management — signals require governed human review.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and tracks safeguarding cases with SLA + moderator workflow.
 * AI classifications are signals only — never auto-resolve.
 */
final class NGC_Safeguarding {

	public const DB_VERSION = '1.1.0';

	/** @var array<string, int> Priority → SLA hours. */
	public const SLA_HOURS = [
		'critical' => 2,
		'high'     => 4,
		'normal'   => 24,
		'low'      => 72,
	];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_install' ], 6 );
		add_action( 'ngc_safeguarding_sla_tick', [ __CLASS__, 'process_sla_breaches' ] );
		if ( ! wp_next_scheduled( 'ngc_safeguarding_sla_tick' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'ngc_safeguarding_sla_tick' );
		}
	}

	public static function maybe_install() {
		$ver = get_option( 'ngc_safeguarding_db_version', '' );
		if ( version_compare( (string) $ver, self::DB_VERSION, '<' ) ) {
			self::install();
			update_option( 'ngc_safeguarding_db_version', self::DB_VERSION, false );
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'ngc_safeguarding_cases';

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				status varchar(32) NOT NULL DEFAULT 'open',
				priority varchar(16) NOT NULL DEFAULT 'normal',
				source varchar(64) NOT NULL DEFAULT 'manual',
				subject_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				reporter_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				summary varchar(255) NOT NULL DEFAULT '',
				details longtext NULL,
				notes longtext NULL,
				ai_signal tinyint(1) NOT NULL DEFAULT 0,
				assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
				sla_hours int(11) NOT NULL DEFAULT 24,
				due_at datetime NULL,
				escalated_at datetime NULL,
				resolved_at datetime NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY status (status),
				KEY priority (priority),
				KEY due_at (due_at),
				KEY assigned_to (assigned_to)
			) {$charset};"
		);
	}

	/**
	 * @param string $priority Priority key.
	 * @return int
	 */
	public static function sla_hours_for( $priority ) {
		$priority = sanitize_key( (string) $priority );
		$hours    = self::SLA_HOURS[ $priority ] ?? self::SLA_HOURS['normal'];
		if ( 'critical' === $priority ) {
			$hours = (int) apply_filters( 'ngc_safeguarding_sla_hours_critical', $hours );
		}
		return (int) apply_filters( 'ngc_safeguarding_sla_hours', $hours, $priority );
	}

	/**
	 * @param array<string, mixed> $data Case payload.
	 * @return int Case ID.
	 */
	public static function create_case( array $data ) {
		global $wpdb;
		$summary  = sanitize_text_field( $data['summary'] ?? $data['title'] ?? 'Safeguarding case' );
		$priority = sanitize_key( $data['priority'] ?? 'normal' );
		if ( ! isset( self::SLA_HOURS[ $priority ] ) ) {
			$priority = 'normal';
		}
		$sla     = isset( $data['sla_hours'] ) ? max( 1, (int) $data['sla_hours'] ) : self::sla_hours_for( $priority );
		$due_at  = gmdate( 'Y-m-d H:i:s', time() + ( $sla * HOUR_IN_SECONDS ) );
		$details = self::redact_sensitive( (string) ( $data['details'] ?? wp_json_encode( $data ) ) );

		$wpdb->insert(
			$wpdb->prefix . 'ngc_safeguarding_cases',
			[
				'status'           => 'open',
				'priority'         => $priority,
				'source'           => sanitize_key( $data['source'] ?? 'agent' ),
				'subject_user_id'  => (int) ( $data['subject_user_id'] ?? $data['entity_id'] ?? 0 ),
				'reporter_user_id' => (int) ( $data['reporter_user_id'] ?? get_current_user_id() ),
				'summary'          => $summary,
				'details'          => $details,
				'notes'            => '',
				'ai_signal'        => ! empty( $data['ai_signal'] ) ? 1 : ( ! empty( $data['safeguarding'] ) ? 1 : 0 ),
				'assigned_to'      => (int) ( $data['assigned_to'] ?? 0 ),
				'sla_hours'        => $sla,
				'due_at'           => $due_at,
				'updated_at'       => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ]
		);
		$id = (int) $wpdb->insert_id;

		if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit(
				'SafeguardingAlertRaised',
				'safeguarding_case',
				(string) $id,
				[
					'priority' => $priority,
					'due_at'   => $due_at,
					'ai_signal'=> true,
				]
			);
		}
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'safeguarding_case_created', 'safeguarding', $id, [ 'summary' => $summary, 'due_at' => $due_at ], get_current_user_id() );
		}
		/**
		 * Fires after a safeguarding case row is inserted.
		 *
		 * @param int                  $id   Case ID.
		 * @param array<string, mixed> $data Original payload.
		 */
		do_action( 'ngc_safeguarding_case_created', $id, $data );
		return $id;
	}

	/**
	 * @param int $case_id Case ID.
	 * @return object|null
	 */
	public static function get( $case_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ngc_safeguarding_cases';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $case_id ) );
		return $row ?: null;
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, object>
	 */
	public static function query( array $args = [] ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'ngc_safeguarding_cases';
		$limit  = min( 100, max( 1, (int) ( $args['limit'] ?? 50 ) ) );
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_key( $args['status'] );
		} else {
			$where[] = "status IN ('open','escalated','in_review')";
		}
		if ( ! empty( $args['priority'] ) ) {
			$where[]  = 'priority = %s';
			$params[] = sanitize_key( $args['priority'] );
		}
		if ( isset( $args['assigned_to'] ) && '' !== $args['assigned_to'] ) {
			$where[]  = 'assigned_to = %d';
			$params[] = (int) $args['assigned_to'];
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY FIELD(priority,"critical","high","normal","low"), due_at ASC, id DESC LIMIT ' . $limit;
		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $sql );
		}
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param int $case_id Case.
	 * @param int $user_id Moderator.
	 * @return bool|WP_Error
	 */
	public static function assign( $case_id, $user_id ) {
		$case = self::get( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'ngc_sfg_not_found', __( 'Case not found.', 'nextgencompanion' ) );
		}
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ngc_safeguarding_cases',
			[
				'assigned_to' => (int) $user_id,
				'status'      => 'in_review',
				'updated_at'  => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $case_id ],
			[ '%d', '%s', '%s' ],
			[ '%d' ]
		);
		self::append_note( $case_id, sprintf( 'Assigned to user #%d', (int) $user_id ) );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'safeguarding_assigned', 'safeguarding', (int) $case_id, [ 'assigned_to' => (int) $user_id ], get_current_user_id() );
		}
		if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit( 'SafeguardingCaseAssigned', 'safeguarding_case', (string) $case_id, [ 'assigned_to' => (int) $user_id ] );
		}
		return true;
	}

	/**
	 * Manual or SLA-driven escalation.
	 *
	 * @param int    $case_id Case.
	 * @param string $reason  Reason.
	 * @return bool|WP_Error
	 */
	public static function escalate( $case_id, $reason = '' ) {
		$case = self::get( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'ngc_sfg_not_found', __( 'Case not found.', 'nextgencompanion' ) );
		}
		$priority = 'critical' === $case->priority ? 'critical' : 'high';
		$sla      = self::sla_hours_for( $priority );
		$due_at   = gmdate( 'Y-m-d H:i:s', time() + ( $sla * HOUR_IN_SECONDS ) );

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ngc_safeguarding_cases',
			[
				'status'       => 'escalated',
				'priority'     => $priority,
				'sla_hours'    => $sla,
				'due_at'       => $due_at,
				'escalated_at' => current_time( 'mysql', true ),
				'updated_at'   => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $case_id ],
			[ '%s', '%s', '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);
		self::append_note( $case_id, 'Escalated: ' . ( $reason ?: 'manual' ) );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'safeguarding_escalated', 'safeguarding', (int) $case_id, [ 'reason' => $reason ], get_current_user_id() );
		}
		if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit(
				'SafeguardingEscalated',
				'safeguarding_case',
				(string) $case_id,
				[ 'reason' => $reason, 'due_at' => $due_at ],
				[ 'data_classification' => 'confidential' ]
			);
		}
		return true;
	}

	/**
	 * @param int    $case_id     Case.
	 * @param string $resolution  Resolution code.
	 * @param string $note        Note.
	 * @return bool|WP_Error
	 */
	public static function resolve( $case_id, $resolution = 'closed', $note = '' ) {
		$case = self::get( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'ngc_sfg_not_found', __( 'Case not found.', 'nextgencompanion' ) );
		}
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ngc_safeguarding_cases',
			[
				'status'      => sanitize_key( $resolution ) ?: 'closed',
				'resolved_at' => current_time( 'mysql', true ),
				'updated_at'  => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $case_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
		if ( $note ) {
			self::append_note( $case_id, 'Resolved: ' . $note );
		}
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'safeguarding_resolved', 'safeguarding', (int) $case_id, [ 'resolution' => $resolution ], get_current_user_id() );
		}
		if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			NGC_Agent_Event_Envelope::emit( 'SafeguardingCaseResolved', 'safeguarding_case', (string) $case_id, [ 'resolution' => $resolution ] );
		}
		return true;
	}

	/**
	 * @param int    $case_id Case.
	 * @param string $note    Note text.
	 */
	public static function append_note( $case_id, $note ) {
		$case = self::get( $case_id );
		if ( ! $case ) {
			return;
		}
		$line  = '[' . gmdate( 'c' ) . ' u' . get_current_user_id() . '] ' . self::redact_sensitive( $note );
		$notes = trim( (string) ( $case->notes ?? '' ) . "\n" . $line );
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ngc_safeguarding_cases',
			[
				'notes'      => $notes,
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $case_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Escalate open cases past due_at.
	 */
	public static function process_sla_breaches() {
		global $wpdb;
		$table = $wpdb->prefix . 'ngc_safeguarding_cases';
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE status IN ('open','in_review') AND due_at IS NOT NULL AND due_at < %s AND escalated_at IS NULL LIMIT 50",
				$now
			)
		);
		foreach ( (array) $ids as $id ) {
			self::escalate( (int) $id, 'sla_breach' );
			if ( class_exists( 'NGC_Durable_Queue' ) ) {
				NGC_Durable_Queue::enqueue(
					NGC_Queue_Worker::QUEUE_SAFEGUARD,
					[
						'type'    => 'safeguard.sla',
						'case_id' => (int) $id,
						'reason'  => 'sla_breach',
					],
					[ 'idempotency_key' => 'sfg-sla:' . (int) $id, 'priority' => 40 ]
				);
			}
			if ( class_exists( 'NGC_Platform_Observability' ) ) {
				NGC_Platform_Observability::alert( 'safeguarding_sla_breach', [ 'case_id' => (int) $id ] );
			}
		}
	}

	/**
	 * Attach evidence metadata to a case.
	 *
	 * @param int                  $case_id Case.
	 * @param array<string,mixed>  $data    type, storage_path, checksum, meta.
	 * @return int|WP_Error
	 */
	public static function add_evidence( $case_id, array $data ) {
		if ( ! self::get( $case_id ) ) {
			return new WP_Error( 'ngc_sfg_not_found', 'Case not found.' );
		}
		global $wpdb;
		$ok = $wpdb->insert(
			NGC_Platform_Schema::table( 'safeguarding_evidence' ),
			[
				'tenant_id'    => class_exists( 'NGC_Tenant_Context' ) ? NGC_Tenant_Context::id() : 1,
				'case_id'      => (int) $case_id,
				'evidence_type'=> sanitize_key( (string) ( $data['type'] ?? 'note' ) ),
				'storage_path' => (string) ( $data['storage_path'] ?? '' ),
				'checksum'     => (string) ( $data['checksum'] ?? '' ),
				'meta_json'    => wp_json_encode( $data['meta'] ?? $data ),
				'created_by'   => get_current_user_id(),
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
		if ( ! $ok ) {
			return new WP_Error( 'ngc_sfg_evidence_failed', 'Failed to store evidence.' );
		}
		self::append_note( $case_id, 'Evidence attached: ' . sanitize_key( (string) ( $data['type'] ?? 'note' ) ) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Appeal / reopen a closed case.
	 *
	 * @param int    $case_id Case.
	 * @param string $reason  Appeal reason.
	 * @return bool|WP_Error
	 */
	public static function appeal( $case_id, $reason = '' ) {
		$case = self::get( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'ngc_sfg_not_found', 'Case not found.' );
		}
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ngc_safeguarding_cases',
			[
				'status'      => 'open',
				'resolved_at' => null,
				'updated_at'  => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $case_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
		self::append_note( $case_id, 'Appeal: ' . sanitize_text_field( $reason ) );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'safeguarding_appeal', 'safeguarding', (int) $case_id, [ 'reason' => $reason ], get_current_user_id() );
		}
		return true;
	}

	/**
	 * @param object|null $case Case row.
	 * @return array{breached:bool,remaining_seconds:int,label:string}
	 */
	public static function sla_status( $case ) {
		if ( ! $case || empty( $case->due_at ) ) {
			return [ 'breached' => false, 'remaining_seconds' => 0, 'label' => 'n/a' ];
		}
		$due = strtotime( $case->due_at . ' UTC' );
		$now = time();
		$rem = $due - $now;
		if ( $rem <= 0 || ! empty( $case->escalated_at ) || in_array( $case->status, [ 'escalated', 'closed', 'resolved' ], true ) ) {
			$breached = $rem <= 0 || 'escalated' === $case->status || ! empty( $case->escalated_at );
			return [
				'breached'          => $breached,
				'remaining_seconds' => max( 0, $rem ),
				'label'             => $breached ? 'BREACHED' : 'OK',
			];
		}
		$h = (int) floor( $rem / 3600 );
		$m = (int) floor( ( $rem % 3600 ) / 60 );
		return [
			'breached'          => false,
			'remaining_seconds' => $rem,
			'label'             => sprintf( '%dh %dm', $h, $m ),
		];
	}

	/**
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function redact_sensitive( $text ) {
		if ( class_exists( 'BIA_Policy' ) ) {
			$text = BIA_Policy::redact( $text );
		}
		$text = preg_replace( '/\b\d{8,17}\b/', '[redacted-account]', $text ) ?? $text;
		$text = preg_replace( '/\b\d{13}\b/', '[redacted-id]', $text ) ?? $text;
		return $text;
	}

	/**
	 * @return array{open: int, high: int, escalated: int, breached: int}
	 */
	public static function stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'ngc_safeguarding_cases';
		$now   = current_time( 'mysql', true );
		return [
			'open'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('open','in_review','escalated')" ),
			'high'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('open','in_review','escalated') AND priority IN ('high','critical')" ),
			'escalated' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'escalated'" ),
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'breached'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status IN ('open','in_review') AND due_at < %s", $now ) ),
		];
	}
}
