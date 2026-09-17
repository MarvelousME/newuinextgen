<?php
/**
 * Checkout / cart integrity for tutoring products.
 *
 * Parent REST checkout and WooCommerce cart both converge on
 * prepare_order_args() so client-supplied prices and identities cannot skip
 * NGC_Session_Price_Integrity.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves Woo products, validates selection, stamps order item metadata.
 */
class NGC_Session_Checkout {

	public const DEFAULT_TIMEZONE = 'Africa/Johannesburg';

	/**
	 * Request/cart fields a client may propose. Never includes price or join URLs.
	 *
	 * @var string[]
	 */
	private static $untrusted_fields = [
		'booking_id',
		'tutor_user_id',
		'student_user_id',
		'subject_id',
		'subject',
		'subject_name',
		'scheduled_start',
		'scheduled_end',
		'product_key',
		'duration_minutes',
		'session_count',
		'timezone',
		'format',
		'commitment',
	];

	/**
	 * Server-validated fields persisted onto the Woo cart item.
	 *
	 * @var string[]
	 */
	private static $cart_snapshot_keys = [
		'booking_id',
		'tutor_user_id',
		'student_user_id',
		'parent_user_id',
		'subject_id',
		'subject_name',
		'scheduled_start',
		'scheduled_end',
		'product_key',
		'duration_minutes',
		'session_count',
		'timezone',
		'user_id',
		'mode',
	];

	/**
	 * @return void
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_item_data' ], 10, 3 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'stamp_line_item' ], 10, 4 );
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'stamp_order_from_cart' ], 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'on_order_processed' ], 20, 3 );
		add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'validate_add_to_cart' ], 10, 3 );
	}

	/**
	 * Normalize create_order args: product, identity, price.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function prepare_order_args( array $args ) {
		$args    = self::enrich_from_booking( $args );
		$actor   = (int) ( $args['user_id'] ?? ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0 ) );
		$student = (int) ( $args['student_user_id'] ?? 0 );
		$parties = NGC_Session_Identity::resolve_parties( $actor, $student ?: $actor );

		try {
			NGC_Session_Identity::assert_billing_customer( $parties['customer_user_id'] );
		} catch ( NGC_Session_Exception $e ) {
			return $e->to_wp_error();
		}

		$key = self::resolve_product_key( $args );
		$def = NGC_Product_Catalog::get( $key );
		if ( ! $def ) {
			return new WP_Error( 'ngc_unknown_product', __( 'Unknown tutoring product.', 'nextgencompanion' ) );
		}

		try {
			$validated = NGC_Session_Price_Integrity::validate(
				array_merge(
					$args,
					[
						'product_key'      => $key,
						'tutor_user_id'    => (int) ( $args['tutor_user_id'] ?? 0 ),
						'student_user_id'  => (int) $parties['student_user_id'],
						'subject_id'       => (string) ( $args['subject_id'] ?? $args['subject'] ?? '' ),
						'duration_minutes' => (int) ( $args['duration_minutes'] ?? $def['duration_minutes'] ),
						'session_count'    => (int) $def['session_count'],
						'currency'         => 'ZAR',
					]
				)
			);
		} catch ( NGC_Session_Exception $e ) {
			NGC_Session_Observability::failure( 'product_resolution_failure_total', [ 'operation' => 'checkout' ] );
			return $e->to_wp_error();
		}

		$product_id = NGC_Product_Provisioner::product_id_for_key( $key );
		if ( ! $product_id ) {
			return new WP_Error( 'product_missing', __( 'Lesson product is not configured.', 'nextgencompanion' ) );
		}

		NGC_Session_Observability::success( 'product_resolution_success_total', [ 'operation' => 'checkout' ] );

		return array_merge(
			$args,
			$validated,
			[
				'user_id'         => (int) $parties['customer_user_id'],
				'parent_user_id'  => (int) $parties['parent_user_id'],
				'student_user_id' => (int) $parties['student_user_id'],
				'product_id'      => $product_id,
				'product_key'     => $key,
				'mode'            => $parties['mode'],
			]
		);
	}

	/**
	 * @param WC_Order             $order Order.
	 * @param array<string, mixed> $args  Args.
	 * @return void
	 */
	public static function stamp_order( $order, array $args ) {
		self::apply_prepared_order_meta( $order, $args );
		$order->update_meta_data( '_ngt_correlation_pending', '1' );
		foreach ( $order->get_items() as $item ) {
			self::write_item_meta( $item, $args );
			$item->save();
		}
	}

