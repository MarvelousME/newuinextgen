<?php
/**
 * Primary navigation — reference-theme menu (verbatim labels/order).
 *
 * Source: nextgen-tutors-theme assets/js/chrome.js NAV_LINKS
 * Exception: "Get Started" CTA intentionally omitted.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical public nav structure (reference order, minus Get Started).
 *
 * @return array<int, array<string, mixed>>
 */
function bi_nav_menu_structure() {
	return [
		[
			'title' => 'Find a Tutor',
			'slug'  => 'find-a-tutor',
		],
		[
			'title' => 'Pricing',
			'slug'  => 'pricing',
		],
		[
			'title' => 'Become a Tutor',
			'slug'  => 'become-a-tutor',
		],
		[
			'title' => 'About',
			'slug'  => 'about',
		],
		[
			'title' => 'Contact',
			'slug'  => 'contact',
		],
		[
			'title'    => 'Compliance',
			'children' => [
				[
					'title' => 'Safety Guide',
					'slug'  => 'safety-guide',
				],
				[
					'title' => 'Terms & Conditions',
					'slug'  => 'terms',
				],
				[
					'title' => 'Privacy Policy',
					'slug'  => 'privacy-policy',
				],
				[
					'title' => 'POPIA Compliance',
					'slug'  => 'child-safety',
				],
				[
					'title' => 'Tutor Vetting',
					'slug'  => 'tutor-vetting',
				],
				[
					'title' => '1st Lesson Guarantee',
					'slug'  => 'guarantee',
				],
			],
		],
		[
			'title' => 'Blog',
			'slug'  => 'blog',
		],
	];
}

/**
 * Legacy grouped shape for callers still expecting label => [ title => path ].
 *
 * @return array<string, array<string, string>>
 */
function bi_nav_menu_groups() {
	$groups = [];
	foreach ( bi_nav_menu_structure() as $item ) {
		if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
			$children = [];
			foreach ( $item['children'] as $child ) {
				$children[ $child['title'] ] = '/' . ltrim( (string) $child['slug'], '/' );
			}
			$groups[ $item['title'] ] = $children;
			continue;
		}
		$groups[ $item['title'] ] = [
			$item['title'] => '/' . ltrim( (string) $item['slug'], '/' ),
		];
	}
	return $groups;
}

/**
 * Bump when public menu structure changes so live sites rebuild once.
 */
function bi_nav_public_schema_version() {
	return '2026-08-14-nav-footer-ssot-v1';
}

/**
 * Ensure primary nav menu exists and is assigned before render.
 */
function bi_ensure_primary_nav_menu() {
	$schema     = bi_nav_public_schema_version();
	$stored     = (string) get_option( 'bi_nav_public_schema', '' );
	$force      = ( $stored !== $schema );
	$locations  = get_theme_mod( 'nav_menu_locations', [] );
	$primary_id = (int) ( $locations['primary'] ?? 0 );
	$needs_sync = $force || ! $primary_id;

	if ( ! $needs_sync && $primary_id ) {
		$items      = wp_get_nav_menu_items( $primary_id );
		$needs_sync = ! is_array( $items ) || count( $items ) < 1;
	}

	if ( ! $needs_sync && function_exists( 'bi_footer_structure' ) ) {
		foreach ( bi_footer_structure() as $col ) {
			if ( empty( $locations[ $col['location'] ] ) ) {
				$needs_sync = true;
				break;
			}
		}
	}

	if ( $needs_sync ) {
		bi_sync_grouped_primary_menu( true );
		bi_sync_footer_menu( true );
		update_option( 'bi_nav_public_schema', $schema, false );
	}
}

/**
 * Render primary navigation (WP menu or structure fallback).
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
 * Fallback nav when no WP menu assigned — mirrors reference order.
 */
