<?php
/**
 * UI data provider registry.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves providers by key.
 */
class NGC_UI_Provider_Registry {

	/**
	 * @var array<string, NGC_UI_Data_Provider>|null
	 */
	private static $providers = null;

	/**
	 * @return array<string, NGC_UI_Data_Provider>
	 */
	public static function all() {
		if ( null !== self::$providers ) {
			return self::$providers;
		}

		$classes = [
			'NGC_UI_Company_Data_Provider',
			'NGC_UI_Page_Content_Provider',
			'NGC_UI_Tutor_Data_Provider',
			'NGC_UI_Subject_Data_Provider',
			'NGC_UI_Pricing_Data_Provider',
			'NGC_UI_Review_Data_Provider',
			'NGC_UI_Dashboard_Data_Provider',
			'NGC_UI_Booking_Data_Provider',
			'NGC_UI_Calendar_Data_Provider',
			'NGC_UI_Analytics_Data_Provider',
			'NGC_UI_Gamification_Data_Provider',
		];

		self::$providers = [];
		foreach ( $classes as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}
			/** @var NGC_UI_Data_Provider $instance */
			$instance = new $class();
			self::$providers[ $instance->get_key() ] = $instance;
		}

		return apply_filters( 'ngc_ui_providers', self::$providers );
	}

	/**
	 * @param string $key Provider key.
	 * @return NGC_UI_Data_Provider|null
	 */
	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Fetch mapped component data.
	 *
	 * @param string               $provider_key Provider.
	 * @param string               $component    Component slug.
	 * @param array<string, mixed> $args         List args.
	 * @return array<int, array<string, mixed>>
	 */
	public static function component_data( $provider_key, $component, $args = [] ) {
		$provider = self::get( $provider_key );
		if ( ! $provider || ! $provider->is_available() ) {
			return [ $provider ? $provider->fallback_empty_state( $component ) : [ 'empty' => true ] ];
		}

		$rows = $provider->list( $args );
		if ( empty( $rows ) ) {
			return [ $provider->fallback_empty_state( $component ) ];
		}

		return array_map(
			static function ( $row ) use ( $provider, $component ) {
				return $provider->map_to_component( $row, $component );
			},
			$rows
		);
	}

	/**
	 * Verification report for all providers.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function verification_report() {
		$report = [];
		foreach ( self::all() as $key => $provider ) {
			$report[] = array_merge(
				$provider->verify_source(),
				[
					'available' => $provider->is_available(),
				]
			);
		}
		return $report;
	}
}
