<?php
/**
 * Guided repair suggestions and execution.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects fixable issues and runs safe repairs.
 */
class NGCPM_Repair {

	/**
	 * @param array<string, array<string, mixed>>|null $scan Scan data.
	 * @return array<int, array<string, mixed>>
	 */
	public static function detect_issues( $scan = null ) {
		$scan   = $scan ?: NGCPM_Scanner::scan( false );
		$issues = [];

		foreach ( $scan as $slug => $row ) {
			$status = (string) ( $row['health_status'] ?? '' );
			$name   = (string) ( $row['name'] ?? $slug );

			if ( 'INACTIVE' === $status ) {
				$issues[] = self::issue(
					$slug,
					$name,
					__( 'Plugin installed but inactive', 'nextgentutors-plugin-manager' ),
					'High',
					'activate',
					98,
					__( 'Will call activate_plugin() on the main plugin file.', 'nextgentutors-plugin-manager' )
				);
				continue;
			}

			if ( 'MISSING' === $status && ! empty( $row['can_auto_install'] ) ) {
				$issues[] = self::issue(
					$slug,
					$name,
					__( 'Required plugin missing', 'nextgentutors-plugin-manager' ),
					'Critical',
					'install',
					95,
					__( 'Will install from verified WordPress.org or package source.', 'nextgentutors-plugin-manager' )
				);
				continue;
			}

			if ( 'VERSION_OUTDATED' === $status ) {
				$issues[] = self::issue(
					$slug,
					$name,
					__( 'Plugin version below minimum', 'nextgentutors-plugin-manager' ),
					'Medium',
					'manual',
					100,
					__( 'Update plugin manually from Plugins screen or vendor.', 'nextgentutors-plugin-manager' )
				);
			}
		}

		return apply_filters( 'ngcpm_repair_issues', $issues, $scan );
	}

	/**
	 * @param string $slug        Registry key.
	 * @param string $name        Plugin name.
	 * @param string $title       Issue title.
	 * @param string $severity    Severity label.
	 * @param string $strategy    install|activate|manual.
	 * @param int    $confidence  Confidence percent.
	 * @param string $preview     Preview text.
	 * @return array<string, mixed>
	 */
	private static function issue( $slug, $name, $title, $severity, $strategy, $confidence, $preview ) {
		return [
			'slug'       => $slug,
			'name'       => $name,
			'title'      => $title,
			'severity'   => $severity,
			'strategy'   => $strategy,
			'confidence' => $confidence,
			'preview'    => $preview,
			'can_execute'=> in_array( $strategy, [ 'install', 'activate' ], true ),
		];
	}

	/**
	 * Execute a repair strategy for one plugin.
	 *
	 * @param string $slug     Registry key.
	 * @param string $strategy install|activate.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function execute( $slug, $strategy ) {
		$strategy = sanitize_key( $strategy );
		NGCPM_Logger::log( 'repair_started', 'Repair started', [ 'slug' => $slug, 'strategy' => $strategy ] );

		if ( 'activate' === $strategy ) {
			$result = NGCPM_Activator::activate( $slug );
		} elseif ( 'install' === $strategy ) {
			$result = NGCPM_Installer::install( $slug );
		} else {
			return [
				'success' => false,
				'message' => __( 'Repair strategy not executable.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		if ( ! empty( $result['success'] ) ) {
			NGCPM_Logger::log( 'repair_success', $result['message'], [ 'slug' => $slug ] );
		} else {
			NGCPM_Logger::log( 'repair_failure', $result['message'] ?? 'Repair failed', [ 'slug' => $slug ] );
		}

		return $result;
	}
}
