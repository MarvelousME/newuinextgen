<?php
/**
 * Deterministic relational demo seeder (Phase 14).
 *
 * Creates entities through domain services wherever possible.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seed orchestrator — idempotent, versioned, auditable.
 */
final class NGC_Demo_Seeder {

	public const OPTION_STATUS = 'ngc_demo_seed_status';
	public const OPTION_GRAPH  = 'ngc_demo_seed_graph';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_demo_clock_advanced', [ __CLASS__, 'on_clock_advanced' ], 10, 2 );
	}

	/**
	 * Full seed (or scenario subset).
	 *
	 * @param string $scenario Scenario key or 'all'.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function seed( $scenario = 'all' ) {
		$gate = NGC_Demo_Env::assert_demo_ops_allowed();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		NGC_Demo_Env::set_demo_mode( true );
		NGC_Demo_Env::install_default_flags();
		if ( class_exists( 'NGC_Database' ) && method_exists( 'NGC_Database', 'ensure_amelia_booking_id_nullable' ) ) {
			NGC_Database::ensure_amelia_booking_id_nullable();
		}

		$started = microtime( true );
		$graph   = [
			'seed_version' => NGC_Demo_Env::SEED_VERSION,
			'scenario'     => sanitize_key( $scenario ),
			'started_at'   => gmdate( 'c' ),
			'users'        => [],
			'children'     => [],
			'matches'      => [],
			'bookings'     => [],
			'reviews'      => [],
			'wallet'       => [],
			'fraud'        => [],
			'safeguarding' => [],
			'agents'       => [],
			'notifications'=> 0,
			'events'       => [],
			'errors'       => [],
		];

		// Suppress new-user emails during seed.
		add_filter( 'wp_send_new_user_notification_to_user', '__return_false', 99 );
		add_filter( 'wp_send_new_user_notification_to_admin', '__return_false', 99 );

		$graph['users'] = NGC_Demo_Registry::ensure_users();

		try {
			self::seed_children( $graph );
			self::seed_matching_scenarios( $graph );
			self::seed_booking_scenarios( $graph );
			self::seed_financial_scenarios( $graph );
			self::seed_tutor_application_meta( $graph );
			self::seed_fraud_security( $graph );
			self::seed_safeguarding( $graph );
			self::seed_agent_scenarios( $graph );
			self::seed_consent_audit( $graph );
		} catch ( Exception $e ) {
			$graph['errors'][] = $e->getMessage();
		}

		remove_filter( 'wp_send_new_user_notification_to_user', '__return_false', 99 );
		remove_filter( 'wp_send_new_user_notification_to_admin', '__return_false', 99 );

		$graph['notifications'] = count( NGC_Demo_Notifications::all() );
		$graph['finished_at']   = gmdate( 'c' );
		$graph['duration_ms']   = (int) round( ( microtime( true ) - $started ) * 1000 );
		$graph['clock']         = NGC_Demo_Clock::status();

		update_option( self::OPTION_GRAPH, $graph, false );
		update_option(
			self::OPTION_STATUS,
			[
				'version'    => NGC_Demo_Env::SEED_VERSION,
				'status'     => empty( $graph['errors'] ) ? 'seeded' : 'seeded_with_errors',
				'seeded_at'  => $graph['finished_at'],
				'scenario'   => $scenario,
			],
			false
		);

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'demo_seed_completed', 'demo', 0, [ 'scenario' => $scenario, 'version' => NGC_Demo_Env::SEED_VERSION ] );
		}
		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info( 'demo', 'seed', 'Demo seed completed', [ 'scenario' => $scenario, 'errors' => $graph['errors'] ] );
		}

		return $graph;
	}

	/**
	 * @param array<string, mixed> $graph Graph (by ref).
	 */
	private static function seed_children( &$graph ) {
		$parent = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		if ( ! $parent || ! class_exists( 'NGC_Child_Learners' ) ) {
			return;
		}

		$specs = [
			[ 'NGT-DEMO-S0002', 'Lerato Molefe', 'Grade 9', 'Mathematics' ],
			[ 'NGT-DEMO-S0003', 'Kagiso Molefe', 'Grade 6', 'English' ],
		];
		foreach ( $specs as $spec ) {
			$student_uid = NGC_Demo_Registry::user_id( $spec[0] );
			$existing    = self::find_child_for_parent( $parent, $spec[1] );
			if ( $existing ) {
				$graph['children'][ $spec[0] ] = $existing;
				continue;
			}
			$id = NGC_Child_Learners::create(
				[
					'parent_user_id'  => $parent,
					'student_user_id' => $student_uid,
					'display_name'    => $spec[1],
					'grade'           => $spec[2],
					'province'        => 'Gauteng',
					'email'           => $spec[0] === 'NGT-DEMO-S0002' ? 'demo.child.a@nextgen.local' : 'demo.child.b@nextgen.local',
					'status'          => 'active',
					'skip_provision'  => $student_uid > 0,
					'meta'            => array_merge(
						NGC_Demo_Env::demo_meta( 'primary-parent' ),
						[ 'stable_id' => $spec[0], 'focus_subject' => $spec[3] ]
					),
				]
			);
			if ( ! is_wp_error( $id ) ) {
				$graph['children'][ $spec[0] ] = (int) $id;
				if ( $student_uid ) {
					NGC_Child_Learners::link_student( (int) $id, $student_uid );
				}
				NGC_Demo_Notifications::emit( 'child-profile-created', 'demo.parent@nextgen.local', 'child_learner.created', [ 'child' => $spec[1] ] );
				$graph['events'][] = 'child_learner.created:' . $id;
			} else {
				$graph['errors'][] = $id->get_error_message();
			}
		}
	}

	/**
	 * @param int    $parent_id Parent.
	 * @param string $name      Name.
	 * @return int
	 */
	private static function find_child_for_parent( $parent_id, $name ) {
		if ( ! class_exists( 'NGC_Child_Learners' ) ) {
			return 0;
		}
		$rows = NGC_Child_Learners::for_parent( (int) $parent_id );
		foreach ( $rows as $row ) {
			if ( ( $row['display_name'] ?? '' ) === $name ) {
				return (int) $row['id'];
			}
			$meta = json_decode( (string) ( $row['meta'] ?? '' ), true );
			if ( is_array( $meta ) && ! empty( $meta['is_demo'] ) && ( $row['display_name'] ?? '' ) === $name ) {
				return (int) $row['id'];
			}
		}
		return 0;
	}

	/**
	 * MATCH-001..008 style scenarios via NGC_Matching.
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_matching_scenarios( &$graph ) {
		if ( ! class_exists( 'NGC_Matching' ) ) {
			return;
		}
		$parent  = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		$student = NGC_Demo_Registry::user_id( 'NGT-DEMO-S0002' );
		$tutor   = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0001' );
		$admin   = NGC_Demo_Registry::user_id( 'NGT-DEMO-A0001' );

		// MATCH-001 strong match.
		$match_id = self::idempotent_match( $parent, $student, 'Mathematics', 'Grade 9', 'Gauteng', 'MATCH-001' );
		if ( $match_id ) {
			$graph['matches']['MATCH-001'] = $match_id;
			if ( $tutor ) {
				NGC_Matching::manual_assign( $match_id, $tutor, $admin ?: $parent );
			}
			NGC_Matching::accept( $match_id, $parent );
			NGC_Demo_Notifications::emit( 'match-proposed', 'demo.parent@nextgen.local', 'match.proposed', [ 'match_id' => $match_id ] );
			NGC_Demo_Notifications::emit( 'match-accepted', 'demo.tutor.approved@nextgen.local', 'match.accepted', [ 'match_id' => $match_id ] );
			$graph['events'][] = 'match.accepted:' . $match_id;
		}

		// MATCH-002 online alternative.
		$m2 = self::idempotent_match( $parent, $student, 'English', 'Grade 9', 'Limpopo', 'MATCH-002' );
		if ( $m2 ) {
			$online = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0002' );
			if ( $online ) {
				NGC_Matching::manual_assign( $m2, $online, $admin ?: $parent );
			}
			$graph['matches']['MATCH-002'] = $m2;
			$graph['events'][] = 'match.proposed:' . $m2;
		}

		// MATCH-004 budget.
		$m4 = self::idempotent_match( $parent, $student, 'Accounting', 'Grade 10', 'Gauteng', 'MATCH-004' );
		if ( $m4 ) {
			$budget = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0003' );
			if ( $budget ) {
				NGC_Matching::manual_assign( $m4, $budget, $admin ?: $parent );
			}
			$graph['matches']['MATCH-004'] = $m4;
		}

		// MATCH-006 suspended tutor must not be newly matched — record exclusion note.
		$graph['matches']['MATCH-006'] = [ 'excluded_tutor' => 'NGT-DEMO-T0004', 'reason' => 'suspended' ];

		// MATCH-007 manual admin match.
		$m7 = self::idempotent_match( $parent, NGC_Demo_Registry::user_id( 'NGT-DEMO-S0003' ), 'English', 'Grade 6', 'Gauteng', 'MATCH-007' );
		if ( $m7 && $admin ) {
			$t2 = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0002' );
			if ( $t2 ) {
				NGC_Matching::manual_assign( $m7, $t2, $admin );
				NGC_Audit::log( 'match_manual_assign', 'match', $m7, [ 'reason' => 'Demo admin override MATCH-007' ], $admin );
			}
			$graph['matches']['MATCH-007'] = $m7;
		}
	}

	/**
	 * @param int    $parent Parent.
	 * @param int    $student Student.
	 * @param string $subject Subject.
	 * @param string $grade Grade.
	 * @param string $province Province.
	 * @param string $scenario Scenario.
	 * @return int
	 */
	private static function idempotent_match( $parent, $student, $subject, $grade, $province, $scenario ) {
		global $wpdb;
		$table = NGC_Database::table( 'matches' );
		if ( ! $table || ! $parent ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE parent_user_id = %d AND subject = %s AND notes LIKE %s ORDER BY id DESC LIMIT 1",
				$parent,
				$subject,
				'%' . $wpdb->esc_like( $scenario ) . '%'
			)
		);
		if ( $existing ) {
			return $existing;
		}
		$id = NGC_Matching::create_from_find_tutor(
			[
				'parent_user_id'  => $parent,
				'student_user_id' => $student,
				'subject'         => $subject,
				'grade'           => $grade,
				'province'        => $province,
				'notes'           => 'Demo scenario ' . $scenario,
			]
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * BOOK-001.. scenarios.
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_booking_scenarios( &$graph ) {
		if ( ! class_exists( 'NGC_Bookings' ) ) {
			return;
		}
		$parent  = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		$student = NGC_Demo_Registry::user_id( 'NGT-DEMO-S0002' );
		$tutor   = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0001' );
		$match   = (int) ( $graph['matches']['MATCH-001'] ?? 0 );
		if ( ! $student || ! $tutor ) {
			return;
		}

		// BOOK-001 confirmed upcoming.
		$b1 = self::create_booking_once(
			[
				'match_id'        => $match,
				'student_user_id' => $student,
				'tutor_user_id'   => $tutor,
				'subject'         => 'Mathematics',
				'scheduled_at'    => NGC_Demo_Clock::mysql( '+2 days' ),
				'amount'          => 450.00,
				'notes'           => 'BOOK-001',
				'meta'            => NGC_Demo_Env::demo_meta( 'BOOK-001' ),
			],
			'BOOK-001'
		);
		if ( $b1 ) {
			NGC_Bookings::transition( $b1, 'confirmed', $parent );
			$graph['bookings']['BOOK-001'] = $b1;
			NGC_Demo_Notifications::emit( 'booking-confirmed', 'demo.parent@nextgen.local', 'booking.confirmed', [ 'booking_id' => $b1 ] );
			$graph['events'][] = 'booking.confirmed:' . $b1;
		}

		// BOOK completed + review path (session completion chain).
		$b_done = self::create_booking_once(
			[
				'match_id'        => $match,
				'student_user_id' => $student,
				'tutor_user_id'   => $tutor,
				'subject'         => 'Mathematics',
				'scheduled_at'    => NGC_Demo_Clock::mysql( '-7 days' ),
				'amount'          => 450.00,
				'notes'           => 'BOOK-COMPLETED',
				'meta'            => NGC_Demo_Env::demo_meta( 'SESSION-COMPLETE' ),
			],
			'BOOK-COMPLETED'
		);
		if ( $b_done ) {
			NGC_Bookings::transition( $b_done, 'confirmed', $parent );
			NGC_Bookings::transition( $b_done, 'completed', $tutor );
			$graph['bookings']['BOOK-COMPLETED'] = $b_done;
			$graph['events'][] = 'lesson.completed:' . $b_done;
			NGC_Demo_Notifications::emit( 'session-completed', 'demo.parent@nextgen.local', 'SessionCompleted', [ 'booking_id' => $b_done ] );
			NGC_Demo_Notifications::emit( 'review-request', 'demo.parent@nextgen.local', 'SessionCompleted', [ 'booking_id' => $b_done ] );
			if ( class_exists( 'NGC_Reviews' ) ) {
				$rev = NGC_Reviews::create_review(
					[
						'parent_user_id'  => $parent,
						'tutor_user_id'   => $tutor,
						'booking_id'      => $b_done,
						'student_user_id' => $student,
						'rating'          => 5,
						'comment'         => 'Demo review — excellent Mathematics session.',
					]
				);
				if ( ! is_wp_error( $rev ) ) {
					$graph['reviews']['REV-001'] = (int) $rev;
				}
			}
		}

		// BOOK-003 pending / requested (payment pending).
		$b_pending = self::create_booking_once(
			[
				'match_id'        => $match,
				'student_user_id' => $student,
				'tutor_user_id'   => $tutor,
				'subject'         => 'Mathematics',
				'scheduled_at'    => NGC_Demo_Clock::mysql( '+5 days' ),
				'amount'          => 450.00,
				'notes'           => 'BOOK-PENDING-PAY',
				'meta'            => NGC_Demo_Env::demo_meta( 'FIN-002' ),
			],
			'BOOK-PENDING-PAY'
		);
		if ( $b_pending ) {
			$graph['bookings']['BOOK-PENDING-PAY'] = $b_pending;
			NGC_Demo_Notifications::emit( 'payment-failure', 'demo.parent@nextgen.local', 'PaymentFailed', [ 'booking_id' => $b_pending ] );
		}

		// Adult student booking.
		$adult = NGC_Demo_Registry::user_id( 'NGT-DEMO-S0001' );
		$t2    = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0002' );
		if ( $adult && $t2 ) {
			$ba = self::create_booking_once(
				[
					'student_user_id' => $adult,
					'tutor_user_id'   => $t2,
					'subject'         => 'English',
					'scheduled_at'    => NGC_Demo_Clock::mysql( '+1 day' ),
					'amount'          => 380.00,
					'notes'           => 'BOOK-ADULT',
					'meta'            => NGC_Demo_Env::demo_meta( 'adult-student' ),
				],
				'BOOK-ADULT'
			);
			if ( $ba ) {
				NGC_Bookings::transition( $ba, 'confirmed', $adult );
				$graph['bookings']['BOOK-ADULT'] = $ba;
			}
		}
	}

	/**
	 * @param array<string, mixed> $data Data.
	 * @param string               $key  Idempotency key in notes.
	 * @return int
	 */
	private static function create_booking_once( $data, $key ) {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );
		if ( ! $table ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE notes = %s LIMIT 1",
				$key
			)
		);
		if ( $existing ) {
			return $existing;
		}
		// Prefer unique slots to avoid conflict.
		$attempt = 0;
		while ( $attempt < 5 ) {
			$id = NGC_Bookings::create( $data );
			if ( ! is_wp_error( $id ) ) {
				return (int) $id;
			}
			if ( 'ngc_booking_conflict' === $id->get_error_code() ) {
				$data['scheduled_at'] = NGC_Demo_Clock::mysql( '+' . ( 2 + $attempt ) . ' days +' . ( $attempt * 2 ) . ' hours' );
				++$attempt;
				continue;
			}
			return 0;
		}
		return 0;
	}

	/**
	 * FIN scenarios via wallet (sandbox — no real PayFast charge).
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_financial_scenarios( &$graph ) {
		$parent = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		$tutor  = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0001' );
		if ( $parent && class_exists( 'NGC_Wallet' ) ) {
			$ref = 'DEMO-WALLET-TOPUP-' . NGC_Demo_Env::SEED_VERSION;
			self::wallet_credit_once( $parent, 2000.00, $ref, 'Demo wallet top-up FIN-006' );
			$graph['wallet']['topup'] = 2000.00;
			NGC_Demo_Notifications::emit( 'wallet-topup', 'demo.parent@nextgen.local', 'WalletTopUp', [ 'amount' => 2000 ] );
		}
		if ( $tutor && class_exists( 'NGC_Reviews' ) && method_exists( 'NGC_Reviews', 'create_payout' ) ) {
			$payout = NGC_Reviews::create_payout( $tutor, 450.00 );
			if ( ! is_wp_error( $payout ) && $payout ) {
				$graph['wallet']['payout_pending'] = (int) $payout;
				NGC_Demo_Notifications::emit( 'tutor-payout-held', 'demo.tutor.approved@nextgen.local', 'TutorPayoutHeld', [ 'payout_id' => $payout ] );
				$graph['events'][] = 'TutorEarningsCreated:' . $payout;
			}
		}
		// FIN-001 success notification tied to completed booking.
		if ( ! empty( $graph['bookings']['BOOK-COMPLETED'] ) ) {
			NGC_Demo_Notifications::emit(
				'payment-receipt',
				'demo.parent@nextgen.local',
				'PaymentSucceeded',
				[ 'booking_id' => $graph['bookings']['BOOK-COMPLETED'], 'amount' => 450, 'currency' => 'ZAR' ]
			);
			NGC_Demo_Notifications::emit(
				'invoice-issued',
				'demo.parent@nextgen.local',
				'PaymentSucceeded',
				[ 'booking_id' => $graph['bookings']['BOOK-COMPLETED'] ]
			);
		}
	}

	/**
	 * @param int    $user_id User.
	 * @param float  $amount Amount.
	 * @param string $ref Ref.
	 * @param string $desc Desc.
	 */
	private static function wallet_credit_once( $user_id, $amount, $ref, $desc ) {
		global $wpdb;
		$table = NGC_Database::table( 'wallet_ledger' );
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE user_id = %d AND reference = %s LIMIT 1",
					$user_id,
					$ref
				)
			);
			if ( $exists ) {
				return;
			}
		}
		NGC_Wallet::credit( $user_id, $amount, $ref, $desc );
	}

	/**
	 * Tutor application states via user meta + audit (applications table if present).
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_tutor_application_meta( &$graph ) {
		$draft = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0005' );
		$sub   = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0006' );
		$re    = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0007' );
		if ( $draft ) {
			update_user_meta( $draft, 'ngc_application_status', 'draft' );
			update_user_meta( $draft, 'ngc_application_missing', [ 'id_document', 'availability' ] );
		}
		if ( $sub ) {
			update_user_meta( $sub, 'ngc_application_status', 'submitted' );
			update_user_meta( $sub, 'ngc_application_submitted_at', NGC_Demo_Clock::mysql( '-2 days' ) );
			NGC_Demo_Notifications::emit( 'tutor-application-submitted', 'demo.tutor.submitted@nextgen.local', 'TutorApplicationSubmitted', [] );
			$graph['events'][] = 'TutorApplicationSubmitted';
		}
		if ( $re ) {
			update_user_meta( $re, 'ngc_application_status', 'resubmission_required' );
			update_user_meta( $re, 'ngc_application_reviewer_notes', 'Demo: police clearance document expired.' );
			NGC_Demo_Notifications::emit( 'tutor-resubmission', 'demo.tutor.resubmit@nextgen.local', 'TutorResubmissionRequested', [] );
			$graph['events'][] = 'TutorResubmissionRequested';
		}
		$approved = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0001' );
		if ( $approved ) {
			NGC_Demo_Notifications::emit( 'tutor-approval', 'demo.tutor.approved@nextgen.local', 'TutorApproved', [] );
			$graph['events'][] = 'TutorApproved';
		}
	}

	/**
	 * Fraud + security scenarios.
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_fraud_security( &$graph ) {
		if ( ! class_exists( 'NGC_Fraud_Engine' ) ) {
			return;
		}
		$parent = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		$tutor  = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0004' );
		if ( method_exists( 'NGC_Fraud_Engine', 'raise_signal' ) && $parent ) {
			$sig = NGC_Fraud_Engine::raise_signal( 'registration_velocity', 'user', $parent, [ 'demo' => true, 'scenario' => 'velocity' ] );
			$graph['fraud']['signal'] = $sig;
		}
		if ( method_exists( 'NGC_Fraud_Engine', 'create_case' ) && $tutor ) {
			$case = NGC_Fraud_Engine::create_case(
				[
					'title'       => 'Demo fraud case — payout detail change',
					'entity_type' => 'user',
					'entity_id'   => $tutor,
					'score'       => 72,
					'severity'   => 'high',
					'evidence'    => [ 'scenario' => 'payout_detail_change', 'is_demo' => true ],
				]
			);
			$graph['fraud']['case'] = $case;
			NGC_Demo_Notifications::emit( 'fraud-case-created', 'demo.fraud@nextgen.local', 'FraudSignalRaised', [ 'case_id' => $case ] );
			$graph['events'][] = 'FraudSignalRaised:' . $case;
		}
		NGC_Demo_Notifications::emit( 'suspicious-login', 'demo.security@nextgen.local', 'SecurityAlert', [ 'scenario' => 'new_device' ] );
		$graph['events'][] = 'SecurityAlert:new_device';
	}

	/**
	 * Safeguarding escalation.
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_safeguarding( &$graph ) {
		if ( ! class_exists( 'NGC_Safeguarding' ) ) {
			return;
		}
		$child_user = NGC_Demo_Registry::user_id( 'NGT-DEMO-S0002' );
		$reporter   = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0001' );
		$case       = NGC_Safeguarding::create_case(
			[
				'summary'          => 'Demo safeguarding escalation — review required',
				'priority'         => 'high',
				'source'           => 'demo_seed',
				'subject_user_id'  => $child_user,
				'reporter_user_id' => $reporter,
				'details'          => 'Synthetic demo case. No real minor at risk. [redacted-id]',
				'ai_signal'        => 1,
			]
		);
		if ( $case ) {
			$graph['safeguarding']['case'] = $case;
			$assignee = NGC_Demo_Registry::user_id( 'NGT-DEMO-SFG0001' );
			if ( $assignee && method_exists( 'NGC_Safeguarding', 'assign' ) ) {
				NGC_Safeguarding::assign( $case, $assignee );
			}
			NGC_Demo_Notifications::emit( 'safeguarding-escalation', 'demo.safeguarding@nextgen.local', 'SafeguardingAlertRaised', [ 'case_id' => $case ] );
			$graph['events'][] = 'SafeguardingAlertRaised:' . $case;
		}
	}

	/**
	 * AI agent scenarios AI-001..010.
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_agent_scenarios( &$graph ) {
		if ( ! class_exists( 'NGC_Agent_Control_Plane' ) ) {
			return;
		}
		if ( method_exists( 'NGC_Agent_Control_Plane', 'seed_registry' ) ) {
			NGC_Agent_Control_Plane::seed_registry();
		}
		if ( ! method_exists( 'NGC_Agent_Control_Plane', 'request_action' ) ) {
			return;
		}
		// AI-008 policy denial / approval request.
		$task = NGC_Agent_Control_Plane::request_action(
			'matching-assistant',
			'agent.recommend',
			[ 'demo' => true, 'scenario' => 'AI-001', 'match_request' => 'MATCH-001' ]
		);
		if ( ! is_wp_error( $task ) ) {
			$graph['agents']['AI-001'] = $task;
		}
		$deny = null;
		if ( class_exists( 'NGC_Agent_Policy_Engine' ) ) {
			$deny = NGC_Agent_Policy_Engine::evaluate(
				'finance.refund.execute',
				[ 'agent_id' => 'financial-reconciliation', 'autonomy_level' => 1, 'demo' => true ]
			);
			$graph['agents']['AI-008'] = $deny;
		}
		$approval = NGC_Agent_Control_Plane::request_action(
			'financial-reconciliation',
			'finance.refund.propose',
			[ 'demo' => true, 'scenario' => 'AI-009', 'amount' => 50 ]
		);
		if ( ! is_wp_error( $approval ) ) {
			$graph['agents']['AI-009'] = $approval;
			$admin = NGC_Demo_Registry::user_id( 'NGT-DEMO-AI0001' );
			if ( $admin && method_exists( 'NGC_Agent_Control_Plane', 'decide_approval' ) ) {
				NGC_Agent_Control_Plane::decide_approval( (int) $approval, true, 'Demo AI-009 approval' );
			}
		}
		// AI-010 kill switch toggle (pause then resume).
		if ( method_exists( 'NGC_Agent_Control_Plane', 'set_global_pause' ) ) {
			NGC_Agent_Control_Plane::set_global_pause( true );
			$graph['agents']['AI-010-paused'] = true;
			NGC_Agent_Control_Plane::set_global_pause( false );
			$graph['agents']['AI-010-resumed'] = true;
		} else {
			update_option( 'ngc_agent_global_pause', false, false );
			$graph['agents']['AI-010'] = 'pause_option_cleared';
		}
	}

	/**
	 * Consent + audit evidence.
	 *
	 * @param array<string, mixed> $graph Graph.
	 */
	private static function seed_consent_audit( &$graph ) {
		$parent = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		if ( $parent && class_exists( 'NGC_Platform_Repository' ) ) {
			NGC_Platform_Repository::create(
				'consent',
				[
					'user_id'        => $parent,
					'visitor_id'     => 'demo-visitor-p0001',
					'consent_status' => 'granted',
					'context'        => wp_json_encode( NGC_Demo_Env::demo_meta( 'consent' ) ),
				]
			);
			$graph['events'][] = 'consent-recorded';
		}
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'demo_consent_recorded', 'user', $parent, NGC_Demo_Env::demo_meta( 'consent' ), $parent );
		}
	}

	/**
	 * @param int $seconds Seconds.
	 * @param int $now Now.
	 */
	public static function on_clock_advanced( $seconds, $now ) {
		unset( $seconds, $now );
		NGC_Demo_Notifications::emit( 'scheduler-tick', 'demo.admin@nextgen.local', 'DemoClockAdvanced', NGC_Demo_Clock::status() );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function status() {
		return [
			'env'    => NGC_Demo_Env::is_demo_mode(),
			'flags'  => NGC_Demo_Env::flags(),
			'status' => get_option( self::OPTION_STATUS, [] ),
			'graph'  => get_option( self::OPTION_GRAPH, [] ),
			'clock'  => NGC_Demo_Clock::status(),
		];
	}
}
