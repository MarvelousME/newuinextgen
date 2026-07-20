<?php
/**
 * Plugin Name:       NextGenTutors-Revamp-Html-Importer
 * Plugin URI:        https://beyondinfinity.co.za/
 * Description:       Import static HTML page content into WordPress pages with mapping, dry-run, rollback, and theme styling adoption.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            BeyondInfinity
 * Text Domain:       revamp-html-importer
 * License:           GPL-2.0-or-later
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RHI_VERSION', '1.0.1' );
define( 'RHI_PLUGIN_FILE', __FILE__ );
define( 'RHI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RHI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once RHI_PLUGIN_DIR . 'includes/class-rhi-logger.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-sanitizer.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-css-adoption.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-html-parser.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-scanner.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-source-resolver.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-page-matcher.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-media-importer.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-rollback.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-importer.php';
require_once RHI_PLUGIN_DIR . 'includes/class-rhi-plugin.php';

register_activation_hook( RHI_PLUGIN_FILE, [ 'RHI_Plugin', 'activate' ] );

RHI_Plugin::instance();
