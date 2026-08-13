<?php
/**
 * Product provisioner unit tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * NGC_Product_Provisioner.
 */
class ProductProvisionerTest extends TestCase {

	public function test_definitions_include_online_1hr_sku() {
		$defs = NGC_Product_Provisioner::definitions();
		$this->assertNotEmpty( $defs );
		$keys = array_column( $defs, 'key' );
		$this->assertContains( 'ngt-online-1hr', $keys );
		$skus = array_column( $defs, 'sku' );
		$this->assertContains( 'NGT-ONLINE-1HR', $skus );
	}

	public function test_definitions_are_unique_by_key() {
		$defs = NGC_Product_Provisioner::definitions();
		$keys = array_column( $defs, 'key' );
		$this->assertSame( count( $keys ), count( array_unique( $keys ) ) );
	}

	public function test_meta_constants_stable() {
		$this->assertSame( '_ngt_product_key', NGC_Product_Provisioner::META_KEY );
		$this->assertSame( '_ngt_duration_minutes', NGC_Product_Provisioner::META_DURATION );
	}
}
