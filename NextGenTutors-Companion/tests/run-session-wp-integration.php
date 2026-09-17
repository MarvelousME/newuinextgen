<?php
/**
 * Live WordPress integration: catalogue → booking → order → payment settle → session → join policy.
 *
 * Invoked from the WordPress container:
 *   php /var/www/html/wp-content/plugins/NextGenTutors-Companion/tests/run-session-wp-integration.php
 *
 * Does not fabricate PayFast ITN payloads. Settlement uses WooCommerce payment_complete(),
 * the same hook PayFast ITN and processing status already fire.
 *
 * @package NextGenCompanion
 */

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	$wp_load = '/var/www/html/wp-load.php';
}
if ( ! is_readable( $wp_load ) ) {
	fwrite( STDERR, "wp-load.php not found\n" );
	exit( 2 );
}

require_once $wp_load;

$fail = 0;
$ok   = 0;
$evidence = [
	'run_id'     => 'booking-commerce-' . gmdate( 'Ymd-His' ),
	'started_at' => gmdate( 'c' ),
	'checks'     => [],
];

/**
 * @param string $label Label.
 * @param bool   $cond  Condition.
 * @param mixed  $data  Extra.
 */
function iassert( $label, $cond, $data = null ) {
	global $fail, $ok, $evidence;
	$evidence['checks'][] = [
		'label'  => $label,
		'result' => $cond ? 'PASS' : 'FAIL',
		'data'   => $data,
	];
	if ( $cond ) {
		echo "PASS $label\n";
		++$ok;
	} else {
		echo "FAIL $label\n";
		if ( null !== $data ) {
			echo '  ' . wp_json_encode( $data ) . "\n";
		}
		++$fail;
	}
}

if ( ! class_exists( 'NGC_Database' ) ) {
	fwrite( STDERR, "Companion not loaded\n" );
	exit( 2 );
}

NGC_Database::create_tables();
if ( class_exists( 'NGC_Roles' ) ) {
	NGC_Roles::install();
}
if ( class_exists( 'NGC_Capability_Registry' ) ) {
	NGC_Capability_Registry::load();
}
iassert( 'booking.create capability registered', class_exists( 'NGC_Capability_Registry' ) && NGC_Capability_Registry::has( 'booking.create' ) );
iassert( 'sessions table exists', (bool) $GLOBALS['wpdb']->get_var( "SHOW TABLES LIKE '{$GLOBALS['wpdb']->prefix}ngc_sessions'" ) );
iassert( 'invoices table exists', (bool) $GLOBALS['wpdb']->get_var( "SHOW TABLES LIKE '{$GLOBALS['wpdb']->prefix}ngc_invoices'" ) );

$wc_active = class_exists( 'WooCommerce' );
iassert( 'woocommerce active', $wc_active );

$lms_available = class_exists( 'NGC_Session_Learning_Adapter' ) && ( new NGC_Session_Learning_Adapter() )->is_available();
iassert( 'masterstudy detected', $lms_available, [ 'available' => $lms_available ] );

$catalogue = [];
if ( $wc_active && class_exists( 'NGC_Product_Provisioner' ) ) {
	$catalogue = NGC_Product_Provisioner::provision_defaults();
}
iassert( 'catalogue provisioned', ! empty( $catalogue['success'] ) && (int) ( $catalogue['count'] ?? 0 ) >= 16, $catalogue );
$product_id = class_exists( 'NGC_Product_Provisioner' ) ? (int) NGC_Product_Provisioner::product_id_for_key( 'NGT-ONLINE-1HR' ) : 0;
iassert( 'NGT-ONLINE-1HR product id', $product_id > 0, [ 'product_id' => $product_id ] );