function bi_nav_fallback_menu() {
	echo '<ul class="ngt-nav__menu ngt-nav__menu--reference">';
	foreach ( bi_nav_menu_structure() as $item ) {
		if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
			echo '<li class="menu-item menu-item-has-children ngt-nav__dropdown">';
			echo '<button type="button" class="ngt-nav__link ngt-nav__dropdown-trigger" aria-expanded="false" aria-haspopup="true">';
			echo esc_html( $item['title'] );
			echo '<svg class="ngt-nav__caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
			echo '</button>';
			echo '<ul class="ngt-nav__submenu sub-menu">';
			foreach ( $item['children'] as $child ) {
				$path = '/' . ltrim( (string) $child['slug'], '/' );
				echo '<li class="menu-item"><a class="ngt-nav__sublink" href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $child['title'] ) . '</a></li>';
			}
			echo '</ul></li>';
			continue;
		}
		$path = '/' . ltrim( (string) $item['slug'], '/' );
		echo '<li class="menu-item"><a class="ngt-nav__link" href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $item['title'] ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Header Sign In CTA (reference chrome) — Dashboard when logged in. No Get Started.
 */
function bi_render_header_sign_in_cta() {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$url  = function_exists( 'bi_user_role_home_url' ) ? bi_user_role_home_url( $user ) : home_url( '/' );
		$label = __( 'Dashboard', 'beyondinfinity' );
	} else {
		$url   = home_url( '/login' );
		$label = __( 'Sign In', 'beyondinfinity' );
	}
	printf(
		'<a class="ngt-btn ngt-btn--outline ngt-btn--sm ngt-nav__signin" href="%1$s" data-testid="ngt-nav-signin">%2$s</a>',
		esc_url( $url ),
		esc_html( $label )
	);
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
 * Menu groups for header navigation (slug lists) — reference shape.
 *
 * @return array<string, string[]>
 */
function bi_nav_page_groups() {
	$groups = [];
	foreach ( bi_nav_menu_structure() as $item ) {
		if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
			$groups[ $item['title'] ] = array_map(
				static function ( $child ) {
					return (string) $child['slug'];
				},
				$item['children']
			);
			continue;
		}
		$groups[ $item['title'] ] = [ (string) $item['slug'] ];
	}
	return $groups;
}

/**
 * Canonical footer columns (Explore / Company / Legal).
 *
 * @return array<string, array<string, mixed>>
 */
function bi_footer_structure() {
	return [
		'explore' => [
			'title'     => 'Explore',
			'location'  => 'footer-explore',
			'menu_name' => 'NextGen Footer Explore',
			'items'     => [
				[ 'title' => 'Find a Tutor', 'slug' => 'find-a-tutor' ],
				[ 'title' => 'Pricing', 'slug' => 'pricing' ],
				[ 'title' => 'Become a Tutor', 'slug' => 'become-a-tutor' ],
				[ 'title' => 'Blog', 'slug' => 'blog' ],
				[ 'title' => 'Subjects', 'slug' => 'subjects' ],
			],
		],
		'company' => [
			'title'     => 'Company',
			'location'  => 'footer-company',
			'menu_name' => 'NextGen Footer Company',
			'items'     => [
				[ 'title' => 'About', 'slug' => 'about' ],
				[ 'title' => 'Contact', 'slug' => 'contact' ],
				[ 'title' => 'Safety Guide', 'slug' => 'safety-guide' ],
				[ 'title' => 'Tutor Vetting', 'slug' => 'tutor-vetting' ],
				[ 'title' => '1st Lesson Guarantee', 'slug' => 'guarantee' ],
				[ 'title' => 'Help & Support', 'slug' => 'support' ],
			],
		],
		'legal'   => [
			'title'     => 'Legal',
			'location'  => 'footer-1',
			'menu_name' => 'NextGen Footer',
			'items'     => [
				[ 'title' => 'Privacy Policy', 'slug' => 'privacy-policy' ],
				[ 'title' => 'Terms', 'slug' => 'terms' ],
				[ 'title' => 'POPIA Compliance', 'slug' => 'child-safety' ],
			],
		],
	];
}

/**
 * @param string $slug Page slug or path.
 * @return WP_Post|null
 */
function bi_nav_resolve_page( $slug ) {
	$page = function_exists( 'bi_find_page_by_slug' ) ? bi_find_page_by_slug( $slug ) : null;
	if ( $page ) {
		return $page;
	}
	$found = get_page_by_path( $slug );
	return $found ? $found : null;
}

/**
 * @param string $slug Page slug.
 * @return string
 */
function bi_nav_item_url( $slug ) {
	return home_url( '/' . ltrim( (string) $slug, '/' ) );
}

/**
 * WP nav item args — page object when published, otherwise a custom URL (never drop the link).
 *
 * @param string $title     Label.
 * @param string $slug      Slug.
 * @param int    $parent_id Parent menu item ID.
 * @return array<string, mixed>
 */
function bi_nav_menu_item_create_args( $title, $slug, $parent_id = 0 ) {
	$page = bi_nav_resolve_page( $slug );
	$args = [
		'menu-item-title'  => $title,
		'menu-item-status' => 'publish',
	];
	if ( $parent_id ) {
		$args['menu-item-parent-id'] = $parent_id;
	}
	if ( $page ) {
		$args['menu-item-object-id'] = (int) $page->ID;
		$args['menu-item-object']    = 'page';
		$args['menu-item-type']      = 'post_type';
		return $args;
	}
	$args['menu-item-url']  = bi_nav_item_url( $slug );
	$args['menu-item-type'] = 'custom';
	return $args;
}

/**
 * Resolved footer links: assigned WP menu, else SSOT.
 *
 * @param string $column_key explore|company|legal.
 * @return array<int, array{title:string,url:string}>
 */
