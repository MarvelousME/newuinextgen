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
		add_action( 'template_redirect', [ __CLASS__, 'maybe_redirect_payfast' ], 6 );
		add_filter( 'allowed_redirect_hosts', [ __CLASS__, 'allow_payfast_hosts' ] );
	}

	/**
	 * PayFast sandbox + live hosts (wp_safe_redirect otherwise strips them).
	 *
	 * @param string[] $hosts Allowed hosts.
	 * @return string[]
	 */
	public static function allow_payfast_hosts( $hosts ) {
		$hosts   = is_array( $hosts ) ? $hosts : [];
		$hosts[] = 'sandbox.payfast.co.za';
		$hosts[] = 'www.payfast.co.za';
		$hosts[] = 'payfast.co.za';
		return array_values( array_unique( $hosts ) );
	}

	/**
	 * Whether the current request is the parent-checkout page.
	 *
	 * @return bool
	 */
	private static function is_checkout_page() {
		if ( function_exists( 'is_page' ) && is_page( 'parent-checkout' ) ) {
			return true;
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return false !== strpos( $request, '/parent-checkout' );
	}

	/**
	 * Create the order and send the parent to PayFast before any theme HTML.
	 */
	public static function maybe_redirect_payfast() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( ! is_user_logged_in() || empty( $_POST['ngc_payfast_pay'] ) ) {
			return;
		}
		if ( ! self::is_checkout_page() ) {
			return;
		}
		$nonce = isset( $_POST['ngc_payfast_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ngc_payfast_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ngc_payfast_pay' ) ) {
			return;
		}

		$user       = wp_get_current_user();
		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$order      = self::create_order(
			[
				'user_id'    => (int) $user->ID,
				'booking_id' => $booking_id,
				'email'      => $user->user_email,
				'first_name' => $user->first_name ?: $user->display_name,
				'last_name'  => $user->last_name ?: '',
			]
		);
		if ( is_wp_error( $order ) ) {
			set_transient( 'ngc_checkout_notice_' . get_current_user_id(), $order->get_error_message(), 120 );
			return;
		}

		$result = self::start_checkout( (int) $order->get_id() );
		if ( is_wp_error( $result ) ) {
			set_transient( 'ngc_checkout_notice_' . get_current_user_id(), $result->get_error_message(), 120 );
			return;
		}

		$registered = WC()->payment_gateways()->payment_gateways();
		$available  = WC()->payment_gateways()->get_available_payment_gateways();
		$gateway    = $registered['ngc_payfast'] ?? $available['ngc_payfast'] ?? null;
		if ( $gateway && method_exists( $gateway, 'get_payment_data' ) ) {
			self::emit_payfast_post( $gateway->get_process_url(), $gateway->get_payment_data( $order ) );
		}

		$redirect = isset( $result['redirect'] ) ? (string) $result['redirect'] : '';
		if ( $redirect ) {
			wp_redirect( $redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- PayFast is an allowlisted external host.
			exit;
		}

		set_transient( 'ngc_checkout_notice_' . get_current_user_id(), __( 'Could not start checkout.', 'nextgencompanion' ), 120 );
	}

	/**
	 * Auto-submit POST so return_url query args cannot poison the PayFast signature.
	 *
	 * @param string               $url  Process URL.
	 * @param array<string, string> $data Signed fields.
	 */
	private static function emit_payfast_post( $url, $data ) {
		if ( ! is_array( $data ) || empty( $data ) || '' === (string) $url ) {
			return;
		}
		nocache_headers();
		echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . esc_html__( 'Redirecting to PayFast', 'nextgencompanion' ) . '</title></head><body>';
		echo '<p>' . esc_html__( 'Redirecting to PayFast…', 'nextgencompanion' ) . '</p>';
		echo '<form id="ngc-payfast-hosted" method="post" action="' . esc_url( $url ) . '">';
		foreach ( $data as $name => $value ) {
			echo '<input type="hidden" name="' . esc_attr( (string) $name ) . '" value="' . esc_attr( (string) $value ) . '" />';
		}
		echo '</form><script>document.getElementById("ngc-payfast-hosted").submit();</script>';
		echo '</body></html>';
		exit;
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

		$args       = self::hydrate_args_from_booking( $args );
		$product_id = (int) ( $args['product_id'] ?? 0 );
		if ( ! $product_id && class_exists( 'NGC_Product_Provisioner' ) ) {
			$product_id = NGC_Product_Provisioner::resolve_for_booking(
				[
					'duration_minutes' => (int) ( $args['duration_minutes'] ?? 60 ),
					'package_type'     => (string) ( $args['package_type'] ?? 'single' ),
					'subject'          => (string) ( $args['subject'] ?? '' ),
				]
			);
		}
		if ( ! $product_id ) {
			$product_id = self::ensure_product();
		}
		$product = $product_id ? wc_get_product( $product_id ) : null;
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
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'update_meta_data' ) ) {
				continue;
			}
			$item->update_meta_data( '_ngt_booking_id', $booking_id );
			$item->update_meta_data( '_ngt_tutor_user_id', (int) ( $args['tutor_user_id'] ?? 0 ) );
			$item->update_meta_data( '_ngt_student_user_id', (int) ( $args['student_user_id'] ?? 0 ) );
			$item->update_meta_data( '_ngt_parent_user_id', $user_id );
			$item->update_meta_data( '_ngt_subject', sanitize_text_field( (string) ( $args['subject'] ?? '' ) ) );
			$item->update_meta_data( '_ngt_duration_minutes', (int) ( $args['duration_minutes'] ?? 60 ) );
			$item->update_meta_data( '_ngt_product_id', $product_id );
			$item->update_meta_data( '_ngt_scheduled_start', sanitize_text_field( (string) ( $args['scheduled_start'] ?? '' ) ) );
			$item->save();
			break;
		}
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
		if ( ! is_user_logged_in() ) {
			return '<p class="ngc-checkout-notice">' . esc_html__( 'Please sign in to pay for a lesson.', 'nextgencompanion' ) . '</p>';
		}

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

		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : (int) $atts['booking_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice_key = 'ngc_checkout_notice_' . get_current_user_id();
		$notice     = get_transient( $notice_key );
		if ( $notice ) {
			delete_transient( $notice_key );
		}

		$payfast = null;
		if ( function_exists( 'WC' ) && WC() && WC()->payment_gateways() ) {
			$gateways = WC()->payment_gateways()->get_available_payment_gateways();
			$payfast  = $gateways['ngc_payfast'] ?? null;
		}

		ob_start();
		echo '<section class="ngc-parent-checkout ngt-card" style="padding:32px;max-width:42rem;margin:24px auto">';
		echo '<h2>' . esc_html__( 'Pay for a lesson', 'nextgencompanion' ) . '</h2>';
		echo '<p>' . esc_html__( 'You will be redirected to PayFast (sandbox on this site) to complete payment. No order is created until you click Pay.', 'nextgencompanion' ) . '</p>';
		if ( $notice ) {
			echo '<p class="ngc-checkout-notice" role="alert">' . esc_html( (string) $notice ) . '</p>';
		}
		if ( ! $payfast ) {
			echo '<p class="ngc-checkout-notice" role="alert">' . esc_html__( 'PayFast gateway is not enabled. Enable it under WooCommerce → Settings → Payments.', 'nextgencompanion' ) . '</p>';
		}
		echo '<form method="post" action="">';
		wp_nonce_field( 'ngc_payfast_pay', 'ngc_payfast_nonce' );
		echo '<input type="hidden" name="ngc_payfast_pay" value="1" />';
		echo '<p><label for="ngc-booking-id">' . esc_html__( 'Booking ID (optional)', 'nextgencompanion' ) . '</label><br />';
		echo '<input id="ngc-booking-id" name="booking_id" type="number" min="0" value="' . esc_attr( (string) $booking_id ) . '" /></p>';
		echo '<button type="submit" class="ngt-btn ngt-btn--primary" data-ngc-no-process="1"' . ( $payfast ? '' : ' disabled' ) . '>' . esc_html__( 'Pay with PayFast', 'nextgencompanion' ) . '</button>';
		echo '</form></section>';
		return (string) ob_get_clean();
	}

	/**
	 * Fill tutor/student/subject/duration from booking when only booking_id is supplied.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array<string, mixed>
	 */
	private static function hydrate_args_from_booking( $args ) {
		$booking_id = (int) ( $args['booking_id'] ?? 0 );
		if ( $booking_id <= 0 || ! class_exists( 'NGC_Bookings' ) ) {
			return $args;
		}
		$booking = NGC_Bookings::get( $booking_id );
		if ( ! $booking ) {
			return $args;
		}
		if ( empty( $args['tutor_user_id'] ) ) {
			$args['tutor_user_id'] = (int) $booking->tutor_user_id;
		}
		if ( empty( $args['student_user_id'] ) ) {
			$args['student_user_id'] = (int) $booking->student_user_id;
		}
		if ( empty( $args['subject'] ) ) {
			$args['subject'] = (string) $booking->subject;
		}
		if ( empty( $args['duration_minutes'] ) ) {
			$args['duration_minutes'] = (int) ( $booking->duration_minutes ?: 60 );
		}
		if ( empty( $args['scheduled_start'] ) && ! empty( $booking->scheduled_at ) ) {
			$args['scheduled_start'] = (string) $booking->scheduled_at;
		}
		return $args;
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
				'permission_callback' => static function () {
					return is_user_logged_in();
				},
				'args'                => [
					'booking_id' => [ 'type' => 'integer', 'default' => 0 ],
					'product_id' => [ 'type' => 'integer', 'default' => 0 ],
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
		$user_id    = get_current_user_id();
		$booking_id = (int) $request->get_param( 'booking_id' );
		if ( $booking_id > 0 && class_exists( 'NGC_Bookings' ) ) {
			$booking = NGC_Bookings::get( $booking_id );
			if ( ! $booking ) {
				return new WP_Error( 'booking_missing', __( 'Booking not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
			}
			$student_id = (int) $booking->student_user_id;
			$allowed    = user_can( $user_id, 'manage_options' ) || $user_id === $student_id;
			if ( ! $allowed && $student_id > 0 ) {
				// Parent of the learner (via matches / child-learner meta).
				$parent_of = (int) get_user_meta( $student_id, 'ngc_parent_user_id', true );
				if ( ! $parent_of ) {
					$parent_of = (int) get_user_meta( $student_id, 'ngt_parent_user_id', true );
				}
				$allowed = $user_id === $parent_of;
			}
			if ( ! $allowed && method_exists( 'NGC_Bookings', 'query_for_parent' ) ) {
				foreach ( NGC_Bookings::query_for_parent( $user_id, 100 ) as $row ) {
					if ( (int) $row->id === $booking_id ) {
						$allowed = true;
						break;
					}
				}
			}
			if ( ! $allowed ) {
				return new WP_Error( 'checkout_forbidden', __( 'You cannot pay for this booking.', 'nextgencompanion' ), [ 'status' => 403 ] );
			}
		}

		$order = self::create_order(
			[
				'user_id'    => $user_id,
				'booking_id' => $booking_id,
				'product_id' => (int) $request->get_param( 'product_id' ),
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
