<?php
/**
 * One-shot: rebuild primary nav + force public footer_style=default.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once get_template_directory() . '/inc/nav-menu.php';

$synced = bi_sync_launch_nav( true );
update_option( 'bi_nav_public_schema', bi_nav_public_schema_version(), false );

// Global theme option: marketing footer (not minimal).
if ( function_exists( 'bi_update_theme_option' ) ) {
	bi_update_theme_option( 'footer_style', 'default' );
} else {
	$opts = get_option( 'bi_theme_options', [] );
	if ( ! is_array( $opts ) ) {
		$opts = [];
	}
	$opts['footer_style'] = 'default';
	update_option( 'bi_theme_options', $opts, false );
}

// Front page post meta override if present.
$front_id = (int) get_option( 'page_on_front' );
if ( $front_id > 0 ) {
	delete_post_meta( $front_id, 'bi_footer_style' );
	delete_post_meta( $front_id, '_bi_footer_style' );
	update_post_meta( $front_id, 'footer_style', 'default' );
}

$id = (int) ( $synced['primary'] ?? 0 );
echo 'menu=' . $id . PHP_EOL;
$items = $id ? wp_get_nav_menu_items( $id ) : [];
foreach ( (array) $items as $it ) {
	$depth = (int) $it->menu_item_parent > 0 ? '  ' : '';
	echo $depth . $it->title . PHP_EOL;
}
echo 'footer=' . (int) ( $synced['footer'] ?? 0 ) . PHP_EOL;
echo 'footer_option=' . ( function_exists( 'bi_get_footer_style' ) ? bi_get_footer_style() : 'n/a' ) . PHP_EOL;
