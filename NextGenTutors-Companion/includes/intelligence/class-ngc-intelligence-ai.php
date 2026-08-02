<?php
/**
 * AI-assisted operational intelligence (rule-based + NGTAI BYOK forecasting).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates summaries and answers NL questions from live KPIs/events.
 */
final class NGC_Intelligence_Ai {

	/**
	 * Executive brief for dashboard header.
	 *
	 * @return array<string, mixed>
	 */
	public static function executive_brief() {
		$dash   = NGC_Intelligence_Kpi_Engine::executive_dashboard();
		$kpis   = is_array( $dash['kpis'] ?? null ) ? $dash['kpis'] : [];
		$errors = 0;
		$bookings = 0;
		foreach ( $kpis as $k ) {
			if ( 'errors_24h' === ( $k['key'] ?? '' ) ) {
				$errors = (int) ( $k['value'] ?? 0 );
			}
			if ( 'bookings_today' === ( $k['key'] ?? '' ) ) {
				$bookings = (int) ( $k['value'] ?? 0 );
			}
		}

		$anomalies = [];
		if ( $errors > 25 ) {
			$anomalies[] = sprintf( 'Elevated error rate: %d errors in 24h.', $errors );
		}
		if ( 0 === $bookings && gmdate( 'G' ) > 10 ) {
			$anomalies[] = 'No bookings recorded today — verify funnel and Amelia integration.';
		}

		$top_plugin = self::top_error_plugin();
		if ( $top_plugin ) {
			$anomalies[] = sprintf( 'Highest error volume from plugin: %s.', $top_plugin );
		}

		$brief = [
			'generated_at'    => gmdate( 'c' ),
			'summary'         => self::build_summary( $kpis, $anomalies ),
			'anomalies'       => $anomalies,
			'recommendations' => self::recommendations( $kpis ),
			'engine'          => 'heuristic',
		];

		if ( self::ngtai_available() && NGC_Intelligence_Config::get()['ai_analysis_enabled'] ) {
			$ai = self::ngtai_analyze(
				'Summarize this operational dashboard for an executive in 3 sentences.',
				[ 'kpis' => $kpis, 'anomalies' => $anomalies, 'series' => $dash['series'] ?? [] ]
			);
			if ( ! empty( $ai['text'] ) ) {
				$brief['summary']  = $ai['text'];
				$brief['engine']   = 'ngtai';
				$brief['ngtai_ok'] = ! empty( $ai['ok'] );
			}
		}

		return $brief;
	}

	/**
	 * @param string $question Natural language question.
	 * @return array<string, mixed>
	 */
	public static function answer( $question ) {
		$q = trim( (string) $question );
		if ( '' === $q ) {
			return [
				'answer' => __( 'Ask a question about bookings, errors, plugins, or workflows.', 'nextgencompanion' ),
				'sources'=> [],
			];
		}

		if ( self::ngtai_available() && NGC_Intelligence_Config::get()['ai_analysis_enabled'] ) {
			$context = [
				'kpis'     => NGC_Intelligence_Kpi_Engine::executive_dashboard()['kpis'] ?? [],
				'workflows'=> NGC_Intelligence_Kpi_Engine::executive_dashboard()['workflows'] ?? [],
				'top_errors_plugin' => self::top_error_plugin(),
			];
			$ai = self::ngtai_analyze( $q, $context );
			if ( ! empty( $ai['text'] ) ) {
				return [
					'answer'  => $ai['text'],
					'sources' => [ 'ngtai', 'intel_events', 'kpis' ],
					'engine'  => 'ngtai',
					'ok'      => ! empty( $ai['ok'] ),
				];
			}
		}

		return self::heuristic_answer( strtolower( $q ) );
	}

