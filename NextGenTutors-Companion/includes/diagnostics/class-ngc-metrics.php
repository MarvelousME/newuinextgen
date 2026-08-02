<?php
/**
 * Observability metrics collector + optional external APM push (OBS-001).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects platform counters for Prometheus scrape / webhook push.
 */
final class NGC_Metrics {

	public const OPTION_ENABLED     = 'ngc_metrics_enabled';
	public const OPTION_TOKEN       = 'ngc_metrics_token';
	public const OPTION_PUSH_URL    = 'ngc_metrics_push_url';
	public const OPTION_ALERT_ERROR = 'ngc_metrics_alert_error_threshold';
	public const CRON_PUSH          = 'ngc_metrics_push_tick';
	public const TRANSIENT_COUNTERS = 'ngc_metrics_counters_v1';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_system_log_written', [ __CLASS__, 'on_system_log' ], 10, 5 );
		add_action( self::CRON_PUSH, [ __CLASS__, 'push_to_webhook' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_schedule_push' ] );
	}

	/**
	 * @return bool
	 */
	public static function is_enabled() {
		return '1' === (string) get_option( self::OPTION_ENABLED, '1' );
	}

	/**
	 * @return array{enabled:bool,token:string,push_url:string,alert_error_threshold:int}
	 */
	public static function settings() {
		$token = (string) get_option( self::OPTION_TOKEN, '' );
		if ( '' === $token ) {
			$token = self::ensure_token();
		}
		return [
			'enabled'                => self::is_enabled(),
			'token'                  => $token,
			'push_url'               => (string) get_option( self::OPTION_PUSH_URL, '' ),
			'alert_error_threshold'  => max( 1, (int) get_option( self::OPTION_ALERT_ERROR, 25 ) ),
		];
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array{enabled:bool,token:string,push_url:string,alert_error_threshold:int}
	 */
	public static function save_settings( $input ) {
		$enabled = ! empty( $input['enabled'] ) ? '1' : '0';
		$push    = esc_url_raw( (string) ( $input['push_url'] ?? '' ) );
		$alert   = max( 1, (int) ( $input['alert_error_threshold'] ?? 25 ) );
		$rotate  = ! empty( $input['rotate_token'] );

		update_option( self::OPTION_ENABLED, $enabled, false );
		update_option( self::OPTION_PUSH_URL, $push, false );
		update_option( self::OPTION_ALERT_ERROR, $alert, false );
		if ( $rotate || '' === (string) get_option( self::OPTION_TOKEN, '' ) ) {
			self::ensure_token( true );
		}
		self::maybe_schedule_push();
		return self::settings();
	}

	/**
	 * @param bool $force Force new token.
	 * @return string
	 */
	public static function ensure_token( $force = false ) {
		$existing = (string) get_option( self::OPTION_TOKEN, '' );
		if ( ! $force && '' !== $existing ) {
			return $existing;
		}
		$token = wp_generate_password( 48, false, false );
		update_option( self::OPTION_TOKEN, $token, false );
		return $token;
	}

	/**
	 * Schedule push when webhook URL configured.
	 */
	public static function maybe_schedule_push() {
		$url = (string) get_option( self::OPTION_PUSH_URL, '' );
		if ( '' === $url || ! self::is_enabled() ) {
			$ts = wp_next_scheduled( self::CRON_PUSH );
			if ( $ts ) {
				wp_unschedule_event( $ts, self::CRON_PUSH );
			}
			return;
		}
		if ( ! wp_next_scheduled( self::CRON_PUSH ) ) {
			wp_schedule_event( time() + 120, 'hourly', self::CRON_PUSH );
		}
	}

	/**
	 * Increment counter when system log writes errors.
	 *
	 * @param string               $level   Level.
	 * @param string               $source  Source.
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function on_system_log( $level, $source, $channel, $message, $context = [] ) {
		unset( $source, $channel, $message, $context );
		if ( ! in_array( $level, [ 'error', 'critical' ], true ) ) {
			return;
		}
		self::bump( 'log_errors_total', 1 );
		$snapshot = self::snapshot();
		$threshold = (int) ( self::settings()['alert_error_threshold'] ?? 25 );
		$errors_1h = (int) ( $snapshot['gauges']['ngc_log_errors_1h'] ?? 0 );
		if ( $errors_1h >= $threshold && ! get_transient( 'ngc_metrics_alert_cooldown' ) ) {
			set_transient( 'ngc_metrics_alert_cooldown', 1, 15 * MINUTE_IN_SECONDS );
			/**
			 * External APM / alerting hook when error rate exceeds threshold.
			 *
			 * @param array<string, mixed> $snapshot Metrics snapshot.
			 * @param int                  $threshold Threshold.
			 */
			do_action( 'ngc_metrics_alert', $snapshot, $threshold );
			if ( class_exists( 'NGC_System_Log' ) ) {
				NGC_System_Log::warning(
					'observability',
					'alert',
					'Error rate threshold exceeded',
					[ 'errors_1h' => $errors_1h, 'threshold' => $threshold ]
				);
			}
		}
	}