$password = 'NgtTest!2026';
$parent   = get_user_by( 'login', 'ngt_e2e_parent' );
if ( ! $parent ) {
	$id = wp_insert_user(
		[
			'user_login'   => 'ngt_e2e_parent',
			'user_pass'    => $password,
			'user_email'   => 'ngt.e2e.parent@example.test',
			'display_name' => 'E2E Parent',
			'role'         => 'parent',
		]
	);
	$parent = is_wp_error( $id ) ? null : get_user_by( 'id', $id );
}
$student = get_user_by( 'login', 'ngt_e2e_child' );
if ( ! $student ) {
	$id = wp_insert_user(
		[
			'user_login'   => 'ngt_e2e_child',
			'user_pass'    => $password,
			'user_email'   => 'ngt.e2e.child@example.test',
			'display_name' => 'E2E Child',
			'role'         => 'child_learner',
		]
	);
	$student = is_wp_error( $id ) ? null : get_user_by( 'id', $id );
}
$tutor = get_user_by( 'login', 'ngt_e2e_tutor' );
if ( ! $tutor ) {
	$id = wp_insert_user(
		[
			'user_login'   => 'ngt_e2e_tutor',
			'user_pass'    => $password,
			'user_email'   => 'ngt.e2e.tutor@example.test',
			'display_name' => 'E2E Tutor',
			'role'         => 'tutor',
		]
	);
	$tutor = is_wp_error( $id ) ? null : get_user_by( 'id', $id );
}

if ( $parent instanceof WP_User ) {
	wp_set_password( $password, $parent->ID );
	$parent->set_role( 'parent' );
}
if ( $student instanceof WP_User ) {
	wp_set_password( $password, $student->ID );
	$student->set_role( 'child_learner' );
}
if ( $tutor instanceof WP_User ) {
	wp_set_password( $password, $tutor->ID );
	$tutor->set_role( 'tutor' );
}

iassert( 'parent user', $parent instanceof WP_User, $parent ? $parent->ID : null );
iassert( 'child user', $student instanceof WP_User, $student ? $student->ID : null );
iassert( 'tutor user', $tutor instanceof WP_User, $tutor ? $tutor->ID : null );
iassert( 'parent can ngc_book_sessions', $parent instanceof WP_User && user_can( $parent, 'ngc_book_sessions' ) );

if ( $parent && $student ) {
	update_user_meta( $student->ID, 'ngc_parent_user_id', $parent->ID );
	update_user_meta( $student->ID, 'ngc_is_minor', '1' );
}

$identity = $parent && $student ? NGC_Session_Identity::resolve_parties( $parent->ID, $student->ID ) : [];
iassert( 'parent pays for child', ( $identity['mode'] ?? '' ) === 'parent_pays_for_child' );

$adult = get_user_by( 'login', 'ngt_e2e_adult' );
if ( ! $adult ) {
	$id = wp_insert_user(
		[
			'user_login'   => 'ngt_e2e_adult',
			'user_pass'    => $password,
			'user_email'   => 'ngt.e2e.adult@example.test',
			'display_name' => 'E2E Adult Student',
			'role'         => 'student',
		]
	);
	$adult = is_wp_error( $id ) ? null : get_user_by( 'id', $id );
}
$adult_id = $adult instanceof WP_User ? $adult->ID : 0;
$adult_mode = $adult_id ? NGC_Session_Identity::resolve_parties( $adult_id, $adult_id ) : [];
iassert( 'adult self purchase identity', ( $adult_mode['mode'] ?? '' ) === 'adult_student_self_purchase' );

$start = gmdate( 'Y-m-d H:i:s', time() + ( 7 * DAY_IN_SECONDS ) + ( wp_rand( 1, 600 ) * 60 ) );
$end   = gmdate( 'Y-m-d H:i:s', strtotime( $start . ' +60 minutes' ) );

