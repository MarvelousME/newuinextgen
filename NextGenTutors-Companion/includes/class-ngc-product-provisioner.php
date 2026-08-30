<?php
/**
 * WooCommerce product provisioner for tutoring SKUs / lesson packages.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provisions WooCommerce products from platform/catalog definitions.
 */
class NGC_Product_Provisioner {

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'ngc_provision_tutor_products', [ __CLASS__, 'provision_defaults' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function provision_defaults() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return [ 'success' => false, 'message' => 'woocommerce inactive' ];
		}
		$skus = [
			'ngc-lesson-online' => [
				'name' => __( 'Online Lesson (1 hour)', 'nextgencompanion' ),
				'price' => '350',
			],
			'ngc-lesson-inperson' => [
				'name' => __( 'In-person Lesson (1 hour)', 'nextgencompanion' ),
				'price' => '450',
			],
		];
		$created = [];
		foreach ( $skus as $sku => $def ) {
			$existing = wc_get_product_id_by_sku( $sku );
			if ( $existing ) {
				$created[] = [ 'sku' => $sku, 'product_id' => $existing, 'status' => 'exists' ];
				continue;
			}
			$product = new WC_Product_Simple();
			$product->set_name( $def['name'] );
			$product->set_sku( $sku );
			$product->set_regular_price( $def['price'] );
			$product->set_status( 'publish' );
			$id = $product->save();
			$created[] = [ 'sku' => $sku, 'product_id' => $id, 'status' => 'created' ];
		}
		return [ 'success' => true, 'products' => $created ];
	}
}
