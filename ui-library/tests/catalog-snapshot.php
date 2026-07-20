<?php
/**
 * Catalog render smoke + MD5 snapshots for top marketing slugs.
 *
 * Usage: php ui-library/tests/catalog-snapshot.php
 *
 * @package NGT_UI
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$top_slugs = array(
	'aurora-text',
	'android',
	'bento-grid',
	'number-ticker',
	'animated-list',
	'animated-beam',
	'shine-border',
	'ripple-button',
	'warp-background',
	'line-shadow-text',
	'morphing-text',
	'scroll-progress',
	'neon-gradient-card',
	'meteors',
	'grid-pattern',
	'globe',
	'shimmer-button',
	'particles',
	'confetti',
	'dock',
	'terminal',
	'typing-animation',
	'retro-grid',
	'iphone',
	'rainbow-button',
);

$dedicated = array(
	'magic-card',
	'border-beam',
	'income-calculator',
);

$snapshot_dir = __DIR__ . '/snapshots';
if ( ! is_dir( $snapshot_dir ) ) {
	mkdir( $snapshot_dir, 0777, true );
}

$update = in_array( '--update', $argv, true );
$errors = 0;

/**
 * @param string $label Assertion label.
 * @param bool   $ok    Result.
 */
function ngt_snapshot_assert( string $label, bool $ok ): void {
	global $errors;
	if ( ! $ok ) {
		echo "FAIL: {$label}\n";
		++$errors;
	}
}

$markers = array(
	'income-calculator' => 'data-ngt-income-calculator',
);

foreach ( array_merge( $top_slugs, $dedicated ) as $slug ) {
	$html = NGT_UI_Renderer::render( $slug );
	ngt_snapshot_assert( "{$slug} renders non-empty", strlen( $html ) > 40 );

	$marker = $markers[ $slug ] ?? 'data-ngt-ui="' . $slug . '"';
	ngt_snapshot_assert( "{$slug} has component marker", false !== strpos( $html, $marker ) );

	if ( in_array( $slug, $top_slugs, true ) ) {
		ngt_snapshot_assert( "{$slug} has data-ngt-kind", false !== strpos( $html, 'data-ngt-kind="' ) );
	}

	$hash     = md5( $html );
	$hashfile = $snapshot_dir . '/' . $slug . '.md5';

	if ( $update || ! is_file( $hashfile ) ) {
		file_put_contents( $hashfile, $hash . "\n" );
		echo "UPDATED snapshot: {$slug} ({$hash})\n";
		continue;
	}

	$expected = trim( (string) file_get_contents( $hashfile ) );
	ngt_snapshot_assert( "{$slug} snapshot hash", $hash === $expected );
}

if ( $errors > 0 ) {
	echo "\n{$errors} snapshot check(s) failed\n";
	exit( 1 );
}

$total = count( $top_slugs ) + count( $dedicated );
echo "OK — {$total} catalog snapshot checks passed\n";
exit( 0 );
