<?php
/**
 * Navigation builder — materializes registry into WP admin menus.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds submenus under NEXT GEN TUTORS and removes legacy top-level menus.
 */
final class NGC_Admin_Navigation {

	/**
	 * Legacy top-level slugs to remove after consolidation.
	 *
	 * @return string[]
	 */
	public static function legacy_top_levels() {
		return [
			'ngtmc-mission-control',
			'ngc-operations',
			'ngc-platform',
			'ngc-workflows',
			'ngc-automation-studio',
			'ngt-hub',
			'ui-ux-pro-max',
			'ngtai',
			'ngt-ui-library',
		];
	}

	/**
	 * Late build: strip legacy parents, ensure all registry screens exist under shell.
	 */
	public static function build() {
		global $submenu;

		$parent = NGC_Admin_Shell::PARENT_SLUG;

		foreach ( self::legacy_top_levels() as $slug ) {
			remove_menu_page( $slug );
			if ( isset( $submenu[ $slug ] ) ) {
				unset( $submenu[ $slug ] );
			}
		}

		$existing = [];
		if ( ! empty( $submenu[ $parent ] ) && is_array( $submenu[ $parent ] ) ) {
			foreach ( $submenu[ $parent ] as $item ) {
				if ( ! empty( $item[2] ) ) {
					$existing[ (string) $item[2] ] = true;
				}
			}
		}

		foreach ( NGC_Admin_Registry::visible_screens() as $screen ) {
			$slug = (string) $screen['slug'];
			if ( $slug === $parent || isset( $existing[ $slug ] ) ) {
				continue;
			}
			if ( isset( $screen['wp_menu'] ) && false === $screen['wp_menu'] ) {
				continue;
			}
			if ( ! empty( $screen['placeholder'] ) ) {
				// Placeholders appear in custom nav tree; keep a WP entry for deep links.
			}
			$cb = $screen['callback'];
			if ( ! is_callable( $cb ) ) {
				if ( ! empty( $screen['placeholder'] ) ) {
					$cb = [ 'NGC_Admin_Layout', 'render_placeholder' ];
				} else {
					continue;
				}
			}
			$label = (string) $screen['menu_title'];
			$badge = ! empty( $screen['badge_key'] ) ? NGC_Admin_Registry::badge_count( $screen['badge_key'] ) : 0;
			if ( $badge > 0 ) {
				$label .= ' <span class="awaiting-mod update-plugins count-' . (int) $badge . '"><span class="pending-count">' . (int) $badge . '</span></span>';
			}
			add_submenu_page(
				$parent,
				(string) $screen['title'],
				$label,
				(string) $screen['capability'],
				$slug,
				$cb
			);
			$existing[ $slug ] = true;
		}

		if ( ! empty( $submenu[ $parent ] ) && is_array( $submenu[ $parent ] ) ) {
			usort(
				$submenu[ $parent ],
				static function ( $a, $b ) use ( $parent ) {
					$sa = (string) ( $a[2] ?? '' );
					$sb = (string) ( $b[2] ?? '' );
					if ( $sa === $parent ) {
						return -1;
					}
					if ( $sb === $parent ) {
						return 1;
					}
					$oa = (int) ( NGC_Admin_Registry::get_screen( $sa )['order'] ?? 500 );
					$ob = (int) ( NGC_Admin_Registry::get_screen( $sb )['order'] ?? 500 );
					if ( $oa === $ob ) {
						return strcasecmp( wp_strip_all_tags( (string) ( $a[0] ?? '' ) ), wp_strip_all_tags( (string) ( $b[0] ?? '' ) ) );
					}
					return $oa <=> $ob;
				}
			);
		}
	}

	/**
	 * Resolve parent for plugins still calling add_submenu_page.
	 *
	 * @param string $preferred Preferred legacy parent.
	 * @return string
	 */
	public static function resolve_parent( $preferred = '' ) {
		unset( $preferred );
		return NGC_Admin_Shell::PARENT_SLUG;
	}
}
