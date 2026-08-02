<?php
/**
 * Custom capability sidebar UI mount (alongside WP admin menu).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the enterprise nav panel and enqueues DnD manager.
 */
final class NGC_Admin_Nav_UI {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'in_admin_header', [ __CLASS__, 'render_panel' ], 15 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	/**
	 * Enqueue nav manager.
	 */
	public static function assets() {
		if ( ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		wp_enqueue_script(
			'ngt-admin-nav-manager',
			NGC_PLUGIN_URL . 'assets/js/admin-nav-manager.js',
			[ 'ngt-admin-shell' ],
			NGC_VERSION,
			true
		);
		wp_localize_script(
			'ngt-admin-nav-manager',
			'ngtAdminNav',
			[
				'restRoot' => esc_url_raw( rest_url( 'ngc/v1/admin' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'tree'     => NGC_Admin_Nav_Tree::build(),
				'i18n'     => [
					'favorites' => __( 'Favorites', 'nextgencompanion' ),
					'manage'    => __( 'Customize navigation', 'nextgencompanion' ),
					'coming'    => __( 'Coming soon', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * Mount point for sidebar tree.
	 */
	public static function render_panel() {
		if ( ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		echo '<aside id="ngt-admin-nav" class="ngt-admin-nav" data-testid="ngt-admin-nav" aria-label="' . esc_attr__( 'NEXT GEN TUTORS navigation', 'nextgencompanion' ) . '">';
		echo '<div class="ngt-admin-nav__brand">' . esc_html( NGC_Platform_Version::display_title() ) . '</div>';
		echo '<div id="ngt-admin-nav-tree" class="ngt-admin-nav__tree"></div>';
		echo '<div class="ngt-admin-nav__footer">';
		echo '<button type="button" class="button-link" id="ngt-admin-nav-edit" data-testid="ngt-admin-nav-edit">' . esc_html__( 'Customize', 'nextgencompanion' ) . '</button>';
		echo '<button type="button" class="button-link" id="ngt-admin-nav-undo">' . esc_html__( 'Undo', 'nextgencompanion' ) . '</button>';
		echo '<button type="button" class="button-link" id="ngt-admin-nav-reset">' . esc_html__( 'Reset', 'nextgencompanion' ) . '</button>';
		echo '</div></aside>';
	}
}
