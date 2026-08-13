<?php
/**
 * Session state machine unit tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * NGC_Session_States.
 */
class SessionStatesTest extends TestCase {

	public function test_happy_path_transitions() {
		$this->assertTrue( NGC_Session_States::can_transition( 'draft', 'awaiting_payment' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'awaiting_payment', 'paid' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'paid', 'booking_confirmed' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'booking_confirmed', 'provisioning' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'provisioning', 'ready' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'ready', 'in_progress' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'in_progress', 'completed' ) );
	}

	public function test_rejects_invalid_jumps() {
		$this->assertFalse( NGC_Session_States::can_transition( 'draft', 'completed' ) );
		$this->assertFalse( NGC_Session_States::can_transition( 'awaiting_payment', 'ready' ) );
		$this->assertFalse( NGC_Session_States::can_transition( 'completed', 'ready' ) );
		$this->assertFalse( NGC_Session_States::can_transition( 'refunded', 'paid' ) );
	}

	public function test_refund_and_cancel_paths() {
		$this->assertTrue( NGC_Session_States::can_transition( 'awaiting_payment', 'cancelled' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'paid', 'refunded' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'ready', 'cancelled' ) );
		$this->assertTrue( NGC_Session_States::can_transition( 'provisioning', 'failed' ) );
	}

	public function test_joinable_statuses() {
		$this->assertTrue( NGC_Session_States::is_joinable( 'ready' ) );
		$this->assertTrue( NGC_Session_States::is_joinable( 'in_progress' ) );
		$this->assertFalse( NGC_Session_States::is_joinable( 'cancelled' ) );
		$this->assertFalse( NGC_Session_States::is_joinable( 'awaiting_payment' ) );
	}

	public function test_same_status_is_noop_allowed() {
		$this->assertTrue( NGC_Session_States::can_transition( 'ready', 'ready' ) );
	}
}