$booking_id = 0;
if ( $parent && $student && $tutor ) {
	wp_set_current_user( $parent->ID );
	$created = NGC_Bookings::create(
		[
			'student_user_id'  => $student->ID,
			'tutor_user_id'    => $tutor->ID,
			'subject'          => 'mathematics',
			'scheduled_at'     => $start,
			'duration_minutes' => 60,
			'amount'           => 320,
			'currency'         => 'ZAR',
			'notes'            => 'e2e-session-integration',
			'actor_user_id'    => $parent->ID,
		]
	);
	iassert(
		'booking.create allowed for parent',
		! is_wp_error( $created ),
		is_wp_error( $created ) ? $created->get_error_message() : null
	);
	if ( is_wp_error( $created ) ) {
		global $wpdb;
		$wpdb->insert(
			NGC_Database::table( 'bookings' ),
			[
				'uuid'             => wp_generate_uuid4(),
				'student_user_id'  => $student->ID,
				'tutor_user_id'    => $tutor->ID,
				'subject'          => 'mathematics',
				'scheduled_at'     => $start,
				'duration_minutes' => 60,
				'status'           => 'requested',
				'amount'           => 320,
				'currency'         => 'ZAR',
				'notes'            => 'e2e-session-integration-direct',
				'created_at'       => current_time( 'mysql', true ),
				'updated_at'       => current_time( 'mysql', true ),
			]
		);
		$booking_id = (int) $wpdb->insert_id;
		iassert( 'booking created (direct after policy)', $booking_id > 0, $created->get_error_message() );
	} else {
		$booking_id = (int) $created;
		iassert( 'booking created', $booking_id > 0, $booking_id );
	}
}

$order_id = 0;
if ( $wc_active && $product_id && $parent && $booking_id ) {
	$order = NGC_Parent_Checkout::create_order(
		[
			'user_id'         => $parent->ID,
			'student_user_id' => $student->ID,
			'tutor_user_id'   => $tutor->ID,
			'booking_id'      => $booking_id,
			'product_key'     => 'NGT-ONLINE-1HR',
			'subject'         => 'mathematics',
			'scheduled_start' => $start,
			'email'           => $parent->user_email,
			'first_name'      => 'E2E',
			'last_name'       => 'Parent',
		]
	);
	if ( is_wp_error( $order ) ) {
		iassert( 'order created', false, $order->get_error_message() );
	} else {
		$order_id = (int) $order->get_id();
		$order->update_status( 'pending', 'e2e pending payment', true );
		iassert( 'order created pending', $order_id > 0 && (float) $order->get_total() === 320.0, [ 'order_id' => $order_id, 'total' => $order->get_total() ] );
	}
}

$unpaid = ( $order_id && $booking_id ) ? NGC_Ensure_Session_Provisioned::run( $order_id, $booking_id ) : null;
iassert(
	'unpaid session awaiting payment',
	is_array( $unpaid ) && NGC_Session_States::AWAITING_PAYMENT === ( $unpaid['status'] ?? '' ),
	is_wp_error( $unpaid ) ? $unpaid->get_error_message() : ( $unpaid['status'] ?? null )
);

$launch_unpaid = ( $unpaid && ! is_wp_error( $unpaid ) ) ? NGC_Session_Launch::launch( (int) $unpaid['id'], $student->ID ) : null;
iassert( 'unpaid launch denied', is_wp_error( $launch_unpaid ), is_wp_error( $launch_unpaid ) ? $launch_unpaid->get_error_code() : 'not_error' );

$stranger = get_user_by( 'login', 'ngt_e2e_stranger' );
if ( ! $stranger ) {
	$id = wp_insert_user(
		[
			'user_login' => 'ngt_e2e_stranger',
			'user_pass'  => $password,
			'user_email' => 'ngt.e2e.stranger@example.test',
			'role'       => 'subscriber',
		]
	);
	$stranger = is_wp_error( $id ) ? null : get_user_by( 'id', $id );
}

if ( $wc_active && $order_id ) {
	$order = wc_get_order( $order_id );
	$order->set_payment_method( 'ngc_payfast' );
	$order->save();
	$order->payment_complete( 'e2e-ref-' . $order_id );
}

$paid = ( $order_id && $booking_id ) ? NGC_Ensure_Session_Provisioned::run( $order_id, $booking_id ) : null;
$paid_ok = is_array( $paid ) && in_array( $paid['status'] ?? '', [ NGC_Session_States::READY, NGC_Session_States::JOIN_WINDOW_OPEN ], true );
iassert(
	'paid session ready',
	$paid_ok,
	is_wp_error( $paid ) ? $paid->get_error_message() : $paid
);

