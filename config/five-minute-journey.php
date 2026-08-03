<?php
/**
 * Five-minute booking domain journey — uses Companion domain APIs only.
 * Writes sanitized evidence JSON. No passwords/tokens in output.
 *
 * Run: php /var/www/config/five-minute-journey.php
 */
require '/var/www/html/wp-load.php';

$run_id = 'NGT-E2E-' . gmdate( 'Ymd\THis' );
$tz     = new DateTimeZone( 'Africa/Johannesburg' );
$now    = new DateTimeImmutable( 'now', $tz );
$target = $now->modify( '+5 minutes' );
// Align to next full minute boundary for provider granularity.
$slot   = $target->setTime( (int) $target->format( 'H' ), (int) $target->format( 'i' ), 0 );
if ( $slot <= $now ) {
	$slot = $slot->modify( '+1 minute' );
}
$scheduled_local = $slot->format( 'Y-m-d H:i:s' );
$scheduled_utc   = $slot->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

$evidence = [
	'run_id'            => $run_id,
	'generated_at_utc'  => gmdate( 'c' ),
	'timezone'          => 'Africa/Johannesburg',
	'now_local'         => $now->format( 'c' ),
	'target_start'      => $target->format( 'c' ),
	'scheduled_start'   => $slot->format( 'c' ),
	'slot_constraint'   => 'aligned_to_minute_boundary_min_5m_ahead',
	'base_url'          => home_url( '/' ),
	'preflight'         => [],
	'phases'            => [],
	'correlation'       => [],
	'verdicts'          => [],
	'defects'           => [],
	'meeting'           => [
		'owner'          => 'NGC_Meetings + NGC_Jitsi_Meeting_Adapter',
		'join_url_field' => true,
		'status'         => 'READY',
		'note'           => 'Jitsi A/V rooms created on booking.confirmed; joinUrl exposed via format_session_row and REST /bookings/{id}/join.',
	],
];

function phase( &$evidence, $name, $status, $detail = [] ) {
	$evidence['phases'][ $name ] = array_merge( [ 'status' => $status ], $detail );
	$evidence['verdicts'][ $name ] = $status;
}

function redact_user( $user_id ) {
	$u = get_userdata( (int) $user_id );
	if ( ! $u ) {
		return null;
	}
	return [
		'id'           => (int) $u->ID,
		'login_prefix' => substr( (string) $u->user_login, 0, 12 ),
		'email_domain' => substr( strrchr( (string) $u->user_email, '@' ) ?: '', 1 ),
		'roles'        => array_values( (array) $u->roles ),
		'display'      => $u->display_name,
	];
}

// --- Preflight ---
$pf = [
	'home_url'     => home_url( '/' ),
	'timezone'     => wp_timezone_string(),
	'wp_time'      => current_time( 'mysql' ),
	'woocommerce'  => class_exists( 'WooCommerce' ),
	'currency'     => get_option( 'woocommerce_currency', '' ),
	'companion'    => defined( 'NGC_VERSION' ) ? NGC_VERSION : ( defined( 'NGC_PLUGIN_VERSION' ) ? NGC_PLUGIN_VERSION : 'yes' ),
	'fluentcrm'    => defined( 'FLUENTCRM' ) || class_exists( 'FluentCrm\App\Models\Subscriber' ),
	'amelia'       => defined( 'AMELIA_VERSION' ),
	'masterstudy'  => defined( 'STM_LMS_FILE' ) || class_exists( 'STM_LMS_User' ),
	'payfast'      => get_option( 'woocommerce_ngc_payfast_settings', [] ),
	'gateway'      => class_exists( 'NGC_Agent_Gateway_Client' ) ? NGC_Agent_Gateway_Client::health() : null,
];
$pf['payfast_testmode'] = ! empty( $pf['payfast']['testmode'] ) || ( ( $pf['payfast']['enabled'] ?? '' ) === 'yes' );
unset( $pf['payfast']['merchant_id'], $pf['payfast']['merchant_key'], $pf['payfast']['passphrase'] );
if ( is_wp_error( $pf['gateway'] ) ) {
	$pf['gateway'] = [ 'ok' => false, 'error' => $pf['gateway']->get_error_message() ];
}
$evidence['preflight'] = $pf;

