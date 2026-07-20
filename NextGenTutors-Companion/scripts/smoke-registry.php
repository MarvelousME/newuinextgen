<?php
if ( ! class_exists( 'NGC_Page_Forms_Registry' ) ) {
	echo "FAIL: NGC_Page_Forms_Registry missing\n";
	exit( 1 );
}
$r = NGC_Page_Forms_Registry::verify();
echo 'ok=' . ( ! empty( $r['ok'] ) ? 'yes' : 'no' ) . "\n";
echo 'pass=' . (int) ( $r['summary']['pass'] ?? 0 ) . "\n";
echo 'warn=' . (int) ( $r['summary']['warning'] ?? 0 ) . "\n";
echo 'fail=' . (int) ( $r['summary']['fail'] ?? 0 ) . "\n";
echo shortcode_exists( 'ngc_tutor_marketplace' ) ? "marketplace_shortcode=yes\n" : "marketplace_shortcode=no\n";
exit( empty( $r['ok'] ) ? 1 : 0 );
