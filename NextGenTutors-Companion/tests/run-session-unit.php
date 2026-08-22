<?php
/**
 * Quick unit smoke for session states + product definitions.
 *
 * @package NextGenCompanion
 */

require_once __DIR__ . '/phpunit/bootstrap.php';

$fail = 0;
$ok   = 0;

function uassert( $label, $cond ) {
	global $fail, $ok;
	if ( $cond ) {
		echo "PASS $label\n";
		++$ok;
	} else {
		echo "FAIL $label\n";
		++$fail;
	}
}

uassert( 'paid→ready invalid', ! NGC_Session_States::can_transition( 'paid', 'ready' ) );
uassert( 'paid→booking_confirmed', NGC_Session_States::can_transition( 'paid', 'booking_confirmed' ) );
uassert( 'ready joinable', NGC_Session_States::is_joinable( 'ready' ) );
uassert( 'cancelled not joinable', ! NGC_Session_States::is_joinable( 'cancelled' ) );

$defs = NGC_Product_Provisioner::definitions();
uassert( 'definitions non-empty', count( $defs ) > 0 );
$keys = array_column( $defs, 'key' );
uassert( 'has ngt-online-1hr', in_array( 'ngt-online-1hr', $keys, true ) );
uassert( 'unique keys', count( $keys ) === count( array_unique( $keys ) ) );

require_once $root . '/includes/session/class-ngc-session-orchestrator.php';
$life = NGC_Session_Orchestrator::initial_lifecycle( false, true, false );
uassert( 'lifecycle paid', $life['status'] === NGC_Session_States::PAID && $life['may_ready'] );
uassert( 'idempotency key', NGC_Session_Orchestrator::ensure_idempotency_key( 1, 2 ) === 'ensure-session:1:2' );
$missing = NGC_Session_Orchestrator::ensure_provisioned( [] );
uassert( 'ensure args required', is_wp_error( $missing ) && $missing->get_error_code() === 'ngc_session_args' );

echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS ($ok)\n";
exit( $fail ? 1 : 0 );
