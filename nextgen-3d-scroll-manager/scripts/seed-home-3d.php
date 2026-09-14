<?php
/**
 * Force-reseed home-3d curated map (includes text/motion rules).
 */
require '/var/www/html/wp-load.php';

delete_option( 'ngt_3d_home_3d_seed_v1' );

$page_id = NGT3D_Home_3d_Page::ensure_page();
NGT3D_Home_3d_Page::seed_rules( $page_id );
update_option( 'ngt_3d_home_3d_seed_v1', '2026-09-12-motion-text', false );

global $wpdb;
$table = $wpdb->prefix . 'ngt_3d_scroll_rules';
$rows  = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT target_selector, animation_names FROM {$table} WHERE enabled = 1 AND (page_id = %d OR page_slug = %s) ORDER BY sort_order ASC",
		$page_id,
		'home-3d'
	),
	ARRAY_A
);

echo "page_id={$page_id}\n";
echo 'rules=' . count( $rows ) . "\n";
echo 'url=' . get_permalink( $page_id ) . "\n";
foreach ( $rows as $r ) {
	echo $r['target_selector'] . ' => ' . $r['animation_names'] . "\n";
}
