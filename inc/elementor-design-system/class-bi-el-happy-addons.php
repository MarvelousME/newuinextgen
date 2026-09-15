<?php
/**
 * Optional Happy Addons kit aliasing — never required.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Happy Addons compatibility.
 */
class BI_EL_Happy_Addons {

	/**
	 * Detect Happy Addons without fatal if absent.
	 */
	public static function is_active() {
		if ( defined( 'HAPPY_ADDONS_VERSION' ) ) {
			return true;
		}
		if ( function_exists( 'did_action' ) && did_action( 'happyaddons/loaded' ) ) {
			return true;
		}
		return class_exists( '\Happy_Addons\Elementor\Plugin', false );
	}

	/**
	 * Wire aliases after Elementor init.
	 */
	public static function init() {
		if ( ! self::is_active() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_aliases' ], 80 );
		add_action( 'elementor/editor/after_enqueue_styles', [ self::class, 'enqueue_aliases' ] );
	}

	/**
	 * Map Happy Addons CSS vars onto BeyondInfinity tokens.
	 */
	public static function enqueue_aliases() {
		$css = ':root{--ha-gd-primary:var(--ngt-primary);--ha-accent:var(--ngt-secondary);--ha-text:var(--ngt-text);}';
		if ( wp_style_is( 'bi-el-ds', 'enqueued' ) || wp_style_is( 'bi-el-ds', 'registered' ) ) {
			wp_add_inline_style( 'bi-el-ds', $css );
			return;
		}
		wp_register_style( 'bi-el-ha-bridge', false, [], BI_EL_DS_VERSION );
		wp_enqueue_style( 'bi-el-ha-bridge' );
		wp_add_inline_style( 'bi-el-ha-bridge', $css );
	}
}
