<?php
/**
 * Parent reviews and tutor ratings.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reviews and ratings.
 */
class NGC_Reviews {

	/**
	 * @param array<string, mixed> $data Review data.
	 * @return int|WP_Error
	 */
	public static function create_review( $data ) {
		global $wpdb;
		$parent_id  = (int) ( $data['parent_user_id'] ?? get_current_user_id() );
		$tutor_id   = (int) ( $data['tutor_user_id'] ?? 0 );
		$booking_id = (int) ( $data['booking_id'] ?? 0 );
		$rating     = min( 5, max( 1, (int) ( $data['rating'] ?? 0 ) ) );
		$comment    = sanitize_textarea_field( $data['comment'] ?? '' );

		if ( ! $tutor_id || ! $rating ) {
			return new WP_Error( 'ngc_review_invalid', __( 'Tutor and rating are required.', 'nextgencompanion' ) );
		}

		$table = NGC_Database::table( 'reviews' );
		$wpdb->insert(
			$table,
			[
				'booking_id'     => $booking_id,
				'parent_user_id' => $parent_id,
				'tutor_user_id'  => $tutor_id,
				'rating'         => $rating,
				'comment'        => $comment,
				'status'         => 'published',
				'created_at'     => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%d', '%d', '%s', '%s', '%s' ]
		);

		$review_id = (int) $wpdb->insert_id;
		self::upsert_rating( $tutor_id, (int) ( $data['student_user_id'] ?? 0 ), $booking_id, $rating, $comment );
		NGC_Audit::log( 'review_created', 'review', $review_id, $data, $parent_id );
		do_action(
			'ngc_review_submitted',
			[
				'review_id'       => $review_id,
				'booking_id'      => $booking_id,
				'tutor_user_id'   => $tutor_id,
				'parent_user_id'  => $parent_id,
				'rating'          => $rating,
				'comment'         => $comment,
			]
		);

		return $review_id;
	}

	/**
	 * @param int    $tutor_id   Tutor user ID.
	 * @param int    $student_id Student user ID.
	 * @param int    $booking_id Booking ID.
	 * @param int    $rating     Rating 1-5.
	 * @param string $comment    Comment.
	 */
	public static function upsert_rating( $tutor_id, $student_id, $booking_id, $rating, $comment = '' ) {
		global $wpdb;
		$table = NGC_Database::table( 'ratings' );

		$existing = 0;
		if ( $booking_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE booking_id = %d LIMIT 1", $booking_id ) );
		}

		$row = [
			'tutor_user_id'   => $tutor_id,
			'student_user_id' => $student_id,
			'booking_id'      => $booking_id,
			'rating'          => $rating,
			'comment'         => $comment,
			'created_at'      => current_time( 'mysql', true ),
		];

		if ( $existing ) {
			$wpdb->update( $table, $row, [ 'id' => $existing ], [ '%d', '%d', '%d', '%d', '%s', '%s' ], [ '%d' ] );
		} else {
			$wpdb->insert( $table, $row, [ '%d', '%d', '%d', '%d', '%s', '%s' ] );
		}
	}

	/**
	 * @param int $tutor_id Tutor user ID.
	 * @return float
	 */
	public static function average_for_tutor( $tutor_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'ratings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$avg = $wpdb->get_var( $wpdb->prepare( "SELECT AVG(rating) FROM {$table} WHERE tutor_user_id = %d", $tutor_id ) );
		return $avg ? round( (float) $avg, 1 ) : 0.0;
	}

	/**
	 * @param int $parent_id Parent user ID.
	 * @return float
	 */
	public static function average_given_by_parent( $parent_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'reviews' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$avg = $wpdb->get_var( $wpdb->prepare( "SELECT AVG(rating) FROM {$table} WHERE parent_user_id = %d", $parent_id ) );
		return $avg ? round( (float) $avg, 1 ) : 0.0;
	}

	/**
	 * Record tutor earning when booking completes.
	 *
	 * @param object $booking Booking row.
	 */
	public static function record_earning( $booking ) {
		global $wpdb;
		$table  = NGC_Database::table( 'earnings' );
		$amount = (float) $booking->amount;
		if ( $amount <= 0 ) {
			$amount = 350.0;
		}
		$tutor_share = round( $amount * 0.7, 2 );

		$wpdb->insert(
			$table,
			[
				'tutor_user_id' => (int) $booking->tutor_user_id,
				'booking_id'    => (int) $booking->id,
				'amount'        => $tutor_share,
				'currency'      => $booking->currency ?: 'ZAR',
				'status'        => 'pending',
				'earned_at'     => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%f', '%s', '%s', '%s' ]
		);
	}

	/**
	 * @param int $tutor_id Tutor user ID.
	 * @return float Pending payout total.
	 */
	public static function pending_payout_for_tutor( $tutor_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'earnings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sum = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table} WHERE tutor_user_id = %d AND status = 'pending'", $tutor_id ) );
		return $sum ? (float) $sum : 0.0;
	}

	/**
	 * Create a pending payout record without settling earnings.
	 *
	 * @param int   $tutor_id Tutor user ID.
	 * @param float $amount   Amount.
	 * @return int|WP_Error Payout ID.
	 */
	public static function create_payout( $tutor_id, $amount = 0.0 ) {
		global $wpdb;
		$pending = self::pending_payout_for_tutor( $tutor_id );
		if ( $amount <= 0 ) {
			$amount = $pending;
		}
		if ( $amount <= 0 ) {
			return new WP_Error( 'ngc_no_payout', __( 'No pending earnings.', 'nextgencompanion' ) );
		}

		/**
		 * Filter payout amount before insert (business rules: minimum, fee).
		 *
		 * @param float $amount   Amount.
		 * @param int   $tutor_id Tutor user ID.
		 */
		$filtered = apply_filters( 'ngc_payout_create_amount', (float) $amount, (int) $tutor_id );
		if ( is_wp_error( $filtered ) ) {
			return $filtered;
		}
		$amount = (float) $filtered;
		if ( $amount <= 0 ) {
			return new WP_Error( 'ngc_no_payout', __( 'No pending earnings.', 'nextgencompanion' ) );
		}

		$payout_table = NGC_Database::table( 'payouts' );
		$wpdb->insert(
			$payout_table,
			[
				'tutor_user_id' => $tutor_id,
				'amount'        => $amount,
				'currency'      => 'ZAR',
				'status'        => 'pending',
				'created_at'    => current_time( 'mysql', true ),
			],
			[ '%d', '%f', '%s', '%s', '%s' ]
		);

		$payout_id = (int) $wpdb->insert_id;
		if ( ! $payout_id ) {
			return new WP_Error( 'ngc_payout_create', __( 'Could not create payout record.', 'nextgencompanion' ) );
		}

		NGC_Workflows::dispatch(
			'payout.calculated',
			[
				'payout_id'     => (string) $payout_id,
				'tutor_user_id' => (string) $tutor_id,
				'amount'        => (string) $amount,
				'status'        => 'pending',
			]
		);

		NGC_Audit::log( 'payout_created', 'payout', $payout_id, [ 'amount' => $amount, 'status' => 'pending' ], 0 );
		return $payout_id;
	}

	/**
	 * Mark a pending payout as paid and settle linked earnings.
	 *
	 * @param int $payout_id Payout ID.
	 * @return int|WP_Error Payout ID.
	 */
	public static function confirm_payout( $payout_id ) {
		global $wpdb;
		$payout_table = NGC_Database::table( 'payouts' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$payout_table} WHERE id = %d", (int) $payout_id ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ngc_payout_missing', __( 'Payout not found.', 'nextgencompanion' ) );
		}
		if ( 'paid' === ( $row['status'] ?? '' ) ) {
			return (int) $payout_id;
		}

		$tutor_id = (int) ( $row['tutor_user_id'] ?? 0 );
		$amount   = (float) ( $row['amount'] ?? 0 );
		$wpdb->update(
			$payout_table,
			[
				'status'  => 'paid',
				'paid_at' => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $payout_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		$earnings_table = NGC_Database::table( 'earnings' );
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$earnings_table} SET status = 'paid', payout_id = %d WHERE tutor_user_id = %d AND status = 'pending'",
				(int) $payout_id,
				$tutor_id
			)
		);

		NGC_Workflows::dispatch(
			'payout.processed',
			[
				'payout_id'     => (string) $payout_id,
				'tutor_user_id' => (string) $tutor_id,
				'amount'        => (string) $amount,
			]
		);

		NGC_Audit::log( 'payout_processed', 'payout', (int) $payout_id, [ 'amount' => $amount ], 0 );
		do_action(
			'ngc_payout_completed',
			(int) $payout_id,
			[
				'amount'         => $amount,
				'tutor_user_id'  => $tutor_id,
			]
		);
		return (int) $payout_id;
	}

