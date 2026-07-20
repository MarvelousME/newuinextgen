<?php
/**
 * Bootstrap SmartHead-style configuration layer.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $BI_STORAGE;
$BI_STORAGE = [];

require_once BI_DIR . '/inc/config/storage.php';
require_once BI_DIR . '/inc/config/lists.php';
require_once BI_DIR . '/inc/config/color-schemes.php';
require_once BI_DIR . '/inc/config/visual-presets.php';
require_once BI_DIR . '/inc/config/options-schema.php';
require_once BI_DIR . '/inc/config/options-loader.php';
require_once BI_DIR . '/inc/config/registry-defaults.php';
require_once BI_DIR . '/inc/config/options-get.php';
require_once BI_DIR . '/inc/config/home-sections.php';
require_once BI_DIR . '/inc/config/theme-styles.php';
require_once BI_DIR . '/inc/config/customizer-bridge.php';
require_once BI_DIR . '/inc/config/override-metabox.php';
require_once BI_DIR . '/inc/config/post-meta.php';

add_filter( 'bi_filter_allow_override_options', 'bi_allow_tutor_override_options' );
function bi_allow_tutor_override_options( $post_types ) {
    $post_types[] = 'tutors';
    return $post_types;
}
