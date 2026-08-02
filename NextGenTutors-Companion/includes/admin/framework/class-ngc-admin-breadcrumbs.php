<?php
/**
 * Context breadcrumbs for unified admin screens.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders breadcrumbs above NGT admin content.
 */
final class NGC_Admin_Breadcrumbs {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'in_admin_header', [ __CLASS__, 'render' ], 20 );
	}

	/**
	 * Output breadcrumb trail.
	 */
	public static function render() {
		if ( ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		$page    = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$screen  = NGC_Admin_Registry::get_screen( $page );
		$cats    = NGC_Admin_Catalog::categories();
		$cat_key = (string) ( $screen['category'] ?? 'operations' );
		$cat     = $cats[ $cat_key ]['label'] ?? ucfirst( $cat_key );
		$module  = NGC_Admin_Registry::get_module( (string) ( $screen['module'] ?? '' ) );
		$title   = $screen['title'] ?? ( $page ?: __( 'Administration', 'nextgencompanion' ) );
		$brand   = class_exists( 'NGC_Platform_Version' ) ? NGC_Platform_Version::display_title() : NGC_Admin_Shell::menu_title();

		echo '<div class="ngt-admin-chrome" role="navigation" aria-label="' . esc_attr__( 'NextGen breadcrumb', 'nextgencompanion' ) . '">';
		echo '<nav class="ngt-admin-breadcrumb">';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . NGC_Admin_Shell::PARENT_SLUG ) ) . '">' . esc_html( $brand ) . '</a>';
		echo ' <span aria-hidden="true">›</span> ';
		echo '<span>' . esc_html( (string) $cat ) . '</span>';
		if ( $module ) {
			echo ' <span aria-hidden="true">›</span> ';
			echo '<span>' . esc_html( (string) $module['label'] ) . '</span>';
		}
		// Unlimited drill-down via nav_parent chain.
		$chain = [];
		$cursor = $screen;
		while ( $cursor && ! empty( $cursor['nav_parent'] ) ) {
			$parent = NGC_Admin_Registry::get_screen( (string) $cursor['nav_parent'] );
			if ( ! $parent ) {
				break;
			}
			array_unshift( $chain, $parent );
			$cursor = $parent;
		}
		foreach ( $chain as $ancestor ) {
			echo ' <span aria-hidden="true">›</span> ';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . $ancestor['slug'] ) ) . '">' . esc_html( (string) $ancestor['title'] ) . '</a>';
		}
		echo ' <span aria-hidden="true">›</span> ';
		echo '<span class="ngt-admin-breadcrumb__current" aria-current="page">' . esc_html( (string) $title ) . '</span>';
		echo '</nav>';
		echo '<div class="ngt-admin-chrome__actions">';
		echo '<label class="screen-reader-text" for="ngt-admin-search">' . esc_html__( 'Search administration', 'nextgencompanion' ) . '</label>';
		echo '<input type="search" id="ngt-admin-search" class="ngt-admin-search" placeholder="' . esc_attr__( 'Search screens, settings, modules…', 'nextgencompanion' ) . '" autocomplete="off" data-testid="ngt-admin-search" />';
		echo '<div id="ngt-admin-search-results" class="ngt-admin-search-results" hidden role="listbox"></div>';
		echo '</div></div>';
	}
}
