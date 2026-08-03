<?php
/**
 * Demo tutor roster — sourced from pages-to-review prototype data.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_switch_theme', 'bi_ensure_live_tutor_cpt_on_theme_switch' );
/**
 * Seed tutors CPT when theme activates with companion present.
 */
function bi_ensure_live_tutor_cpt_on_theme_switch() {
	if ( function_exists( 'bi_ensure_live_tutor_cpt' ) ) {
		bi_ensure_live_tutor_cpt();
	}
}

/**
 * Marquee trust chips for the home band.
 *
 * @return array<int, array{t:string,e:string}>
 */
function bi_get_marquee_items() {
    return [
        [ 't' => 'CAPS Curriculum', 'e' => '🇿🇦' ],
        [ 't' => 'IEB Curriculum', 'e' => '⭐' ],
        [ 't' => 'Cambridge AS & A', 'e' => '🇬🇧' ],
        [ 't' => 'SACE-Registered Tutors', 'e' => '🛡️' ],
        [ 't' => 'ID & Police Clearance', 'e' => '🔒' ],
        [ 't' => 'Pure Maths Specialists', 'e' => '📐' ],
        [ 't' => 'Online & In-Person', 'e' => '💻' ],
        [ 't' => 'NextGen100 Guarantee', 'e' => '✓' ],
    ];
}

/**
 * Subject tracks for home grid.
 *
 * @return array<int, array{name:string,desc:string}>
 */
function bi_get_subject_tracks() {
    return [
        [ 'name' => 'Mathematics', 'desc' => 'CAPS & IEB Pure Maths (Gr 1–12) plus Matric exam prep.' ],
        [ 'name' => 'English HL', 'desc' => 'Essays, literature study and comprehension coaching.' ],
        [ 'name' => 'Physical Sciences', 'desc' => 'Physics and chemistry for CAPS, IEB and Cambridge.' ],
        [ 'name' => 'Coding & Python', 'desc' => 'IT/CAT projects, Scratch and Python foundations.' ],
        [ 'name' => 'Accounting', 'desc' => 'Bookkeeping, ledgers and financial statements.' ],
        [ 'name' => 'Life Sciences', 'desc' => 'Biology, cellular structures and exam technique.' ],
        [ 'name' => 'Economics', 'desc' => 'Macro, micro and market systems explained clearly.' ],
        [ 'name' => 'Statistics', 'desc' => 'Tertiary stats, hypothesis testing and data analysis.' ],
    ];
}

/**
 * Human-readable subject label from URL slug.
 *
 * @param string $slug Sanitized subject slug.
 * @return string
 */
function bi_subject_label_from_slug( $slug ) {
    foreach ( bi_get_subject_tracks() as $subject ) {
        if ( sanitize_title( $subject['name'] ) === $slug ) {
            return $subject['name'];
        }
    }
    return ucwords( str_replace( '-', ' ', $slug ) );
}

/**
 * Filter demo/CPT tutors by hero-search subject slug.
 *
 * @param array<int, array<string, mixed>> $tutors Tutor rows.
 * @param string                           $subject_slug Subject slug.
 * @return array<int, array<string, mixed>>
 */
function bi_filter_tutors_by_subject( $tutors, $subject_slug ) {
    if ( ! $subject_slug ) {
        return $tutors;
    }
    return array_values(
        array_filter(
            $tutors,
            static function ( $tutor ) use ( $subject_slug ) {
                foreach ( (array) ( $tutor['subjects'] ?? [] ) as $subject ) {
                    if ( sanitize_title( (string) $subject ) === $subject_slug ) {
                        return true;
                    }
                }
                return false;
            }
        )
    );
}

/**
 * Whether placeholder/demo roster content is allowed (default: off for production).
 *
 * Auto-on for Docker / local demo seed stacks so empty CPT never blanks the UI.
 *
 * @return bool
 */
