<?php
declare(strict_types=1);

namespace NGTBM\Domain\Subsystem;

/**
 * In-memory subsystem registry for the Control Plane.
 */
final class SubsystemRegistry {

	/** @var array<string,SubsystemDefinition> */
	private array $items = [];

	/**
	 * @param SubsystemDefinition|array<string,mixed> $definition
	 */
	public function register( $definition ): void {
		if ( is_array( $definition ) ) {
			$definition = SubsystemDefinition::from_array( $definition );
		}
		if ( ! $definition instanceof SubsystemDefinition || $definition->id === '' ) {
			return;
		}
		$this->items[ $definition->id ] = $definition;
	}

	public function get( string $id ): ?SubsystemDefinition {
		return $this->items[ $id ] ?? null;
	}

	/**
	 * @return list<SubsystemDefinition>
	 */
	public function all(): array {
		return array_values( $this->items );
	}

	/**
	 * @return list<array<string,mixed>>
	 */
	public function to_list(): array {
		return array_map( static fn( SubsystemDefinition $d ) => $d->to_array(), $this->all() );
	}

	public function set_enabled( string $id, bool $enabled ): ?SubsystemDefinition {
		$current = $this->get( $id );
		if ( ! $current ) {
			return null;
		}
		$updated = new SubsystemDefinition(
			$current->id,
			$current->name,
			$current->icon,
			$current->category,
			$current->capabilities,
			$current->screens,
			$current->resources,
			$current->config_schema,
			$current->depends_on,
			$current->provides,
			$enabled ? $current->status : 'offline',
			$current->legacy_admin_url,
			$enabled,
		);
		$this->items[ $id ] = $updated;
		$store              = get_option( 'ngtbm_subsystem_enabled', [] );
		if ( ! is_array( $store ) ) {
			$store = [];
		}
		$store[ $id ] = $enabled;
		update_option( 'ngtbm_subsystem_enabled', $store, false );
		return $updated;
	}
}
