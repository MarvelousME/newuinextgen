<?php
/**
 * Quick unit smoke for Talent Intelligence scorer + fairness.
 *
 * @package NextGenCompanion
 */

require_once __DIR__ . '/phpunit/bootstrap.php';

$fail = 0;
$ok   = 0;

/**
 * @param string $label Label.
 * @param bool   $cond Condition.
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

$base = dirname( __DIR__ ) . '/includes/talent/';
require_once $base . 'interface-ngc-talent-intelligence-provider.php';
require_once $base . 'class-ngc-talent-settings.php';
require_once $base . 'class-ngc-talent-fairness.php';
require_once $base . 'class-ngc-talent-profile-helper.php';
require_once $base . 'class-ngc-talent-noop-provider.php';
require_once $base . 'class-ngc-talent-bridge-rules-provider.php';
require_once $base . 'class-ngc-talent-service.php';

$GLOBALS['ngc_test_options'] = [];

$d = NGC_Talent_Settings::defaults();
uassert( 'defaults off', false === $d['enabled'] );
uassert( 'auto approve forbidden', true === $d['auto_approve_forbidden'] );
uassert( 'rank find tutor off', false === $d['rank_find_tutor'] );

NGC_Talent_Settings::update( [ 'auto_approve_forbidden' => false ] );
uassert( 'auto approve stays forbidden', true === NGC_Talent_Settings::get()['auto_approve_forbidden'] );

$scrub = NGC_Talent_Fairness::scrub( [ 'subjects' => [ 'Math' ], 'ethnicity' => 'x', 'gender' => 'y' ] );
uassert( 'scrub strips ethnicity', in_array( 'ethnicity', $scrub['stripped'], true ) );
uassert( 'scrub keeps subjects', ! empty( $scrub['clean']['subjects'] ) );

$provider = new NGC_Talent_Bridge_Rules_Provider();
$eval = $provider->evaluate_match(
	[
		'subjects' => [ 'Mathematics', 'Physics' ],
		'grades'   => [ '10', '11', '12' ],
		'province' => 'Gauteng',
		'location' => 'Gauteng',
		'deliveryModes' => [ 'online' ],
		'bio' => '5 years teaching CAPS Mathematics',
		'experience_years' => 5,
		'qualifications' => [ 'PGCE' ],
		'languages' => [ 'English' ],
	],
	[
		'subjects' => [ 'Mathematics' ],
		'grades'   => [ '12' ],
		'province' => 'Gauteng',
		'location' => 'Gauteng',
		'deliveryModes' => [ 'online' ],
		'experience_years_min' => 3,
		'qualifications' => [ 'PGCE' ],
		'languages' => [ 'English' ],
	]
);

uassert( 'eval ok', ! empty( $eval['ok'] ) );
uassert( 'score numeric', is_numeric( $eval['score'] ) );
uassert( 'has components', ! empty( $eval['components'] ) );
uassert( 'recommended or partial', in_array( $eval['recommendation'], [ 'RECOMMENDED_FOR_REVIEW', 'PARTIAL_MATCH', 'LOW_MATCH', 'INSUFFICIENT_DATA' ], true ) );
uassert( 'autoApproveForbidden', ! empty( $eval['autoApproveForbidden'] ) );
uassert( 'safeguarding note', ! empty( $eval['safeguarding'] ) );

$low = $provider->evaluate_match(
	[ 'subjects' => [ 'Art' ], 'bio' => '' ],
	[ 'subjects' => [ 'Mathematics', 'Physics' ], 'grades' => [ '12' ] ]
);
uassert( 'low/insufficient', in_array( $low['recommendation'], [ 'LOW_MATCH', 'INSUFFICIENT_DATA', 'PARTIAL_MATCH' ], true ) );

$noop = new NGC_Talent_Noop_Provider();
uassert( 'noop slug', 'noop' === $noop->slug() );

$skills = NGC_Talent_Service::extract_skills( [ 'subjects' => [ 'Math' ], 'bio' => 'CAPS English tutor', 'race' => 'nope' ] );
uassert( 'extract skills', in_array( 'math', $skills['skills'], true ) || in_array( 'english', $skills['skills'], true ) );

echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS ($ok)\n";
exit( $fail ? 1 : 0 );
