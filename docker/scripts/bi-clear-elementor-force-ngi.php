<?php
/**
 * Clear Elementor stored documents and force theme .ngi-* defaults.
 * Usage: wp eval-file /var/www/html/wp-content/themes/nextgentutors-tutorfabulous/../../../../docker/scripts/... 
 * Better: mount via compose - file lives in docker/scripts on host.
 */

$slugs = [
	'home',
	'about',
	'pricing',
	'find-a-tutor',
	'become-a-tutor',
	'contact',
	'support',
	'guarantee',
	'tutor-vetting',
	'safety-guide',
	'privacy-policy',
	'terms',
	'child-safety',
	'blog',
	'login',
	'register',
	'parent-checkout',
	'thank-you',
	'onboarding',
	'parent-dashboard',
	'student-dashboard',
	'tutor-dashboard',
	'admin-dashboard',
	'wordpress-setup',
];

$keys = [
	'_elementor_data',
	'_elementor_edit_mode',
	'_elementor_template_type',
	'_elementor_version',
	'_elementor_pro_version',
	'_elementor_css',
	'_elementor_page_settings',
];

$n = 0;
foreach ( $slugs as $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		WP_CLI::log( "skip:$slug" );
		continue;
	}
	$id = (int) $page->ID;
	foreach ( $keys as $key ) {
		delete_post_meta( $id, $key );
	}
	$opts = get_post_meta( $id, 'bi_options', true );
	if ( ! is_array( $opts ) ) {
		$opts = [];
	}
	$opts['force_theme_default'] = 1;
	update_post_meta( $id, 'bi_options', $opts );
	$tpl = get_page_template_slug( $id );
	if ( $tpl && false !== strpos( (string) $tpl, 'elementor' ) ) {
		delete_post_meta( $id, '_wp_page_template' );
	}
	++$n;
	WP_CLI::log( "cleared:$slug:$id" );
}

$front = (int) get_option( 'page_on_front' );
if ( $front ) {
	foreach ( $keys as $key ) {
		delete_post_meta( $front, $key );
	}
	$opts = get_post_meta( $front, 'bi_options', true );
	if ( ! is_array( $opts ) ) {
		$opts = [];
	}
	$opts['force_theme_default'] = 1;
	update_post_meta( $front, 'bi_options', $opts );
	WP_CLI::log( "cleared:front:$front" );
}

WP_CLI::success( "NGI Elementor clear complete ($n pages)" );
