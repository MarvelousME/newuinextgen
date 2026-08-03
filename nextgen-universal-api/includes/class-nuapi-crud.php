<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Generic, schema-aware CRUD engine. Only ever touches tables that:
 *   1. live under the current $wpdb->prefix,
 *   2. were discovered by NUAPI_Scanner and are not on the hard blocklist,
 *   3. have been explicitly enabled by an administrator in wp-admin.
 * Every column and table name used in a query is validated against a
 * live DESCRIBE of the table — never taken raw from the request.
 */
class NUAPI_CRUD {

	const NS = 'nuapi/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {

		register_rest_route( self::NS, '/registry', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_registry' ),
			'permission_callback' => array( 'NUAPI_Security', 'can_read' ),
		) );

		register_rest_route( self::NS, '/data/(?P<table>[a-zA-Z0-9_]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'list_rows' ),
				'permission_callback' => array( 'NUAPI_Security', 'can_read' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_row' ),
				'permission_callback' => array( 'NUAPI_Security', 'can_write' ),
			),
		) );

		register_rest_route( self::NS, '/data/(?P<table>[a-zA-Z0-9_]+)/(?P<id>[0-9]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_row' ),
				'permission_callback' => array( 'NUAPI_Security', 'can_read' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'update_row' ),
				'permission_callback' => array( 'NUAPI_Security', 'can_write' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'delete_row' ),
				'permission_callback' => array( 'NUAPI_Security', 'can_write' ),
			),
		) );
	}

	public static function get_registry( WP_REST_Request $request ) {
		return rest_ensure_response( NUAPI_Scanner::get_registry() );
	}

	/** Confirms the requested table is real, WP-prefixed, not blocklisted, and enabled. */
	private static function validate_table( $table ) {
		global $wpdb;

		$table = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $table );
		if ( strpos( $table, $wpdb->prefix ) !== 0 ) {
			return new WP_Error( 'nuapi_invalid_table', __( 'Unknown table.', 'nuapi' ), array( 'status' => 404 ) );
		}

		$settings       = get_option( 'nuapi_settings', array() );
		$enabled_tables = isset( $settings['enabled_tables'] ) ? (array) $settings['enabled_tables'] : array();
		if ( ! in_array( $table, $enabled_tables, true ) ) {
			return new WP_Error( 'nuapi_table_disabled', __( 'This table has not been enabled for API access. Enable it from NextGen Universal API → Tables & Permissions.', 'nuapi' ), array( 'status' => 403 ) );
		}

		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return new WP_Error( 'nuapi_invalid_table', __( 'Table no longer exists.', 'nuapi' ), array( 'status' => 404 ) );
		}

		$cols        = $wpdb->get_results( "DESCRIBE `{$table}`" );
		$schema      = array();
		$primary_key = 'id';
		foreach ( (array) $cols as $col ) {
			$schema[ $col->Field ] = $col->Type;
			if ( 'PRI' === $col->Key ) {
				$primary_key = $col->Field;
			}
		}
		if ( empty( $schema ) ) {
			return new WP_Error( 'nuapi_invalid_table', __( 'Could not read table schema.', 'nuapi' ), array( 'status' => 500 ) );
		}

		return array( 'table' => $table, 'columns' => $schema, 'primary_key' => $primary_key );
	}

	private static function sanitize_value( $value, $sql_type ) {
		$sql_type = strtolower( $sql_type );
		if ( preg_match( '/int|bigint|smallint|tinyint/', $sql_type ) ) {
			return (int) $value;
		}
		if ( preg_match( '/decimal|float|double/', $sql_type ) ) {
			return (float) $value;
		}
		if ( preg_match( '/^(date|datetime|timestamp)/', $sql_type ) ) {
			$value = sanitize_text_field( $value );
			return preg_match( '/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $value ) ? $value : null;
		}
		if ( preg_match( '/text|blob/', $sql_type ) ) {
			return sanitize_textarea_field( (string) $value );
		}
		return sanitize_text_field( (string) $value );
	}

	/** Whitelist-only WHERE clause — only query params matching real columns are used. */
	private static function build_filters( WP_REST_Request $request, array $schema ) {
		$where  = array();
		$values = array();

		foreach ( $request->get_query_params() as $key => $val ) {
			if ( ! isset( $schema[ $key ] ) ) {
				continue;
			}
			$where[]  = "`{$key}` = %s";
			$values[] = (string) $val;
		}

		if ( empty( $where ) ) {
			return array( '', array() );
		}
		return array( ' WHERE ' . implode( ' AND ', $where ), $values );
	}

	public static function list_rows( WP_REST_Request $request ) {
		global $wpdb;
		$meta = self::validate_table( $request['table'] );
		if ( is_wp_error( $meta ) ) { return $meta; }

		$table    = $meta['table'];
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 25 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		list( $where_sql, $where_values ) = self::build_filters( $request, $meta['columns'] );

		$orderby = $request->get_param( 'orderby' );
		$orderby = ( $orderby && isset( $meta['columns'][ $orderby ] ) ) ? $orderby : $meta['primary_key'];
		$order   = ( 'asc' === strtolower( (string) $request->get_param( 'order' ) ) ) ? 'ASC' : 'DESC';

		$count_sql = "SELECT COUNT(*) FROM `{$table}`" . $where_sql;
		$total     = (int) ( $where_values ? $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) ) : $wpdb->get_var( $count_sql ) );

		$sql    = "SELECT * FROM `{$table}`" . $where_sql . " ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";
		$params = array_merge( $where_values, array( $per_page, $offset ) );
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return rest_ensure_response( array(
			'table'       => $table,
			'page'        => $page,
			'per_page'    => $per_page,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'rows'        => $rows,
		) );
	}

	public static function get_row( WP_REST_Request $request ) {
		global $wpdb;
		$meta = self::validate_table( $request['table'] );
		if ( is_wp_error( $meta ) ) { return $meta; }

		$table = $meta['table'];
		$pk    = $meta['primary_key'];
		$id    = (int) $request['id'];

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$pk}` = %d", $id ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'nuapi_not_found', __( 'Row not found.', 'nuapi' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $row );
	}

	public static function create_row( WP_REST_Request $request ) {
		global $wpdb;
		$meta = self::validate_table( $request['table'] );
		if ( is_wp_error( $meta ) ) { return $meta; }

		$table = $meta['table'];
		$pk    = $meta['primary_key'];
		$body  = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'nuapi_bad_request', __( 'Body must be a JSON object.', 'nuapi' ), array( 'status' => 400 ) );
		}

		$data = array();
		foreach ( $body as $col => $val ) {
			if ( $col === $pk || ! isset( $meta['columns'][ $col ] ) ) { continue; }
			$data[ $col ] = self::sanitize_value( $val, $meta['columns'][ $col ] );
		}
		if ( empty( $data ) ) {
			return new WP_Error( 'nuapi_bad_request', __( 'No valid columns supplied.', 'nuapi' ), array( 'status' => 400 ) );
		}

		$ok = $wpdb->insert( $table, $data );
		if ( false === $ok ) {
			return new WP_Error( 'nuapi_db_error', __( 'Insert failed.', 'nuapi' ) . ' ' . $wpdb->last_error, array( 'status' => 500 ) );
		}

		NUAPI_Logger::log( 'create', $table, (int) $wpdb->insert_id, $request );
		$new_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$pk}` = %d", $wpdb->insert_id ), ARRAY_A );
		return rest_ensure_response( $new_row );
	}

	public static function update_row( WP_REST_Request $request ) {
		global $wpdb;
		$meta = self::validate_table( $request['table'] );
		if ( is_wp_error( $meta ) ) { return $meta; }

		$table = $meta['table'];
		$pk    = $meta['primary_key'];
		$id    = (int) $request['id'];
		$body  = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'nuapi_bad_request', __( 'Body must be a JSON object.', 'nuapi' ), array( 'status' => 400 ) );
		}

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$pk}` = %d", $id ), ARRAY_A );
		if ( ! $existing ) {
			return new WP_Error( 'nuapi_not_found', __( 'Row not found.', 'nuapi' ), array( 'status' => 404 ) );
		}

		$data = array();
		foreach ( $body as $col => $val ) {
			if ( $col === $pk || ! isset( $meta['columns'][ $col ] ) ) { continue; }
			$data[ $col ] = self::sanitize_value( $val, $meta['columns'][ $col ] );
		}
		if ( empty( $data ) ) {
			return new WP_Error( 'nuapi_bad_request', __( 'No valid columns supplied.', 'nuapi' ), array( 'status' => 400 ) );
		}

		$ok = $wpdb->update( $table, $data, array( $pk => $id ) );
		if ( false === $ok && $wpdb->last_error ) {
			return new WP_Error( 'nuapi_db_error', __( 'Update failed.', 'nuapi' ) . ' ' . $wpdb->last_error, array( 'status' => 500 ) );
		}

		NUAPI_Logger::log( 'update', $table, $id, $request );
		$updated = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$pk}` = %d", $id ), ARRAY_A );
		return rest_ensure_response( $updated );
	}

	public static function delete_row( WP_REST_Request $request ) {
		global $wpdb;
		$meta = self::validate_table( $request['table'] );
		if ( is_wp_error( $meta ) ) { return $meta; }

		$table = $meta['table'];
		$pk    = $meta['primary_key'];
		$id    = (int) $request['id'];

		if ( 'true' !== (string) $request->get_param( 'confirm' ) ) {
			return new WP_Error( 'nuapi_confirm_required', __( 'Pass ?confirm=true to permanently delete this row.', 'nuapi' ), array( 'status' => 400 ) );
		}

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$pk}` = %d", $id ), ARRAY_A );
		if ( ! $existing ) {
			return new WP_Error( 'nuapi_not_found', __( 'Row not found.', 'nuapi' ), array( 'status' => 404 ) );
		}

		$ok = $wpdb->delete( $table, array( $pk => $id ) );
		if ( false === $ok ) {
			return new WP_Error( 'nuapi_db_error', __( 'Delete failed.', 'nuapi' ) . ' ' . $wpdb->last_error, array( 'status' => 500 ) );
		}

		NUAPI_Logger::log( 'delete', $table, $id, $request );
		return rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
	}
}