$dup = ( $order_id && $booking_id ) ? NGC_Ensure_Session_Provisioned::run( $order_id, $booking_id ) : null;
iassert(
	'duplicate provision same session',
	is_array( $dup ) && is_array( $paid ) && (int) $dup['id'] === (int) $paid['id'],
	[ 'first' => $paid['id'] ?? null, 'second' => $dup['id'] ?? null ]
);

$invoice = $order_id && class_exists( 'NGC_Invoices' ) ? NGC_Invoices::get_by_order_id( $order_id ) : null;
iassert( 'invoice one per order', $invoice && (float) $invoice->amount === 320.0, $invoice );

if ( $paid_ok && $stranger ) {
	$denied = NGC_Session_Launch::launch( (int) $paid['id'], $stranger->ID );
	iassert( 'stranger launch denied', is_wp_error( $denied ), is_wp_error( $denied ) ? $denied->get_error_code() : 'not_error' );
}

$early = $paid_ok ? NGC_Session_Launch::launch( (int) $paid['id'], $student->ID ) : null;
iassert(
	'join too_early before window',
	is_wp_error( $early ) && 'ngc_join_denied' === $early->get_error_code(),
	is_wp_error( $early ) ? $early->get_error_data() : $early
);

if ( $paid_ok ) {
	NGC_Session_Repository::update(
		(int) $paid['id'],
		[
			'scheduled_start' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
			'scheduled_end'   => gmdate( 'Y-m-d H:i:s', time() + 3540 ),
		]
	);
	$opened = NGC_Session_Repository::get( (int) $paid['id'] );
	if ( NGC_Session_State_Machine::can_transition( (string) $opened['status'], NGC_Session_States::JOIN_WINDOW_OPEN ) ) {
		NGC_Session_Repository::transition( (int) $paid['id'], NGC_Session_States::JOIN_WINDOW_OPEN );
	}
	$join_student = NGC_Session_Launch::launch( (int) $paid['id'], $student->ID );
	iassert(
		'student join authorized',
		is_array( $join_student ) && ! empty( $join_student['launch_url'] ),
		is_wp_error( $join_student ) ? $join_student->get_error_message() : $join_student
	);
	$join_tutor = NGC_Session_Launch::launch( (int) $paid['id'], $tutor->ID );
	iassert(
		'tutor join authorized',
		is_array( $join_tutor ) && ! empty( $join_tutor['launch_url'] ),
		is_wp_error( $join_tutor ) ? $join_tutor->get_error_message() : $join_tutor
	);
	$completed = null;
	ob_start();
	try {
		$completed = NGC_Session_Launch::complete( (int) $paid['id'], $tutor->ID );
	} catch ( Throwable $e ) {
		$completed = new WP_Error( 'ngc_complete_threw', $e->getMessage() );
	}
	ob_end_clean();
	iassert(
		'session completed',
		is_array( $completed ) && NGC_Session_States::COMPLETED === ( $completed['status'] ?? '' ),
		is_wp_error( $completed ) ? $completed->get_error_message() : ( $completed['status'] ?? null )
	);
}

function ngt_test_insert_booking( $student_id, $tutor_id, $scheduled_at, $notes ) {
	global $wpdb;
	$wpdb->insert(
		NGC_Database::table( 'bookings' ),
		[
			'uuid'             => wp_generate_uuid4(),
			'student_user_id'  => (int) $student_id,
			'tutor_user_id'    => (int) $tutor_id,
			'subject'          => 'mathematics',
			'scheduled_at'     => $scheduled_at,
			'duration_minutes' => 60,
			'status'           => 'requested',
			'amount'           => 320,
			'currency'         => 'ZAR',
			'notes'            => $notes,
			'created_at'       => current_time( 'mysql', true ),
			'updated_at'       => current_time( 'mysql', true ),
		]
	);
	return (int) $wpdb->insert_id;
}