function bi_demo_content_enabled() {
	$default = false;
	if ( class_exists( 'NGC_Platform_Demo' ) && method_exists( 'NGC_Platform_Demo', 'is_enabled' ) && NGC_Platform_Demo::is_enabled() ) {
		$default = true;
	} elseif ( class_exists( 'NGC_Tutor_Seeder' ) && method_exists( 'NGC_Tutor_Seeder', 'demo_seed_allowed' ) && NGC_Tutor_Seeder::demo_seed_allowed() ) {
		$default = true;
	} elseif ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
		$default = true;
	}
	return (bool) apply_filters( 'bi_demo_content_enabled', $default );
}

/**
 * Merge static demo roster rows with live CPT tutors of the same name.
 *
 * Keeps booking/view CTAs working when the UI falls back to demo data.
 *
 * @param array<int, array<string, mixed>> $demo Demo rows.
 * @return array<int, array<string, mixed>>
 */
function bi_hydrate_demo_tutors_with_live( $demo ) {
	if ( empty( $demo ) || ! is_array( $demo ) ) {
		return [];
	}
	$live = [];
	if ( function_exists( 'bi_get_live_tutors' ) ) {
		// Avoid recursion: pull CPT only (skip static demo) when possible.
		if ( post_type_exists( 'tutors' ) ) {
			$q = new WP_Query(
				[
					'post_type'      => 'tutors',
					'post_status'    => 'publish',
					'posts_per_page' => 48,
					'no_found_rows'  => true,
					'orderby'        => 'title',
					'order'          => 'ASC',
				]
			);
			foreach ( $q->posts as $post ) {
				$live[] = bi_format_tutor_post( $post );
			}
			wp_reset_postdata();
		}
	}
	if ( empty( $live ) ) {
		return array_values( $demo );
	}

	$by_name = [];
	foreach ( $live as $row ) {
		$key = strtolower( trim( (string) ( $row['name'] ?? '' ) ) );
		if ( $key ) {
			$by_name[ $key ] = $row;
		}
	}

	$out = [];
	foreach ( $demo as $row ) {
		$key = strtolower( trim( (string) ( $row['name'] ?? '' ) ) );
		if ( $key && isset( $by_name[ $key ] ) ) {
			$merged           = array_merge( $row, $by_name[ $key ] );
			$merged['isDemo'] = ! empty( $by_name[ $key ]['isDemo'] ) || bi_tutor_post_is_demo( (int) ( $by_name[ $key ]['postId'] ?? 0 ) );
			$out[]            = $merged;
		} else {
			$row['isDemo'] = true;
			if ( empty( $row['permalink'] ) ) {
				$row['permalink'] = add_query_arg( 'preferred_tutor', rawurlencode( (string) ( $row['name'] ?? '' ) ), home_url( '/find-a-tutor/' ) );
				$row['url']       = $row['permalink'];
			}
			$out[] = $row;
		}
	}
	return $out;
}

/**
 * Demo tutors when CPT / REST are unavailable — disabled unless explicitly enabled.
 *
 * @param int $limit Max tutors.
 * @return array<int, array<string, mixed>>
 */