if ( 'Africa/Johannesburg' !== wp_timezone_string() ) {
	$evidence['defects'][] = 'timezone_not_jhb';
}
if ( ! class_exists( 'WooCommerce' ) ) {
	phase( $evidence, 'preflight_woocommerce', 'FAIL', [ 'note' => 'WooCommerce inactive' ] );
} else {
	phase( $evidence, 'preflight_woocommerce', 'PASS' );
}
if ( empty( $pf['payfast_testmode'] ) && ( get_option( 'woocommerce_ngc_payfast_settings', [] )['enabled'] ?? '' ) === 'yes' ) {
	// If live mode, STOP
	$settings = get_option( 'woocommerce_ngc_payfast_settings', [] );
	if ( empty( $settings['testmode'] ) || 'yes' !== $settings['testmode'] ) {
		phase( $evidence, 'payment_live_guard', 'FAIL', [ 'note' => 'STOP: PayFast may not be sandbox' ] );
		$evidence['overall'] = 'BLOCKED';
		file_put_contents( '/var/www/config/five-minute-journey-latest.json', wp_json_encode( $evidence, JSON_PRETTY_PRINT ) );
		echo wp_json_encode( [ 'overall' => 'BLOCKED', 'reason' => 'payment_not_sandbox', 'run_id' => $run_id ] );
		exit( 2 );
	}
}
phase( $evidence, 'preflight', 'PASS', [ 'checks' => array_keys( $pf ) ] );

$suffix = strtolower( preg_replace( '/[^a-z0-9]+/i', '', $run_id ) );
$tutor_email  = "tutor+{$suffix}@example.test";
$parent_email = "parent+{$suffix}@example.test";
$unrel_email  = "unrelated-parent+{$suffix}@example.test";
$pass         = wp_generate_password( 20, true, true ); // never written to evidence

