<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\WordPress;

use NGTBM\Domain\Authorization\CapabilityCatalog;
use NGTBM\Domain\Authorization\RoleCatalog;
use NGTBM\Infrastructure\Persistence\Schema;

/**
 * Activation / deactivation.
 */
final class Activator {

	public static function activate(): void {
		Schema::install();
		CapabilityCatalog::register_caps();
		RoleCatalog::ensure_roles();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
