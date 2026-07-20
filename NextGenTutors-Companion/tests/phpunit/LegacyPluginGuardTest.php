<?php
/**
 * PHPUnit tests for NGC_Legacy_Plugin_Guard.
 *
 * @package NextGenCompanion
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Guard deny matrix with stub plugin headers.
 */
final class LegacyPluginGuardTest extends TestCase {

	public function test_denies_core_basename(): void {
		$this->assertTrue( NGC_Legacy_Plugin_Guard::is_denied( 'nextgen-tutors-core/nextgen-tutors-core.php' ) );
	}

	public function test_denies_importer_folder_prefix(): void {
		$this->assertTrue( NGC_Legacy_Plugin_Guard::is_denied( 'nextgen-tutors-importer/nextgen-tutors-importer.php' ) );
	}

	public function test_allows_companion(): void {
		$this->assertFalse( NGC_Legacy_Plugin_Guard::is_denied( 'NextGenTutors-Companion/nextgencompanion.php' ) );
	}

	public function test_allows_woocommerce(): void {
		$this->assertFalse( NGC_Legacy_Plugin_Guard::is_denied( 'woocommerce/woocommerce.php' ) );
	}

	public function test_denies_by_text_domain(): void {
		$this->assertTrue( NGC_Legacy_Plugin_Guard::is_denied( 'custom-folder/custom.php' ) );
	}

	public function test_denies_legacy_nextgen_tutors_name(): void {
		$this->assertTrue( NGC_Legacy_Plugin_Guard::is_denied( 'legacy/legacy.php' ) );
	}

	public function test_denies_exact_plugin_name_core(): void {
		$this->assertTrue( NGC_Legacy_Plugin_Guard::is_denied( 'vendor/ngt-core.php' ) );
	}
}
