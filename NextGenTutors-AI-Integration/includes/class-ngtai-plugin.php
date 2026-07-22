<?php
/**
 * Plugin orchestrator.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Plugin {
	private static $initialized = false;

	/**
	 * Initialize available integration surfaces.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;
		load_plugin_textdomain( 'nextgentutors-ai-integration', false, dirname( NGTAI_PLUGIN_BASENAME ) . '/languages' );

		if ( class_exists( 'NGTAI_Migrator' ) && defined( 'NGTAI_Migrator::DB_VERSION' ) && NGTAI_Migrator::DB_VERSION !== get_option( 'ngtai_db_version', '' ) ) {
			NGTAI_Migrator::migrate();
		}
		if ( class_exists( 'NGTAI_Cron' ) ) {
			NGTAI_Cron::init();
			NGTAI_Cron::schedule();
		}

		$companion = class_exists( 'NGC_Plugin' );
		if ( ! $companion ) {
			add_action( 'admin_notices', [ __CLASS__, 'companion_notice' ] );
		} elseif ( class_exists( 'NGTAI_Outbox_Bridge' ) ) {
			NGTAI_Outbox_Bridge::init();
			add_filter( 'ngc_dashboard_html', [ __CLASS__, 'render_match_recommendations' ], 20, 3 );
			add_filter( 'ngc_metrics_snapshot', [ __CLASS__, 'extend_metrics' ] );
		}

		foreach ( [ 'NGTAI_Rest_Health', 'NGTAI_Rest_Callbacks', 'NGTAI_Rest_Approvals', 'NGTAI_Rest_Outbox' ] as $class ) {
			if ( class_exists( $class ) ) {
				$class::init();
			}
		}
		if ( is_admin() && class_exists( 'NGTAI_Admin' ) ) {
			NGTAI_Admin::init();
		}
		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'NGTAI_Cli' ) ) {
			NGTAI_Cli::register();
		}
	}

	/**
	 * Render degraded-mode notice.
	 *
	 * @return void
	 */
	public static function companion_notice() {
		if ( NGTAI_Access::can_manage() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'NextGenTutors Companion is missing. AI delivery is paused; health and settings remain available.', 'nextgentutors-ai-integration' ) . '</p></div>';
		}
	}

	/**
	 * Append approved recommendation summaries to parent dashboards.
	 *
	 * @param string $html    Existing HTML.
	 * @param string $type    Dashboard type.
	 * @param int    $user_id User ID.
	 * @return string
	 */
	public static function render_match_recommendations( $html, $type, $user_id ) {
		if ( 'parent' !== $type || ! $user_id ) {
			return $html;
		}
		global $wpdb;
		$table = NGTAI_Database::table( 'agent_results' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT correlation_id,result_json FROM {$table} WHERE action_name=%s AND status IN ('approved','applied') ORDER BY id DESC LIMIT %d",
				'match.recommendation',
				3
			),
			ARRAY_A
		);
		if ( ! $rows ) {
			return $html;
		}
		ob_start();
		echo '<section class="ngtai-recommendations" data-testid="ngtai-match-recommendations"><h3>' . esc_html__( 'Tutor recommendations', 'nextgentutors-ai-integration' ) . '</h3>';
		foreach ( $rows as $row ) {
			$result = json_decode( (string) $row['result_json'], true );
			$result = is_array( $result ) ? $result : [];
			echo '<article><p>' . esc_html( (string) ( $result['explanation'] ?? '' ) ) . '</p><ol>';
			foreach ( array_slice( (array) ( $result['ranking'] ?? $result['candidates'] ?? [] ), 0, 5 ) as $candidate ) {
				$id    = absint( $candidate['tutor_id'] ?? $candidate['user_id'] ?? 0 );
				$score = isset( $candidate['score'] ) ? (string) $candidate['score'] : '';
				echo '<li>' . esc_html( sprintf( 'Tutor #%d — %s', $id, $score ) ) . '</li>';
			}
			echo '</ol><small>' . esc_html__( 'Correlation:', 'nextgentutors-ai-integration' ) . ' ' . esc_html( (string) $row['correlation_id'] ) . '</small></article>';
		}
		echo '</section>';
		return $html . ob_get_clean();
	}

	/**
	 * Add bridge metrics.
	 *
	 * @param array<string,mixed> $metrics Metrics.
	 * @return array<string,mixed>
	 */
	public static function extend_metrics( $metrics ) {
		$metrics = is_array( $metrics ) ? $metrics : [];
		$counts  = class_exists( 'NGTAI_Delivery_Repository' ) ? NGTAI_Delivery_Repository::counts() : [];
		$metrics['ngtai_outbox_pending']          = (int) ( $counts['pending'] ?? 0 );
		$metrics['ngtai_outbox_failed']           = (int) ( $counts['failed'] ?? 0 );
		$metrics['ngtai_outbox_dead_letter']      = (int) ( $counts['dead_letter'] ?? 0 );
		$metrics['ngtai_signature_failure_total'] = (int) get_option( 'ngtai_signature_failure_total', 0 );
		$metrics['ngtai_duplicate_event_total']   = (int) get_option( 'ngtai_duplicate_event_total', 0 );
		$metrics['ngtai_callback_failure_total']  = (int) get_option( 'ngtai_callback_failure_total', 0 );
		$metrics['ngtai_policy_denied_total']     = (int) get_option( 'ngtai_policy_denied_total', 0 );
		return $metrics;
	}
}
