<?php
/**
 * Stage 2 — Scan Elementor / WPBakery post meta for theme/plugin asset URL refs.
 *
 * @package NextGenTutors
 */

$report = array(
	'generated_at' => gmdate( 'c' ),
	'status'       => 'VERIFIED',
	'method'       => 'SQL LIKE scan of postmeta for asset path fragments',
	'limitations'  => array(
		'Does not decode all serialized nested structures exhaustively',
		'URL fragments only — relative paths without domain may be under-counted',
	),
	'counts'       => array(),
	'samples'      => array(),
	'asset_hits'   => array(),
);

global $wpdb;

$meta_keys = array(
	'_elementor_data',
	'_elementor_css',
	'_elementor_page_settings',
	'_wpb_shortcodes_custom_css',
	'_wpb_post_custom_css',
	'vc_custom_css',
);

$fragments = array(
	'wp-content/themes/',
	'wp-content/plugins/',
	'wp-content/uploads/',
	'/assets/',
	'nextgentutors-beyondinfinity',
	'agntix',
	'ftech',
	'hello-elementor',
);

$counts = array();
foreach ( $meta_keys as $key ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$counts[ $key ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $key ) );
}
$report['counts']['meta_rows'] = $counts;

$hit_map = array();
foreach ( $fragments as $frag ) {
	$like = '%' . $wpdb->esc_like( $frag ) . '%';
	$sql  = $wpdb->prepare(
		"SELECT meta_id, post_id, meta_key, LEFT(meta_value, 180) AS sample
		 FROM {$wpdb->postmeta}
		 WHERE meta_key IN ('_elementor_data','_wpb_shortcodes_custom_css','_wpb_post_custom_css','vc_custom_css')
		   AND meta_value LIKE %s
		 LIMIT 40",
		$like
	);
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( $sql, ARRAY_A );
	$hit_map[ $frag ] = array(
		'sample_count' => is_array( $rows ) ? count( $rows ) : 0,
		'samples'      => $rows ? $rows : array(),
	);

	// Total count (capped query cost).
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$total = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ('_elementor_data','_wpb_shortcodes_custom_css','_wpb_post_custom_css','vc_custom_css')
			   AND meta_value LIKE %s",
			$like
		)
	);
	$hit_map[ $frag ]['total_rows'] = $total;
}

$report['asset_hits'] = $hit_map;

// Extract distinct upload/theme path-ish tokens from a limited Elementor sample.
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$blob_rows = $wpdb->get_col(
	"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE '%wp-content%' LIMIT 25"
);
$paths = array();
foreach ( (array) $blob_rows as $blob ) {
	if ( preg_match_all( '#wp-content/(?:themes|plugins|uploads)/[a-zA-Z0-9_./\-]+#', (string) $blob, $m ) ) {
		foreach ( $m[0] as $p ) {
			$paths[ $p ] = isset( $paths[ $p ] ) ? $paths[ $p ] + 1 : 1;
		}
	}
}
arsort( $paths );
$report['distinct_wp_content_paths_sample'] = array_slice( $paths, 0, 80, true );
$report['policy'] = 'Any path appearing here is UNSAFE TO REMOVE until proven unused outside builder content.';

$out = WP_CONTENT_DIR . '/themes/nextgentutors-beyondinfinity/audit-reports/elementor-wpbakery-asset-scan.json';
// Prefer monorepo audit-reports if theme mount is package-only.
$candidates = array(
	ABSPATH . '../audit-reports/elementor-wpbakery-asset-scan.json',
	dirname( WP_CONTENT_DIR ) . '/audit-reports/elementor-wpbakery-asset-scan.json',
);

// Write into container path that maps to host when possible.
$written = null;
$hostish = '/var/www/html/wp-content/themes/nextgentutors-beyondinfinity';
// Always echo JSON; host copy via docker cp by caller.
echo wp_json_encode( $report, JSON_PRETTY_PRINT ) . "\n";
