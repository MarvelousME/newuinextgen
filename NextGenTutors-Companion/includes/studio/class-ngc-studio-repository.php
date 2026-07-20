<?php
/**
 * Studio workflow persistence layer.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD for studio workflows, forms, emails, notifications, executions.
 */
class NGC_Studio_Repository {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_workflows( $status = '' ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( ! $table ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [];
		}
		if ( $status ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY updated_at DESC", sanitize_key( $status ) ),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY updated_at DESC", ARRAY_A );
		}
		return array_map( [ __CLASS__, 'decode_workflow_row' ], (array) $rows );
	}

	/**
	 * @param int $id Workflow ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_workflow( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( ! $table ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
		return $row ? self::decode_workflow_row( $row ) : null;
	}

	/**
	 * @param string $key Workflow key.
	 * @return array<string, mixed>|null
	 */
	public static function get_workflow_by_key( $key ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( ! $table ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE workflow_key = %s", sanitize_key( $key ) ), ARRAY_A );
		return $row ? self::decode_workflow_row( $row ) : null;
	}

	/**
	 * @param array<string, mixed> $data Workflow payload.
	 * @return array{ok:bool,id?:int,workflow?:array<string,mixed>,message?:string}
	 */
	public static function create_workflow( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( ! $table ) {
			return [ 'ok' => false, 'message' => __( 'Studio tables missing.', 'nextgencompanion' ) ];
		}

		$key = sanitize_key( (string) ( $data['workflow_key'] ?? $data['key'] ?? '' ) );
		if ( ! $key ) {
			$key = 'wf_' . wp_generate_password( 8, false, false );
		}
		if ( self::get_workflow_by_key( $key ) ) {
			return [ 'ok' => false, 'message' => __( 'Workflow key already exists.', 'nextgencompanion' ) ];
		}

		$graph = self::normalize_graph( $data['graph'] ?? $data['graph_json'] ?? [] );
		$user  = get_current_user_id();

		$inserted = $wpdb->insert(
			$table,
			[
				'workflow_key'  => $key,
				'name'            => sanitize_text_field( (string) ( $data['name'] ?? $key ) ),
				'description'     => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
				'status'          => 'draft',
				'version'         => 1,
				'graph_json'      => wp_json_encode( $graph ),
				'compiled_json'   => '',
				'settings_json'   => wp_json_encode( (array) ( $data['settings'] ?? [] ) ),
				'template_key'    => sanitize_key( (string) ( $data['template_key'] ?? '' ) ),
				'created_by'      => $user,
				'updated_by'      => $user,
			],
			[ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d' ]
		);

		if ( ! $inserted ) {
			return [ 'ok' => false, 'message' => __( 'Failed to create workflow.', 'nextgencompanion' ) ];
		}

		$id = (int) $wpdb->insert_id;
		return [ 'ok' => true, 'id' => $id, 'workflow' => self::get_workflow( $id ) ];
	}

	/**
	 * @param int                  $id   Workflow ID.
	 * @param array<string, mixed> $data Patch payload.
	 * @return array{ok:bool,workflow?:array<string,mixed>,message?:string}
	 */
	public static function update_workflow( $id, $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		$existing = self::get_workflow( $id );
		if ( ! $existing || ! $table ) {
			return [ 'ok' => false, 'message' => __( 'Workflow not found.', 'nextgencompanion' ) ];
		}

		$update = [ 'updated_by' => get_current_user_id() ];
		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( (string) $data['name'] );
		}
		if ( isset( $data['description'] ) ) {
			$update['description'] = sanitize_textarea_field( (string) $data['description'] );
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( (string) $data['status'] );
		}
		if ( isset( $data['graph'] ) || isset( $data['graph_json'] ) ) {
			$update['graph_json'] = wp_json_encode( self::normalize_graph( $data['graph'] ?? $data['graph_json'] ?? [] ) );
		}
		if ( isset( $data['compiled'] ) || isset( $data['compiled_json'] ) ) {
			$update['compiled_json'] = wp_json_encode( (array) ( $data['compiled'] ?? $data['compiled_json'] ?? [] ) );
		}
		if ( isset( $data['settings'] ) || isset( $data['settings_json'] ) ) {
			$update['settings_json'] = wp_json_encode( (array) ( $data['settings'] ?? $data['settings_json'] ?? [] ) );
		}
		if ( isset( $data['version'] ) ) {
			$update['version'] = (int) $data['version'];
		}

		$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		return [ 'ok' => true, 'workflow' => self::get_workflow( $id ) ];
	}

	/**
	 * @param int $id Workflow ID.
	 * @return array{ok:bool,message?:string}
	 */
	public static function delete_workflow( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( ! $table || ! self::get_workflow( $id ) ) {
			return [ 'ok' => false, 'message' => __( 'Workflow not found.', 'nextgencompanion' ) ];
		}
		$wpdb->delete( $table, [ 'id' => (int) $id ], [ '%d' ] );
		self::delete_triggers_for_workflow( $id );
		NGC_Studio_Runtime::unregister_workflow( (int) $id );
		return [ 'ok' => true ];
	}

	/**
	 * @param int                  $workflow_id Workflow ID.
	 * @param array<string, mixed> $compiled    Compiled plan.
	 * @return void
	 */
	public static function save_version_snapshot( $workflow_id, $compiled ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_versions' );
		$wf    = self::get_workflow( $workflow_id );
		if ( ! $table || ! $wf ) {
			return;
		}
		$wpdb->insert(
			$table,
			[
				'workflow_id'    => (int) $workflow_id,
				'version'        => (int) ( $wf['version'] ?? 1 ),
				'graph_json'     => wp_json_encode( $wf['graph'] ?? [] ),
				'compiled_json'  => wp_json_encode( $compiled ),
				'snapshot_json'  => wp_json_encode( $wf ),
				'published_by'   => get_current_user_id(),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%d' ]
		);
	}

	/**
	 * @param int                  $workflow_id Workflow ID.
	 * @param array<int, array<string, mixed>> $triggers Triggers.
	 */
	public static function sync_triggers( $workflow_id, $triggers ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_triggers' );
		if ( ! $table ) {
			return;
		}
		$wpdb->delete( $table, [ 'workflow_id' => (int) $workflow_id ], [ '%d' ] );
		foreach ( $triggers as $trigger ) {
			$wpdb->insert(
				$table,
				[
					'workflow_id'  => (int) $workflow_id,
					'trigger_key'  => sanitize_key( (string) ( $trigger['key'] ?? '' ) ),
					'trigger_type' => sanitize_key( (string) ( $trigger['type'] ?? 'event' ) ),
					'config_json'  => wp_json_encode( (array) ( $trigger['config'] ?? [] ) ),
					'is_active'    => 1,
				],
				[ '%d', '%s', '%s', '%s', '%d' ]
			);
		}
	}

	/**
	 * @param int $workflow_id Workflow ID.
	 */
	public static function delete_triggers_for_workflow( $workflow_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_triggers' );
		if ( $table ) {
			$wpdb->delete( $table, [ 'workflow_id' => (int) $workflow_id ], [ '%d' ] );
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_active_triggers() {
		global $wpdb;
		$table = NGC_Database::table( 'studio_triggers' );
		if ( ! $table ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE is_active = 1", ARRAY_A );
		return array_map(
			static function ( $row ) {
				$row['config'] = json_decode( (string) ( $row['config_json'] ?? '' ), true ) ?: [];
				return $row;
			},
			(array) $rows
		);
	}

	/**
	 * @param array<string, mixed> $data Execution row.
	 * @return int
	 */
	public static function create_execution( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_executions' );
		if ( ! $table ) {
			return 0;
		}
		$wpdb->insert(
			$table,
			[
				'workflow_id'      => (int) ( $data['workflow_id'] ?? 0 ),
				'workflow_version' => (int) ( $data['workflow_version'] ?? 1 ),
				'trigger_event'    => sanitize_text_field( (string) ( $data['trigger_event'] ?? '' ) ),
				'status'           => sanitize_key( (string) ( $data['status'] ?? 'running' ) ),
				'context_json'     => wp_json_encode( (array) ( $data['context'] ?? [] ) ),
				'path_json'        => wp_json_encode( (array) ( $data['path'] ?? [] ) ),
				'results_json'     => wp_json_encode( (array) ( $data['results'] ?? [] ) ),
				'duration_ms'      => (int) ( $data['duration_ms'] ?? 0 ),
				'error_message'    => sanitize_text_field( (string) ( $data['error_message'] ?? '' ) ),
				'is_simulation'    => ! empty( $data['is_simulation'] ) ? 1 : 0,
				'actor_id'         => (int) ( $data['actor_id'] ?? get_current_user_id() ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d' ]
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param int                  $id   Execution ID.
	 * @param array<string, mixed> $data Patch.
	 */
	public static function update_execution( $id, $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_executions' );
		if ( ! $table || ! $id ) {
			return;
		}
		$update = [];
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( (string) $data['status'] );
		}
		if ( isset( $data['path'] ) ) {
			$update['path_json'] = wp_json_encode( (array) $data['path'] );
		}
		if ( isset( $data['results'] ) ) {
			$update['results_json'] = wp_json_encode( (array) $data['results'] );
		}
		if ( isset( $data['duration_ms'] ) ) {
			$update['duration_ms'] = (int) $data['duration_ms'];
		}
		if ( isset( $data['error_message'] ) ) {
			$update['error_message'] = sanitize_text_field( (string) $data['error_message'] );
		}
		if ( ! empty( $data['completed'] ) ) {
			$update['completed_at'] = current_time( 'mysql', true );
		}
		if ( $update ) {
			$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		}
	}

	/**
	 * @param int $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_executions( $limit = 50 ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_executions' );
		if ( ! $table ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY started_at DESC LIMIT %d", (int) $limit ), ARRAY_A );
		return array_map( [ __CLASS__, 'decode_execution_row' ], (array) $rows );
	}

	/**
	 * @param int $id Execution ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_execution( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_executions' );
		if ( ! $table ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
		return $row ? self::decode_execution_row( $row ) : null;
	}

	/**
	 * @param array<string, mixed> $row DB row.
	 * @return array<string, mixed>
	 */
	private static function decode_workflow_row( $row ) {
		$row['graph']     = json_decode( (string) ( $row['graph_json'] ?? '' ), true ) ?: [ 'nodes' => [], 'edges' => [] ];
		$row['compiled']  = json_decode( (string) ( $row['compiled_json'] ?? '' ), true ) ?: [];
		$row['settings']  = json_decode( (string) ( $row['settings_json'] ?? '' ), true ) ?: [];
		unset( $row['graph_json'], $row['compiled_json'], $row['settings_json'] );
		return $row;
	}

	/**
	 * @param array<string, mixed> $row DB row.
	 * @return array<string, mixed>
	 */
	private static function decode_execution_row( $row ) {
		$row['context'] = json_decode( (string) ( $row['context_json'] ?? '' ), true ) ?: [];
		$row['path']    = json_decode( (string) ( $row['path_json'] ?? '' ), true ) ?: [];
		$row['results'] = json_decode( (string) ( $row['results_json'] ?? '' ), true ) ?: [];
		unset( $row['context_json'], $row['path_json'], $row['results_json'] );
		return $row;
	}

	/**
	 * @param mixed $graph Graph payload.
	 * @return array{nodes:array,edges:array}
	 */
	private static function normalize_graph( $graph ) {
		if ( is_string( $graph ) ) {
			$graph = json_decode( $graph, true );
		}
		if ( ! is_array( $graph ) ) {
			return [ 'nodes' => [], 'edges' => [] ];
		}
		return [
			'nodes' => array_values( (array) ( $graph['nodes'] ?? [] ) ),
			'edges' => array_values( (array) ( $graph['edges'] ?? [] ) ),
		];
	}

	// --- Forms CRUD ---

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_forms( $status = '' ) {
		return self::list_entity( 'studio_forms', $status, 'decode_form_row' );
	}

	/**
	 * @param int $id Form ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_form( $id ) {
		return self::get_entity( 'studio_forms', $id, 'decode_form_row' );
	}

	/**
	 * @param string $key Form key.
	 * @return array<string, mixed>|null
	 */
	public static function get_form_by_key( $key ) {
		return self::get_entity_by_key( 'studio_forms', 'form_key', $key, 'decode_form_row' );
	}

	/**
	 * @param array<string, mixed> $data Form data.
	 * @return array{ok:bool,id?:int,form?:array<string,mixed>,message?:string}
	 */
	public static function create_form( $data ) {
		$key = sanitize_key( (string) ( $data['form_key'] ?? $data['key'] ?? '' ) );
		if ( ! $key ) {
			$key = 'form_' . wp_generate_password( 8, false, false );
		}
		if ( self::get_form_by_key( $key ) ) {
			return [ 'ok' => false, 'message' => __( 'Form key exists.', 'nextgencompanion' ) ];
		}
		global $wpdb;
		$table = NGC_Database::table( 'studio_forms' );
		$wpdb->insert(
			$table,
			[
				'form_key'      => $key,
				'name'          => sanitize_text_field( (string) ( $data['name'] ?? $key ) ),
				'status'        => sanitize_key( (string) ( $data['status'] ?? 'draft' ) ),
				'schema_json'   => wp_json_encode( (array) ( $data['schema'] ?? $data['fields'] ?? [] ) ),
				'workflow_id'   => (int) ( $data['workflow_id'] ?? 0 ),
				'settings_json' => wp_json_encode( (array) ( $data['settings'] ?? [] ) ),
			],
			[ '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
		$id = (int) $wpdb->insert_id;
		return [ 'ok' => true, 'id' => $id, 'form' => self::get_form( $id ) ];
	}

	/**
	 * @param int                  $id   Form ID.
	 * @param array<string, mixed> $data Patch.
	 * @return array{ok:bool,form?:array<string,mixed>}
	 */
	public static function update_form( $id, $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_forms' );
		if ( ! self::get_form( $id ) ) {
			return [ 'ok' => false ];
		}
		$update = [];
		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( (string) $data['name'] );
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( (string) $data['status'] );
		}
		if ( isset( $data['schema'] ) || isset( $data['fields'] ) ) {
			$update['schema_json'] = wp_json_encode( (array) ( $data['schema'] ?? $data['fields'] ?? [] ) );
		}
		if ( isset( $data['workflow_id'] ) ) {
			$update['workflow_id'] = (int) $data['workflow_id'];
		}
		if ( isset( $data['settings'] ) ) {
			$update['settings_json'] = wp_json_encode( (array) $data['settings'] );
		}
		if ( $update ) {
			$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		}
		return [ 'ok' => true, 'form' => self::get_form( $id ) ];
	}

	/**
	 * @param int $id Form ID.
	 * @return array{ok:bool}
	 */
	public static function delete_form( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_forms' );
		$wpdb->delete( $table, [ 'id' => (int) $id ], [ '%d' ] );
		return [ 'ok' => true ];
	}

	// --- Emails CRUD ---

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_emails( $status = '' ) {
		return self::list_entity( 'studio_emails', $status, 'decode_email_row' );
	}

	/**
	 * @param int $id Email ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_email( $id ) {
		return self::get_entity( 'studio_emails', $id, 'decode_email_row' );
	}

	/**
	 * @param string $key Email key.
	 * @return array<string, mixed>|null
	 */
	public static function get_email_by_key( $key ) {
		return self::get_entity_by_key( 'studio_emails', 'email_key', $key, 'decode_email_row' );
	}

	/**
	 * @param array<string, mixed> $data Email data.
	 * @return array{ok:bool,id?:int,email?:array<string,mixed>,message?:string}
	 */
	public static function create_email( $data ) {
		$key = sanitize_key( (string) ( $data['email_key'] ?? $data['key'] ?? '' ) );
		if ( ! $key ) {
			$key = 'email_' . wp_generate_password( 8, false, false );
		}
		if ( self::get_email_by_key( $key ) ) {
			return [ 'ok' => false, 'message' => __( 'Email key exists.', 'nextgencompanion' ) ];
		}
		global $wpdb;
		$table = NGC_Database::table( 'studio_emails' );
		$wpdb->insert(
			$table,
			[
				'email_key'         => $key,
				'name'              => sanitize_text_field( (string) ( $data['name'] ?? $key ) ),
				'status'            => sanitize_key( (string) ( $data['status'] ?? 'draft' ) ),
				'subject'           => sanitize_text_field( (string) ( $data['subject'] ?? '' ) ),
				'body_html'         => wp_kses_post( (string) ( $data['body_html'] ?? $data['html'] ?? '' ) ),
				'body_text'         => sanitize_textarea_field( (string) ( $data['body_text'] ?? $data['text'] ?? '' ) ),
				'merge_fields_json' => wp_json_encode( (array) ( $data['merge_fields'] ?? [] ) ),
				'workflow_id'       => (int) ( $data['workflow_id'] ?? 0 ),
				'settings_json'     => wp_json_encode( (array) ( $data['settings'] ?? [] ) ),
				'version'           => 1,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d' ]
		);
		$id = (int) $wpdb->insert_id;
		return [ 'ok' => true, 'id' => $id, 'email' => self::get_email( $id ) ];
	}

	/**
	 * @param int                  $id   Email ID.
	 * @param array<string, mixed> $data Patch.
	 * @return array{ok:bool,email?:array<string,mixed>}
	 */
	public static function update_email( $id, $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_emails' );
		if ( ! self::get_email( $id ) ) {
			return [ 'ok' => false ];
		}
		$update = [];
		foreach (
			[
				'name'    => 'sanitize_text_field',
				'status'  => 'sanitize_key',
				'subject' => 'sanitize_text_field',
			] as $field => $fn
		) {
			if ( isset( $data[ $field ] ) ) {
				$update[ $field ] = $fn( (string) $data[ $field ] );
			}
		}
		if ( isset( $data['body_html'] ) || isset( $data['html'] ) ) {
			$update['body_html'] = wp_kses_post( (string) ( $data['body_html'] ?? $data['html'] ?? '' ) );
		}
		if ( isset( $data['body_text'] ) || isset( $data['text'] ) ) {
			$update['body_text'] = sanitize_textarea_field( (string) ( $data['body_text'] ?? $data['text'] ?? '' ) );
		}
		if ( isset( $data['merge_fields'] ) ) {
			$update['merge_fields_json'] = wp_json_encode( (array) $data['merge_fields'] );
		}
		if ( isset( $data['workflow_id'] ) ) {
			$update['workflow_id'] = (int) $data['workflow_id'];
		}
		if ( isset( $data['settings'] ) ) {
			$update['settings_json'] = wp_json_encode( (array) $data['settings'] );
		}
		if ( isset( $data['version'] ) ) {
			$update['version'] = (int) $data['version'];
		}
		if ( $update ) {
			$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		}
		return [ 'ok' => true, 'email' => self::get_email( $id ) ];
	}

	/**
	 * @param int $id Email ID.
	 * @return array{ok:bool}
	 */
	public static function delete_email( $id ) {
		global $wpdb;
		$wpdb->delete( NGC_Database::table( 'studio_emails' ), [ 'id' => (int) $id ], [ '%d' ] );
		return [ 'ok' => true ];
	}

	// --- Notifications CRUD ---

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_notifications( $status = '' ) {
		return self::list_entity( 'studio_notifications', $status, 'decode_notification_row' );
	}

	/**
	 * @param int $id Notification ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_notification( $id ) {
		return self::get_entity( 'studio_notifications', $id, 'decode_notification_row' );
	}

	/**
	 * @param string $key Notification key.
	 * @return array<string, mixed>|null
	 */
	public static function get_notification_by_key( $key ) {
		return self::get_entity_by_key( 'studio_notifications', 'notification_key', $key, 'decode_notification_row' );
	}

	/**
	 * @param array<string, mixed> $data Notification data.
	 * @return array{ok:bool,id?:int,notification?:array<string,mixed>,message?:string}
	 */
	public static function create_notification( $data ) {
		$key = sanitize_key( (string) ( $data['notification_key'] ?? $data['key'] ?? '' ) );
		if ( ! $key ) {
			$key = 'notify_' . wp_generate_password( 8, false, false );
		}
		if ( self::get_notification_by_key( $key ) ) {
			return [ 'ok' => false, 'message' => __( 'Notification key exists.', 'nextgencompanion' ) ];
		}
		global $wpdb;
		$table = NGC_Database::table( 'studio_notifications' );
		$wpdb->insert(
			$table,
			[
				'notification_key' => $key,
				'name'             => sanitize_text_field( (string) ( $data['name'] ?? $key ) ),
				'channel'          => sanitize_key( (string) ( $data['channel'] ?? 'email' ) ),
				'status'           => sanitize_key( (string) ( $data['status'] ?? 'draft' ) ),
				'config_json'      => wp_json_encode( (array) ( $data['config'] ?? [] ) ),
				'workflow_id'      => (int) ( $data['workflow_id'] ?? 0 ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%d' ]
		);
		$id = (int) $wpdb->insert_id;
		return [ 'ok' => true, 'id' => $id, 'notification' => self::get_notification( $id ) ];
	}

	/**
	 * @param int                  $id   Notification ID.
	 * @param array<string, mixed> $data Patch.
	 * @return array{ok:bool,notification?:array<string,mixed>}
	 */
	public static function update_notification( $id, $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_notifications' );
		if ( ! self::get_notification( $id ) ) {
			return [ 'ok' => false ];
		}
		$update = [];
		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( (string) $data['name'] );
		}
		if ( isset( $data['channel'] ) ) {
			$update['channel'] = sanitize_key( (string) $data['channel'] );
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( (string) $data['status'] );
		}
		if ( isset( $data['config'] ) ) {
			$update['config_json'] = wp_json_encode( (array) $data['config'] );
		}
		if ( isset( $data['workflow_id'] ) ) {
			$update['workflow_id'] = (int) $data['workflow_id'];
		}
		if ( $update ) {
			$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		}
		return [ 'ok' => true, 'notification' => self::get_notification( $id ) ];
	}

	/**
	 * @param int $id Notification ID.
	 * @return array{ok:bool}
	 */
	public static function delete_notification( $id ) {
		global $wpdb;
		$wpdb->delete( NGC_Database::table( 'studio_notifications' ), [ 'id' => (int) $id ], [ '%d' ] );
		return [ 'ok' => true ];
	}

	// --- Dashboards CRUD ---

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_dashboards( $status = '' ) {
		return self::list_entity( 'studio_dashboards', $status, 'decode_dashboard_row' );
	}

	/**
	 * @param int $id Dashboard ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_dashboard( $id ) {
		return self::get_entity( 'studio_dashboards', $id, 'decode_dashboard_row' );
	}

	/**
	 * @param string $key Dashboard key.
	 * @return array<string, mixed>|null
	 */
	public static function get_dashboard_by_key( $key ) {
		return self::get_entity_by_key( 'studio_dashboards', 'dashboard_key', $key, 'decode_dashboard_row' );
	}

	/**
	 * @param array<string, mixed> $data Dashboard data.
	 * @return array{ok:bool,id?:int,dashboard?:array<string,mixed>,message?:string}
	 */
	public static function create_dashboard( $data ) {
		$key = sanitize_key( (string) ( $data['dashboard_key'] ?? $data['key'] ?? '' ) );
		if ( ! $key ) {
			$key = 'dash_' . wp_generate_password( 8, false, false );
		}
		if ( self::get_dashboard_by_key( $key ) ) {
			return [ 'ok' => false, 'message' => __( 'Dashboard key exists.', 'nextgencompanion' ) ];
		}
		global $wpdb;
		$table = NGC_Database::table( 'studio_dashboards' );
		$wpdb->insert(
			$table,
			[
				'dashboard_key' => $key,
				'name'          => sanitize_text_field( (string) ( $data['name'] ?? $key ) ),
				'role'          => sanitize_key( (string) ( $data['role'] ?? 'admin' ) ),
				'status'        => sanitize_key( (string) ( $data['status'] ?? 'draft' ) ),
				'layout_json'   => wp_json_encode( (array) ( $data['layout'] ?? [] ) ),
				'widgets_json'  => wp_json_encode( (array) ( $data['widgets'] ?? [] ) ),
				'workflow_id'   => (int) ( $data['workflow_id'] ?? 0 ),
				'settings_json' => wp_json_encode( (array) ( $data['settings'] ?? [] ) ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
		$id = (int) $wpdb->insert_id;
		return [ 'ok' => true, 'id' => $id, 'dashboard' => self::get_dashboard( $id ) ];
	}

	/**
	 * @param int                  $id   Dashboard ID.
	 * @param array<string, mixed> $data Patch.
	 * @return array{ok:bool,dashboard?:array<string,mixed>}
	 */
	public static function update_dashboard( $id, $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_dashboards' );
		if ( ! self::get_dashboard( $id ) ) {
			return [ 'ok' => false ];
		}
		$update = [];
		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( (string) $data['name'] );
		}
		if ( isset( $data['role'] ) ) {
			$update['role'] = sanitize_key( (string) $data['role'] );
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( (string) $data['status'] );
		}
		if ( isset( $data['layout'] ) ) {
			$update['layout_json'] = wp_json_encode( (array) $data['layout'] );
		}
		if ( isset( $data['widgets'] ) ) {
			$update['widgets_json'] = wp_json_encode( (array) $data['widgets'] );
		}
		if ( isset( $data['workflow_id'] ) ) {
			$update['workflow_id'] = (int) $data['workflow_id'];
		}
		if ( isset( $data['settings'] ) ) {
			$update['settings_json'] = wp_json_encode( (array) $data['settings'] );
		}
		if ( $update ) {
			$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		}
		return [ 'ok' => true, 'dashboard' => self::get_dashboard( $id ) ];
	}

	/**
	 * @param int $id Dashboard ID.
	 * @return array{ok:bool}
	 */
	public static function delete_dashboard( $id ) {
		global $wpdb;
		$wpdb->delete( NGC_Database::table( 'studio_dashboards' ), [ 'id' => (int) $id ], [ '%d' ] );
		return [ 'ok' => true ];
	}

	/**
	 * @param string $table_key Table key.
	 * @param string $status    Optional status filter.
	 * @param string $decoder   Decoder method.
	 * @return array<int, array<string, mixed>>
	 */
	private static function list_entity( $table_key, $status, $decoder ) {
		global $wpdb;
		$table = NGC_Database::table( $table_key );
		if ( ! $table || ! self::table_exists( $table ) ) {
			return [];
		}
		if ( $status ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY updated_at DESC", sanitize_key( $status ) ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY updated_at DESC", ARRAY_A );
		}
		return array_map( [ __CLASS__, $decoder ], (array) $rows );
	}

	/**
	 * @param string $table_key Table key.
	 * @param int    $id        Entity ID.
	 * @param string $decoder   Decoder.
	 * @return array<string, mixed>|null
	 */
	private static function get_entity( $table_key, $id, $decoder ) {
		global $wpdb;
		$table = NGC_Database::table( $table_key );
		if ( ! $table ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
		return $row ? call_user_func( [ __CLASS__, $decoder ], $row ) : null;
	}

	/**
	 * @param string $table_key Table key.
	 * @param string $col       Key column.
	 * @param string $key       Key value.
	 * @param string $decoder   Decoder.
	 * @return array<string, mixed>|null
	 */
	private static function get_entity_by_key( $table_key, $col, $key, $decoder ) {
		global $wpdb;
		$table = NGC_Database::table( $table_key );
		if ( ! $table ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$col} = %s", sanitize_key( $key ) ), ARRAY_A );
		return $row ? call_user_func( [ __CLASS__, $decoder ], $row ) : null;
	}

	/**
	 * @param string $table Table name.
	 * @return bool
	 */
	private static function table_exists( $table ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private static function decode_form_row( $row ) {
		$row['schema']   = json_decode( (string) ( $row['schema_json'] ?? '' ), true ) ?: [ 'fields' => [] ];
		$row['settings'] = json_decode( (string) ( $row['settings_json'] ?? '' ), true ) ?: [];
		unset( $row['schema_json'], $row['settings_json'] );
		return $row;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private static function decode_email_row( $row ) {
		$row['merge_fields'] = json_decode( (string) ( $row['merge_fields_json'] ?? '' ), true ) ?: [];
		$row['settings']     = json_decode( (string) ( $row['settings_json'] ?? '' ), true ) ?: [];
		unset( $row['merge_fields_json'], $row['settings_json'] );
		return $row;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private static function decode_notification_row( $row ) {
		$row['config'] = json_decode( (string) ( $row['config_json'] ?? '' ), true ) ?: [];
		unset( $row['config_json'] );
		return $row;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private static function decode_dashboard_row( $row ) {
		$row['layout']   = json_decode( (string) ( $row['layout_json'] ?? '' ), true ) ?: [];
		$row['widgets']  = json_decode( (string) ( $row['widgets_json'] ?? '' ), true ) ?: [];
		$row['settings'] = json_decode( (string) ( $row['settings_json'] ?? '' ), true ) ?: [];
		unset( $row['layout_json'], $row['widgets_json'], $row['settings_json'] );
		return $row;
	}
}