	/**
	 * @param string $key   Counter key.
	 * @param int    $delta Delta.
	 */
	public static function bump( $key, $delta = 1 ) {
		$key      = sanitize_key( $key );
		$counters = get_transient( self::TRANSIENT_COUNTERS );
		if ( ! is_array( $counters ) ) {
			$counters = [];
		}
		$counters[ $key ] = (int) ( $counters[ $key ] ?? 0 ) + (int) $delta;
		set_transient( self::TRANSIENT_COUNTERS, $counters, DAY_IN_SECONDS );
	}

	/**
	 * Alias for bump (platform kernel + Prometheus series).
	 *
	 * @param string               $key    Counter key.
	 * @param int                  $delta  Delta.
	 * @param array<string,string> $labels Optional labels (folded into key suffix).
	 */
	public static function inc( $key, $delta = 1, $labels = [] ) {
		$suffix = '';
		if ( is_array( $labels ) && $labels ) {
			ksort( $labels );
			$parts = [];
			foreach ( $labels as $lk => $lv ) {
				$parts[] = sanitize_key( (string) $lk ) . '_' . sanitize_key( (string) $lv );
			}
			$suffix = '_' . implode( '_', $parts );
		}
		self::bump( sanitize_key( (string) $key ) . $suffix, $delta );
	}

	/**
	 * Store a gauge value in the counters transient under gauge_ prefix.
	 *
	 * @param string $key   Gauge key.
	 * @param float  $value Value.
	 */
	public static function set_gauge( $key, $value ) {
		$key      = 'gauge_' . sanitize_key( (string) $key );
		$counters = get_transient( self::TRANSIENT_COUNTERS );
		if ( ! is_array( $counters ) ) {
			$counters = [];
		}
		$counters[ $key ] = (float) $value;
		set_transient( self::TRANSIENT_COUNTERS, $counters, DAY_IN_SECONDS );
	}

	/**
	 * Full metrics snapshot (gauges + counters).
	 *
	 * @return array{generated_at:string,counters:array<string,int>,gauges:array<string,float|int>,labels:array<string,string>}
	 */
	public static function snapshot() {
		global $wpdb;
		$gauges   = [];
		$counters = get_transient( self::TRANSIENT_COUNTERS );
		if ( ! is_array( $counters ) ) {
			$counters = [];
		}

		$gauges['ngc_up'] = 1;

		$bookings = NGC_Database::table( 'bookings' );
		if ( $bookings ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$gauges['ngc_bookings_open'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$bookings} WHERE status IN ('requested','confirmed','in_progress')"
			);
		}

