<?php
/**
 * Idempotent WooCommerce tutoring product provisioner.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures CSV/spec products exist with stable _ngt_* meta (no duplicates).
 */
class NGC_Product_Provisioner {

	public const META_KEY      = '_ngt_product_key';
	public const META_SUBJECT  = '_ngt_subject_id';
	public const META_PACKAGE  = '_ngt_package_type';
	public const META_DURATION = '_ngt_duration_minutes';
	public const META_SESSIONS = '_ngt_session_count';
	public const META_VERSION  = '_ngt_version';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'maybe_auto_provision' ], 40 );
	}

	/**
	 * One-time local/demo provision when WC active and flag unset.
	 */
	public static function maybe_auto_provision() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_option( 'ngc_products_provisioned_v1' ) ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$result = self::provision_all( false );
		if ( empty( $result['errors'] ) ) {
			update_option( 'ngc_products_provisioned_v1', gmdate( 'c' ), false );
		}
	}

	/**
	 * @param bool $dry_run Preview.
	 * @return array<string, mixed>
	 */
	public static function provision_all( $dry_run = false ) {
		$result = [
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => [],
			'products'=> [],
		];
		if ( ! class_exists( 'WooCommerce' ) ) {
			$result['errors'][] = 'WooCommerce not active.';
			return $result;
		}

		foreach ( self::definitions() as $def ) {
			$out = self::ensure_product( $def, $dry_run );
			if ( is_wp_error( $out ) ) {
				$result['errors'][] = $out->get_error_message();
				continue;
			}
			$result[ $out['action'] ] = (int) ( $result[ $out['action'] ] ?? 0 ) + 1;
			$result['products'][]     = $out;
		}
		return $result;
	}

	/**
	 * Spec-derived product definitions (from integrate CSV + NGT keys).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function definitions() {
		$rows = [];
		$path = trailingslashit( NGC_PLUGIN_DIR ) . 'integrate/nextgen-tutors-woocommerce-products.csv';
		if ( file_exists( $path ) ) {
			$handle = fopen( $path, 'r' );
			if ( $handle ) {
				$headers = fgetcsv( $handle, 0, ',', '"', '\\' );
				while ( $headers && ( $row = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) {
					$data = array_combine( $headers, $row );
					if ( ! $data || empty( $data['SKU'] ) ) {
						continue;
					}
					$sku  = sanitize_text_field( (string) $data['SKU'] );
					$key  = strtolower( str_replace( '_', '-', $sku ) );
					$pkg  = self::infer_package_type( $sku, (string) ( $data['Name'] ?? '' ) );
					$rows[] = [
						'key'              => $key,
						'sku'              => $sku,
						'name'             => sanitize_text_field( (string) ( $data['Name'] ?? $sku ) ),
						'price'            => (string) ( $data['Regular price'] ?? '0' ),
						'description'      => (string) ( $data['Description'] ?? '' ),
						'short_description'=> (string) ( $data['Short description'] ?? '' ),
						'categories'       => (string) ( $data['Categories'] ?? '' ),
						'package_type'     => $pkg['package_type'],
						'duration_minutes' => $pkg['duration_minutes'],
						'session_count'    => $pkg['session_count'],
						'subject_id'       => $pkg['subject_id'],
						'version'          => '1',
					];
				}
				fclose( $handle );
			}
		}
		return $rows;
	}

	/**
	 * @param string $sku  SKU.
	 * @param string $name Name.
	 * @return array<string, mixed>
	 */
	private static function infer_package_type( $sku, $name ) {
		$count = 1;
		if ( preg_match( '/-(\d+)(?:-|$)/', $sku, $m ) && (int) $m[1] > 1 && false === strpos( $sku, '1HR' ) ) {
			$count = (int) $m[1];
		}
		if ( preg_match( '/(\d+)\s+Lessons/i', $name, $m2 ) ) {
			$count = (int) $m2[1];
		}
		$type = 'single';
		if ( $count > 1 ) {
			$type = 'package';
		}
		if ( false !== stripos( $sku, 'HIGHFREQ' ) ) {
			$type = 'high_frequency';
		}
		$subject = 'general';
		if ( false !== stripos( $sku, 'TERTIARY' ) ) {
			$subject = 'tertiary';
		} elseif ( false !== stripos( $sku, 'INPERSON' ) ) {
			$subject = 'in_person';
		} elseif ( false !== stripos( $sku, 'ONLINE' ) ) {
			$subject = 'online';
		}
		return [
			'package_type'     => $type,
			'duration_minutes' => 60,
			'session_count'    => $count,
			'subject_id'       => $subject,
		];
	}

	/**
	 * @param array<string, mixed> $def     Definition.
	 * @param bool                 $dry_run Dry run.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function ensure_product( $def, $dry_run = false ) {
		$key = sanitize_key( (string) ( $def['key'] ?? '' ) );
		$sku = sanitize_text_field( (string) ( $def['sku'] ?? $key ) );
		if ( '' === $key ) {
			return new WP_Error( 'ngc_product_key', 'Missing product key.' );
		}

		$product_id = self::find_by_key( $key );
		if ( ! $product_id && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $sku );
		}

		if ( $dry_run ) {
			return [
				'action'     => $product_id ? 'skipped' : 'created',
				'product_id' => $product_id,
				'key'        => $key,
				'sku'        => $sku,
			];
		}

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				$product_id = 0;
			}
		}

		if ( ! $product_id ) {
			$product = new WC_Product_Simple();
			$product->set_name( (string) $def['name'] );
			$product->set_sku( $sku );
			$product->set_regular_price( (string) $def['price'] );
			$product->set_virtual( true );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			if ( ! empty( $def['description'] ) ) {
				$product->set_description( wp_kses_post( (string) $def['description'] ) );
			}
			if ( ! empty( $def['short_description'] ) ) {
				$product->set_short_description( wp_kses_post( (string) $def['short_description'] ) );
			}
			$product_id = (int) $product->save();
			$action     = 'created';
		} else {
			$product = wc_get_product( $product_id );
			$product->set_regular_price( (string) $def['price'] );
			$product->set_price( (string) $def['price'] );
			$product->save();
			$action = 'updated';
		}

		update_post_meta( $product_id, self::META_KEY, $key );
		update_post_meta( $product_id, self::META_SUBJECT, sanitize_key( (string) ( $def['subject_id'] ?? '' ) ) );
		update_post_meta( $product_id, self::META_PACKAGE, sanitize_key( (string) ( $def['package_type'] ?? 'single' ) ) );
		update_post_meta( $product_id, self::META_DURATION, (int) ( $def['duration_minutes'] ?? 60 ) );
		update_post_meta( $product_id, self::META_SESSIONS, (int) ( $def['session_count'] ?? 1 ) );
		update_post_meta( $product_id, self::META_VERSION, sanitize_text_field( (string) ( $def['version'] ?? '1' ) ) );

		if ( ! empty( $def['categories'] ) && class_exists( 'NGC_WooCommerce_Catalog' ) ) {
			$names = NGC_WooCommerce_Catalog::parse_category_names( (string) $def['categories'] );
			if ( $names ) {
				NGC_WooCommerce_Catalog::assign_product_categories( $product_id, $names );
			}
		}

		if ( class_exists( 'NGC_Audit' ) && 'created' === $action ) {
			NGC_Audit::log( 'product_resolved', 'product', $product_id, [ 'key' => $key, 'sku' => $sku ] );
		}

		return [
			'action'     => $action,
			'product_id' => $product_id,
			'key'        => $key,
			'sku'        => $sku,
		];
	}

	/**
	 * @param string $key Product key.
	 * @return int
	 */
	public static function find_by_key( $key ) {
		$q = new WP_Query(
			[
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => sanitize_key( $key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		return $q->posts ? (int) $q->posts[0] : 0;
	}

	/**
	 * Resolve product for booking context.
	 *
	 * @param array<string, mixed> $ctx subject, duration, package.
	 * @return int Product ID.
	 */
	public static function resolve_for_booking( $ctx = [] ) {
		$duration = (int) ( $ctx['duration_minutes'] ?? 60 );
		$package  = sanitize_key( (string) ( $ctx['package_type'] ?? 'single' ) );
		// Default online 1hr single — matches NGT-ONLINE-1HR.
		$key = 'ngt-online-1hr';
		if ( 'package' === $package ) {
			$key = 'ngt-online-4-1to3';
		}
		$id = self::find_by_key( $key );
		if ( $id ) {
			return $id;
		}
		self::provision_all( false );
		return self::find_by_key( $key );
	}
}