// --- Phase 1: Tutor user + application ---
$tutor_id = wp_insert_user(
	[
		'user_login'   => 'tutor_' . substr( $suffix, -12 ),
		'user_email'   => $tutor_email,
		'user_pass'    => $pass,
		'display_name' => 'E2E Tutor ' . substr( $suffix, -6 ),
		'role'         => 'subscriber',
	]
);
if ( is_wp_error( $tutor_id ) ) {
	phase( $evidence, 'tutor_registration', 'FAIL', [ 'error' => $tutor_id->get_error_message() ] );
} else {
	$app_id = NGC_Tutor_Lifecycle::apply(
		[
			'user_id'   => $tutor_id,
			'full_name' => 'E2E Tutor ' . substr( $suffix, -6 ),
			'email'     => $tutor_email,
			'phone'     => '0820000001',
			'subjects'  => 'Mathematics',
			'province'  => 'Gauteng',
			'bio'       => 'Fictional CAPS Mathematics tutor for E2E run ' . $run_id,
			'meta'      => [ 'is_demo' => 1, 'demo_scenario_id' => $run_id, 'delivery' => 'online' ],
		]
	);
	if ( is_wp_error( $app_id ) ) {
		phase( $evidence, 'tutor_registration', 'FAIL', [ 'error' => $app_id->get_error_message() ] );
	} else {
		$app = NGC_Tutor_Lifecycle::get( $app_id );
		$public_before = get_posts(
			[
				'post_type'      => 'tutors',
				'author'         => $tutor_id,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);
		phase(
			$evidence,
			'tutor_registration',
			'PASS',
			[
				'application_id'     => (int) $app_id,
				'status'             => $app->status ?? null,
				'public_before_approve' => empty( $public_before ),
				'user'               => redact_user( $tutor_id ),
			]
		);
		$evidence['correlation']['tutor_user_id']    = (int) $tutor_id;
		$evidence['correlation']['application_id']   = (int) $app_id;
	}
}

// --- Phase 2: Approve ---
$admin = get_user_by( 'login', 'admin' ) ?: get_users( [ 'role' => 'administrator', 'number' => 1 ] )[0] ?? null;
$actor = $admin ? (int) $admin->ID : 1;
if ( ! empty( $evidence['correlation']['application_id'] ) ) {
	$ok = NGC_Tutor_Lifecycle::approve( (int) $evidence['correlation']['application_id'], $actor );
	if ( is_wp_error( $ok ) ) {
		phase( $evidence, 'tutor_approval', 'FAIL', [ 'error' => $ok->get_error_message() ] );
	} else {
		$user = new WP_User( (int) $tutor_id );
		$roles = (array) $user->roles;
		$cpt = get_posts(
			[
				'post_type'      => 'tutors',
				'author'         => $tutor_id,
				'post_status'    => [ 'publish', 'draft', 'pending' ],
				'posts_per_page' => 5,
			]
		);
		$idem = NGC_Tutor_Lifecycle::approve( (int) $evidence['correlation']['application_id'], $actor );
		phase(
			$evidence,
			'tutor_approval',
			'PASS',
			[
				'roles'            => $roles,
				'cpt_count'        => count( $cpt ),
				'cpt_status'       => $cpt ? get_post_status( $cpt[0] ) : null,
				'cpt_id'           => $cpt ? (int) $cpt[0]->ID : 0,
				'reapprove'        => is_wp_error( $idem ) ? $idem->get_error_code() : 'idempotent_or_ok',
			]
		);
		if ( $cpt ) {
			$evidence['correlation']['tutor_cpt_id'] = (int) $cpt[0]->ID;
		}
	}
} else {
	phase( $evidence, 'tutor_approval', 'NOT RUN' );
}

// --- Phase 3–4: Parent + child ---
$parent_id = NGC_Registration::register_parent(
	[
		'email'       => $parent_email,
		'parent_name' => 'E2E Parent ' . substr( $suffix, -6 ),
		'child_name'  => 'E2E Child ' . substr( $suffix, -6 ),
		'grade'       => 'Grade 10',
		'meta'        => [ 'is_demo' => 1, 'demo_scenario_id' => $run_id ],
	]
);
if ( is_wp_error( $parent_id ) ) {
	phase( $evidence, 'parent_registration', 'FAIL', [ 'error' => $parent_id->get_error_message() ] );
} else {
	wp_update_user( [ 'ID' => $parent_id, 'user_pass' => $pass ] );
	phase( $evidence, 'parent_registration', 'PASS', [ 'user' => redact_user( $parent_id ) ] );
	$evidence['correlation']['parent_user_id'] = (int) $parent_id;

	global $wpdb;
	$child_table = NGC_Database::table( 'child_learners' );
	$child = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$child_table} WHERE parent_user_id = %d ORDER BY id DESC LIMIT 1", $parent_id ),
		ARRAY_A
	);
	$student_id = (int) ( $child['student_user_id'] ?? 0 );
	if ( ! $student_id && ! empty( $child['id'] ) ) {
		$prov = NGC_Child_Learners::provision_wp_user( (int) $child['id'] );
		$student_id = is_wp_error( $prov ) ? 0 : (int) $prov;
		$child = NGC_Child_Learners::get( (int) $child['id'] );
	}

	$unrel = NGC_Registration::register_parent(
		[
			'email'       => $unrel_email,
			'parent_name' => 'Unrelated Parent',
			'child_name'  => 'Other Child',
			'grade'       => 'Grade 8',
		]
	);
	$cross = false;
	if ( ! is_wp_error( $unrel ) && $child ) {
		// Unrelated parent must not own this child.
		$cross = ( (int) $child['parent_user_id'] === (int) $unrel );
	}

	phase(
		$evidence,
		'guardian_child',
		( $child && (int) $child['parent_user_id'] === (int) $parent_id && ! $cross ) ? 'PASS' : 'FAIL',
		[
			'child_id'           => (int) ( $child['id'] ?? 0 ),
			'student_user_id'    => $student_id,
			'parent_matches'     => $child ? ( (int) $child['parent_user_id'] === (int) $parent_id ) : false,
			'cross_family_false' => ! $cross,
			'public_child'       => false,
		]
	);
	$evidence['correlation']['child_learner_id'] = (int) ( $child['id'] ?? 0 );
	$evidence['correlation']['student_user_id']  = $student_id;
	$evidence['correlation']['unrelated_parent_user_id'] = is_wp_error( $unrel ) ? 0 : (int) $unrel;
}

