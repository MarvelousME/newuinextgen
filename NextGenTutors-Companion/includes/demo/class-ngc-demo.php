<?php
/**
 * Demo facade — wires env/clock/seed for bootstrap.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module init aggregator for Phase 14 demo stack.
 */
final class NGC_Demo {

	/**
	 * Initialize demo subsystems.
	 */
	public static function init() {
		NGC_Demo_Env::init();
		NGC_Demo_Clock::init();
		NGC_Demo_Seeder::init();
		NGC_Demo_Admin::init();
	}
}
