<?php
/**
 * Bridges content-pack plugins (Command Center, Completion Suite) with Companion workflows.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unifies RTM messaging, completion-suite REST events, and catalog workflow imports.
 */
class NGC_Content_Pack_Bridge {

	/**
	 * Map completion-suite / v2 trigger slugs → companion dispatch events.
	 *
	 * @return array<string, string>
	 */
	public static function event_aliases() {
		return apply_filters(
			'ngc_content_pack_event_aliases',
			[
				'find_a_tutor_form_submitted'     => 'find_tutor.submitted',
				'progress_report_created'         => 'progress_report.submitted',
				'lesson_note_created'             => 'lesson_note.created',
				'lesson_status_completed'         => 'lesson.completed',
				'weakness_identified'             => 'resource.recommended',
				'lesson_confirmed_paid'           => 'payout.calculated',
				'woocommerce_order_status_completed' => 'payment.received',
			]
		);
	}

	/**
	 * Map theme RTM room slugs → Command Center room post_name when plugin active.
	 *
	 * @return array<string, string>
	 */
	public static function rtm_room_map() {
		return apply_filters(
			'ngc_rtm_room_map',
			[
				'staff'              => 'staff-room',
				'admin'              => 'admin-room',
				'tutor-support'      => 'tutor-support-room',
				'booking-issues'     => 'booking-room',
				'payment-issues'     => 'finance-room',
				'escalated-support'  => 'support-escalation-room',
				'lesson-issues'      => 'tutor-support-room',
			]
		);
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'bi_workflow_rtm_queued', [ __CLASS__, 'mirror_rtm_to_command_center' ], 10, 2 );
		add_action( 'wp_insert_post', [ __CLASS__, 'on_operational_post' ], 20, 3 );
		add_filter( 'ngc_integrate_event_bindings', [ __CLASS__, 'extend_integrate_bindings' ] );
	}

	/**
	 * @param array<string, mixed> $bindings Existing bindings.
	 * @return array<string, mixed>
	 */
	public static function extend_integrate_bindings( $bindings ) {
		$extra = [
			'progress_report.submitted' => [ 'type' => 'dispatch', 'event' => 'progress_report.submitted' ],
			'lesson_note.created'       => [ 'type' => 'dispatch', 'event' => 'lesson_note.created' ],
			'resource.recommended'      => [ 'type' => 'dispatch', 'event' => 'resource.recommended' ],
			'lesson_confirmed_paid'     => [ 'type' => 'dispatch', 'event' => 'payout.calculated' ],
		];
		return array_merge( $bindings, $extra );
	}

	/**
	 * Mirror theme workflow RTM queue into Command Center ngt_rtm_message CPT.
	 *
	 * @param string $room    Room slug from workflow pack.
	 * @param string $message Message body.
	 */
	public static function mirror_rtm_to_command_center( $room, $message ) {
		if ( ! post_type_exists( 'ngt_rtm_room' ) || ! post_type_exists( 'ngt_rtm_message' ) ) {
			return;
		}
		$map    = self::rtm_room_map();
		$target = $map[ sanitize_key( (string) $room ) ] ?? sanitize_title( (string) $room );
		$posts  = get_posts(
			[
				'post_type'      => 'ngt_rtm_room',
				'name'           => $target,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);
		$room_id = ! empty( $posts[0] ) ? (int) $posts[0] : 0;
		if ( $room_id <= 0 ) {
			return;
		}
		wp_insert_post(
			[
				'post_type'    => 'ngt_rtm_message',
				'post_status'  => 'publish',
				'post_title'   => 'Workflow RTM — ' . current_time( 'mysql' ),
				'post_content' => wp_strip_all_tags( (string) $message ),
				'post_author'  => get_current_user_id() ?: 1,
				'meta_input'   => [
					'room_id' => $room_id,
					'source'  => 'companion_workflow_pack',
				],
			]
		);
	}

	/**
	 * Dispatch companion workflows when Completion Suite operational CPTs are created.
	 *
	 * @param int      $post_id Post ID.
	 * @param WP_Post  $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public static function on_operational_post( $post_id, $post, $update ) {
		if ( $update || wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status ) {
			return;
		}
		$map = [
			'ngt_report'   => 'progress_report.submitted',
			'ngt_note'     => 'lesson_note.created',
			'ngt_payout'   => 'payout.calculated',
			'ngt_resource' => 'resource.recommended',
		];
		if ( empty( $map[ $post->post_type ] ) ) {
			return;
		}
		NGC_Workflows::dispatch(
			$map[ $post->post_type ],
			[
				'post_id' => (string) $post_id,
				'title'   => $post->post_title,
				'student' => (string) get_post_meta( $post_id, 'ngt_student', true ),
				'tutor'   => (string) get_post_meta( $post_id, 'ngt_tutor', true ),
				'amount'  => (string) get_post_meta( $post_id, 'ngt_amount', true ),
			]
		);
	}

	/**
	 * @param WP_Post         $post     Post object.
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $creating Created flag.
	 * @deprecated Use on_operational_post().
	 */
	public static function on_progress_report( $post, $request, $creating ) {
		if ( ! $creating || 'ngt_report' !== $post->post_type ) {
			return;
		}
		NGC_Workflows::dispatch(
			'progress_report.submitted',
			[
				'report_id' => (string) $post->ID,
				'title'     => $post->post_title,
				'student'   => (string) get_post_meta( $post->ID, 'ngt_student', true ),
				'tutor'     => (string) get_post_meta( $post->ID, 'ngt_tutor', true ),
			]
		);
	}

	/**
	 * @param WP_Post         $post     Post object.
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $creating Created flag.
	 */
	public static function on_lesson_note( $post, $request, $creating ) {
		if ( ! $creating || 'ngt_note' !== $post->post_type ) {
			return;
		}
		NGC_Workflows::dispatch(
			'lesson_note.created',
			[
				'note_id' => (string) $post->ID,
				'title'   => $post->post_title,
				'student' => (string) get_post_meta( $post->ID, 'ngt_student', true ),
				'tutor'   => (string) get_post_meta( $post->ID, 'ngt_tutor', true ),
			]
		);
	}

	/**
	 * @param WP_Post         $post     Post object.
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $creating Created flag.
	 */
	public static function on_payout_created( $post, $request, $creating ) {
		if ( ! $creating || 'ngt_payout' !== $post->post_type ) {
			return;
		}
		NGC_Workflows::dispatch(
			'payout.calculated',
			[
				'payout_id' => (string) $post->ID,
				'title'     => $post->post_title,
				'amount'    => (string) get_post_meta( $post->ID, 'ngt_amount', true ),
				'tutor'     => (string) get_post_meta( $post->ID, 'ngt_tutor', true ),
			]
		);
	}

	/**
	 * @param WP_Post         $post     Post object.
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $creating Created flag.
	 */
	public static function on_resource_recommended( $post, $request, $creating ) {
		if ( ! $creating || 'ngt_resource' !== $post->post_type ) {
			return;
		}
		NGC_Workflows::dispatch(
			'resource.recommended',
			[
				'resource_id' => (string) $post->ID,
				'title'       => $post->post_title,
			]
		);
	}

	/**
	 * Import catalog JSON from integrate/catalog into spec store (idempotent).
	 *
	 * @return array{ok:bool,imported:int,errors:array<int,string>}
	 */
	public static function import_catalog_specs() {
		if ( ! class_exists( 'NGC_Workflow_Spec_Registry' ) ) {
			return [ 'ok' => false, 'imported' => 0, 'errors' => [ 'registry_missing' ] ];
		}
		return NGC_Workflow_Spec_Registry::import_from_catalog( true );
	}
}
