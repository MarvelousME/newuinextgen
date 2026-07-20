<?php
/**
 * User notification system (database + email).
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Notifications {

	public static function register_hooks(): void {
		add_action( 'ngt_automation_event_fired', [ __CLASS__, 'on_event' ], 20, 2 );
	}

	/**
	 * @param array<string, mixed> $meta Optional meta.
	 */
	public static function create( int $user_id, string $type, string $title, string $body, string $link = '', array $meta = [] ): int {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'notifications' );

		$wpdb->insert(
			$table,
			[
				'user_id' => $user_id,
				'type'    => sanitize_key( $type ),
				'title'   => sanitize_text_field( $title ),
				'body'    => sanitize_textarea_field( $body ),
				'link'    => esc_url_raw( $link ),
				'meta'    => $meta ? wp_json_encode( $meta ) : null,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		$id = (int) $wpdb->insert_id;
		$user = get_user_by( 'id', $user_id );
		if ( $user && $user->user_email ) {
			wp_mail(
				$user->user_email,
				$title,
				$body . ( $link ? "\n\n" . $link : '' ),
				[ 'Content-Type: text/plain; charset=UTF-8' ]
			);
		}

		do_action( 'ngt_notification_created', $id, $user_id, $type );
		return $id;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_user( int $user_id, int $limit = 20, bool $unread_only = false ): array {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'notifications' );
		$sql   = "SELECT * FROM {$table} WHERE user_id = %d";
		if ( $unread_only ) {
			$sql .= ' AND is_read = 0';
		}
		$sql .= ' ORDER BY created_at DESC LIMIT %d';

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $user_id, $limit ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	public static function mark_read( int $notification_id, int $user_id ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			NGT_Hub_Database::table( 'notifications' ),
			[ 'is_read' => 1 ],
			[ 'id' => $notification_id, 'user_id' => $user_id ],
			[ '%d' ],
			[ '%d', '%d' ]
		);
	}

	public static function unread_count( int $user_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM " . NGT_Hub_Database::table( 'notifications' ) . ' WHERE user_id = %d AND is_read = 0',
				$user_id
			)
		);
	}

	/**
	 * @param array<string, mixed> $payload Event payload.
	 */
	public static function on_event( string $event_key, array $payload ): void {
		$map = [
			'ngt.lesson.completed'        => [ 'student_user_id', 'lesson_completed', __( 'Lesson completed', 'nextgen-automation-hub' ) ],
			'ngt.tutor.approved'          => [ 'user_id', 'tutor_approved', __( 'Tutor application approved', 'nextgen-automation-hub' ) ],
			'ngt.find_tutor.submitted'    => [ 'parent_user_id', 'intake_received', __( 'Find a Tutor request received', 'nextgen-automation-hub' ) ],
			'ngt.payment.overdue'         => [ 'parent_user_id', 'payment_overdue', __( 'Payment overdue', 'nextgen-automation-hub' ) ],
		];

		if ( ! isset( $map[ $event_key ] ) ) {
			return;
		}

		[ $user_field, $type, $title ] = $map[ $event_key ];
		$user_id = (int) ( $payload[ $user_field ] ?? 0 );
		if ( ! $user_id ) {
			return;
		}

		$body = $payload['message'] ?? $payload['progress_note'] ?? __( 'You have a new update in your dashboard.', 'nextgen-automation-hub' );
		self::create( $user_id, $type, $title, (string) $body );
	}
}
