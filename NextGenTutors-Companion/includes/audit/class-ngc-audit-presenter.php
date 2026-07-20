<?php
/**
 * Human-readable audit presentation — unified system-wide activity feed.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formats audit_log + system_log rows for admin tables (no raw JSON).
 */
class NGC_Audit_Presenter {

	/** @var array<string, string> */
	private static $action_labels = [
		'login'              => 'User signed in',
		'logout'             => 'User signed out',
		'login_failed'       => 'Sign-in attempt failed',
		'password_reset'     => 'Password reset',
		'user_updated'       => 'User profile updated',
		'user_deleted'       => 'User deleted',
		'role_changed'       => 'User role changed',
		'tutors_cpt_seeded'  => 'Demo tutors seeded',
		'workflow_run'       => 'Workflow executed',
		'booking_created'    => 'Booking created',
		'payment_received'   => 'Payment received',
		'export_run'         => 'Data export run',
		'repair_run'         => 'Self-healing repair run',
	];

	/**
	 * Recent unified activity (audit + system log).
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, string>>
	 */
	public static function unified_recent( $limit = 25 ) {
		$limit  = max( 1, min( 100, (int) $limit ) );
		$items  = [];
		$audit  = NGC_Audit_Service::search( [ 'limit' => $limit ] );
		$system = class_exists( 'NGC_System_Log_Service' )
			? NGC_System_Log_Service::search( [ 'limit' => $limit ] )
			: [];

		foreach ( $audit as $row ) {
			$items[] = [
				'sort'   => (string) ( $row['created_at'] ?? '' ),
				'source' => __( 'Audit', 'nextgencompanion' ),
				'id'     => (string) ( $row['id'] ?? '' ),
				'when'   => self::format_time( $row['created_at'] ?? '' ),
				'actor'  => self::human_actor( $row ),
				'action' => self::human_action( $row['action'] ?? '' ),
				'object' => self::human_object( $row ),
				'detail' => self::human_detail( $row ),
				'result' => self::human_result( $row['result'] ?? 'success' ),
			];
		}

		foreach ( $system as $row ) {
			$level   = strtoupper( (string) ( $row['level'] ?? 'INFO' ) );
			$message = (string) ( $row['message'] ?? '' );
			$items[] = [
				'sort'   => (string) ( $row['created_at'] ?? '' ),
				'source' => __( 'System', 'nextgencompanion' ),
				'id'     => 'S' . (string) ( $row['id'] ?? '' ),
				'when'   => self::format_time( $row['created_at'] ?? '' ),
				'actor'  => self::human_system_actor( $row ),
				'action' => $level . ' — ' . $message,
				'object' => self::human_system_object( $row ),
				'detail' => self::human_system_detail( $row ),
				'result' => $level,
			];
		}

		usort(
			$items,
			static function ( $a, $b ) {
				return strcmp( $b['sort'], $a['sort'] );
			}
		);

		return array_slice( $items, 0, $limit );
	}

	/**
	 * @param string $action Action slug.
	 * @return string
	 */
	public static function human_action( $action ) {
		$key = sanitize_key( $action );
		if ( isset( self::$action_labels[ $key ] ) ) {
			return self::$action_labels[ $key ];
		}
		return ucwords( str_replace( '_', ' ', $key ) );
	}

	/**
	 * @param array<string, mixed> $row Audit row.
	 * @return string
	 */
	public static function human_actor( $row ) {
		$uid = (int) ( $row['actor_user_id'] ?? 0 );
		if ( $uid > 0 ) {
			$user = get_userdata( $uid );
			return $user ? $user->display_name : sprintf( __( 'User #%d', 'nextgencompanion' ), $uid );
		}
		$type = (string) ( $row['actor_type'] ?? 'system' );
		$map  = [
			'system'    => __( 'System', 'nextgencompanion' ),
			'anonymous' => __( 'Anonymous visitor', 'nextgencompanion' ),
			'user'      => __( 'User', 'nextgencompanion' ),
			'cron'      => __( 'Scheduled task', 'nextgencompanion' ),
		];
		return $map[ $type ] ?? ucfirst( $type );
	}

	/**
	 * @param array<string, mixed> $row Audit row.
	 * @return string
	 */
	public static function human_object( $row ) {
		$type = sanitize_key( (string) ( $row['object_type'] ?? '' ) );
		$id   = (int) ( $row['object_id'] ?? 0 );

		if ( ! $type && ! $id ) {
			return '—';
		}

		switch ( $type ) {
			case 'user':
				if ( $id > 0 ) {
					$user = get_userdata( $id );
					return $user
						? sprintf( '%s (%s)', $user->display_name, __( 'user account', 'nextgencompanion' ) )
						: sprintf( __( 'User #%d', 'nextgencompanion' ), $id );
				}
				break;
			case 'tutors':
			case 'tutor':
				if ( $id > 0 ) {
					$post = get_post( $id );
					return $post
						? sprintf( '%s (%s)', $post->post_title, __( 'tutor profile', 'nextgencompanion' ) )
						: sprintf( __( 'Tutor #%d', 'nextgencompanion' ), $id );
				}
				break;
			case 'booking':
				return $id > 0
					? sprintf( __( 'Booking #%d', 'nextgencompanion' ), $id )
					: __( 'Booking', 'nextgencompanion' );
			case 'order':
				return $id > 0
					? sprintf( __( 'Order #%d', 'nextgencompanion' ), $id )
					: __( 'Order', 'nextgencompanion' );
		}

		$label = ucwords( str_replace( '_', ' ', $type ) );
		return $id > 0 ? "{$label} #{$id}" : $label;
	}

