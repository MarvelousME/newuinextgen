<?php
/**
 * Official tutoring product catalogue (CSV-backed definitions).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical NGT product definitions derived from nextgen-tutors-woocommerce-products.csv.
 * Does not invent subject-specific SKUs — subjects are booking attributes.
 */
class NGC_Product_Catalog {

	public const META_KEY            = '_ngt_product_key';
	public const META_SUBJECT        = '_ngt_subject_id';
	public const META_PACKAGE        = '_ngt_package_type';
	public const META_DURATION       = '_ngt_duration_minutes';
	public const META_SESSION_COUNT  = '_ngt_session_count';
	public const META_VERSION        = '_ngt_version';
	public const META_FORMAT         = '_ngt_format';
	public const CATALOG_VERSION     = '1';

	/**
	 * Official catalogue. Keys are stable product keys (SKU).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		return [
			'NGT-ONLINE-1HR' => [
				'name'             => 'Online Tutoring - Single 1 Hour Lesson',
				'short'            => 'Single online tutoring lesson for Grade 1 to tertiary learners.',
				'price'            => '320',
				'categories'       => [ 'Online Tutoring' ],
				'package_type'     => 'single',
				'duration_minutes' => 60,
				'session_count'    => 1,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-INPERSON-1HR' => [
				'name'             => 'In-Person Tutoring - Single 1 Hour Lesson',
				'short'            => 'Single in-person tutoring lesson where the tutor travels to the learner.',
				'price'            => '350',
				'categories'       => [ 'In-Person Tutoring' ],
				'package_type'     => 'single',
				'duration_minutes' => 60,
				'session_count'    => 1,
				'format'           => 'inperson',
				'virtual'          => false,
			],
			'NGT-TERTIARY-1HR' => [
				'name'             => 'Tertiary Tutoring - Single 1 Hour Lesson',
				'short'            => 'Single tertiary tutoring lesson.',
				'price'            => '500',
				'categories'       => [ 'Tertiary Tutoring' ],
				'package_type'     => 'single',
				'duration_minutes' => 60,
				'session_count'    => 1,
				'format'           => 'tertiary',
				'virtual'          => true,
			],
			'NGT-ONLINE-4-1TO3' => [
				'name'             => 'Online Tutoring - 4 Lessons Monthly - 1 to 3 Month Commitment',
				'short'            => '4 online lessons per month at R320/lesson.',
				'price'            => '1280',
				'categories'       => [ 'Online Tutoring' ],
				'package_type'     => 'package-4-1to3',
				'duration_minutes' => 60,
				'session_count'    => 4,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-ONLINE-8-1TO3' => [
				'name'             => 'Online Tutoring - 8 Lessons Monthly - 1 to 3 Month Commitment',
				'short'            => '8 online lessons per month at R320/lesson.',
				'price'            => '2560',
				'categories'       => [ 'Online Tutoring' ],
				'package_type'     => 'package-8-1to3',
				'duration_minutes' => 60,
				'session_count'    => 8,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-ONLINE-4-3TO12' => [
				'name'             => 'Online Tutoring - 4 Lessons Monthly - 3 to 12 Month Commitment',
				'short'            => '4 online lessons per month at R300/lesson.',
				'price'            => '1200',
				'categories'       => [ 'Online Tutoring' ],
				'package_type'     => 'package-4-3to12',
				'duration_minutes' => 60,
				'session_count'    => 4,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-ONLINE-8-3TO12' => [
				'name'             => 'Online Tutoring - 8 Lessons Monthly - 3 to 12 Month Commitment',
				'short'            => '8 online lessons per month at R300/lesson.',
				'price'            => '2400',
				'categories'       => [ 'Online Tutoring' ],
				'package_type'     => 'package-8-3to12',
				'duration_minutes' => 60,
				'session_count'    => 8,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-INPERSON-4-1TO3' => [
				'name'             => 'In-Person Tutoring - 4 Lessons Monthly - 1 to 3 Month Commitment',
				'short'            => '4 in-person lessons per month at R350/lesson.',
				'price'            => '1400',
				'categories'       => [ 'In-Person Tutoring' ],
				'package_type'     => 'package-4-1to3',
				'duration_minutes' => 60,
				'session_count'    => 4,
				'format'           => 'inperson',
				'virtual'          => false,
			],
			'NGT-INPERSON-8-1TO3' => [
				'name'             => 'In-Person Tutoring - 8 Lessons Monthly - 1 to 3 Month Commitment',
				'short'            => '8 in-person lessons per month at R350/lesson.',
				'price'            => '2800',
				'categories'       => [ 'In-Person Tutoring' ],
				'package_type'     => 'package-8-1to3',
				'duration_minutes' => 60,
				'session_count'    => 8,
				'format'           => 'inperson',
				'virtual'          => false,
			],
			'NGT-INPERSON-4-3TO12' => [
				'name'             => 'In-Person Tutoring - 4 Lessons Monthly - 3 to 12 Month Commitment',
				'short'            => '4 in-person lessons per month at R320/lesson.',
				'price'            => '1280',
				'categories'       => [ 'In-Person Tutoring' ],
				'package_type'     => 'package-4-3to12',
				'duration_minutes' => 60,
				'session_count'    => 4,
				'format'           => 'inperson',
				'virtual'          => false,
			],
			'NGT-INPERSON-8-3TO12' => [
				'name'             => 'In-Person Tutoring - 8 Lessons Monthly - 3 to 12 Month Commitment',
				'short'            => '8 in-person lessons per month at R320/lesson.',
				'price'            => '2560',
				'categories'       => [ 'In-Person Tutoring' ],
				'package_type'     => 'package-8-3to12',
				'duration_minutes' => 60,
				'session_count'    => 8,
				'format'           => 'inperson',
				'virtual'          => false,
			],
			'NGT-TERTIARY-4' => [
				'name'             => 'Tertiary Tutoring - 4 Lessons Monthly',
				'short'            => '4 tertiary lessons per month at R500/lesson.',
				'price'            => '2000',
				'categories'       => [ 'Tertiary Tutoring' ],
				'package_type'     => 'package-4',
				'duration_minutes' => 60,
				'session_count'    => 4,
				'format'           => 'tertiary',
				'virtual'          => true,
			],
			'NGT-TERTIARY-8' => [
				'name'             => 'Tertiary Tutoring - 8 Lessons Monthly',
				'short'            => '8 tertiary lessons per month at R500/lesson.',
				'price'            => '4000',
				'categories'       => [ 'Tertiary Tutoring' ],
				'package_type'     => 'package-8',
				'duration_minutes' => 60,
				'session_count'    => 8,
				'format'           => 'tertiary',
				'virtual'          => true,
			],
			'NGT-HIGHFREQ-12' => [
				'name'             => 'High-Frequency Tutoring - 12 Lessons Monthly',
				'short'            => '12 lessons per month at R300/lesson.',
				'price'            => '3600',
				'categories'       => [ 'High-Frequency Tutoring' ],
				'package_type'     => 'highfreq-12',
				'duration_minutes' => 60,
				'session_count'    => 12,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-HIGHFREQ-16' => [
				'name'             => 'High-Frequency Tutoring - 16 Lessons Monthly',
				'short'            => '16 lessons per month at R300/lesson.',
				'price'            => '4800',
				'categories'       => [ 'High-Frequency Tutoring' ],
				'package_type'     => 'highfreq-16',
				'duration_minutes' => 60,
				'session_count'    => 16,
				'format'           => 'online',
				'virtual'          => true,
			],
			'NGT-HIGHFREQ-20' => [
				'name'             => 'High-Frequency Tutoring - 20 Lessons Monthly',
				'short'            => '20 lessons per month at R300/lesson.',
				'price'            => '6000',
				'categories'       => [ 'High-Frequency Tutoring' ],
				'package_type'     => 'highfreq-20',
				'duration_minutes' => 60,
				'session_count'    => 20,
				'format'           => 'online',
				'virtual'          => true,
			],
		];
	}

	/**
	 * @param string $key Product key / SKU.
	 * @return array<string, mixed>|null
	 */
	public static function get( $key ) {
		$defs = self::definitions();
		$key  = strtoupper( sanitize_text_field( (string) $key ) );
		return $defs[ $key ] ?? null;
	}

