<?php
/**
 * Plugin Name:       NextGenTutors Beyond Measure
 * Plugin URI:        https://www.nextgentutors.co.za/
 * Description:       Control Plane admin OS — React SPA in wp-admin; WordPress remains auth, RBAC, REST, and persistence authority. Subsystems own business logic.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            BeyondInfinity / GET ONLINE NOW
 * Text Domain:       nextgentutors-beyond-measure
 * Domain Path:       /languages
 *
 * @package NextGenTutorsBeyondMeasure
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NGTBM_VERSION', '1.0.0' );
define( 'NGTBM_PLUGIN_FILE', __FILE__ );
define( 'NGTBM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGTBM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NGTBM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'NGTBM_REST_NAMESPACE', 'nextgentutors-control/v1' );

$ngtbm_autoload = NGTBM_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $ngtbm_autoload ) ) {
	require_once $ngtbm_autoload;
} else {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = 'NGTBM\\';
			if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
			$path     = NGTBM_PLUGIN_DIR . 'src/' . $relative . '.php';
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	);
}

register_activation_hook( __FILE__, [ \NGTBM\Infrastructure\WordPress\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \NGTBM\Infrastructure\WordPress\Activator::class, 'deactivate' ] );

add_action(
	'plugins_loaded',
	static function (): void {
		\NGTBM\Infrastructure\WordPress\Plugin::instance()->boot();
	},
	20
);
