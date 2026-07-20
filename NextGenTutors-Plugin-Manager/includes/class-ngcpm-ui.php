<?php
/**
 * UI rendering helpers.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared UI helpers for templates.
 */
class NGCPM_UI {

	/**
	 * Inline Lucide-style SVG icon.
	 *
	 * @param string $name Icon key.
	 * @return string
	 */
	public static function icon( $name ) {
		$icons = [
			'puzzle'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a2.135 2.135 0 0 1-2.704 0l-1.568-1.568a1.026 1.026 0 0 0-.878-.29c-.493.074-.99.074-1.483 0a1.026 1.026 0 0 0-.878.29l-1.568 1.568a2.135 2.135 0 0 1-2.704 0l-1.611-1.611a2.135 2.135 0 0 1 0-2.704l1.568-1.568a1.026 1.026 0 0 0 .29-.878c-.074-.493-.074-.99 0-1.483a1.026 1.026 0 0 0-.29-.878l-1.568-1.568a2.135 2.135 0 0 1 0-2.704l1.611-1.611a2.135 2.135 0 0 1 2.704 0l1.568 1.568c.23.23.556.338.878.29.493-.074.99-.074 1.483 0 .322.049.648-.059.878-.29l1.568-1.568a2.135 2.135 0 0 1 2.704 0l1.611 1.611a2.135 2.135 0 0 1 0 2.704l-1.568 1.568a1.026 1.026 0 0 0-.29.878c.074.493.074.99 0 1.483Z"/><path d="M9 12h6"/></svg>',
			'refresh'      => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>',
			'download'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>',
			'power'        => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.77 0"/></svg>',
			'wrench'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
			'shield'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
			'export'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="m9 15 3-3 3 3"/></svg>',
			'search'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
			'menu'         => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>',
			'home'         => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
			'heart'        => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
			'scroll'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
			'lock'         => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
			'layers'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/></svg>',
			'git-branch'   => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="6" x2="6" y1="3" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>',
			'settings'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
			'x'            => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
			'chevron'      => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
			'rocket'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
			'check'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
			'activity'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>',
			'info'         => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
		];
		return $icons[ $name ] ?? $icons['puzzle'];
	}

	/**
	 * Badge class from status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	public static function badge_class( $status ) {
		return 'ngcpm-badge ngcpm-badge--' . sanitize_html_class( strtolower( (string) $status ) );
	}

	/**
	 * Map node status class.
	 *
	 * @param string $status Health status.
	 * @return string
	 */
	public static function map_node_class( $status ) {
		$map = [
			'READY'             => 'is-ready',
			'ACTIVE'            => 'is-ready',
			'INSTALLED'         => 'is-info',
			'INACTIVE'          => 'is-warning',
			'VERSION_OUTDATED'  => 'is-warning',
			'MISSING'           => 'is-danger',
			'MANUAL_REQUIRED'   => 'is-manual',
			'SKIPPED'           => 'is-muted',
			'FAILED'            => 'is-danger',
		];
		return $map[ $status ] ?? 'is-muted';
	}

	/**
	 * Security score heuristic.
	 *
	 * @param array<string, mixed> $health Health array.
	 * @return int
	 */
	public static function security_score( $health ) {
		$penalty = ( (int) ( $health['missing'] ?? 0 ) * 12 )
			+ ( (int) ( $health['inactive'] ?? 0 ) * 6 )
			+ ( (int) ( $health['manual_required'] ?? 0 ) * 4 )
			+ ( (int) ( $health['outdated'] ?? 0 ) * 3 );
		return max( 0, min( 100, 100 - $penalty ) );
	}

	/**
	 * System map pipeline nodes.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan data.
	 * @return array<int, array<string, string>>
	 */
	public static function pipeline_nodes( $scan ) {
		$order = [];
		foreach ( NGCPM_Registry::pipeline_slugs() as $key ) {
			if ( 'core' === $key ) {
				$order[] = [ 'key' => 'core', 'label' => 'WordPress Core', 'status' => 'READY' ];
				continue;
			}
			$row = $scan[ $key ] ?? NGCPM_Registry::get( $key ) ?? [];
			$order[] = [
				'key'    => $key,
				'label'  => (string) ( $row['name'] ?? $key ),
				'status' => (string) ( $row['health_status'] ?? 'MISSING' ),
			];
		}
		return $order;
	}

