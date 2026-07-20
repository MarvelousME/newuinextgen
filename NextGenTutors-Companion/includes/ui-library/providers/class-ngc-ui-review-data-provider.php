<?php
/**
 * Reviews and testimonials provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Published reviews from ngc_reviews table.
 */
class NGC_UI_Review_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'review';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Reviews' ) && class_exists( 'NGC_Database' );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		if ( ! $this->is_available() ) {
			return [];
		}

		global $wpdb;
		$table = NGC_Database::table( 'reviews' );
		$limit = max( 1, min( 20, (int) ( $args['limit'] ?? 6 ) ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s ORDER BY created_at DESC LIMIT %d",
				'published',
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return [];
		}

		return array_map( [ $this, 'hydrate_review' ], $rows );
	}

	/**
	 * @param array<string, mixed> $row DB row.
	 * @return array<string, mixed>
	 */
	private function hydrate_review( $row ) {
		$parent = get_userdata( (int) ( $row['parent_user_id'] ?? 0 ) );
		$tutor  = get_userdata( (int) ( $row['tutor_user_id'] ?? 0 ) );
		return [
			'id'          => (int) ( $row['id'] ?? 0 ),
			'rating'      => (int) ( $row['rating'] ?? 0 ),
			'comment'     => $row['comment'] ?? '',
			'author'      => $parent ? $parent->display_name : '',
			'tutor_name'  => $tutor ? $tutor->display_name : '',
			'created_at'  => $row['created_at'] ?? '',
		];
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		return [
			'quote'  => $row['comment'] ?? '',
			'rating' => $row['rating'] ?? 0,
			'author' => $row['author'] ?? '',
			'role'   => __( 'Parent', 'nextgencompanion' ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'table'    => 'ngc_reviews',
		];
	}
}
