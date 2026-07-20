<?php
/**
 * Unified system log writer — central facade for all platform logging.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes structured log entries to wp_ngc_system_log and bridges legacy loggers.
 */
class NGC_System_Log {

	const LEVELS = [ 'debug', 'info', 'notice', 'warning', 'error', 'critical' ];

	/**
	 * Hook registration and bridges from existing log subsystems.
	 */
	public static function init() {
		add_action( 'ngc_audit_logged', [ __CLASS__, 'bridge_audit' ], 10, 6 );
		add_action( 'ngc_exception_logged', [ __CLASS__, 'bridge_exception' ], 10, 3 );
		add_action( 'ngc_workflow_logged', [ __CLASS__, 'bridge_workflow' ], 10, 3 );
		add_action( 'ngcpm_action_logged', [ __CLASS__, 'bridge_plugin_manager' ], 10, 3 );
		add_action( 'shutdown', [ __CLASS__, 'capture_last_php_error' ], 999 );
	}

	/**
	 * @param string               $level   Log level.
	 * @param string               $source  Module slug.
	 * @param string               $channel Channel (integration, workflow, system, …).
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Extra context.
	 * @return int Insert ID or 0.
	 */
	public static function write( $level, $source, $channel, $message, $context = [] ) {
		global $wpdb;

		$level = in_array( $level, self::LEVELS, true ) ? $level : 'info';

		if ( ! NGC_Database::table( 'system_log' ) ) {
			return 0;
		}

		$table = NGC_Database::table( 'system_log' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( ! $exists ) {
			NGC_Database::create_tables();
		}

		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$wpdb->insert(
			$table,
			[
				'uuid'           => class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4(),
				'level'          => $level,
				'channel'        => sanitize_key( $channel ),
				'source'         => sanitize_key( $source ),
				'message'        => sanitize_text_field( $message ),
				'context'        => wp_json_encode( is_array( $context ) ? $context : [] ),
				'user_id'        => (int) get_current_user_id(),
				'correlation_id' => sanitize_text_field( (string) ( $context['correlation_id'] ?? '' ) ),
				'ip_address'     => $ip,
				'created_at'     => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);

		$id = (int) $wpdb->insert_id;
		/**
		 * Fires after a system log row is written.
		 *
		 * @param string               $level   Level.
		 * @param string               $source  Source.
		 * @param string               $channel Channel.
		 * @param string               $message Message.
		 * @param array<string, mixed> $context Context.
		 */
		do_action( 'ngc_system_log_written', $level, $source, $channel, $message, is_array( $context ) ? $context : [] );

		return $id;
	}

	/**
	 * @param string               $source  Source.
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return int
	 */
	public static function debug( $source, $channel, $message, $context = [] ) {
		return self::write( 'debug', $source, $channel, $message, $context );
	}

	/**
	 * @param string               $source  Source.
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return int
	 */
	public static function info( $source, $channel, $message, $context = [] ) {
		return self::write( 'info', $source, $channel, $message, $context );
	}

	/**
	 * @param string               $source  Source.
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return int
	 */
	public static function warning( $source, $channel, $message, $context = [] ) {
		return self::write( 'warning', $source, $channel, $message, $context );
	}

	/**
	 * @param string               $source  Source.
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return int
	 */
	public static function error( $source, $channel, $message, $context = [] ) {
		return self::write( 'error', $source, $channel, $message, $context );
	}

	/**
	 * Bridge from NGC_Audit.
	 *
	 * @param string               $action      Action.
	 * @param string               $object_type Object type.
	 * @param int                  $object_id   Object ID.
	 * @param array<string, mixed> $context     Context.
	 * @param int                  $actor_id    Actor.
	 * @param array<string, mixed> $meta        Meta.
	 */
	public static function bridge_audit( $action, $object_type, $object_id, $context, $actor_id, $meta ) {
		self::info(
			'audit',
			'audit',
			sprintf( '%s on %s#%d', $action, $object_type, $object_id ),
			array_merge(
				$context,
				[
					'action'         => $action,
					'object_type'    => $object_type,
					'object_id'      => $object_id,
					'actor_user_id'  => $actor_id,
					'result'         => $meta['result'] ?? 'success',
					'correlation_id' => $meta['correlation_id'] ?? '',
				]
			)
		);
	}

	/**
	 * @param string               $type    Type.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function bridge_exception( $type, $message, $context ) {
		self::error( 'exception', 'diagnostics', $message, array_merge( $context, [ 'type' => $type ] ) );
	}

	/**
	 * @param string               $workflow_key Workflow key.
	 * @param string               $status       Status.
	 * @param array<string, mixed> $context      Context.
	 */
	public static function bridge_workflow( $workflow_key, $status, $context ) {
		$level = 'failed' === $status ? 'error' : 'info';
		self::write( $level, 'workflow', 'workflow', "Workflow {$workflow_key}: {$status}", $context );
	}

	/**
	 * @param string               $type    Type.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function bridge_plugin_manager( $type, $message, $context ) {
		self::info( 'plugin_manager', 'fleet', $message, array_merge( $context, [ 'event_type' => $type ] ) );
	}

	/**
	 * Capture fatal PHP errors on shutdown when WP_DEBUG_LOG is on.
	 */
	public static function capture_last_php_error() {
		$error = error_get_last();
		if ( ! $error || ! in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
			return;
		}
		self::error(
			'php',
			'runtime',
			$error['message'],
			[
				'file' => $error['file'],
				'line' => $error['line'],
				'type' => $error['type'],
			]
		);
	}
}