	/**
	 * @param WC_Order_Item_Product $item Item.
	 * @param array<string, mixed>  $args Args.
	 * @return void
	 */
	public static function write_item_meta( $item, array $args ) {
		$tutor    = (int) ( $args['tutor_user_id'] ?? 0 );
		$student  = (int) ( $args['student_user_id'] ?? 0 );
		$parent   = (int) ( $args['parent_user_id'] ?? 0 );
		$customer = (int) ( $args['user_id'] ?? 0 );
		if ( $customer <= 0 ) {
			$customer = $parent ? $parent : $student;
		}

		foreach (
			[
				'_ngt_booking_id'        => (int) ( $args['booking_id'] ?? 0 ),
				'_ngt_tutor_id'          => $tutor,
				'_ngt_tutor_name'        => self::display_name( $tutor ),
				'_ngt_student_id'        => $student,
				'_ngt_student_name'      => self::display_name( $student ),
				'_ngt_parent_id'         => $parent,
				'_ngt_customer_id'       => $customer,
				'_ngt_subject_id'        => sanitize_title( (string) ( $args['subject_id'] ?? '' ) ),
				'_ngt_subject_name'      => sanitize_text_field( (string) ( $args['subject_name'] ?? $args['subject'] ?? '' ) ),
				'_ngt_duration_minutes'  => (int) ( $args['duration_minutes'] ?? 60 ),
				'_ngt_session_count'     => (int) ( $args['session_count'] ?? 1 ),
				'_ngt_scheduled_start'   => sanitize_text_field( (string) ( $args['scheduled_start'] ?? '' ) ),
				'_ngt_scheduled_end'     => sanitize_text_field( (string) ( $args['scheduled_end'] ?? '' ) ),
				'_ngt_timezone'          => sanitize_text_field( (string) ( $args['timezone'] ?? self::DEFAULT_TIMEZONE ) ),
				'_ngt_product_key'       => (string) ( $args['product_key'] ?? '' ),
				'_ngt_pricing_rule'      => NGC_Product_Catalog::CATALOG_VERSION,
			] as $meta_key => $value
		) {
			$item->update_meta_data( $meta_key, $value );
		}
	}

	/**
	 * Copy only allow-listed fields from a request/cart source.
	 *
	 * @param array<string, mixed> $source     Request or cart item.
	 * @param int                  $product_id Product ID.
	 * @return array<string, mixed>
	 */
	public static function collect_untrusted_context( array $source, $product_id = 0 ) {
		$args = [];
		foreach ( self::$untrusted_fields as $field ) {
			$value = self::scalar_from_source( $source, $field );
			if ( null !== $value ) {
				$args[ $field ] = $value;
			}
		}
		$key = NGC_Product_Catalog::key_for_product( $product_id );
		if ( $key ) {
			$args['product_key'] = $key;
		}
		return $args;
	}

	/**
	 * Validate untrusted cart/checkout fields through the same integrity path as parent checkout.
	 *
	 * @param array<string, mixed> $source     Request or cart item.
	 * @param int                  $product_id Product ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function prepare_cart_context( array $source, $product_id = 0 ) {
		return self::prepare_order_args( self::collect_untrusted_context( $source, $product_id ) );
	}

	/**
	 * Persist only server-validated fields on the cart item.
	 *
	 * @param array<string, mixed> $prepared Prepared args.
	 * @return array<string, mixed>
	 */
	public static function to_cart_item_data( array $prepared ) {
		$out = [ '_ngt_validated' => 1 ];
		foreach ( self::$cart_snapshot_keys as $key ) {
			if ( isset( $prepared[ $key ] ) ) {
				$out[ 'ngt_' . $key ] = $prepared[ $key ];
			}
		}
		return $out;
	}

