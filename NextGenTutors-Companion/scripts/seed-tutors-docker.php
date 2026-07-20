<?php
if ( ! defined( 'NGC_ALLOW_DEMO_SEED' ) ) {
	define( 'NGC_ALLOW_DEMO_SEED', true );
}
if ( ! class_exists( 'NGC_Tutor_Seeder' ) ) {
	echo "FAIL: seeder missing\n";
	exit( 1 );
}
$result = NGC_Tutor_Seeder::ensure_seeded( true );
echo 'result=' . wp_json_encode( $result ) . "\n";
echo 'published=' . (int) NGC_Tutor_Seeder::published_count() . "\n";
exit( 0 );