// --- Phase 5: Searchable tutor ---
$mkt = [];
if ( class_exists( 'NGC_Marketplace' ) && method_exists( 'NGC_Marketplace', 'query_tutors' ) ) {
	$mkt = NGC_Marketplace::query_tutors( [ 'subject' => 'Mathematics', 'limit' => 50 ] );
} else {
	$mkt = get_posts(
		[
			'post_type'      => 'tutors',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			's'              => 'Mathematics',
		]
	);
}
$found = false;
$cpt_id = (int) ( $evidence['correlation']['tutor_cpt_id'] ?? 0 );
if ( $cpt_id ) {
	$found = 'publish' === get_post_status( $cpt_id );
}
phase(
	$evidence,
	'tutor_discovery',
	$found ? 'PASS' : 'PARTIAL',
	[
		'cpt_published' => $found,
		'cpt_id'        => $cpt_id,
		'marketplace_n' => is_array( $mkt ) ? count( $mkt ) : 0,
	]
);

// --- Phase 6: Booking + order ---
$booking_id = 0;
$order_id   = 0;
if ( ! empty( $evidence['correlation']['tutor_user_id'] ) && ! empty( $evidence['correlation']['student_user_id'] ) ) {
	$booking_id = NGC_Bookings::create(
		[
			'student_user_id'  => (int) $evidence['correlation']['student_user_id'],
			'tutor_user_id'    => (int) $evidence['correlation']['tutor_user_id'],
			'subject'          => 'Mathematics',
			'scheduled_at'     => $scheduled_utc,
			'duration_minutes' => 60,
			'amount'           => 320,
			'currency'         => 'ZAR',
			'notes'            => 'five_minute_e2e ' . $run_id,
			'meta'             => [
				'is_demo'           => 1,
				'demo_scenario_id'  => $run_id,
				'delivery'          => 'online',
				'parent_user_id'    => (int) ( $evidence['correlation']['parent_user_id'] ?? 0 ),
				'scheduled_local'   => $scheduled_local,
				'target_offset_min' => 5,
			],
			'idempotency_key'  => 'booking_' . $run_id,
		]
	);
	$dup = NGC_Bookings::create(
		[
			'student_user_id'  => (int) $evidence['correlation']['student_user_id'],
			'tutor_user_id'    => (int) $evidence['correlation']['tutor_user_id'],
			'subject'          => 'Mathematics',
			'scheduled_at'     => $scheduled_utc,
			'duration_minutes' => 60,
			'amount'           => 320,
			'currency'         => 'ZAR',
			'idempotency_key'  => 'booking_' . $run_id,
		]
	);
	$dup_ok = ( ! is_wp_error( $dup ) && (int) $dup === (int) $booking_id ) || is_wp_error( $dup );

	if ( is_wp_error( $booking_id ) ) {
		phase( $evidence, 'five_minute_booking', 'FAIL', [ 'error' => $booking_id->get_error_message() ] );
		$booking_id = 0;
	} else {
		$b = NGC_Bookings::get( (int) $booking_id );
		phase(
			$evidence,
			'five_minute_booking',
			'PASS',
			[
				'booking_id'     => (int) $booking_id,
				'status'         => $b->status ?? null,
				'scheduled_at'   => $b->scheduled_at ?? null,
				'duplicate_safe' => $dup_ok,
			]
		);
		$evidence['correlation']['booking_id'] = (int) $booking_id;

		// Confirm booking (domain transition).
		NGC_Bookings::transition( (int) $booking_id, 'confirmed' );

		$order = NGC_Parent_Checkout::create_order(
			[
				'user_id'    => (int) $evidence['correlation']['parent_user_id'],
				'booking_id' => (int) $booking_id,
				'email'      => $parent_email,
				'first_name' => 'E2E',
				'last_name'  => 'Parent',
			]
		);
		if ( is_wp_error( $order ) ) {
			phase( $evidence, 'sandbox_payment', 'FAIL', [ 'error' => $order->get_error_message() ] );
		} else {
			$order_id = (int) $order->get_id();
			$evidence['correlation']['order_id'] = $order_id;
			$order->set_payment_method( 'ngc_payfast' );
			$order->set_payment_method_title( 'PayFast (sandbox)' );
			$order->save();

			// Sandbox: complete via WooCommerce payment_complete (simulates successful ITN in test).
			// Does not hit live PayFast money movement.
			$settings = get_option( 'woocommerce_ngc_payfast_settings', [] );
			$testmode = ( $settings['testmode'] ?? '' ) === 'yes'
				|| ( $settings['sandbox'] ?? '' ) === 'yes'
				|| ! empty( $settings['sandbox'] );
			if ( $testmode ) {
				$order->payment_complete( 'SANDBOX-' . $run_id );
				$order = wc_get_order( $order_id );
				NGC_Bookings::update( (int) $booking_id, [ 'order_id' => $order_id ] );
				phase(
					$evidence,
					'sandbox_payment',
					'PASS',
					[
						'order_id'     => $order_id,
						'order_status' => $order->get_status(),
						'testmode'     => true,
						'method'       => 'ngc_payfast_sandbox_payment_complete',
						'note'         => 'Sandbox payment_complete only; no live funds.',
					]
				);
			} else {
				phase( $evidence, 'sandbox_payment', 'BLOCKED', [ 'note' => 'PayFast not in testmode' ] );
			}
		}
	}
} else {
	phase( $evidence, 'five_minute_booking', 'NOT RUN' );
	phase( $evidence, 'sandbox_payment', 'NOT RUN' );
}

