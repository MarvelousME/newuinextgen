<?php
/**
 * Maps page slugs to recommended UI components (page-builder hints).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, string[]>
 */
function ng_ui_page_component_map() {
	return [
		'home'              => [ 'hero', 'stats-band', 'subject-card', 'tutor-card', 'review-card', 'pricing-card' ],
		'find-a-tutor'      => [ 'hero', 'stats-band', 'tutor-card' ],
		'become-a-tutor'    => [ 'hero', 'stats-band' ],
		'tutor-marketplace' => [ 'tutor-card' ],
		'pricing'           => [ 'hero', 'pricing-card', 'stats-band' ],
		'about'             => [ 'hero', 'stats-band', 'review-card' ],
		'contact'           => [ 'hero' ],
		'support'           => [ 'hero' ],
		'guarantee'         => [ 'hero', 'stats-band' ],
		'tutor-vetting'     => [ 'hero', 'stats-band' ],
		'safety-guide'      => [ 'hero' ],
		'child-safety'      => [ 'hero' ],
		'blog'              => [ 'hero' ],
		'register'          => [ 'hero' ],
		'login'             => [ 'hero' ],
		'parent-dashboard'  => [ 'dashboard-kpi', 'booking-list' ],
		'student-dashboard' => [ 'dashboard-kpi', 'booking-list', 'achievement-badge' ],
		'tutor-dashboard'   => [ 'dashboard-kpi', 'booking-list' ],
		'admin-dashboard'   => [ 'dashboard-kpi' ],
	];
}
