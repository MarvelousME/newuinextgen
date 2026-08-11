<?php
declare(strict_types=1);

namespace NGTBM\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Ensures Control Plane does not couple to Companion internals.
 */
final class NoCompanionInternalsTest extends TestCase {

	public function test_no_companion_include_requires(): void {
		$root = dirname( __DIR__, 2 ) . '/src';
		$rii  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root ) );
		foreach ( $rii as $file ) {
			if ( ! $file->isFile() || substr( $file->getFilename(), -4 ) !== '.php' ) {
				continue;
			}
			$code = (string) file_get_contents( $file->getPathname() );
			$this->assertStringNotContainsString( 'NextGenTutors-Companion/includes', $code, $file->getPathname() );
			$this->assertDoesNotMatchRegularExpression( '/require(_once)?\s+[\'"].*Companion/i', $code, $file->getPathname() );
		}
	}
}
