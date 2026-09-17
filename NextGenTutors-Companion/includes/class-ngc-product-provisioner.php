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
 * Idempotent catalogue provisioner from the official NGT product definitions.
 * Running this repeatedly must never create duplicate logical products.
 */
class NGC_Product_Provisioner {

	public const OPTION_STATUS = 'ngc_tutor_products_provision_status';

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'ngc_provision_tutor_products', [ __CLASS__, 'provision_defaults' ] );
		add_action( 'woocommerce_init', [ __CLASS__, 'maybe_provision' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_admin_status' ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function maybe_provision() {
		if ( ! is_admin() && ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return [ 'success' => false, 'message' => 'deferred' ];
		}
		$existing = get_option( self::OPTION_STATUS, [] );
		if ( is_array( $existing )
			&& ( $existing['version'] ?? '' ) === NGC_Product_Catalog::CATALOG_VERSION
			&& ! empty( $existing['success'] )
			&& (int) ( $existing['count'] ?? 0 ) >= count( NGC_Product_Catalog::definitions() )
		) {
			return $existing;
		}
		return self::provision_defaults();
	}

	/**
	 * Inspect, update, or create each catalogue definition. Never duplicates.
	 *
	 * @return array<string, mixed>
	 */
	public static function provision_defaults() {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product_id_by_sku' ) || ! class_exists( 'WC_Product_Simple' ) ) {
			return [ 'success' => false, 'message' => 'woocommerce inactive' ];
		}

		$created = [];
		$errors  = [];
		foreach ( NGC_Product_Catalog::definitions() as $key => $def ) {
			$result = self::ensure_product( $key, $def );
			if ( is_wp_error( $result ) ) {
				$errors[] = $key . ': ' . $result->get_error_message();
				continue;
			}
			$created[] = $result;
		}

		$status = [
			'success'    => empty( $errors ),
			'products'   => $created,
			'errors'     => $errors,
			'count'      => count( $created ),
			'version'    => NGC_Product_Catalog::CATALOG_VERSION,
			'verified_at'=> gmdate( 'c' ),
		];
		update_option( self::OPTION_STATUS, $status, false );
		update_option( 'ngc_tutor_products_provisioned', empty( $errors ) ? 1 : 0, false );
		return $status;
	}

	/**
	 * @param string               $key Product key / SKU.
	 * @param array<string, mixed> $def Definition.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function ensure_product( $key, array $def ) {
		$key        = strtoupper( (string) $key );
		$product_id = self::find_existing( $key );
		$creating   = ! $product_id;

		if ( $creating ) {
			$product = new WC_Product_Simple();
			$product->set_sku( $key );
		} else {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return new WP_Error( 'ngc_product_missing', 'Product ID ' . $product_id . ' not loadable' );
			}
		}

		$product->set_name( (string) $def['name'] );
		$product->set_regular_price( (string) $def['price'] );
		$product->set_price( (string) $def['price'] );
		$product->set_virtual( ! empty( $def['virtual'] ) );
		$product->set_sold_individually( true );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_status( 'publish' );
		$product->set_tax_status( 'taxable' );
		if ( ! empty( $def['short'] ) ) {
			$product->set_short_description( (string) $def['short'] );
			$product->set_description( (string) $def['short'] );
		}
		$id = $product->save();
		if ( ! $id ) {
			return new WP_Error( 'ngc_product_save', 'failed to save SKU ' . $key );
		}

		update_post_meta( $id, NGC_Product_Catalog::META_KEY, $key );
		update_post_meta( $id, NGC_Product_Catalog::META_PACKAGE, (string) $def['package_type'] );
		update_post_meta( $id, NGC_Product_Catalog::META_DURATION, (int) $def['duration_minutes'] );
		update_post_meta( $id, NGC_Product_Catalog::META_SESSION_COUNT, (int) $def['session_count'] );
		update_post_meta( $id, NGC_Product_Catalog::META_VERSION, NGC_Product_Catalog::CATALOG_VERSION );
		update_post_meta( $id, NGC_Product_Catalog::META_FORMAT, (string) $def['format'] );
		update_post_meta( $id, '_ngt_payfast_compatible', '1' );

		if ( ! empty( $def['categories'] ) && class_exists( 'NGC_WooCommerce_Catalog' ) ) {
			NGC_WooCommerce_Catalog::assign_product_categories( $id, (array) $def['categories'] );
		}

		return [
			'sku'        => $key,
			'product_id' => (int) $id,
			'status'     => $creating ? 'created' : 'updated',
			'price'      => (string) $def['price'],
			'purchasable'=> true,
			'virtual'    => ! empty( $def['virtual'] ),
		];
	}

	/**
	 * @param string $key SKU / product key.
	 * @return int
	 */
	public static function find_existing( $key ) {
		$key = strtoupper( (string) $key );
		$id  = (int) wc_get_product_id_by_sku( $key );
		if ( $id ) {
			return $id;
		}
		$q = new WP_Query(
			[
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => NGC_Product_Catalog::META_KEY,
				'meta_value'     => $key,
				'no_found_rows'  => true,
			]
		);
		return $q->posts ? (int) $q->posts[0] : 0;
	}

	/**
	 * Resolve Woo product ID from a tutoring selection.
	 *
	 * @param string $key Product key.
	 * @return int
	 */
	public static function product_id_for_key( $key ) {
		self::provision_defaults();
		return self::find_existing( $key );
	}

	/**
	 * Admin verification payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function verification_status() {
		$stored = get_option( self::OPTION_STATUS, [] );
		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * @return void
	 */
	public static function maybe_admin_status() {
		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( isset( $_GET['ngc_provision_products'] ) && check_admin_referer( 'ngc_provision_products' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::provision_defaults();
		}
	}
}
