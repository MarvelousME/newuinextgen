<?php
/**
 * Metadata-driven entity registry for admin CRUD/grids.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers pilot entities and extension hook.
 */
final class NGC_Admin_Entity_Registry {

	/** @var array<string, array<string, mixed>> */
	private static $entities = [];

	/**
	 * Init + register defaults.
	 */
	public static function init() {
		self::register_defaults();
		/**
		 * Register additional admin entities.
		 *
		 * @param string $class Self.
		 */
		if ( function_exists( 'do_action' ) ) {
			do_action( 'ngt_admin_register_entities', self::class );
		}
	}

	/**
	 * @param array<string, mixed> $entity Entity definition.
	 */
	public static function register( array $entity ) {
		$key = sanitize_key( (string) ( $entity['key'] ?? '' ) );
		if ( '' === $key ) {
			return;
		}
		self::$entities[ $key ] = array_merge(
			[
				'key'          => $key,
				'label'        => $key,
				'capability'   => 'manage_options',
				'columns'      => [],
				'filters'      => [],
				'fields'       => [],
				'soft_delete'  => false,
				'export_key'   => '',
				'list_callback'=> null,
				'get_callback' => null,
				'create_callback' => null,
				'update_callback' => null,
				'delete_callback' => null,
			],
			$entity,
			[ 'key' => $key ]
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		return self::$entities;
	}

	/**
	 * @param string $key Key.
	 * @return array<string, mixed>|null
	 */
	public static function get( $key ) {
		$key = sanitize_key( (string) $key );
		return self::$entities[ $key ] ?? null;
	}

	/**
	 * Pilot entities.
	 */
	private static function register_defaults() {
		self::register(
			[
				'key'        => 'applications',
				'label'      => __( 'Tutor Applications', 'nextgencompanion' ),
				'capability' => 'ngc_review_tutors',
				'columns'    => [
					[ 'key' => 'id', 'label' => 'ID', 'sortable' => true ],
					[ 'key' => 'full_name', 'label' => __( 'Name', 'nextgencompanion' ), 'sortable' => true ],
					[ 'key' => 'email', 'label' => __( 'Email', 'nextgencompanion' ) ],
					[ 'key' => 'subjects', 'label' => __( 'Subjects', 'nextgencompanion' ) ],
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'sortable' => true ],
				],
				'filters'    => [
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'type' => 'select', 'options' => [ 'pending', 'approved', 'rejected' ] ],
				],
				'fields'     => [
					[ 'key' => 'full_name', 'label' => __( 'Name', 'nextgencompanion' ), 'type' => 'text', 'required' => true ],
					[ 'key' => 'email', 'label' => __( 'Email', 'nextgencompanion' ), 'type' => 'email', 'required' => true ],
					[ 'key' => 'subjects', 'label' => __( 'Subjects', 'nextgencompanion' ), 'type' => 'text' ],
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'type' => 'select', 'options' => [ 'pending', 'approved', 'rejected' ] ],
				],
				'export_key' => 'applications',
				'list_callback' => [ __CLASS__, 'list_applications' ],
				'get_callback'  => [ __CLASS__, 'get_application' ],
				'update_callback' => [ __CLASS__, 'update_application' ],
				'delete_callback' => null,
				'actions'    => [ 'approve', 'reject' ],
			]
		);

		self::register(
			[
				'key'        => 'matches',
				'label'      => __( 'Matches', 'nextgencompanion' ),
				'capability' => 'ngc_manage_matches',
				'columns'    => [
					[ 'key' => 'id', 'label' => 'ID', 'sortable' => true ],
					[ 'key' => 'subject', 'label' => __( 'Subject', 'nextgencompanion' ), 'sortable' => true ],
					[ 'key' => 'grade', 'label' => __( 'Grade', 'nextgencompanion' ) ],
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'sortable' => true ],
					[ 'key' => 'score', 'label' => __( 'Score', 'nextgencompanion' ), 'sortable' => true ],
				],
				'filters'    => [
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'type' => 'text' ],
				],
				'fields'     => [
					[ 'key' => 'subject', 'label' => __( 'Subject', 'nextgencompanion' ), 'type' => 'text', 'required' => true ],
					[ 'key' => 'grade', 'label' => __( 'Grade', 'nextgencompanion' ), 'type' => 'text' ],
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'type' => 'text' ],
					[ 'key' => 'score', 'label' => __( 'Score', 'nextgencompanion' ), 'type' => 'number' ],
				],
				'export_key' => 'matches',
				'list_callback' => [ __CLASS__, 'list_matches' ],
				'get_callback'  => [ __CLASS__, 'get_match' ],
				'update_callback' => [ __CLASS__, 'update_match' ],
				'soft_delete' => true,
				'delete_callback' => [ __CLASS__, 'delete_match' ],
			]
		);

		self::register(
			[
				'key'        => 'safeguarding_cases',
				'label'      => __( 'Safeguarding Cases', 'nextgencompanion' ),
				'capability' => 'manage_options',
				'columns'    => [
					[ 'key' => 'id', 'label' => 'ID', 'sortable' => true ],
					[ 'key' => 'priority', 'label' => __( 'Priority', 'nextgencompanion' ), 'sortable' => true ],
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'sortable' => true ],
					[ 'key' => 'summary', 'label' => __( 'Summary', 'nextgencompanion' ) ],
					[ 'key' => 'assigned_to', 'label' => __( 'Assigned', 'nextgencompanion' ) ],
					[ 'key' => 'due_at', 'label' => __( 'Due', 'nextgencompanion' ) ],
				],
				'filters'    => [
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'type' => 'text' ],
					[ 'key' => 'priority', 'label' => __( 'Priority', 'nextgencompanion' ), 'type' => 'text' ],
				],
				'fields'     => [
					[ 'key' => 'priority', 'label' => __( 'Priority', 'nextgencompanion' ), 'type' => 'text' ],
					[ 'key' => 'status', 'label' => __( 'Status', 'nextgencompanion' ), 'type' => 'text' ],
					[ 'key' => 'summary', 'label' => __( 'Summary', 'nextgencompanion' ), 'type' => 'textarea', 'required' => true ],
				],
				'export_key' => 'safeguarding',
				'list_callback' => [ __CLASS__, 'list_safeguarding' ],
				'get_callback'  => [ __CLASS__, 'get_safeguarding' ],
				'update_callback' => [ __CLASS__, 'update_safeguarding' ],
				'actions'    => [ 'assign', 'escalate', 'resolve' ],
			]
		);
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array{rows:array,total:int}
	 */
	public static function list_applications( $args = [] ) {
		$status = sanitize_key( (string) ( $args['status'] ?? 'pending' ) );
		$limit  = max( 1, min( 200, (int) ( $args['per_page'] ?? 25 ) ) );
		$page   = max( 1, (int) ( $args['page'] ?? 1 ) );
		$apps   = class_exists( 'NGC_Tutor_Lifecycle' ) ? NGC_Tutor_Lifecycle::list_applications( $status ?: 'pending', 500 ) : [];
		$rows   = [];
		foreach ( (array) $apps as $app ) {
			$rows[] = [
				'id'        => (int) $app->id,
				'full_name' => (string) $app->full_name,
				'email'     => (string) $app->email,
				'subjects'  => (string) $app->subjects,
				'status'    => (string) ( $app->status ?? $status ),
			];
		}
		if ( ! empty( $args['search'] ) ) {
			$q = strtolower( (string) $args['search'] );
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $r ) use ( $q ) {
						return false !== strpos( strtolower( implode( ' ', $r ) ), $q );
					}
				)
			);
		}
		$total = count( $rows );
		$offset = ( $page - 1 ) * $limit;
		return [ 'rows' => array_slice( $rows, $offset, $limit ), 'total' => $total ];
	}

	/**
	 * @param int $id ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_application( $id ) {
		$list = self::list_applications( [ 'status' => 'pending', 'per_page' => 500 ] );
		foreach ( $list['rows'] as $row ) {
			if ( (int) $row['id'] === (int) $id ) {
				return $row;
			}
		}
		$list = self::list_applications( [ 'status' => 'approved', 'per_page' => 500 ] );
		foreach ( $list['rows'] as $row ) {
			if ( (int) $row['id'] === (int) $id ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * @param int                  $id   ID.
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>
	 */
	public static function update_application( $id, array $data ) {
		$status = sanitize_key( (string) ( $data['status'] ?? '' ) );
		if ( 'approved' === $status && class_exists( 'NGC_Tutor_Lifecycle' ) ) {
			NGC_Tutor_Lifecycle::approve( (int) $id );
		} elseif ( 'rejected' === $status && class_exists( 'NGC_Tutor_Lifecycle' ) ) {
			NGC_Tutor_Lifecycle::reject( (int) $id );
		}
		return self::get_application( $id ) ?: [ 'id' => (int) $id, 'status' => $status ];
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array{rows:array,total:int}
	 */
	public static function list_matches( $args = [] ) {
		global $wpdb;
		$table = class_exists( 'NGC_Database' ) ? NGC_Database::table( 'matches' ) : '';
		if ( ! $table ) {
			return [ 'rows' => [], 'total' => 0 ];
		}
		$limit  = max( 1, min( 200, (int) ( $args['per_page'] ?? 25 ) ) );
		$page   = max( 1, (int) ( $args['page'] ?? 1 ) );
		$offset = ( $page - 1 ) * $limit;
		$status = sanitize_text_field( (string) ( $args['status'] ?? '' ) );
		$where  = '1=1';
		$params = [];
		if ( $status ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}
		if ( ! empty( $args['search'] ) ) {
			$where   .= ' AND (subject LIKE %s OR grade LIKE %s OR status LIKE %s)';
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
		$sql   = "SELECT id, subject, grade, status, score FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$raw = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$rows = [];
		foreach ( (array) $raw as $r ) {
			$rows[] = [
				'id'      => (int) $r['id'],
				'subject' => (string) $r['subject'],
				'grade'   => (string) $r['grade'],
				'status'  => (string) $r['status'],
				'score'   => (string) $r['score'],
			];
		}
		return [ 'rows' => $rows, 'total' => $total ];
	}

	/**
	 * @param int $id ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_match( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'matches' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, subject, grade, status, score FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * @param int                  $id   ID.
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>|null
	 */
	public static function update_match( $id, array $data ) {
		global $wpdb;
		$table  = NGC_Database::table( 'matches' );
		$update = [];
		foreach ( [ 'subject', 'grade', 'status', 'score' ] as $col ) {
			if ( isset( $data[ $col ] ) ) {
				$update[ $col ] = sanitize_text_field( (string) $data[ $col ] );
			}
		}
		if ( $update ) {
			$wpdb->update( $table, $update, [ 'id' => (int) $id ] );
		}
		return self::get_match( $id );
	}

	/**
	 * Soft-delete match by marking cancelled.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public static function delete_match( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'matches' );
		return false !== $wpdb->update( $table, [ 'status' => 'cancelled' ], [ 'id' => (int) $id ] );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array{rows:array,total:int}
	 */
	public static function list_safeguarding( $args = [] ) {
		if ( ! class_exists( 'NGC_Safeguarding' ) ) {
			return [ 'rows' => [], 'total' => 0 ];
		}
		$limit = max( 1, min( 200, (int) ( $args['per_page'] ?? 25 ) ) );
		$cases = NGC_Safeguarding::query(
			[
				'limit'    => 500,
				'status'   => sanitize_text_field( (string) ( $args['status'] ?? '' ) ),
				'priority' => sanitize_text_field( (string) ( $args['priority'] ?? '' ) ),
			]
		);
		$rows = [];
		foreach ( (array) $cases as $case ) {
			$rows[] = [
				'id'          => (int) $case->id,
				'priority'    => (string) $case->priority,
				'status'      => (string) $case->status,
				'summary'     => (string) $case->summary,
				'assigned_to' => (string) ( $case->assigned_to ?: '' ),
				'due_at'      => (string) ( $case->due_at ?? '' ),
			];
		}
		if ( ! empty( $args['search'] ) ) {
			$q = strtolower( (string) $args['search'] );
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $r ) use ( $q ) {
						return false !== strpos( strtolower( (string) $r['summary'] ), $q );
					}
				)
			);
		}
		$total  = count( $rows );
		$page   = max( 1, (int) ( $args['page'] ?? 1 ) );
		$offset = ( $page - 1 ) * $limit;
		return [ 'rows' => array_slice( $rows, $offset, $limit ), 'total' => $total ];
	}

	/**
	 * @param int $id ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_safeguarding( $id ) {
		$list = self::list_safeguarding( [ 'per_page' => 500 ] );
		foreach ( $list['rows'] as $row ) {
			if ( (int) $row['id'] === (int) $id ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * @param int                  $id   ID.
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>|null
	 */
	public static function update_safeguarding( $id, array $data ) {
		if ( ! class_exists( 'NGC_Safeguarding' ) ) {
			return null;
		}
		$op = sanitize_key( (string) ( $data['op'] ?? '' ) );
		if ( 'assign' === $op && method_exists( 'NGC_Safeguarding', 'assign' ) ) {
			NGC_Safeguarding::assign( (int) $id, get_current_user_id() );
		} elseif ( 'escalate' === $op && method_exists( 'NGC_Safeguarding', 'escalate' ) ) {
			NGC_Safeguarding::escalate( (int) $id );
		} elseif ( 'resolve' === $op && method_exists( 'NGC_Safeguarding', 'resolve' ) ) {
			NGC_Safeguarding::resolve( (int) $id, (string) ( $data['resolution'] ?? 'closed' ) );
		}
		return self::get_safeguarding( $id );
	}
}

if ( ! function_exists( 'ngt_admin_register_entity' ) ) {
	/**
	 * @param array<string, mixed> $entity Entity.
	 */
	function ngt_admin_register_entity( array $entity ) {
		NGC_Admin_Entity_Registry::register( $entity );
	}
}
