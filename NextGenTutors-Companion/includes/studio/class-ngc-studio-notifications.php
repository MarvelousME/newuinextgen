<?php
/**
 * Multi-channel notification designer.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Studio notifications with hot-apply dispatcher.
 */
class NGC_Studio_Notifications {

	/** @var array<string, array<string, mixed>> */
	private static $published = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_studio_notifications_reload', [ __CLASS__, 'reload_published' ] );
		self::reload_published();
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function channel_catalog() {
		return apply_filters(
			'ngc_studio_notification_channels',
			[
				'email'     => [ 'label' => 'Email', 'adapter' => 'email' ],
				'sms'       => [ 'label' => 'SMS', 'adapter' => 'webhook' ],
				'whatsapp'  => [ 'label' => 'WhatsApp', 'adapter' => 'webhook' ],
				'push'      => [ 'label' => 'Push', 'adapter' => 'action' ],
				'dashboard' => [ 'label' => 'Dashboard', 'adapter' => 'action' ],
				'toast'     => [ 'label' => 'Toast', 'adapter' => 'action' ],
				'slack'     => [ 'label' => 'Slack', 'adapter' => 'webhook' ],
				'teams'     => [ 'label' => 'Microsoft Teams', 'adapter' => 'webhook' ],
			]
		);
	}

	/**
	 * @param int                  $id   Notification ID.
	 * @param array<string, mixed> $data Payload.
	 * @return array{ok:bool,notification?:array<string,mixed>}
	 */
	public static function save_and_apply( $id, $data ) {
		$result = NGC_Studio_Repository::update_notification( $id, $data );
		if ( empty( $result['ok'] ) ) {
			return $result;
		}
		self::reload_published();
		return $result;
	}

	/**
	 * @param int $id Notification ID.
	 * @return array{ok:bool,notification?:array<string,mixed>}
	 */
	public static function publish( $id ) {
		return self::save_and_apply( $id, [ 'status' => 'published' ] );
	}

	/**
	 * Reload published notifications.
	 */
	public static function reload_published() {
		self::$published = [];
		foreach ( NGC_Studio_Repository::list_notifications( 'published' ) as $row ) {
			self::$published[ (string) $row['notification_key'] ] = $row;
		}
	}

	/**
	 * Dispatch notification by key or channel config.
	 *
	 * @param string               $key     Notification key or channel.
	 * @param array<string, mixed> $context Payload.
	 * @param bool                 $simulate Simulation.
	 * @return array{ok:bool,channel?:string,message?:string}
	 */
	public static function dispatch( $key, $context = [], $simulate = false ) {
		$row = self::$published[ $key ] ?? null;
		$channel = $row ? (string) ( $row['channel'] ?? 'email' ) : sanitize_key( $key );
		$config  = (array) ( $row['config'] ?? $context );

		if ( $simulate ) {
			return [ 'ok' => true, 'channel' => $channel, 'simulated' => true ];
		}

		$idem = isset( $context['idempotency_key'] ) ? (string) $context['idempotency_key'] : ( 'notify:' . sanitize_key( (string) $key ) . ':' . md5( wp_json_encode( $context ) ) );
		if ( class_exists( 'NGC_Idempotency' ) && ! empty( $context['idempotency_key'] ) ) {
			$once = NGC_Idempotency::once(
				$idem,
				static function () use ( $key, $context, $channel, $config ) {
					return self::dispatch_inner( $key, $context, $channel, $config );
				},
				NGC_Idempotency::fingerprint( is_array( $context ) ? $context : [] ),
				'notify'
			);
			return is_array( $once ) ? $once : [ 'ok' => ! is_wp_error( $once ), 'channel' => $channel ];
		}

		return self::dispatch_inner( $key, $context, $channel, $config );
	}

	/**
	 * @param string               $key     Key.
	 * @param array<string,mixed>  $context Context.
	 * @param string               $channel Channel.
	 * @param array<string,mixed>  $config  Config.
	 * @return array{ok:bool,channel?:string,message?:string}
	 */
	private static function dispatch_inner( $key, $context, $channel, $config ) {
		$result = [ 'ok' => true, 'channel' => $channel ];

		switch ( $channel ) {
			case 'email':
				if ( class_exists( 'NGC_Email_Adapter' ) ) {
					$email = new NGC_Email_Adapter();
					$result = $email->create_or_update(
						'send_template',
						[
							'template_key' => (string) ( $config['template_key'] ?? 'admin_notification' ),
							'to'           => (string) ( $config['to'] ?? $context['email'] ?? get_option( 'admin_email' ) ),
							'context'      => $context,
						]
					);
				}
				break;
			case 'dashboard':
			case 'toast':
			case 'push':
				set_transient(
					'ngc_studio_notice_' . get_current_user_id(),
					[
						'channel' => $channel,
						'message' => (string) ( $config['message'] ?? $context['message'] ?? '' ),
						'at'      => time(),
					],
					300
				);
				do_action( 'ngc_studio_dashboard_notification', $channel, $config, $context );
				break;
			case 'sms':
			case 'whatsapp':
			case 'slack':
			case 'teams':
				$url = (string) ( $config['webhook_url'] ?? '' );
				if ( $url ) {
					wp_remote_post(
						$url,
						[
							'body'    => wp_json_encode( array_merge( $context, [ 'channel' => $channel ] ) ),
							'timeout' => 15,
						]
					);
				}
				NGC_Workflows::dispatch( 'notification.' . $channel, $context );
				break;
			default:
				do_action( 'ngc_studio_notification_dispatch', $channel, $config, $context );
		}

		do_action( 'ngc_studio_notification_sent', $key, $channel, $context, $result );

		return $result;
	}

	/**
	 * Seed default notifications.
	 */
	public static function seed_defaults() {
		if ( NGC_Studio_Repository::list_notifications() ) {
			return;
		}
		NGC_Studio_Repository::create_notification(
			[
				'notification_key' => 'parent_booking_confirmed',
				'name'             => 'Parent Booking Confirmed',
				'channel'          => 'email',
				'status'           => 'published',
				'config'           => [
					'template_key' => 'booking_confirmed',
					'to'           => '{{email}}',
					'timing'       => 'immediate',
				],
			]
		);
		NGC_Studio_Repository::create_notification(
			[
				'notification_key' => 'admin_payment_failed',
				'name'             => 'Admin Payment Failed Alert',
				'channel'          => 'dashboard',
				'status'           => 'published',
				'config'           => [
					'message'   => 'Payment failed for {{email}}',
					'escalation'=> true,
					'retry'     => true,
				],
			]
		);
	}
}
