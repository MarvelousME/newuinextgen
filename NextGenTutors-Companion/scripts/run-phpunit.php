<?php
/**
 * Run PHPUnit when available (optional dev dependency).
 *
 * Usage: php scripts/run-phpunit.php
 *
 * @package NextGenCompanion
 */

$root = dirname( __DIR__ );
$bin  = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
$cfg  = $root . DIRECTORY_SEPARATOR . 'phpunit.xml.dist';

if ( ! is_file( $bin ) ) {
	echo "SKIP — run composer install in NextGenTutors-Companion for PHPUnit\n";
	exit( 0 );
}

passthru( escapeshellarg( $bin ) . ' -c ' . escapeshellarg( $cfg ), $code );
exit( (int) $code );
