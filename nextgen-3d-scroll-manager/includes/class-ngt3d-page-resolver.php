<?php
/**
 * Server-side page resolver.
 *
 * Resolves the current WordPress request to a canonical (page_id, page_slug)
 * pair BEFORE any DB query for rules. This avoids client-side "what page am I on?"
 * round-trips and allows precise asset enqueueing.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Page_Resolver {

	/** @var array|null Resolved page info cached for the current request. */
	private static ?array $resolved = null;

	/**
	 * Resolve the current page.
	 *
	 * @return array{ id: int, slug: string, is_front: bool, is_singular: bool }
	 */
	public static function resolve(): array {
		if ( null !== self::$resolved ) {
			return self::$resolved;
		}

		// Front page.
		if ( is_front_page() ) {
			$page_id = (int) get_option( 'page_on_front', 0 );
			$slug    = 'home';

			if ( $page_id > 0 ) {
				$page = get_post( $page_id );
				if ( $page ) {
					$slug = $page->post_name;
				}
			}

			self::$resolved = [
				'id'          => $page_id,
				'slug'        => $slug,
				'is_front'    => true,
				'is_singular' => true,
			];

			return self::$resolved;
		}

		// Standard page.
		if ( is_singular( 'page' ) ) {
			$post_id = (int) get_queried_object_id();
			$slug    = get_post_field( 'post_name', $post_id );

			self::$resolved = [
				'id'          => $post_id,
				'slug'        => sanitize_key( (string) $slug ),
				'is_front'    => false,
				'is_singular' => true,
			];

			return self::$resolved;
		}

		// Other singular (post, CPT, etc.).
		if ( is_singular() ) {
			$post_id = (int) get_queried_object_id();
			$slug    = get_post_field( 'post_name', $post_id );

			self::$resolved = [
				'id'          => $post_id,
				'slug'        => sanitize_key( (string) $slug ),
				'is_front'    => false,
				'is_singular' => true,
			];

			return self::$resolved;
		}

		// Archives, search, 404, etc. — no rules apply.
		self::$resolved = [ 'id' => 0, 'slug' => '', 'is_front' => false, 'is_singular' => false ];

		return self::$resolved;
	}

	/**
	 * Reset the internal cache (useful for unit tests).
	 */
	public static function reset(): void {
		self::$resolved = null;
	}

	/**
	 * Whether the current page can possibly have 3D rules.
	 * Returns false on admin, builder edit mode, and REST requests.
	 *
	 * @return bool
	 */
	public static function can_load_engine(): bool {
		if ( is_admin() ) {
			return false;
		}

		$settings = NGT3D_Runtime_Config::get_settings();
		if ( empty( $settings['engine_enabled'] ) ) {
			return false;
		}

		// Respect the bi_is_builder_edit_mode() check if the theme is active.
		if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
			return false;
		}

		// REST API requests that are not preview.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		// WordPress AJAX.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// wp-cron / WP CLI.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}

		return true;
	}
}
