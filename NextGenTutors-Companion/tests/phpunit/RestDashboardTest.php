<?php
/**
 * Dashboard REST contract tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * NGC_Rest_Dashboard.
 */
class RestDashboardTest extends TestCase {

	/** @var array{data:string[],kpis:string[],application:string[]} */
	private $contract;

	protected function setUp(): void {
		require_once dirname( __DIR__, 2 ) . '/includes/rest/class-ngc-rest-dashboard.php';
		$raw = file_get_contents( __DIR__ . '/fixtures/tutor-dashboard-keys.json' );
		$this->contract = json_decode( (string) $raw, true );
	}

	public function test_composed_tutor_payload_keys_match_fixture() {
		$kpis    = NGC_Rest_Dashboard::compose_tutor_kpis( 100.5, 3, 4.8, 12.0 );
		$payload = NGC_Rest_Dashboard::compose_tutor_data(
			[ 'id' => 7, 'displayName' => 'Ada' ],
			$kpis,
			[
				'status'      => 'approved',
				'reviewNotes' => '',
				'submittedAt' => 'a',
				'updatedAt'   => 'b',
			],
			[
				'recent' => [ [ 'id' => 1 ] ],
				'next'   => [ 'id' => 1 ],
			]
		);
		$this->assertSame( $this->contract['data'], array_keys( $payload ) );
		$this->assertSame( $this->contract['kpis'], array_keys( $payload['kpis'] ) );
		$this->assertSame( [ [ 'id' => 1 ] ], $payload['recentSessions'] );
		$this->assertSame( [ 'id' => 1 ], $payload['nextSession'] );
	}

	public function test_composed_tutor_kpi_keys_match_fixture() {
		$kpis = NGC_Rest_Dashboard::compose_tutor_kpis( 0, 0, null, 0 );
		$this->assertSame( $this->contract['kpis'], array_keys( $kpis ) );
	}

	public function test_tutor_endpoint_uses_compose_helpers() {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/rest/class-ngc-rest-dashboard.php' );
		$this->assertMatchesRegularExpression( '/function tutor\s*\(.*self::compose_tutor_data\s*\(/s', $src );
		$this->assertMatchesRegularExpression( '/function tutor_kpis\s*\(.*self::compose_tutor_kpis\s*\(/s', $src );
	}

	public function test_application_payload_null() {
		$this->assertNull( NGC_Rest_Dashboard::application_payload( null ) );
	}

	public function test_application_payload_shape() {
		$row = (object) [
			'status'       => 'pending',
			'review_notes' => 'Wait',
			'created_at'   => '2026-08-01 10:00:00',
			'updated_at'   => '2026-08-02 10:00:00',
		];
		$payload = NGC_Rest_Dashboard::application_payload( $row );
		$this->assertSame( $this->contract['application'], array_keys( $payload ) );
		$this->assertSame( 'pending', $payload['status'] );
		$this->assertSame( 'Wait', $payload['reviewNotes'] );
	}

	public function test_composed_learner_payload_keys() {
		$payload = NGC_Rest_Dashboard::compose_learner_data(
			[ 'id' => 2, 'displayName' => 'Pat' ],
			[ 'sessionsCompleted' => 0 ],
			[],
			[ 'learners' => [ 'A' ] ]
		);
		$this->assertSame(
			[ 'user', 'kpis', 'learners', 'recentSessions', 'nextSession' ],
			array_keys( $payload )
		);
		$this->assertSame( [], $payload['recentSessions'] );
		$this->assertNull( $payload['nextSession'] );
	}

	public function test_student_and_parent_use_compose_learner_data() {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/rest/class-ngc-rest-dashboard.php' );
		$this->assertMatchesRegularExpression( '/function student\s*\(.*self::compose_learner_data\s*\(/s', $src );
		$this->assertMatchesRegularExpression( '/function parent\s*\(.*self::compose_learner_data\s*\(/s', $src );
		$this->assertMatchesRegularExpression( '/function admin\s*\(.*self::compose_admin_data\s*\(/s', $src );
	}

	public function test_session_digest_picks_next_from_recent() {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/rest/class-ngc-rest-dashboard.php' );
		$this->assertStringContainsString( '$next = $recent[ $i ] ?? null;', $src );
		$this->assertStringNotContainsString(
			'$next = NGC_Bookings::format_session_row( $b, $user_id );',
			$src
		);
	}
}
