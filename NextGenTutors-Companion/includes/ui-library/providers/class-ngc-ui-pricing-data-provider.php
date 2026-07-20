<?php
/**
 * Pricing / WooCommerce products provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lesson packages and pricing cards.
 */
class NGC_UI_Pricing_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'pricing';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'WooCommerce' ) || function_exists( 'ngc_get_pricing_tiers' ) || class_exists( 'NGC_Section_CMS' );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		if ( function_exists( 'ngc_get_pricing_tiers' ) ) {
			return ngc_get_pricing_tiers( $args );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			return [];
		}

		$products = wc_get_products(
			[
				'status'  => 'publish',
				'limit'   => (int) ( $args['limit'] ?? 6 ),
				'orderby' => 'menu_order',
				'order'   => 'ASC',
				'category'=> [ 'lesson-packages' ],
			]
		);

		$rows = [];
		foreach ( $products as $product ) {
			$rows[] = [
				'product_id' => $product->get_id(),
				'title'      => $product->get_name(),
				'price'      => $product->get_price(),
				'mode'       => $product->get_meta( 'lesson_mode' ),
				'duration'   => $product->get_meta( 'commitment_length' ),
				'url'        => $product->get_permalink(),
			];
		}
		return $rows;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		return [
			'title'    => $row['title'] ?? '',
			'price'    => $row['price'] ?? '',
			'mode'     => $row['mode'] ?? '',
			'duration' => $row['duration'] ?? '',
			'cta_url'  => $row['url'] ?? '',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'sources'  => [ 'WooCommerce products', 'ngc_get_pricing_tiers' ],
		];
	}
}
