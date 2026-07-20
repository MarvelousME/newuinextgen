<?php
/**
 * Parent-paid lesson checkout via WooCommerce + PayFast.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates payable orders for parents and exposes checkout shortcode/REST.
 */
class NGC_Parent_Checkout {

	const PRODUCT_OPTION = 'ngc_lesson_credit_product_id';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_shortcode( 'ngc_parent_checkout', [ __CLASS__, 'shortcode' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
	}

	/**
	 * Ensure a simple WooCommerce product exists for lesson credits.
	 *
	 * @return int Product ID.
	 */
	public static function ensure_product() {
		$product_id = (int) get_option( self::PRODUCT_OPTION, 0 );
		if ( $product_id && 'product' === get_post_type( $product_id ) ) {
			return $product_id;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return 0;
		}

		$product_id = wp_insert_post(
			[
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => __( 'Online Lesson Credit', 'nextgencompanion' ),
			],
			true
		);

		if ( is_wp_error( $product_id ) || ! $product_id ) {
			return 0;
		}

		wp_set_object_terms( $product_id, 'simple', 'product_type' );
		update_post_meta( $product_id, '_regular_price', '320' );
		update_post_meta( $product_id, '_price', '320' );
		update_post_meta( $product_id, '_virtual', 'yes' );
		update_option( self::PRODUCT_OPTION, $product_id, false );

		return (int) $product_id;
	}

	/**
	 * @param array<string, mixed> $args Checkout args.
	 * @return WC_Order|WP_Error
	 */
	public static function create_order( $args = [] ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'nextgencompanion' ) );
		}

		$product_id  = (int) ( $args['product_id'] ?? self::ensure_product() );
		$product     = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $product ) {
			return new WP_Error( 'product_missing', __( 'Lesson product is not configured.', 'nextgencompanion' ) );
		}

		$user_id    = (int) ( $args['user_id'] ?? get_current_user_id() );
		$booking_id = (int) ( $args['booking_id'] ?? 0 );
		$email      = sanitize_email( $args['email'] ?? '' );
		$first      = sanitize_text_field( $args['first_name'] ?? 'Parent' );
		$last       = sanitize_text_field( $args['last_name'] ?? 'Guardian' );

		$order = wc_create_order( [ 'customer_id' => max( 0, $user_id ) ] );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$order->add_product( $product, max( 1, (int) ( $args['qty'] ?? 1 ) ) );
		$order->set_billing_first_name( $first );
		$order->set_billing_last_name( $last );
		if ( $email ) {
			$order->set_billing_email( $email );
		}
		$order->set_currency( 'ZAR' );
		if ( $booking_id ) {
			$order->update_meta_data( 'ngc_booking_id', $booking_id );
		}
		$order->calculate_totals();
		$order->save();

		return $order;
	}

	/**
	 * Redirect parent to PayFast checkout for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function start_checkout( $order_id ) {
		$order_id = (int) $order_id;
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			return new WP_Error( 'order_missing', __( 'Order not found.', 'nextgencompanion' ) );
		}

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$gateway  = $gateways['ngc_payfast'] ?? null;
		if ( ! $gateway ) {
			return new WP_Error( 'payfast_unavailable', __( 'PayFast gateway is not enabled.', 'nextgencompanion' ) );
		}

		return $gateway->process_payment( $order_id );
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts = [] ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '<p class="ngc-checkout-notice">' . esc_html__( 'Online payments are temporarily unavailable.', 'nextgencompanion' ) . '</p>';
		}

		$atts = shortcode_atts(
			[
				'booking_id' => '0',
				'product_id' => '0',
			],
			(array) $atts,
			'ngc_parent_checkout'
		);

		$user = wp_get_current_user();
		$order = self::create_order(
			[
				'user_id'    => (int) $user->ID,
				'booking_id' => (int) $atts['booking_id'],
				'product_id' => (int) $atts['product_id'],
				'email'      => $user->user_email,
				'first_name' => $user->first_name ?: $user->display_name,
				'last_name'  => $user->last_name ?: '',
			]
		);

		if ( is_wp_error( $order ) ) {
			return '<p class="ngc-checkout-notice">' . esc_html( $order->get_error_message() ) . '</p>';
		}

		$result = self::start_checkout( $order->get_id() );
		if ( is_wp_error( $result ) ) {
			return '<p class="ngc-checkout-notice">' . esc_html( $result->get_error_message() ) . '</p>';
		}

		if ( ! empty( $result['redirect'] ) ) {
			wp_safe_redirect( $result['redirect'] );
			exit;
		}

		return '<p class="ngc-checkout-notice">' . esc_html__( 'Could not start checkout.', 'nextgencompanion' ) . '</p>';
	}

	/**
	 * REST: POST /ngc/v1/checkout/parent
	 */
	public static function register_rest() {
		register_rest_route(
			'ngc/v1',
			'/checkout/parent',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_create_checkout' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'booking_id' => [ 'type' => 'integer', 'default' => 0 ],
					'email'      => [ 'type' => 'string' ],
					'first_name' => [ 'type' => 'string' ],
					'last_name'  => [ 'type' => 'string' ],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_create_checkout( $request ) {
		$order = self::create_order(
			[
				'user_id'    => get_current_user_id(),
				'booking_id' => (int) $request->get_param( 'booking_id' ),
				'email'      => (string) $request->get_param( 'email' ),
				'first_name' => (string) $request->get_param( 'first_name' ),
				'last_name'  => (string) $request->get_param( 'last_name' ),
			]
		);

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$result = self::start_checkout( $order->get_id() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			[
				'order_id' => $order->get_id(),
				'redirect' => $result['redirect'] ?? '',
				'result'   => $result['result'] ?? 'success',
			]
		);
	}
}
