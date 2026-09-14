<?php
/**
 * Canonical page rendering — flat WordPress-native page contract.
 *
 * Target structure for primary pages:
 *   page-{slug}.php → get_header() → page body → get_footer()
 *
 * Builder / Elementor canvas short-circuits remain for compatibility.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle Elementor canvas / builder-edit shells that must not wrap in theme chrome.
 *
 * @return bool True when the request was fully handled (caller should return).
 */
function bi_canonical_page_short_circuit() {
	$post_id = function_exists( 'bi_get_current_page_id' ) ? bi_get_current_page_id() : get_queried_object_id();

	if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
		if ( function_exists( 'bi_is_elementor_canvas_template' ) && bi_is_elementor_canvas_template( $post_id ) ) {
			if ( function_exists( 'bi_ensure_page_in_loop' ) ) {
				bi_ensure_page_in_loop( $post_id );
			}
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			return true;
		}
		get_header();
		if ( function_exists( 'bi_render_builder_content' ) ) {
			bi_render_builder_content();
		}
		get_footer();
		return true;
	}

	if ( function_exists( 'bi_is_elementor_canvas_template' ) && bi_is_elementor_canvas_template( $post_id ) ) {
		if ( function_exists( 'bi_ensure_page_in_loop' ) ) {
			bi_ensure_page_in_loop( $post_id );
		}
		while ( have_posts() ) {
			the_post();
			the_content();
		}
		return true;
	}

	return false;
}

/**
 * Open the standard primary content shell.
 *
 * @param string $slug Registry / page slug.
 */
function bi_canonical_main_open( $slug = '' ) {
	$slug = $slug ?: ( function_exists( 'bi_page_slug' ) ? bi_page_slug() : '' );
	echo '<main id="primary" class="site-main bi-theme-main">';
	if ( function_exists( 'bi_page_open' ) ) {
		bi_page_open( $slug );
	} else {
		echo '<div class="bi-theme-content framer-frame ng-section">';
	}
}

/**
 * Close the standard primary content shell.
 *
 * @param string $slug Registry / page slug.
 */
function bi_canonical_main_close( $slug = '' ) {
	$slug = $slug ?: ( function_exists( 'bi_page_slug' ) ? bi_page_slug() : '' );
	if ( function_exists( 'bi_page_close' ) ) {
		bi_page_close( $slug );
	} else {
		echo '</div>';
	}
	echo '</main>';
}

/**
 * Render theme body or builder body after get_header().
 *
 * @param string        $slug          Page slug for shell chrome.
 * @param callable|null $body_callback Canonical page body renderer.
 */
function bi_canonical_render_body( $slug, $body_callback = null ) {
	$post_id = function_exists( 'bi_get_current_page_id' ) ? bi_get_current_page_id() : get_queried_object_id();

	if ( function_exists( 'bi_should_show_theme_fallback' ) && bi_should_show_theme_fallback( $post_id ) ) {
		bi_canonical_main_open( $slug );
		if ( is_callable( $body_callback ) ) {
			call_user_func( $body_callback );
		}
		bi_canonical_main_close( $slug );
		return;
	}

	if ( function_exists( 'bi_elementor_theme_location_handled' ) && bi_elementor_theme_location_handled() ) {
		return;
	}

	if ( function_exists( 'bi_render_builder_content' ) ) {
		bi_render_builder_content();
	}
}

/**
 * Load canonical page body from template-parts/pages/{slug}.php.
 *
 * @param string $slug Page slug.
 * @return bool
 */
function bi_canonical_load_page_body( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( ! $slug ) {
		return false;
	}

	$path = trailingslashit( BI_DIR ) . 'template-parts/pages/' . $slug . '.php';
	if ( ! file_exists( $path ) ) {
		return false;
	}

	include $path;
	return true;
}

/**
 * Append registry shortcodes for a slug (live Companion forms / dashboards).
 *
 * @param string $slug Registry slug.
 */
function bi_canonical_append_shortcodes( $slug ) {
	if ( function_exists( 'bi_elementor_is_seeding_capture' ) && bi_elementor_is_seeding_capture() ) {
		return;
	}
	if ( function_exists( 'bi_render_registry_shortcodes' ) ) {
		bi_render_registry_shortcodes( $slug );
	}
}
