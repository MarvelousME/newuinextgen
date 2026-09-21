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
		$this->method_description = __( 'Accept payments via PayFast. Sandbox credentials are prefilled. Uncheck Sandbox and paste live Merchant ID / Key / Passphrase when you go live.', 'nextgencompanion' );
		$this->has_fields         = false;
		$this->supports           = [ 'products' ];

		$this->init_form_fields();
		$this->init_settings();
		$this->apply_resolved_credentials();

		$this->title       = $this->get_option( 'title', __( 'PayFast', 'nextgencompanion' ) );
		$this->description = $this->get_option( 'description', __( 'Pay securely with PayFast.', 'nextgencompanion' ) );
		$this->enabled     = $this->get_option( 'enabled', 'no' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );
	}

	/**
	 * Overlay sandbox defaults and wp-config constants.
	 */
	private function apply_resolved_credentials() {
		$resolved           = NGC_PayFast_Credentials::resolve( is_array( $this->settings ) ? $this->settings : [] );
		$this->merchant_id  = $resolved['merchant_id'];
		$this->merchant_key = $resolved['merchant_key'];
		$this->passphrase   = $resolved['passphrase'];
		$this->sandbox      = (bool) $resolved['sandbox'];
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
				'default' => 'yes',
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
				'description' => __( 'Sandbox is prefilled with PayFast public test merchant 10000100. Replace with your live merchant ID when leaving sandbox.', 'nextgencompanion' ),
				'default'     => NGC_PayFast_Credentials::SANDBOX_MERCHANT_ID,
			],
			'merchant_key' => [
				'title'   => __( 'Merchant Key', 'nextgencompanion' ),
				'type'    => 'text',
				'default' => NGC_PayFast_Credentials::SANDBOX_MERCHANT_KEY,
			],
			'passphrase'   => [
				'title'       => __( 'Passphrase', 'nextgencompanion' ),
				'type'        => 'password',
				'description' => __( 'Required outside sandbox — used for ITN signature validation. Production without a passphrase rejects all ITNs.', 'nextgencompanion' ),
				'default'     => NGC_PayFast_Credentials::SANDBOX_PASSPHRASE,
			],
			'sandbox'      => [
				'title'   => __( 'Sandbox', 'nextgencompanion' ),
				'type'    => 'checkbox',
				'label'   => __( 'Use PayFast sandbox (uncheck for live merchant credentials)', 'nextgencompanion' ),
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

		$order->set_payment_method( $this->id );
		$order->set_payment_method_title( $this->get_title() );
		$order->update_status( 'pending', __( 'Awaiting PayFast payment.', 'nextgencompanion' ) );
		$order->save();

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * Auto-submit POST form to PayFast hosted checkout (GET query strings are rejected).
	 *
	 * @param int $order_id Order ID.
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$hosted = $this->get_hosted_checkout( $order );
		echo '<p>' . esc_html__( 'Redirecting to PayFast…', 'nextgencompanion' ) . '</p>';
		echo $this->hosted_form_html( $hosted ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Signed hosted checkout payload.
	 *
	 * @param WC_Order $order Order.
	 * @return array{process_url:string,fields:array<string,string>,signature:string}
	 */
	public function get_hosted_checkout( $order ) {
		$fields = $this->build_payment_data( $order );
		$sig    = (string) ( $fields['signature'] ?? '' );
		return [
			'process_url' => $this->process_url(),
			'fields'      => $fields,
			'signature'   => $sig,
		];
	}

	/**
	 * @param array{process_url:string,fields:array<string,string>} $hosted Hosted payload.
	 * @return string
	 */
	public function hosted_form_html( array $hosted ) {
		$action = esc_url( (string) ( $hosted['process_url'] ?? $this->process_url() ) );
		$html   = '<form id="ngc-payfast-checkout" action="' . $action . '" method="POST">';
		foreach ( (array) ( $hosted['fields'] ?? [] ) as $key => $val ) {
			$html .= '<input type="hidden" name="' . esc_attr( (string) $key ) . '" value="' . esc_attr( (string) $val ) . '" />';
		}
		$html .= '<noscript><button type="submit">' . esc_html__( 'Continue to PayFast', 'nextgencompanion' ) . '</button></noscript>';
		$html .= '</form>';
		$html .= '<script>document.getElementById("ngc-payfast-checkout")&&document.getElementById("ngc-payfast-checkout").submit();</script>';
		return $html;
	}

	/**
	 * PayFast process URL.
	 *
	 * @return string
	 */
	public function process_url() {
		return $this->sandbox
			? NGC_PayFast_Credentials::SANDBOX_PROCESS_URL
			: NGC_PayFast_Credentials::LIVE_PROCESS_URL;
	}

	/**
	 * Build signed PayFast payload.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, string>
	 */
	public function build_payment_data( $order ) {
		$booking_id = (string) $order->get_meta( 'ngc_booking_id' );
		$product    = 'NGT-ONLINE-1HR';
		foreach ( $order->get_items() as $item ) {
			$sku = is_callable( [ $item, 'get_product' ] ) && $item->get_product() ? (string) $item->get_product()->get_sku() : '';
			if ( $sku ) {
				$product = $sku;
				break;
			}
		}

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
			'item_description' => $product,
			'custom_str1'      => $product,
			'custom_str2'      => $booking_id,
		];

		$data['signature'] = NGC_PayFast_Itn::generate_signature( $data, $this->passphrase );
		return $data;
	}

	/**
	 * WooCommerce API entry: echo + exit.
	 */
	public function handle_itn_request() {
		$posted = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result = $this->process_itn( is_array( $posted ) ? $posted : [] );
		status_header( (int) $result['status'] );
		echo (string) $result['body'];
		exit;
	}

	/**
	 * Process a signed PayFast ITN without exiting (for sandbox proofs).
	 *
	 * @param array<string, mixed> $posted ITN POST body.
	 * @return array{status:int,body:string,reason:string,order_id:int}
	 */
	public function process_itn( array $posted ) {
		if ( class_exists( 'NGC_Rate_Limiter' ) && ! NGC_Rate_Limiter::check( 'payfast_itn', 120, 60 ) ) {
			return $this->itn_result( 429, '', 'rate_limit', 0 );
		}

		$order_id = isset( $posted['m_payment_id'] ) ? absint( $posted['m_payment_id'] ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			return $this->itn_result( 404, '', 'order_not_found', $order_id );
		}

		$prior_pf = (string) $order->get_meta( '_ngc_payfast_pf_payment_id', true );
		$gate     = NGC_PayFast_Itn::validate_notification(
			$posted,
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
			if ( ! empty( $data['idempotent'] ) ) {
				if ( class_exists( 'NGC_Audit' ) ) {
					NGC_Audit::log( 'payfast_itn_replay', 'payment', $order_id, [ 'pf_payment_id' => $posted['pf_payment_id'] ?? '' ], 0 );
				}
				return $this->itn_result( 200, 'OK', 'replay', $order_id );
			}
			return $this->itn_result( (int) ( $data['status'] ?? 400 ), '', $gate->get_error_code(), $order_id, $gate->get_error_message() );
		}

		$status = sanitize_text_field( (string) ( $posted['payment_status'] ?? '' ) );
		$pf_id  = sanitize_text_field( (string) ( $posted['pf_payment_id'] ?? '' ) );

		if ( 'COMPLETE' === $status ) {
			if ( ! $order->is_paid() ) {
				$order->payment_complete( $pf_id );
			}
			$order->update_meta_data( '_ngc_payfast_pf_payment_id', $pf_id );
			$order->update_meta_data( '_ngc_payfast_itn_status', $status );
			$order->update_meta_data( '_ngc_payfast_amount_gross', sanitize_text_field( (string) ( $posted['amount_gross'] ?? $posted['amount'] ?? '' ) ) );
			$order->update_meta_data( '_ngc_payfast_sandbox', $this->sandbox ? 'yes' : 'no' );
			$order->update_meta_data( '_ngc_payfast_custom_str1', sanitize_text_field( (string) ( $posted['custom_str1'] ?? '' ) ) );
			$order->save();
			if ( $pf_id ) {
				NGC_PayFast_Itn::mark_processed( $pf_id, $order_id );
			}
			if ( class_exists( 'NGC_Payments' ) ) {
				NGC_Payments::settle_order( $order->get_id(), [ 'trusted_system' => true ] );
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

		return $this->itn_result( 200, 'OK', 'complete' === strtolower( $status ) ? 'complete' : sanitize_key( $status ), $order_id );
	}

	/**
	 * @param int    $status  HTTP status.
	 * @param string $body    Body.
	 * @param string $reason  Reason.
	 * @param int    $order_id Order.
	 * @param string $detail  Detail.
	 * @return array{status:int,body:string,reason:string,order_id:int}
	 */
	private function itn_result( $status, $body, $reason, $order_id, $detail = '' ) {
		if ( $status >= 400 && class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'payfast_itn_rejected',
				'payment',
				(int) $order_id,
				[
					'reason' => $reason,
					'detail' => $detail,
					'code'   => (int) $status,
				],
				0
			);
		}
		if ( $status >= 400 && class_exists( 'NGC_Fraud_Engine' ) && in_array( $reason, [ 'ngc_pf_signature', 'ngc_pf_amount', 'ngc_pf_merchant' ], true ) ) {
			NGC_Fraud_Engine::raise_signal(
				'payment_failure_spike',
				'payfast_itn',
				0,
				[ 'reason' => $reason, 'detail' => $detail ]
			);
		}
		return [
			'status'   => (int) $status,
			'body'     => '' !== $body ? $body : ( $status >= 400 ? $reason : 'OK' ),
			'reason'   => (string) $reason,
			'order_id' => (int) $order_id,
		];
	}
}