	/**
	 * @param int   $tutor_id Tutor user ID.
	 * @param float $amount Amount.
	 * @param bool|null $auto_confirm Settle earnings immediately when true.
	 * @return int|WP_Error Payout ID.
	 */
	public static function process_payout( $tutor_id, $amount = 0.0, $auto_confirm = null ) {
		if ( null === $auto_confirm ) {
			$auto_confirm = (bool) apply_filters( 'ngc_payout_auto_confirm', true );
		}
		$payout_id = self::create_payout( $tutor_id, $amount );
		if ( is_wp_error( $payout_id ) ) {
			return $payout_id;
		}
		if ( $auto_confirm ) {
			return self::confirm_payout( $payout_id );
		}
		return $payout_id;
	}

	/**
	 * Total published (or all) review rows.
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;
		if ( ! class_exists( 'NGC_Database' ) ) {
			return 0;
		}
		$table = NGC_Database::table( 'reviews' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Recent review rows for admin listings.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( $limit = 25 ) {
		global $wpdb;
		if ( ! class_exists( 'NGC_Database' ) ) {
			return [];
		}
		$table = NGC_Database::table( 'reviews' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return [];
		}
		$limit = max( 1, min( 100, (int) $limit ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, booking_id, parent_user_id, tutor_user_id, rating, comment, status, created_at FROM {$table} ORDER BY id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Static API.
	}
}
