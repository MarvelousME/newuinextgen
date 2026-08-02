<?php
/**
 * Auto-instrumentation collectors bridging existing platform hooks.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Universal event collectors — no duplicate reporting in plugins.
 */
final class NGC_Intelligence_Collectors {

	private static $rest_start = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		if ( ! NGC_Intelligence_Config::is_enabled() ) {
			return;
		}

		$config = NGC_Intelligence_Config::get();

		add_action( 'ngc_system_log_written', [ __CLASS__, 'on_system_log' ], 10, 5 );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow' ], 10, 2 );
		add_action( 'ngc_workflow_completed', [ __CLASS__, 'on_workflow_completed' ], 10, 3 );
		add_action( 'ngc_intelligence_event_ingested', [ __CLASS__, 'on_ingested' ], 10, 2 );

		if ( ! empty( $config['collect_auth'] ) ) {
			add_action( 'wp_login', [ __CLASS__, 'on_login' ], 10, 2 );
			add_action( 'wp_login_failed', [ __CLASS__, 'on_login_failed' ], 10, 1 );
			add_action( 'user_register', [ __CLASS__, 'on_register' ], 10, 1 );
		}

		if ( ! empty( $config['collect_rest'] ) ) {
			add_filter( 'rest_pre_dispatch', [ __CLASS__, 'rest_pre_dispatch' ], 10, 3 );
			add_filter( 'rest_post_dispatch', [ __CLASS__, 'rest_post_dispatch' ], 10, 3 );
		}

		if ( ! empty( $config['collect_bookings'] ) ) {
			add_action( 'ngc_booking_created', [ __CLASS__, 'on_booking_created' ], 10, 1 );
		}

		if ( ! empty( $config['collect_audit'] ) ) {
			add_action( 'ngc_audit_logged', [ __CLASS__, 'on_audit' ], 10, 6 );
		}

		if ( ! empty( $config['collect_exceptions'] ) ) {
			add_action( 'ngc_exception_logged', [ __CLASS__, 'on_exception' ], 10, 3 );
		}

		if ( ! empty( $config['collect_security'] ) ) {
			add_action( 'wp_authenticate_user', [ __CLASS__, 'on_auth_check' ], 10, 2 );
		}

		add_action( 'activated_plugin', [ __CLASS__, 'on_plugin_change' ], 10, 1 );
		add_action( 'deactivated_plugin', [ __CLASS__, 'on_plugin_deactivated' ], 10, 1 );
		add_action( 'upgrader_process_complete', [ __CLASS__, 'on_upgrader' ], 10, 2 );

		if ( class_exists( 'NGC_Domain_Event_Bridge' ) ) {
			add_action( 'ngc_domain_event', [ __CLASS__, 'on_domain_event' ], 10, 1 );
		}