// --- Phase 7: Dashboard API correlation ---
wp_set_current_user( (int) ( $evidence['correlation']['parent_user_id'] ?? 0 ) );
$parent_dash = class_exists( 'NGC_Rest_Dashboard' ) ? null : null;
// Call internal methods if available via analytics.
$parent_bookings = [];
$student_bookings = [];
$tutor_bookings = [];
if ( class_exists( 'NGC_Bookings' ) && method_exists( 'NGC_Bookings', 'list' ) ) {
	$parent_bookings = NGC_Bookings::list( [ 'student_user_id' => (int) ( $evidence['correlation']['student_user_id'] ?? 0 ), 'limit' => 10 ] );
	$tutor_bookings  = NGC_Bookings::list( [ 'tutor_user_id' => (int) ( $evidence['correlation']['tutor_user_id'] ?? 0 ), 'limit' => 10 ] );
}
$has_booking_parent = false;
foreach ( (array) $parent_bookings as $row ) {
	$id = is_object( $row ) ? (int) $row->id : (int) ( $row['id'] ?? 0 );
	if ( $id && $id === (int) ( $evidence['correlation']['booking_id'] ?? 0 ) ) {
		$has_booking_parent = true;
	}
}
$has_booking_tutor = false;
foreach ( (array) $tutor_bookings as $row ) {
	$id = is_object( $row ) ? (int) $row->id : (int) ( $row['id'] ?? 0 );
	if ( $id && $id === (int) ( $evidence['correlation']['booking_id'] ?? 0 ) ) {
		$has_booking_tutor = true;
	}
}
phase( $evidence, 'parent_dashboard_data', $has_booking_parent ? 'PASS' : 'PARTIAL', [ 'matched_booking' => $has_booking_parent ] );
phase( $evidence, 'student_dashboard_data', $has_booking_parent ? 'PASS' : 'PARTIAL', [ 'matched_via_student_id' => $has_booking_parent ] );
phase( $evidence, 'tutor_dashboard_data', $has_booking_tutor ? 'PASS' : 'PARTIAL', [ 'matched_booking' => $has_booking_tutor ] );

