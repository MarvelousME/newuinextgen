<?php
/**
 * Child learner records linked to parent accounts.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD for wp_ngc_child_learners.
 */
class NGC_Child_Learners {

	/**
	 * @param array<string, mixed> $data Fields.
	 * @return int|WP_Error Record ID.
	 */
	public static function create( $data ) {
		global $wpdb;
		$parent_id = (int) ( $data['parent_user_id'] ?? 0 );
		$name      = sanitize_text_field( (string) ( $data['display_name'] ?? $data['name'] ?? '' ) );
		if ( ! $parent_id || '' === $name ) {
			return new WP_Error( 'ngc_child_invalid', __( 'Parent and learner name are required.', 'nextgencompanion' ) );
		}

		$table = NGC_Database::table( 'child_learners' );
		$uuid  = NGC_Uuid::generate();
		$now   = current_time( 'mysql', true );

		$wpdb->insert(
			$table,
			[
				'uuid'            => $uuid,
				'parent_user_id'  => $parent_id,
				'student_user_id' => (int) ( $data['student_user_id'] ?? 0 ),
				'display_name'    => $name,
				'grade'           => sanitize_text_field( (string) ( $data['grade'] ?? '' ) ),
				'province'        => sanitize_text_field( (string) ( $data['province'] ?? '' ) ),
				'email'           => sanitize_email( (string) ( $data['email'] ?? '' ) ),
				'status'          => sanitize_key( (string) ( $data['status'] ?? 'active' ) ),
				'meta'            => wp_json_encode( (array) ( $data['meta'] ?? [] ) ),
				'created_at'      => $now,
				'updated_at'      => $now,
			],
			[ '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return new WP_Error( 'ngc_child_create', __( 'Could not create child learner record.', 'nextgencompanion' ) );
		}

		NGC_Workflows::dispatch(
			'child_learner.created',
			[
				'child_learner_id' => (string) $id,
				'uuid'             => $uuid,
				'parent_user_id'   => (string) $parent_id,
				'display_name'     => $name,
				'grade'            => (string) ( $data['grade'] ?? '' ),
			]
		);

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'child_learner_created', 'child_learner', $id, [ 'parent_user_id' => $parent_id ], $parent_id );
		}

		if ( empty( (int) ( $data['student_user_id'] ?? 0 ) ) && empty( $data['skip_provision'] ) ) {
			self::provision_wp_user( $id, (string) ( $data['email'] ?? '' ) );
		}

		return $id;
	}

	/**
	 * Create or link a WordPress child_learner user for a child learner row.
	 *
	 * @param int    $child_id Child learner record ID.
	 * @param string $email    Optional email (synthetic if empty).
	 * @return int|WP_Error User ID.
	 */
	public static function provision_wp_user( $child_id, $email = '' ) {
		$row = self::get( (int) $child_id );
		if ( ! $row ) {
			return new WP_Error( 'ngc_child_missing', __( 'Child learner record not found.', 'nextgencompanion' ) );
		}
		if ( ! empty( $row['student_user_id'] ) ) {
			return (int) $row['student_user_id'];
		}

		$parent_id = (int) $row['parent_user_id'];
		$name      = (string) $row['display_name'];
		$email     = sanitize_email( $email );
		if ( ! $email || ! is_email( $email ) ) {
			$host  = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'nextgentutors.local';
			$email = sanitize_email( 'child.' . (int) $child_id . '.' . $parent_id . '@' . $host );
		}

		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			self::link_student( (int) $child_id, (int) $existing->ID );
			return (int) $existing->ID;
		}

		$username = sanitize_user( 'child' . (int) $child_id, true );
		if ( username_exists( $username ) ) {
			$username = sanitize_user( $username . wp_rand( 100, 999 ), true );
		}

		$user_id = wp_create_user( $username, wp_generate_password( 20, true, true ), $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		wp_update_user(
			[
				'ID'           => $user_id,
				'display_name' => $name,
				'first_name'   => $name,
			]
		);

		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			$user->set_role( 'child_learner' );
		}

		if ( ! empty( $row['grade'] ) ) {
			update_user_meta( $user_id, 'ngc_grade', (string) $row['grade'] );
		}
		update_user_meta( $user_id, 'ngc_parent_user_id', $parent_id );

		self::link_student( (int) $child_id, (int) $user_id );

		if ( class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			$adapters = NGC_Workflow_Orchestrator::adapters();
			if ( isset( $adapters['masterstudy'] ) && $adapters['masterstudy']->is_available() ) {
				$adapters['masterstudy']->create_or_update(
					'create_student',
					[
						'user_id'    => $user_id,
						'email'      => $email,
						'first_name' => $name,
						'grade'      => (string) $row['grade'],
					]
				);
			}
		}

		NGC_Workflows::dispatch(
			'child_learner.provisioned',
			[
				'child_learner_id' => (string) $child_id,
				'student_user_id'  => (string) $user_id,
				'parent_user_id'   => (string) $parent_id,
			]
		);

		return (int) $user_id;
	}

	/**
	 * @param int $parent_id Parent user ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_parent( $parent_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'child_learners' );
		if ( ! $table ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE parent_user_id = %d AND status != 'archived' ORDER BY id ASC",
				(int) $parent_id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param int $id Record ID.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'child_learners' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Link WP student user to child learner row.
	 *
	 * @param int $child_id      Child learner ID.
	 * @param int $student_id    Student user ID.
	 * @return bool
	 */
	public static function link_student( $child_id, $student_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'child_learners' );
		$ok    = (bool) $wpdb->update(
			$table,
			[
				'student_user_id' => (int) $student_id,
				'updated_at'      => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $child_id ],
			[ '%d', '%s' ],
			[ '%d' ]
		);
		if ( $ok ) {
			update_user_meta( (int) $student_id, 'ngc_child_learner_id', (int) $child_id );
		}
		return $ok;
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Static API.
	}
}
