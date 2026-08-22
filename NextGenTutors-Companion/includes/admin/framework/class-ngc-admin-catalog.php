<?php
/**
 * Default module/screen catalog — business-capability hierarchy.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the enterprise navigation taxonomy and known screens.
 */
final class NGC_Admin_Catalog {

	/**
	 * Cached screens.php payload. Loaded with require, not require_once.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private static $screen_definitions = null;

	/**
	 * Business categories (capability groups).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function categories() {
		return [
			'platform'       => [ 'label' => __( 'Platform', 'nextgencompanion' ), 'order' => 1, 'icon' => 'dashicons-dashboard' ],
			'education'      => [ 'label' => __( 'Education', 'nextgencompanion' ), 'order' => 10, 'icon' => 'dashicons-welcome-learn-more' ],
			'operations'     => [ 'label' => __( 'Operations', 'nextgencompanion' ), 'order' => 20, 'icon' => 'dashicons-calendar-alt' ],
			'commerce'       => [ 'label' => __( 'Commerce', 'nextgencompanion' ), 'order' => 30, 'icon' => 'dashicons-cart' ],
			'crm'            => [ 'label' => __( 'CRM', 'nextgencompanion' ), 'order' => 40, 'icon' => 'dashicons-groups' ],
			'ai'             => [ 'label' => __( 'AI Platform', 'nextgencompanion' ), 'order' => 50, 'icon' => 'dashicons-rest-api' ],
			'website'        => [ 'label' => __( 'Website', 'nextgencompanion' ), 'order' => 60, 'icon' => 'dashicons-admin-appearance' ],
			'reporting'      => [ 'label' => __( 'Reporting', 'nextgencompanion' ), 'order' => 70, 'icon' => 'dashicons-chart-area' ],
			'development'    => [ 'label' => __( 'Development', 'nextgencompanion' ), 'order' => 80, 'icon' => 'dashicons-editor-code' ],
			'administration' => [ 'label' => __( 'Administration', 'nextgencompanion' ), 'order' => 90, 'icon' => 'dashicons-admin-settings' ],
			// Legacy aliases mapped for existing screen category keys.
			'command'        => [ 'label' => __( 'Platform', 'nextgencompanion' ), 'order' => 1, 'icon' => 'dashicons-dashboard' ],
			'communications' => [ 'label' => __( 'CRM', 'nextgencompanion' ), 'order' => 40, 'icon' => 'dashicons-email' ],
			'automation'     => [ 'label' => __( 'Operations', 'nextgencompanion' ), 'order' => 21, 'icon' => 'dashicons-networking' ],
			'content'        => [ 'label' => __( 'Website', 'nextgencompanion' ), 'order' => 60, 'icon' => 'dashicons-admin-page' ],
			'infrastructure' => [ 'label' => __( 'Administration', 'nextgencompanion' ), 'order' => 91, 'icon' => 'dashicons-shield' ],
			'settings'       => [ 'label' => __( 'Administration', 'nextgencompanion' ), 'order' => 92, 'icon' => 'dashicons-admin-generic' ],
		];
	}

	/**
	 * Seed modules + screens into the registry.
	 */
	public static function register_defaults() {
		self::register_modules();
		self::register_screens();
		self::register_badges();
	}

