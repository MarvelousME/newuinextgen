<?php
/**
 * Floating Notification Centre — captures WP admin notices into a modal.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central admin notifications store + FAB / modal.
 */
final class NGC_Admin_Notifications {

	public const USER_META = 'ngt_admin_notifications';

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_footer', [ __CLASS__, 'render_fab' ], 50 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ], 30 );
		add_action( 'admin_head', [ __CLASS__, 'hide_native_notices_css' ], 1 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar' ], 100 );
	}

	/**
	 * @return bool
	 */
	public static function enabled() {
		return is_admin() && current_user_can( 'manage_options' );
	}

	/**
	 * Hide disruptive native notices site-wide in wp-admin (moved into centre by JS).
	 */
	public static function hide_native_notices_css() {
		if ( ! self::enabled() ) {
			return;
		}
		echo '<style id="ngt-notif-hide">'
			. '#wpbody-content > .notice,'
			. '#wpbody-content > .update-nag,'
			. '#wpbody-content > .updated,'
			. '#wpbody-content > .error,'
			. '#wpbody-content > .notice-alt,'
			. '#wpbody-content > .e-notice,'
			. '#wpbody-content > .woocommerce-message,'
			. '#wpbody-content > .woocommerce-info,'
			. '#wpbody-content > .woocommerce-error,'
			. '#wpbody-content > .monsterinsights-notice,'
			. '#wpbody-content > .monsterinsights-box,'
			. '#wpbody-content > div[class*="monsterinsights-"],'
			. '#wpbody-content > .ms_lms_notice,'
			. '#wpbody-content > .stm-lms-notice,'
			. '#wpbody-content > .fs-notice,'
			. '#wpbody-content > .jetpack-jitm-message,'
			. '.wrap > .notice,'
			. '.wrap > .update-nag,'
			. '.wrap > .updated,'
			. '.wrap > .error,'
			. '.ngt-admin-page > .notice,'
			. 'body.wp-admin #wpbody-content .notice:not(.ngt-notif-exempt):not(.inline),'
			. 'body.wp-admin #wpbody-content .update-nag'
			. '{position:absolute!important;left:-99999px!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;margin:0!important;padding:0!important;border:0!important;opacity:0!important;pointer-events:none!important}'
			. '.ngt-notif-exempt,.ngt-notif-root .notice{position:static!important;left:auto!important;width:auto!important;height:auto!important;overflow:visible!important;clip:auto!important;opacity:1!important;pointer-events:auto!important}'
			. '</style>';
	}

	/**
	 * Admin bar entry opens the modal.
	 *
	 * @param WP_Admin_Bar $bar Bar.
	 */
	public static function admin_bar( $bar ) {
		if ( ! self::enabled() || ! is_admin_bar_showing() ) {
			return;
		}
		$unread = self::unread_count();
		$title  = '<span class="ab-icon dashicons dashicons-bell" style="margin-top:2px"></span>'
			. '<span class="ab-label">' . esc_html__( 'Notifications', 'nextgencompanion' ) . '</span>';
		if ( $unread > 0 ) {
			$title .= ' <span class="awaiting-mod ngt-notif-ab-count">' . (int) $unread . '</span>';
		}
		$bar->add_node(
			[
				'id'    => 'ngt-notifications',
				'title' => $title,
				'href'  => '#ngt-notif-open',
				'meta'  => [
					'title' => __( 'Open Notification Centre', 'nextgencompanion' ),
					'class' => 'ngt-notif-admin-bar',
				],
			]
		);
	}

