<?php
/**
 * Database schema and table helpers.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Database {

	/** @var array<string, string> */
	private static $tables = [];

	public static function table( string $key ): string {
		if ( empty( self::$tables ) ) {
			global $wpdb;
			self::$tables = [
				'events'           => $wpdb->prefix . 'ngt_events',
				'rtm_rooms'        => $wpdb->prefix . 'ngt_rtm_rooms',
				'rtm_messages'     => $wpdb->prefix . 'ngt_rtm_messages',
				'notifications'    => $wpdb->prefix . 'ngt_notifications',
				'matches'          => $wpdb->prefix . 'ngt_matches',
				'payouts'          => $wpdb->prefix . 'ngt_payouts',
				'gamification'     => $wpdb->prefix . 'ngt_gamification',
				'badges'           => $wpdb->prefix . 'ngt_badges',
				'tutor_documents'  => $wpdb->prefix . 'ngt_tutor_documents',
				'rate_limits'      => $wpdb->prefix . 'ngt_rate_limits',
			];
		}
		return self::$tables[ $key ] ?? '';
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$queries = [
			"CREATE TABLE " . self::table( 'events' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_key varchar(191) NOT NULL DEFAULT '',
				source varchar(64) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				payload longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY event_key (event_key),
				KEY created_at (created_at)
			) $charset;",

			"CREATE TABLE " . self::table( 'rtm_rooms' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				slug varchar(64) NOT NULL DEFAULT '',
				title varchar(191) NOT NULL DEFAULT '',
				visibility varchar(32) NOT NULL DEFAULT 'staff',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY slug (slug)
			) $charset;",

			"CREATE TABLE " . self::table( 'rtm_messages' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				room_id bigint(20) unsigned NOT NULL DEFAULT 0,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				message longtext NOT NULL,
				message_type varchar(32) NOT NULL DEFAULT 'user',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY room_id (room_id),
				KEY created_at (created_at)
			) $charset;",

			"CREATE TABLE " . self::table( 'notifications' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				type varchar(64) NOT NULL DEFAULT 'info',
				title varchar(191) NOT NULL DEFAULT '',
				body longtext NULL,
				link varchar(255) NOT NULL DEFAULT '',
				is_read tinyint(1) NOT NULL DEFAULT 0,
				meta longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY is_read (is_read)
			) $charset;",

			"CREATE TABLE " . self::table( 'matches' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				subject varchar(191) NOT NULL DEFAULT '',
				grade varchar(64) NOT NULL DEFAULT '',
				area varchar(128) NOT NULL DEFAULT '',
				status varchar(32) NOT NULL DEFAULT 'pending',
				score decimal(5,2) NOT NULL DEFAULT 0.00,
				notes longtext NULL,
				meta longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY status (status),
				KEY tutor_user_id (tutor_user_id)
			) $charset;",

			"CREATE TABLE " . self::table( 'payouts' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				period_start date NULL,
				period_end date NULL,
				gross_amount decimal(12,2) NOT NULL DEFAULT 0.00,
				platform_fee decimal(12,2) NOT NULL DEFAULT 0.00,
				net_amount decimal(12,2) NOT NULL DEFAULT 0.00,
				lesson_count int(11) NOT NULL DEFAULT 0,
				status varchar(32) NOT NULL DEFAULT 'pending',
				meta longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				paid_at datetime NULL,
				PRIMARY KEY (id),
				KEY tutor_user_id (tutor_user_id),
				KEY status (status)
			) $charset;",

			"CREATE TABLE " . self::table( 'gamification' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				points int(11) NOT NULL DEFAULT 0,
				reason varchar(191) NOT NULL DEFAULT '',
				event_key varchar(191) NOT NULL DEFAULT '',
				meta longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id)
			) $charset;",

			"CREATE TABLE " . self::table( 'badges' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				badge_key varchar(64) NOT NULL DEFAULT '',
				label varchar(191) NOT NULL DEFAULT '',
				earned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY user_badge (user_id, badge_key)
			) $charset;",

			"CREATE TABLE " . self::table( 'tutor_documents' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
				doc_type varchar(64) NOT NULL DEFAULT 'other',
				status varchar(32) NOT NULL DEFAULT 'pending',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id)
			) $charset;",

			"CREATE TABLE " . self::table( 'rate_limits' ) . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				rate_key varchar(191) NOT NULL DEFAULT '',
				hits int(11) NOT NULL DEFAULT 1,
				window_start datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY rate_key (rate_key)
			) $charset;",
		];

		foreach ( $queries as $sql ) {
			dbDelta( $sql );
		}

		self::seed_rtm_rooms();
	}

	public static function maybe_upgrade(): void {
		$stored = get_option( 'ngt_hub_db_version', '' );
		if ( version_compare( (string) $stored, NGT_Hub::DB_VERSION, '<' ) ) {
			self::install();
			update_option( 'ngt_hub_db_version', NGT_Hub::DB_VERSION, false );
		}
	}

	private static function seed_rtm_rooms(): void {
		global $wpdb;
		$table = self::table( 'rtm_rooms' );
		$rooms = [
			[ 'staff', __( 'Staff Operations', 'nextgen-automation-hub' ), 'staff' ],
			[ 'admin', __( 'Admin Control', 'nextgen-automation-hub' ), 'admin' ],
			[ 'tutor-support', __( 'Tutor Support', 'nextgen-automation-hub' ), 'staff' ],
			[ 'booking-issues', __( 'Booking Issues', 'nextgen-automation-hub' ), 'staff' ],
			[ 'payment-issues', __( 'Payment Issues', 'nextgen-automation-hub' ), 'staff' ],
			[ 'lesson-issues', __( 'Lesson Issues', 'nextgen-automation-hub' ), 'staff' ],
			[ 'escalated-support', __( 'Escalated Support', 'nextgen-automation-hub' ), 'staff' ],
		];

		foreach ( $rooms as $room ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $room[0] )
			);
			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					[
						'slug'       => $room[0],
						'title'      => $room[1],
						'visibility' => $room[2],
					],
					[ '%s', '%s', '%s' ]
				);
			}
		}
	}
}
