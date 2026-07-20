<?php
/**
 * Dashboard KPI provider (Studio dashboards).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role-specific dashboard widgets.
 */
class NGC_UI_Dashboard_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'dashboard';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Studio_Dashboards' ) || is_user_logged_in();
	}

	/**
	 * @param array<string, mixed> $args { role }.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return [];
		}

		$role = sanitize_key( $args['role'] ?? '' );
		if ( ! $role ) {
			if ( user_can( $user_id, 'manage_options' ) ) {
				$role = 'admin';
			} elseif ( user_can( $user_id, 'ngc_tutor' ) ) {
				$role = 'tutor';
			} elseif ( user_can( $user_id, 'ngc_parent' ) ) {
				$role = 'parent';
			} else {
				$role = 'student';
			}
		}

		$payload = apply_filters( 'ngc_ui_dashboard_payload', [], $role, $user_id );
		if ( ! empty( $payload ) ) {
			return [ $payload ];
		}

		if ( class_exists( 'NGC_Studio_Dashboards' ) && method_exists( 'NGC_Studio_Dashboards', 'get_summary' ) ) {
			$summary = NGC_Studio_Dashboards::get_summary( $user_id, $role );
			return $summary ? [ $summary ] : [];
		}

		return [];
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
			'filter'   => 'ngc_ui_dashboard_payload',
			'class'    => 'NGC_Studio_Dashboards',
		];
	}
}
