<?php
/**
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * WooCommerce catalog helpers.
 */
class WooCommerceCatalogTest extends TestCase {

	public function test_parse_category_names_splits_csv() {
		$names = NGC_WooCommerce_Catalog::parse_category_names( 'Online Tutoring, In-Person Tutoring' );
		$this->assertCount( 2, $names );
		$this->assertSame( 'Online Tutoring', $names[0] );
	}

	public function test_parse_category_names_empty() {
		$this->assertSame( [], NGC_WooCommerce_Catalog::parse_category_names( '' ) );
	}
}
