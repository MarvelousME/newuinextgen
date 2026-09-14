<?php
$t0 = microtime( true );
require '/var/www/html/wp-load.php';
echo 'load=' . round( microtime( true ) - $t0, 3 ) . "\n";

$front = (int) get_option( 'page_on_front' );
$query = new WP_Query(
	[
		'page_id'     => $front,
		'post_status' => 'publish',
	]
);
$GLOBALS['wp_query']     = $query;
$GLOBALS['wp_the_query'] = $query;
if ( $query->have_posts() ) {
	$query->the_post();
}

// Mimic main query flags for is_front_page().
$GLOBALS['wp_query']->is_page       = true;
$GLOBALS['wp_query']->is_singular   = true;
$GLOBALS['wp_query']->is_home       = false;
$GLOBALS['wp_query']->is_front_page = true;

echo 'is_front=' . ( is_front_page() ? '1' : '0' ) . "\n";
echo 'kinetic=' . ( bi_use_kinetic_home() ? '1' : '0' ) . "\n";

$s = microtime( true );
ob_start();
get_header();
$header = ob_get_clean();
echo 'header=' . round( microtime( true ) - $s, 3 ) . 's bytes=' . strlen( $header ) . "\n";

$s = microtime( true );
ob_start();
bi_canonical_render_body(
	'home',
	static function () {
		bi_canonical_load_page_body( 'home' );
	}
);
$body = ob_get_clean();
echo 'body=' . round( microtime( true ) - $s, 3 ) . 's bytes=' . strlen( $body ) . "\n";

$s = microtime( true );
ob_start();
get_footer();
$footer = ob_get_clean();
echo 'footer=' . round( microtime( true ) - $s, 3 ) . 's bytes=' . strlen( $footer ) . "\n";
echo 'total=' . round( microtime( true ) - $t0, 3 ) . "s\n";
