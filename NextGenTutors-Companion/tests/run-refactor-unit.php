<?php
/**
 * Lightweight characterization tests for the 1–7 refactor slices.
 *
 * Usage: php tests/run-refactor-unit.php
 *
 * @package NextGenCompanion
 */

require_once __DIR__ . '/phpunit/bootstrap.php';

$root = dirname( __DIR__ );
$fail = 0;
$ok   = 0;

function rassert( $label, $cond ) {
	global $fail, $ok;
	if ( $cond ) {
		echo "PASS $label\n";
		++$ok;
	} else {
		echo "FAIL $label\n";
		++$fail;
	}
}

require_once $root . '/includes/session/class-ngc-session-orchestrator.php';
require_once $root . '/includes/class-ngc-uuid.php';
require_once $root . '/includes/class-ngc-bookings.php';
require_once $root . '/includes/rest/class-ngc-rest-dashboard.php';
require_once $root . '/includes/database/class-ngc-schema-statements.php';

$life = NGC_Session_Orchestrator::initial_lifecycle( false, true, false );
rassert( 'lifecycle paid ready', NGC_Session_States::PAID === $life['status'] && $life['may_ready'] );
rassert( 'ensure args required', is_wp_error( NGC_Session_Orchestrator::ensure_provisioned( [] ) ) );

rassert( 'booking statuses', [ 'requested', 'confirmed', 'cancelled', 'completed' ] === NGC_Bookings::statuses() );
rassert( 'booking invalid status', is_wp_error( NGC_Bookings::normalize_status( 'paid' ) ) );
$build = new ReflectionMethod( 'NGC_Bookings', 'build_create_row' );
$build->setAccessible( true );
$row = $build->invoke( null, [ 'student_user_id' => 3, 'tutor_user_id' => 7, 'subject' => 'Math' ] );
rassert( 'booking create row requested', 'requested' === $row['status'] && 'ZAR' === $row['currency'] && 60 === $row['duration_minutes'] );

$contract = json_decode( (string) file_get_contents( __DIR__ . '/phpunit/fixtures/tutor-dashboard-keys.json' ), true );
$kpis     = NGC_Rest_Dashboard::compose_tutor_kpis( 1, 2, 3, 4 );
$payload  = NGC_Rest_Dashboard::compose_tutor_data( [ 'id' => 1 ], $kpis, [ 'status' => 'pending' ], [ 'recent' => [], 'next' => null ] );
rassert( 'tutor payload keys', $contract['data'] === array_keys( $payload ) );
rassert( 'tutor kpi keys', $contract['kpis'] === array_keys( $payload['kpis'] ) );
$learner = NGC_Rest_Dashboard::compose_learner_data( [ 'id' => 1 ], [ 'sessionsCompleted' => 0 ], [], [ 'learners' => [] ] );
rassert( 'learner payload keys', [ 'user', 'kpis', 'learners', 'recentSessions', 'nextSession' ] === array_keys( $learner ) );
rassert( 'learner nextSession empty is null', null === $learner['nextSession'] );
$app = NGC_Rest_Dashboard::application_payload( (object) [ 'status' => 'pending', 'review_notes' => 'n', 'created_at' => 'a', 'updated_at' => 'b' ] );
rassert( 'application payload keys', $contract['application'] === array_keys( $app ) );

require_once $root . '/includes/admin/framework/class-ngc-admin-catalog.php';
$screens = NGC_Admin_Catalog::screen_definitions();
$slugs   = array_column( $screens, 'slug' );
rassert( 'screens count', count( $screens ) >= 20 );
rassert( 'mission-control screen', in_array( 'ngtmc-mission-control', $slugs, true ) );
rassert( 'subjects screen', in_array( 'ngt-edu-subjects', $slugs, true ) );
rassert( 'screens second load is array', is_array( NGC_Admin_Catalog::screen_definitions() ) );

$sql    = NGC_Schema_Statements::create_sql( NGC_Schema_Statements::fixture_tables(), 'DEFAULT CHARSET utf8mb4' );
$joined = implode( "\n", $sql );
$hash   = NGC_Schema_Statements::canonical_sql_hash();
$frozen = trim( (string) file_get_contents( __DIR__ . '/phpunit/fixtures/schema-sql.sha256' ) );
rassert( 'schema has bookings', false !== strpos( $joined, 'CREATE TABLE wp_ngc_bookings' ) );
rassert( 'schema has amelia id', false !== strpos( $joined, 'amelia_booking_id' ) );
rassert( 'schema has reminder key', false !== strpos( $joined, 'booking_reminder' ) );
rassert( 'schema sql hash frozen', $frozen === $hash );

$dash_src = (string) file_get_contents( $root . '/includes/rest/class-ngc-rest-dashboard.php' );
rassert( 'tutor() uses compose_tutor_data', (bool) preg_match( '/function tutor\s*\(.*self::compose_tutor_data\s*\(/s', $dash_src ) );
rassert( 'student() uses compose_learner_data', (bool) preg_match( '/function student\s*\(.*self::compose_learner_data\s*\(/s', $dash_src ) );
rassert( 'session_digest reuses recent row', false !== strpos( $dash_src, '$next = $recent[ $i ] ?? null;' ) || false !== strpos( $dash_src, '$next = $recent[ $i ];' ) );

$replay = new ReflectionMethod( 'NGC_Bookings', 'idempotency_replay_id' );
$replay->setAccessible( true );
$zero = $replay->invoke( null, [ 'result' => [ 'booking_id' => 0 ] ] );
rassert( 'replay id 0 is error', is_wp_error( $zero ) && 'ngc_booking_idempotency_replay' === $zero->get_error_code() );

$req = new ReflectionMethod( 'NGC_Session_Orchestrator', 'require_session_row' );
$req->setAccessible( true );
$missing = $req->invoke( null, null );
rassert( 'null session after upsert is error', is_wp_error( $missing ) && 'ngc_session_missing' === $missing->get_error_code() );

$catalog_src = (string) file_get_contents( $root . '/includes/admin/framework/class-ngc-admin-catalog.php' );
rassert( 'screens loaded with require', false !== strpos( $catalog_src, "require __DIR__ . '/screens.php'" ) && false === strpos( $catalog_src, "require_once __DIR__ . '/screens.php'" ) );

$tag_dir = dirname( $root ) . '/NextGenTutors-BeyondInfinity/inc/tags';
$fns     = [];
foreach ( glob( $tag_dir . '/*.php' ) as $file ) {
	preg_match_all( '/^function (bi_\w+)/m', file_get_contents( $file ), $m );
	$fns = array_merge( $fns, $m[1] );
}
rassert( 'template tag functions preserved', 48 === count( $fns ) && count( $fns ) === count( array_unique( $fns ) ) );
rassert( 'bi_render_tutor_profile present', in_array( 'bi_render_tutor_profile', $fns, true ) );
rassert( 'bi_get_phone present', in_array( 'bi_get_phone', $fns, true ) );

echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS ($ok)\n";
exit( $fail ? 1 : 0 );