	/**
	 * Verification matrix rows from scan.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @return array<int, array<string, string>>
	 */
	public static function verification_rows( $scan ) {
		$rows = [];
		foreach ( $scan as $slug => $row ) {
			$active   = ! empty( $row['active'] );
			$expected = ! empty( $row['required'] ) ? 'active' : 'optional';
			$actual   = $active ? 'active' : ( ! empty( $row['installed'] ) ? 'inactive' : 'missing' );
			$status   = (string) ( $row['health_status'] ?? 'MISSING' );
			$severity = 'MISSING' === $status && ! empty( $row['required'] ) ? 'Critical' : ( 'INACTIVE' === $status ? 'High' : '—' );
			if ( 'READY' === $status ) {
				$status = 'PASS';
			} elseif ( 'MANUAL_REQUIRED' === ( $row['health_status'] ?? '' ) ) {
				$status = 'NOT_CONFIGURED';
			} elseif ( in_array( $status, [ 'MISSING', 'FAILED' ], true ) ) {
				$status = empty( $row['required'] ) ? 'NOT_CONFIGURED' : 'FAIL';
			} else {
				$status = 'WARNING';
			}
			$rows[] = [
				'feature'  => (string) ( $row['name'] ?? $slug ),
				'expected' => $expected,
				'actual'   => $actual,
				'status'   => $status,
				'severity' => $severity,
				'slug'     => $slug,
				'action'   => 'PASS' === $status ? '—' : ( 'MANUAL_REQUIRED' === ( $row['health_status'] ?? '' ) ? 'Manual' : 'Repair' ),
			];
		}
		return $rows;
	}

	/**
	 * Health check categories.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @param array<string, mixed>               $health Health.
	 * @return array<int, array<string, mixed>>
	 */
	public static function health_categories( $scan, $health ) {
		$plugin_ok = (int) ( $health['required_ready'] ?? 0 ) >= (int) ( $health['required_total'] ?? 1 );
		$cookies   = NGCPM_Cookies::run_checks();
		$cookie_row = [
			'name'     => __( 'Cookies', 'nextgentutors-plugin-manager' ),
			'status'   => 'PASS',
			'evidence' => __( 'Cookie system available', 'nextgentutors-plugin-manager' ),
		];
		foreach ( $cookies as $check ) {
			$st = (string) ( $check['status'] ?? '' );
			if ( 'FAIL' === $st ) {
				$cookie_row['status']   = 'FAIL';
				$cookie_row['evidence'] = (string) ( $check['evidence'] ?? '' );
				break;
			}
			if ( 'WARNING' === $st && 'PASS' === $cookie_row['status'] ) {
				$cookie_row['status']   = 'WARNING';
				$cookie_row['evidence'] = (string) ( $check['evidence'] ?? '' );
			}
		}

		return [
			[
				'name'     => __( 'Plugins', 'nextgentutors-plugin-manager' ),
				'status'   => $plugin_ok ? 'PASS' : 'WARNING',
				'evidence' => sprintf( '%d/%d required ready', (int) ( $health['required_ready'] ?? 0 ), (int) ( $health['required_total'] ?? 0 ) ),
			],
			self::stack_category( $scan, 'woocommerce', 'payfast-payment-gateway', __( 'Payments', 'nextgentutors-plugin-manager' ), __( 'WooCommerce + PayFast gateway', 'nextgentutors-plugin-manager' ) ),
			self::stack_category( $scan, 'ameliabooking', null, __( 'Bookings', 'nextgentutors-plugin-manager' ), __( 'Amelia Booking plugin', 'nextgentutors-plugin-manager' ) ),
			self::stack_category( $scan, 'masterstudy-lms', null, __( 'LMS', 'nextgentutors-plugin-manager' ), __( 'MasterStudy LMS', 'nextgentutors-plugin-manager' ) ),
			self::stack_category( $scan, 'fluent-smtp', null, __( 'Mail', 'nextgentutors-plugin-manager' ), __( 'FluentSMTP', 'nextgentutors-plugin-manager' ) ),
			self::stack_category( $scan, 'automatorwp', null, __( 'Automations', 'nextgentutors-plugin-manager' ), __( 'AutomatorWP', 'nextgentutors-plugin-manager' ) ),
			$cookie_row,
			[
				'name'     => __( 'Security', 'nextgentutors-plugin-manager' ),
				'status'   => self::security_score( $health ) >= 80 ? 'PASS' : 'WARNING',
				'evidence' => sprintf( __( 'Risk score %d/100', 'nextgentutors-plugin-manager' ), self::security_score( $health ) ),
			],
		];
	}

