<?php
/**
 * Multi-channel notification dispatch (in-app + email + webhooks + Teams/Slack/SMS/WhatsApp).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes operational alerts to configured channels.
 */
final class NGC_Intelligence_Dispatch {

	/**
	 * Validate outbound webhook URL (SSRF guard).
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_safe_webhook_url( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}
		if ( 'https' !== strtolower( (string) $parts['scheme'] ) ) {
			return false;
		}
		$host = strtolower( (string) $parts['host'] );
		$blocked_hosts = [ 'localhost', '127.0.0.1', '0.0.0.0', '[::1]', 'metadata.google.internal' ];
		if ( in_array( $host, $blocked_hosts, true ) ) {
			return false;
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return (bool) filter_var(
				$host,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			);
		}
		return true;
	}

	/**
	 * @param string               $type    Notification type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @param bool                 $dedupe  Dedupe flag.
	 * @return int In-app notification ID.
	 */
	public static function notify( $type, $title, $message, array $meta = [], $dedupe = false ) {
		$id = NGC_Intelligence_Notifications::create( $type, $title, $message, $meta, $dedupe );

		$config = NGC_Intelligence_Config::get();
		$should_external = in_array( $type, [ 'error', 'critical', 'warning' ], true )
			|| ! empty( $config['notify_all_channels'] );

		if ( ! $should_external ) {
			return $id;
		}

		if ( ! empty( $config['notify_email'] ) && is_email( $config['notify_email'] ) ) {
			wp_mail(
				$config['notify_email'],
				'[NextGen Intel] ' . $title,
				$message . "\n\n" . wp_json_encode( $meta, JSON_PRETTY_PRINT ),
				[ 'Content-Type: text/plain; charset=UTF-8' ]
			);
		}

		self::dispatch_channels( $type, $title, $message, $meta, $config );

		return $id;
	}

	/**
	 * @param string               $type    Type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @param array<string, mixed> $config  Config.
	 */
	private static function dispatch_channels( $type, $title, $message, array $meta, array $config ) {
		$map = [
			'webhook_url'        => 'generic',
			'teams_webhook_url'  => 'teams',
			'slack_webhook_url'  => 'slack',
			'whatsapp_webhook_url' => 'whatsapp',
			'sms_webhook_url'    => 'sms',
		];
		foreach ( $map as $key => $channel ) {
			if ( empty( $config[ $key ] ) ) {
				continue;
			}
			$body = NGC_Intelligence_Channels::format( $channel, $type, $title, $message, $meta );
			self::webhook( (string) $config[ $key ], $body, $channel );
		}
	}

	/**
	 * @param string               $url     Webhook URL.
	 * @param array<string, mixed> $body    Payload.
	 * @param string               $channel Channel id for logging.
	 */
	public static function webhook( $url, array $body, $channel = 'generic' ) {
		$url = esc_url_raw( $url );
		if ( ! self::is_safe_webhook_url( $url ) ) {
			if ( class_exists( 'NGC_System_Log' ) ) {
				NGC_System_Log::warning( 'intelligence', 'webhook_blocked', 'Unsafe webhook URL blocked', [ 'channel' => $channel ] );
			}
			return;
		}
		$config = NGC_Intelligence_Config::get();
		$secret = (string) ( $config['webhook_secret'] ?? '' );
		$headers = [
			'Content-Type' => 'application/json',
			'User-Agent'   => 'NextGen-Intelligence/1.1',
			'X-NGC-Channel' => sanitize_key( $channel ),
		];
		if ( $secret ) {
			$headers['X-NGC-Signature'] = hash_hmac( 'sha256', wp_json_encode( $body ), $secret );
		}
		wp_safe_remote_post(
			$url,
			[
				'timeout'  => 8,
				'headers'  => $headers,
				'body'     => wp_json_encode( $body ),
				'blocking' => false,
				'reject_unsafe_urls' => true,
			]
		);
	}
}
