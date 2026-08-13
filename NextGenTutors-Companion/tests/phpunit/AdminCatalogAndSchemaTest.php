<?php
/**
 * Admin catalog + schema statement tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * Catalog screens and schema SQL.
 */
class AdminCatalogAndSchemaTest extends TestCase {

	public function test_screen_definitions_include_known_slugs() {
		require_once dirname( __DIR__, 2 ) . '/includes/admin/framework/class-ngc-admin-catalog.php';
		$screens = NGC_Admin_Catalog::screen_definitions();
		$this->assertIsArray( $screens );
		$this->assertGreaterThanOrEqual( 20, count( $screens ) );
		$slugs = array_column( $screens, 'slug' );
		$this->assertContains( 'ngtmc-mission-control', $slugs );
		$this->assertContains( 'ngt-edu-subjects', $slugs );
		$this->assertContains( 'ngc-applications', $slugs );
		$again = NGC_Admin_Catalog::screen_definitions();
		$this->assertSame( $screens, $again );
	}

	public function test_screens_php_require_twice_returns_array() {
		$path  = dirname( __DIR__, 2 ) . '/includes/admin/framework/screens.php';
		$first = require $path;
		$second = require $path;
		$this->assertIsArray( $first );
		$this->assertIsArray( $second );
		$this->assertSame( $first, $second );
	}

	public function test_catalog_loads_screens_with_require_not_require_once() {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/admin/framework/class-ngc-admin-catalog.php' );
		$this->assertStringContainsString( "require __DIR__ . '/screens.php'", $src );
		$this->assertStringNotContainsString( "require_once __DIR__ . '/screens.php'", $src );
	}

	public function test_schema_sql_hash_is_byte_stable() {
		$schema = dirname( __DIR__, 2 ) . '/includes/database/class-ngc-schema-statements.php';
		require_once $schema;
		$expected = trim( (string) file_get_contents( __DIR__ . '/fixtures/schema-sql.sha256' ) );
		$this->assertSame( $expected, NGC_Schema_Statements::canonical_sql_hash() );
		$sql    = NGC_Schema_Statements::create_sql( NGC_Schema_Statements::fixture_tables(), 'DEFAULT CHARSET utf8mb4' );
		$joined = implode( "\n", $sql );
		$this->assertStringContainsString( 'CREATE TABLE wp_ngc_bookings', $joined );
		$this->assertStringContainsString( 'amelia_booking_id', $joined );
		$this->assertStringContainsString( 'booking_reminder', $joined );
	}
}
