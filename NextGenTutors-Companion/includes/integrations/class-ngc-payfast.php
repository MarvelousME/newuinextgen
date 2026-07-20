<?php
/**
 * PayFast gateway bootstrap for WooCommerce.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers NGC PayFast gateway when WooCommerce is active.
 */
class NGC_PayFast {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'woocommerce_payment_gateways', [ __CLASS__, 'register_gateway' ] );
		add_action( 'woocommerce_api_ngc_payfast_itn', [ __CLASS__, 'handle_itn' ] );
	}

	/**
	 * @param string[] $gateways Gateway class names.
	 * @return string[]
	 */
	public static function register_gateway( $gateways ) {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return $gateways;
		}
		require_once NGC_PLUGIN_DIR . 'includes/integrations/class-ngc-payfast-gateway.php';
		$gateways[] = 'NGC_PayFast_Gateway';
		return $gateways;
	}

	/**
	 * ITN endpoint handler.
	 */
	public static function handle_itn() {
		if ( ! class_exists( 'NGC_PayFast_Gateway' ) ) {
			require_once NGC_PLUGIN_DIR . 'includes/integrations/class-ngc-payfast-gateway.php';
		}
		$gateway = new NGC_PayFast_Gateway();
		$gateway->handle_itn_request();
	}
}
