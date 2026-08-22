<?php
/**
 * PayFast WooCommerce payment gateway (inbound SA payments).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sandbox + live PayFast redirect gateway with ITN handler.
 */
class NGC_PayFast_Gateway extends WC_Payment_Gateway {

	/** @var string */
	public $merchant_id = '';

	/** @var string */
	public $merchant_key = '';

	/** @var string */
	public $passphrase = '';

	/** @var bool */
	public $sandbox = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'ngc_payfast';
		$this->method_title       = __( 'PayFast (NextGen)', 'nextgencompanion' );
		$this->method_description = __( 'Accept payments via PayFast. Configure merchant credentials in WooCommerce → Settings → Payments.', 'nextgencompanion' );
		$this->has_fields         = false;
		$this->supports           = [ 'products' ];

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title', __( 'PayFast', 'nextgencompanion' ) );
		$this->description  = $this->get_option( 'description', __( 'Pay securely with PayFast.', 'nextgencompanion' ) );
		$this->enabled      = $this->get_option( 'enabled', 'no' );
		$this->merchant_id  = $this->get_option( 'merchant_id', '' );
		$this->merchant_key = $this->get_option( 'merchant_key', '' );
		$this->passphrase   = $this->get_option( 'passphrase', '' );
		$this->sandbox      = 'yes' === $this->get_option( 'sandbox', 'yes' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Admin settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'      => [
				'title'   => __( 'Enable/Disable', 'nextgencompanion' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayFast', 'nextgencompanion' ),
				'default' => 'no',
			],
			'title'        => [
				'title'   => __( 'Title', 'nextgencompanion' ),
				'type'    => 'text',
				'default' => __( 'PayFast', 'nextgencompanion' ),
			],
			'description'  => [
				'title'   => __( 'Description', 'nextgencompanion' ),
				'type'    => 'textarea',
				'default' => __( 'Pay securely with PayFast (card, EFT, instant EFT).', 'nextgencompanion' ),
			],
			'merchant_id'  => [
				'title'       => __( 'Merchant ID', 'nextgencompanion' ),
				'type'        => 'text',
				'description' => __( 'From your PayFast merchant account.', 'nextgencompanion' ),
			],
			'merchant_key' => [
				'title' => __( 'Merchant Key', 'nextgencompanion' ),
				'type'  => 'text',
			],
			'passphrase'   => [
				'title'       => __( 'Passphrase', 'nextgencompanion' ),
				'type'        => 'password',
				'description' => __( 'Required outside sandbox — used for ITN signature validation. Production without a passphrase rejects all ITNs.', 'nextgencompanion' ),
			],
			'sandbox'      => [
				'title'   => __( 'Sandbox', 'nextgencompanion' ),
				'type'    => 'checkbox',
				'label'   => __( 'Use PayFast sandbox', 'nextgencompanion' ),
				'default' => 'yes',
			],
		];
	}

	/**
	 * @param int $order_id Order ID.
	 * @return array<string, string>
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Invalid order.', 'nextgencompanion' ), 'error' );
			return [ 'result' => 'failure' ];
		}

		$order->update_status( 'pending', __( 'Awaiting PayFast payment.', 'nextgencompanion' ) );

		$data = $this->build_payment_data( $order );
		$url  = $this->get_process_url();

		return [
			'result'   => 'success',
			'redirect' => $url . '?' . http_build_query( $data, '', '&', PHP_QUERY_RFC1738 ),
		];
	}

	/**
	 * PayFast process URL.
	 *
	 * @return string
	 */
	public function get_process_url() {
		$live = false === $this->sandbox;
		if ( function_exists( 'apply_filters' ) ) {
			$live = (bool) apply_filters( 'ngc_payfast_live_mode', $live );
		}
		return $live
			? 'https://www.payfast.co.za/eng/process'
			: 'https://sandbox.payfast.co.za/eng/process';
	}

	/**
	 * Signed payload for hosted checkout (GET or POST). Empty values omitted.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, string>
	 */
	public function get_payment_data( $order ) {
		return $this->build_payment_data( $order );
	}

	/**
	 * Build signed PayFast payload.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, string>
	 */
	private function build_payment_data( $order ) {
		$data = [
			'merchant_id'   => $this->merchant_id,
			'merchant_key'  => $this->merchant_key,
			'return_url'    => $this->get_return_url( $order ),
			'cancel_url'    => $order->get_cancel_order_url_raw(),
			'notify_url'    => WC()->api_request_url( 'ngc_payfast_itn' ),
			'name_first'    => $order->get_billing_first_name(),
			'name_last'     => $order->get_billing_last_name(),
			'email_address' => $order->get_billing_email(),
			'm_payment_id'  => (string) $order->get_id(),
			'amount'        => number_format( (float) $order->get_total(), 2, '.', '' ),
			'item_name'     => sprintf(
				/* translators: %s: order number */
				__( 'Order %s', 'nextgencompanion' ),
				$order->get_order_number()
			),
		];
		foreach ( $data as $key => $val ) {
			if ( '' === trim( (string) $val ) ) {
				unset( $data[ $key ] );
			}
		}

		$data['signature'] = NGC_PayFast_Itn::generate_signature( $data, $this->passphrase );
		return $data;
	}

	/**
	 * Handle PayFast ITN (Instant Transaction Notification).
	 */
	public function handle_itn_request() {
		// Rate-limit ITN endpoint (shared fingerprint).
		if ( class_exists( 'NGC_Rate_Limiter' ) && ! NGC_Rate_Limiter::check( 'payfast_itn', 120, 60 ) ) {
			$this->itn_fail( 429, 'rate_limit' );
		}

		$posted = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$order_id = isset( $posted['m_payment_id'] ) ? absint( $posted['m_payment_id'] ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			$this->itn_fail( 404, 'order_not_found' );
		}

		$prior_pf = (string) $order->get_meta( '_ngc_payfast_pf_payment_id', true );
		$gate     = NGC_PayFast_Itn::validate_notification(
			is_array( $posted ) ? $posted : [],
			[
				'merchant_id' => $this->merchant_id,
				'passphrase'  => $this->passphrase,
				'sandbox'     => $this->sandbox,
			],
			(float) $order->get_total(),
			$prior_pf ?: null
		);

		if ( is_wp_error( $gate ) ) {
			$data = $gate->get_error_data();
			// Idempotent replay: acknowledge with 200 so PayFast stops retrying.
			if ( ! empty( $data['idempotent'] ) ) {
				if ( class_exists( 'NGC_Audit' ) ) {
					NGC_Audit::log( 'payfast_itn_replay', 'payment', $order_id, [ 'pf_payment_id' => $posted['pf_payment_id'] ?? '' ], 0 );
				}
				status_header( 200 );
				echo 'OK';
				exit;
			}
			$this->itn_fail( (int) ( $data['status'] ?? 400 ), $gate->get_error_code(), $gate->get_error_message() );
		}

		$status = sanitize_text_field( $posted['payment_status'] ?? '' );
		$pf_id  = sanitize_text_field( $posted['pf_payment_id'] ?? '' );

		if ( 'COMPLETE' === $status ) {
			if ( ! $order->is_paid() ) {
				$order->payment_complete( $pf_id );
			}
			if ( $pf_id ) {
				$order->update_meta_data( '_ngc_payfast_pf_payment_id', $pf_id );
				$order->save();
				NGC_PayFast_Itn::mark_processed( $pf_id, $order_id );
			}
			if ( class_exists( 'NGC_Payments' ) ) {
				NGC_Payments::settle_order( $order->get_id() );
			}
			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log( 'payfast_itn_complete', 'payment', $order_id, [ 'pf_payment_id' => $pf_id ], 0 );
			}
		} elseif ( in_array( $status, [ 'FAILED', 'CANCELLED' ], true ) ) {
			if ( ! $order->has_status( [ 'completed', 'processing' ] ) ) {
				$order->update_status( 'failed', __( 'PayFast payment failed or cancelled.', 'nextgencompanion' ) );
			}
			if ( $pf_id ) {
				NGC_PayFast_Itn::mark_processed( $pf_id, $order_id );
			}
		}

		status_header( 200 );
		echo 'OK';
		exit;
	}

	/**
	 * Reject ITN with audit trail.
	 *
	 * @param int    $code   HTTP status.
	 * @param string $reason Reason code.
	 * @param string $detail Optional detail.
	 */
	private function itn_fail( $code, $reason, $detail = '' ) {
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'payfast_itn_rejected',
				'payment',
				0,
				[
					'reason' => $reason,
					'detail' => $detail,
					'code'   => (int) $code,
				],
				0
			);
		}
		if ( class_exists( 'NGC_Fraud_Engine' ) && in_array( $reason, [ 'ngc_pf_signature', 'ngc_pf_amount', 'ngc_pf_merchant' ], true ) ) {
			NGC_Fraud_Engine::raise_signal(
				'payment_failure_spike',
				'payfast_itn',
				0,
				[ 'reason' => $reason, 'detail' => $detail ]
			);
		}
		status_header( (int) $code );
		exit;
	}
}
