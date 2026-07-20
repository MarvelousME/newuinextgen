<?php
/**
 * Real analytics and metric formulas.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analytics metrics computed from WP + custom/plugin tables.
 */
class NGC_Platform_Analytics {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function metric_matrix() {
		return [
			'total_users' => [ 'formula' => 'COUNT(users)', 'tables' => [ 'wp_users' ] ],
			'total_parents' => [ 'formula' => 'COUNT(role=parent|parent_guardian)', 'tables' => [ 'wp_usermeta' ] ],
			'total_students' => [ 'formula' => 'COUNT(role=student)', 'tables' => [ 'wp_usermeta' ] ],
			'total_tutors' => [ 'formula' => 'COUNT(role=tutor)', 'tables' => [ 'wp_usermeta' ] ],
			'tutor_applicants' => [ 'formula' => 'COUNT(status=pending|rejected|approved)', 'tables' => [ 'ngc_tutor_applications' ] ],
			'approved_tutors' => [ 'formula' => 'COUNT(status=approved)', 'tables' => [ 'ngc_tutor_applications' ] ],
			'rejected_tutors' => [ 'formula' => 'COUNT(status=rejected)', 'tables' => [ 'ngc_tutor_applications' ] ],
			'active_bookings' => [ 'formula' => 'COUNT(status in requested,confirmed)', 'tables' => [ 'ngc_bookings' ] ],
			'completed_lessons' => [ 'formula' => 'COUNT(status=completed)', 'tables' => [ 'ngc_bookings' ] ],
			'cancelled_lessons' => [ 'formula' => 'COUNT(status=cancelled)', 'tables' => [ 'ngc_bookings' ] ],
			'revenue' => [ 'formula' => 'SUM(invoices.paid.amount)', 'tables' => [ 'ngc_invoices' ] ],
			'pending_payments' => [ 'formula' => 'COUNT(invoice.status=issued)', 'tables' => [ 'ngc_invoices' ] ],
			'paid_invoices' => [ 'formula' => 'COUNT(invoice.status=paid)', 'tables' => [ 'ngc_invoices' ] ],
			'failed_payments' => [ 'formula' => 'COUNT(analytics event payment_failed)', 'tables' => [ 'ngc_analytics_events' ] ],
			'refunds' => [ 'formula' => 'COUNT(analytics event payment_refunded)', 'tables' => [ 'ngc_analytics_events' ] ],
			'wallet_balances' => [ 'formula' => 'SUM(latest wallet balance_after)', 'tables' => [ 'ngc_wallet_ledger' ] ],
			'tutor_payouts' => [ 'formula' => 'SUM(payouts.amount status=paid)', 'tables' => [ 'ngc_payouts' ] ],
			'reviews' => [ 'formula' => 'COUNT(reviews)', 'tables' => [ 'ngc_reviews' ] ],
			'average_tutor_rating' => [ 'formula' => 'AVG(ratings.rating)', 'tables' => [ 'ngc_ratings' ] ],
			'conversion_rate' => [ 'formula' => 'conversions / visitor_profiles', 'tables' => [ 'ngc_conversion_events', 'ngc_visitor_profiles' ] ],
			'lead_source_performance' => [ 'formula' => 'COUNT by source', 'tables' => [ 'ngc_acquisition_sources' ] ],
			'affiliate_performance' => [ 'formula' => 'COUNT by affiliate_id', 'tables' => [ 'ngc_affiliate_clicks' ] ],
			'query_string_performance' => [ 'formula' => 'COUNT source params', 'tables' => [ 'ngc_acquisition_sources' ] ],
			'device_breakdown' => [ 'formula' => 'COUNT by device_type', 'tables' => [ 'ngc_analytics_events payload.device_type' ] ],
			'browser_breakdown' => [ 'formula' => 'COUNT by browser', 'tables' => [ 'ngc_analytics_events payload.browser' ] ],
			'location_breakdown' => [ 'formula' => 'COUNT by city/country', 'tables' => [ 'ngc_visitor_profiles' ] ],
			'returning_users' => [ 'formula' => 'COUNT(session_count>1)', 'tables' => [ 'ngc_user_profiles' ] ],
			'new_users' => [ 'formula' => 'COUNT(registered in period)', 'tables' => [ 'wp_users' ] ],
			'session_count' => [ 'formula' => 'COUNT(user_sessions)', 'tables' => [ 'ngc_user_sessions' ] ],
			'funnel_drop_off' => [ 'formula' => 'stage deltas from conversion_events', 'tables' => [ 'ngc_conversion_events' ] ],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function snapshot() {
		global $wpdb;
		$bookings = NGC_Database::table( 'bookings' );
		$apps     = NGC_Database::table( 'tutor_applications' );
		$invoices = NGC_Database::table( 'invoices' );
		$wallet   = NGC_Database::table( 'wallet_ledger' );
		$payouts  = NGC_Database::table( 'payouts' );
		$reviews  = NGC_Database::table( 'reviews' );
		$ratings  = NGC_Database::table( 'ratings' );
		$events   = NGC_Database::table( 'analytics_events' );
		$sources  = NGC_Database::table( 'acquisition_sources' );
		$visitors = NGC_Database::table( 'visitor_profiles' );
		$sessions = NGC_Database::table( 'user_sessions' );
		$conv     = NGC_Database::table( 'conversion_events' );
		$profiles = NGC_Database::table( 'user_profiles' );

		$data = [
			'total_users'       => count_users()['total_users'],
			'total_parents'     => self::count_users_in_roles( [ 'parent', 'parent_guardian' ] ),
			'total_students'    => self::count_users_in_roles( [ 'student' ] ),
			'total_tutors'      => self::count_users_in_roles( [ 'tutor' ] ),
			'tutor_applicants'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$apps}" ),
			'approved_tutors'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$apps} WHERE status = 'approved'" ),
			'rejected_tutors'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$apps} WHERE status = 'rejected'" ),
			'active_bookings'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings} WHERE status IN ('requested','confirmed')" ),
			'completed_lessons' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings} WHERE status = 'completed'" ),
			'cancelled_lessons' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings} WHERE status = 'cancelled'" ),
			'revenue'           => (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$invoices} WHERE status = 'paid'" ),
			'pending_payments'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$invoices} WHERE status = 'issued'" ),
			'paid_invoices'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$invoices} WHERE status = 'paid'" ),
			'failed_payments'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$events} WHERE event_key = 'payment_failed'" ),
			'refunds'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$events} WHERE event_key = 'payment_refunded'" ),
			'wallet_balances'   => (float) $wpdb->get_var( "SELECT COALESCE(SUM(balance_after),0) FROM {$wallet} w INNER JOIN (SELECT user_id, MAX(id) mid FROM {$wallet} GROUP BY user_id) m ON m.mid = w.id" ),
			'tutor_payouts'     => (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$payouts} WHERE status = 'paid'" ),
			'reviews'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$reviews}" ),
			'average_tutor_rating' => (float) $wpdb->get_var( "SELECT COALESCE(AVG(rating),0) FROM {$ratings}" ),
			'session_count'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions}" ),
			'new_users'         => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s", gmdate( 'Y-m-01 00:00:00' ) ) ),
			'returning_users'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$profiles} WHERE session_count > 1" ),
		];

		$visitor_count = max( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$visitors}" ) );
		$conversions   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$conv}" );
		$data['conversion_rate'] = round( ( $conversions / $visitor_count ) * 100, 2 );
		$data['lead_source_performance'] = self::group_counts( $sources, 'source' );
		$data['affiliate_performance']   = self::group_counts( NGC_Database::table( 'affiliate_clicks' ), 'affiliate_id' );
		$data['query_string_performance'] = self::group_counts( $sources, 'campaign' );
		$data['device_breakdown'] = self::group_payload_counts( $events, 'device_type' );
		$data['browser_breakdown'] = self::group_payload_counts( $events, 'browser' );
		$data['location_breakdown'] = self::group_counts( $visitors, 'country' );
		$data['funnel_drop_off'] = self::funnel_drop_off( $conv );

		return $data;
	}

	/**
	 * @param string[] $roles Roles.
	 * @return int
	 */
	private static function count_users_in_roles( $roles ) {
		$total = 0;
		foreach ( $roles as $role ) {
			$total += count( get_users( [ 'role' => $role, 'fields' => 'ID' ] ) );
		}
		return $total;
	}

	/**
	 * @param string $table  Table.
	 * @param string $column Column.
	 * @return array<string, int>
	 */
	private static function group_counts( $table, $column ) {
		global $wpdb;
		if ( ! $table ) {
			return [];
		}
		$column = sanitize_key( $column );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT {$column} k, COUNT(*) c FROM {$table} GROUP BY {$column} ORDER BY c DESC LIMIT 20", ARRAY_A );
		$out = [];
		foreach ( $rows as $row ) {
			$key = (string) ( $row['k'] ?? '' );
			if ( '' === $key ) {
				$key = 'unknown';
			}
			$out[ $key ] = (int) $row['c'];
		}
		return $out;
	}

	/**
	 * @param string $table Table.
	 * @param string $field JSON field.
	 * @return array<string, int>
	 */
	private static function group_payload_counts( $table, $field ) {
		global $wpdb;
		$out = [];
		if ( ! $table ) {
			return $out;
		}
		$field = sanitize_key( $field );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT payload FROM {$table} WHERE event_key = 'device_profile' LIMIT 1000", ARRAY_A );
		foreach ( $rows as $row ) {
			$data = json_decode( (string) $row['payload'], true );
			$key  = sanitize_text_field( $data[ $field ] ?? 'unknown' );
			if ( ! isset( $out[ $key ] ) ) {
				$out[ $key ] = 0;
			}
			++$out[ $key ];
		}
		arsort( $out );
		return array_slice( $out, 0, 20, true );
	}

	/**
	 * @param string $conversion_table Conversion table.
	 * @return array<string, int>
	 */
	private static function funnel_drop_off( $conversion_table ) {
		global $wpdb;
		if ( ! $conversion_table ) {
			return [];
		}
		$stages = [
			'landing'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$conversion_table} WHERE event_key = %s", 'landing_view' ) ),
			'lead'         => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$conversion_table} WHERE event_key = %s", 'lead_created' ) ),
			'match'        => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$conversion_table} WHERE event_key = %s", 'match_created' ) ),
			'booking'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$conversion_table} WHERE event_key = %s", 'booking_created' ) ),
			'payment'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$conversion_table} WHERE event_key = %s", 'payment_completed' ) ),
		];
		$drop = [];
		$prev = null;
		foreach ( $stages as $stage => $count ) {
			if ( null === $prev ) {
				$drop[ $stage ] = 0;
				$prev = $count;
				continue;
			}
			$drop[ $stage ] = max( 0, $prev - $count );
			$prev = $count;
		}
		return $drop;
	}
}