	/**
	 * @param array $cart_item_data Data.
	 * @param int   $product_id     Product.
	 * @param int   $variation_id   Variation.
	 * @return array<string, mixed>
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		unset( $variation_id );
		if ( ! NGC_Product_Catalog::key_for_product( $product_id ) ) {
			return $cart_item_data;
		}
		$prepared = self::prepare_cart_context( self::request_source(), (int) $product_id );
		if ( is_wp_error( $prepared ) ) {
			return $cart_item_data;
		}
		return array_merge( $cart_item_data, self::to_cart_item_data( $prepared ) );
	}

	/**
	 * @param bool $passed     Passed.
	 * @param int  $product_id Product.
	 * @param int  $qty        Qty.
	 * @return bool
	 */
	public static function validate_add_to_cart( $passed, $product_id, $qty ) {
		unset( $qty );
		if ( ! $passed ) {
			return false;
		}
		$key = NGC_Product_Catalog::key_for_product( $product_id );
		if ( ! $key ) {
			return $passed;
		}
		$price = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( $price && ! NGC_Product_Catalog::price_matches( $key, $price->get_regular_price() ) ) {
			return self::reject_cart( __( 'Product price does not match the tutoring catalogue.', 'nextgencompanion' ) );
		}
		$prepared = self::prepare_cart_context( self::request_source(), (int) $product_id );
		if ( is_wp_error( $prepared ) ) {
			return self::reject_cart( $prepared->get_error_message() );
		}
		return true;
	}

	/**
	 * @param WC_Order_Item_Product $item          Item.
	 * @param string                $cart_item_key Key.
	 * @param array                 $values        Values.
	 * @param WC_Order              $order         Order.
	 * @return void
	 */
	public static function stamp_line_item( $item, $cart_item_key, $values, $order ) {
		unset( $cart_item_key );
		$product_id = (int) ( $values['product_id'] ?? 0 );
		if ( ! $product_id && is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
			$product_id = (int) $item->get_product_id();
		}
		$key = NGC_Product_Catalog::key_for_product( $product_id );
		if ( ! $key ) {
			$key = (string) ( $values['ngt_product_key'] ?? '' );
		}
		if ( ! $key ) {
			return;
		}
		$prepared = self::prepare_cart_context( is_array( $values ) ? $values : [], $product_id );
		if ( is_wp_error( $prepared ) ) {
			throw new Exception( $prepared->get_error_message() );
		}
		self::write_item_meta( $item, $prepared );
		self::apply_prepared_order_meta( $order, $prepared );
	}

	/**
	 * @param WC_Order $order Order.
	 * @param array    $data  Data.
	 * @return void
	 */
	public static function stamp_order_from_cart( $order, $data ) {
		unset( $data );
		foreach ( $order->get_items() as $item ) {
			$prepared = [
				'booking_id'  => (int) $item->get_meta( '_ngt_booking_id' ),
				'product_key' => (string) $item->get_meta( '_ngt_product_key' ),
				'user_id'     => (int) $item->get_meta( '_ngt_customer_id' ) ?: (int) $item->get_meta( '_ngt_parent_id' ),
			];
			if ( empty( $prepared['booking_id'] ) && '' === $prepared['product_key'] ) {
				continue;
			}
			self::apply_prepared_order_meta( $order, $prepared );
			break;
		}
	}

	/**
	 * @param int      $order_id Order.
	 * @param array    $posted   Posted.
	 * @param WC_Order $order    Order.
	 * @return void
	 */
	public static function on_order_processed( $order_id, $posted, $order ) {
		unset( $posted );
		NGC_Session_Observability::success( 'checkout_success_total', [ 'order_id' => (int) $order_id ] );
		if ( class_exists( 'NGC_Ensure_Session_Provisioned' ) ) {
			$booking_id = $order ? (int) $order->get_meta( 'ngc_booking_id' ) : 0;
			NGC_Ensure_Session_Provisioned::run( (int) $order_id, $booking_id );
		}
	}