function bi_get_demo_tutors( $limit = 10 ) {
	if ( ! bi_demo_content_enabled() ) {
		return [];
	}

	$all = [
		[
			'postId'     => 0,
			'name'       => 'Karabo Molefe',
			'hourlyRate' => 500,
			'rate'       => 500,
			'rating'     => 4.95,
			'reviews'    => 42,
			'groupType'  => 'both',
			'imageUrl'   => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=600&h=600',
			'avatar'     => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=600&h=600',
			'degree'     => 'B.Sc (Hons) Theoretical Physics · UCT',
			'bio'        => 'I break difficult calculus and mechanics into simple building blocks learners find intuitive.',
			'subjects'   => [ 'Mathematics', 'Physical Sciences', 'Statistics' ],
			'grades'     => [ 'Grade 10', 'Grade 11', 'Grade 12' ],
			'province'   => 'Western Cape',
			'location'   => 'Western Cape',
			'formats'    => [ 'Online', 'In-person' ],
			'vetted'     => true,
			'available'  => true,
			'matchScore' => 99,
			'permalink'  => home_url( '/find-a-tutor/' ),
			'url'        => home_url( '/find-a-tutor/' ),
			'isDemo'     => true,
		],
		[
			'postId'     => 0,
			'name'       => 'Lindiwe Nkosi',
			'hourlyRate' => 320,
			'rate'       => 320,
			'rating'     => 5.0,
			'reviews'    => 156,
			'groupType'  => 'both',
			'imageUrl'   => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=600&h=600',
			'avatar'     => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=600&h=600',
			'degree'     => 'B.Sc Applied Maths & Comp Sci · Wits',
			'bio'        => 'Interactive science tutoring combined with coding fundamentals for Grade 8–12.',
			'subjects'   => [ 'Physical Sciences', 'Python', 'Mathematics' ],
			'grades'     => [ 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12' ],
			'province'   => 'Gauteng',
			'location'   => 'Gauteng',
			'formats'    => [ 'Online' ],
			'vetted'     => true,
			'available'  => true,
			'matchScore' => 99,
			'permalink'  => home_url( '/find-a-tutor/' ),
			'url'        => home_url( '/find-a-tutor/' ),
			'isDemo'     => true,
		],
		[
			'postId'     => 0,
			'name'       => 'Johan van der Merwe',
			'hourlyRate' => 300,
			'rate'       => 300,
			'rating'     => 4.8,
			'reviews'    => 67,
			'groupType'  => 'personal',
			'imageUrl'   => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&q=80&w=600&h=600',
			'avatar'     => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&q=80&w=600&h=600',
			'degree'     => 'B.A. English & Economics · Stellenbosch',
			'bio'        => 'IEB educator specialising in essay planning, grammar and confidence coaching.',
			'subjects'   => [ 'English HL', 'Economics', 'History' ],
			'grades'     => [ 'Grade 10', 'Grade 11', 'Grade 12' ],
			'province'   => 'Western Cape',
			'location'   => 'Western Cape',
			'formats'    => [ 'In-person' ],
			'vetted'     => true,
			'available'  => true,
			'matchScore' => 96,
			'permalink'  => home_url( '/find-a-tutor/' ),
			'url'        => home_url( '/find-a-tutor/' ),
			'isDemo'     => true,
		],
		[
			'postId'     => 0,
			'name'       => 'Priya Govender',
			'hourlyRate' => 320,
			'rate'       => 320,
			'rating'     => 4.9,
			'reviews'    => 88,
			'groupType'  => 'online',
			'imageUrl'   => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=600&h=600',
			'avatar'     => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=600&h=600',
			'degree'     => 'B.Com Accounting · UKZN',
			'bio'        => 'Teaching bookkeeping with visual spreadsheet templates learners can reuse.',
			'subjects'   => [ 'Accounting', 'Business Studies', 'Afrikaans FAL' ],
			'grades'     => [ 'Grade 10', 'Grade 11', 'Grade 12' ],
			'province'   => 'KwaZulu-Natal',
			'location'   => 'KwaZulu-Natal',
			'formats'    => [ 'Online' ],
			'vetted'     => true,
			'available'  => true,
			'matchScore' => 98,
			'permalink'  => home_url( '/find-a-tutor/' ),
			'url'        => home_url( '/find-a-tutor/' ),
			'isDemo'     => true,
		],
		[
			'postId'     => 0,
			'name'       => 'Thabo Ndlovu',
			'hourlyRate' => 400,
			'rate'       => 400,
			'rating'     => 4.9,
			'reviews'    => 112,
			'groupType'  => 'both',
			'imageUrl'   => 'https://images.unsplash.com/photo-1531384441138-2736e62e0919?auto=format&fit=crop&q=80&w=600&h=600',
			'avatar'     => 'https://images.unsplash.com/photo-1531384441138-2736e62e0919?auto=format&fit=crop&q=80&w=600&h=600',
			'degree'     => 'B.Sc Mathematics · Wits',
			'bio'        => 'Six years of Matric maths coaching focused on distinctions and exam technique.',
			'subjects'   => [ 'Mathematics', 'Statistics', 'Economics' ],
			'grades'     => [ 'Grade 10', 'Grade 11', 'Grade 12' ],
			'province'   => 'Gauteng',
			'location'   => 'Gauteng',
			'formats'    => [ 'Online', 'In-person' ],
			'vetted'     => true,
			'available'  => true,
			'matchScore' => 98,
			'permalink'  => home_url( '/find-a-tutor/' ),
			'url'        => home_url( '/find-a-tutor/' ),
			'isDemo'     => true,
		],
		[
			'postId'     => 0,
			'name'       => 'Sarah Pretorius',
			'hourlyRate' => 450,
			'rate'       => 450,
			'rating'     => 5.0,
			'reviews'    => 89,
			'groupType'  => 'online',
			'imageUrl'   => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&q=80&w=600&h=600',
			'avatar'     => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&q=80&w=600&h=600',
			'degree'     => 'B.Sc Physics · Cambridge',
			'bio'        => 'Physics made intuitive — I teach the why behind every formula so it sticks.',
			'subjects'   => [ 'Physical Sciences', 'Mathematics', 'Coding' ],
			'grades'     => [ 'Grade 11', 'Grade 12' ],
			'province'   => 'Gauteng',
			'location'   => 'Gauteng',
			'formats'    => [ 'Online' ],
			'vetted'     => true,
			'available'  => true,
			'matchScore' => 99,
			'permalink'  => home_url( '/find-a-tutor/' ),
			'url'        => home_url( '/find-a-tutor/' ),
			'isDemo'     => true,
		],
	];

	$sliced = array_slice( $all, 0, max( 1, (int) $limit ) );
	return bi_hydrate_demo_tutors_with_live( $sliced );
}

/**
 * Resolve tutor meta with legacy + NGC key fallbacks.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key suffix (e.g. hourly_rate).
 * @param mixed  $default Default value.
 * @return mixed
 */
function bi_tutor_meta( $post_id, $key, $default = '' ) {
	$keys = [
		'hourly_rate' => [ 'tutor_rate', '_ngc_hourly_rate', 'ngc_hourly_rate' ],
		'rating'      => [ 'tutor_average_rating', '_ngc_rating', 'ngc_rating' ],
		'reviews'     => [ 'tutor_review_count', '_ngc_reviews', 'ngc_reviews' ],
		'vetted'      => [ 'tutor_vetted', '_ngc_vetted', 'ngc_vetted', 'vetted' ],
		'available'   => [ 'tutor_available', 'available', '_ngc_available' ],
		'degree'      => [ 'tutor_degree', '_ngc_degree', 'ngc_degree' ],
		'mode'        => [ 'tutor_mode', '_ngc_mode', 'ngc_mode' ],
	];
	$candidates = $keys[ $key ] ?? [ $key ];
	foreach ( $candidates as $meta_key ) {
		$val = get_post_meta( $post_id, $meta_key, true );
		if ( '' !== $val && null !== $val && false !== $val ) {
			return $val;
		}
	}
	return $default;
}

/**
 * Format a tutors CPT post for carousel cards and marketplace UI.
 *
 * @param WP_Post $post Tutor post.
 * @return array<string, mixed>
 */
function bi_format_tutor_post( $post ) {
	$id = $post->ID;
	$subjects = wp_get_post_terms( $id, 'subject', [ 'fields' => 'names' ] );
	$province = wp_get_post_terms( $id, 'province', [ 'fields' => 'names' ] );
	$grades   = wp_get_post_terms( $id, 'grade', [ 'fields' => 'names' ] );
	$formats  = taxonomy_exists( 'learning_format' )
		? wp_get_post_terms( $id, 'learning_format', [ 'fields' => 'names' ] )
		: [];

	return [
		'postId'     => $id,
		'name'       => $post->post_title,
		'avatar'     => get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: (string) get_post_meta( $id, 'tutor_image_url', true ),
		'imageUrl'   => get_the_post_thumbnail_url( $id, 'medium' ) ?: (string) get_post_meta( $id, 'tutor_image_url', true ),
		'rating'     => (float) bi_tutor_meta( $id, 'rating', 4.8 ),
		'reviews'    => (int) bi_tutor_meta( $id, 'reviews', 0 ),
		'hourlyRate' => (int) bi_tutor_meta( $id, 'hourly_rate', 320 ),
		'rate'       => (int) bi_tutor_meta( $id, 'hourly_rate', 320 ),
		'groupType'  => bi_tutor_meta( $id, 'mode', 'both' ),
		'degree'     => (string) bi_tutor_meta( $id, 'degree', '' ),
		'bio'        => wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ),
		'subjects'   => is_wp_error( $subjects ) ? [] : (array) $subjects,
		'grades'     => is_wp_error( $grades ) ? [] : (array) $grades,
		'province'   => ( is_wp_error( $province ) || empty( $province ) ) ? '' : $province[0],
		'location'   => ( is_wp_error( $province ) || empty( $province ) ) ? '' : $province[0],
		'formats'    => is_wp_error( $formats ) ? [] : (array) $formats,
		'vetted'     => (bool) bi_tutor_meta( $id, 'vetted', false ),
		'available'  => (bool) bi_tutor_meta( $id, 'available', true ),
		'matchScore' => (int) min( 99, max( 72, round( ( (float) bi_tutor_meta( $id, 'rating', 4.8 ) / 5 ) * 100 ) ) ),
		'permalink'  => get_permalink( $id ),
		'url'        => get_permalink( $id ),
	];
}

