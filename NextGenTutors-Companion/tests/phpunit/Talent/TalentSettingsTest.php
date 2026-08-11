<?php
/**
 * Talent settings / scorer PHPUnit tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * Talent Intelligence unit tests.
 */
class TalentSettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['ngc_test_options'] = [];
		$base = dirname( __DIR__, 2 ) . '/../includes/talent/';
		foreach (
			[
				'interface-ngc-talent-intelligence-provider.php',
				'class-ngc-talent-settings.php',
				'class-ngc-talent-fairness.php',
				'class-ngc-talent-noop-provider.php',
				'class-ngc-talent-bridge-rules-provider.php',
				'class-ngc-talent-service.php',
			] as $file
		) {
			$path = $base . $file;
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
		NGC_Talent_Service::reset_provider();
	}

	public function test_defaults_safe() {
		$d = NGC_Talent_Settings::defaults();
		$this->assertFalse( $d['enabled'] );
		$this->assertTrue( $d['auto_approve_forbidden'] );
	}

	public function test_high_match_recommendation() {
		$p = new NGC_Talent_Bridge_Rules_Provider();
		$r = $p->evaluate_match(
			[
				'subjects' => [ 'Mathematics' ],
				'grades' => [ '12' ],
				'province' => 'Gauteng',
				'location' => 'Gauteng',
				'deliveryModes' => [ 'online' ],
				'experience_years' => 5,
				'qualifications' => [ 'PGCE' ],
				'languages' => [ 'English' ],
				'bio' => 'experienced',
			],
			[
				'subjects' => [ 'Mathematics' ],
				'grades' => [ '12' ],
				'location' => 'Gauteng',
				'deliveryModes' => [ 'online' ],
				'experience_years_min' => 2,
				'qualifications' => [ 'PGCE' ],
				'languages' => [ 'English' ],
			]
		);
		$this->assertTrue( $r['ok'] );
		$this->assertNotNull( $r['score'] );
		$this->assertArrayHasKey( 'components', $r );
		$this->assertTrue( $r['autoApproveForbidden'] );
	}

	public function test_fairness_strips_protected() {
		$s = NGC_Talent_Fairness::scrub( [ 'gender' => 'x', 'subjects' => [ 'Math' ] ] );
		$this->assertContains( 'gender', $s['stripped'] );
		$this->assertArrayHasKey( 'subjects', $s['clean'] );
	}
}
