<?php
/**
 * Platform analytics provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trust stats and KPI counters for marketing sections.
 */
class NGC_UI_Analytics_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'analytics';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Platform_Tracking' ) || post_type_exists( 'tutors' );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		$stats = apply_filters( 'ngc_ui_trust_stats', [], $args );

		if ( ! empty( $stats ) ) {
			return [ [ 'items' => $stats ] ];
		}

		$tutor_count = $this->is_available() ? (int) wp_count_posts( 'tutors' )->publish : 0;
		$items       = [];

		if ( $tutor_count > 0 ) {
			$items[] = [
				'label' => __( 'Vetted Tutors', 'nextgencompanion' ),
				'value' => (string) $tutor_count,
				'icon'  => 'users',
			];
		}

		if ( class_exists( 'NGC_Platform_Tracking' ) && method_exists( 'NGC_Platform_Tracking', 'get_aggregate' ) ) {
			$agg = NGC_Platform_Tracking::get_aggregate();
			if ( is_array( $agg ) ) {
				foreach ( $agg as $key => $val ) {
					$items[] = [
						'label' => ucwords( str_replace( '_', ' ', $key ) ),
						'value' => (string) $val,
						'icon'  => 'chart',
					];
				}
			}
		}

		return $items ? [ [ 'items' => $items ] ] : [];
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
			'filter'   => 'ngc_ui_trust_stats',
			'class'    => 'NGC_Platform_Tracking',
		];
	}
}
