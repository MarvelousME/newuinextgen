<?php
/**
 * Central administration registry — modules, screens, search metadata.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin-agnostic registration API for the unified admin shell.
 */
final class NGC_Admin_Registry {

	/** @var array<string, array<string, mixed>> */
	private static $modules = [];

	/** @var array<string, array<string, mixed>> */
	private static $screens = [];

	/** @var array<string, callable> */
	private static $badge_providers = [];

	/**
	 * Allow late discovery hooks.
	 */
	public static function init() {
		/**
		 * Plugins register modules/screens here.
		 *
		 * @param string $registry Class name (self).
		 */
		do_action( 'ngt_admin_register', self::class );
	}

	/**
	 * @param array<string, mixed> $module Module definition.
	 */
	public static function register_module( array $module ) {
		$slug = sanitize_key( (string) ( $module['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return;
		}
		self::$modules[ $slug ] = array_merge(
			[
				'slug'         => $slug,
				'label'        => $slug,
				'category'     => 'operations',
				'icon'         => 'dashicons-admin-generic',
				'capability'   => 'manage_options',
				'order'        => 50,
				'description'  => '',
				'keywords'     => [],
				'dependencies' => [],
				'visible'      => true,
			],
			$module,
			[ 'slug' => $slug ]
		);
	}

	/**
	 * @param array<string, mixed> $screen Screen definition.
	 */
	public static function register_screen( array $screen ) {
		$slug = sanitize_key( (string) ( $screen['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return;
		}
		self::$screens[ $slug ] = array_merge(
			[
				'slug'         => $slug,
				'title'        => $slug,
				'menu_title'   => '',
				'module'       => 'operations',
				'category'     => 'operations',
				'capability'   => 'manage_options',
				'callback'     => null,
				'order'        => 50,
				'parent'       => NGC_Admin_Shell::PARENT_SLUG,
				'nav_parent'   => '',
				'icon'         => '',
				'keywords'     => [],
				'help_url'     => '',
				'legacy_parent'=> '',
				'badge_key'    => '',
				'visible'      => true,
				'placeholder'  => false,
				'wp_menu'      => true,
				'hidden'       => false,
				'favorite_eligible' => true,
			],
			$screen,
			[ 'slug' => $slug ]
		);
		if ( empty( self::$screens[ $slug ]['menu_title'] ) ) {
			self::$screens[ $slug ]['menu_title'] = self::$screens[ $slug ]['title'];
		}
	}

	/**
	 * @param string   $key      Badge key.
	 * @param callable $provider Returns int count.
	 */
	public static function register_badge_provider( $key, $provider ) {
		$key = sanitize_key( (string) $key );
		if ( $key && is_callable( $provider ) ) {
			self::$badge_providers[ $key ] = $provider;
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function modules() {
		$mods = self::$modules;
		uasort(
			$mods,
			static function ( $a, $b ) {
				return ( (int) ( $a['order'] ?? 50 ) ) <=> ( (int) ( $b['order'] ?? 50 ) );
			}
		);
		return $mods;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function screens() {
		$screens = self::$screens;
		uasort(
			$screens,
			static function ( $a, $b ) {
				$cat = strcmp( (string) ( $a['category'] ?? '' ), (string) ( $b['category'] ?? '' ) );
				if ( 0 !== $cat ) {
					return $cat;
				}
				return ( (int) ( $a['order'] ?? 50 ) ) <=> ( (int) ( $b['order'] ?? 50 ) );
			}
		);
		return $screens;
	}

	/**
	 * @param string $slug Screen slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_screen( $slug ) {
		$slug = sanitize_key( (string) $slug );
		return self::$screens[ $slug ] ?? null;
	}

	/**
	 * @param string $slug Module slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_module( $slug ) {
		$slug = sanitize_key( (string) $slug );
		return self::$modules[ $slug ] ?? null;
	}

	/**
	 * @param string $key Badge key.
	 * @return int
	 */
	public static function badge_count( $key ) {
		$key = sanitize_key( (string) $key );
		if ( empty( self::$badge_providers[ $key ] ) ) {
			return 0;
		}
		$count = (int) call_user_func( self::$badge_providers[ $key ] );
		return max( 0, $count );
	}

	/**
	 * Screens the current user may see.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function visible_screens() {
		$out = [];
		foreach ( self::screens() as $slug => $screen ) {
			if ( empty( $screen['visible'] ) ) {
				continue;
			}
			$cap = (string) ( $screen['capability'] ?? 'manage_options' );
			if ( ! current_user_can( $cap ) ) {
				continue;
			}
			if ( ! empty( $screen['dependencies'] ) && is_array( $screen['dependencies'] ) ) {
				$ok = true;
				foreach ( $screen['dependencies'] as $dep ) {
					if ( is_string( $dep ) && ! class_exists( $dep ) && ! function_exists( $dep ) ) {
						$ok = false;
						break;
					}
				}
				if ( ! $ok ) {
					continue;
				}
			}
			$out[ $slug ] = $screen;
		}
		return $out;
	}

	/**
	 * Search index rows.
	 *
	 * @param string $query Query.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $query ) {
		$query = strtolower( trim( (string) $query ) );
		if ( '' === $query ) {
			return [];
		}
		$hits = [];
		foreach ( self::visible_screens() as $screen ) {
			$hay = strtolower(
				implode(
					' ',
					array_merge(
						[
							(string) ( $screen['title'] ?? '' ),
							(string) ( $screen['menu_title'] ?? '' ),
							(string) ( $screen['module'] ?? '' ),
							(string) ( $screen['category'] ?? '' ),
							(string) ( $screen['slug'] ?? '' ),
						],
						(array) ( $screen['keywords'] ?? [] )
					)
				)
			);
			if ( false !== strpos( $hay, $query ) ) {
				$hits[] = [
					'slug'  => $screen['slug'],
					'title' => $screen['title'],
					'url'   => admin_url( 'admin.php?page=' . $screen['slug'] ),
					'module'=> $screen['module'],
				];
			}
		}
		return array_slice( $hits, 0, 25 );
	}
}
