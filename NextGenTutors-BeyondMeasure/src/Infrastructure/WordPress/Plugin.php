<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\WordPress;

use NGTBM\Domain\Authorization\CapabilityCatalog;
use NGTBM\Domain\Authorization\RoleCatalog;
use NGTBM\Domain\Subsystem\SubsystemRegistry;
use NGTBM\Infrastructure\Integrations\ArchitectureLoader;
use NGTBM\Infrastructure\Persistence\Schema;
use NGTBM\Infrastructure\REST\RestKernel;

/**
 * Plugin bootstrap.
 */
final class Plugin {

	private static ?self $instance = null;

	private SubsystemRegistry $subsystems;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		$this->subsystems = new SubsystemRegistry();
	}

	public function boot(): void {
		Schema::maybe_upgrade();
		CapabilityCatalog::register_caps();
		RoleCatalog::ensure_roles();

		add_action( 'init', [ $this, 'discover_subsystems' ], 40 );
		add_action( 'rest_api_init', [ RestKernel::class, 'register' ] );
		add_action( 'admin_menu', [ AdminMenu::class, 'register' ], 55 );
		add_action( 'admin_enqueue_scripts', [ Assets::class, 'enqueue' ] );

		// Public registration hook for other plugins.
		add_action( 'ngt_control_plane/register_subsystem', [ $this->subsystems, 'register' ], 10, 1 );
	}

	public function discover_subsystems(): void {
		ArchitectureLoader::hydrate( $this->subsystems );
		/**
		 * Fire after architecture JSON load so Companion adapters can enrich.
		 *
		 * @param SubsystemRegistry $registry Registry.
		 */
		do_action( 'ngt_control_plane/boot', $this->subsystems );
	}

	public function subsystems(): SubsystemRegistry {
		return $this->subsystems;
	}
}
