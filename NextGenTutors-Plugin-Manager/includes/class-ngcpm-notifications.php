<?php
/**
 * Admin notifications with persistent dismissal.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and dismisses contextual admin notices.
 */
class NGCPM_Notifications {

	const USER_META_KEY = 'ngcpm_dismissed_notices';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_visible() {
		$all       = self::build_notices();
		$dismissed = self::get_dismissed_map();
		$visible   = [];

		foreach ( $all as $notice ) {
			$id   = (string) ( $notice['id'] ?? '' );
			$hash = (string) ( $notice['hash'] ?? '' );
			if ( ! $id || ! $hash ) {
				continue;
			}
			if ( isset( $dismissed[ $id ] ) && $dismissed[ $id ] === $hash ) {
				continue;
			}
			$visible[] = $notice;
		}

		return $visible;
	}

	/**
	 * @return array<string, string> id => content hash.
	 */
	private static function get_dismissed_map() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return [];
		}
		$raw = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$map = [];
		foreach ( $raw as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['id'] ) && ! empty( $entry['hash'] ) ) {
				$map[ (string) $entry['id'] ] = (string) $entry['hash'];
			}
		}
		return $map;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_notices() {
		$scan   = NGCPM_Scanner::scan( true );
		$health = NGCPM_Health::calculate( $scan );
		$notices = [];

		if ( 'NOT_READY' === ( $health['overall_status'] ?? '' ) ) {
			$notices[] = self::notice(
				'readiness_not_ready',
				'warning',
				__( 'System not ready', 'nextgentutors-plugin-manager' ),
				sprintf(
					/* translators: 1: ready count 2: required total */
					__( '%1$d of %2$d required plugins are active. Install or activate missing plugins.', 'nextgentutors-plugin-manager' ),
					(int) ( $health['required_ready'] ?? 0 ),
					(int) ( $health['required_total'] ?? 0 )
				),
				'install-activate-all'
			);
		}

		if ( (int) ( $health['manual_required'] ?? 0 ) > 0 ) {
			$notices[] = self::notice(
				'manual_plugins',
				'info',
				__( 'Manual plugins required', 'nextgentutors-plugin-manager' ),
				__( 'Premium or manual plugins need zip upload to ngcpm-packages.', 'nextgentutors-plugin-manager' ),
				'nav:missing'
			);
		}

		if ( (int) ( $health['failed'] ?? 0 ) > 0 ) {
			$notices[] = self::notice(
				'recent_failures',
				'error',
				__( 'Recent install failures', 'nextgentutors-plugin-manager' ),
				sprintf(
					/* translators: %d: failure count */
					__( '%d recent failure(s) in audit log. Review logs and retry.', 'nextgentutors-plugin-manager' ),
					(int) $health['failed']
				),
				'nav:logs'
			);
		}

		$cookie_checks = NGCPM_Cookies::run_checks();
		foreach ( $cookie_checks as $check ) {
			if ( in_array( (string) ( $check['status'] ?? '' ), [ 'FAIL' ], true ) ) {
				$notices[] = self::notice(
					'cookie_' . strtolower( (string) ( $check['id'] ?? 'check' ) ),
					'warning',
					__( 'Cookie check needs attention', 'nextgentutors-plugin-manager' ),
					(string) ( $check['evidence'] ?? '' ),
					'cookie-probe'
				);
			}
		}

		return apply_filters( 'ngcpm_admin_notifications', $notices, $scan, $health );
	}

	/**
	 * @param string $id      Stable notice id.
	 * @param string $type    info|warning|error|success.
	 * @param string $title   Title.
	 * @param string $message Body.
	 * @param string $action  Optional data-action or nav:key.
	 * @return array<string, mixed>
	 */
	private static function notice( $id, $type, $title, $message, $action = '' ) {
		$hash = md5( $type . '|' . $title . '|' . $message );
		return [
			'id'      => $id,
			'hash'    => $hash,
			'type'    => $type,
			'title'   => $title,
			'message' => $message,
			'action'  => $action,
		];
	}

	/**
	 * Persist dismissal for current user.
	 *
	 * @param string $id   Notice id.
	 * @param string $hash Content hash.
	 * @param string $scope user|global (global requires manage_options).
	 * @return array{success:bool,message:string}
	 */
	public static function dismiss( $id, $hash, $scope = 'user' ) {
		$id   = sanitize_key( $id );
		$hash = sanitize_text_field( $hash );

		if ( ! $id || ! $hash ) {
			return [
				'success' => false,
				'message' => __( 'Invalid notification.', 'nextgentutors-plugin-manager' ),
			];
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return [
				'success' => false,
				'message' => __( 'You must be logged in to dismiss notifications.', 'nextgentutors-plugin-manager' ),
			];
		}

		if ( 'global' === $scope && ! current_user_can( 'manage_options' ) ) {
			return [
				'success' => false,
				'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ),
			];
		}

		$stored = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$found = false;
		foreach ( $stored as $idx => $entry ) {
			if ( is_array( $entry ) && ( $entry['id'] ?? '' ) === $id ) {
				$stored[ $idx ] = [
					'id'           => $id,
					'hash'         => $hash,
					'dismissed_at' => gmdate( 'c' ),
					'scope'        => $scope,
				];
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$stored[] = [
				'id'           => $id,
				'hash'         => $hash,
				'dismissed_at' => gmdate( 'c' ),
				'scope'        => $scope,
			];
		}

		update_user_meta( $user_id, self::USER_META_KEY, $stored );
		NGCPM_Logger::log( 'notification_dismissed', 'Notification dismissed', [ 'id' => $id ] );

		return [
			'success' => true,
			'message' => __( 'Notification dismissed.', 'nextgentutors-plugin-manager' ),
		];
	}
}
