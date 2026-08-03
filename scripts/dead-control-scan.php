<?php
/**
 * Dead-control scan for Companion admin PHP.
 *
 * Scans for href="#", javascript:void, render_placeholder, and "coming soon".
 * Writes JSON report to delivery/evidence/dead-control-scan-latest.json
 *
 * Usage (from repo root):
 *   php scripts/dead-control-scan.php
 *
 * @package NextGenCompanion
 */

declare(strict_types=1);

$repo_root = dirname( __DIR__ );
$scan_root = $repo_root . DIRECTORY_SEPARATOR . 'NextGenTutors-Companion' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'admin';
$out_file  = $repo_root . DIRECTORY_SEPARATOR . 'delivery' . DIRECTORY_SEPARATOR . 'evidence' . DIRECTORY_SEPARATOR . 'dead-control-scan-latest.json';

$patterns = [
	'href_hash'           => '/href\s*=\s*["\']#["\']/i',
	'javascript_void'     => '/javascript\s*:\s*void/i',
	'render_placeholder'  => '/render_placeholder/i',
	'coming_soon'         => '/coming\s+soon/i',
];

$findings = [];
$files_scanned = 0;
$files_with_hits = 0;

if ( ! is_dir( $scan_root ) ) {
	fwrite( STDERR, "Scan root missing: {$scan_root}\n" );
	exit( 2 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $scan_root, FilesystemIterator::SKIP_DOTS )
);

foreach ( $iterator as $file_info ) {
	/** @var SplFileInfo $file_info */
	if ( ! $file_info->isFile() ) {
		continue;
	}
	if ( strtolower( (string) $file_info->getExtension() ) !== 'php' ) {
		continue;
	}

	$path = $file_info->getPathname();
	++$files_scanned;

	$contents = @file_get_contents( $path );
	if ( false === $contents ) {
		continue;
	}

	$lines = preg_split( "/\r\n|\n|\r/", $contents );
	if ( ! is_array( $lines ) ) {
		continue;
	}

	$file_hits = [];
	foreach ( $lines as $idx => $line ) {
		$line_no = $idx + 1;
		foreach ( $patterns as $kind => $regex ) {
			if ( ! preg_match( $regex, $line ) ) {
				continue;
			}
			$file_hits[] = [
				'kind'    => $kind,
				'line'    => $line_no,
				'snippet' => trim( substr( $line, 0, 200 ) ),
			];
		}
	}

	if ( ! $file_hits ) {
		continue;
	}

	++$files_with_hits;
	$rel = str_replace( '\\', '/', substr( $path, strlen( $repo_root ) + 1 ) );
	$findings[] = [
		'file'  => $rel,
		'hits'  => $file_hits,
		'count' => count( $file_hits ),
	];
}

$by_kind = [];
foreach ( array_keys( $patterns ) as $kind ) {
	$by_kind[ $kind ] = 0;
}
foreach ( $findings as $f ) {
	foreach ( $f['hits'] as $hit ) {
		$kind = $hit['kind'];
		if ( ! isset( $by_kind[ $kind ] ) ) {
			$by_kind[ $kind ] = 0;
		}
		++$by_kind[ $kind ];
	}
}

$report = [
	'generated_at'     => gmdate( 'c' ),
	'scan_root'        => 'NextGenTutors-Companion/includes/admin',
	'patterns'         => array_keys( $patterns ),
	'files_scanned'    => $files_scanned,
	'files_with_hits'  => $files_with_hits,
	'total_hits'       => array_sum( $by_kind ),
	'hits_by_kind'     => $by_kind,
	'findings'         => $findings,
];

$out_dir = dirname( $out_file );
if ( ! is_dir( $out_dir ) && ! mkdir( $out_dir, 0775, true ) && ! is_dir( $out_dir ) ) {
	fwrite( STDERR, "Could not create output directory: {$out_dir}\n" );
	exit( 2 );
}

$json = json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
if ( false === $json ) {
	fwrite( STDERR, "JSON encode failed\n" );
	exit( 2 );
}

if ( false === file_put_contents( $out_file, $json . "\n" ) ) {
	fwrite( STDERR, "Could not write: {$out_file}\n" );
	exit( 2 );
}

$rel_out = str_replace( '\\', '/', substr( $out_file, strlen( $repo_root ) + 1 ) );
echo "Dead-control scan complete\n";
echo "  files_scanned: {$files_scanned}\n";
echo "  files_with_hits: {$files_with_hits}\n";
echo "  total_hits: {$report['total_hits']}\n";
foreach ( $by_kind as $kind => $n ) {
	echo "  {$kind}: {$n}\n";
}
echo "  wrote: {$rel_out}\n";

exit( 0 );
