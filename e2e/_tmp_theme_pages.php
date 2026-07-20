<?php
require '/var/www/html/wp-load.php';
switch_theme( 'nextgentutors-beyondinfinity' );
echo 'theme=' . wp_get_theme()->get_stylesheet() . PHP_EOL;
if ( class_exists( 'NGC_Business_Profile' ) ) {
	NGC_Business_Profile::apply( true );
	echo 'biz=' . wp_json_encode( NGC_Business_Profile::status() ) . PHP_EOL;
}
$slugs = [ 'register', 'contact', 'become-a-tutor', 'find-a-tutor', 'pricing', 'about', 'login', 'privacy-policy' ];
foreach ( $slugs as $slug ) {
	$p = get_page_by_path( $slug );
	echo $slug . '=' . ( $p ? ( 'id:' . $p->ID . ' status:' . $p->post_status ) : 'MISSING' ) . PHP_EOL;
}
