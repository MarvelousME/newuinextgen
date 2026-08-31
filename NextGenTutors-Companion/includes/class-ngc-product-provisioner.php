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
		add_action( 'woocommerce_init', [ __CLASS__, 'maybe_provision' ] );
	}

	/**
	 * Idempotent storefront-safe provision: skip after first successful run.
	 *
	 * @return array<string, mixed>
	 */
	public static function maybe_provision() {
		if ( get_option( 'ngc_tutor_products_provisioned' ) ) {
			return [ 'success' => true, 'skipped' => true ];
		}
		if ( ! is_admin() && ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return [ 'success' => false, 'message' => 'deferred' ];
		}
		$result = self::provision_defaults();
		if ( ! empty( $result['success'] ) ) {
			update_option( 'ngc_tutor_products_provisioned', 1, false );
		}
		return $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function provision_defaults() {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product_id_by_sku' ) || ! class_exists( 'WC_Product_Simple' ) ) {
			return [ 'success' => false, 'message' => 'woocommerce inactive' ];
		}
		$online   = function_exists( 'bi_get_theme_option' ) ? (int) bi_get_theme_option( 'rate_online', 350 ) : 350;
		$inperson = function_exists( 'bi_get_theme_option' ) ? (int) bi_get_theme_option( 'rate_inperson', 450 ) : 450;
		$skus     = [
			'ngc-lesson-online' => [
				'name'  => __( 'Online Lesson (1 hour)', 'nextgencompanion' ),
				'price' => (string) max( 1, $online ),
			],
			'ngc-lesson-inperson' => [
				'name'  => __( 'In-person Lesson (1 hour)', 'nextgencompanion' ),
				'price' => (string) max( 1, $inperson ),
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
			if ( ! $id ) {
				return [
					'success'  => false,
					'message'  => 'failed to create SKU ' . $sku,
					'products' => $created,
				];
			}
			$created[] = [ 'sku' => $sku, 'product_id' => $id, 'status' => 'created' ];
		}
		return [ 'success' => true, 'products' => $created ];
	}
}
