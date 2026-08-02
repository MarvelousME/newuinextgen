<?php
/**
 * Floating Notification Centre — routes WP notices via JS + REST store.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central admin notifications store + FAB drawer.
 */
final class NGC_Admin_Notifications {

	public const USER_META = 'ngt_admin_notifications';

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_footer', [ __CLASS__, 'render_fab' ], 50 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'admin_head', [ __CLASS__, 'hide_native_notices_css' ] );
	}

	/**
	 * Hide disruptive native notices on NGT screens (moved into centre by JS).
	 */
	public static function hide_native_notices_css() {
		if ( ! class_exists( 'NGC_Admin_Shell' ) || ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		echo '<style id="ngt-notif-hide">.ngt-admin-screen #wpbody-content > .notice, .ngt-admin-screen .wrap > .notice{position:absolute!important;left:-9999px!important;height:1px!important;overflow:hidden!important}</style>';
	}

	/**
	 * @param array<string, mixed> $item Notification.
	 */
	public static function push( array $item ) {
		$user = get_current_user_id();
		if ( ! $user ) {
			return;
		}
		$list   = get_user_meta( $user, self::USER_META, true );
		$list   = is_array( $list ) ? $list : [];
		$list[] = $item;
		$list   = array_slice( $list, -100 );
		update_user_meta( $user, self::USER_META, $list );
	}

	/**
	 * Ingest a notice captured client-side.
	 *
	 * @param array<string, mixed> $item Item.
	 * @return array<int, array<string, mixed>>
	 */
	public static function ingest( array $item ) {
		self::push(
			[
				'id'       => 'notice_' . wp_generate_password( 8, false, false ),
				'title'    => sanitize_text_field( (string) ( $item['title'] ?? __( 'System message', 'nextgencompanion' ) ) ),
				'message'  => sanitize_textarea_field( (string) ( $item['message'] ?? '' ) ),
				'severity' => sanitize_key( (string) ( $item['severity'] ?? 'info' ) ),
				'plugin'   => sanitize_key( (string) ( $item['plugin'] ?? 'wordpress' ) ),
				'created'  => time(),
				'read'     => false,
			]
		);
		return self::list_items();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_items() {
		$user = get_current_user_id();
		$list = $user ? get_user_meta( $user, self::USER_META, true ) : [];
		$list = is_array( $list ) ? $list : [];

		if ( class_exists( 'NGC_Intelligence_Notifications' ) && method_exists( 'NGC_Intelligence_Notifications', 'list_for_user' ) ) {
			$intel = NGC_Intelligence_Notifications::list_for_user( $user );
			if ( is_array( $intel ) ) {
				foreach ( $intel as $row ) {
					$list[] = [
						'id'       => 'intel_' . ( $row['id'] ?? wp_generate_password( 6, false, false ) ),
						'title'    => (string) ( $row['title'] ?? __( 'Intelligence', 'nextgencompanion' ) ),
						'message'  => (string) ( $row['message'] ?? '' ),
						'severity' => (string) ( $row['severity'] ?? 'info' ),
						'plugin'   => 'intelligence',
						'created'  => (int) ( $row['created'] ?? time() ),
						'read'     => ! empty( $row['read'] ),
					];
				}
			}
		}

		usort(
			$list,
			static function ( $a, $b ) {
				return ( (int) ( $b['created'] ?? 0 ) ) <=> ( (int) ( $a['created'] ?? 0 ) );
			}
		);
		return $list;
	}

	/**
	 * @param array<int, string> $ids IDs.
	 * @param string             $op  ack|dismiss|snooze|ingest.
	 * @param array<string, mixed> $extra Extra.
	 * @return array<int, array<string, mixed>>
	 */
	public static function mutate( array $ids, $op, array $extra = [] ) {
		if ( 'ingest' === $op ) {
			return self::ingest( $extra );
		}
		$user = get_current_user_id();
		$list = get_user_meta( $user, self::USER_META, true );
		$list = is_array( $list ) ? $list : [];
		$ids  = array_map( 'strval', $ids );
		$now  = time();
		$out  = [];
		foreach ( $list as $item ) {
			$id = (string) ( $item['id'] ?? '' );
			if ( ! in_array( $id, $ids, true ) ) {
				$out[] = $item;
				continue;
			}
			if ( 'dismiss' === $op ) {
				continue;
			}
			if ( 'ack' === $op ) {
				$item['read'] = true;
			}
			if ( 'snooze' === $op ) {
				$item['snooze_until'] = $now + HOUR_IN_SECONDS;
			}
			$out[] = $item;
		}
		update_user_meta( $user, self::USER_META, $out );
		return self::list_items();
	}

	/**
	 * Assets.
	 */
	public static function assets() {
		if ( ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		wp_enqueue_script(
			'ngt-admin-notifications',
			NGC_PLUGIN_URL . 'assets/js/admin-notifications.js',
			[ 'ngt-admin-shell' ],
			NGC_VERSION,
			true
		);
		wp_localize_script(
			'ngt-admin-notifications',
			'ngtAdminNotif',
			[
				'restRoot' => esc_url_raw( rest_url( 'ngc/v1/admin' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'items'    => self::list_items(),
			]
		);
	}

	/**
	 * FAB + drawer markup.
	 */
	public static function render_fab() {
		if ( ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		$unread = 0;
		foreach ( self::list_items() as $item ) {
			if ( empty( $item['read'] ) && ( empty( $item['snooze_until'] ) || (int) $item['snooze_until'] < time() ) ) {
				++$unread;
			}
		}
		echo '<div id="ngt-notif-root" class="ngt-notif-root" data-testid="ngt-notif-root">';
		echo '<button type="button" id="ngt-notif-fab" class="ngt-notif-fab" data-testid="ngt-notif-fab" aria-expanded="false" aria-controls="ngt-notif-drawer">';
		echo '<span class="dashicons dashicons-bell" aria-hidden="true"></span>';
		echo '<span class="screen-reader-text">' . esc_html__( 'Notifications', 'nextgencompanion' ) . '</span>';
		echo '<span class="ngt-notif-fab__count" data-count="' . (int) $unread . '">' . (int) $unread . '</span>';
		echo '</button>';
		echo '<aside id="ngt-notif-drawer" class="ngt-notif-drawer" hidden data-testid="ngt-notif-drawer" role="dialog" aria-label="' . esc_attr__( 'Notification Centre', 'nextgencompanion' ) . '">';
		echo '<header><h2>' . esc_html__( 'Notification Centre', 'nextgencompanion' ) . '</h2>';
		echo '<button type="button" class="button-link" id="ngt-notif-close">' . esc_html__( 'Close', 'nextgencompanion' ) . '</button></header>';
		echo '<div class="ngt-notif-toolbar"><input type="search" id="ngt-notif-search" placeholder="' . esc_attr__( 'Search…', 'nextgencompanion' ) . '" />';
		echo '<select id="ngt-notif-filter"><option value="">' . esc_html__( 'All severities', 'nextgencompanion' ) . '</option>';
		foreach ( [ 'success', 'info', 'warning', 'error', 'critical' ] as $sev ) {
			echo '<option value="' . esc_attr( $sev ) . '">' . esc_html( $sev ) . '</option>';
		}
		echo '</select>';
		echo '<button type="button" class="button" id="ngt-notif-ack-all">' . esc_html__( 'Acknowledge all', 'nextgencompanion' ) . '</button></div>';
		echo '<div id="ngt-notif-list" class="ngt-notif-list"></div>';
		echo '</aside></div>';
	}
}