	/**
	 * Fill tutor/subject/schedule from the booking when the client omitted them.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array<string, mixed>
	 */
	private static function enrich_from_booking( array $args ) {
		$booking_id = (int) ( $args['booking_id'] ?? 0 );
		if ( $booking_id <= 0 || ! class_exists( 'NGC_Bookings' ) ) {
			return $args;
		}
		$booking = NGC_Bookings::get( $booking_id );
		if ( ! $booking ) {
			return $args;
		}
		$args['student_user_id']       = (int) ( $args['student_user_id'] ?? 0 ) ?: (int) $booking->student_user_id;
		$args['tutor_user_id']         = $args['tutor_user_id'] ?? (int) $booking->tutor_user_id;
		$args['subject']               = $args['subject'] ?? (string) $booking->subject;
		$args['scheduled_start']       = $args['scheduled_start'] ?? (string) $booking->scheduled_at;
		$args['duration_minutes']      = $args['duration_minutes'] ?? (int) $booking->duration_minutes;
		$args['booking_tutor_user_id'] = (int) $booking->tutor_user_id;
		$args['booking_subject']       = (string) $booking->subject;
		return $args;
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return string
	 */
	private static function resolve_product_key( array $args ) {
		$key = strtoupper( (string) ( $args['product_key'] ?? '' ) );
		if ( '' !== $key ) {
			return $key;
		}
		return NGC_Product_Catalog::resolve_key(
			sanitize_key( (string) ( $args['format'] ?? 'online' ) ),
			(int) ( $args['session_count'] ?? 1 ),
			(string) ( $args['commitment'] ?? '1to3' )
		);
	}

	/**
	 * @param mixed  $order    WC order.
	 * @param array<string, mixed> $prepared Validated args.
	 * @return void
	 */
	private static function apply_prepared_order_meta( $order, array $prepared ) {
		if ( ! $order || ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}
		if ( ! empty( $prepared['booking_id'] ) ) {
			$order->update_meta_data( 'ngc_booking_id', (int) $prepared['booking_id'] );
		}
		if ( isset( $prepared['product_key'] ) ) {
			$order->update_meta_data( '_ngt_product_key', (string) $prepared['product_key'] );
		}
		if ( ! empty( $prepared['user_id'] ) && method_exists( $order, 'set_customer_id' ) ) {
			$order->set_customer_id( (int) $prepared['user_id'] );
		}
	}

	/**
	 * @param array<string, mixed> $source Source.
	 * @param string               $field  Field.
	 * @return string|null
	 */
	private static function scalar_from_source( array $source, $field ) {
		$raw = null;
		if ( isset( $source[ $field ] ) && '' !== $source[ $field ] && null !== $source[ $field ] ) {
			$raw = $source[ $field ];
		} elseif ( isset( $source[ 'ngt_' . $field ] ) && '' !== $source[ 'ngt_' . $field ] && null !== $source[ 'ngt_' . $field ] ) {
			$raw = $source[ 'ngt_' . $field ];
		}
		if ( null === $raw ) {
			return null;
		}
		return sanitize_text_field( wp_unslash( (string) $raw ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function request_source() {
		return is_array( $_REQUEST ) ? $_REQUEST : []; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * @param string $message Notice.
	 * @return false
	 */
	private static function reject_cart( $message ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, 'error' );
		}
		NGC_Session_Observability::failure( 'product_resolution_failure_total', [ 'operation' => 'add_to_cart' ] );
		return false;
	}

	/**
	 * @param int $user_id User.
	 * @return string
	 */
	private static function display_name( $user_id ) {
		if ( ! $user_id || ! function_exists( 'get_userdata' ) ) {
			return '';
		}
		$user = get_userdata( (int) $user_id );
		return $user ? (string) $user->display_name : '';
	}
}