/**
 * Booking URL that carries a tutor (and optional slot) into the workflow.
 *
 * Logged-out parents land on the find-a-tutor intake; the drawer swaps in
 * parent-checkout for signed-in users.
 *
 * @param int                   $tutor_id Tutors CPT ID.
 * @param array<string, string> $slot     Optional date/start/end/subject/delivery.
 * @return string
 */
function bi_tutor_booking_url( $tutor_id, $slot = [] ) {
	$tutor_id = (int) $tutor_id;
	if ( $tutor_id <= 0 ) {
		return home_url( '/find-a-tutor/' );
	}

	$args = [ 'ngc_tutor_id' => $tutor_id ];
	$map  = [
		'date'     => 'ngc_slot_date',
		'start'    => 'ngc_slot_start',
		'end'      => 'ngc_slot_end',
		'subject'  => 'ngc_subject',
		'delivery' => 'ngc_delivery_mode',
	];
	foreach ( $map as $key => $arg ) {
		if ( ! empty( $slot[ $key ] ) ) {
			$args[ $arg ] = sanitize_text_field( (string) $slot[ $key ] );
		}
	}

	return add_query_arg( $args, home_url( '/find-a-tutor/' ) );
}

/**
 * Tutor + slot the visitor asked to book, read from booking query args.
 *
 * @return array<string, mixed> Empty array when no valid tutor was requested.
 */
