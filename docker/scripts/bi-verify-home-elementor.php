<?php
require '/var/www/html/wp-load.php';
$f = (int) get_option( 'page_on_front' );
echo 'FRONT=' . $f . PHP_EOL;
echo 'TPL=' . get_page_template_slug( $f ) . PHP_EOL;
echo 'EL=' . ( get_post_meta( $f, '_elementor_edit_mode', true ) ?: 'none' ) . PHP_EOL;
$a = get_page_by_path( 'about' );
echo 'ABOUT_TPL=' . get_page_template_slug( $a->ID ) . PHP_EOL;
echo 'ABOUT_EL=' . ( get_post_meta( $a->ID, '_elementor_edit_mode', true ) ?: 'none' ) . PHP_EOL;
$c = get_page_by_path( 'contact' );
$data = get_post_meta( $c->ID, '_elementor_data', true );
$has = is_string( $data ) && false !== strpos( $data, 'ngc_contact_support_form' );
echo 'CONTACT_SC=' . ( $has ? '1' : '0' ) . PHP_EOL;