	/**
	 * Catalogue key stored on a WooCommerce product, if this is an NGT SKU.
	 *
	 * @param int $product_id Product ID.
	 * @return string Empty when the product is not a tutoring catalogue item.
	 */
	public static function key_for_product( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 || ! function_exists( 'get_post_meta' ) ) {
			return '';
		}
		return (string) get_post_meta( $product_id, self::META_KEY, true );
	}

	/**
	 * Resolve product key from format + session count + commitment window.
	 *
	 * @param string $format        online|inperson|tertiary.
	 * @param int    $session_count Sessions.
	 * @param string $commitment    1to3|3to12|highfreq.
	 * @return string Empty if unresolved.
	 */
	public static function resolve_key( $format, $session_count = 1, $commitment = '1to3' ) {
		$format        = sanitize_key( (string) $format );
		$session_count = (int) $session_count;
		$commitment    = sanitize_key( (string) $commitment );
		if ( $session_count <= 1 ) {
			$map = [
				'online'   => 'NGT-ONLINE-1HR',
				'inperson' => 'NGT-INPERSON-1HR',
				'tertiary' => 'NGT-TERTIARY-1HR',
			];
			return $map[ $format ] ?? 'NGT-ONLINE-1HR';
		}
		if ( in_array( $session_count, [ 12, 16, 20 ], true ) ) {
			return 'NGT-HIGHFREQ-' . $session_count;
		}
		$window = ( '3to12' === $commitment ) ? '3TO12' : '1TO3';
		if ( 'tertiary' === $format ) {
			return 'NGT-TERTIARY-' . $session_count;
		}
		$prefix = ( 'inperson' === $format ) ? 'NGT-INPERSON' : 'NGT-ONLINE';
		$key    = $prefix . '-' . $session_count . '-' . $window;
		return isset( self::definitions()[ $key ] ) ? $key : '';
	}

	/**
	 * @param string $key   Product key.
	 * @param string $price Candidate price.
	 * @return bool
	 */
	public static function price_matches( $key, $price ) {
		$def = self::get( $key );
		if ( ! $def ) {
			return false;
		}
		return abs( (float) $def['price'] - (float) $price ) < 0.001;
	}
}
