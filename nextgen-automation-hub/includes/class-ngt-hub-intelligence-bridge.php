<?php
/**
 * Automation Hub → Companion intelligence bridge.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emits Hub automation events into the central intelligence bus.
 */
final class NGT_Hub_Intelligence_Bridge {

	/**
	 * Register hooks after Companion loads.
	 */
	public static function register_hooks(): void {
		add_action( 'plugins_loaded', [ __CLASS__, 'boot' ], 30 );
	}

	/**
	 * Wire collectors when intelligence SDK is available.
	 */
	public static function boot(): void {
		if ( ! class_exists( 'NGC_Intelligence' ) ) {
			return;
		}
		add_action( 'ngt_automation_event_fired', [ __CLASS__, 'on_event' ], 10, 2 );
		add_action( 'ngt_notification_created', [ __CLASS__, 'on_notification' ], 10, 3 );
		add_action( 'ngt_workflow_action_ran', [ __CLASS__, 'on_action_ran' ], 10, 3 );
	}

	/**
	 * @param string               $event_key Event key.
	 * @param array<string, mixed> $payload   Payload.
	 */
	public static function on_event( $event_key, $payload ): void {
		$severity = self::severity_for_key( $event_key );
		NGC_Intelligence::emit(
			[
				'event_key'   => sanitize_key( str_replace( '.', '_', $event_key ) ),
				'plugin_slug' => 'automation-hub',
				'module'      => sanitize_key( (string) ( $payload['source'] ?? 'workflows' ) ),
				'domain'      => 'workflows',
				'severity'    => $severity,
				'outcome'     => str_contains( $event_key, 'fail' ) ? 'failure' : 'success',
				'user_id'     => (int) ( $payload['user_id'] ?? 0 ),
				'message'     => 'Hub event: ' . $event_key,
				'payload'     => is_array( $payload ) ? $payload : [],
				'source'      => 'hub_intelligence_bridge',
			]
		);
	}

	/**
	 * @param int    $id      Notification ID.
	 * @param int    $user_id User ID.
	 * @param string $type    Type.
	 */
	public static function on_notification( $id, $user_id, $type ): void {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'notification.hub_created',
				'plugin_slug' => 'automation-hub',
				'module'      => 'notifications',
				'severity'    => 'info',
				'outcome'     => 'success',
				'user_id'     => (int) $user_id,
				'message'     => 'Hub notification: ' . $type,
				'payload'     => [ 'id' => (int) $id, 'type' => $type ],
				'source'      => 'hub_intelligence_bridge',
			]
		);
	}

	/**
	 * @param string               $type   Action type.
	 * @param array<string, mixed> $action Action.
	 * @param array<string, mixed> $vars   Vars.
	 */
	public static function on_action_ran( $type, $action, $vars ): void {
		$key = sanitize_key( (string) $type );
		NGC_Intelligence::emit(
			[
				'event_key'   => 'workflow.action.' . $key,
				'plugin_slug' => 'automation-hub',
				'module'      => 'workflows',
				'severity'    => 'info',
				'message'     => 'Workflow action ran: ' . $key,
				'payload'     => [ 'action' => $action, 'vars' => $vars ],
				'source'      => 'hub_intelligence_bridge',
			]
		);
	}

	/**
	 * @param string $event_key Event key.
	 * @return string
	 */
	private static function severity_for_key( $event_key ) {
		if ( str_contains( $event_key, 'fail' ) || str_contains( $event_key, 'error' ) ) {
			return 'error';
		}
		if ( str_contains( $event_key, 'warn' ) ) {
			return 'warning';
		}
		return 'info';
	}
}

NGT_Hub_Intelligence_Bridge::register_hooks();
