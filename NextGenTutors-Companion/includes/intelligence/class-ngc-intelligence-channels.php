<?php
/**
 * Channel-specific webhook payload templates (Teams, Slack, SMS, WhatsApp).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formats operational alerts for external notification channels.
 */
final class NGC_Intelligence_Channels {

	/**
	 * @param string               $channel teams|slack|whatsapp|sms|generic.
	 * @param string               $type    Alert type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @return array<string, mixed>
	 */
	public static function format( $channel, $type, $title, $message, array $meta = [] ) {
		switch ( sanitize_key( $channel ) ) {
			case 'teams':
				return self::teams( $type, $title, $message, $meta );
			case 'slack':
				return self::slack( $type, $title, $message, $meta );
			case 'whatsapp':
				return self::whatsapp( $type, $title, $message, $meta );
			case 'sms':
				return self::sms( $type, $title, $message, $meta );
			default:
				return [
					'type'    => $type,
					'title'   => $title,
					'message' => $message,
					'meta'    => $meta,
					'site'    => site_url(),
					'at'      => gmdate( 'c' ),
				];
		}
	}

	/**
	 * @param string               $type    Type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @return array<string, mixed>
	 */
	public static function teams( $type, $title, $message, array $meta ) {
		$color = self::color_for_type( $type );
		return [
			'@type'    => 'MessageCard',
			'@context' => 'https://schema.org/extensions',
			'summary'  => $title,
			'themeColor' => $color,
			'title'    => '[NextGen Intel] ' . $title,
			'text'     => $message,
			'sections' => [
				[
					'facts' => [
						[ 'name' => 'Severity', 'value' => strtoupper( $type ) ],
						[ 'name' => 'Site', 'value' => site_url() ],
						[ 'name' => 'Time (UTC)', 'value' => gmdate( 'c' ) ],
					],
				],
			],
			'potentialAction' => [
				[
					'@type' => 'OpenUri',
					'name'  => 'Open Mission Control',
					'targets' => [
						[ 'os' => 'default', 'uri' => admin_url( 'admin.php?page=ngtmc-mission-control&tab=intelligence' ) ],
					],
				],
			],
			'meta' => $meta,
		];
	}

	/**
	 * @param string               $type    Type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @return array<string, mixed>
	 */
	public static function slack( $type, $title, $message, array $meta ) {
		$emoji = self::emoji_for_type( $type );
		return [
			'text'    => $emoji . ' *' . $title . "*\n" . $message,
			'blocks'  => [
				[
					'type' => 'header',
					'text' => [ 'type' => 'plain_text', 'text' => $emoji . ' ' . $title ],
				],
				[
					'type' => 'section',
					'text' => [ 'type' => 'mrkdwn', 'text' => $message ],
				],
				[
					'type' => 'context',
					'elements' => [
						[ 'type' => 'mrkdwn', 'text' => '*Severity:* `' . $type . '` · *Site:* ' . site_url() ],
					],
				],
				[
					'type' => 'actions',
					'elements' => [
						[
							'type' => 'button',
							'text' => [ 'type' => 'plain_text', 'text' => 'Mission Control' ],
							'url'  => admin_url( 'admin.php?page=ngtmc-mission-control&tab=intelligence' ),
						],
					],
				],
			],
			'meta' => $meta,
		];
	}

	/**
	 * Meta Cloud API compatible template (via configured webhook gateway).
	 *
	 * @param string               $type    Type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @return array<string, mixed>
	 */
	public static function whatsapp( $type, $title, $message, array $meta ) {
		$config = NGC_Intelligence_Config::get();
		$to     = (string) ( $config['whatsapp_to'] ?? '' );
		return [
			'messaging_product' => 'whatsapp',
			'to'              => $to,
			'type'            => 'template',
			'template'        => [
				'name'     => 'nextgen_intel_alert',
				'language' => [ 'code' => 'en' ],
				'components' => [
					[
						'type'       => 'body',
						'parameters' => [
							[ 'type' => 'text', 'text' => strtoupper( $type ) ],
							[ 'type' => 'text', 'text' => $title ],
							[ 'type' => 'text', 'text' => wp_trim_words( $message, 40 ) ],
						],
					],
				],
			],
			'fallback_text' => sprintf( '[%s] %s: %s', strtoupper( $type ), $title, $message ),
			'meta'          => $meta,
		];
	}

	/**
	 * Twilio-compatible JSON (via SMS gateway webhook).
	 *
	 * @param string               $type    Type.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @return array<string, mixed>
	 */
	public static function sms( $type, $title, $message, array $meta ) {
		$config = NGC_Intelligence_Config::get();
		return [
			'to'   => (string) ( $config['sms_to'] ?? '' ),
			'body' => sprintf( '[NextGen %s] %s — %s', strtoupper( $type ), $title, wp_trim_words( $message, 24 ) ),
			'meta' => $meta,
		];
	}

	/**
	 * @param string $type Type.
	 * @return string
	 */
	private static function color_for_type( $type ) {
		$map = [
			'critical' => 'B91C1C',
			'error'    => 'DC2626',
			'warning'  => 'D97706',
			'success'  => '059669',
			'info'     => '2563EB',
		];
		return $map[ $type ] ?? '64748B';
	}

	/**
	 * @param string $type Type.
	 * @return string
	 */
	private static function emoji_for_type( $type ) {
		$map = [
			'critical' => '🚨',
			'error'    => '❌',
			'warning'  => '⚠️',
			'success'  => '✅',
			'info'     => 'ℹ️',
		];
		return $map[ $type ] ?? '📊';
	}
}
