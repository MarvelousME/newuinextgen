<?php
/**
 * Audit framework service — search, filtering, timeline, correlation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extended audit query and auth event capture.
 */
class NGC_Audit_Service {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_login', [ __CLASS__, 'on_login' ], 10, 2 );
		add_action( 'wp_logout', [ __CLASS__, 'on_logout' ] );
		add_action( 'wp_login_failed', [ __CLASS__, 'on_login_failed' ] );
		add_action( 'password_reset', [ __CLASS__, 'on_password_reset' ], 10, 2 );
		add_action( 'profile_update', [ __CLASS__, 'on_user_updated' ], 10, 2 );
		add_action( 'delete_user', [ __CLASS__, 'on_user_deleted' ] );
		add_action( 'set_user_role', [ __CLASS__, 'on_role_changed' ], 10, 3 );
	}

	/**
	 * Search audit log with filters.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $args = [] ) {
		global $wpdb;
		$table  = NGC_Database::table( 'audit_log' );
		$where  = [ '1=1' ];
		$values = [];

		foreach ( [ 'action', 'object_type', 'actor_type', 'result', 'correlation_id', 'workflow_key' ] as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$where[]  = "{$key} = %s";
				$values[] = sanitize_text_field( (string) $args[ $key ] );
			}
		}
		if ( ! empty( $args['actor_user_id'] ) ) {
			$where[]  = 'actor_user_id = %d';
			$values[] = (int) $args['actor_user_id'];
		}
		if ( ! empty( $args['object_id'] ) ) {
			$where[]  = 'object_id = %d';
			$values[] = (int) $args['object_id'];
		}
		if ( ! empty( $args['from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( (string) $args['from'] );
		}
		if ( ! empty( $args['to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( (string) $args['to'] );
		}
		if ( ! empty( $args['q'] ) ) {
			$where[]  = '(action LIKE %s OR context LIKE %s OR object_type LIKE %s)';
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['q'] ) ) . '%';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$limit    = max( 1, min( 5000, (int) ( $args['limit'] ?? 100 ) ) );
		$offset   = max( 0, (int) ( $args['offset'] ?? 0 ) );
		$values[] = $limit;
		$values[] = $offset;

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
		foreach ( $rows as &$row ) {
			$row['context']    = json_decode( (string) ( $row['context'] ?? '{}' ), true ) ?: [];
			$row['old_values'] = json_decode( (string) ( $row['old_values'] ?? '{}' ), true ) ?: [];
			$row['new_values'] = json_decode( (string) ( $row['new_values'] ?? '{}' ), true ) ?: [];
		}
		return $rows;
	}

	/**
	 * User activity timeline.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function user_timeline( $user_id, $limit = 100 ) {
		return self::search( [ 'actor_user_id' => (int) $user_id, 'limit' => $limit ] );
	}

	/**
	 * Object history.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object ID.
	 * @param int    $limit       Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function object_history( $object_type, $object_id, $limit = 100 ) {
		return self::search( [
			'object_type' => $object_type,
			'object_id'   => (int) $object_id,
			'limit'       => $limit,
		] );
	}

	/**
	 * Correlated events.
	 *
	 * @param string $correlation_id Correlation ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function correlation( $correlation_id ) {
		return self::search( [ 'correlation_id' => $correlation_id, 'limit' => 500 ] );
	}

	/**
	 * @param string $user_login Username.
	 * @param WP_User $user User.
	 */
	public static function on_login( $user_login, $user ) {
		NGC_Audit::log( 'login', 'user', $user->ID, [ 'login' => $user_login ], $user->ID, [
			'actor_type' => 'user',
			'result'     => 'success',
		] );
	}

	/** Logout handler. */
	public static function on_logout() {
		$uid = get_current_user_id();
		if ( $uid ) {
			NGC_Audit::log( 'logout', 'user', $uid, [], $uid, [ 'actor_type' => 'user' ] );
		}
	}

	/**
	 * @param string $username Username.
	 */
	public static function on_login_failed( $username ) {
		NGC_Audit::log( 'login_failed', 'user', 0, [ 'username' => sanitize_user( $username ) ], 0, [
			'actor_type' => 'anonymous',
			'result'     => 'failed',
		] );
	}

	/**
	 * @param WP_User $user     User.
	 * @param string  $new_pass Password.
	 */
	public static function on_password_reset( $user, $new_pass ) {
		NGC_Audit::log( 'password_reset', 'user', $user->ID, [], $user->ID );
	}

	/**
	 * @param int     $user_id User ID.
	 * @param WP_User $old     Old user data.
	 */
	public static function on_user_updated( $user_id, $old ) {
		NGC_Audit::log( 'user_updated', 'user', $user_id, [], $user_id, [
			'old_values' => [ 'email' => $old->user_email, 'display_name' => $old->display_name ],
			'new_values' => [ 'email' => get_userdata( $user_id )->user_email ?? '' ],
		] );
	}

	/**
	 * @param int $user_id User ID.
	 */
	public static function on_user_deleted( $user_id ) {
		NGC_Audit::log( 'user_deleted', 'user', $user_id, [], get_current_user_id() );
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $role    New role.
	 * @param array  $old     Old roles.
	 */
	public static function on_role_changed( $user_id, $role, $old ) {
		NGC_Audit::log( 'role_changed', 'user', $user_id, [], get_current_user_id(), [
			'old_values' => [ 'roles' => $old ],
			'new_values' => [ 'role' => $role ],
		] );
	}
}