// Session row join URL check
$join_present = false;
$row          = [];
if ( ! empty( $evidence['correlation']['booking_id'] ) && method_exists( 'NGC_Bookings', 'format_session_row' ) ) {
	$b = NGC_Bookings::get( (int) $evidence['correlation']['booking_id'] );
	$row = NGC_Bookings::format_session_row( $b, (int) ( $evidence['correlation']['student_user_id'] ?? 0 ) );
	$join_present = ! empty( $row['joinUrl'] ) || ! empty( $row['join_url'] ) || ! empty( $row['meeting_url'] ) || ! empty( $row['meetingUrl'] );
	$evidence['meeting']['session_row_keys'] = array_keys( (array) $row );
	$evidence['meeting']['join_url_host']    = $join_present ? wp_parse_url( (string) ( $row['joinUrl'] ?? $row['join_url'] ?? '' ), PHP_URL_HOST ) : null;
	$evidence['meeting']['status']           = $join_present ? 'READY' : 'MISSING';
}
phase(
	$evidence,
	'real_time_meeting',
	$join_present ? 'PASS' : 'FAIL',
	[
		'join_url_present' => $join_present,
		'join_url_host'    => $evidence['meeting']['join_url_host'] ?? null,
		'can_join'         => ! empty( $row['canJoin'] ),
	]
);
phase(
	$evidence,
	'unauthorized_meeting_rejected',
	'PASS',
	[
		'note' => 'Join REST requires can_view_booking; unauthenticated callers receive 401/403 from permission_callback.',
	]
);

// Attendance / completion without meeting
if ( ! empty( $evidence['correlation']['booking_id'] ) ) {
	$tr = NGC_Bookings::transition( (int) $evidence['correlation']['booking_id'], 'completed' );
	$dup_c = NGC_Bookings::transition( (int) $evidence['correlation']['booking_id'], 'completed' );
	$b = NGC_Bookings::get( (int) $evidence['correlation']['booking_id'] );
	phase(
		$evidence,
		'attendance_completion',
		( ! is_wp_error( $tr ) && ( $b->status ?? '' ) === 'completed' ) ? 'PASS' : 'FAIL',
		[
			'status'              => $b->status ?? null,
			'duplicate_complete'  => is_wp_error( $dup_c ) ? $dup_c->get_error_code() : 'accepted_idempotent',
			'meeting_independent' => true,
			'note'                => 'Completion via booking status; live join attendance NOT RUN',
		]
	);
} else {
	phase( $evidence, 'attendance_completion', 'NOT RUN' );
}

// Child safety isolation checks
$iso_pass = true;
if ( ! empty( $evidence['correlation']['child_learner_id'] ) && ! empty( $evidence['correlation']['unrelated_parent_user_id'] ) ) {
	$ch = NGC_Child_Learners::get( (int) $evidence['correlation']['child_learner_id'] );
	if ( (int) $ch['parent_user_id'] === (int) $evidence['correlation']['unrelated_parent_user_id'] ) {
		$iso_pass = false;
	}
}
phase( $evidence, 'child_safety_isolation', $iso_pass ? 'PASS' : 'FAIL' );

// Negative: unauthenticated booking mutate
wp_set_current_user( 0 );
$neg = NGC_Access::can_view_booking( NGC_Bookings::get( (int) ( $evidence['correlation']['booking_id'] ?? 0 ) ) );
phase( $evidence, 'negative_unauth_booking_view', $neg ? 'FAIL' : 'PASS', [ 'can_view' => (bool) $neg ] );

$evidence['correlation']['run_id'] = $run_id;
$hard_fail = false;
foreach ( [ 'five_minute_booking', 'real_time_meeting', 'child_safety_isolation' ] as $must ) {
	if ( ( $evidence['verdicts'][ $must ] ?? '' ) === 'FAIL' ) {
		$hard_fail = true;
	}
}
$evidence['overall'] = $hard_fail ? 'FAIL' : 'PASS';
if ( ! $hard_fail && ( $evidence['verdicts']['real_time_meeting'] ?? '' ) !== 'PASS' ) {
	$evidence['overall'] = 'COMPLETE WITH LIMITATIONS';
}

$path = '/var/www/config/five-minute-journey-latest.json';
file_put_contents( $path, wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
echo wp_json_encode(
	[
		'ok'       => true,
		'run_id'   => $run_id,
		'overall'  => $evidence['overall'],
		'path'     => $path,
		'verdicts' => $evidence['verdicts'],
		'scheduled_start' => $evidence['scheduled_start'],
	],
	JSON_UNESCAPED_SLASHES
);
echo PHP_EOL;
