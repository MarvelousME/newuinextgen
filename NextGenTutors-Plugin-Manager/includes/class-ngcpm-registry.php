<?php
/**
 * Configurable dependency registry.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin dependency definitions.
 */
class NGCPM_Registry {

	const SOURCE_WPORG    = 'wordpress.org';
	const SOURCE_LOCAL    = 'local_zip';
	const SOURCE_REMOTE   = 'remote_zip';
	const SOURCE_MANUAL   = 'premium/manual';

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_all() {
		$registry = self::defaults();
		$custom   = get_option( NGCPM_Settings::OPTION_REGISTRY, [] );
		if ( is_array( $custom ) && ! empty( $custom ) ) {
			foreach ( $custom as $slug => $row ) {
				if ( isset( $registry[ $slug ] ) && is_array( $row ) ) {
					$registry[ $slug ] = array_merge( $registry[ $slug ], $row );
				}
			}
		}
		return apply_filters( 'ngcpm_registry', $registry );
	}

	/**
	 * @param string $slug Plugin slug.
	 * @return array<string, mixed>|null
	 */
	public static function get( $slug ) {
		$all = self::get_all();
		return $all[ $slug ] ?? null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function defaults() {
		$packages = trailingslashit( WP_CONTENT_DIR ) . 'ngcpm-packages/';

		return [
			'woocommerce' => [
				'name'             => 'WooCommerce',
				'slug'             => 'woocommerce',
				'main_file'        => 'woocommerce/woocommerce.php',
				'required_version' => '8.0.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 10,
				'setup_url'        => admin_url( 'admin.php?page=wc-settings' ),
				'notes'            => __( 'Core commerce engine for bookings and PayFast.', 'nextgentutors-plugin-manager' ),
			],
			'elementor' => [
				'name'             => 'Elementor',
				'slug'             => 'elementor',
				'main_file'        => 'elementor/elementor.php',
				'required_version' => '3.20.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 20,
				'setup_url'        => admin_url( 'admin.php?page=elementor' ),
				'notes'            => __( 'Page builder used by BeyondInfinity layouts.', 'nextgentutors-plugin-manager' ),
			],
			'fluent-crm' => [
				'name'             => 'FluentCRM',
				'slug'             => 'fluent-crm',
				'main_file'        => 'fluent-crm/fluent-crm.php',
				'required_version' => '2.8.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 30,
				'setup_url'        => admin_url( 'admin.php?page=fluentcrm-admin' ),
				'notes'            => __( 'CRM automation for tutor/parent workflows.', 'nextgentutors-plugin-manager' ),
			],
			'fluent-smtp' => [
				'name'             => 'FluentSMTP',
				'slug'             => 'fluent-smtp',
				'main_file'        => 'fluent-smtp/fluent-smtp.php',
				'required_version' => '2.2.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 40,
				'setup_url'        => admin_url( 'admin.php?page=fluent-smtp' ),
				'notes'            => __( 'Transactional email delivery.', 'nextgentutors-plugin-manager' ),
			],
			'masterstudy-lms' => [
				'name'             => 'MasterStudy LMS',
				'slug'             => 'masterstudy-lms-learning-management-system',
				'wporg_slug'       => 'masterstudy-lms-learning-management-system',
				'main_file'        => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
				'required_version' => '3.0.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 50,
				'setup_url'        => admin_url( 'admin.php?page=stm-lms-settings' ),
				'notes'            => __( 'LMS instructor/student roles.', 'nextgentutors-plugin-manager' ),
			],
			'gamipress' => [
				'name'             => 'GamiPress',
				'slug'             => 'gamipress',
				'main_file'        => 'gamipress/gamipress.php',
				'required_version' => '6.0.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 60,
				'setup_url'        => admin_url( 'admin.php?page=gamipress_settings' ),
				'notes'            => __( 'Gamification layer for NextGen Companion.', 'nextgentutors-plugin-manager' ),
			],
			'automatorwp' => [
				'name'             => 'AutomatorWP',
				'slug'             => 'automatorwp',
				'main_file'        => 'automatorwp/automatorwp.php',
				'required_version' => '4.0.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 70,
				'setup_url'        => admin_url( 'admin.php?page=automatorwp' ),
				'notes'            => __( 'Cross-plugin automation recipes.', 'nextgentutors-plugin-manager' ),
			],
			'user-role-editor' => [
				'name'             => 'User Role Editor',
				'slug'             => 'user-role-editor',
				'main_file'        => 'user-role-editor/user-role-editor.php',
				'required_version' => '4.60.0',
				'source_type'      => self::SOURCE_WPORG,
				'install_url'      => '',
				'package_path'     => '',
				'required'         => true,
				'priority'         => 80,
				'setup_url'        => admin_url( 'options-general.php?page=settings-user-role-editor.php' ),
				'notes'            => __( 'Role/capability management for tutor stacks.', 'nextgentutors-plugin-manager' ),
			],
			'ameliabooking' => [
				'name'             => 'Amelia Booking',
				'slug'             => 'ameliabooking',
				'main_file'        => 'ameliabooking/ameliabooking.php',
				'required_version' => '7.0.0',
				'source_type'      => self::SOURCE_MANUAL,
				'install_url'      => '',
				'package_path'     => $packages . 'ameliabooking.zip',
				'required'         => true,
				'priority'         => 90,
				'setup_url'        => admin_url( 'admin.php?page=wpamelia' ),
				'notes'            => __( 'Premium booking — upload zip to wp-content/ngcpm-packages/ameliabooking.zip or install manually.', 'nextgentutors-plugin-manager' ),
			],
			'payfast-payment-gateway' => [
				'name'             => 'PayFast WooCommerce Gateway',
				'slug'             => 'woocommerce-payfast-gateway',
				'main_file'        => 'woocommerce-payfast-gateway/woocommerce-payfast-gateway.php',
				'required_version' => '1.4.0',
				'source_type'      => self::SOURCE_MANUAL,
				'install_url'      => '',
				'package_path'     => $packages . 'woocommerce-payfast-gateway.zip',
				'required'         => true,
				'priority'         => 100,
				'setup_url'        => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=payfast' ),
				'notes'            => __( 'Required for ZA payments. Download from PayFast/WooCommerce, then place woocommerce-payfast-gateway.zip in offline-packages.', 'nextgentutors-plugin-manager' ),
			],
			'js_composer' => [
				'name'             => 'WPBakery Page Builder',
				'slug'             => 'js_composer',
				'main_file'        => 'js_composer/js_composer.php',
				'required_version' => '7.0.0',
				'source_type'      => self::SOURCE_MANUAL,
				'install_url'      => '',
				'package_path'     => $packages . 'js_composer.zip',
				'required'         => false,
				'priority'         => 110,
				'setup_url'        => admin_url( 'admin.php?page=vc-general' ),
				'notes'            => __( 'Optional legacy builder — manual install required.', 'nextgentutors-plugin-manager' ),
			],
			'ultimate-elementor' => [
				'name'             => 'Ultimate Addons for Elementor',
				'slug'             => 'ultimate-elementor',
				'main_file'        => 'ultimate-elementor/ultimate-elementor.php',
				'required_version' => '1.36.0',
				'source_type'      => self::SOURCE_MANUAL,
				'install_url'      => '',
				'package_path'     => $packages . 'ultimate-elementor.zip',
				'required'         => false,
				'priority'         => 120,
				'setup_url'        => admin_url( 'admin.php?page=uae' ),
				'notes'            => __( 'Optional premium addon — not required for go-live. Purchase from Brainstorm Force, then place ultimate-elementor.zip in offline-packages.', 'nextgentutors-plugin-manager' ),
			],
		];
	}

	/**
	 * Sorted by priority for batch install.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sorted() {
		$all = self::get_all();
		uasort(
			$all,
			static function ( $a, $b ) {
				return (int) ( $a['priority'] ?? 999 ) <=> (int) ( $b['priority'] ?? 999 );
			}
		);
		return $all;
	}

	/**
	 * Parent dependency slugs for graph edges (core = WordPress).
	 *
	 * @param string $slug Registry key.
	 * @return string[]
	 */
	public static function depends_on( $slug ) {
		$map = [
			'woocommerce'             => [ 'core' ],
			'payfast-payment-gateway' => [ 'woocommerce' ],
			'ameliabooking'           => [ 'woocommerce' ],
			'ultimate-elementor'    => [ 'elementor' ],
		];
		return $map[ $slug ] ?? [ 'core' ];
	}

	/**
	 * Dependency edges for graph visualization.
	 *
	 * @return array<int, array{from: string, to: string}>
	 */
	public static function dependency_edges() {
		$edges = [];
		foreach ( self::sorted() as $slug => $def ) {
			foreach ( self::depends_on( $slug ) as $parent ) {
				$edges[] = [ 'from' => $parent, 'to' => $slug ];
			}
		}
		return $edges;
	}

	/**
	 * Ordered slugs for dashboard system map pipeline.
	 *
	 * @return string[]
	 */
	public static function pipeline_slugs() {
		return [
			'core',
			'woocommerce',
			'payfast-payment-gateway',
			'ameliabooking',
			'masterstudy-lms',
			'fluent-crm',
			'fluent-smtp',
			'automatorwp',
			'gamipress',
		];
	}

	/**
	 * Whitelisted remote zip URLs from settings.
	 *
	 * @return array<string, string> slug => url
	 */
	public static function remote_whitelist() {
		$urls = get_option( NGCPM_Settings::OPTION_REMOTE_ZIPS, [] );
		return is_array( $urls ) ? $urls : [];
	}
}
