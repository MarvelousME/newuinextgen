<?php
/**
 * Grouped primary navigation with dropdowns.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu groups: label => [ slug => path ].
 *
 * @return array<string, array<string, string>>
 */
function bi_nav_menu_groups() {
	return [
		__( 'Discover', 'beyondinfinity' ) => [
			__( 'Find a Tutor', 'beyondinfinity' ) => '/find-a-tutor',
			__( 'Pricing', 'beyondinfinity' )      => '/pricing',
			__( 'Guarantee', 'beyondinfinity' )    => '/guarantee',
			__( 'Blog', 'beyondinfinity' )         => '/blog',
		],
		__( 'Trust', 'beyondinfinity' ) => [
			__( 'Tutor Vetting', 'beyondinfinity' )  => '/tutor-vetting',
			__( 'Safety Guide', 'beyondinfinity' )   => '/safety-guide',
			__( 'Child Safety', 'beyondinfinity' )   => '/child-safety',
			__( 'About', 'beyondinfinity' )          => '/about',
		],
		__( 'For Tutors', 'beyondinfinity' ) => [
			__( 'Become a Tutor', 'beyondinfinity' ) => '/become-a-tutor',
		],
		__( 'Help', 'beyondinfinity' ) => [
			__( 'Contact', 'beyondinfinity' ) => '/contact',
			__( 'Support', 'beyondinfinity' ) => '/support',
		],
		__( 'Account', 'beyondinfinity' ) => [
			__( 'Register', 'beyondinfinity' )        => '/register',
			__( 'Log In', 'beyondinfinity' )          => '/login',
			__( 'Parent Dashboard', 'beyondinfinity' ) => '/parent-dashboard',
			__( 'Student Dashboard', 'beyondinfinity' ) => '/student-dashboard',
		],
	];
}

/**
 * Ensure primary nav menu exists and is assigned before render.
 */
function bi_ensure_primary_nav_menu() {
	$locations  = get_theme_mod( 'nav_menu_locations', [] );
	$primary_id = (int) ( $locations['primary'] ?? 0 );
	$needs_sync = ! $primary_id;

	if ( $primary_id ) {
		$items = wp_get_nav_menu_items( $primary_id );
		$needs_sync = ! is_array( $items ) || count( $items ) < 1;
	}

	if ( $needs_sync ) {
		bi_sync_launch_nav( false );
	}
}

/**
 * Render primary navigation (WP menu or grouped fallback).
 */
function bi_render_primary_nav_menu() {
	bi_ensure_primary_nav_menu();

	$locations = get_theme_mod( 'nav_menu_locations', [] );
	$menu_id   = (int) ( $locations['primary'] ?? 0 );
	$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : false;

	if ( ! is_array( $items ) || count( $items ) < 1 ) {
		bi_nav_fallback_menu();
		return;
	}

	wp_nav_menu(
		[
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'ngt-nav__menu',
			'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			'fallback_cb'    => 'bi_nav_fallback_menu',
		]
	);
}

/**
 * Fallback grouped nav when no WP menu assigned.
 */
function bi_nav_fallback_menu() {
	$groups = bi_nav_menu_groups();
	echo '<ul class="ngt-nav__menu ngt-nav__menu--grouped">';
	foreach ( $groups as $label => $items ) {
		if ( count( $items ) === 1 ) {
			$title = array_key_first( $items );
			$path  = $items[ $title ];
			echo '<li class="menu-item"><a class="ngt-nav__link" href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $title ) . '</a></li>';
			continue;
		}
		echo '<li class="menu-item menu-item-has-children ngt-nav__dropdown">';
		echo '<button type="button" class="ngt-nav__link ngt-nav__dropdown-trigger" aria-expanded="false" aria-haspopup="true">';
		echo esc_html( $label );
		echo '<svg class="ngt-nav__caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
		echo '</button>';
		echo '<ul class="ngt-nav__submenu sub-menu">';
		foreach ( $items as $title => $path ) {
			echo '<li class="menu-item"><a class="ngt-nav__sublink" href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $title ) . '</a></li>';
		}
		echo '</ul></li>';
	}
	echo '</ul>';
}

/**
 * Add dropdown classes to wp_nav_menu output.
 *
 * @param string[] $classes CSS classes.
 * @param WP_Post  $item    Menu item.
 * @return string[]
 */
