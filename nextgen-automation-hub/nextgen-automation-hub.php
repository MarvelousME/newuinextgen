<?php
/**
 * Plugin Name: NextGen Automation Hub
 * Description: Automates NextGen Tutors roles, dashboards, RTM, workflows, matching, payouts, and gamification.
 * Version: 2.0.0
 * Author: Marvin Saunders / Get Online NOW
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: nextgen-automation-hub
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NGT_HUB_VERSION', '2.0.0' );
define( 'NGT_HUB_DB_VERSION', '2.0.0' );
define( 'NGT_HUB_FILE', __FILE__ );
define( 'NGT_HUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGT_HUB_URL', plugin_dir_url( __FILE__ ) );

require_once NGT_HUB_DIR . 'includes/class-ngt-hub.php';

NGT_Hub::init();

/** Backward-compatible facade for integrations referencing NGT_Automation_Hub. */
if ( ! class_exists( 'NGT_Automation_Hub', false ) ) {
	class NGT_Automation_Hub {
		public static function init(): void {
			NGT_Hub::init();
		}

		public static function fire_event( string $event_key, string $source = 'system', int $user_id = 0, int $object_id = 0, array $payload = [] ): void {
			NGT_Hub::fire_event( $event_key, $source, $user_id, $object_id, $payload );
		}

		public static function get_workflows(): array {
			return NGT_Hub::get_workflows();
		}
	}
}
