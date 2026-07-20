<?php
/**
 * Hydrate prototype marketing shells with live Companion / CPT data.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collect public platform stats for prototype pageheads.
 *
 * @return array<string, int|float|string>
 */
function bi_prototype_live_platform_stats() {
	$tutor_count = function_exists( 'bi_count_published_tutors' ) ? bi_count_published_tutors() : 0;
	$live        = function_exists( 'bi_get_live_tutors' ) ? bi_get_live_tutors( 50 ) : [];

	if ( empty( $live ) && class_exists( 'NGC_Marketplace' ) ) {
		$query = NGC_Marketplace::query_tutors( [ 'per_page' => 50 ] );
		$live  = is_array( $query['items'] ?? null ) ? $query['items'] : [];
		if ( ! $tutor_count ) {
			$tutor_count = (int) ( $query['total'] ?? count( $live ) );
		}
	}

	$ratings = array_filter(
		array_map(
			static function ( $t ) {
				return (float) ( $t['rating'] ?? 0 );
			},
			$live
		)
	);

	$avg = $ratings ? round( array_sum( $ratings ) / count( $ratings ), 1 ) : 4.8;

	return apply_filters(
		'bi_prototype_live_platform_stats',
		[
			'tutor_count'  => max( $tutor_count, count( $live ) ),
			'avg_rating'   => $avg,
			'satisfaction' => min( 99, max( 90, (int) round( ( $avg / 5 ) * 100 ) ) ),
			'subjects'     => count( function_exists( 'bi_get_subject_tracks' ) ? bi_get_subject_tracks() : [] ),
		]
	);
}

/**
 * Dashboard REST type for a page slug.
 *
 * @param string $slug Page slug.
 * @return string
 */
function bi_prototype_dashboard_type( $slug ) {
	$map = [
		'student-dashboard' => 'student',
		'parent-dashboard'  => 'parent',
		'tutor-dashboard'   => 'tutor',
		'admin-dashboard'   => 'admin',
		'onboarding'        => 'admin',
	];
	return $map[ $slug ] ?? '';
}

/**
 * Render live REST dashboard mount for prototype dashboard pages.
 *
 * @param string $slug Page slug.
 */
function bi_render_prototype_live_dashboard( $slug ) {
	$type = bi_prototype_dashboard_type( $slug );
	if ( ! $type ) {
		return;
	}

	if ( function_exists( 'bi_enqueue_dashboard_rest_for_type' ) ) {
		bi_enqueue_dashboard_rest_for_type( $type );
	}

	echo '<section class="section bi-prototype-live-dash" id="live-dashboard-data" data-dashboard-role="' . esc_attr( $type ) . '">';
	echo '<div class="wrap">';
	echo '<p class="bi-live-badge">' . esc_html__( 'Live platform data', 'beyondinfinity' ) . '</p>';
	echo '<div class="bi-dashboard-rest ngt-card" data-dashboard="' . esc_attr( $type ) . '" role="region" aria-live="polite" aria-busy="true">';
	echo '<p class="bi-dashboard-rest__loading">' . esc_html__( 'Loading live dashboard…', 'beyondinfinity' ) . '</p>';
	echo '</div></div></section>';
}

/**
 * Enqueue client hydrator on prototype blend pages.
 */
function bi_prototype_live_data_assets() {
	if ( ! function_exists( 'bi_prototype_blend_active' ) || ! bi_prototype_blend_active() ) {
		return;
	}

	$slug = function_exists( 'bi_page_slug' ) ? bi_page_slug() : '';
	if ( ! $slug ) {
		return;
	}

	wp_enqueue_script(
		'bi-prototype-live-data',
		BI_URI . '/assets/js/prototype-live-data.js',
		[ 'bi-ngt-wp-bridge' ],
		BI_VERSION,
		true
	);

	$config = [
		'slug'    => $slug,
		'stats'   => bi_prototype_live_platform_stats(),
		'rest'    => [
			'root'      => esc_url_raw( rest_url() ),
			'namespace' => function_exists( 'bi_rest_namespace' ) ? bi_rest_namespace() : 'ngc/v1',
			'nonce'     => wp_create_nonce( 'wp_rest' ),
		],
		'pages'   => [
			'findTutor' => home_url( '/find-a-tutor/' ),
			'login'     => home_url( '/login/' ),
		],
		'dashboardType' => bi_prototype_dashboard_type( $slug ),
	];

	if ( $config['dashboardType'] && function_exists( 'bi_dashboard_rest_config' ) ) {
		$config['dashboard'] = bi_dashboard_rest_config( $config['dashboardType'] );
	}

	wp_localize_script( 'bi-prototype-live-data', 'biPrototypeLive', $config );
}
add_action( 'wp_enqueue_scripts', 'bi_prototype_live_data_assets', 45 );

/**
 * Force prototype markers + shortcode blocks on every launch page (idempotent).
 *
 * @return array<string, int>|WP_Error
 */
function bi_sync_all_prototype_pages() {
	$result = function_exists( 'bi_sync_launch_pages' ) ? bi_sync_launch_pages() : null;
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$synced = 0;
	if ( function_exists( 'bi_load_page_map' ) && function_exists( 'bi_sync_page_prototype_content' ) ) {
		$pages = bi_load_page_map();
		if ( ! is_wp_error( $pages ) ) {
			foreach ( (array) $pages as $row ) {
				$slug = sanitize_key( (string) ( $row['slug'] ?? '' ) );
				if ( ! $slug ) {
					continue;
				}
				$page = function_exists( 'bi_find_page_by_slug' ) ? bi_find_page_by_slug( $slug ) : get_page_by_path( $slug );
				if ( $page ) {
					bi_sync_page_prototype_content( (int) $page->ID, $slug );
					++$synced;
				}
			}
		}
	}

	if ( is_array( $result ) ) {
		$result['prototype_synced'] = $synced;
		return $result;
	}

	return [ 'prototype_synced' => $synced ];
}
