<?php
// Minimal WP stubs for CLI scan test.
if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		return strtolower( preg_replace( '/[^a-z0-9]+/', '-', $title ) );
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $message;
		public function __construct( $code, $message ) { $this->message = $message; }
		public function get_error_message() { return $this->message; }
	}
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function get_page_by_path() { return null; }
function get_page_by_title() { return null; }
function get_option() { return 0; }
function get_post_meta() { return ''; }

require dirname( __DIR__ ) . '/includes/class-rhi-logger.php';
require dirname( __DIR__ ) . '/includes/class-rhi-sanitizer.php';
require dirname( __DIR__ ) . '/includes/class-rhi-page-matcher.php';
require dirname( __DIR__ ) . '/includes/class-rhi-css-adoption.php';
require dirname( __DIR__ ) . '/includes/class-rhi-html-parser.php';
require dirname( __DIR__ ) . '/includes/class-rhi-scanner.php';

$dir = 'c:/Users/marvi/Music/REVAMP/webpages-content';
$result = RHI_Scanner::scan( $dir );
if ( is_wp_error( $result ) ) {
	echo 'ERROR: ' . $result->get_error_message() . "\n";
	exit( 1 );
}
echo count( $result ) . " files\n";
foreach ( $result as $f ) {
	echo $f['relative_path'] . ' | ' . $f['action'] . ' | ' . $f['confidence'] . '% | ' . $f['suggested_slug'] . "\n";
}
