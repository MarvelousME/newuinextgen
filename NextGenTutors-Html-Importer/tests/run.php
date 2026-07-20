<?php
/**
 * Lightweight unit tests for Html-Importer (no WordPress bootstrap).
 *
 * Usage: php tests/run.php
 *
 * @package RevampHtmlImporter
 */

$root   = dirname( __DIR__ );
$errors = 0;

function rhi_test_assert( $label, $ok ) {
	global $errors;
	if ( ! $ok ) {
		echo "FAIL: {$label}\n";
		++$errors;
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}

require_once $root . '/includes/class-rhi-page-matcher.php';

$map = RHI_Page_Matcher::filename_slug_map();
rhi_test_assert( 'index maps to home', isset( $map['index.html'] ) && 'home' === $map['index.html'] );
rhi_test_assert( 'privacy maps to privacy-policy', isset( $map['privacy.html'] ) && 'privacy-policy' === $map['privacy.html'] );
rhi_test_assert( 'skip list is array', is_array( RHI_Page_Matcher::skip_filenames() ) );

if ( $errors > 0 ) {
	echo "\n{$errors} test(s) failed\n";
	exit( 1 );
}

echo "OK — 3 unit tests passed\n";
exit( 0 );
