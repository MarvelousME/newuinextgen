<?php
/**
 * Canonical tutor matching source — Tutor CPT with legacy WP-user adapter.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for tutor discovery, scoring, and counts.
 */
class NGC_Tutor_Cpt_Source {

	/**
	 * @return int
	 */
	public static function count_total() {
		if ( ! post_type_exists( 'tutors' ) ) {
			return 0;
		}
		$counts = wp_count_posts( 'tutors' );
		return isset( $counts->publish ) ? (int) $counts->publish : 0;
	}

	/**
	 * @return int
	 */
	public static function count_demo() {
		if ( ! post_type_exists( 'tutors' ) ) {
			return 0;
		}
		$q = new WP_Query(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'ngc_demo_seed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'no_found_rows'  => false,
			]
		);
		return (int) $q->found_posts;
	}

	/**
	 * @return int
	 */
	public static function count_real() {
		return max( 0, self::count_total() - self::count_demo() );
	}

	/**
	 * Published tutor CPT posts for matching.
	 *
	 * @param int $limit Max posts.
	 * @return WP_Post[]
	 */
	public static function get_published_posts( $limit = 50 ) {
		if ( ! post_type_exists( 'tutors' ) ) {
			return [];
		}
		return get_posts(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, (int) $limit ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
	}

	/**
	 * Score tutors for legacy NGC_Matching (user_id keyed rows).
	 *
	 * @param string $subject  Subject.
	 * @param string $grade    Grade.
	 * @param string $province Province.
	 * @return array<int, array<string, mixed>>
	 */
	public static function score_for_legacy_matching( $subject, $grade, $province ) {
		$params  = [
			'subject'  => $subject,
			'grade'    => $grade,
			'province' => $province,
		];
		$matches = class_exists( 'NGC_Smart_Matching' ) ? NGC_Smart_Matching::run_match( $params ) : [];
		$rows    = [];

		foreach ( $matches as $row ) {
			$post_id  = (int) ( $row['id'] ?? $row['post_id'] ?? 0 );
			$user_id  = (int) get_post_meta( $post_id, '_ngc_linked_user_id', true );
			$rows[] = [
				'user_id'  => $user_id,
				'post_id'  => $post_id,
				'score'    => (float) ( $row['score'] ?? 0 ),
				'name'     => (string) ( $row['title'] ?? $row['name'] ?? '' ),
				'subject'   => $subject,
				'grade'     => $grade,
				'province'  => $province,
			];
		}

		if ( ! empty( $rows ) ) {
			return $rows;
		}

		return self::score_legacy_wp_users( $subject, $grade, $province );
	}

	/**
	 * Fallback: score WP users with tutor role when no CPT matches exist.
	 *
	 * @param string $subject  Subject.
	 * @param string $grade    Grade.
	 * @param string $province Province.
	 * @return array<int, array<string, mixed>>
	 */
	private static function score_legacy_wp_users( $subject, $grade, $province ) {
		$tutors = get_users( [ 'role' => 'tutor', 'number' => 50 ] );
		$rows   = [];
		foreach ( $tutors as $user ) {
			$score = 0.0;
			$subs  = (array) get_user_meta( $user->ID, 'ngc_subjects', true );
			if ( $subject && in_array( $subject, $subs, true ) ) {
				$score += 40;
			}
			$prov = (string) get_user_meta( $user->ID, 'ngc_province', true );
			if ( $province && $prov && stripos( $prov, $province ) !== false ) {
				$score += 25;
			}
			$grades = (array) get_user_meta( $user->ID, 'ngc_grades', true );
			if ( $grade && in_array( $grade, $grades, true ) ) {
				$score += 20;
			}
			$rows[] = [
				'user_id'  => (int) $user->ID,
				'post_id'  => 0,
				'score'    => $score,
				'name'     => $user->display_name,
				'subject'  => $subject,
				'grade'    => $grade,
				'province' => $province,
			];
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				return ( $b['score'] <=> $a['score'] );
			}
		);
		return $rows;
	}

	/**
	 * Sync WP-user tutors into Tutor CPT posts (repair/migration).
	 *
	 * @param bool $dry_run Preview only.
	 * @return array{created:int,updated:int,skipped:int,errors:int}
	 */
	public static function sync_user_tutors_to_cpt( $dry_run = false ) {
		$result = [ 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0 ];
		if ( ! post_type_exists( 'tutors' ) ) {
			return $result;
		}

		$users = get_users( [ 'role' => 'tutor', 'number' => 200 ] );
		foreach ( $users as $user ) {
			$linked = (int) get_user_meta( $user->ID, '_ngc_tutor_cpt_id', true );
			if ( $linked && get_post_status( $linked ) ) {
				++$result['skipped'];
				continue;
			}

			$existing = get_posts(
				[
					'post_type'      => 'tutors',
					'post_status'    => 'any',
					'meta_key'       => '_ngc_linked_user_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => (string) $user->ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			);

		if ( ! empty( $existing ) ) {
			if ( ! $dry_run ) {
				update_user_meta( $user->ID, '_ngc_tutor_cpt_id', (int) $existing[0] );
				self::link_integration_meta( (int) $existing[0], (int) $user->ID );
				++$result['updated'];
			} else {
				++$result['skipped'];
			}
			continue;
		}

			if ( $dry_run ) {
				++$result['created'];
				continue;
			}

			$post_id = wp_insert_post(
				[
					'post_type'    => 'tutors',
					'post_status'  => 'publish',
					'post_title'   => $user->display_name,
					'post_name'    => sanitize_title( $user->user_login ),
					'post_excerpt' => (string) get_user_meta( $user->ID, 'description', true ),
				],
				true
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				++$result['errors'];
				continue;
			}

			update_post_meta( $post_id, '_ngc_linked_user_id', (int) $user->ID );
			update_post_meta( $post_id, 'ngc_demo_seed', 0 );
			update_user_meta( $user->ID, '_ngc_tutor_cpt_id', (int) $post_id );

			$rate = (int) get_user_meta( $user->ID, 'ngc_hourly_rate', true );
			if ( $rate ) {
				update_post_meta( $post_id, 'tutor_rate', $rate );
				update_post_meta( $post_id, '_ngc_hourly_rate', $rate );
			}

			self::link_integration_meta( (int) $post_id, (int) $user->ID );

			++$result['created'];
		}

		if ( class_exists( 'NGC_Audit' ) && ! $dry_run && ( $result['created'] > 0 || $result['updated'] > 0 ) ) {
			NGC_Audit::log( 'tutor_cpt_sync', 'tutors', 0, $result );
		}

		return $result;
	}

	/**
	 * Copy integration IDs from WP user meta onto Tutor CPT post meta.
	 *
	 * @param int $post_id Tutor CPT post ID.
	 * @param int $user_id Linked WP user ID.
	 * @return array{amelia:int,masterstudy:int,fluentcrm:int}
	 */
	public static function link_integration_meta( $post_id, $user_id ) {
		$post_id = (int) $post_id;
		$user_id = (int) $user_id;
		$linked  = [
			'amelia'      => 0,
			'masterstudy' => 0,
			'fluentcrm'   => 0,
		];

		$amelia_id = (int) get_user_meta( $user_id, 'ngc_amelia_employee_id', true );
		if ( $amelia_id ) {
			update_post_meta( $post_id, '_ngc_amelia_employee_id', $amelia_id );
			$linked['amelia'] = $amelia_id;
		}

		$stm_id = (int) get_user_meta( $user_id, 'ngc_stm_instructor_id', true );
		if ( $stm_id ) {
			update_post_meta( $post_id, '_ngc_stm_instructor_id', $stm_id );
			$linked['masterstudy'] = $stm_id;
		}

		$crm_id = (int) get_user_meta( $user_id, 'ngc_fluentcrm_contact_id', true );
		if ( $crm_id ) {
			update_post_meta( $post_id, '_ngc_fluentcrm_contact_id', $crm_id );
			$linked['fluentcrm'] = $crm_id;
		}

		return $linked;
	}

	/**
	 * Provision Amelia + MasterStudy profiles for a tutor user when plugins are active.
	 *
	 * @param int  $user_id WP user ID.
	 * @param bool $dry_run Preview only.
	 * @return array<string, mixed>
	 */
	public static function provision_integrations_for_user( $user_id, $dry_run = false ) {
		$user_id = (int) $user_id;
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return [ 'ok' => false, 'error' => 'user_not_found' ];
		}

		$result = [ 'amelia' => null, 'masterstudy' => null, 'ok' => true ];
		if ( $dry_run ) {
			$result['dry_run'] = true;
			return $result;
		}

		if ( class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			$adapters = NGC_Workflow_Orchestrator::adapters();
			$payload  = [
				'user_id'    => $user_id,
				'email'      => $user->user_email,
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
				'subjects'   => get_user_meta( $user_id, 'ngc_subjects', true ),
				'bio'        => get_user_meta( $user_id, 'description', true ),
			];

			if ( isset( $adapters['amelia'] ) && $adapters['amelia']->is_available() ) {
				$result['amelia'] = $adapters['amelia']->create_or_update( 'create_employee', $payload );
			}
			if ( isset( $adapters['masterstudy'] ) && $adapters['masterstudy']->is_available() ) {
				$result['masterstudy'] = $adapters['masterstudy']->create_or_update( 'create_instructor', $payload );
			}
		}

		$post_id = (int) get_user_meta( $user_id, '_ngc_tutor_cpt_id', true );
		if ( $post_id ) {
			$result['meta_linked'] = self::link_integration_meta( $post_id, $user_id );
		}

		return $result;
	}

	/**
	 * Whether a tutor post is demo-seeded.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_demo_tutor( $post_id ) {
		if ( (bool) get_post_meta( (int) $post_id, 'ngc_showcase_tutor', true ) ) {
			return false;
		}
		return (bool) get_post_meta( (int) $post_id, 'ngc_demo_seed', true );
	}

	/**
	 * Promote one demo tutor to a real showcase tutor for local stacks (powers bi_get_live_tutors).
	 *
	 * @return array<string, mixed>
	 */
	public static function ensure_showcase_tutor() {
		if ( self::count_real() > 0 ) {
			return [ 'ok' => true, 'status' => 'exists', 'real' => self::count_real() ];
		}

		$posts = get_posts(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'meta_key'       => 'ngc_demo_seed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( empty( $posts[0] ) ) {
			$posts = self::get_published_posts( 1 );
		}
		if ( empty( $posts[0] ) ) {
			return [ 'ok' => false, 'reason' => 'no_tutors' ];
		}

		$post_id = (int) $posts[0]->ID;
		delete_post_meta( $post_id, 'ngc_demo_seed' );
		update_post_meta( $post_id, 'ngc_showcase_tutor', '1' );
		update_post_meta( $post_id, '_ngc_onboarded', gmdate( 'c' ) );

		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info( 'tutor_cpt', 'marketplace', 'Showcase tutor promoted from demo seed', [ 'post_id' => $post_id ] );
		}

		return [ 'ok' => true, 'status' => 'promoted', 'post_id' => $post_id ];
	}
}
