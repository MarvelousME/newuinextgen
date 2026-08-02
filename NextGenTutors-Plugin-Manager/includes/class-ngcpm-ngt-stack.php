<?php
/**
 * First-party NextGenTutors stack plugin matrix (beyond WooCommerce registry).
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mission Control, Companion, AI Integration, etc.
 */
class NGCPM_NGT_Stack {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function matrix() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$want = [
			'NextGenTutors-Companion/nextgencompanion.php'                    => [
				'label'    => 'Companion',
				'required' => true,
				'priority' => 5,
				'notes'    => __( 'Authoritative domain layer — required.', 'nextgentutors-plugin-manager' ),
			],
			'NextGenTutors-Mission-Control/nextgentutors-mission-control.php' => [
				'label'    => 'Mission Control',
				'required' => false,
				'priority' => 8,
				'notes'    => __( 'Ops control plane — configure, seed, verify, overrides.', 'nextgentutors-plugin-manager' ),
			],
			'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php'  => [
				'label'    => 'Plugin Manager',
				'required' => false,
				'priority' => 9,
				'notes'    => __( 'Fleet install and registry health (this plugin).', 'nextgentutors-plugin-manager' ),
			],
			'NextGenTutors-AI-Integration/nextgentutors-ai-integration.php'   => [
				'label'    => 'AI Integration',
				'required' => false,
				'priority' => 12,
				'notes'    => __( 'Agent outbox bridge — optional until agents-api is configured.', 'nextgentutors-plugin-manager' ),
			],
			'nextgen-automation-hub/nextgen-automation-hub.php'               => [
				'label'    => 'Automation Hub',
				'required' => false,
				'priority' => 15,
				'notes'    => __( 'Event triggers + RTM — defers to Companion when active.', 'nextgentutors-plugin-manager' ),
			],
			'NextGenTutors-Html-Importer/revamp-html-importer.php'            => [
				'label'    => 'Html Importer',
				'required' => false,
				'priority' => 90,
				'notes'    => __( 'One-time migration tool — deactivate after import.', 'nextgentutors-plugin-manager' ),
			],
		];

		$all    = get_plugins();
		$active = (array) get_option( 'active_plugins', [] );
		$rows   = [];

		foreach ( $want as $file => $meta ) {
			$installed = isset( $all[ $file ] );
			$is_active = in_array( $file, $active, true );
			$rows[]    = [
				'file'      => $file,
				'label'     => (string) $meta['label'],
				'required'  => ! empty( $meta['required'] ),
				'priority'  => (int) $meta['priority'],
				'notes'     => (string) $meta['notes'],
				'installed' => $installed,
				'active'    => $is_active,
				'version'   => $installed ? (string) ( $all[ $file ]['Version'] ?? '' ) : '',
				'status'    => self::row_status( $installed, $is_active, ! empty( $meta['required'] ) ),
			];
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return (int) $a['priority'] <=> (int) $b['priority'];
			}
		);

		return $rows;
	}

	/**
	 * @param bool $installed Installed.
	 * @param bool $active    Active.
	 * @param bool $required  Required.
	 * @return string
	 */
	private static function row_status( $installed, $active, $required ) {
		if ( $required && ( ! $installed || ! $active ) ) {
			return 'MISSING';
		}
		if ( $installed && $active ) {
			return 'READY';
		}
		if ( $installed ) {
			return 'INACTIVE';
		}
		return 'OPTIONAL';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function summary() {
		$rows = self::matrix();
		$required_missing = array_filter(
			$rows,
			static function ( $row ) {
				return ! empty( $row['required'] ) && empty( $row['active'] );
			}
		);
		return [
			'ok'               => empty( $required_missing ),
			'total'            => count( $rows ),
			'active'           => count( array_filter( $rows, static fn( $r ) => ! empty( $r['active'] ) ) ),
			'required_missing' => array_values( array_map( static fn( $r ) => $r['label'], $required_missing ) ),
			'rows'             => $rows,
		];
	}
}
