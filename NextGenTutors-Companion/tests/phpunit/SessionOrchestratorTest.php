<?php
/**
 * Session orchestrator characterization tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * NGC_Session_Orchestrator helpers.
 */
class SessionOrchestratorTest extends TestCase {

	protected function setUp(): void {
		require_once dirname( __DIR__, 2 ) . '/includes/session/class-ngc-session-orchestrator.php';
	}

	public function test_missing_args_returns_wp_error() {
		$result = NGC_Session_Orchestrator::ensure_provisioned( [] );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'ngc_session_args', $result->get_error_code() );
	}

	public function test_idempotency_key_shape() {
		$this->assertSame( 'ensure-session:12:34', NGC_Session_Orchestrator::ensure_idempotency_key( 12, 34 ) );
	}

	public function test_initial_lifecycle_paid() {
		$life = NGC_Session_Orchestrator::initial_lifecycle( false, true, false );
		$this->assertSame( NGC_Session_States::PAID, $life['status'] );
		$this->assertSame( 'paid', $life['payment'] );
		$this->assertTrue( $life['may_ready'] );
	}

	public function test_initial_lifecycle_failed() {
		$life = NGC_Session_Orchestrator::initial_lifecycle( true, false, false );
		$this->assertSame( NGC_Session_States::FAILED, $life['status'] );
		$this->assertSame( 'failed', $life['payment'] );
		$this->assertFalse( $life['may_ready'] );
	}

	public function test_initial_lifecycle_legacy_confirmed() {
		$life = NGC_Session_Orchestrator::initial_lifecycle( false, false, true );
		$this->assertSame( NGC_Session_States::BOOKING_CONFIRMED, $life['status'] );
		$this->assertSame( 'unpaid', $life['payment'] );
		$this->assertTrue( $life['may_ready'] );
	}

	public function test_join_window_missing_session() {
		$window = NGC_Session_Orchestrator::join_window_status( null );
		$this->assertFalse( $window['allowed'] );
		$this->assertSame( 'missing_session', $window['reason'] );
	}

	public function test_join_window_unpaid_with_order() {
		$session = (object) [
			'status'          => NGC_Session_States::READY,
			'order_id'        => 9,
			'payment_status'  => 'unpaid',
			'scheduled_start' => gmdate( 'Y-m-d H:i:s' ),
		];
		$window = NGC_Session_Orchestrator::join_window_status( $session );
		$this->assertFalse( $window['allowed'] );
		$this->assertSame( 'payment_required', $window['reason'] );
	}

	public function test_join_window_no_schedule_ready() {
		$session = (object) [
			'status'          => NGC_Session_States::READY,
			'order_id'        => 0,
			'payment_status'  => 'unpaid',
			'scheduled_start' => null,
		];
		$window = NGC_Session_Orchestrator::join_window_status( $session );
		$this->assertTrue( $window['allowed'] );
		$this->assertSame( 'no_schedule', $window['reason'] );
	}

	public function test_require_session_row_null_is_wp_error() {
		$ref = new ReflectionMethod( NGC_Session_Orchestrator::class, 'require_session_row' );
		$ref->setAccessible( true );
		$result = $ref->invoke( null, null );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'ngc_session_missing', $result->get_error_code() );
	}

	public function test_require_session_row_object() {
		$ref = new ReflectionMethod( NGC_Session_Orchestrator::class, 'require_session_row' );
		$ref->setAccessible( true );
		$row = (object) [ 'id' => 9 ];
		$this->assertSame( $row, $ref->invoke( null, $row ) );
	}

	public function test_upsert_guards_null_get_after_create() {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/session/class-ngc-session-orchestrator.php' );
		$this->assertStringContainsString( 'self::require_session_row( NGC_Sessions::get( (int) $create ) )', $src );
		$this->assertStringContainsString( 'self::require_session_row( NGC_Sessions::get( (int) $session->id ) )', $src );
	}
}
