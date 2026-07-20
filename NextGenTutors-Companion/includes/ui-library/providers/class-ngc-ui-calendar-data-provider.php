<?php
/**
 * Calendar availability provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor calendar slots.
 */
class NGC_UI_Calendar_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'calendar';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Tutor_Calendar_Service' );
	}

	/**
	 * @param array<string, mixed> $args { tutor_id }.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		$tutor_id = (int) ( $args['tutor_id'] ?? 0 );
		if ( ! $tutor_id || ! $this->is_available() ) {
			return [];
		}

		if ( method_exists( 'NGC_Tutor_Calendar_Service', 'get_calendar' ) ) {
			$cal = NGC_Tutor_Calendar_Service::get_calendar( $tutor_id, $args );
			$slots = is_array( $cal['data']['slots'] ?? null ) ? $cal['data']['slots'] : [];
			return $slots;
		}

		return apply_filters( 'ngc_ui_calendar_slots', [], $tutor_id, $args );
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		return $row;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'class'    => 'NGC_Tutor_Calendar_Service',
			'rest'     => 'ngc/v1/tutor-calendar',
		];
	}
}
