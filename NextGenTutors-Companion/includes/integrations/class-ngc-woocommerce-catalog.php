<?php
/**
 * WooCommerce product catalog import from integrate CSV.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports nextgen-tutors-woocommerce-products.csv into WooCommerce.
 */
class NGC_WooCommerce_Catalog {

	/**
	 * @return string
	 */
	public static function csv_path() {
		return trailingslashit( NGC_PLUGIN_DIR ) . 'integrate/nextgen-tutors-woocommerce-products.csv';
	}

	/**
	 * @param bool $dry_run Preview only.
	 * @return array{created:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_from_csv( $dry_run = false ) {
		$result = [
			'created'    => 0,
			'skipped'    => 0,
			'categorized'=> 0,
			'errors'     => [],
		];

		if ( ! class_exists( 'WooCommerce' ) ) {
			$result['errors'][] = 'WooCommerce not active.';
			return $result;
		}

		$path = self::csv_path();
		if ( ! file_exists( $path ) ) {
			$result['errors'][] = 'CSV not found: ' . $path;
			return $result;
		}

		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			$result['errors'][] = 'Could not open CSV.';
			return $result;
		}

		$headers = fgetcsv( $handle );
		if ( ! $headers ) {
			fclose( $handle );
			$result['errors'][] = 'Empty CSV.';
			return $result;
		}

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$data = array_combine( $headers, $row );
			if ( ! $data || empty( $data['SKU'] ) ) {
				continue;
			}
			$sku = sanitize_text_field( (string) $data['SKU'] );
			if ( function_exists( 'wc_get_product_id_by_sku' ) && wc_get_product_id_by_sku( $sku ) ) {
				++$result['skipped'];
				continue;
			}
			$categories = self::parse_category_names( (string) ( $data['Categories'] ?? '' ) );
			if ( $dry_run ) {
				++$result['created'];
				if ( $categories ) {
					++$result['categorized'];
				}
				continue;
			}
			$product = new WC_Product_Simple();
			$product->set_name( sanitize_text_field( (string) ( $data['Name'] ?? $sku ) ) );
			$product->set_sku( $sku );
			$product->set_regular_price( (string) ( $data['Regular price'] ?? $data['Price'] ?? '0' ) );
			$product->set_virtual( true );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			if ( ! empty( $data['Description'] ) ) {
				$product->set_description( wp_kses_post( (string) $data['Description'] ) );
			}
			if ( ! empty( $data['Short description'] ) ) {
				$product->set_short_description( wp_kses_post( (string) $data['Short description'] ) );
			}
			$id = $product->save();
			if ( $id ) {
				++$result['created'];
				if ( $categories && self::assign_product_categories( $id, $categories ) ) {
					++$result['categorized'];
				}
			} else {
				$result['errors'][] = 'Failed to create SKU ' . $sku;
			}
		}
		fclose( $handle );
		return $result;
	}

	/**
	 * Parse comma-separated category labels from CSV.
	 *
	 * @param string $value Raw Categories column.
	 * @return string[]
	 */
	public static function parse_category_names( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return [];
		}
		$names = array_map( 'trim', explode( ',', $value ) );
		return array_values( array_filter( $names ) );
	}

	/**
	 * Resolve or create WooCommerce product_cat terms.
	 *
	 * @param string[] $names Category names.
	 * @return int[]
	 */
	public static function resolve_category_ids( $names ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}
		$ids = [];
		foreach ( (array) $names as $name ) {
			$name = sanitize_text_field( (string) $name );
			if ( '' === $name ) {
				continue;
			}
			$term = term_exists( $name, 'product_cat' );
			if ( ! $term ) {
				$term = wp_insert_term( $name, 'product_cat' );
			}
			if ( is_wp_error( $term ) ) {
				continue;
			}
			$ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param int      $product_id Product ID.
	 * @param string[] $names      Category names.
	 * @return bool
	 */
	public static function assign_product_categories( $product_id, $names ) {
		$ids = self::resolve_category_ids( $names );
		if ( ! $ids ) {
			return false;
		}
		$set = wp_set_object_terms( (int) $product_id, $ids, 'product_cat' );
		return ! is_wp_error( $set );
	}
}