if ( $wc_active && $product_id && $parent && $student && $tutor ) {
	$fail_booking_id = ngt_test_insert_booking( $student->ID, $tutor->ID, gmdate( 'Y-m-d H:i:s', time() + 86400 ), 'e2e-failed-payment' );
	$fail_order      = $fail_booking_id ? NGC_Parent_Checkout::create_order(
		[
			'user_id'         => $parent->ID,
			'student_user_id' => $student->ID,
			'tutor_user_id'   => $tutor->ID,
			'booking_id'      => $fail_booking_id,
			'product_key'     => 'NGT-ONLINE-1HR',
			'subject'         => 'mathematics',
			'email'           => $parent->user_email,
		]
	) : new WP_Error( 'no_booking', 'booking insert failed' );
	if ( ! is_wp_error( $fail_order ) ) {
		$fail_order_id = (int) $fail_order->get_id();
		NGC_Ensure_Session_Provisioned::run( $fail_order_id, $fail_booking_id );
		$fail_order->update_status( 'failed', 'e2e failed payment', true );
		$failed_session = NGC_Session_Repository::get_by_order_id( $fail_order_id );
		iassert(
			'failed payment session failed',
			$failed_session && NGC_Session_States::FAILED === ( $failed_session['status'] ?? '' ),
			$failed_session
		);
	} else {
		iassert( 'failed payment order', false, $fail_order->get_error_message() );
	}

	$ref_booking_id = ngt_test_insert_booking( $student->ID, $tutor->ID, gmdate( 'Y-m-d H:i:s', time() + 172800 ), 'e2e-refund' );
	$ref_order      = $ref_booking_id ? NGC_Parent_Checkout::create_order(
		[
			'user_id'         => $parent->ID,
			'student_user_id' => $student->ID,
			'tutor_user_id'   => $tutor->ID,
			'booking_id'      => $ref_booking_id,
			'product_key'     => 'NGT-ONLINE-1HR',
			'subject'         => 'mathematics',
			'email'           => $parent->user_email,
		]
	) : new WP_Error( 'no_booking', 'booking insert failed' );
	if ( ! is_wp_error( $ref_order ) ) {
		$ref_order_id = (int) $ref_order->get_id();
		$ref_order->payment_complete( 'e2e-ref-refund' );
		NGC_Ensure_Session_Provisioned::run( $ref_order_id, $ref_booking_id );
		$ref_order->update_status( 'refunded', 'e2e refund', true );
		$refunded = NGC_Session_Repository::get_by_order_id( $ref_order_id );
		iassert(
			'refunded session refunded',
			$refunded && NGC_Session_States::REFUNDED === ( $refunded['status'] ?? '' ),
			$refunded
		);
	} else {
		iassert( 'refund order', false, $ref_order->get_error_message() );
	}
}

$evidence['passed']     = $ok;
$evidence['failed']     = $fail;
$evidence['finished_at'] = gmdate( 'c' );
$evidence['masterstudy'] = $lms_available ? 'available' : 'unavailable';
$evidence['relationship'] = [
	'order_id'   => $order_id,
	'booking_id' => $booking_id,
	'session'    => is_array( $paid ) ? [
		'id'              => $paid['id'] ?? null,
		'correlation_id'  => $paid['correlation_id'] ?? null,
		'status'          => $paid['status'] ?? null,
		'course_id'       => $paid['masterstudy_course_id'] ?? null,
		'lesson_id'       => $paid['masterstudy_lesson_id'] ?? null,
		'meeting_id'      => $paid['meeting_id'] ?? null,
		'payment_status'  => $paid['payment_status'] ?? null,
	] : null,
	'invoice_id' => $invoice->id ?? null,
];

$out_dir = dirname( __DIR__ ) . '/tests/evidence/' . $evidence['run_id'];
if ( ! is_dir( $out_dir ) ) {
	wp_mkdir_p( $out_dir );
}
file_put_contents( $out_dir . '/wp-integration.json', wp_json_encode( $evidence, JSON_PRETTY_PRINT ) );
echo "\n$ok passed, $fail failed\n";
echo 'evidence: ' . $out_dir . "/wp-integration.json\n";
exit( $fail ? 1 : 0 );
