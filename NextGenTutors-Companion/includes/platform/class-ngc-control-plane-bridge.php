<?php
/**
 * Thin Control Plane registration adapter — no domain logic moved.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Talent / Memory / Platform into Beyond Measure via public action.
 */
final class NGC_Control_Plane_Bridge {

	public static function init(): void {
		add_action( 'ngt_control_plane/boot', [ __CLASS__, 'register' ], 20, 1 );
		add_action( 'admin_notices', [ __CLASS__, 'maybe_banner' ] );
	}

	/**
	 * @param mixed $registry Optional Beyond Measure registry object.
	 */
	public static function register( $registry = null ): void {
		$talent_url = admin_url( 'admin.php?page=ngc-talent-intelligence' );
		$memory_url = admin_url( 'admin.php?page=ngc-memory-center' );
		$kernel_url = admin_url( 'admin.php?page=ngc-platform-kernel' );

		$defs = [
			[
				'id'             => 'ngt-talent-intelligence',
				'name'           => 'Talent Intelligence',
				'icon'           => 'brain',
				'category'       => 'AI & Intelligence',
				'capabilities'   => [ 'talent.match.evaluate', 'talent.match.explain', 'talent.rank', 'talent.health' ],
				'screens'        => [
					[ 'id' => 'dashboard', 'label' => 'Overview' ],
					[ 'id' => 'evaluations', 'label' => 'Evaluations' ],
					[ 'id' => 'configuration', 'label' => 'Configuration' ],
				],
				'resources'      => [ [ 'id' => 'talent-evaluation' ] ],
				'dependsOn'      => [ 'companion' ],
				'provides'       => [ 'talent.match.evaluate', 'talent.match.explain', 'talent.rank' ],
				'status'         => 'healthy',
				'legacyAdminUrl' => $talent_url,
				'enabled'        => true,
			],
			[
				'id'             => 'ngt-agent-memory',
				'name'           => 'Agent Memory',
				'icon'           => 'database',
				'category'       => 'AI & Intelligence',
				'capabilities'   => [ 'memory.retrieve', 'memory.write', 'memory.health' ],
				'dependsOn'      => [ 'companion' ],
				'provides'       => [ 'memory.retrieve', 'memory.write' ],
				'status'         => 'healthy',
				'legacyAdminUrl' => $memory_url,
				'enabled'        => true,
			],
			[
				'id'             => 'ngt-platform-kernel',
				'name'           => 'Platform Kernel',
				'icon'           => 'server',
				'category'       => 'Platform',
				'capabilities'   => [ 'platform.capability.invoke' ],
				'dependsOn'      => [ 'companion' ],
				'provides'       => [ 'platform.capability.invoke' ],
				'status'         => 'healthy',
				'legacyAdminUrl' => $kernel_url,
				'enabled'        => true,
			],
		];

		foreach ( $defs as $def ) {
			do_action( 'ngt_control_plane/register_subsystem', $def );
			if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
				$registry->register( $def );
			}
		}
	}

	public static function maybe_banner(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : '';
		if ( ! in_array( $page, [ 'ngc-talent-intelligence', 'ngc-memory-center', 'ngc-platform-kernel' ], true ) ) {
			return;
		}
		if ( ! defined( 'NGTBM_VERSION' ) ) {
			return;
		}
		$url = admin_url( 'admin.php?page=ngtbm-beyond-measure' );
		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'Prefer the NextGenTutors Beyond Measure Control Plane for day-to-day operations.', 'nextgencompanion' );
		echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Open Beyond Measure', 'nextgencompanion' ) . '</a>';
		echo '</p></div>';
	}
}
