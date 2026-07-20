<?php
/**
 * Split catalog.css into per-kind CSS files (one-time / CI helper).
 *
 * Usage: php scripts/split-catalog-css.php
 */

$root    = dirname( __DIR__ );
$catalog = $root . '/ui-library/assets/css/components/catalog.css';
$kinds   = $root . '/ui-library/assets/css/kinds';

$content = file_get_contents( $catalog );
if ( false === $content ) {
	fwrite( STDERR, "Cannot read catalog.css\n" );
	exit( 1 );
}

$markers = array(
	'button'      => '/\/\* —— Buttons —— \*\/.*?(?=\/\* —— Text effects —— \*\/)/s',
	'text'        => '/\/\* —— Text effects —— \*\/.*?(?=\/\* —— Patterns \/ backgrounds —— \*\/)/s',
	'pattern'     => '/\/\* —— Patterns \/ backgrounds —— \*\/.*?(?=\/\* —— Cards —— \*\/)/s',
	'card'        => '/\/\* —— Cards —— \*\/.*?(?=\/\* —— Devices —— \*\/)/s',
	'device'      => '/\/\* —— Devices —— \*\/.*?(?=\/\* —— Progress —— \*\/)/s',
	'progress'    => '/\/\* —— Progress —— \*\/.*?(?=\/\* —— Lists \/ layouts —— \*\/)/s',
	'list'        => '/\/\* —— Lists \/ layouts —— \*\/.*?(?=\/\* —— Media —— \*\/)/s',
	'media'       => '/\/\* —— Media —— \*\/.*?(?=\/\* —— Interactive —— \*\/)/s',
	'interactive' => '/\/\* —— Interactive —— \*\/.*?(?=\.ngt-ui-blur-fade|@media \(prefers-reduced-motion)/s',
	'misc'        => '/(\.ngt-ui-blur-fade.*?(?=@media \(prefers-reduced-motion))/s',
);

foreach ( $markers as $kind => $pattern ) {
	if ( ! preg_match( $pattern, $content, $m ) ) {
		fwrite( STDERR, "WARN: no match for kind-$kind\n" );
		continue;
	}
	$block = trim( $m[0] );
	if ( 'map' === $kind ) {
		// map rules live inside media section in legacy file — extracted via media kind when present.
	}
	file_put_contents(
		$kinds . "/kind-$kind.css",
		"/* Kind: $kind — split from catalog.css */\n$block\n"
	);
	echo "Wrote kind-$kind.css\n";
}

// Extract map rules from media block if present in full catalog.
if ( preg_match( '/(\.ngt-ui-map[\s\S]*?)(?=\/\* —— Interactive)/', $content, $map_match ) ) {
	file_put_contents(
		$kinds . '/kind-map.css',
		"/* Kind: map — split from catalog.css */\n" . trim( $map_match[1] ) . "\n"
	);
	echo "Wrote kind-map.css\n";
}

$base = <<<'CSS'
/* NGT UI Catalog — base shell (kind rules in kind-*.css) */

.ngt-ui-comp {
  box-sizing: border-box;
  position: relative;
  color: var(--ngt-color-text, #111);
  font-family: "Source Sans 3", "Segoe UI", system-ui, sans-serif;
}
.ngt-ui-comp *,
.ngt-ui-comp *::before,
.ngt-ui-comp *::after { box-sizing: border-box; }

CSS;

$tail = <<<'CSS'

@media (prefers-reduced-motion: reduce) {
  .ngt-ui-comp *,
  .ngt-ui-comp *::before,
  .ngt-ui-comp *::after {
    animation: none !important;
    transition: none !important;
  }
}

@media (max-width: 640px) {
  .ngt-ui-media--code-comparison,
  .ngt-ui-list--bento-grid { grid-template-columns: 1fr; }
  .ngt-ui-bento__cell:nth-child(1) { grid-column: auto; }
}

CSS;

file_put_contents( $catalog, $base . $tail );
echo "Trimmed catalog.css\n";
