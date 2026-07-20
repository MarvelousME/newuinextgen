<?php
/**
 * Restore files from audit-reports/quarantine using the latest manifest.
 *
 * Usage (from solution root):
 *   php scripts/restore-quarantined-files.php
 *   php scripts/restore-quarantined-files.php --dry-run
 *
 * @package NGT
 */

declare(strict_types=1);

$root = dirname( __DIR__ );
$quarantine = $root . DIRECTORY_SEPARATOR . 'audit-reports' . DIRECTORY_SEPARATOR . 'quarantine';
$manifests = glob( $quarantine . DIRECTORY_SEPARATOR . 'manifest-*.json' ) ?: array();

$dry = in_array( '--dry-run', $argv, true );

if ( ! $manifests ) {
	fwrite( STDOUT, "Quarantine is empty. Nothing to restore.\n" );
	exit( 0 );
}

usort(
	$manifests,
	static function ( $a, $b ) {
		return filemtime( $b ) <=> filemtime( $a );
	}
);

$manifest_path = $manifests[0];
$data = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $data ) || empty( $data['files'] ) || ! is_array( $data['files'] ) ) {
	fwrite( STDERR, "Invalid manifest: {$manifest_path}\n" );
	exit( 1 );
}

$restored = 0;
foreach ( $data['files'] as $row ) {
	$from = isset( $row['quarantine_path'] ) ? (string) $row['quarantine_path'] : '';
	$to   = isset( $row['original_path'] ) ? (string) $row['original_path'] : '';
	if ( '' === $from || '' === $to || ! is_readable( $from ) ) {
		continue;
	}
	if ( $dry ) {
		fwrite( STDOUT, "[dry-run] {$from} -> {$to}\n" );
		++$restored;
		continue;
	}
	$dir = dirname( $to );
	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0755, true );
	}
	if ( ! copy( $from, $to ) ) {
		fwrite( STDERR, "Failed: {$from}\n" );
		exit( 1 );
	}
	fwrite( STDOUT, "Restored: {$to}\n" );
	++$restored;
}

fwrite( STDOUT, "Done. Items: {$restored}. Manifest: {$manifest_path}\n" );
exit( 0 );
