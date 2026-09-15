<?php
/**
 * NextGen Elementor Design System — presentation layer on existing architecture.
 *
 * Elementor → NextGen widgets → BeyondInfinity markup → Companion data → NGT3D runtime.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BI_EL_DS_VERSION', '1.0.0' );
define( 'BI_EL_DS_DIR', __DIR__ );
define( 'BI_EL_DS_URI', BI_URI . '/inc/elementor-design-system' );

require_once BI_EL_DS_DIR . '/class-bi-el-tokens.php';
require_once BI_EL_DS_DIR . '/class-bi-el-presets.php';
require_once BI_EL_DS_DIR . '/class-bi-el-data.php';
require_once BI_EL_DS_DIR . '/class-bi-el-motion.php';
require_once BI_EL_DS_DIR . '/class-bi-el-happy-addons.php';
require_once BI_EL_DS_DIR . '/class-bi-el-renders.php';
require_once BI_EL_DS_DIR . '/class-bi-el-inventory.php';
require_once BI_EL_DS_DIR . '/class-bi-el-templates.php';

add_action( 'after_setup_theme', 'bi_el_ds_boot', 25 );
/**
 * Boot design system (does not replace existing NG UI widgets).
 */
function bi_el_ds_boot() {
	add_action( 'wp_enqueue_scripts', [ 'BI_EL_Tokens', 'enqueue' ], 70 );
	add_action( 'elementor/editor/after_enqueue_styles', [ 'BI_EL_Tokens', 'enqueue_editor' ] );
	add_action( 'elementor/preview/enqueue_styles', [ 'BI_EL_Tokens', 'enqueue' ] );
	add_action( 'elementor/frontend/after_enqueue_styles', [ 'BI_EL_Tokens', 'enqueue' ] );
	add_action( 'elementor/init', [ 'BI_EL_Happy_Addons', 'init' ] );
	add_action( 'elementor/init', [ 'BI_EL_Templates', 'register_source' ] );
	add_filter( 'ngt3d_page_rules', [ 'BI_EL_Motion', 'inject_page_rules' ], 20, 2 );
	add_filter( 'body_class', 'bi_el_ds_body_class' );
}

/**
 * Body class when design-system widgets are present.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function bi_el_ds_body_class( $classes ) {
	if ( BI_EL_Motion::page_has_widgets( bi_get_current_page_id() ) ) {
		$classes[] = 'bi-el-ds-page';
	}
	return $classes;
}

/**
 * Whether current request is Elementor editor canvas.
 */
function bi_el_ds_is_editor_mode() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}
	$plugin = \Elementor\Plugin::$instance;
	if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
		return true;
	}
	return false;
}

/**
 * Whether current request is Elementor preview iframe.
 */
function bi_el_ds_is_preview_mode() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}
	$plugin = \Elementor\Plugin::$instance;
	if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
		return true;
	}
	return false;
}
