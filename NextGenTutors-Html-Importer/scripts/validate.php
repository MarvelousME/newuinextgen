<?php
/**
 * RHI smoke validation (run outside WordPress).
 *
 * Usage: php scripts/validate.php
 *
 * @package RevampHtmlImporter
 */

$root   = dirname( __DIR__ );
$errors = 0;

echo "RHI validate\n";

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

foreach ( $files as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}
	$path = $file->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$out  = [];
	$code = 0;
	exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1', $out, $code );
	if ( 0 !== $code ) {
		echo "FAIL lint: {$path}\n";
		echo implode( "\n", $out ) . "\n";
		++$errors;
	}
}

$required = [
	'revamp-html-importer.php',
	'includes/class-rhi-plugin.php',
	'includes/class-rhi-scanner.php',
	'includes/class-rhi-source-resolver.php',
	'includes/class-rhi-page-matcher.php',
	'includes/class-rhi-importer.php',
	'includes/class-rhi-html-parser.php',
	'includes/class-rhi-sanitizer.php',
	'includes/class-rhi-rollback.php',
	'includes/class-rhi-media-importer.php',
	'admin/class-rhi-admin.php',
	'assets/admin.js',
	'assets/admin.css',
];

foreach ( $required as $rel ) {
	if ( ! is_file( $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel ) ) ) {
		echo "FAIL missing: {$rel}\n";
		++$errors;
	}
}

$matcher = file_get_contents( $root . '/includes/class-rhi-page-matcher.php' );
if ( false === strpos( $matcher, 'filename_slug_map' ) || false === strpos( $matcher, 'index.html' ) ) {
	echo "FAIL page matcher must map index.html\n";
	++$errors;
}

$main = file_get_contents( $root . '/revamp-html-importer.php' );
if ( false === strpos( $main, "define( 'RHI_VERSION', '1.0.1' )" ) ) {
	echo "FAIL expected RHI_VERSION 1.0.1\n";
	++$errors;
}

$admin = file_get_contents( $root . '/admin/class-rhi-admin.php' );
if ( false !== strpos( $admin, 'C:\\\\Users\\\\marvi' ) ) {
	echo "FAIL admin must not hardcode Windows source path\n";
	++$errors;
}

$resolver = file_get_contents( $root . '/includes/class-rhi-source-resolver.php' );
if ( false === strpos( $resolver, 'ngt-html-source' ) ) {
	echo "FAIL source resolver must support Docker mount\n";
	++$errors;
}

if ( $errors > 0 ) {
	echo "\n{$errors} error(s)\n";
	exit( 1 );
}

$test_out  = [];
$test_code = 0;
exec( 'php ' . escapeshellarg( $root . '/tests/run.php' ) . ' 2>&1', $test_out, $test_code );
if ( 0 !== $test_code ) {
	echo implode( "\n", $test_out ) . "\n";
	exit( 1 );
}

echo "OK — all checks passed\n";
exit( 0 );
