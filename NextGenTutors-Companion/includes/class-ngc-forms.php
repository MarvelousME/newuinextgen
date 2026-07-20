<?php
/**
 * Form validation assets for all ngc-form elements.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues polished client-side validation (JS/CSS).
 */
class NGC_Forms {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_enqueue' ], 20 );
	}

	/**
	 * Enqueue on pages likely to contain ngc forms.
	 */
	public static function maybe_enqueue() {
		if ( is_admin() ) {
			return;
		}
		self::enqueue_validation();
	}

	/**
	 * Register and enqueue validation bundle.
	 */
	public static function enqueue_validation() {
		wp_register_style( 'ngc-validation', NGC_PLUGIN_URL . 'assets/css/ngc-validation.css', [], NGC_VERSION );
		wp_register_script( 'ngc-validation', NGC_PLUGIN_URL . 'assets/js/ngc-validation.js', [], NGC_VERSION, true );
		wp_enqueue_style( 'ngc-validation' );
		wp_enqueue_script( 'ngc-validation' );
	}
}
