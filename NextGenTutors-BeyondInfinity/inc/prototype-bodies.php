<?php
/**
 * Original-top-ui prototype body includes.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bi_prototypes_dir() {
	return BI_DIR . '/prototypes';
}

function bi_prototype_page_slug_aliases() {
	return [
		'privacy'       => 'privacy-policy',
		'dashboard'     => 'student-dashboard',
		'setup'         => 'wordpress-setup',
		'index'         => 'home',
		'tutor-profile' => 'tutors',
	];
}

if ( ! function_exists( 'ngt_get_page_url' ) ) {
	function ngt_get_page_url( $slug ) {
		$slug = sanitize_title( (string) $slug );
		$map  = bi_prototype_page_slug_aliases();
		if ( isset( $map[ $slug ] ) ) {
			$slug = $map[ $slug ];
		}

		if ( 'tutors' === $slug ) {
			$tutors = get_posts(
				[
					'post_type'      => 'tutors',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				]
			);
			if ( ! empty( $tutors[0] ) ) {
				return get_permalink( $tutors[0] );
			}
			return home_url( '/find-a-tutor/' );
		}

		$page = get_page_by_path( $slug );
		return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
	}
}

function bi_include_prototype_body( $file, $context = [] ) {
	$slug = isset( $context['slug'] ) ? sanitize_key( (string) $context['slug'] ) : '';
	if ( ! $slug && function_exists( 'bi_page_slug' ) ) {
		$slug = sanitize_key( (string) bi_page_slug() );
	}

	$file = basename( (string) $file );
	if ( ! preg_match( '/-body\.php$/', $file ) ) {
		$file .= '-body.php';
	}

	$path = bi_prototypes_dir() . '/' . $file;
	if ( ! file_exists( $path ) ) {
		return;
	}

	ob_start();
	include $path;
	$html = (string) ob_get_clean();
	$html = preg_replace( '/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html );
	$html = preg_replace( '/<script\b[^>]*\/>/i', '', $html );

	if ( $slug && function_exists( 'bi_process_prototype_html' ) && function_exists( 'bi_use_prototype_blend' ) && bi_use_prototype_blend() ) {
		echo bi_process_prototype_html( $html, $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function bi_render_registry_shortcodes( $slug ) {
	if ( ! function_exists( 'bi_pages_registry' ) ) {
		return;
	}

	$registry = bi_pages_registry();
	if ( empty( $registry[ $slug ]['shortcodes'] ) ) {
		return;
	}

	echo '<section class="ng-page-section ngt-section bi-prototype-shortcodes"><div class="ng-container bi-narrow">';
	foreach ( (array) $registry[ $slug ]['shortcodes'] as $tag ) {
		if ( ! shortcode_exists( $tag ) ) {
			continue;
		}
		$sc = '[' . $tag . ']';
		if ( function_exists( 'bi_shortcode_block' ) ) {
			bi_shortcode_block( $sc );
		} else {
			echo do_shortcode( $sc );
		}
	}
	echo '</div></section>';
}
