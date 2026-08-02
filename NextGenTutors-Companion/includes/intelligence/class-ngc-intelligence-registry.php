<?php
/**
 * Plugin / feature / KPI registration SDK.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry for Mission Control auto-discovery.
 */
final class NGC_Intelligence_Registry {

	public const OPTION = 'ngc_intelligence_registry';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_intelligence_register', [ __CLASS__, 'on_register_action' ], 10, 1 );
		add_action( 'init', [ __CLASS__, 'register_core_plugins' ], 20 );
	}

	/**
	 * @param array<string, mixed> $definition Plugin definition.
	 */
	public static function register_plugin( array $definition ) {
		$slug = sanitize_key( (string) ( $definition['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return;
		}
		$all = self::all();
		$all['plugins'][ $slug ] = [
			'slug'        => $slug,
			'name'        => sanitize_text_field( (string) ( $definition['name'] ?? $slug ) ),
			'version'     => sanitize_text_field( (string) ( $definition['version'] ?? '' ) ),
			'description' => sanitize_textarea_field( (string) ( $definition['description'] ?? '' ) ),
			'features'    => array_values( array_map( 'sanitize_key', (array) ( $definition['features'] ?? [] ) ) ),
			'kpis'        => array_values( (array) ( $definition['kpis'] ?? [] ) ),
			'health'      => is_array( $definition['health'] ?? null ) ? $definition['health'] : [],
			'registered_at' => gmdate( 'c' ),
		];
		update_option( self::OPTION, $all, false );
	}

	/**
	 * @param array<string, mixed> $definition Feature definition.
	 */
	public static function on_register_action( $definition ) {
		if ( is_array( $definition ) ) {
			self::register_plugin( $definition );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function all() {
		$stored = get_option( self::OPTION, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		if ( empty( $stored['plugins'] ) || ! is_array( $stored['plugins'] ) ) {
			$stored['plugins'] = [];
		}
		return $stored;
	}

	/**
	 * Register first-party stack on boot.
	 */
	public static function register_core_plugins() {
		$core = [
			[
				'slug'        => 'companion',
				'name'        => 'NextGen Companion',
				'version'     => defined( 'NGC_VERSION' ) ? NGC_VERSION : '',
				'description' => 'Domain layer — bookings, payments, workflows, AI',
				'features'    => [ 'bookings', 'payments', 'matching', 'workflows', 'ai', 'demo' ],
				'kpis'        => [ 'bookings_today', 'revenue_today', 'errors_24h', 'pending_matches' ],
			],
			[
				'slug'        => 'mission-control',
				'name'        => 'Mission Control',
				'version'     => defined( 'NGTMC_VERSION' ) ? NGTMC_VERSION : '',
				'description' => 'Operational control plane',
				'features'    => [ 'orchestrator', 'overrides', 'intelligence' ],
				'kpis'        => [ 'system_health' ],
			],
			[
				'slug'        => 'automation-hub',
				'name'        => 'Automation Hub',
				'version'     => defined( 'NGT_HUB_VERSION' ) ? NGT_HUB_VERSION : '',
				'description' => 'Event triggers, RTM, workflows bridge',
				'features'    => [ 'rtm', 'workflows', 'notifications' ],
				'kpis'        => [],
			],
			[
				'slug'        => 'ai-integration',
				'name'        => 'AI Integration',
				'version'     => defined( 'NGTAI_VERSION' ) ? NGTAI_VERSION : '',
				'description' => 'Agent outbox bridge',
				'features'    => [ 'outbox', 'approvals' ],
				'kpis'        => [],
			],
			[
				'slug'        => 'plugin-manager',
				'name'        => 'Plugin Manager',
				'version'     => defined( 'NGCPM_VERSION' ) ? NGCPM_VERSION : '',
				'description' => 'Fleet install and registry',
				'features'    => [ 'install', 'health' ],
				'kpis'        => [],
			],
			[
				'slug'        => 'theme',
				'name'        => 'BeyondInfinity Theme',
				'version'     => defined( 'BI_VERSION' ) ? BI_VERSION : '',
				'description' => 'Presentation layer',
				'features'    => [ 'templates', 'motion', 'ui-library' ],
				'kpis'        => [],
			],
		];
		foreach ( $core as $def ) {
			self::register_plugin( $def );
		}
		/**
		 * Allow plugins to register intelligence metadata.
		 *
		 * @param NGC_Intelligence_Registry $registry Registry instance.
		 */
		do_action( 'ngc_intelligence_register_plugins', self::class );
	}
}
