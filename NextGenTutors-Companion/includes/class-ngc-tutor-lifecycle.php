<?php
/**
 * Tutor application lifecycle — delegates workflows to orchestrator.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply, approve, reject, resubmit, suspend.
 */
class NGC_Tutor_Lifecycle {

	/**
	 * @param array<string, mixed> $data Application data.
	 * @return int|WP_Error Application ID.
	 */
	public static function apply( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'tutor_applications' );

		$user_id = isset( $data['user_id'] ) ? (int) $data['user_id'] : get_current_user_id();
		$row     = [
			'user_id'    => $user_id,
			'full_name'  => sanitize_text_field( $data['full_name'] ?? $data['name'] ?? '' ),
			'email'      => sanitize_email( $data['email'] ?? '' ),
			'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
			'subjects'   => sanitize_textarea_field( $data['subjects'] ?? $data['subject'] ?? '' ),
			'province'   => sanitize_text_field( $data['province'] ?? $data['area'] ?? '' ),
			'bio'        => sanitize_textarea_field( $data['bio'] ?? $data['experience'] ?? '' ),
			'status'     => 'pending',
			'created_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];

		// Live DBs may have a UNIQUE uuid column added by later migrations.
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'uuid'" );
		if ( ! empty( $col ) ) {
			$row['uuid'] = class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4();
		}

		$inserted = $wpdb->insert( $table, $row );
		if ( ! $inserted ) {
			return new WP_Error( 'ngc_apply_failed', __( 'Could not submit application.', 'nextgencompanion' ) );
		}

		$id = (int) $wpdb->insert_id;
		NGC_Workflow_Orchestrator::run(
			'TUTOR_REGISTERED',
			array_merge(
				$data,
				[
					'application_id' => $id,
					'user_id'        => $user_id,
					'email'          => $row['email'],
					'full_name'      => $row['full_name'],
					'phone'          => $row['phone'],
					'subjects'       => $row['subjects'],
					'province'       => $row['province'],
					'bio'            => $row['bio'],
				]
			)
		);

		return $id;
	}

	/**
	 * @param int $application_id Application ID.
	 * @param int $actor_id       Reviewer.
	 * @return true|WP_Error
	 */
	public static function approve( $application_id, $actor_id = 0 ) {
		global $wpdb;
		$app = self::get( $application_id );
		if ( ! $app ) {
			return new WP_Error( 'ngc_app_not_found', __( 'Application not found.', 'nextgencompanion' ) );
		}

		$table = NGC_Database::table( 'tutor_applications' );
		$wpdb->update(
			$table,
			[ 'status' => 'approved', 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => $application_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		$user_id = (int) $app->user_id;
		if ( ! $user_id && $app->email ) {
			$user = get_user_by( 'email', $app->email );
			$user_id = $user ? (int) $user->ID : 0;
		}

		NGC_Workflow_Orchestrator::run(
			'TUTOR_APPROVED',
			[
				'application_id' => $application_id,
				'user_id'        => $user_id,
				'email'          => $app->email,
				'full_name'      => $app->full_name,
				'phone'          => $app->phone,
				'subjects'       => $app->subjects,
				'province'       => $app->province,
				'bio'            => $app->bio,
				'actor_id'       => $actor_id,
			]
		);

		return true;
	}

	/**
	 * @param int    $application_id Application ID.
	 * @param string $notes          Rejection notes.
	 * @param int    $actor_id       Reviewer.
	 * @return true|WP_Error
	 */
	public static function reject( $application_id, $notes = '', $actor_id = 0 ) {
		global $wpdb;
		$app = self::get( $application_id );
		if ( ! $app ) {
			return new WP_Error( 'ngc_app_not_found', __( 'Application not found.', 'nextgencompanion' ) );
		}

		$table = NGC_Database::table( 'tutor_applications' );
		$wpdb->update(
			$table,
			[
				'status'       => 'rejected',
				'review_notes' => sanitize_textarea_field( $notes ),
				'updated_at'   => current_time( 'mysql', true ),
			],
			[ 'id' => $application_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		NGC_Workflow_Orchestrator::run(
			'TUTOR_REJECTED',
			[
				'application_id' => $application_id,
				'user_id'        => (int) $app->user_id,
				'email'          => $app->email,
				'full_name'      => $app->full_name,
				'review_notes'   => $notes,
				'allow_resubmit' => true,
				'actor_id'       => $actor_id,
			]
		);

		return true;
	}

	/**
	 * @param int                  $application_id Application ID.
	 * @param array<string, mixed> $data           Updated fields.
	 * @return true|WP_Error
	 */
	public static function resubmit( $application_id, $data ) {
		global $wpdb;
		$app = self::get( $application_id );
		if ( ! $app ) {
			return new WP_Error( 'ngc_app_not_found', __( 'Application not found.', 'nextgencompanion' ) );
		}

		$fields = [
			'full_name'  => sanitize_text_field( $data['full_name'] ?? $app->full_name ),
			'subjects'   => sanitize_textarea_field( $data['subjects'] ?? $app->subjects ),
			'province'   => sanitize_text_field( $data['province'] ?? $app->province ),
			'bio'        => sanitize_textarea_field( $data['bio'] ?? $app->bio ),
			'status'     => 'pending',
			'updated_at' => current_time( 'mysql', true ),
		];

		$table = NGC_Database::table( 'tutor_applications' );
		$wpdb->update( $table, $fields, [ 'id' => $application_id ] );

		NGC_Workflow_Orchestrator::run(
			'TUTOR_RESUBMITTED',
			array_merge(
				$data,
				[
					'application_id' => $application_id,
					'user_id'        => (int) $app->user_id,
					'email'          => $app->email,
					'full_name'      => $fields['full_name'],
					'subjects'       => $fields['subjects'],
					'province'       => $fields['province'],
					'bio'            => $fields['bio'],
				]
			)
		);

		return true;
	}

	/**
	 * @param int    $user_id Tutor user ID.
	 * @param string $reason  Reason.
	 * @param int    $actor_id Actor.
	 * @return true|WP_Error
	 */
	public static function suspend( $user_id, $reason = '', $actor_id = 0 ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'ngc_user_not_found', __( 'User not found.', 'nextgencompanion' ) );
		}

		update_user_meta( $user_id, 'ngc_tutor_verified', 0 );
		update_user_meta( $user_id, 'ngc_tutor_suspended', 1 );
		update_user_meta( $user_id, 'ngc_suspend_reason', sanitize_textarea_field( $reason ) );

		$post = NGC_Post_Types::get_tutor_post_by_user_id( $user_id );
		if ( $post ) {
			wp_update_post( [ 'ID' => $post->ID, 'post_status' => 'draft' ] );
		}

		NGC_Audit::log( 'tutor_suspended', 'user', $user_id, [ 'reason' => $reason ], $actor_id );
		return true;
	}

	/**
	 * @param int $application_id ID.
	 * @return object|null
	 */
	public static function get( $application_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'tutor_applications' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $application_id ) );
	}

	/**
	 * @param string $status Status filter.
	 * @param int    $limit  Limit.
	 * @return array<int, object>
	 */
	public static function list_applications( $status = 'pending', $limit = 50 ) {
		global $wpdb;
		$table = NGC_Database::table( 'tutor_applications' );
		if ( $status ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", $status, $limit ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ) );
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// REST and forms.
	}
}