function bi_get_requested_tutor_context() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$tutor_id = isset( $_GET['ngc_tutor_id'] ) ? absint( wp_unslash( $_GET['ngc_tutor_id'] ) ) : 0;
	$name_q   = isset( $_GET['preferred_tutor'] ) ? sanitize_text_field( wp_unslash( $_GET['preferred_tutor'] ) ) : '';

	$post = null;
	if ( $tutor_id ) {
		$post = get_post( $tutor_id );
	}
	if ( ( ! $post || 'tutors' !== $post->post_type || 'publish' !== $post->post_status ) && $name_q && post_type_exists( 'tutors' ) ) {
		$found = get_posts(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'publish',
				'title'          => $name_q,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			]
		);
		// WP_Query title exact match is unreliable across versions — scan.
		if ( empty( $found ) ) {
			$found = get_posts(
				[
					'post_type'      => 'tutors',
					'post_status'    => 'publish',
					's'              => $name_q,
					'posts_per_page' => 12,
					'no_found_rows'  => true,
				]
			);
		}
		foreach ( $found as $candidate ) {
			if ( strcasecmp( $candidate->post_title, $name_q ) === 0 ) {
				$post     = $candidate;
				$tutor_id = (int) $candidate->ID;
				break;
			}
		}
	}

	if ( ! $post || 'tutors' !== $post->post_type || 'publish' !== $post->post_status ) {
		if ( $name_q ) {
			$slot = [];
			foreach (
				[
					'date'     => 'ngc_slot_date',
					'start'    => 'ngc_slot_start',
					'end'      => 'ngc_slot_end',
					'subject'  => 'ngc_subject',
					'delivery' => 'ngc_delivery_mode',
				] as $key => $arg
			) {
				if ( ! empty( $_GET[ $arg ] ) ) {
					$slot[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $arg ] ) );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return [
				'id'        => 0,
				'name'      => $name_q,
				'permalink' => home_url( '/find-a-tutor/' ),
				'slot'      => $slot,
			];
		}
		return [];
	}

	$slot = [];
	foreach (
		[
			'date'     => 'ngc_slot_date',
			'tart'    => 'ngc_slot_start',
			'send'      => 'ngc_slot_end',
			'subject'  => 'ngc_subject',
			'delivery' => 'ngc_delivery_mode',
		] as $key => $arg
	) {
		if ( ! empty( $_GET[ $arg ] ) ) {
			$slot[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $arg ] ) );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	return [
		'id'        => $tutor_id,
		'name'      => $post->post_title,
		'permalink' => (string) get_permalink( $post ),
		'slot'      => $slot,
	];
}

