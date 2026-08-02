<?php
/**
 * Workflow engine — triggers, actions, cron.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Workflows {

	const HEALTH_CRON = 'ngt_daily_health_check';

	public static function register_hooks(): void {
		add_action( self::HEALTH_CRON, [ __CLASS__, 'run_health_check' ] );
		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 10, 1 );
		add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'on_wc_order_completed' ], 10, 1 );
	}

	public static function import_bundled(): void {
		$path = NGT_HUB_DIR . 'config/workflows.json';
		if ( ! file_exists( $path ) ) {
			return;
		}
		$json = json_decode( (string) file_get_contents( $path ), true );
		if ( ! empty( $json['workflows'] ) ) {
			update_option( NGT_Hub::OPTION_WORKFLOWS, wp_json_encode( $json ), false );
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_workflows(): array {
		$raw = get_option( NGT_Hub::OPTION_WORKFLOWS, '' );
		if ( ! $raw ) {
			self::import_bundled();
			$raw = get_option( NGT_Hub::OPTION_WORKFLOWS, '' );
		}
		$data = json_decode( (string) $raw, true );
		return is_array( $data['workflows'] ?? null ) ? $data['workflows'] : [];
	}

	public static function schedule_health_cron(): void {
		if ( class_exists( 'NGT_Hub_Companion_Delegate', false ) && NGT_Hub_Companion_Delegate::companion_active() ) {
			self::unschedule_health_cron();
			NGT_Hub_Companion_Delegate::log(
				'info',
				'Skipped Hub health cron — Companion ngc_daily_health_check owns diagnostics.',
				[ 'hook' => self::HEALTH_CRON ]
			);
			return;
		}
		if ( ! wp_next_scheduled( self::HEALTH_CRON ) ) {
			wp_schedule_event( time(), 'daily', self::HEALTH_CRON );
		}
	}

	public static function unschedule_health_cron(): void {
		$ts = wp_next_scheduled( self::HEALTH_CRON );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HEALTH_CRON );
		}
	}

	public static function run_health_check(): void {
		if ( class_exists( 'NGT_Hub_Companion_Delegate', false ) && NGT_Hub_Companion_Delegate::companion_active() ) {
			NGT_Hub_Companion_Delegate::log( 'warning', 'Blocked Hub health cron run — Companion owns diagnostics.' );
			return;
		}
		NGT_Hub::fire_event(
			'ngt.daily.health_check',
			'system',
			0,
			0,
			[
				'timestamp'  => current_time( 'mysql', true ),
				'wp_version' => get_bloginfo( 'version' ),
				'php_version'=> phpversion(),
			]
		);
	}

	public static function fire_event( string $event_key, string $source = 'system', int $user_id = 0, int $object_id = 0, array $payload = [] ): void {
		global $wpdb;

		$payload = array_merge(
			[
				'event'    => $event_key,
				'source'   => $source,
				'user_id'  => $user_id,
				'object_id'=> $object_id,
			],
			$payload
		);

		$wpdb->insert(
			NGT_Hub_Database::table( 'events' ),
			[
				'event_key' => $event_key,
				'source'    => $source,
				'user_id'   => $user_id,
				'object_id' => $object_id,
				'payload'   => wp_json_encode( $payload ),
			],
			[ '%s', '%s', '%d', '%d', '%s' ]
		);

		do_action( 'ngt_automation_event_fired', $event_key, $payload );

		foreach ( self::get_workflows() as $workflow ) {
			if ( empty( $workflow['enabled'] ) ) {
				continue;
			}
			$trigger = $workflow['trigger']['event'] ?? '';
			if ( $trigger !== $event_key ) {
				continue;
			}
			foreach ( (array) ( $workflow['actions'] ?? [] ) as $action ) {
				self::run_action( $action, $payload );
			}
		}
	}

	/**
	 * @param array<string, mixed> $action Action definition.
	 * @param array<string, mixed> $vars   Template variables.
	 */
	private static function run_action( array $action, array $vars ): void {
		$type = $action['type'] ?? '';
		switch ( $type ) {
			case 'log_event':
				// Already logged in fire_event.
				break;

			case 'create_rtm_message':
				NGT_Hub_RTM::post_system_message(
					sanitize_key( $action['room'] ?? 'staff' ),
					self::render_template( (string) ( $action['message'] ?? '' ), $vars )
				);
				break;

			case 'wp_mail_admin':
				wp_mail(
					get_option( 'admin_email' ),
					self::render_template( (string) ( $action['subject'] ?? 'NextGen Alert' ), $vars ),
					self::render_template( (string) ( $action['message'] ?? '' ), $vars )
				);
				break;

			case 'wp_mail_user':
				$uid = (int) self::render_template( (string) ( $action['user_id'] ?? '{{user_id}}' ), $vars );
				$user = get_user_by( 'id', $uid );
				if ( $user ) {
					wp_mail(
						$user->user_email,
						self::render_template( (string) ( $action['subject'] ?? '' ), $vars ),
						self::render_template( (string) ( $action['message'] ?? '' ), $vars )
					);
				}
				break;

			case 'add_user_role':
				$uid  = (int) self::render_template( (string) ( $action['user_id'] ?? '{{user_id}}' ), $vars );
				$role = sanitize_key( $action['role'] ?? '' );
				if ( $uid && $role ) {
					$user = get_user_by( 'id', $uid );
					if ( $user ) {
						$user->add_role( $role );
					}
				}
				break;

			case 'create_support_case':
				wp_insert_post(
					[
						'post_type'    => 'ngt_support_case',
						'post_title'   => self::render_template( (string) ( $action['title'] ?? 'Support Case' ), $vars ),
						'post_content' => self::render_template( (string) ( $action['description'] ?? '' ), $vars ),
						'post_status'  => 'publish',
						'meta_input'   => [
							'ngt_priority' => sanitize_key( $action['priority'] ?? 'normal' ),
							'ngt_status'   => 'open',
						],
					]
				);
				break;
		}

		do_action( 'ngt_workflow_action_ran', $type, $action, $vars );
	}

	/**
	 * @param array<string, mixed> $vars Variables.
	 */
	private static function render_template( string $template, array $vars ): string {
		return (string) preg_replace_callback(
			'/\{\{(\w+)\}\}/',
			static function ( $m ) use ( $vars ) {
				$key = $m[1];
				if ( ! isset( $vars[ $key ] ) ) {
					return '';
				}
				$val = $vars[ $key ];
				return is_array( $val ) ? implode( ', ', $val ) : (string) $val;
			},
			$template
		);
	}

	public static function on_user_register( int $user_id ): void {
		NGT_Hub::fire_event( 'wp.user_registered', 'wordpress', $user_id, 0, [ 'user_id' => $user_id ] );
	}

	public static function on_wc_order_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );
		NGT_Hub::fire_event(
			'woocommerce.order.completed',
			'woocommerce',
			$order ? (int) $order->get_user_id() : 0,
			$order_id,
			[
				'order_id' => $order_id,
				'user_id'  => $order ? (int) $order->get_user_id() : 0,
			]
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $workflows Workflows.
	 */
	public static function save_workflows( array $workflows ): void {
		$data = [
			'schema'    => 'ngt_workflow_pack',
			'version'   => NGT_Hub::VERSION,
			'workflows' => $workflows,
		];
		update_option( NGT_Hub::OPTION_WORKFLOWS, wp_json_encode( $data ), false );
	}

	public static function toggle_workflow( string $key, bool $enabled ): void {
		$workflows = self::get_workflows();
		foreach ( $workflows as &$wf ) {
			if ( ( $wf['key'] ?? '' ) === $key ) {
				$wf['enabled'] = $enabled;
				break;
			}
		}
		unset( $wf );
		self::save_workflows( $workflows );
	}
}