	/**
	 * @param array<string, mixed> $row Audit row.
	 * @return string
	 */
	public static function human_detail( $row ) {
		$parts   = [];
		$context = is_array( $row['context'] ?? null ) ? $row['context'] : [];

		foreach ( [ 'event', 'status', 'reason', 'message', 'email', 'login', 'workflow', 'template' ] as $key ) {
			if ( ! empty( $context[ $key ] ) && is_scalar( $context[ $key ] ) ) {
				$parts[] = ucfirst( $key ) . ': ' . sanitize_text_field( (string) $context[ $key ] );
			}
		}

		if ( empty( $parts ) && ! empty( $row['workflow_key'] ) ) {
			$parts[] = __( 'Workflow', 'nextgencompanion' ) . ': ' . sanitize_text_field( (string) $row['workflow_key'] );
		}

		if ( empty( $parts ) && ! empty( $row['correlation_id'] ) ) {
			$parts[] = __( 'Correlation', 'nextgencompanion' ) . ': ' . sanitize_text_field( (string) $row['correlation_id'] );
		}

		return $parts ? implode( ' · ', $parts ) : '—';
	}

	/**
	 * @param string $result Result slug.
	 * @return string
	 */
	public static function human_result( $result ) {
		$map = [
			'success' => __( 'Success', 'nextgencompanion' ),
			'failed'  => __( 'Failed', 'nextgencompanion' ),
			'denied'  => __( 'Denied', 'nextgencompanion' ),
			'partial' => __( 'Partial', 'nextgencompanion' ),
		];
		$key = sanitize_key( $result );
		return $map[ $key ] ?? ucfirst( $key );
	}

	/**
	 * @param string $created_at MySQL datetime (UTC).
	 * @return string
	 */
	public static function format_time( $created_at ) {
		if ( ! $created_at ) {
			return '—';
		}
		$ts = strtotime( $created_at . ' UTC' );
		return $ts ? wp_date( 'M j, Y g:i a', $ts ) : $created_at;
	}

	/**
	 * Human-readable repair result (no JSON).
	 *
	 * @param array<string, mixed> $result Repair output.
	 * @return string
	 */
	public static function format_repair_notice( $result ) {
		if ( empty( $result ) || ! is_array( $result ) ) {
			return __( 'Repair completed.', 'nextgencompanion' );
		}
		$parts = [];
		foreach ( $result as $key => $val ) {
			if ( is_bool( $val ) ) {
				$parts[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . ( $val ? __( 'OK', 'nextgencompanion' ) : __( 'Skipped', 'nextgencompanion' ) );
			} elseif ( is_scalar( $val ) ) {
				$parts[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . sanitize_text_field( (string) $val );
			} elseif ( is_array( $val ) && isset( $val['status'] ) ) {
				$parts[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . sanitize_text_field( (string) $val['status'] );
			}
		}
		return $parts ? implode( ' · ', $parts ) : __( 'Repair completed.', 'nextgencompanion' );
	}

	/**
	 * @param array<string, mixed> $row System log row.
	 * @return string
	 */
	private static function human_system_actor( $row ) {
		$uid = (int) ( $row['user_id'] ?? 0 );
		if ( $uid > 0 ) {
			$user = get_userdata( $uid );
			return $user ? $user->display_name : sprintf( __( 'User #%d', 'nextgencompanion' ), $uid );
		}
		return __( 'System', 'nextgencompanion' );
	}

	/**
	 * @param array<string, mixed> $row System log row.
	 * @return string
	 */
	private static function human_system_object( $row ) {
		$channel = (string) ( $row['channel'] ?? '' );
		$source  = (string) ( $row['source'] ?? '' );
		if ( $channel && $source ) {
			return ucfirst( $channel ) . ' / ' . ucfirst( $source );
		}
		return $channel ? ucfirst( $channel ) : ( $source ? ucfirst( $source ) : '—' );
	}

	/**
	 * @param array<string, mixed> $row System log row.
	 * @return string
	 */
	private static function human_system_detail( $row ) {
		$context = is_array( $row['context'] ?? null ) ? $row['context'] : [];
		$parts   = [];
		foreach ( [ 'slug', 'plugin', 'post_id', 'user_id', 'status', 'reason' ] as $key ) {
			if ( isset( $context[ $key ] ) && is_scalar( $context[ $key ] ) ) {
				$parts[] = ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . sanitize_text_field( (string) $context[ $key ] );
			}
		}
		if ( ! empty( $row['correlation_id'] ) ) {
			$parts[] = __( 'Correlation', 'nextgencompanion' ) . ': ' . sanitize_text_field( (string) $row['correlation_id'] );
		}
		return $parts ? implode( ' · ', $parts ) : '—';
	}
}
