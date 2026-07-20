<?php
require '/var/www/html/wp-load.php';
switch_theme( 'nextgentutors-beyondinfinity' );
$t = wp_get_theme();
echo 'theme=' . $t->get( 'Name' ) . ' stylesheet=' . $t->get_stylesheet() . PHP_EOL;
$r = NGC_Business_Profile::apply( true );
echo 'biz=' . wp_json_encode( $r ) . PHP_EOL;
NGC_Roles::install();
echo "roles_installed\n";
NGC_Demo_Env::set_demo_mode( true );
$g = NGC_Demo_Seeder::seed( 'all' );
echo is_wp_error( $g ) ? ( 'seed_err=' . $g->get_error_message() ) : ( 'seed_ok=' . wp_json_encode( $g['bookings'] ?? [] ) );
echo PHP_EOL;
$v = NGC_Demo_Verifier::verify();
echo 'verify=' . ( ! empty( $v['ok'] ) ? 'PASS' : 'FAIL' ) . ' ' . wp_json_encode( $v['failures'] ?? [] ) . PHP_EOL;
echo 'status=' . wp_json_encode( NGC_Business_Profile::status() ) . PHP_EOL;
