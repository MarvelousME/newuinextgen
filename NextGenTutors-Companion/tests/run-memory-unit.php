<?php
/**
 * Quick unit smoke for Bridge memory settings + write policy.
 *
 * @package NextGenCompanion
 */

require_once __DIR__ . '/phpunit/bootstrap.php';

$fail = 0;
$ok   = 0;

/**
 * @param string $label Assertion label.
 * @param bool   $cond  Condition.
 */
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

$GLOBALS['ngc_test_options'] = [];
NGC_Memory_Service::reset_provider();

$d = NGC_Memory_Settings::defaults();
uassert( 'defaults disabled', false === $d['enabled'] );
uassert( 'proxy off', false === $d['proxy_enabled'] );
uassert( 'skills off', false === $d['skills_enabled'] );
uassert( 'wiki off', false === $d['wiki_enabled'] );
uassert( 'codegraph off', false === $d['codegraph_enabled'] );
uassert( 'minors deny default', false === $d['allow_long_term_minors'] );

NGC_Memory_Settings::update( [ 'proxy_enabled' => true ] );
uassert( 'proxy forced false', false === NGC_Memory_Settings::get()['proxy_enabled'] );

uassert( 'inactive', ! NGC_Memory_Settings::is_active() );
uassert( 'retrieve blocked', ! NGC_Memory_Settings::retrieve_allowed() );
uassert( 'write blocked', ! NGC_Memory_Settings::write_allowed() );

uassert( 'classify forbidden', 'FORBIDDEN' === NGC_Memory_Service::classify( [ 'text' => 'api_key secret' ] ) );
uassert( 'classify minor', 'MINOR_LINKED' === NGC_Memory_Service::classify( [ 'text' => 'x', 'minor_linked' => true ] ) );

$gate = NGC_Memory_Service::write_policy_gate( 'MINOR_LINKED', [] );
uassert( 'deny minors', ! $gate['allow'] );

$gate2 = NGC_Memory_Service::write_policy_gate( 'ROUTINE', [ 'tutoring_data' => true ] );
uassert( 'deny tutoring without allow', ! $gate2['allow'] );

$r = NGC_Memory_Service::retrieve_safe( [ 'query' => 'hi' ] );
uassert( 'retrieve safe ok', ! empty( $r['ok'] ) && '' === $r['context_text'] );

$w = NGC_Memory_Service::write_safe( [ 'text' => 'hi', 'async' => false ] );
uassert( 'write safe disabled', ! empty( $w['ok'] ) && empty( $w['written'] ) );

$noop = new NGC_Memory_Noop_Provider();
uassert( 'noop slug', 'noop' === $noop->slug() );
uassert( 'noop health', ! empty( $noop->health()['ok'] ) );

echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS ($ok)\n";
exit( $fail ? 1 : 0 );
