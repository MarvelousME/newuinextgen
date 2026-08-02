<?php
/**
 * Intelligence platform configuration (hot-reload via options).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central reporting configuration.
 */
final class NGC_Intelligence_Config {

	public const OPTION = 'ngc_intelligence_config';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return [
			'enabled'                 => true,
			'retention_days'          => 90,
			'refresh_interval_ms'     => 5000,
			'sse_enabled'             => true,
			'sampling_rate'           => 1.0,
			'collect_rest'            => true,
			'collect_auth'            => true,
			'collect_workflows'       => true,
			'collect_bookings'        => true,
			'collect_payments'        => true,
			'collect_security'        => true,
			'collect_ajax'            => true,
			'collect_audit'           => true,
			'collect_exceptions'      => true,
			'alert_error_threshold'   => 25,
			'alert_booking_failures'  => 3,
			'mask_pii'                => true,
			'timezone'                => wp_timezone_string(),
			'notify_email'            => '',
			'notify_webhook'          => false,
			'notify_all_channels'     => false,
			'webhook_url'             => '',
			'webhook_secret'          => '',
			'teams_webhook_url'       => '',
			'slack_webhook_url'       => '',
			'whatsapp_webhook_url'    => '',
			'sms_webhook_url'         => '',
			'whatsapp_to'             => '',
			'sms_to'                  => '',
			'dashboard_theme'         => 'auto',
			'export_permissions'      => 'admin',
			'ai_analysis_enabled'     => true,
			'maintenance_window'      => false,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * API-safe config — masks secrets returned to admin UI.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_for_api() {
		$config = self::get();
		if ( ! empty( $config['webhook_secret'] ) ) {
			$config['webhook_secret'] = '********';
			$config['webhook_secret_set'] = true;
		} else {
			$config['webhook_secret_set'] = false;
		}
		unset( $config['webhook_secret'] );
		return $config;
	}

	/**
	 * @param array<string, mixed> $patch Patch.
	 * @return array<string, mixed>
	 */
	public static function save( array $patch ) {
		$current = self::get();
		$allowed = array_keys( self::defaults() );
		foreach ( $patch as $key => $value ) {
			if ( ! in_array( $key, $allowed, true ) ) {
				continue;
			}
			$default = self::defaults()[ $key ];
			if ( is_bool( $default ) ) {
				$current[ $key ] = (bool) $value;
			} elseif ( is_int( $default ) ) {
				$current[ $key ] = (int) $value;
			} elseif ( is_float( $default ) ) {
				$current[ $key ] = (float) $value;
			} elseif ( 'notify_email' === $key ) {
				$current[ $key ] = sanitize_email( (string) $value );
			} elseif ( in_array( $key, [ 'webhook_url', 'teams_webhook_url', 'slack_webhook_url', 'whatsapp_webhook_url', 'sms_webhook_url' ], true ) ) {
				$url = esc_url_raw( (string) $value );
				if ( '' !== $url && class_exists( 'NGC_Intelligence_Dispatch' ) && ! NGC_Intelligence_Dispatch::is_safe_webhook_url( $url ) ) {
					continue;
				}
				$current[ $key ] = $url;
			} elseif ( 'webhook_secret' === $key ) {
				$secret = sanitize_text_field( (string) $value );
				if ( '' !== $secret && '********' !== $secret ) {
					$current[ $key ] = $secret;
				}
			} else {
				$current[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		$current['sampling_rate'] = min( 1.0, max( 0.01, (float) $current['sampling_rate'] ) );
		$current['retention_days'] = max( 7, min( 365, (int) $current['retention_days'] ) );
		update_option( self::OPTION, $current, false );
		NGC_Intelligence_Audit::log( 'config.updated', [ 'keys' => array_keys( $patch ) ] );
		return $current;
	}

	/**
	 * @return bool
	 */
	public static function is_enabled() {
		$c = self::get();
		return ! empty( $c['enabled'] );
	}

	/**
	 * @return bool
	 */
	public static function should_sample() {
		$c = self::get();
		return ( mt_rand() / mt_getrandmax() ) <= (float) $c['sampling_rate'];
	}

	/**
	 * @param string $collector Collector key e.g. collect_rest.
	 * @return bool
	 */
	public static function collects( $collector ) {
		$c = self::get();
		return ! empty( $c[ $collector ] );
	}
}
