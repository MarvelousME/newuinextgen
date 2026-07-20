<?php
/**
 * Hot-reload runtime registry — no restart required on save.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * In-memory compiled workflow registry with dynamic hook registration.
 */
class NGC_Studio_Runtime {

	/** @var array<int, array<string, mixed>> */
	private static $workflows = [];

	/** @var array<string, array<int>> trigger_key => workflow ids */
	private static $trigger_index = [];

	/** @var array<string, array{callable:callable,priority:int}> */
	private static $hook_handles = [];

	/** @var bool */
	private static $booted = false;

	/**
	 * Bootstrap — load published workflows and register listeners.
	 */
	public static function init() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		self::reload_all();
		add_action( 'ngc_studio_event', [ __CLASS__, 'dispatch_event' ], 10, 2 );
	}

	/**
	 * Reload all published workflows from DB (hot apply).
	 */
	public static function reload_all() {
		if ( ! self::tables_ready() ) {
			return;
		}
		self::clear_hooks();
		self::$workflows    = [];
		self::$trigger_index = [];

		foreach ( NGC_Studio_Repository::list_workflows( 'published' ) as $wf ) {
			self::register_workflow( $wf );
		}

		do_action( 'ngc_studio_runtime_reloaded', self::$workflows );
	}

	/**
	 * Apply a single workflow immediately after save/publish.
	 *
	 * @param array<string, mixed> $workflow Workflow row.
	 * @return array{ok:bool,message?:string}
	 */
	public static function apply_workflow( $workflow ) {
		$id = (int) ( $workflow['id'] ?? 0 );
		if ( ! $id ) {
			return [ 'ok' => false, 'message' => __( 'Invalid workflow.', 'nextgencompanion' ) ];
		}

		$compile = NGC_Studio_Compiler::compile( (array) ( $workflow['graph'] ?? [] ) );
		if ( empty( $compile['ok'] ) ) {
			return [ 'ok' => false, 'message' => implode( '; ', (array) ( $compile['errors'] ?? [] ) ) ];
		}

		$compiled = $compile['compiled'];
		NGC_Studio_Repository::update_workflow(
			$id,
			[
				'compiled' => $compiled,
				'version'  => (int) ( $workflow['version'] ?? 1 ) + ( 'published' === ( $workflow['status'] ?? '' ) ? 0 : 0 ),
			]
		);

		if ( 'published' === ( $workflow['status'] ?? '' ) ) {
			$workflow['compiled'] = $compiled;
			self::unregister_workflow( $id );
			self::register_workflow( $workflow );
			NGC_Studio_Repository::sync_triggers( $id, (array) ( $compiled['triggers'] ?? [] ) );
			NGC_Studio_Repository::save_version_snapshot( $id, $compiled );
		}

		update_option( 'ngc_studio_last_apply', [ 'workflow_id' => $id, 'at' => time() ], false );

		return [ 'ok' => true ];
	}

	/**
	 * @param array<string, mixed> $workflow Workflow.
	 */
	public static function register_workflow( $workflow ) {
		$id = (int) ( $workflow['id'] ?? 0 );
		if ( ! $id || empty( $workflow['compiled']['plan'] ) ) {
			return;
		}
		self::$workflows[ $id ] = $workflow;
		foreach ( (array) ( $workflow['compiled']['triggers'] ?? [] ) as $trigger ) {
			$key = sanitize_key( (string) ( $trigger['key'] ?? '' ) );
			if ( ! $key ) {
				continue;
			}
			self::$trigger_index[ $key ]   = self::$trigger_index[ $key ] ?? [];
			self::$trigger_index[ $key ][] = $id;
			self::ensure_hook_listener( $key );
		}
	}

	/**
	 * @param int $workflow_id Workflow ID.
	 */
	public static function unregister_workflow( $workflow_id ) {
		$workflow_id = (int) $workflow_id;
		unset( self::$workflows[ $workflow_id ] );
		foreach ( self::$trigger_index as $key => $ids ) {
			self::$trigger_index[ $key ] = array_values( array_filter( $ids, static fn( $id ) => (int) $id !== $workflow_id ) );
		}
	}

	/**
	 * @param string               $event   Trigger key.
	 * @param array<string, mixed> $context Context.
	 */
	public static function dispatch_event( $event, $context = [] ) {
		$key = sanitize_key( (string) $event );
		$ids = self::$trigger_index[ $key ] ?? [];
		foreach ( $ids as $workflow_id ) {
			$wf = self::$workflows[ (int) $workflow_id ] ?? null;
			if ( $wf ) {
				NGC_Studio_Engine::execute( $wf, $context, $key );
			}
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function active_workflows() {
		return array_values( self::$workflows );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function status() {
		return [
			'active_workflows' => count( self::$workflows ),
			'active_triggers'  => count( self::$trigger_index ),
			'registered_hooks' => count( self::$hook_handles ),
			'last_apply'       => get_option( 'ngc_studio_last_apply', [] ),
		];
	}

	/**
	 * @param string $trigger_key Trigger.
	 */
	private static function ensure_hook_listener( $trigger_key ) {
		if ( isset( self::$hook_handles[ $trigger_key ] ) ) {
			return;
		}
		$hook_map = NGC_Studio_Triggers::hook_map();
		$wp_hook  = $hook_map[ $trigger_key ] ?? 'ngc_studio_custom_event';
		$callable = static function () use ( $trigger_key ) {
			$args    = func_get_args();
			$context = is_array( $args[0] ?? null ) ? $args[0] : [ 'args' => $args ];
			NGC_Studio_Event_Bus::emit( $trigger_key, $context );
		};
		add_action( $wp_hook, $callable, 20, 99 );
		self::$hook_handles[ $trigger_key ] = [ 'callable' => $callable, 'hook' => $wp_hook ];
	}

	/**
	 * Remove dynamically registered hooks.
	 */
	private static function clear_hooks() {
		foreach ( self::$hook_handles as $key => $handle ) {
			remove_action( $handle['hook'], $handle['callable'], 20 );
		}
		self::$hook_handles = [];
	}

	/**
	 * @return bool
	 */
	private static function tables_ready() {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( ! $table ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
	}
}