		add_action( 'ngc_health_check_completed', [ __CLASS__, 'on_health_check' ], 10, 1 );
		add_action( 'ngc_match_accepted', [ __CLASS__, 'on_match_accepted' ], 10, 1 );
		add_action( 'ngc_studio_notification_sent', [ __CLASS__, 'on_notification_sent' ], 10, 4 );
	}

	/**
	 * @param string               $level   Level.
	 * @param string               $source  Source.
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function on_system_log( $level, $source, $channel, $message, $context = [] ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'system.log.' . sanitize_key( $level ),
				'plugin_slug' => 'companion',
				'module'      => sanitize_key( $source ),
				'feature'     => sanitize_key( $channel ),
				'severity'    => $level,
				'outcome'     => in_array( $level, [ 'error', 'critical' ], true ) ? 'failure' : 'success',
				'message'     => $message,
				'payload'     => is_array( $context ) ? $context : [],
				'source'      => 'collector_system_log',
			]
		);
	}

	/**
	 * @param string               $key     Workflow key.
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function on_workflow( $key, $payload ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'workflow.dispatched',
				'plugin_slug' => 'companion',
				'module'      => 'workflows',
				'feature'     => sanitize_key( $key ),
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'Workflow dispatched: ' . $key,
				'payload'     => is_array( $payload ) ? $payload : [],
				'source'      => 'collector_workflow',
			]
		);
	}

	/**
	 * @param WP_User $user     User.
	 * @param string  $username Username.
	 */
	public static function on_login( $user, $username ) {
		unset( $username );
		NGC_Intelligence::emit(
			[
				'event_key'   => 'auth.login',
				'plugin_slug' => 'wordpress',
				'module'      => 'auth',
				'severity'    => 'info',
				'outcome'     => 'success',
				'user_id'     => $user instanceof WP_User ? $user->ID : 0,
				'message'     => 'User logged in',
				'source'      => 'collector_auth',
			]
		);
	}

	/**
	 * @param int $user_id User ID.
	 */
	public static function on_register( $user_id ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'user.registered',
				'plugin_slug' => 'wordpress',
				'module'      => 'users',
				'severity'    => 'info',
				'outcome'     => 'success',
				'user_id'     => (int) $user_id,
				'message'     => 'User registered',
				'source'      => 'collector_auth',
			]
		);
	}

	/**
	 * @param string $username Username.
	 */
	public static function on_login_failed( $username ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'auth.login_failed',
				'plugin_slug' => 'wordpress',
				'module'      => 'auth',
				'severity'    => 'warning',
				'outcome'     => 'failure',
				'message'     => 'Failed login attempt',
				'payload'     => [ 'username' => sanitize_user( (string) $username ) ],
				'source'      => 'collector_auth',
			]
		);
	}

	/**
	 * @param WP_User|WP_Error $user     User.
	 * @param string           $password Password.
	 * @return WP_User|WP_Error
	 */
	public static function on_auth_check( $user, $password ) {
		unset( $password );
		if ( is_wp_error( $user ) ) {
			NGC_Intelligence::emit(
				[
					'event_key'   => 'security.auth_blocked',
					'plugin_slug' => 'wordpress',
					'module'      => 'security',
					'severity'    => 'warning',
					'outcome'     => 'failure',
					'message'     => $user->get_error_message(),
					'source'      => 'collector_security',
				]
			);
		}
		return $user;
	}

	/**
	 * @param mixed $booking_id Booking ID or context.
	 */
	public static function on_booking_created( $booking_id ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'booking.created',
				'plugin_slug' => 'companion',
				'module'      => 'bookings',
				'domain'      => 'bookings',
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'Booking created',
				'payload'     => is_array( $booking_id ) ? $booking_id : [ 'booking_id' => $booking_id ],
				'source'      => 'collector_booking',
			]
		);
	}

	/**
	 * @param string               $workflow Workflow.
	 * @param array<string, mixed> $context  Context.
	 * @param mixed                $result   Result.
	 */
	public static function on_workflow_completed( $workflow, $context, $result ) {
		$failed = is_wp_error( $result ) || ( is_array( $result ) && ! empty( $result['error'] ) );
		NGC_Intelligence::emit(
			[
				'event_key'   => 'workflow.completed',
				'plugin_slug' => 'companion',
				'module'      => 'workflows',
				'feature'     => sanitize_key( (string) $workflow ),
				'severity'    => $failed ? 'error' : 'info',
				'outcome'     => $failed ? 'failure' : 'success',
				'message'     => 'Workflow completed: ' . $workflow,
				'payload'     => [
					'context' => is_array( $context ) ? $context : [],
					'result'  => is_wp_error( $result ) ? $result->get_error_message() : $result,
				],
				'source'      => 'collector_workflow',
			]
		);
	}

	/**
	 * @param string               $action      Action.
	 * @param string               $object_type Type.
	 * @param int                  $object_id   ID.
	 * @param array<string, mixed> $context     Context.
	 * @param int                  $actor_id    Actor.
	 * @param array<string, mixed> $meta        Meta.
	 */
	public static function on_audit( $action, $object_type, $object_id, $context, $actor_id, $meta ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'audit.' . sanitize_key( $action ),
				'plugin_slug' => 'companion',
				'module'      => 'audit',
				'severity'    => 'notice',
				'outcome'     => 'success',
				'user_id'     => (int) $actor_id,
				'message'     => $action . ' on ' . $object_type,
				'payload'     => [
					'object_type' => $object_type,
					'object_id'   => $object_id,
					'context'     => is_array( $context ) ? $context : [],
					'meta'        => is_array( $meta ) ? $meta : [],
				],
				'source'      => 'collector_audit',
			]
		);
	}

	/**
	 * @param string               $type    Type.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function on_exception( $type, $message, $context ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'exception.' . sanitize_key( $type ),
				'plugin_slug' => 'companion',
				'module'      => 'diagnostics',
				'severity'    => 'error',
				'outcome'     => 'failure',
				'message'     => $message,
				'payload'     => is_array( $context ) ? $context : [],
				'source'      => 'collector_exception',
			]
		);
	}

	/**
	 * @param array<string, mixed> $report Health report.
	 */
	public static function on_health_check( $report ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'platform.health.check',
				'plugin_slug' => 'companion',
				'module'      => 'observability',
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'Health check completed',
				'payload'     => is_array( $report ) ? $report : [],
				'source'      => 'collector_health',
				'force'       => true,
			]
		);
	}

	/**
	 * @param int $match_id Match ID.
	 */
	public static function on_match_accepted( $match_id ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'matching.accepted',
				'plugin_slug' => 'companion',
				'module'      => 'matching',
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'Match accepted',
				'payload'     => [ 'match_id' => (int) $match_id ],
				'source'      => 'collector_matching',
			]
		);
	}

	/**
	 * @param string               $key     Key.
	 * @param string               $channel Channel.
	 * @param array<string, mixed> $context Context.
	 * @param mixed                $result  Result.
	 */
	public static function on_notification_sent( $key, $channel, $context, $result ) {
		$failed = is_wp_error( $result ) || ( is_array( $result ) && empty( $result['ok'] ) );
		NGC_Intelligence::emit(
			[
				'event_key'   => 'notification.' . ( $failed ? 'failed' : 'sent' ),
				'plugin_slug' => 'companion',
				'module'      => 'notifications',
				'feature'     => sanitize_key( $channel ),
				'severity'    => $failed ? 'error' : 'info',
				'outcome'     => $failed ? 'failure' : 'success',
				'message'     => 'Notification ' . $key . ' via ' . $channel,
				'payload'     => [
					'key'     => $key,
					'context' => is_array( $context ) ? $context : [],
				],
				'source'      => 'collector_notification',
			]
		);
	}

	/**
	 * @param WP_Upgrader $upgrader Upgrader.
	 * @param array       $options  Options.
	 */
	public static function on_upgrader( $upgrader, $options ) {
		unset( $upgrader );
		if ( empty( $options['action'] ) || 'update' !== $options['action'] ) {
			return;
		}
		NGC_Intelligence::emit(
			[
				'event_key'   => 'plugin.updated',
				'plugin_slug' => 'platform',
				'severity'    => 'notice',
				'message'     => 'Package updated: ' . ( $options['type'] ?? 'unknown' ),
				'payload'     => $options,
				'source'      => 'collector_plugin',
			]
		);
	}

	/**
	 * @param mixed            $result  Result.
	 * @param WP_REST_Server   $server  Server.
	 * @param WP_REST_Request  $request Request.
	 * @return mixed
	 */
	public static function rest_pre_dispatch( $result, $server, $request ) {
		unset( $server );
		if ( ! $request instanceof WP_REST_Request ) {
			return $result;
		}
		$route = $request->get_route();
		if ( 0 === strpos( $route, '/ngc/v1/intelligence/stream' ) ) {
			return $result;
		}
		self::$rest_start[ spl_object_hash( $request ) ] = microtime( true );
		return $result;
	}

	/**
	 * @param WP_REST_Response|WP_Error $response Response.
	 * @param WP_REST_Server            $server   Server.
	 * @param WP_REST_Request           $request  Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_post_dispatch( $response, $server, $request ) {
		unset( $server );
		if ( ! $request instanceof WP_REST_Request ) {
			return $response;
		}
		$key = spl_object_hash( $request );
		$start = self::$rest_start[ $key ] ?? null;
		unset( self::$rest_start[ $key ] );
		$duration = $start ? (int) round( ( microtime( true ) - $start ) * 1000 ) : null;
		$route    = $request->get_route();
		$status   = $response instanceof WP_REST_Response ? $response->get_status() : 500;
		$plugin   = 'unknown';
		if ( 0 === strpos( $route, '/ngc/v1' ) ) {
			$plugin = 'companion';
		} elseif ( 0 === strpos( $route, '/ngt-hub/v1' ) || 0 === strpos( $route, '/ngt/v1' ) ) {
			$plugin = 'automation-hub';
		} elseif ( 0 === strpos( $route, '/ngtai/v1' ) ) {
			$plugin = 'ai-integration';
		}

		NGC_Intelligence::emit(
			[
				'event_key'    => 'api.rest.request',
				'plugin_slug'  => $plugin,
				'module'       => 'rest',
				'feature'      => sanitize_key( str_replace( '/', '_', trim( $route, '/' ) ) ),
				'severity'     => $status >= 500 ? 'error' : ( $status >= 400 ? 'warning' : 'info' ),
				'outcome'      => $status < 400 ? 'success' : 'failure',
				'duration_ms'  => $duration,
				'message'      => $request->get_method() . ' ' . $route,
				'payload'      => [ 'status' => $status, 'method' => $request->get_method() ],
				'source'       => 'collector_rest',
			]
		);
		return $response;
	}

	/**
	 * @param string $plugin Plugin file.
	 */
	public static function on_plugin_change( $plugin ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'plugin.activated',
				'plugin_slug' => 'platform',
				'severity'    => 'notice',
				'message'     => 'Plugin activated: ' . $plugin,
				'payload'     => [ 'plugin' => $plugin ],
				'source'      => 'collector_plugin',
			]
		);
	}

	/**
	 * @param string $plugin Plugin file.
	 */
	public static function on_plugin_deactivated( $plugin ) {
		NGC_Intelligence::emit(
			[
				'event_key'   => 'plugin.deactivated',
				'plugin_slug' => 'platform',
				'severity'    => 'warning',
				'message'     => 'Plugin deactivated: ' . $plugin,
				'payload'     => [ 'plugin' => $plugin ],
				'source'      => 'collector_plugin',
			]
		);
	}

	/**
	 * @param array<string, mixed> $envelope Domain event.
	 */
	public static function on_domain_event( $envelope ) {
		if ( ! is_array( $envelope ) ) {
			return;
		}
		NGC_Intelligence::emit(
			[
				'event_key'   => sanitize_key( (string) ( $envelope['event'] ?? 'domain.event' ) ),
				'plugin_slug' => 'companion',
				'module'      => 'agents',
				'severity'    => 'info',
				'payload'     => $envelope,
				'source'      => 'collector_domain',
			]
		);
	}

	/**
	 * @param array<string, mixed> $event Event.
	 * @param int                  $id    ID.
	 */
	public static function on_ingested( $event, $id ) {
		unset( $event, $id );
	}
}
