<?php
/**
 * Theme integration for NextGen UI Library.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BI_DIR . '/inc/ui-library/component-loader.php';
require_once BI_DIR . '/inc/ui-library/builders.php';

add_action( 'after_setup_theme', 'ng_ui_bootstrap', 20 );
/**
 * Wire companion UI library to theme partials.
 */
function ng_ui_bootstrap() {
	add_filter( 'ngc_ui_render_component', 'ng_ui_render_component', 20, 2 );
	add_action( 'wp_enqueue_scripts', 'ng_ui_enqueue_assets', 30 );
}

/**
 * Render a registered UI component via template partial.
 *
 * @param string               $html    Existing output.
 * @param array<string, mixed> $context { slug, page_key, limit, ... }.
 * @return string
 */
function ng_ui_render_component( $html, $context ) {
	if ( '' !== $html || empty( $context['slug'] ) ) {
		return $html;
	}

	if ( ! class_exists( 'NGC_UI_Component_Registry' ) ) {
		return $html;
	}

	$slug = sanitize_key( $context['slug'] );
	$def  = NGC_UI_Component_Registry::get( $slug );
	if ( ! $def ) {
		return $html;
	}

	$provider_key = $def['provider'] ?? '';
	$data         = $provider_key && class_exists( 'NGC_UI_Provider_Registry' )
		? NGC_UI_Provider_Registry::component_data( $provider_key, $slug, $context )
		: [];

	ng_ui_enqueue_component_assets( $slug, $def );

	ob_start();
	$partial = BI_DIR . '/template-parts/ui-library/' . sanitize_file_name( $def['partial'] ?? $slug ) . '.php';
	if ( is_readable( $partial ) ) {
		$args = [
			'slug'  => $slug,
			'def'   => $def,
			'items' => $data,
			'ctx'   => $context,
		];
		include $partial;
	} else {
		echo '<div class="ng-ui-fallback" data-ng-ui="' . esc_attr( $slug ) . '"></div>';
	}
	return (string) ob_get_clean();
}

/**
 * Enqueue global UI tokens + per-page bundles.
 */
function ng_ui_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'ng-ui-tokens',
		BI_URI . '/assets/css/ng-ui-tokens.css',
		[],
		BI_VERSION
	);

	wp_enqueue_style(
		'ng-ui-components',
		BI_URI . '/assets/css/ng-ui-components.css',
		[ 'ng-ui-tokens' ],
		BI_VERSION
	);

	if ( is_front_page() || is_page() ) {
		$slug = is_front_page() ? 'home' : get_post_field( 'post_name', get_queried_object_id() );
		if ( $slug && class_exists( 'NGC_UI_Component_Registry' ) ) {
			foreach ( NGC_UI_Component_Registry::for_page( (string) $slug ) as $comp_slug => $def ) {
				ng_ui_enqueue_component_assets( $comp_slug, $def );
			}
		}
	}
}

/**
 * @param string               $slug Component slug.
 * @param array<string, mixed> $def  Definition.
 */
function ng_ui_enqueue_component_assets( $slug, $def ) {
	$map = [
		'ng-ui-hero'      => 'assets/css/ng-ui-hero.css',
		'ng-ui-cards'     => 'assets/css/ng-ui-cards.css',
		'ng-ui-dashboard' => 'assets/css/ng-ui-dashboard.css',
		'ng-ui-booking'   => 'assets/css/ng-ui-booking.css',
	];

	foreach ( (array) ( $def['assets'] ?? [] ) as $handle ) {
		if ( isset( $map[ $handle ] ) && ! wp_style_is( $handle, 'enqueued' ) ) {
			wp_enqueue_style( $handle, BI_URI . '/' . $map[ $handle ], [ 'ng-ui-components' ], BI_VERSION );
		}
	}

	if ( ! wp_script_is( 'ng-ui', 'enqueued' ) ) {
		wp_enqueue_script(
			'ng-ui',
			BI_URI . '/assets/js/ng-ui.js',
			[],
			BI_VERSION,
			true
		);
	}
}

/**
 * Template tag for partials.
 *
 * @param string               $slug    Component slug.
 * @param array<string, mixed> $context Context.
 */
function ng_ui_component( $slug, $context = [] ) {
	echo apply_filters( 'ngc_ui_render_component', '', array_merge( [ 'slug' => $slug ], $context ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