/**
 * Human-readable slot summary for intake notes and drawer copy.
 *
 * @param array<string, string> $slot Slot parts.
 * @return string
 */
function bi_format_tutor_slot_label( $slot ) {
	if ( empty( $slot ) ) {
		return '';
	}
	$bits = [];
	if ( ! empty( $slot['date'] ) ) {
		$bits[] = $slot['date'];
	}
	if ( ! empty( $slot['start'] ) ) {
		$bits[] = empty( $slot['end'] ) ? $slot['start'] : $slot['start'] . '–' . $slot['end'];
	}
	if ( ! empty( $slot['delivery'] ) ) {
		$bits[] = ucfirst( (string) $slot['delivery'] );
	}
	return implode( ' · ', $bits );
}

/**
 * Ensure published tutors CPT exists (no auto-seed).
 *
 * @return bool
 */
function bi_ensure_live_tutor_cpt() {
	if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
		NGC_Tutor_Cpt_Source::ensure_showcase_tutor();
	}
	return bi_count_published_tutors() > 0 && ! empty( bi_get_live_tutors( 1 ) );
}

/**
 * Exclude companion demo-seed tutors from public listings.
 *
 * When demo content is allowed (local / NGC_ALLOW_DEMO_SEED), return null so
 * seeded CPT tutors remain visible instead of blanking the marketplace.
 *
 * @return array<string, mixed>|null
 */
function bi_tutor_exclude_demo_meta_query() {
	if ( bi_demo_content_enabled() ) {
		return null;
	}
	return [
		'relation' => 'OR',
		[
			'key'     => 'ngc_demo_seed',
			'compare' => 'NOT EXISTS',
		],
		[
			'key'     => 'ngc_demo_seed',
			'value'   => '1',
			'compare' => '!=',
		],
	];
}

/**
 * @return bool
 */
function bi_tutor_post_is_demo( $post_id ) {
	if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
		return NGC_Tutor_Cpt_Source::is_demo_tutor( $post_id );
	}
	return (bool) get_post_meta( (int) $post_id, 'ngc_demo_seed', true );
}

/**
 * @return int
 */
function bi_count_published_tutors() {
	if ( ! post_type_exists( 'tutors' ) ) {
		return 0;
	}
	$counts = wp_count_posts( 'tutors' );
	return isset( $counts->publish ) ? (int) $counts->publish : 0;
}

/**
 * Live tutors from CPT — preferred data source when plugin + posts exist.
 *
 * @param int $limit Max tutors.
 * @return array<int, array<string, mixed>>
 */