function bi_nav_menu_item_classes( $classes, $item ) {
	if ( in_array( 'menu-item-has-children', $classes, true ) ) {
		$classes[] = 'ngt-nav__dropdown';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'bi_nav_menu_item_classes', 10, 2 );

/**
 * Menu groups for header navigation (slug lists).
 *
 * @return array<string, string[]>
 */
function bi_nav_page_groups() {
	return [
		__( 'Discover', 'beyondinfinity' ) => [
			'find-a-tutor', 'pricing', 'guarantee', 'blog',
		],
		__( 'Trust', 'beyondinfinity' ) => [
			'tutor-vetting', 'safety-guide', 'child-safety', 'about',
		],
		__( 'For Tutors', 'beyondinfinity' ) => [
			'become-a-tutor',
		],
		__( 'Help', 'beyondinfinity' ) => [
			'contact', 'support',
		],
		__( 'Account', 'beyondinfinity' ) => [
			'register', 'login', 'parent-checkout', 'thank-you',
		],
		__( 'Dashboards', 'beyondinfinity' ) => [
			'parent-dashboard', 'student-dashboard', 'tutor-dashboard', 'admin-dashboard', 'onboarding',
		],
		__( 'Legal', 'beyondinfinity' ) => [
			'privacy-policy', 'terms',
		],
		__( 'Admin', 'beyondinfinity' ) => [
			'wordpress-setup',
		],
	];
}

/**
 * Create / refresh grouped primary menu in WP admin.
 *
 * @param bool $force Rebuild even when menu already populated.
 */
function bi_sync_grouped_primary_menu( $force = false ) {
	$menu_name = 'NextGen Primary Grouped';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : 0;
	if ( ! $menu_id ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}
	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return 0;
	}

	$existing = wp_get_nav_menu_items( $menu_id );
	if ( ! $force && is_array( $existing ) && count( $existing ) > 3 ) {
		$locations = get_theme_mod( 'nav_menu_locations', [] );
		if ( empty( $locations['primary'] ) ) {
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
		return $menu_id;
	}

	bi_clear_nav_menu_items( $menu_id );

	foreach ( bi_nav_page_groups() as $group_label => $slugs ) {
		$parent_id = 0;
		$page_items = [];
		foreach ( $slugs as $slug ) {
			$page = function_exists( 'bi_find_page_by_slug' ) ? bi_find_page_by_slug( $slug ) : get_page_by_path( $slug );
			if ( ! $page ) {
				continue;
			}
			$page_items[] = $page;
		}
		if ( empty( $page_items ) ) {
			continue;
		}
		if ( count( $page_items ) > 1 ) {
			$parent_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				[
					'menu-item-title'  => $group_label,
					'menu-item-url'    => '#',
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				]
			);
		}
		foreach ( $page_items as $page ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				[
					'menu-item-title'     => $page->post_title,
					'menu-item-object-id' => $page->ID,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => count( $page_items ) > 1 ? $parent_id : 0,
				]
			);
		}
	}

	$locations = get_theme_mod( 'nav_menu_locations', [] );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return $menu_id;
}

/**
 * Flat menu containing every launch page from page-map.json.
 *
 * @param bool $force Rebuild menu items.
 * @return int Menu term ID or 0.
 */
function bi_sync_all_pages_menu( $force = false ) {
	$menu_name = 'NextGen All Pages';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : 0;
	if ( ! $menu_id ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}
	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return 0;
	}

	$existing = wp_get_nav_menu_items( $menu_id );
	$expected = function_exists( 'bi_load_page_map' ) ? bi_load_page_map() : [];
	if ( is_wp_error( $expected ) ) {
		$expected = [];
	}
	$expected_count = is_array( $expected ) ? count( $expected ) : 0;

	if ( ! $force && is_array( $existing ) && count( $existing ) >= $expected_count && $expected_count > 0 ) {
		return $menu_id;
	}

	bi_clear_nav_menu_items( $menu_id );

	foreach ( (array) $expected as $entry ) {
		$slug = $entry['slug'] ?? '';
		if ( ! $slug ) {
			continue;
		}
		$page = function_exists( 'bi_find_page_by_slug' ) ? bi_find_page_by_slug( $slug ) : get_page_by_path( $slug );
		if ( ! $page ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-title'     => $page->post_title,
				'menu-item-object-id' => $page->ID,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]
		);
	}

	return $menu_id;
}

/**
 * Footer menu with legal and utility pages.
 *
 * @param bool $force Rebuild menu items.
 * @return int Menu term ID or 0.
 */
function bi_sync_footer_menu( $force = false ) {
	$menu_name = 'NextGen Footer';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : 0;
	if ( ! $menu_id ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}
	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return 0;
	}

	$slugs = [ 'privacy-policy', 'terms', 'child-safety', 'safety-guide', 'guarantee', 'contact', 'support' ];
	$existing = wp_get_nav_menu_items( $menu_id );

	if ( ! $force && is_array( $existing ) && count( $existing ) >= count( $slugs ) ) {
		$locations = get_theme_mod( 'nav_menu_locations', [] );
		$locations['footer-1'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		return $menu_id;
	}

	bi_clear_nav_menu_items( $menu_id );

	foreach ( $slugs as $slug ) {
		$page = function_exists( 'bi_find_page_by_slug' ) ? bi_find_page_by_slug( $slug ) : get_page_by_path( $slug );
		if ( ! $page ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-title'     => $page->post_title,
				'menu-item-object-id' => $page->ID,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]
		);
	}

	$locations = get_theme_mod( 'nav_menu_locations', [] );
	$locations['footer-1'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return $menu_id;
}

/**
 * Sync all launch navigation menus.
 *
 * @param bool $force Rebuild all menus.
 * @return array<string, int>
 */
function bi_sync_launch_nav( $force = false ) {
	return [
		'primary'   => (int) bi_sync_grouped_primary_menu( $force ),
		'all_pages' => (int) bi_sync_all_pages_menu( $force ),
		'footer'    => (int) bi_sync_footer_menu( $force ),
	];
}

/**
 * Delete all items from a nav menu.
 *
 * @param int $menu_id Menu term ID.
 */
function bi_clear_nav_menu_items( $menu_id ) {
	$existing = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $existing ) ) {
		return;
	}
	foreach ( $existing as $item ) {
		wp_delete_post( (int) $item->ID, true );
	}
}

add_action( 'after_switch_theme', function () {
	bi_sync_launch_nav( true );
} );

add_action(
	'init',
	function () {
		if ( is_admin() ) {
			return;
		}
		bi_ensure_primary_nav_menu();
	},
	20
);
