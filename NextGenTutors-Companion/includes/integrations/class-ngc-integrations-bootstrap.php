<?php
/**
 * Local/Docker integration bootstrap — PayFast sandbox, CRM, GamiPress, LMS.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent configuration for third-party plugins in dev/Docker stacks.
 */
class NGC_Integrations_Bootstrap {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_integrate_runtime_ready', [ __CLASS__, 'maybe_auto_configure' ], 20 );
		// init (not plugins_loaded): translations may load and FluentCRM migrations must be done.
		add_action( 'init', [ __CLASS__, 'maybe_bootstrap_fluentcrm' ], 20 );
	}

	/**
	 * Late bootstrap when FluentCRM loads after Companion.
	 */
	public static function maybe_bootstrap_fluentcrm() {
		if ( ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			return;
		}
		try {
			$adapter = new NGC_Fluentcrm_Adapter();
			if ( $adapter->is_available() ) {
				$adapter->bootstrap_assets();
			}
		} catch ( Throwable $e ) {
			// Never take the site down over CRM bootstrap (e.g. FluentCRM tables not migrated yet).
			error_log( 'NGC FluentCRM bootstrap skipped: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Auto-configure once when demo seed is allowed (Docker local stack).
	 */
	public static function maybe_auto_configure() {
		if ( ! ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) ) {
			return;
		}
		if ( get_option( 'ngc_integrations_bootstrapped' ) ) {
			return;
		}
		self::configure_local_stack();
		update_option( 'ngc_integrations_bootstrapped', gmdate( 'c' ), false );
	}

	/**
	 * Configure all integration plugins for local E2E.
	 *
	 * @param bool $force Re-run even if previously bootstrapped.
	 * @return array<string, mixed>
	 */
	public static function configure_local_stack( $force = false ) {
		if ( $force ) {
			delete_option( 'ngc_integrations_bootstrapped' );
		}

		$results = [
			'payfast'         => self::configure_payfast(),
			'fluentcrm'       => self::configure_fluentcrm(),
			'gamipress'       => self::configure_gamipress(),
			'masterstudy'     => self::configure_masterstudy(),
			'amelia'          => self::configure_amelia(),
			'section_cms'     => self::configure_section_cms(),
			'forms'           => self::configure_forms(),
			'parent_checkout' => self::configure_parent_checkout(),
		];

		do_action( 'ngc_integrations_bootstrapped', $results );

		return $results;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_payfast() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return [ 'ok' => false, 'reason' => 'woocommerce_inactive' ];
		}

		$existing = get_option( 'woocommerce_ngc_payfast_settings', [] );
		if ( ! empty( $existing['merchant_id'] ) && 'yes' === ( $existing['enabled'] ?? '' ) ) {
			return [ 'ok' => true, 'status' => 'already_configured' ];
		}

		$sandbox = [
			'enabled'      => 'yes',
			'title'        => __( 'PayFast', 'nextgencompanion' ),
			'description'  => __( 'Pay securely with PayFast (sandbox).', 'nextgencompanion' ),
			'merchant_id'  => '10000100',
			'merchant_key' => '46f0cd694581a',
			'passphrase'   => 'payfast',
			'sandbox'      => 'yes',
		];

		update_option( 'woocommerce_ngc_payfast_settings', $sandbox, false );
		update_option( 'woocommerce_default_gateway', 'ngc_payfast', false );

		return [ 'ok' => true, 'status' => 'sandbox_configured' ];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_fluentcrm() {
		if ( ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			return [ 'ok' => false, 'reason' => 'adapter_missing' ];
		}

		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return [ 'ok' => false, 'reason' => 'fluentcrm_inactive' ];
		}

		$adapter->bootstrap_assets();
		$verify = $adapter->verify();

		return [
			'ok'     => ! empty( $verify['ok'] ),
			'status' => $verify['status'] ?? 'bootstrapped',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_gamipress() {
		if ( ! class_exists( 'NGC_Gamipress_Adapter' ) || ! NGC_Gamipress_Adapter::is_active() ) {
			return [ 'ok' => false, 'reason' => 'gamipress_inactive' ];
		}

		NGC_Gamipress_Adapter::ensure_point_types();
		$achievements = NGC_Gamipress_Adapter::ensure_achievements();

		return [
			'ok'           => true,
			'achievements' => $achievements,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_masterstudy() {
		if ( ! class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			return [ 'ok' => false, 'reason' => 'adapter_missing' ];
		}

		$adapter = new NGC_Masterstudy_Adapter();
		$verify  = $adapter->verify();

		return [
			'ok'     => ! empty( $verify['ok'] ),
			'status' => $verify['status'] ?? 'unknown',
		];
	}

	/**
	 * Sync Amelia API key from Amelia plugin settings when available.
	 *
	 * @return array<string, mixed>
	 */
	public static function configure_amelia() {
		if ( ! class_exists( 'NGC_Amelia_Bootstrap' ) ) {
			return [ 'ok' => false, 'reason' => 'bootstrap_missing' ];
		}

		if ( ! NGC_Amelia_Bootstrap::is_active() ) {
			return [ 'ok' => false, 'reason' => 'amelia_inactive' ];
		}

		$result = NGC_Amelia_Bootstrap::bootstrap( true );
		$verify = class_exists( 'NGC_Amelia_Adapter' ) ? ( new NGC_Amelia_Adapter() )->verify() : [];

		return [
			'ok'         => ! empty( $result['ok'] ) && ! empty( $verify['ok'] ),
			'bootstrap'  => $result,
			'status'     => $verify['status'] ?? ( $result['api']['status'] ?? 'bootstrapped' ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_forms() {
		if ( ! class_exists( 'NGC_Page_Forms_Registry' ) ) {
			return [ 'ok' => false, 'reason' => 'registry_missing' ];
		}

		$result = NGC_Page_Forms_Registry::ensure_production_forms( true );
		$verify = NGC_Page_Forms_Registry::verify();

		return [
			'ok'     => ! empty( $verify['ok'] ),
			'repair' => $result,
			'summary'=> $verify['summary'] ?? [],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_parent_checkout() {
		if ( ! class_exists( 'NGC_Parent_Checkout' ) ) {
			return [ 'ok' => false, 'reason' => 'checkout_missing' ];
		}

		$product_id = NGC_Parent_Checkout::ensure_product();

		return [
			'ok'         => $product_id > 0,
			'product_id' => $product_id,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function configure_section_cms() {
		if ( ! class_exists( 'NGC_Section_CMS' ) ) {
			return [ 'ok' => false, 'reason' => 'cms_missing' ];
		}

		NGC_Section_CMS::install_defaults();

		return [ 'ok' => true, 'sections' => count( NGC_Section_CMS::section_keys() ) ];
	}

	/**
	 * Health summary for diagnostics / wp ngc verify.
	 *
	 * @return array<string, mixed>
	 */
	public static function status() {
		$checks = [];

		if ( class_exists( 'NGC_PayFast_Gateway' ) && class_exists( 'WooCommerce' ) ) {
			$settings = get_option( 'woocommerce_ngc_payfast_settings', [] );
			$checks['payfast'] = [
				'ok'     => 'yes' === ( $settings['enabled'] ?? '' ) && ! empty( $settings['merchant_id'] ),
				'status' => ! empty( $settings['merchant_id'] ) ? 'configured' : 'not_configured',
			];
		}

		if ( class_exists( 'NGC_Gamipress_Adapter' ) ) {
			$checks['gamipress'] = [
				'ok'     => NGC_Gamipress_Adapter::is_active(),
				'status' => NGC_Gamipress_Adapter::is_active() ? 'active' : 'inactive',
			];
		}

		if ( class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			$ms = ( new NGC_Masterstudy_Adapter() )->verify();
			$checks['masterstudy'] = [
				'ok'     => ! empty( $ms['ok'] ),
				'status' => $ms['status'] ?? 'unknown',
			];
		}

		if ( class_exists( 'NGC_Amelia_Adapter' ) ) {
			$am = ( new NGC_Amelia_Adapter() )->verify();
			$checks['amelia'] = [
				'ok'     => ! empty( $am['ok'] ),
				'status' => $am['status'] ?? 'unknown',
			];
		}

		if ( class_exists( 'NGC_Section_CMS' ) ) {
			$seeded = (bool) NGC_Section_CMS::get_section_row( NGC_Section_CMS::PAGE_HOME, 'hero' );
			$checks['section_cms'] = [
				'ok'     => $seeded,
				'status' => $seeded ? 'seeded' : 'empty',
			];
		}

		return [
			'bootstrapped' => (bool) get_option( 'ngc_integrations_bootstrapped' ),
			'checks'       => $checks,
		];
	}
}