function bi_get_live_tutors( $limit = 6 ) {
	$limit = max( 1, (int) $limit );
	if ( ! post_type_exists( 'tutors' ) ) {
		return bi_get_demo_tutors( $limit );
	}

	$base_args = [
		'post_type'      => 'tutors',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'no_found_rows'  => true,
	];
	$demo_filter = bi_tutor_exclude_demo_meta_query();
	if ( $demo_filter ) {
		$base_args['meta_query'] = [ $demo_filter ];
	}

	$query = new WP_Query(
		array_merge(
			$base_args,
			[
				'orderby'  => 'meta_value_num',
				'meta_key' => 'tutor_average_rating',
				'order'    => 'DESC',
			]
		)
	);

	if ( ! $query->have_posts() ) {
		$query = new WP_Query(
			array_merge(
				$base_args,
				[
					'orderby' => 'date',
					'order'   => 'DESC',
				]
			)
		);
	}

	$out          = [];
	$allow_demo   = bi_demo_content_enabled();
	foreach ( $query->posts as $post ) {
		if ( ! $allow_demo && bi_tutor_post_is_demo( $post->ID ) ) {
			continue;
		}
		$row           = bi_format_tutor_post( $post );
		$row['isDemo'] = bi_tutor_post_is_demo( $post->ID );
		$out[]         = $row;
	}
	wp_reset_postdata();

	if ( empty( $out ) ) {
		$out = bi_get_demo_tutors( $limit );
	}

	return apply_filters( 'bi_live_tutors', $out, $limit );
}

/**
 * Tutors for carousel: verified CPT only (no demo roster).
 *
 * @param int $limit Max items.
 * @return array<int, array<string, mixed>>
 */
function bi_get_carousel_tutors( $limit = 10 ) {
	$limit = max( 1, (int) $limit );

	$live = bi_get_live_tutors( $limit );
	if ( ! empty( $live ) ) {
		return $live;
	}

	$demo = bi_get_demo_tutors( $limit );
	return apply_filters( 'bi_filter_carousel_tutors', $demo, $limit );
}

/**
 * Empty-state markup when no verified tutors are published yet.
 */
function bi_render_tutors_empty_state() {
	$find = home_url( '/find-a-tutor' );
	$apply = home_url( '/become-a-tutor' );
	?>
	<section class="ngi-section ngi-alt ngi-section--tutors-empty" id="tutors">
		<div class="ngi-wrap">
			<div class="ngi-section-head ngi-reveal">
				<div class="ngi-eyebrow"><?php esc_html_e( 'Featured tutors', 'beyondinfinity' ); ?></div>
				<h2 class="ngi-heading"><?php esc_html_e( 'Verified educators joining every week.', 'beyondinfinity' ); ?></h2>
				<p class="ngi-subtitle"><?php esc_html_e( 'We are onboarding SACE-registered tutors across CAPS, IEB and Cambridge. Browse subjects or apply to teach with NextGen Tutors.', 'beyondinfinity' ); ?></p>
			</div>
			<p class="ngi-reveal" style="text-align:center;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
				<a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $find ); ?>"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
				<a class="ngi-btn ngi-btn-ghost" href="<?php echo esc_url( $apply ); ?>"><?php esc_html_e( 'Become a Tutor', 'beyondinfinity' ); ?></a>
			</p>
		</div>
	</section>
	<?php
}

/**
 * Testimonials from pages-to-review.
 *
 * @return array<int, array{author:string,role:string,quote:string,stars:int}>
 */
