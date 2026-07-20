<?php
if ( function_exists( 'bi_sync_all_prototype_pages' ) ) {
	$r = bi_sync_all_prototype_pages();
} elseif ( function_exists( 'bi_sync_launch_pages' ) ) {
	$r = bi_sync_launch_pages();
} else {
	echo "sync functions missing\n";
	exit( 1 );
}

if ( is_wp_error( $r ) ) {
	echo $r->get_error_message() . "\n";
	exit( 1 );
}

echo wp_json_encode( $r ) . "\n";
