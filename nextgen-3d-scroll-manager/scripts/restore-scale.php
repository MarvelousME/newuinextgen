<?php
require '/var/www/html/wp-load.php';
global $wpdb;
$t = $wpdb->prefix . 'ngt_3d_scroll_rules';
$row = $wpdb->get_row(
	"SELECT id, animation_options FROM {$t} WHERE enabled=1 AND target_selector='#hero' ORDER BY id DESC LIMIT 1",
	ARRAY_A
);
$opts = json_decode( (string) ( $row['animation_options'] ?? '{}' ), true );
if ( ! is_array( $opts ) ) {
	$opts = [];
}
$opts['scaleFrom'] = 0.82;
$wpdb->update( $t, [ 'animation_options' => wp_json_encode( $opts ) ], [ 'id' => (int) $row['id'] ] );
NGT3D_Rule_Repository::flush_all_caches();
echo "restored=0.82\n";