function bi_get_featured_testimonials() {
    $live = apply_filters( 'bi_filter_live_testimonials', [] );
    if ( ! empty( $live ) ) {
        return $live;
    }
    if ( ! bi_demo_content_enabled() ) {
        return [];
    }

    $rows = [
        [
            'author' => 'Naledi Maduna',
            'role'   => 'IEB Matric · Parent of Lethabo',
            'quote'  => 'Pure Mathematics went from 54% to a distinction in final IEB Matric — recorded sessions were a lifesaver for revision.',
            'stars'  => 5,
            'avatar' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=160&h=160&q=80',
        ],
        [
            'author' => 'Hennie Swart',
            'role'   => 'Parent · Johannesburg',
            'quote'  => 'ID and police-clearance vetting meant we could trust our tutor completely. NextGen made finding safe at-home support effortless.',
            'stars'  => 5,
            'avatar' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=160&h=160&q=80',
        ],
        [
            'author' => 'Brian Woods',
            'role'   => 'Grade 11 Learner',
            'quote'  => 'I used to think tutoring meant you were behind. Now every session is genuinely interesting and my marks show it.',
            'stars'  => 5,
            'avatar' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=160&h=160&q=80',
        ],
        [
            'author' => 'Nomsa Dlamini',
            'role'   => 'Parent · Gauteng',
            'quote'  => 'We finally found a tutor who explained maths in a way my daughter understood. Her confidence returned within weeks.',
            'stars'  => 5,
            'avatar' => 'https://images.unsplash.com/photo-1589156280159-27698a01f33d?auto=format&fit=crop&w=160&h=160&q=80',
        ],
        [
            'author' => 'Thandiwe Mokoena',
            'role'   => 'Parent · Durban',
            'quote'  => 'CAPS-aligned sessions and WhatsApp follow-ups kept my son accountable. We saw Physical Sciences climb two symbols in a term.',
            'stars'  => 5,
            'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=160&h=160&q=80',
        ],
        [
            'author' => 'Johan van der Berg',
            'role'   => 'Parent · Cape Town',
            'quote'  => 'Transparent Rand pricing and SACE-verified tutors made switching from agency tutoring an easy decision for our family.',
            'stars'  => 5,
            'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=160&h=160&q=80',
        ],
    ];

    return apply_filters( 'bi_filter_featured_testimonials', $rows, false );
}

/**
 * Whether testimonials are static demo data (no live CPT feed yet).
 */
function bi_testimonials_use_demo_data() {
  $live = apply_filters( 'bi_filter_live_testimonials', [] );
    return empty( $live ) && bi_demo_content_enabled();
}

function bi_testimonials_source_label() {
    return bi_testimonials_use_demo_data()
        ? __( 'Representative parent stories shown for preview.', 'beyondinfinity' )
        : __( 'Verified parent and learner stories from across South Africa.', 'beyondinfinity' );
}

/**
 * Featured blog posts (static until WP posts power the blog).
 *
 * @return array<int, array{title:string,category:string,meta:string,image:string}>
 */
function bi_get_blog_posts() {
    return [
        [
            'title'    => 'How to Master Grade 12 Mathematics Before Matric',
            'category' => 'Exam Prep',
            'meta'     => 'NextGen Editorial · 5 min read · June 2026',
            'image'    => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&q=80&w=1200',
        ],
        [
            'title'    => 'NSC Exam Countdown: Your 30-Day Plan',
            'category' => 'Exam Prep',
            'meta'     => '8 min · May 2026',
            'image'    => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&q=80&w=400',
        ],
        [
            'title'    => 'Understanding Your Child\'s IEB Report Card',
            'category' => 'Parent Resources',
            'meta'     => '6 min · May 2026',
            'image'    => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&q=80&w=400',
        ],
        [
            'title'    => 'The Pomodoro Technique for SA Students',
            'category' => 'Study Tips',
            'meta'     => '4 min · May 2026',
            'image'    => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&q=80&w=400',
        ],
    ];
}

/**
 * Blog category cards.
 *
 * @return array<int, array{icon:string,title:string,desc:string}>
 */
function bi_get_blog_categories() {
    return [
        [ 'icon' => '📝', 'title' => 'Study Tips', 'desc' => 'Revision techniques, time management and note-taking.' ],
        [ 'icon' => '🎓', 'title' => 'Exam Prep', 'desc' => 'NSC/IEB guides, past papers and countdown planners.' ],
        [ 'icon' => '🔢', 'title' => 'Mathematics', 'desc' => 'CAPS & IEB Maths from anxiety to distinction.' ],
        [ 'icon' => '🧪', 'title' => 'Sciences', 'desc' => 'Physical Sciences and Life Sciences exam technique.' ],
        [ 'icon' => '👪', 'title' => 'Parent Resources', 'desc' => 'Supporting learners and choosing tutoring.' ],
        [ 'icon' => '🏆', 'title' => 'Success Stories', 'desc' => 'Real South African student journeys.' ],
    ];
}
