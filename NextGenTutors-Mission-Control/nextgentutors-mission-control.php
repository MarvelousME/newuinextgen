<?php
/**
 * Plugin Name:       NextGenTutors Mission Control
 * Plugin URI:        https://www.nextgentutors.co.za/
 * Description:       Master panel to configure, override, repair, and orchestrate the entire NextGen Tutors stack (theme, Companion, plugins, demo, AI).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            BeyondInfinity / GET ONLINE NOW
 * Text Domain:       nextgentutors-mission-control
 * Domain Path:       /languages
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NGTMC_VERSION', '1.0.0' );
define( 'NGTMC_PLUGIN_FILE', __FILE__ );
define( 'NGTMC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGTMC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NGTMC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once NGTMC_PLUGIN_DIR . 'includes/class-ngtmc-plugin.php';

NGTMC_Plugin::init();