	/**
	 * Modules.
	 */
	private static function register_modules() {
		$modules = [
			[ 'slug' => 'mission-control', 'label' => 'Mission Control', 'category' => 'command', 'order' => 1, 'icon' => 'dashicons-superhero' ],
			[ 'slug' => 'tutors', 'label' => 'Tutors', 'category' => 'education', 'order' => 10, 'icon' => 'dashicons-groups' ],
			[ 'slug' => 'students', 'label' => 'Students', 'category' => 'education', 'order' => 11, 'icon' => 'dashicons-id' ],
			[ 'slug' => 'parents', 'label' => 'Parents', 'category' => 'education', 'order' => 12, 'icon' => 'dashicons-admin-users' ],
			[ 'slug' => 'bookings', 'label' => 'Bookings', 'category' => 'operations', 'order' => 20, 'icon' => 'dashicons-calendar-alt' ],
			[ 'slug' => 'matching', 'label' => 'Matching', 'category' => 'operations', 'order' => 21, 'icon' => 'dashicons-randomize' ],
			[ 'slug' => 'payments', 'label' => 'Payments', 'category' => 'commerce', 'order' => 30, 'icon' => 'dashicons-money-alt' ],
			[ 'slug' => 'ai', 'label' => 'AI Platform', 'category' => 'ai', 'order' => 50, 'icon' => 'dashicons-rest-api' ],
			[ 'slug' => 'automation', 'label' => 'Automation', 'category' => 'automation', 'order' => 60, 'icon' => 'dashicons-networking' ],
			[ 'slug' => 'reports', 'label' => 'Reports', 'category' => 'reporting', 'order' => 70, 'icon' => 'dashicons-chart-area' ],
			[ 'slug' => 'content', 'label' => 'Content', 'category' => 'content', 'order' => 80, 'icon' => 'dashicons-admin-appearance' ],
			[ 'slug' => 'platform', 'label' => 'Platform', 'category' => 'platform', 'order' => 90, 'icon' => 'dashicons-database-view' ],
			[ 'slug' => 'system', 'label' => 'System', 'category' => 'infrastructure', 'order' => 100, 'icon' => 'dashicons-shield' ],
			[ 'slug' => 'plugins', 'label' => 'Plugins', 'category' => 'settings', 'order' => 105, 'icon' => 'dashicons-admin-plugins' ],
			[ 'slug' => 'settings', 'label' => 'Settings', 'category' => 'settings', 'order' => 110, 'icon' => 'dashicons-admin-settings' ],
		];
		foreach ( $modules as $m ) {
			NGC_Admin_Registry::register_module( $m );
		}
	}

	/**
	 * Known screens mapped to business modules.
	 */
	private static function register_screens() {
		foreach ( self::load_screen_definitions() as $screen ) {
			NGC_Admin_Registry::register_screen( $screen );
		}
	}

	/**
	 * @internal Characterization helper; not a stable plugin API.
	 * @return array<int, array<string, mixed>>
	 */
	public static function screen_definitions() {
		return self::load_screen_definitions();
	}

	/**
	 * screens.php must be loaded with require (not require_once): a second
	 * require_once returns true instead of the definitions array.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function load_screen_definitions() {
		if ( null === self::$screen_definitions ) {
			$loaded = require __DIR__ . '/screens.php';
			self::$screen_definitions = is_array( $loaded ) ? $loaded : [];
		}
		return self::$screen_definitions;
	}

	/**
	 * Badge providers.
	 */
	private static function register_badges() {
		NGC_Admin_Registry::register_badge_provider(
			'tutor_applications',
			static function () {
				if ( ! class_exists( 'NGC_Marketplace' ) ) {
					return 0;
				}
				if ( method_exists( 'NGC_Marketplace', 'count_pending_applications' ) ) {
					return (int) NGC_Marketplace::count_pending_applications();
				}
				return 0;
			}
		);
		NGC_Admin_Registry::register_badge_provider(
			'errors',
			static function () {
				if ( class_exists( 'NGC_Exception_Log' ) && method_exists( 'NGC_Exception_Log', 'open_count' ) ) {
					return (int) NGC_Exception_Log::open_count();
				}
				return 0;
			}
		);
		NGC_Admin_Registry::register_badge_provider(
			'ai_approvals',
			static function () {
				if ( class_exists( 'NGTAI_Approvals' ) && method_exists( 'NGTAI_Approvals', 'pending_count' ) ) {
					return (int) NGTAI_Approvals::pending_count();
				}
				return 0;
			}
		);
	}
}
