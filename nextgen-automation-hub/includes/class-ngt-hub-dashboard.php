<?php
/**
 * Live role dashboards with real data.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Dashboard {

	public static function register_hooks(): void {
		add_shortcode( 'ngt_dashboard', [ __CLASS__, 'shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	public static function enqueue_assets(): void {
		if ( ! is_singular() && ! is_page() ) {
			return;
		}
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'ngt_dashboard' ) ) {
			return;
		}
		wp_enqueue_style( 'ngt-hub', NGT_HUB_URL . 'assets/css/ngt-hub.css', [], NGT_Hub::VERSION );
		wp_enqueue_script( 'ngt-dashboard', NGT_HUB_URL . 'assets/js/ngt-dashboard.js', [], NGT_Hub::VERSION, true );
		wp_localize_script(
			'ngt-dashboard',
			'NGTDashboard',
			[
				'rest'  => rest_url( 'ngt/v1/' ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( [ 'role' => 'student' ], $atts, 'ngt_dashboard' );
		$role = sanitize_key( $atts['role'] );

		if ( ! is_user_logged_in() && 'admin' !== $role ) {
			return '<div class="ngt-wrap"><p>' . esc_html__( 'Please log in to view your dashboard.', 'nextgen-automation-hub' ) . '</p></div>';
		}

		$data = self::get_live_data( $role, get_current_user_id() );

		ob_start();
		?>
		<div class="ngt-wrap ngt-dashboard" data-role="<?php echo esc_attr( $role ); ?>">
			<div class="ngt-grid ngt-stats">
				<?php foreach ( $data['stats'] as $key => $stat ) : ?>
					<div class="ngt-card ngt-stat">
						<span class="ngt-stat-value"><?php echo esc_html( (string) $stat['value'] ); ?></span>
						<span class="ngt-stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $data['widgets'] ) ) : ?>
				<div class="ngt-grid" style="margin-top:18px">
					<?php foreach ( $data['widgets'] as $widget ) : ?>
						<div class="ngt-card">
							<h3><?php echo esc_html( $widget['title'] ); ?></h3>
							<?php if ( ! empty( $widget['items'] ) ) : ?>
								<ul class="ngt-list">
									<?php foreach ( $widget['items'] as $item ) : ?>
										<li><?php echo esc_html( (string) $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p><?php echo esc_html( $widget['empty'] ?? __( 'No data yet.', 'nextgen-automation-hub' ) ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $data['gamification'] ) ) : ?>
				<div class="ngt-card ngt-gamification" style="margin-top:18px">
					<h3><?php esc_html_e( 'Points & Badges', 'nextgen-automation-hub' ); ?></h3>
					<p><strong><?php echo esc_html( (string) ( $data['gamification']['points'] ?? 0 ) ); ?></strong> <?php esc_html_e( 'points', 'nextgen-automation-hub' ); ?> · <?php esc_html_e( 'Level', 'nextgen-automation-hub' ); ?> <?php echo esc_html( (string) ( $data['gamification']['level'] ?? 1 ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_live_data( string $role, int $user_id ): array {
		if ( class_exists( 'NGC_Automation_Hub_Bridge' ) ) {
			$request = new WP_REST_Request( 'GET', '/ngc/v1/hub/dashboard-widgets/' . $role );
			$response = NGC_Automation_Hub_Bridge::rest_dashboard_widgets( $request );
			if ( ! is_wp_error( $response ) ) {
				$body = $response->get_data();
				return self::normalize_companion_data( $body, $role );
			}
		}

		$stats   = self::native_stats( $role, $user_id );
		$widgets = self::native_widgets( $role, $user_id );
		$gamification = NGT_Hub_Gamification::user_summary( $user_id );

		$data = [
			'role'         => $role,
			'stats'        => $stats,
			'widgets'      => $widgets,
			'gamification' => $gamification,
		];

		return apply_filters( 'ngt_dashboard_widget_data', $data, $role );
	}

	/**
	 * @param array<string, mixed> $body Companion response.
	 * @return array<string, mixed>
	 */
	private static function normalize_companion_data( array $body, string $role ): array {
		$stats = [];
		foreach ( (array) ( $body['stats'] ?? [] ) as $key => $value ) {
			$stats[] = [
				'key'   => $key,
				'value' => $value,
				'label' => self::stat_label( $key ),
			];
		}
		return [
			'role'         => $role,
			'stats'        => $stats,
			'widgets'      => [],
			'gamification' => $body['gamification'] ?? [],
			'charts'       => $body['charts'] ?? [],
		];
	}

	/**
	 * @return array<int, array{key: string, value: int|float|string, label: string}>
	 */
	public static function native_stats( string $role, int $user_id ): array {
		$raw = [];

		switch ( $role ) {
			case 'student':
				$raw = [
					'upcoming_lessons'  => count( NGT_Hub_Data_Model::get_user_lessons( $user_id, 'student', 50 ) ),
					'notifications'     => NGT_Hub_Notifications::unread_count( $user_id ),
					'points'            => NGT_Hub_Gamification::total_points( $user_id ),
				];
				break;
			case 'parent':
				$raw = [
					'active_matches'    => self::parent_match_count( $user_id ),
					'notifications'     => NGT_Hub_Notifications::unread_count( $user_id ),
					'children_profiles' => NGT_Hub_Data_Model::count_posts( 'ngt_student_profile', [ [ 'key' => 'ngt_parent_user_id', 'value' => $user_id ] ] ),
				];
				break;
			case 'tutor':
				$payout = NGT_Hub_Payouts::tutor_summary( $user_id );
				$raw = [
					'active_students'   => count( NGT_Hub_Data_Model::get_user_lessons( $user_id, 'tutor', 50 ) ),
					'pending_payouts'   => 'R' . number_format( (float) $payout['pending_payouts'], 0 ),
					'completed_lessons' => (int) $payout['lesson_count'],
				];
				break;
			case 'admin':
				$raw = [
					'pending_matches'   => NGT_Hub_Matching::pending_count(),
					'lessons_today'     => self::lessons_today_count(),
					'support_cases'     => NGT_Hub_Data_Model::count_posts( 'ngt_support_case' ),
					'events_logged'     => self::events_today_count(),
				];
				break;
			case 'support':
				$raw = [
					'open_cases'        => NGT_Hub_Data_Model::count_posts( 'ngt_support_case', [ [ 'key' => 'ngt_status', 'value' => 'open' ] ] ),
					'rtm_messages'      => self::rtm_messages_today(),
					'escalated'         => NGT_Hub_Data_Model::count_posts( 'ngt_support_case', [ [ 'key' => 'ngt_priority', 'value' => 'high' ] ] ),
				];
				break;
		}

		$stats = [];
		foreach ( $raw as $key => $value ) {
			$stats[] = [
				'key'   => $key,
				'value' => $value,
				'label' => self::stat_label( $key ),
			];
		}
		return $stats;
	}

	/**
	 * @return array<int, array{title: string, items?: array<int, string>, empty?: string}>
	 */
	private static function native_widgets( string $role, int $user_id ): array {
		$widgets = [];
		if ( 'student' === $role || 'tutor' === $role ) {
			$lessons = NGT_Hub_Data_Model::get_user_lessons( $user_id, 'tutor' === $role ? 'tutor' : 'student', 5 );
			$items   = [];
			foreach ( $lessons as $lesson ) {
				$date = get_post_meta( $lesson->ID, 'ngt_lesson_date', true );
				$items[] = $lesson->post_title . ( $date ? ' — ' . $date : '' );
			}
			$widgets[] = [
				'title' => __( 'Upcoming Lessons', 'nextgen-automation-hub' ),
				'items' => $items,
				'empty' => __( 'No upcoming lessons scheduled.', 'nextgen-automation-hub' ),
			];
		}
		if ( in_array( $role, [ 'student', 'parent', 'tutor' ], true ) ) {
			$notes = NGT_Hub_Notifications::for_user( $user_id, 5, true );
			$items = array_map(
				static function ( $n ) {
					return $n['title'];
				},
				$notes
			);
			$widgets[] = [
				'title' => __( 'Notifications', 'nextgen-automation-hub' ),
				'items' => $items,
				'empty' => __( 'No unread notifications.', 'nextgen-automation-hub' ),
			];
		}
		return $widgets;
	}

	private static function stat_label( string $key ): string {
		$labels = [
			'upcoming_lessons'    => __( 'Upcoming Lessons', 'nextgen-automation-hub' ),
			'notifications'       => __( 'Unread Notifications', 'nextgen-automation-hub' ),
			'points'              => __( 'Points', 'nextgen-automation-hub' ),
			'active_matches'      => __( 'Active Matches', 'nextgen-automation-hub' ),
			'children_profiles'   => __( 'Children', 'nextgen-automation-hub' ),
			'active_students'     => __( 'Active Students', 'nextgen-automation-hub' ),
			'pending_payouts'     => __( 'Pending Payouts', 'nextgen-automation-hub' ),
			'completed_lessons'   => __( 'Completed Lessons', 'nextgen-automation-hub' ),
			'pending_matches'     => __( 'Pending Matches', 'nextgen-automation-hub' ),
			'lessons_today'       => __( 'Lessons Today', 'nextgen-automation-hub' ),
			'support_cases'       => __( 'Support Cases', 'nextgen-automation-hub' ),
			'events_logged'       => __( 'Events Today', 'nextgen-automation-hub' ),
			'open_cases'          => __( 'Open Cases', 'nextgen-automation-hub' ),
			'rtm_messages'        => __( 'RTM Messages Today', 'nextgen-automation-hub' ),
			'escalated'           => __( 'Escalated Cases', 'nextgen-automation-hub' ),
			'upcoming_lessons_comp'=> __( 'Upcoming Lessons', 'nextgen-automation-hub' ),
			'completed_lessons_comp'=> __( 'Completed Lessons', 'nextgen-automation-hub' ),
			'children_count'      => __( 'Children', 'nextgen-automation-hub' ),
			'active_bookings'     => __( 'Active Bookings', 'nextgen-automation-hub' ),
			'total_earnings'      => __( 'Total Earnings', 'nextgen-automation-hub' ),
			'total_bookings_today'=> __( 'Bookings Today', 'nextgen-automation-hub' ),
			'total_revenue_today' => __( 'Revenue Today', 'nextgen-automation-hub' ),
			'active_tutors'       => __( 'Active Tutors', 'nextgen-automation-hub' ),
		];
		return $labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
	}

	private static function parent_match_count( int $user_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM " . NGT_Hub_Database::table( 'matches' ) . " WHERE parent_user_id = %d AND status IN ('pending','proposed','accepted')",
				$user_id
			)
		);
	}

	private static function lessons_today_count(): int {
		return NGT_Hub_Data_Model::count_posts(
			'ngt_lesson',
			[
				[
					'key'     => 'ngt_lesson_date',
					'value'   => gmdate( 'Y-m-d' ),
					'compare' => 'LIKE',
				],
			]
		);
	}

	private static function events_today_count(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . NGT_Hub_Database::table( 'events' ) . " WHERE DATE(created_at) = CURDATE()"
		);
	}

	private static function rtm_messages_today(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . NGT_Hub_Database::table( 'rtm_messages' ) . " WHERE DATE(created_at) = CURDATE()"
		);
	}
}
