<?php
/**
 * Mission Control — Intelligence tab bridge.
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues assets and exposes REST config for live dashboard.
 */
final class NGTMC_Intelligence {

	/**
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( 'NGC_Intelligence' ) && class_exists( 'NGC_Intelligence_Kpi_Engine' );
	}

	/**
	 * @param string $hook Admin hook.
	 * @param string $tab  Active tab.
	 */
	public static function maybe_enqueue( $hook, $tab ) {
		if ( false === strpos( (string) $hook, NGTMC_Admin::PAGE ) || 'intelligence' !== $tab ) {
			return;
		}
		if ( ! self::is_available() ) {
			return;
		}

		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			[],
			'4.4.1',
			true
		);
		wp_enqueue_script(
			'vis-network',
			'https://unpkg.com/vis-network/standalone/umd/vis-network.min.js',
			[],
			'9.1.9',
			true
		);
		wp_enqueue_style(
			'ngtmc-intelligence',
			NGTMC_PLUGIN_URL . 'assets/css/intelligence.css',
			[ 'ngtmc-admin' ],
			NGTMC_VERSION
		);
		wp_enqueue_script(
			'ngtmc-intelligence-grid',
			NGTMC_PLUGIN_URL . 'assets/js/intelligence-grid.js',
			[],
			NGTMC_VERSION,
			true
		);
		wp_enqueue_script(
			'ngtmc-intelligence-charts',
			NGTMC_PLUGIN_URL . 'assets/js/intelligence-charts.js',
			[ 'chartjs', 'vis-network' ],
			NGTMC_VERSION,
			true
		);
		wp_enqueue_script(
			'ngtmc-intelligence',
			NGTMC_PLUGIN_URL . 'assets/js/intelligence.js',
			[ 'chartjs', 'vis-network', 'ngtmc-intelligence-grid', 'ngtmc-intelligence-charts' ],
			NGTMC_VERSION,
			true
		);

		$config = class_exists( 'NGC_Intelligence_Config' ) ? NGC_Intelligence_Config::get() : [];
		wp_localize_script(
			'ngtmc-intelligence',
			'ngtmcIntel',
			[
				'restRoot'   => esc_url_raw( rest_url( 'ngc/v1/intelligence' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'refreshMs'  => (int) ( $config['refresh_interval_ms'] ?? 5000 ),
				'sseEnabled' => ! empty( $config['sse_enabled'] ),
			]
		);
	}
}
