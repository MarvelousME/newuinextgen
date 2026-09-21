<?php
/**
 * Force-create 3d-scroll-test page + NGT3D rules (incl. text/motion).
 */
require '/var/www/html/wp-load.php';

delete_option( 'ngt_3d_demo_page_seed_v1' );

$page_id = NGT3D_Demo_Page::ensure_page();
NGT3D_Demo_Page::seed_rules( $page_id );
update_option( 'ngt_3d_demo_page_seed_v1', '2026-09-12-motion-text', false );

global $wpdb;
$table = $wpdb->prefix . 'ngt_3d_scroll_rules';
$count = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE enabled = 1 AND (page_id = %d OR page_slug = %s)",
		$page_id,
		'3d-scroll-test'
	)
);
$text = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE enabled = 1 AND page_slug = %s AND (animation_names LIKE %s OR animation_names LIKE %s OR animation_names LIKE %s)",
		'3d-scroll-test',
		'%gsapify-text%',
		'%text-%',
		'%fade-up%'
	)
);

echo "page_id={$page_id}\n";
echo "rules={$count}\n";
echo "textish={$text}\n";
echo 'url=' . get_permalink( $page_id ) . "\n";
