<?php
/**
 * Component registry — maps slugs to providers, templates, assets.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical UI component definitions.
 */
class NGC_UI_Component_Registry {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		$defs = [
			'hero'           => [
				'label'    => __( 'Hero', 'nextgencompanion' ),
				'provider' => 'page_content',
				'partial'  => 'hero',
				'pages'    => [ 'home', 'find-a-tutor', 'become-a-tutor', 'pricing', 'about', 'contact' ],
				'assets'   => [ 'ng-ui-hero' ],
			],
		'stats-band'     => [
				'label'    => __( 'Trust stats', 'nextgencompanion' ),
				'provider' => 'analytics',
				'partial'  => 'stats-band',
				'pages'    => [ 'home', 'about', 'become-a-tutor', 'find-a-tutor', 'pricing', 'guarantee', 'tutor-vetting' ],
				'assets'   => [ 'ng-ui-dashboard' ],
			],
			'tutor-card'     => [
				'label'    => __( 'Tutor card', 'nextgencompanion' ),
				'provider' => 'tutor',
				'partial'  => 'tutor-card',
				'pages'    => [ 'home', 'tutor-marketplace', 'our-tutors', 'subjects' ],
				'assets'   => [ 'ng-ui-cards' ],
			],
			'subject-card'   => [
				'label'    => __( 'Subject card', 'nextgencompanion' ),
				'provider' => 'subject',
				'partial'  => 'subject-card',
				'pages'    => [ 'home', 'subjects' ],
				'assets'   => [ 'ng-ui-cards' ],
			],
			'pricing-card'   => [
				'label'    => __( 'Pricing card', 'nextgencompanion' ),
				'provider' => 'pricing',
				'partial'  => 'pricing-card',
				'pages'    => [ 'home', 'pricing' ],
				'assets'   => [ 'ng-ui-pricing' ],
			],
			'review-card'    => [
				'label'    => __( 'Review card', 'nextgencompanion' ),
				'provider' => 'review',
				'partial'  => 'review-card',
				'pages'    => [ 'home', 'tutor-profile' ],
				'assets'   => [ 'ng-ui-reviews' ],
			],
			'dashboard-kpi'  => [
				'label'    => __( 'Dashboard KPI', 'nextgencompanion' ),
				'provider' => 'dashboard',
				'partial'  => 'dashboard-kpi',
				'pages'    => [ 'parent-dashboard', 'student-dashboard', 'tutor-dashboard', 'admin-dashboard' ],
				'assets'   => [ 'ng-ui-dashboard' ],
			],
			'booking-list'   => [
				'label'    => __( 'Booking list', 'nextgencompanion' ),
				'provider' => 'booking',
				'partial'  => 'booking-list',
				'pages'    => [ 'parent-dashboard', 'student-dashboard', 'tutor-dashboard' ],
				'assets'   => [ 'ng-ui-booking' ],
			],
			'calendar-grid'  => [
				'label'    => __( 'Calendar', 'nextgencompanion' ),
				'provider' => 'calendar',
				'partial'  => 'calendar-grid',
				'pages'    => [ 'tutor-profile', 'tutor-calendar' ],
				'assets'   => [ 'ng-ui-booking' ],
			],
			'achievement-badge' => [
				'label'    => __( 'Achievement badge', 'nextgencompanion' ),
				'provider' => 'gamification',
				'partial'  => 'achievement-badge',
				'pages'    => [ 'student-dashboard', 'tutor-profile' ],
				'assets'   => [ 'ng-ui-booking' ],
			],
			'empty-state'    => [
				'label'    => __( 'Empty state', 'nextgencompanion' ),
				'provider' => '',
				'partial'  => 'empty-state',
				'pages'    => [ '*' ],
				'assets'   => [],
			],
		];

		return apply_filters( 'ngc_ui_component_definitions', $defs );
	}

	/**
	 * @param string $slug Component slug.
	 * @return array<string, mixed>|null
	 */
	public static function get( $slug ) {
		$defs = self::definitions();
		return $defs[ $slug ] ?? null;
	}

	/**
	 * Components used on a page slug.
	 *
	 * @param string $page_slug Page slug.
	 * @return array<string, array<string, mixed>>
	 */
	public static function for_page( $page_slug ) {
		$page_slug = sanitize_title( $page_slug );
		$matched   = [];
		foreach ( self::definitions() as $slug => $def ) {
			$pages = $def['pages'] ?? [];
			if ( in_array( '*', $pages, true ) || in_array( $page_slug, $pages, true ) ) {
				$matched[ $slug ] = $def;
			}
		}
		return $matched;
	}
}
