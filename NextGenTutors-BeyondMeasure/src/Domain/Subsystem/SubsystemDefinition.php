<?php
declare(strict_types=1);

namespace NGTBM\Domain\Subsystem;

/**
 * Subsystem definition registered into the Control Plane.
 */
final class SubsystemDefinition {

	/**
	 * @param list<string>              $capabilities
	 * @param list<array<string,mixed>> $screens
	 * @param list<array<string,mixed>> $resources
	 * @param array<string,mixed>       $config_schema
	 * @param list<string>              $depends_on
	 * @param list<string>              $provides
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $icon = 'cube',
		public readonly string $category = 'General',
		public readonly array $capabilities = [],
		public readonly array $screens = [],
		public readonly array $resources = [],
		public readonly array $config_schema = [],
		public readonly array $depends_on = [],
		public readonly array $provides = [],
		public readonly string $status = 'healthy',
		public readonly string $legacy_admin_url = '',
		public readonly bool $enabled = true,
	) {}

	/**
	 * @param array<string,mixed> $data
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['id'] ?? '' ),
			(string) ( $data['name'] ?? '' ),
			(string) ( $data['icon'] ?? 'cube' ),
			(string) ( $data['category'] ?? 'General' ),
			array_values( (array) ( $data['capabilities'] ?? [] ) ),
			array_values( (array) ( $data['screens'] ?? [] ) ),
			array_values( (array) ( $data['resources'] ?? [] ) ),
			(array) ( $data['configSchema'] ?? $data['config_schema'] ?? [] ),
			array_values( (array) ( $data['dependsOn'] ?? $data['depends_on'] ?? [] ) ),
			array_values( (array) ( $data['provides'] ?? [] ) ),
			(string) ( $data['status'] ?? 'healthy' ),
			(string) ( $data['legacyAdminUrl'] ?? $data['legacy_admin_url'] ?? '' ),
			(bool) ( $data['enabled'] ?? true ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return [
			'id'             => $this->id,
			'name'           => $this->name,
			'icon'           => $this->icon,
			'category'       => $this->category,
			'capabilities'   => $this->capabilities,
			'screens'        => $this->screens,
			'resources'      => $this->resources,
			'configSchema'   => $this->config_schema,
			'dependsOn'      => $this->depends_on,
			'provides'       => $this->provides,
			'status'         => $this->status,
			'legacyAdminUrl' => $this->legacy_admin_url,
			'enabled'        => $this->enabled,
		];
	}
}