		$children = NGC_Database::table( 'child_learners' );
		if ( $children ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$gauges['ngc_child_learners_active'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$children} WHERE status = 'active'"
			);
		}

		$slog = NGC_Database::table( 'system_log' );
		if ( $slog ) {
			$since = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$gauges['ngc_log_errors_1h'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$slog} WHERE level IN ('error','critical') AND created_at >= %s",
					$since
				)
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$gauges['ngc_log_warnings_1h'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$slog} WHERE level = 'warning' AND created_at >= %s",
					$since
				)
			);
		}

		if ( class_exists( 'NGC_Safeguarding' ) && method_exists( 'NGC_Safeguarding', 'stats' ) ) {
			$sfg = NGC_Safeguarding::stats();
			$gauges['ngc_safeguarding_open']     = (int) ( $sfg['open'] ?? 0 );
			$gauges['ngc_safeguarding_breached'] = (int) ( $sfg['breached'] ?? 0 );
		}

		if ( class_exists( 'NGC_Fraud_Engine' ) && method_exists( 'NGC_Fraud_Engine', 'stats' ) ) {
			$frd = NGC_Fraud_Engine::stats();
			$gauges['ngc_fraud_open'] = (int) ( $frd['open'] ?? 0 );
		}

		if ( class_exists( 'NGC_Durable_Queue' ) ) {
			$qstats = NGC_Durable_Queue::stats();
			$gauges['ngc_queue_dlq_open'] = (int) ( $qstats['dlq_open'] ?? 0 );
			foreach ( (array) ( $qstats['queues'] ?? [] ) as $qname => $statuses ) {
				foreach ( (array) $statuses as $st => $cnt ) {
					$gauges[ 'ngc_queue_' . sanitize_key( $qname ) . '_' . sanitize_key( $st ) ] = (int) $cnt;
				}
			}
		}
		if ( class_exists( 'NGC_Ledger' ) ) {
			$gauges['ngc_ledger_cash'] = (float) NGC_Ledger::balance( 'cash' );
		}
		foreach ( $counters as $ck => $cv ) {
			if ( 0 === strpos( (string) $ck, 'gauge_' ) ) {
				$gauges[ 'ngc_' . substr( (string) $ck, 6 ) ] = (float) $cv;
			}
		}

		$outbox = $wpdb->prefix . 'ngc_event_outbox';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $outbox ) );
		if ( $exists ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$gauges['ngc_event_outbox_pending'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$outbox} WHERE status IN ('pending','failed')"
			);
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return [
			'generated_at' => gmdate( 'c' ),
			'counters'     => array_map( 'intval', $counters ),
			'gauges'       => $gauges,
			'labels'       => [
				'instance' => is_string( $host ) ? $host : 'unknown',
				'plugin'   => 'nextgencompanion',
				'version'  => defined( 'NGC_VERSION' ) ? NGC_VERSION : '0',
			],
		];
	}

	/**
	 * Prometheus exposition format.
	 *
	 * @return string
	 */
	public static function prometheus_text() {
		$snap   = self::snapshot();
		$labels = self::format_labels( $snap['labels'] );
		$lines  = [
			'# HELP ngc_up Companion process up.',
			'# TYPE ngc_up gauge',
		];

		foreach ( $snap['gauges'] as $name => $value ) {
			$safe = self::prom_name( $name );
			$lines[] = sprintf( '%s%s %s', $safe, $labels, self::prom_number( $value ) );
		}
		foreach ( $snap['counters'] as $name => $value ) {
			$safe = self::prom_name( 'ngc_' . $name );
			$lines[] = '# TYPE ' . $safe . ' counter';
			$lines[] = sprintf( '%s%s %s', $safe, $labels, self::prom_number( $value ) );
		}

		$lines[] = '';
		return implode( "\n", $lines );
	}

	/**
	 * @param array<string, string> $labels Labels.
	 * @return string
	 */
	private static function format_labels( $labels ) {
		$parts = [];
		foreach ( $labels as $k => $v ) {
			$parts[] = sanitize_key( $k ) . '="' . self::escape_label( (string) $v ) . '"';
		}
		return empty( $parts ) ? '' : '{' . implode( ',', $parts ) . '}';
	}

	/**
	 * @param string $value Label value.
	 * @return string
	 */
	private static function escape_label( $value ) {
		return str_replace( [ '\\', "\n", '"' ], [ '\\\\', '\\n', '\\"' ], $value );
	}

	/**
	 * @param string $name Metric name.
	 * @return string
	 */
	private static function prom_name( $name ) {
		$name = preg_replace( '/[^a-zA-Z0-9_:]/', '_', (string) $name );
		return $name ?: 'ngc_metric';
	}

	/**
	 * @param float|int $value Value.
	 * @return string
	 */
	private static function prom_number( $value ) {
		if ( is_float( $value ) ) {
			return rtrim( rtrim( sprintf( '%.6F', $value ), '0' ), '.' );
		}
		return (string) (int) $value;
	}

	/**
	 * Validate scrape/push token from request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function authorize_request( $request ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$expected = (string) get_option( self::OPTION_TOKEN, '' );
		if ( '' === $expected ) {
			return false;
		}
		$auth = (string) $request->get_header( 'authorization' );
		$token = '';
		if ( preg_match( '/^\s*Bearer\s+(\S+)/i', $auth, $m ) ) {
			$token = $m[1];
		}
		if ( '' === $token ) {
			$token = (string) $request->get_param( 'token' );
		}
		return hash_equals( $expected, $token );
	}

	/**
	 * Push JSON snapshot to configured webhook (Datadog/New Relic/custom collector).
	 *
	 * @return array{ok:bool,status:int,message:string}
	 */
	public static function push_to_webhook() {
		$url = (string) get_option( self::OPTION_PUSH_URL, '' );
		if ( '' === $url || ! self::is_enabled() ) {
			return [ 'ok' => false, 'status' => 0, 'message' => 'push_disabled' ];
		}

		$payload = [
			'source'  => 'nextgencompanion',
			'metrics' => self::snapshot(),
		];
		$response = wp_remote_post(
			$url,
			[
				'timeout' => 8,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . (string) get_option( self::OPTION_TOKEN, '' ),
					'User-Agent'    => 'NextGenCompanion-Metrics/' . ( defined( 'NGC_VERSION' ) ? NGC_VERSION : '1' ),
				],
				'body'    => wp_json_encode( $payload ),
			]
		);

		if ( is_wp_error( $response ) ) {
			if ( class_exists( 'NGC_System_Log' ) ) {
				NGC_System_Log::warning( 'observability', 'push', $response->get_error_message() );
			}
			return [ 'ok' => false, 'status' => 0, 'message' => $response->get_error_message() ];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$ok   = $code >= 200 && $code < 300;
		self::bump( 'metrics_push_total', 1 );
		if ( ! $ok ) {
			self::bump( 'metrics_push_failures_total', 1 );
		}
		return [
			'ok'      => $ok,
			'status'  => $code,
			'message' => $ok ? 'pushed' : 'http_' . $code,
		];
	}
}
