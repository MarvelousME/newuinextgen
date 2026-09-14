<?php
/**
 * Plugin Name: NextGen 3D Filmstrip
 * Description: Perspective 3D filmstrip deck for Subjects or Tutors — server-rendered from Companion/theme data.
 * Version: 1.0.0
 * Author: NextGen Tutors
 * Text Domain: nextgen-3d-filmstrip
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'NGTFS_VERSION', '1.0.0' );
define( 'NGTFS_FILE', __FILE__ );
define( 'NGTFS_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGTFS_URL', plugin_dir_url( __FILE__ ) );

require_once NGTFS_DIR . 'includes/class-ngtfs-data.php';
require_once NGTFS_DIR . 'includes/class-ngtfs-renderer.php';
require_once NGTFS_DIR . 'includes/class-ngtfs-assets.php';
require_once NGTFS_DIR . 'includes/class-ngtfs-shortcode.php';
require_once NGTFS_DIR . 'includes/class-ngtfs-widget.php';
require_once NGTFS_DIR . 'includes/class-ngtfs-admin.php';

/**
 * Bootstrap.
 */
final class NGTFS_Plugin {

	public static function init(): void {
		NGTFS_Assets::init();
		NGTFS_Shortcode::init();
		NGTFS_Admin::init();
		add_action( 'widgets_init', static function () {
			register_widget( NGTFS_Widget::class );
		} );
	}
}

NGTFS_Plugin::init();
