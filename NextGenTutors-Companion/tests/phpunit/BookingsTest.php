<?php
/**
 * Bookings helper unit tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * NGC_Bookings.
 */
class BookingsTest extends TestCase {

	protected function setUp(): void {
		require_once dirname( __DIR__, 2 ) . '/includes/class-ngc-uuid.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-ngc-bookings.php';
	}

	public function test_statuses_are_stable() {
		$this->assertSame( [ 'requested', 'confirmed', 'cancelled', 'completed' ], NGC_Bookings::statuses() );
	}

	public function test_normalize_status_rejects_unknown() {
		$result = NGC_Bookings::normalize_status( 'paid' );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'ngc_invalid_status', $result->get_error_code() );
	}

	public function test_normalize_status_accepts_confirmed() {
		$this->assertSame( 'confirmed', NGC_Bookings::normalize_status( 'confirmed' ) );
	}

	public function test_build_create_row_defaults() {
		$row = $this->callPrivate(
			'build_create_row',
			[
				[
					'student_user_id' => 3,
					'tutor_user_id'   => 7,
					'subject'         => 'Mathematics',
				],
			]
		);
		$this->assertSame( 'requested', $row['status'] );
		$this->assertSame( 60, $row['duration_minutes'] );
		$this->assertSame( 'ZAR', $row['currency'] );
		$this->assertSame( 3, $row['student_user_id'] );
		$this->assertSame( 7, $row['tutor_user_id'] );
		$this->assertSame( 'Mathematics', $row['subject'] );
		$this->assertArrayHasKey( 'uuid', $row );
	}

	public function test_idempotency_replay_id_zero_is_error() {
		$result = $this->callPrivate(
			'idempotency_replay_id',
			[
				[ 'status' => 'replay', 'result' => [ 'booking_id' => 0 ] ],
			]
		);
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'ngc_booking_idempotency_replay', $result->get_error_code() );
	}

	public function test_idempotency_replay_id_positive() {
		$result = $this->callPrivate(
			'idempotency_replay_id',
			[
				[ 'status' => 'replay', 'result' => [ 'booking_id' => 42 ] ],
			]
		);
		$this->assertSame( 42, $result );
	}

	public function test_begin_create_idempotency_skip_without_key() {
		$result = $this->callPrivate( 'begin_create_idempotency', [ [] ] );
		$this->assertSame( [ 'status' => 'skip' ], $result );
	}

	/**
	 * @param string $method Method name.
	 * @param array<int, mixed> $args Args.
	 * @return mixed
	 */
	private function callPrivate( $method, array $args ) {
		$ref = new ReflectionMethod( NGC_Bookings::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( null, $args );
	}
}