function bi_footer_resolved_items( $column_key ) {
	$struct = bi_footer_structure();
	$col    = $struct[ $column_key ] ?? null;
	if ( ! $col ) {
		return [];
	}
	$locations = get_theme_mod( 'nav_menu_locations', [] );
	$menu_id   = (int) ( $locations[ $col['location'] ] ?? 0 );
	$wp_items  = $menu_id ? wp_get_nav_menu_items( $menu_id ) : false;
	if ( is_array( $wp_items ) && count( $wp_items ) > 0 ) {
		$out = [];
		foreach ( $wp_items as $it ) {
			if ( (int) $it->menu_item_parent > 0 ) {
				continue;
			}
			$out[] = [
				'title' => (string) $it->title,
				'url'   => (string) $it->url,
			];
		}
		return $out;
	}
	$out = [];
	foreach ( $col['items'] as $item ) {
		$out[] = [
			'title' => (string) $item['title'],
			'url'   => bi_nav_item_url( $item['slug'] ),
		];
	}
	return $out;
}

/**
 * @param string $column_key explore|company.
 */
function bi_render_footer_link_list( $column_key ) {
	echo '<ul class="ngt-footer__links">';
	foreach ( bi_footer_resolved_items( $column_key ) as $item ) {
		echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Legal bar (default + minimal footers).
 *
 * @param bool $include_support Prepend Support (dashboard minimal footer).
 */
function bi_render_footer_legal( $include_support = false ) {
	echo '<div class="bi-footer-legal">';
	if ( $include_support ) {
		echo '<a href="' . esc_url( home_url( '/support' ) ) . '">' . esc_html__( 'Support', 'beyondinfinity' ) . '</a>';
	}
	foreach ( bi_footer_resolved_items( 'legal' ) as $item ) {
		echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a>';
	}
	echo '</div>';
}

/**
 * Create / refresh primary menu from reference structure.
 *
 * @param bool $force Rebuild even when menu already populated.
 * @return int Menu term ID or 0.
 */
function bi_sync_grouped_primary_menu( $force = false ) {
	$menu_name = 'NextGen Primary';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : 0;

	// Migrate from legacy grouped menu name if present.
	if ( ! $menu_id ) {
		$legacy = wp_get_nav_menu_object( 'NextGen Primary Grouped' );
		if ( $legacy ) {
			$menu_id = (int) $legacy->term_id;
			wp_update_term(
				$menu_id,
				'nav_menu',
				[ 'name' => $menu_name ]
			);
		}
	}

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

	foreach ( bi_nav_menu_structure() as $item ) {
		if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
			$parent_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				[
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => '#',
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				]
			);
			foreach ( $item['children'] as $child ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					bi_nav_menu_item_create_args( $child['title'], $child['slug'], (int) $parent_id )
				);
			}
			continue;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			bi_nav_menu_item_create_args( $item['title'], $item['slug'] )
		);
	}

	$locations            = get_theme_mod( 'nav_menu_locations', [] );
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
 * Sync one footer column menu from SSOT.
 *
 * @param string $column_key explore|company|legal.
 * @param bool   $force      Rebuild.
 * @return int Menu term ID or 0.
 */
function bi_sync_footer_column( $column_key, $force = false ) {
	$struct = bi_footer_structure();
	$col    = $struct[ $column_key ] ?? null;
	if ( ! $col ) {
		return 0;
	}
	$menu_name = $col['menu_name'];
	$location  = $col['location'];
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : 0;
	if ( ! $menu_id ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}
	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return 0;
	}

	$existing = wp_get_nav_menu_items( $menu_id );
	$expected = count( $col['items'] );
	if ( ! $force && is_array( $existing ) && count( $existing ) >= $expected ) {
		$locations              = get_theme_mod( 'nav_menu_locations', [] );
		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		return $menu_id;
	}

	bi_clear_nav_menu_items( $menu_id );
	foreach ( $col['items'] as $item ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			bi_nav_menu_item_create_args( $item['title'], $item['slug'] )
		);
	}

	$locations              = get_theme_mod( 'nav_menu_locations', [] );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return $menu_id;
}

/**
 * Footer menus (Explore, Company, Legal). Returns Legal menu ID for back-compat.
 *
 * @param bool $force Rebuild menu items.
 * @return int Legal menu term ID or 0.
 */
function bi_sync_footer_menu( $force = false ) {
	$legal_id = 0;
	foreach ( array_keys( bi_footer_structure() ) as $key ) {
		$id = (int) bi_sync_footer_column( $key, $force );
		if ( 'legal' === $key ) {
			$legal_id = $id;
		}
	}
	return $legal_id;
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

add_action(
	'after_switch_theme',
	function () {
		bi_sync_grouped_primary_menu( true );
		bi_sync_footer_menu( true );
	}
);

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
