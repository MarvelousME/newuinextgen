<?php
/**
 * Visual dashboard designer — widget catalog, runtime, shortcodes.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Studio dashboards with hot-apply on save.
 */
class NGC_Studio_Dashboards {

	/** @var array<string, array<string, mixed>> */
	private static $published = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 25 );
		add_action( 'ngc_studio_dashboards_reload', [ __CLASS__, 'reload_published' ] );
		add_filter( 'ngc_studio_role_dashboard', [ __CLASS__, 'override_role_dashboard' ], 10, 2 );
		self::reload_published();
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function widget_catalog() {
		$widgets = [
			'stat_card'        => [ 'label' => 'Stat card', 'group' => 'metrics' ],
			'chart_bar'        => [ 'label' => 'Bar chart', 'group' => 'metrics' ],
			'chart_line'       => [ 'label' => 'Line chart', 'group' => 'metrics' ],
			'table'            => [ 'label' => 'Data table', 'group' => 'data' ],
			'list'             => [ 'label' => 'List', 'group' => 'data' ],
			'bookings'         => [ 'label' => 'Upcoming bookings', 'group' => 'tutoring' ],
			'lessons'          => [ 'label' => 'Lessons', 'group' => 'tutoring' ],
			'earnings'         => [ 'label' => 'Earnings', 'group' => 'finance' ],
			'notifications'    => [ 'label' => 'Notifications', 'group' => 'alerts' ],
			'quick_actions'    => [ 'label' => 'Quick actions', 'group' => 'actions' ],
			'workflow_status'  => [ 'label' => 'Workflow status', 'group' => 'studio' ],
			'recent_activity'  => [ 'label' => 'Recent activity', 'group' => 'studio' ],
			'welcome'          => [ 'label' => 'Welcome banner', 'group' => 'layout' ],
			'spacer'           => [ 'label' => 'Spacer', 'group' => 'layout' ],
		];
		return apply_filters( 'ngc_studio_dashboard_widget_catalog', $widgets );
	}

	/**
	 * @return array<string, string>
	 */
	public static function role_catalog() {
		return apply_filters(
			'ngc_studio_dashboard_roles',
			[
				'admin'   => __( 'Administrator', 'nextgencompanion' ),
				'tutor'   => __( 'Tutor', 'nextgencompanion' ),
				'parent'  => __( 'Parent', 'nextgencompanion' ),
				'student' => __( 'Student', 'nextgencompanion' ),
				'all'     => __( 'All roles', 'nextgencompanion' ),
			]
		);
	}

	/**
	 * @param int                  $id   Dashboard ID.
	 * @param array<string, mixed> $data Payload.
	 * @return array{ok:bool,dashboard?:array<string,mixed>}
	 */
	public static function save_and_apply( $id, $data ) {
		$result = NGC_Studio_Repository::update_dashboard( $id, $data );
		if ( empty( $result['ok'] ) ) {
			return $result;
		}
		$dashboard = $result['dashboard'];
		if ( $dashboard && 'published' === ( $dashboard['status'] ?? '' ) ) {
			self::$published[ (string) $dashboard['dashboard_key'] ] = $dashboard;
		}
		do_action( 'ngc_studio_dashboards_reload' );
		return $result;
	}

	/**
	 * @param int $id Dashboard ID.
	 * @return array{ok:bool,dashboard?:array<string,mixed>}
	 */
	public static function publish( $id ) {
		return self::save_and_apply( $id, [ 'status' => 'published' ] );
	}

	/**
	 * Reload published dashboards.
	 */
	public static function reload_published() {
		self::$published = [];
		foreach ( NGC_Studio_Repository::list_dashboards( 'published' ) as $dashboard ) {
			self::$published[ (string) $dashboard['dashboard_key'] ] = $dashboard;
		}
	}

	/**
	 * Register shortcodes.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'ngc_studio_dashboard', [ __CLASS__, 'shortcode' ] );
		foreach ( self::$published as $key => $dashboard ) {
			add_shortcode( 'ngc_dashboard_' . $key, static function () use ( $key ) {
				return NGC_Studio_Dashboards::render( $key );
			} );
		}
	}

	/**
	 * @param array<string, string> $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( [ 'key' => '' ], $atts, 'ngc_studio_dashboard' );
		return self::render( sanitize_key( (string) $atts['key'] ) );
	}

	/**
	 * @param string $key Dashboard key.
	 * @return string
	 */
	public static function render( $key ) {
		$dashboard = self::$published[ $key ] ?? NGC_Studio_Repository::get_dashboard_by_key( $key );
		if ( ! $dashboard ) {
			return '';
		}
		$widgets = (array) ( $dashboard['widgets'] ?? [] );
		$layout  = (array) ( $dashboard['layout'] ?? [] );
		$cols    = max( 1, (int) ( $layout['columns'] ?? 2 ) );

		ob_start();
		echo '<div class="ngc-studio-dashboard" data-key="' . esc_attr( $key ) . '" style="--ngc-dash-cols:' . esc_attr( (string) $cols ) . '">';
		foreach ( $widgets as $widget ) {
			echo self::render_widget( (array) $widget );
		}
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $widget Widget config.
	 * @return string
	 */
	private static function render_widget( $widget ) {
		$type  = sanitize_key( (string) ( $widget['type'] ?? 'stat_card' ) );
		$title = esc_html( (string) ( $widget['title'] ?? '' ) );
		$span  = max( 1, min( 4, (int) ( $widget['span'] ?? 1 ) ) );
		$html  = '<div class="ngc-studio-dashboard__widget ngc-studio-dashboard__widget--' . esc_attr( $type ) . '" style="grid-column:span ' . esc_attr( (string) $span ) . '">';
		if ( $title ) {
			$html .= '<h4 class="ngc-studio-dashboard__title">' . $title . '</h4>';
		}
		switch ( $type ) {
			case 'welcome':
				$html .= '<p>' . esc_html( (string) ( $widget['body'] ?? __( 'Welcome back.', 'nextgencompanion' ) ) ) . '</p>';
				break;
			case 'stat_card':
				$value = (string) ( $widget['value'] ?? '' );
				if ( 'auto' === $value || '' === $value ) {
					$value = self::resolve_stat_value( (string) ( $widget['metric'] ?? '' ) );
				}
				$html .= '<div class="ngc-studio-dashboard__stat">' . esc_html( $value ) . '</div>';
				break;
			case 'chart_bar':
			case 'chart_line':
				$html .= '<div class="ngc-studio-dashboard__chart" data-chart="' . esc_attr( $type ) . '">' . self::render_chart_placeholder( $widget ) . '</div>';
				break;
			case 'table':
			case 'list':
				$html .= '<ul class="ngc-studio-dashboard__list">' . self::render_list_items( $widget ) . '</ul>';
				break;
			case 'bookings':
				$html .= self::render_bookings_widget( $widget );
				break;
			case 'lessons':
				$html .= self::render_lessons_widget( $widget );
				break;
			case 'earnings':
				$html .= '<div class="ngc-studio-dashboard__stat">' . esc_html( self::format_currency( self::current_user_earnings() ) ) . '</div>';
				break;
			case 'notifications':
				$html .= self::render_notifications_widget();
				break;
			case 'quick_actions':
				foreach ( (array) ( $widget['actions'] ?? [] ) as $action ) {
					$label = esc_html( (string) ( $action['label'] ?? '' ) );
					$url   = esc_url( (string) ( $action['url'] ?? '#' ) );
					if ( $label ) {
						$html .= '<a class="ngc-studio-dashboard__action" href="' . $url . '">' . $label . '</a>';
					}
				}
				break;
			case 'workflow_status':
				$runtime = class_exists( 'NGC_Studio_Runtime' ) ? NGC_Studio_Runtime::status() : [];
				$html   .= '<p>' . esc_html( sprintf(
					/* translators: 1: active workflows 2: triggers */
					__( '%1$d active workflows · %2$d triggers', 'nextgencompanion' ),
					(int) ( $runtime['active_workflows'] ?? 0 ),
					(int) ( $runtime['active_triggers'] ?? 0 )
				) ) . '</p>';
				break;
			case 'recent_activity':
				$html .= '<ul class="ngc-studio-dashboard__list">' . self::render_recent_activity() . '</ul>';
				break;
			case 'spacer':
				$html .= '<span class="ngc-studio-dashboard__spacer" aria-hidden="true"></span>';
				break;
			default:
				$html .= '<p class="ngc-studio-dashboard__placeholder">' . esc_html( ucwords( str_replace( '_', ' ', $type ) ) ) . '</p>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * @param string $metric Metric key.
	 * @return string
	 */
	private static function resolve_stat_value( $metric ) {
		switch ( sanitize_key( $metric ) ) {
			case 'active_workflows':
				return (string) ( class_exists( 'NGC_Studio_Runtime' ) ? (int) ( NGC_Studio_Runtime::status()['active_workflows'] ?? 0 ) : 0 );
			case 'executions_today':
				global $wpdb;
				$table = NGC_Database::table( 'studio_executions' );
				if ( ! $table ) {
					return '0';
				}
				$today = gmdate( 'Y-m-d' );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return (string) (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE started_at >= %s", $today . ' 00:00:00' ) );
			case 'wallet_balance':
				return self::format_currency( class_exists( 'NGC_Wallet' ) ? NGC_Wallet::balance( get_current_user_id() ) : 0 );
			default:
				return '—';
		}
	}

	/**
	 * @param float $amount Amount.
	 * @return string
	 */
	private static function format_currency( $amount ) {
		return 'R ' . number_format( (float) $amount, 2 );
	}

	/**
	 * @return float
	 */
	private static function current_user_earnings() {
		global $wpdb;
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return 0.0;
		}
		$table = NGC_Database::table( 'earnings' );
		if ( ! $table ) {
			return 0.0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE tutor_user_id = %d AND status = 'pending'", $user_id ) );
	}

	/**
	 * @param array<string, mixed> $widget Widget config.
	 * @return string
	 */
	private static function render_chart_placeholder( $widget ) {
		$items = (array) ( $widget['series'] ?? [ 3, 5, 2, 8, 4 ] );
		return '<span>' . esc_html( implode( ' · ', array_map( 'strval', $items ) ) ) . '</span>';
	}

	/**
	 * @param array<string, mixed> $widget Widget.
	 * @return string
	 */
	private static function render_list_items( $widget ) {
		$items = (array) ( $widget['items'] ?? [] );
		if ( ! $items ) {
			return '<li>' . esc_html__( 'No items configured.', 'nextgencompanion' ) . '</li>';
		}
		$html = '';
		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( is_array( $item ) ? (string) ( $item['label'] ?? '' ) : (string) $item ) . '</li>';
		}
		return $html;
	}

	/**
	 * @param array<string, mixed> $widget Widget.
	 * @return string
	 */
	private static function render_bookings_widget( $widget ) {
		if ( ! class_exists( 'NGC_Bookings' ) ) {
			return '<p>' . esc_html__( 'Bookings unavailable.', 'nextgencompanion' ) . '</p>';
		}
		$user_id = get_current_user_id();
		$args    = [ 'limit' => 5 ];
		$user    = wp_get_current_user();
		if ( in_array( 'tutor', (array) $user->roles, true ) ) {
			$args['tutor_user_id'] = $user_id;
		} else {
			$args['student_user_id'] = $user_id;
		}
		$rows = NGC_Bookings::query( $args );
		if ( ! $rows ) {
			return '<p>' . esc_html__( 'No upcoming bookings.', 'nextgencompanion' ) . '</p>';
		}
		$html = '<ul class="ngc-studio-dashboard__list">';
		foreach ( $rows as $row ) {
			$html .= '<li>' . esc_html( $row->subject . ' — ' . ( $row->scheduled_at ?: $row->status ) ) . '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * @param array<string, mixed> $widget Widget.
	 * @return string
	 */
	private static function render_lessons_widget( $widget ) {
		return self::render_bookings_widget( $widget );
	}

	/**
	 * @return string
	 */
	private static function render_notifications_widget() {
		$user_id = get_current_user_id();
		$alerts  = get_transient( 'ngc_dashboard_alerts_' . $user_id );
		if ( ! is_array( $alerts ) || ! $alerts ) {
			return '<p>' . esc_html__( 'No new notifications.', 'nextgencompanion' ) . '</p>';
		}
		$html = '<ul class="ngc-studio-dashboard__list">';
		foreach ( array_slice( $alerts, 0, 5 ) as $alert ) {
			$html .= '<li>' . esc_html( (string) ( $alert['message'] ?? '' ) ) . '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * @return string
	 */
	private static function render_recent_activity() {
		if ( ! class_exists( 'NGC_Studio_Repository' ) ) {
			return '<li>' . esc_html__( 'No activity.', 'nextgencompanion' ) . '</li>';
		}
		$rows = NGC_Studio_Repository::list_executions( 5 );
		if ( ! $rows ) {
			return '<li>' . esc_html__( 'No recent workflow runs.', 'nextgencompanion' ) . '</li>';
		}
		$html = '';
		foreach ( $rows as $row ) {
			$html .= '<li>#' . esc_html( (string) ( $row['id'] ?? '' ) ) . ' — ' . esc_html( (string) ( $row['status'] ?? '' ) ) . '</li>';
		}
		return $html;
	}

	/**
	 * @param array<string, mixed> $dashboard Default dashboard payload.
	 * @param string               $role      Role key.
	 * @return array<string, mixed>
	 */
	public static function override_role_dashboard( $dashboard, $role ) {
		foreach ( self::$published as $published ) {
			if ( (string) ( $published['role'] ?? '' ) === sanitize_key( $role ) ) {
				return array_merge( (array) $dashboard, [
					'studio_key' => (string) ( $published['dashboard_key'] ?? '' ),
					'widgets'    => (array) ( $published['widgets'] ?? [] ),
					'layout'     => (array) ( $published['layout'] ?? [] ),
				] );
			}
		}
		return $dashboard;
	}

	/**
	 * Seed default dashboards.
	 */
	public static function seed_defaults() {
		if ( NGC_Studio_Repository::list_dashboards() ) {
			return;
		}
		NGC_Studio_Repository::create_dashboard(
			[
				'dashboard_key' => 'admin_overview',
				'name'          => 'Admin Overview',
				'role'          => 'admin',
				'status'        => 'published',
				'layout'        => [ 'columns' => 3 ],
				'widgets'       => [
					[ 'type' => 'welcome', 'title' => 'Welcome', 'body' => 'Automation Studio admin overview', 'span' => 3 ],
					[ 'type' => 'stat_card', 'title' => 'Active workflows', 'metric' => 'active_workflows', 'value' => 'auto', 'span' => 1 ],
					[ 'type' => 'stat_card', 'title' => 'Executions today', 'metric' => 'executions_today', 'value' => 'auto', 'span' => 1 ],
					[ 'type' => 'workflow_status', 'title' => 'Runtime', 'span' => 1 ],
					[ 'type' => 'recent_activity', 'title' => 'Recent activity', 'span' => 2 ],
					[ 'type' => 'quick_actions', 'title' => 'Quick actions', 'span' => 1, 'actions' => [
						[ 'label' => 'Automation Studio', 'url' => admin_url( 'admin.php?page=ngc-automation-studio' ) ],
					] ],
				],
			]
		);
		NGC_Studio_Repository::create_dashboard(
			[
				'dashboard_key' => 'tutor_home',
				'name'          => 'Tutor Home',
				'role'          => 'tutor',
				'status'        => 'published',
				'layout'        => [ 'columns' => 2 ],
				'widgets'       => [
					[ 'type' => 'welcome', 'title' => 'Tutor dashboard', 'span' => 2 ],
					[ 'type' => 'bookings', 'title' => 'Upcoming sessions', 'span' => 1 ],
					[ 'type' => 'earnings', 'title' => 'Earnings', 'span' => 1 ],
				],
			]
		);
	}
}