	/**
	 * Truthful stack health — NOT_CONFIGURED when not installed, never FAIL for absent tracking in admin.
	 *
	 * @param array<string, array<string, mixed>> $scan     Scan.
	 * @param string                              $primary  Primary registry slug.
	 * @param string|null                         $secondary Optional secondary slug.
	 * @param string                              $name     Category label.
	 * @param string                              $evidence Evidence label.
	 * @return array<string, string>
	 */
	private static function stack_category( $scan, $primary, $secondary, $name, $evidence ) {
		$primary_row   = $scan[ $primary ] ?? [];
		$secondary_row = $secondary ? ( $scan[ $secondary ] ?? [] ) : [];

		if ( ! empty( $primary_row['active'] ) && ( ! $secondary || ! empty( $secondary_row['active'] ) ) ) {
			return [ 'name' => $name, 'status' => 'PASS', 'evidence' => $evidence ];
		}
		if ( ! empty( $primary_row['installed'] ) || ( $secondary && ! empty( $secondary_row['installed'] ) ) ) {
			return [ 'name' => $name, 'status' => 'WARNING', 'evidence' => $evidence . ' — ' . __( 'installed but inactive or incomplete', 'nextgentutors-plugin-manager' ) ];
		}
		return [ 'name' => $name, 'status' => 'NOT CONFIGURED', 'evidence' => $evidence . ' — ' . __( 'not installed yet', 'nextgentutors-plugin-manager' ) ];
	}

	/**
	 * Environment label for badges.
	 *
	 * @return string
	 */
	public static function environment_label() {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$type = wp_get_environment_type();
			return ucfirst( (string) $type );
		}
		return defined( 'WP_DEBUG' ) && WP_DEBUG
			? __( 'Development', 'nextgentutors-plugin-manager' )
			: __( 'Production', 'nextgentutors-plugin-manager' );
	}

	/**
	 * Dependency graph nodes and edges.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @return array{nodes: array<int, array<string, string>>, edges: array<int, array{from: string, to: string}>}
	 */
	public static function dependency_graph( $scan ) {
		$nodes = [
			[ 'id' => 'core', 'label' => 'WordPress Core', 'status' => 'READY' ],
		];
		$edges = NGCPM_Registry::dependency_edges();

		foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
			$row     = $scan[ $slug ] ?? [];
			$nodes[] = [
				'id'     => $slug,
				'label'  => (string) ( $row['name'] ?? $def['name'] ?? $slug ),
				'status' => (string) ( $row['health_status'] ?? 'MISSING' ),
			];
		}

		return [ 'nodes' => $nodes, 'edges' => $edges ];
	}

	/**
	 * Inactive installed plugins.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @return array<int, array<string, mixed>>
	 */
	public static function inactive_plugins( $scan ) {
		$rows = [];
		foreach ( $scan as $slug => $row ) {
			if ( ! empty( $row['installed'] ) && empty( $row['active'] ) ) {
				$rows[] = [
					'slug'   => $slug,
					'name'   => (string) ( $row['name'] ?? $slug ),
					'status' => (string) ( $row['health_status'] ?? 'INACTIVE' ),
					'version'=> (string) ( $row['version'] ?? '' ),
				];
			}
		}
		return $rows;
	}

	/**
	 * Plugin configuration hub rows.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @return array<int, array<string, mixed>>
	 */
	public static function configuration_hub( $scan ) {
		$rows = [];
		foreach ( $scan as $slug => $row ) {
			if ( empty( $row['active'] ) ) {
				continue;
			}
			$setup = (string) ( $row['setup_url'] ?? '' );
			if ( ! $setup ) {
				continue;
			}
			$rows[] = [
				'slug'  => $slug,
				'name'  => (string) ( $row['name'] ?? $slug ),
				'url'   => $setup,
				'notes' => (string) ( $row['notes'] ?? '' ),
			];
		}
		return $rows;
	}

	/**
	 * Exception log entries from NextGen Companion when available.
	 *
	 * @param int $limit Max entries.
	 * @return array<int, array<string, mixed>>
	 */
	public static function exception_logs( $limit = 30 ) {
		if ( class_exists( 'NGC_Exception_Log' ) && method_exists( 'NGC_Exception_Log', 'recent' ) ) {
			return NGC_Exception_Log::recent( $limit );
		}
		$raw = get_option( 'ngc_exception_log', [] );
		if ( ! is_array( $raw ) ) {
			return [];
		}
		return array_slice( $raw, 0, max( 1, (int) $limit ) );
	}
}
