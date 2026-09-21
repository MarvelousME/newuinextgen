<?php
define( 'WP_USE_THEMES', false );
require '/var/www/html/wp-load.php';

$html = do_shortcode( '[nextgen_subjects]' );
preg_match_all( '/href="([^"]+)"/', (string) $html, $m );
foreach ( array_slice( $m[1], 0, 8 ) as $u ) {
	echo $u . PHP_EOL;
}

echo 'taxonomy=' . ( taxonomy_exists( 'subject' ) ? 'yes' : 'no' ) . PHP_EOL;
if ( class_exists( 'NGC_Subjects_CMS' ) ) {
	echo 'cms=' . count( NGC_Subjects_CMS::catalog() ) . PHP_EOL;
}
if ( function_exists( 'bi_get_subject_tracks' ) ) {
	echo 'tracks=' . count( bi_get_subject_tracks() ) . PHP_EOL;
}

$rows = NGSW_Catalog::resolve( '', true );
echo 'rows=' . count( $rows ) . PHP_EOL;
if ( $rows ) {
	echo 'first_url=' . $rows[0]['url'] . PHP_EOL;
	echo 'first_name=' . $rows[0]['name'] . PHP_EOL;
}