	/**
	 * @return int
	 */
	public static function unread_count() {
		$n = 0;
		foreach ( self::list_items() as $item ) {
			if ( empty( $item['read'] ) && ( empty( $item['snooze_until'] ) || (int) $item['snooze_until'] < time() ) ) {
				++$n;
			}
		}
		return $n;
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
	 * Ingest a notice captured client-side (deduped by fingerprint).
	 *
	 * @param array<string, mixed> $item Item.
	 * @return array<int, array<string, mixed>>
	 */
	public static function ingest( array $item ) {
		$message = wp_kses_post( (string) ( $item['message'] ?? $item['html'] ?? '' ) );
		$text    = sanitize_textarea_field( wp_strip_all_tags( $message ) );
		if ( '' === trim( $text ) ) {
			return self::list_items();
		}
		$fingerprint = sanitize_key( (string) ( $item['fingerprint'] ?? '' ) );
		if ( '' === $fingerprint ) {
			$fingerprint = md5( strtolower( preg_replace( '/\s+/', ' ', $text ) ) );
		}

		$user = get_current_user_id();
		$list = $user ? get_user_meta( $user, self::USER_META, true ) : [];
		$list = is_array( $list ) ? $list : [];
		foreach ( $list as $existing ) {
			if ( ( $existing['fingerprint'] ?? '' ) === $fingerprint ) {
				return self::list_items();
			}
		}

		self::push(
			[
				'id'          => 'notice_' . wp_generate_password( 8, false, false ),
				'title'       => sanitize_text_field( (string) ( $item['title'] ?? __( 'Admin notice', 'nextgencompanion' ) ) ),
				'message'     => $text,
				'html'        => $message,
				'severity'    => sanitize_key( (string) ( $item['severity'] ?? 'info' ) ),
				'plugin'      => sanitize_key( (string) ( $item['plugin'] ?? 'wordpress' ) ),
				'fingerprint' => $fingerprint,
				'created'     => time(),
				'read'        => false,
			]
		);
		return self::list_items();
	}

	/**
	 * Batch ingest.
	 *
	 * @param array<int, array<string, mixed>> $items Items.
	 * @return array<int, array<string, mixed>>
	 */
	public static function ingest_many( array $items ) {
		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				self::ingest( $item );
			}
		}
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
						'html'     => '',
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
	 * @param array<int, string>   $ids   IDs.
	 * @param string               $op    ack|dismiss|snooze|ingest|ingest_many.
	 * @param array<string, mixed> $extra Extra.
	 * @return array<int, array<string, mixed>>
	 */
	public static function mutate( array $ids, $op, array $extra = [] ) {
		if ( 'ingest' === $op ) {
			return self::ingest( $extra );
		}
		if ( 'ingest_many' === $op ) {
			$batch = isset( $extra['items'] ) && is_array( $extra['items'] ) ? $extra['items'] : [];
			return self::ingest_many( $batch );
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
	 * Assets — all wp-admin screens for admins.
	 */
	public static function assets() {
		if ( ! self::enabled() ) {
			return;
		}
		$ver = NGC_VERSION;
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'ngt-admin-tokens', NGC_PLUGIN_URL . 'assets/css/admin-tokens.css', [], $ver );
		wp_enqueue_style( 'ngt-admin-shell', NGC_PLUGIN_URL . 'assets/css/admin-shell.css', [ 'ngt-admin-tokens' ], $ver );
		wp_enqueue_script(
			'ngt-admin-notifications',
			NGC_PLUGIN_URL . 'assets/js/admin-notifications.js',
			[],
			$ver,
			true
		);
		wp_localize_script(
			'ngt-admin-notifications',
			'ngtAdminNotif',
			[
				'restRoot' => esc_url_raw( rest_url( 'ngc/v1/admin' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'items'    => self::list_items(),
				'i18n'     => [
					'empty'   => __( 'No notifications', 'nextgencompanion' ),
					'title'   => __( 'Notification Centre', 'nextgencompanion' ),
					'admin'   => __( 'Admin notice', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * FAB + modal markup.
	 */
	public static function render_fab() {
		if ( ! self::enabled() ) {
			return;
		}
		$unread = self::unread_count();
		?>
		<div id="ngt-notif-root" class="ngt-notif-root" data-testid="ngt-notif-root">
			<button type="button" id="ngt-notif-fab" class="ngt-notif-fab<?php echo $unread > 0 ? ' has-new' : ''; ?>" data-testid="ngt-notif-fab" aria-expanded="false" aria-controls="ngt-notif-modal" aria-haspopup="dialog">
				<span class="dashicons dashicons-bell" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Notifications', 'nextgencompanion' ); ?></span>
				<span class="ngt-notif-fab__count" data-count="<?php echo (int) $unread; ?>"><?php echo (int) $unread; ?></span>
			</button>
			<div id="ngt-notif-modal" class="ngt-notif-modal" hidden data-testid="ngt-notif-drawer" role="presentation">
				<div class="ngt-notif-modal__backdrop" data-ngt-notif-close="1" tabindex="-1"></div>
				<aside id="ngt-notif-drawer" class="ngt-notif-drawer" role="dialog" aria-modal="true" aria-labelledby="ngt-notif-title">
					<header>
						<h2 id="ngt-notif-title"><?php esc_html_e( 'Notification Centre', 'nextgencompanion' ); ?></h2>
						<button type="button" class="button-link" id="ngt-notif-close" data-ngt-notif-close="1"><?php esc_html_e( 'Close', 'nextgencompanion' ); ?></button>
					</header>
					<div class="ngt-notif-toolbar">
						<input type="search" id="ngt-notif-search" placeholder="<?php esc_attr_e( 'Search…', 'nextgencompanion' ); ?>" />
						<select id="ngt-notif-filter">
							<option value=""><?php esc_html_e( 'All severities', 'nextgencompanion' ); ?></option>
							<?php foreach ( [ 'success', 'info', 'warning', 'error', 'critical' ] as $sev ) : ?>
								<option value="<?php echo esc_attr( $sev ); ?>"><?php echo esc_html( $sev ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button" id="ngt-notif-ack-all"><?php esc_html_e( 'Acknowledge all', 'nextgencompanion' ); ?></button>
					</div>
					<div id="ngt-notif-list" class="ngt-notif-list"></div>
				</aside>
			</div>
			<div id="ngt-notif-vault" class="ngt-notif-vault" aria-hidden="true"></div>
		</div>
		<?php
	}
}
