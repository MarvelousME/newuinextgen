<?php
declare(strict_types=1);

namespace NGTBM\Tests\Unit;

use NGTBM\Domain\Authorization\RoleCatalog;
use NGTBM\Domain\Resource\ResourceCatalog;
use PHPUnit\Framework\TestCase;

final class RbacMatrixTest extends TestCase {

	public function test_roles_are_capability_bundles(): void {
		$m = RoleCatalog::access_matrix();
		$this->assertGreaterThanOrEqual( 9, count( $m['roles'] ) );
		$this->assertTrue( $m['matrix']['ngt_platform_admin']['ngt_cp_access'] );
		$this->assertFalse( $m['matrix']['ngt_support']['ngt_talent_configure'] ?? true );
	}

	public function test_talent_resource_permissions_are_granular(): void {
		$r = ResourceCatalog::get( 'talent-evaluation' );
		$this->assertIsArray( $r );
		$this->assertSame( 'ngt_talent_read', $r['permissions']['read'] );
		$this->assertSame( 'ngt_talent_evaluate', $r['permissions']['execute'] );
	}
}
