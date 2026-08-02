<?php
/**
 * Hierarchical navigation tree for capability-based admin IA.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds nested nav from registry categories → modules → screens.
 */
final class NGC_Admin_Nav_Tree {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function build() {
		$cats    = NGC_Admin_Catalog::categories();
		$layout  = NGC_Admin_Nav_Layout::get();
		$hidden  = array_map( 'strval', (array) ( $layout['hidden'] ?? [] ) );
		$order   = (array) ( $layout['order'] ?? [] );
		$renames = (array) ( $layout['renames'] ?? [] );
		$favs    = array_map( 'strval', (array) ( $layout['favorites'] ?? [] ) );
		$collapsed = array_map( 'strval', (array) ( $layout['collapsed'] ?? [] ) );

		$by_cat = [];
		foreach ( NGC_Admin_Registry::visible_screens() as $screen ) {
			if ( ! empty( $screen['wp_menu'] ) && false === $screen['wp_menu'] ) {
				// still show in custom nav unless hidden.
			}
			$cat = (string) ( $screen['category'] ?? 'operations' );
			$by_cat[ $cat ][] = $screen;
		}

		$tree = [];
		foreach ( $cats as $cat_key => $cat ) {
			$items = $by_cat[ $cat_key ] ?? [];
			if ( ! $items && empty( $layout['custom_groups'][ $cat_key ] ) ) {
				continue;
			}
			$children = [];
			foreach ( $items as $screen ) {
				$slug = (string) $screen['slug'];
				if ( in_array( $slug, $hidden, true ) ) {
					continue;
				}
				$nav_parent = (string) ( $screen['nav_parent'] ?? '' );
				if ( $nav_parent ) {
					continue; // attached under parent below.
				}
				$children[] = self::screen_node( $screen, $items, $favs, $renames );
			}
			usort(
				$children,
				static function ( $a, $b ) use ( $order ) {
					$oa = array_search( $a['id'], $order, true );
					$ob = array_search( $b['id'], $order, true );
					$oa = false === $oa ? (int) ( $a['order'] ?? 50 ) : $oa;
					$ob = false === $ob ? (int) ( $b['order'] ?? 50 ) : $ob;
					return $oa <=> $ob;
				}
			);
			$label = $renames[ 'cat:' . $cat_key ] ?? ( $cat['label'] ?? $cat_key );
			$tree[] = [
				'id'        => 'cat:' . $cat_key,
				'type'      => 'category',
				'label'     => $label,
				'icon'      => $cat['icon'] ?? 'dashicons-category',
				'order'     => (int) ( $cat['order'] ?? 50 ),
				'collapsed' => in_array( 'cat:' . $cat_key, $collapsed, true ),
				'children'  => $children,
			];
		}

		usort(
			$tree,
			static function ( $a, $b ) use ( $order ) {
				$oa = array_search( $a['id'], $order, true );
				$ob = array_search( $b['id'], $order, true );
				$oa = false === $oa ? (int) $a['order'] : $oa;
				$ob = false === $ob ? (int) $b['order'] : $ob;
				return $oa <=> $ob;
			}
		);

		$favorites = [];
		foreach ( $favs as $slug ) {
			$screen = NGC_Admin_Registry::get_screen( $slug );
			if ( $screen ) {
				$favorites[] = self::screen_node( $screen, [], $favs, $renames );
			}
		}

		return [
			'favorites' => $favorites,
			'tree'      => $tree,
			'layout'    => $layout,
		];
	}

	/**
	 * @param array<string, mixed>             $screen Screen.
	 * @param array<int, array<string, mixed>> $siblings All in category.
	 * @param array<int, string>               $favs Favorites.
	 * @param array<string, string>            $renames Renames.
	 * @return array<string, mixed>
	 */
	private static function screen_node( array $screen, array $siblings, array $favs, array $renames ) {
		$slug = (string) $screen['slug'];
		$kids = [];
		foreach ( $siblings as $child ) {
			if ( (string) ( $child['nav_parent'] ?? '' ) === $slug ) {
				$kids[] = self::screen_node( $child, $siblings, $favs, $renames );
			}
		}
		return [
			'id'         => $slug,
			'type'       => ! empty( $screen['placeholder'] ) ? 'placeholder' : 'screen',
			'label'      => $renames[ $slug ] ?? (string) ( $screen['menu_title'] ?: $screen['title'] ),
			'url'        => empty( $screen['placeholder'] ) ? admin_url( 'admin.php?page=' . $slug ) : '',
			'icon'       => (string) ( $screen['icon'] ?? '' ),
			'order'      => (int) ( $screen['order'] ?? 50 ),
			'favorite'   => in_array( $slug, $favs, true ),
			'badge'      => ! empty( $screen['badge_key'] ) ? NGC_Admin_Registry::badge_count( $screen['badge_key'] ) : 0,
			'placeholder'=> ! empty( $screen['placeholder'] ),
			'children'   => $kids,
		];
	}
}