	/**
	 * @param string               $prompt  Prompt.
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>
	 */
	private static function ngtai_analyze( $prompt, array $context ) {
		if ( ! class_exists( 'NGTAI_Api_Client' ) ) {
			return [ 'ok' => false, 'text' => '' ];
		}
		$task = [
			'task_id'        => 'intel-' . substr( md5( $prompt . wp_json_encode( $context ) ), 0, 12 ),
			'agent'          => 'operational_analyst',
			'correlation_id' => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'intel', true ),
			'input'          => [
				'prompt'  => $prompt,
				'context' => $context,
				'site'    => site_url(),
			],
		];
		$result = NGTAI_Api_Client::post_task( $task );
		if ( empty( $result['ok'] ) ) {
			return [ 'ok' => false, 'text' => '', 'error' => $result['error'] ?? 'ngtai_unavailable' ];
		}
		$body = is_array( $result['body'] ?? null ) ? $result['body'] : [];
		$text = (string) ( $body['summary'] ?? $body['answer'] ?? $body['text'] ?? $body['message'] ?? '' );
		if ( '' === $text && isset( $body['result'] ) ) {
			$text = is_string( $body['result'] ) ? $body['result'] : wp_json_encode( $body['result'] );
		}
		return [ 'ok' => true, 'text' => $text ];
	}

	/**
	 * @return bool
	 */
	private static function ngtai_available() {
		return class_exists( 'NGTAI_Config' ) && class_exists( 'NGTAI_Api_Client' )
			&& NGTAI_Config::configured() && NGTAI_Config::enabled()
			&& ! get_option( 'ngtai_global_pause', false );
	}

	/**
	 * @param string $q Question lowercased.
	 * @return array<string, mixed>
	 */
	private static function heuristic_answer( $q ) {
		if ( false !== strpos( $q, 'error' ) || false !== strpos( $q, 'fail' ) ) {
			$plugin = self::top_error_plugin();
			return [
				'answer'  => $plugin
					? sprintf( __( 'The plugin generating the most errors recently is %s.', 'nextgencompanion' ), $plugin )
					: __( 'No significant error clusters detected in the last 24 hours.', 'nextgencompanion' ),
				'sources' => [ 'intel_events', 'system_log' ],
				'engine'  => 'heuristic',
			];
		}
		if ( false !== strpos( $q, 'booking' ) ) {
			$dash = NGC_Intelligence_Kpi_Engine::executive_dashboard();
			$val  = 0;
			foreach ( (array) ( $dash['kpis'] ?? [] ) as $k ) {
				if ( 'bookings_today' === ( $k['key'] ?? '' ) ) {
					$val = (int) ( $k['value'] ?? 0 );
				}
			}
			return [
				'answer'  => sprintf( __( 'There are %d bookings recorded today.', 'nextgencompanion' ), $val ),
				'sources' => [ 'bookings' ],
				'engine'  => 'heuristic',
			];
		}
		if ( false !== strpos( $q, 'workflow' ) || false !== strpos( $q, 'slow' ) ) {
			$stats = NGC_Intelligence_Kpi_Engine::executive_dashboard()['workflows'] ?? [];
			return [
				'answer'  => sprintf(
					__( 'Workflow runs today: %1$d, failed: %2$d.', 'nextgencompanion' ),
					(int) ( $stats['today'] ?? 0 ),
					(int) ( $stats['failed'] ?? 0 )
				),
				'sources' => [ 'workflow_runs' ],
				'engine'  => 'heuristic',
			];
		}
		if ( false !== strpos( $q, 'predict' ) || false !== strpos( $q, 'forecast' ) ) {
			return self::forecast_answer();
		}
		return [
			'answer'  => __( 'Try asking about errors, bookings, workflows, or forecasts.', 'nextgencompanion' ),
			'sources' => [],
			'engine'  => 'heuristic',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function forecast_answer() {
		$series = NGC_Intelligence_Kpi_Engine::executive_dashboard()['series']['bookings_7d'] ?? [];
		$avg    = 0;
		if ( is_array( $series ) && count( $series ) ) {
			$sum = 0;
			foreach ( $series as $row ) {
				$sum += (int) ( $row['c'] ?? 0 );
			}
			$avg = (int) round( $sum / count( $series ) );
		}
		$heuristic = sprintf(
			__( 'Based on the 7-day average (%1$d/day), next week may see roughly %2$d bookings.', 'nextgencompanion' ),
			$avg,
			$avg * 7
		);
		if ( self::ngtai_available() ) {
			$ai = self::ngtai_analyze(
				'Forecast next month booking volume based on the 7-day series. Reply in one paragraph with a numeric estimate.',
				[ 'bookings_7d' => $series, 'daily_average' => $avg ]
			);
			if ( ! empty( $ai['text'] ) ) {
				return [
					'answer'  => $ai['text'],
					'sources' => [ 'bookings_7d_series', 'ngtai' ],
					'engine'  => 'ngtai',
				];
			}
		}
		return [
			'answer'  => $heuristic,
			'sources' => [ 'bookings_7d_series' ],
			'engine'  => 'heuristic',
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $kpis KPI cards.
	 * @param array<int, string>               $anomalies Anomalies.
	 * @return string
	 */
	private static function build_summary( array $kpis, array $anomalies ) {
		$parts = [ __( 'Platform operational snapshot:', 'nextgencompanion' ) ];
		foreach ( $kpis as $k ) {
			$parts[] = sprintf( '%s: %s', $k['label'] ?? $k['key'], $k['value'] ?? 0 );
		}
		if ( $anomalies ) {
			$parts[] = __( 'Attention:', 'nextgencompanion' ) . ' ' . implode( ' ', $anomalies );
		} else {
			$parts[] = __( 'No critical anomalies detected.', 'nextgencompanion' );
		}
		return implode( ' ', $parts );
	}

	/**
	 * @param array<int, array<string, mixed>> $kpis KPIs.
	 * @return array<int, string>
	 */
	private static function recommendations( array $kpis ) {
		$recs = [];
		foreach ( $kpis as $k ) {
			if ( 'errors_24h' === ( $k['key'] ?? '' ) && (int) ( $k['value'] ?? 0 ) > 10 ) {
				$recs[] = __( 'Review system log and intelligence event drill-down for error clusters.', 'nextgencompanion' );
			}
			if ( 'pending_matches' === ( $k['key'] ?? '' ) && (int) ( $k['value'] ?? 0 ) > 5 ) {
				$recs[] = __( 'Clear pending match queue in Companion Ops.', 'nextgencompanion' );
			}
		}
		if ( ! $recs ) {
			$recs[] = __( 'System within normal operating parameters.', 'nextgencompanion' );
		}
		return $recs;
	}

	/**
	 * @return string
	 */
	private static function top_error_plugin() {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return '';
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			"SELECT plugin_slug, COUNT(*) AS c FROM {$table} WHERE severity IN ('error','critical') AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR) GROUP BY plugin_slug ORDER BY c DESC LIMIT 1",
			ARRAY_A
		);
		return is_array( $row ) ? (string) ( $row['plugin_slug'] ?? '' ) : '';
	}
}
