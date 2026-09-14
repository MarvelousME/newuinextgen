<?php
/**
 * One-shot NGT3D settings chain probe (CLI).
 */
require '/var/www/html/wp-load.php';

global $wpdb;
$t = $wpdb->prefix . 'ngt_3d_scroll_rules';

$row = $wpdb->get_row(
	"SELECT id, animation_options FROM {$t} WHERE enabled=1 AND target_selector='#hero' ORDER BY id DESC LIMIT 1",
	ARRAY_A
);

if ( ! $row ) {
	fwrite( STDERR, "NO_HERO_RULE\n" );
	exit( 1 );
}

$opts = json_decode( (string) $row['animation_options'], true );
if ( ! is_array( $opts ) ) {
	$opts = [];
}

$before = $opts['scaleFrom'] ?? null;
$opts['scaleFrom'] = 0.60;
$wpdb->update(
	$t,
	[ 'animation_options' => wp_json_encode( $opts ) ],
	[ 'id' => (int) $row['id'] ]
);
NGT3D_Rule_Repository::flush_all_caches();

echo "before={$before}\n";
echo "updated_id={$row['id']}\n";
echo "scaleFrom=0.60\n";
echo 'flag=' . get_option( 'ngt_3d_showcase_home_map_v1' ) . "\n";
echo 'enabled_count=' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE enabled=1" ) . "\n";
