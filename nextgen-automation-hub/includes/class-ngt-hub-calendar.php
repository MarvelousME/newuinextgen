<?php
/**
 * Calendar / scheduling UI from ngt_lesson CPT.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Calendar {

	public static function register_hooks(): void {
		add_shortcode( 'ngt_calendar', [ __CLASS__, 'shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	public static function enqueue_assets(): void {
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'ngt_calendar' ) ) {
			return;
		}
		wp_enqueue_style( 'ngt-hub', NGT_HUB_URL . 'assets/css/ngt-hub.css', [], NGT_Hub::VERSION );
	}

	/**
	 * @param array<string, string> $atts Attributes.
	 */
	public static function shortcode( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="ngt-wrap"><p>' . esc_html__( 'Please log in to view your schedule.', 'nextgen-automation-hub' ) . '</p></div>';
		}

		$atts = shortcode_atts(
			[
				'role'  => '',
				'month' => gmdate( 'Y-m' ),
			],
			$atts,
			'ngt_calendar'
		);

		$user_id = get_current_user_id();
		$role    = $atts['role'] ?: self::detect_role( $user_id );
		$events  = self::events_for_user( $user_id, $role, $atts['month'] );

		ob_start();
		?>
		<div class="ngt-wrap ngt-calendar">
			<h2><?php esc_html_e( 'Schedule', 'nextgen-automation-hub' ); ?> — <?php echo esc_html( $atts['month'] ); ?></h2>
			<div class="ngt-grid">
				<?php if ( empty( $events ) ) : ?>
					<div class="ngt-card"><p><?php esc_html_e( 'No lessons scheduled this month.', 'nextgen-automation-hub' ); ?></p></div>
				<?php else : ?>
					<?php foreach ( $events as $event ) : ?>
						<div class="ngt-card ngt-calendar-event">
							<strong><?php echo esc_html( $event['title'] ); ?></strong>
							<p><?php echo esc_html( $event['date'] ); ?> · <?php echo esc_html( $event['duration'] ); ?> min</p>
							<p><?php echo esc_html( $event['status'] ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function detect_role( int $user_id ): string {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return 'student';
		}
		if ( in_array( 'ngt_tutor', (array) $user->roles, true ) || in_array( 'tutor', (array) $user->roles, true ) ) {
			return 'tutor';
		}
		if ( in_array( 'ngt_parent', (array) $user->roles, true ) || in_array( 'parent', (array) $user->roles, true ) ) {
			return 'parent';
		}
		return 'student';
	}

	/**
	 * @return array<int, array{title: string, date: string, duration: string, status: string}>
	 */
	public static function events_for_user( int $user_id, string $role, string $month ): array {
		$meta_key = 'tutor' === $role ? 'ngt_tutor_user_id' : 'ngt_student_user_id';
		$q = new WP_Query(
			[
				'post_type'      => 'ngt_lesson',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_query'     => [
					[
						'key'   => $meta_key,
						'value' => $user_id,
					],
					[
						'key'     => 'ngt_lesson_date',
						'value'   => $month,
						'compare' => 'LIKE',
					],
				],
				'meta_key'       => 'ngt_lesson_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			]
		);

		$events = [];
		foreach ( $q->posts as $post ) {
			$events[] = [
				'title'    => $post->post_title,
				'date'     => (string) get_post_meta( $post->ID, 'ngt_lesson_date', true ),
				'duration' => (string) ( get_post_meta( $post->ID, 'ngt_lesson_duration', true ) ?: '60' ),
				'status'   => (string) ( get_post_meta( $post->ID, 'ngt_lesson_status', true ) ?: 'scheduled' ),
			];
		}
		return $events;
	}
}
