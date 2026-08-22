<?php
/**
 * Subjects CMS — CRUD for homepage tabs, marquee tracks, and subject landing pages.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores subject marketing content in option ngc_subjects_catalog.
 */
class NGC_Subjects_CMS {

	/** @var string */
	const OPTION_KEY = 'ngc_subjects_catalog';

	/** @var string */
	const SCHEMA_VERSION = '2026-08-10-subjects-v1';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_post_ngc_subjects_save', [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_post_ngc_subjects_delete', [ __CLASS__, 'handle_delete' ] );
		add_action( 'admin_post_ngc_subjects_seed', [ __CLASS__, 'handle_seed' ] );
		add_action( 'admin_post_ngc_subjects_sync_pages', [ __CLASS__, 'handle_sync_pages' ] );
		add_action( 'init', [ __CLASS__, 'maybe_seed_defaults' ], 20 );
	}

	/**
	 * Seed once when option is empty.
	 */
	public static function maybe_seed_defaults() {
		$raw = get_option( self::OPTION_KEY, null );
		if ( null !== $raw && false !== $raw ) {
			return;
		}
		self::save_all( self::default_subjects() );
		update_option( 'ngc_subjects_schema', self::SCHEMA_VERSION, false );
	}

	/**
	 * Default catalog matching BeyondInfinity kinetic tabs + tracks.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_subjects() {
		return [
			[
				'slug'           => 'mathematics',
				'title'          => 'Mathematics',
				'desc'           => 'CAPS & IEB Pure Maths (Gr 1–12) plus Matric exam prep.',
				'body'           => 'CAPS & IEB Pure Maths from Grade 1–12, Matric exam prep, homework rescue and weekly progress reports for parents.',
				'bullets'        => [ 'Grade 1–12', 'Exam technique', 'Weekly progress', 'Homework rescue' ],
				'page_lead'      => 'Master CAPS and IEB mathematics with vetted tutors who explain clearly and track progress weekly.',
				'page_content'   => "From Grade 1 foundations through Matric Pure Maths, our tutors cover algebra, geometry, trigonometry and calculus with homework rescue and exam technique.\n\nParents receive weekly progress notes so you always know where your learner stands.",
				'show_in_tabs'   => true,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 10,
			],
			[
				'slug'           => 'physical-science',
				'title'          => 'Physical Science',
				'desc'           => 'Physics and chemistry for CAPS, IEB and Cambridge.',
				'body'           => 'Physics and chemistry with practical understanding, problem-solving drills and Matric confidence building.',
				'bullets'        => [ 'Physics', 'Chemistry', 'Problem solving', 'Matric prep' ],
				'page_lead'      => 'Physics and chemistry tutoring built for CAPS, IEB and Cambridge learners.',
				'page_content'   => "Build conceptual understanding and exam confidence with problem-solving drills, formula mastery and practical explanations.\n\nIdeal for Grades 10–12 Matric prep and tertiary bridging.",
				'show_in_tabs'   => true,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 20,
			],
			[
				'slug'           => 'english',
				'title'          => 'English HL',
				'desc'           => 'Essays, literature study and comprehension coaching.',
				'body'           => 'Essays, literature, comprehension and grammar coaching aligned to IEB and CAPS outcomes.',
				'bullets'        => [ 'Comprehension', 'Essay writing', 'Grammar', 'Literature' ],
				'page_lead'      => 'Essay writing, literature and comprehension coaching aligned to CAPS and IEB.',
				'page_content'   => "Improve comprehension, grammar, literature analysis and persuasive writing with tutors who mark to school expectations.\n\nBuild confidence for controlled tests and final exams.",
				'show_in_tabs'   => true,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 30,
			],
			[
				'slug'           => 'programming',
				'title'          => 'Programming',
				'desc'           => 'IT/CAT projects, Scratch and Python foundations.',
				'body'           => 'Python, IT/CAT projects and logic foundations for school, college and portfolio building.',
				'bullets'        => [ 'Python basics', 'Web projects', 'Logic', 'Portfolio support' ],
				'page_lead'      => 'Python, IT/CAT and coding foundations for school projects and portfolios.',
				'page_content'   => "Learn logic, Python basics and project structure with tutors who support CAT/IT school assessments and early portfolio work.\n\nSuitable for beginners through intermediate learners.",
				'show_in_tabs'   => true,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 40,
			],
			[
				'slug'           => 'accounting',
				'title'          => 'Accounting',
				'desc'           => 'Bookkeeping, ledgers and financial statements.',
				'body'           => 'Bookkeeping, ledgers and financial statements explained with school-aligned practice.',
				'bullets'        => [ 'Bookkeeping', 'Ledgers', 'Financial statements', 'Exam prep' ],
				'page_lead'      => 'Clear bookkeeping and financial statement coaching for CAPS Accounting.',
				'page_content'   => 'Master journals, ledgers, trial balances and statements with tutors who specialise in school Accounting.',
				'show_in_tabs'   => false,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 50,
			],
			[
				'slug'           => 'life-sciences',
				'title'          => 'Life Sciences',
				'desc'           => 'Biology, cellular structures and exam technique.',
				'body'           => 'Biology concepts, diagrams and exam technique for CAPS Life Sciences.',
				'bullets'        => [ 'Biology', 'Diagrams', 'Exam technique', 'Matric prep' ],
				'page_lead'      => 'Life Sciences tutoring focused on concepts, diagrams and exam technique.',
				'page_content'   => 'From cellular biology to ecology, tutors help learners explain processes clearly and score on structured questions.',
				'show_in_tabs'   => false,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 60,
			],
			[
				'slug'           => 'economics',
				'title'          => 'Economics',
				'desc'           => 'Macro, micro and market systems explained clearly.',
				'body'           => 'Macro and micro economics with market systems explained for school assessments.',
				'bullets'        => [ 'Macro', 'Micro', 'Market systems', 'Essays' ],
				'page_lead'      => 'Macro, micro and market systems explained for CAPS Economics.',
				'page_content'   => 'Tutors break down graphs, essays and theory so learners can apply concepts under exam pressure.',
				'show_in_tabs'   => false,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 70,
			],
			[
				'slug'           => 'statistics',
				'title'          => 'Statistics',
				'desc'           => 'Tertiary stats, hypothesis testing and data analysis.',
				'body'           => 'Tertiary stats, hypothesis testing and data analysis support.',
				'bullets'        => [ 'Hypothesis testing', 'Data analysis', 'Tertiary support', 'Software help' ],
				'page_lead'      => 'Statistics tutoring for school and early tertiary modules.',
				'page_content'   => 'Cover probability, distributions, hypothesis testing and interpretation with patient, structured tutoring.',
				'show_in_tabs'   => false,
				'show_in_tracks' => true,
				'enabled'        => true,
				'sort_order'     => 80,
			],
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		$raw = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $raw ) || ! $raw ) {
			return self::normalize_list( self::default_subjects() );
		}
		return self::normalize_list( $raw );
	}

	/**
	 * Enabled subjects sorted.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function enabled() {
		$rows = array_values(
			array_filter(
				self::all(),
				static function ( $row ) {
					return ! empty( $row['enabled'] );
				}
			)
		);
		usort(
			$rows,
			static function ( $a, $b ) {
				return (int) ( $a['sort_order'] ?? 0 ) <=> (int) ( $b['sort_order'] ?? 0 );
			}
		);
		return $rows;
	}

	/**
	 * @param string $slug Subject slug.
	 * @return array<string, mixed>|null
	 */
	public static function get( $slug ) {
		$slug = sanitize_title( (string) $slug );
		foreach ( self::all() as $row ) {
			if ( ( $row['slug'] ?? '' ) === $slug ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Homepage explorer tabs.
	 *
	 * @return array<int, array{slug:string,title:string,body:string,bullets:array<int,string>,url?:string}>
	 */
	public static function tabs_for_theme() {
		$out = [];
		foreach ( self::enabled() as $row ) {
			if ( empty( $row['show_in_tabs'] ) ) {
				continue;
			}
			$item = [
				'slug'    => (string) $row['slug'],
				'title'   => (string) $row['title'],
				'body'    => (string) $row['body'],
				'bullets' => array_values( (array) ( $row['bullets'] ?? [] ) ),
			];
			$url = self::public_url( $row );
			if ( $url ) {
				$item['url'] = $url;
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Marquee / track cards.
	 *
	 * @return array<int, array{name:string,desc:string,slug?:string,url?:string}>
	 */
	public static function tracks_for_theme() {
		$out = [];
		foreach ( self::enabled() as $row ) {
			if ( empty( $row['show_in_tracks'] ) ) {
				continue;
			}
			$item = [
				'name' => (string) $row['title'],
				'desc' => (string) ( $row['desc'] ?: $row['body'] ),
				'slug' => (string) $row['slug'],
			];
			$url = self::public_url( $row );
			if ( $url ) {
				$item['url'] = $url;
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Public URL for a subject row.
	 *
	 * @param array<string, mixed> $row Subject.
	 * @return string
	 */
	public static function public_url( array $row ) {
		$page_id = (int) ( $row['page_id'] ?? 0 );
		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return (string) $url;
			}
		}
		$slug = sanitize_title( (string) ( $row['slug'] ?? '' ) );
		if ( ! $slug ) {
			return '';
		}
		$page = get_page_by_path( 'subjects/' . $slug );
		if ( $page instanceof WP_Post ) {
			return (string) get_permalink( $page );
		}
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			return (string) get_permalink( $page );
		}
		return home_url( '/subjects/' . $slug . '/' );
	}

	/**
	 * Persist full list.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @return bool
	 */
	public static function save_all( array $rows ) {
		$normalized = self::normalize_list( $rows );
		return (bool) update_option( self::OPTION_KEY, $normalized, false );
	}

	/**
	 * Upsert one subject.
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array{ok:bool,slug:string,message:string}
	 */
	public static function upsert( array $input ) {
		$slug = sanitize_title( (string) ( $input['slug'] ?? $input['title'] ?? '' ) );
		if ( ! $slug ) {
			return [ 'ok' => false, 'slug' => '', 'message' => 'Slug or title is required.' ];
		}

		$row = self::normalize_row(
			array_merge(
				[
					'slug'           => $slug,
					'title'          => (string) ( $input['title'] ?? $slug ),
					'desc'           => '',
					'body'           => '',
					'bullets'        => [],
					'page_lead'      => '',
					'page_content'   => '',
					'show_in_tabs'   => false,
					'show_in_tracks' => true,
					'enabled'        => true,
					'sort_order'     => 100,
					'page_id'        => 0,
				],
				$input,
				[ 'slug' => $slug ]
			)
		);

		$all     = self::all();
		$found   = false;
		$updated = [];
		foreach ( $all as $existing ) {
			if ( ( $existing['slug'] ?? '' ) === $slug ) {
				$row['page_id'] = (int) ( $row['page_id'] ?: ( $existing['page_id'] ?? 0 ) );
				$updated[]      = $row;
				$found          = true;
			} else {
				$updated[] = $existing;
			}
		}
		if ( ! $found ) {
			$updated[] = $row;
		}

		self::save_all( $updated );
		return [ 'ok' => true, 'slug' => $slug, 'message' => 'Subject saved.' ];
	}

	/**
	 * @param string $slug Slug.
	 * @return bool
	 */
	public static function delete( $slug ) {
		$slug = sanitize_title( (string) $slug );
		$all  = array_values(
			array_filter(
				self::all(),
				static function ( $row ) use ( $slug ) {
					return ( $row['slug'] ?? '' ) !== $slug;
				}
			)
		);
		return self::save_all( $all );
	}

	/**
	 * Ensure hub + subject WP pages exist and store page_id.
	 *
	 * @return array{created:int,updated:int,hub_id:int}
	 */
	public static function sync_pages() {
		$hub_id  = self::ensure_hub_page();
		$created = 0;
		$updated = 0;
		$all     = self::all();
		$out     = [];

		foreach ( $all as $row ) {
			if ( empty( $row['enabled'] ) ) {
				$out[] = $row;
				continue;
			}
			$result = self::ensure_subject_page( $row, $hub_id );
			if ( $result['created'] ) {
				++$created;
			}
			if ( $result['updated'] ) {
				++$updated;
			}
			$row['page_id'] = (int) $result['page_id'];
			$out[]          = $row;
		}

		self::save_all( $out );
		return [
			'created' => $created,
			'updated' => $updated,
			'hub_id'  => $hub_id,
		];
	}

	/**
	 * @return int Hub page ID.
	 */
	private static function ensure_hub_page() {
		$existing = get_page_by_path( 'subjects' );
		if ( $existing instanceof WP_Post ) {
			return (int) $existing->ID;
		}
		$id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Subjects',
				'post_name'    => 'subjects',
				'post_content' => '<!-- wp:paragraph --><p>Explore tutoring subjects offered by NextGen Tutors.</p><!-- /wp:paragraph -->',
			],
			true
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * @param array<string, mixed> $row Subject.
	 * @param int                  $hub_id Parent page.
	 * @return array{page_id:int,created:bool,updated:bool}
	 */
	private static function ensure_subject_page( array $row, $hub_id ) {
		$slug    = sanitize_title( (string) ( $row['slug'] ?? '' ) );
		$page_id = (int) ( $row['page_id'] ?? 0 );
		$created = false;
		$updated = false;

		if ( $page_id > 0 && get_post( $page_id ) ) {
			$post = get_post( $page_id );
		} else {
			$post = get_page_by_path( 'subjects/' . $slug );
			if ( ! $post ) {
				$post = get_page_by_path( $slug );
			}
		}

		$content = self::build_page_content( $row );
		$title   = (string) ( $row['title'] ?? $slug );

		if ( $post instanceof WP_Post ) {
			$page_id = (int) $post->ID;
			wp_update_post(
				[
					'ID'           => $page_id,
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_parent'  => (int) $hub_id,
					'post_content' => $content,
					'post_status'  => 'publish',
				]
			);
			$updated = true;
		} else {
			$page_id = wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_parent'  => (int) $hub_id,
					'post_content' => $content,
				],
				true
			);
			if ( is_wp_error( $page_id ) ) {
				return [ 'page_id' => 0, 'created' => false, 'updated' => false ];
			}
			$page_id = (int) $page_id;
			$created = true;
		}

		update_post_meta( $page_id, '_wp_page_template', 'page-subject.php' );
		update_post_meta( $page_id, 'ngc_subject_slug', $slug );

		return [
			'page_id' => $page_id,
			'created' => $created,
			'updated' => $updated,
		];
	}

	/**
	 * @param array<string, mixed> $row Subject.
	 * @return string
	 */
	private static function build_page_content( array $row ) {
		$lead = trim( (string) ( $row['page_lead'] ?? '' ) );
		$body = trim( (string) ( $row['page_content'] ?? '' ) );
		if ( ! $body ) {
			$body = (string) ( $row['body'] ?? '' );
		}
		$parts = [];
		if ( $lead ) {
			$parts[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $lead ) . '</strong></p><!-- /wp:paragraph -->';
		}
		foreach ( preg_split( "/\n{2,}/", $body ) as $para ) {
			$para = trim( $para );
			if ( $para ) {
				$parts[] = '<!-- wp:paragraph --><p>' . esc_html( $para ) . '</p><!-- /wp:paragraph -->';
			}
		}
		$bullets = array_values( array_filter( array_map( 'strval', (array) ( $row['bullets'] ?? [] ) ) ) );
		if ( $bullets ) {
			$lis = '';
			foreach ( $bullets as $b ) {
				$lis .= '<li>' . esc_html( $b ) . '</li>';
			}
			$parts[] = '<!-- wp:list --><ul>' . $lis . '</ul><!-- /wp:list -->';
		}
		$find = esc_url( home_url( '/find-a-tutor/?subject=' . rawurlencode( (string) ( $row['slug'] ?? '' ) ) ) );
		$parts[] = '<!-- wp:paragraph --><p><a href="' . $find . '">Find a tutor for ' . esc_html( (string) ( $row['title'] ?? '' ) ) . '</a></p><!-- /wp:paragraph -->';
		return implode( "\n", $parts );
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_list( array $rows ) {
		$out   = [];
		$seen  = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$n = self::normalize_row( $row );
			if ( ! $n['slug'] || isset( $seen[ $n['slug'] ] ) ) {
				continue;
			}
			$seen[ $n['slug'] ] = true;
			$out[]              = $n;
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return (int) ( $a['sort_order'] ?? 0 ) <=> (int) ( $b['sort_order'] ?? 0 );
			}
		);
		return $out;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private static function normalize_row( array $row ) {
		$slug = sanitize_title( (string) ( $row['slug'] ?? $row['title'] ?? $row['name'] ?? '' ) );
		$title = sanitize_text_field( (string) ( $row['title'] ?? $row['name'] ?? $slug ) );
		$bullets = $row['bullets'] ?? [];
		if ( is_string( $bullets ) ) {
			$bullets = preg_split( '/[\r\n|]+/', $bullets ) ?: [];
		}
		$bullets = array_values(
			array_filter(
				array_map(
					static function ( $b ) {
						return sanitize_text_field( (string) $b );
					},
					(array) $bullets
				)
			)
		);

		return [
			'slug'           => $slug,
			'title'          => $title,
			'desc'           => sanitize_textarea_field( (string) ( $row['desc'] ?? '' ) ),
			'body'           => sanitize_textarea_field( (string) ( $row['body'] ?? '' ) ),
			'bullets'        => $bullets,
			'page_lead'      => sanitize_textarea_field( (string) ( $row['page_lead'] ?? '' ) ),
			'page_content'   => sanitize_textarea_field( (string) ( $row['page_content'] ?? '' ) ),
			'show_in_tabs'   => ! empty( $row['show_in_tabs'] ),
			'show_in_tracks' => ! empty( $row['show_in_tracks'] ),
			'enabled'        => ! isset( $row['enabled'] ) || ! empty( $row['enabled'] ),
			'sort_order'     => (int) ( $row['sort_order'] ?? 100 ),
			'page_id'        => (int) ( $row['page_id'] ?? 0 ),
		];
	}

	/**
	 * Admin POST: save subject.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_subjects_save' );

		$bullets_raw = isset( $_POST['bullets'] ) ? wp_unslash( (string) $_POST['bullets'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$result      = self::upsert(
			[
				'slug'           => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( (string) $_POST['slug'] ) ) : '',
				'title'          => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['title'] ) ) : '',
				'desc'           => isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['desc'] ) ) : '',
				'body'           => isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['body'] ) ) : '',
				'bullets'        => $bullets_raw,
				'page_lead'      => isset( $_POST['page_lead'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['page_lead'] ) ) : '',
				'page_content'   => isset( $_POST['page_content'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['page_content'] ) ) : '',
				'show_in_tabs'   => ! empty( $_POST['show_in_tabs'] ),
				'show_in_tracks' => ! empty( $_POST['show_in_tracks'] ),
				'enabled'        => ! empty( $_POST['enabled'] ),
				'sort_order'     => isset( $_POST['sort_order'] ) ? (int) $_POST['sort_order'] : 100,
				'page_id'        => isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0,
			]
		);

		$redirect = add_query_arg(
			[
				'page'    => 'ngt-edu-subjects',
				'updated' => $result['ok'] ? '1' : '0',
				'edit'    => $result['slug'],
				'msg'     => rawurlencode( $result['message'] ),
			],
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Admin POST: delete subject.
	 */
	public static function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_subjects_delete' );
		$slug = isset( $_GET['slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['slug'] ) ) : '';
		if ( $slug ) {
			self::delete( $slug );
		}
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'ngt-edu-subjects',
					'deleted' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Admin POST: reset defaults.
	 */
	public static function handle_seed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_subjects_seed' );
		self::save_all( self::default_subjects() );
		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => 'ngt-edu-subjects',
					'seeded' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Admin POST: sync WP pages.
	 */
	public static function handle_sync_pages() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_subjects_sync_pages' );
		$stats = self::sync_pages();
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'ngt-edu-subjects',
					'synced'  => '1',
					'created' => (int) $stats['created'],
					'updated' => (int) $stats['updated'],
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render admin CRUD UI (used by Education Subjects screen).
	 */
	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		$edit_slug = isset( $_GET['edit'] ) ? sanitize_title( wp_unslash( (string) $_GET['edit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editing   = $edit_slug ? self::get( $edit_slug ) : null;
		if ( ! $editing && 'new' === $edit_slug ) {
			$editing = self::normalize_row(
				[
					'slug'           => '',
					'title'          => '',
					'show_in_tabs'   => true,
					'show_in_tracks' => true,
					'enabled'        => true,
					'sort_order'     => 100,
				]
			);
		}

		$rows = self::all();

		if ( ! empty( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Subject saved.', 'nextgencompanion' ) . '</p></div>';
		}
		if ( ! empty( $_GET['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Subject deleted.', 'nextgencompanion' ) . '</p></div>';
		}
		if ( ! empty( $_GET['seeded'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Default subjects restored.', 'nextgencompanion' ) . '</p></div>';
		}
		if ( ! empty( $_GET['synced'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(
				sprintf(
					/* translators: 1: created count, 2: updated count */
					__( 'Subject pages synced (%1$d created, %2$d updated).', 'nextgencompanion' ),
					(int) ( $_GET['created'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					(int) ( $_GET['updated'] ?? 0 ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				)
			) . '</p></div>';
		}

		echo '<div class="ngt-admin-card" style="margin-bottom:16px">';
		echo '<p>' . esc_html__( 'Manage subject tabs (homepage explorer), marquee tracks, and landing-page copy. Tutor matching taxonomy terms remain separate under marketplace profiles.', 'nextgencompanion' ) . '</p>';
		echo '<p>';
		echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=ngt-edu-subjects&edit=new' ) ) . '">' . esc_html__( 'Add subject', 'nextgencompanion' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ngc_subjects_sync_pages' ), 'ngc_subjects_sync_pages' ) ) . '">' . esc_html__( 'Sync subject pages', 'nextgencompanion' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ngc_subjects_seed' ), 'ngc_subjects_seed' ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Replace all subjects with defaults?', 'nextgencompanion' ) ) . '\');">' . esc_html__( 'Restore defaults', 'nextgencompanion' ) . '</a>';
		echo '</p></div>';

		if ( is_array( $editing ) ) {
			self::render_form( $editing, 'new' === $edit_slug );
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Title', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Slug', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Tabs', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Tracks', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Enabled', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Order', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Page', 'nextgencompanion' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'nextgencompanion' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( ! $rows ) {
			echo '<tr><td colspan="8">' . esc_html__( 'No subjects yet.', 'nextgencompanion' ) . '</td></tr>';
		}

		foreach ( $rows as $row ) {
			$url = self::public_url( $row );
			echo '<tr>';
			echo '<td><strong>' . esc_html( (string) $row['title'] ) . '</strong></td>';
			echo '<td><code>' . esc_html( (string) $row['slug'] ) . '</code></td>';
			echo '<td>' . ( ! empty( $row['show_in_tabs'] ) ? '✓' : '—' ) . '</td>';
			echo '<td>' . ( ! empty( $row['show_in_tracks'] ) ? '✓' : '—' ) . '</td>';
			echo '<td>' . ( ! empty( $row['enabled'] ) ? '✓' : '—' ) . '</td>';
			echo '<td>' . esc_html( (string) (int) $row['sort_order'] ) . '</td>';
			echo '<td>';
			if ( $url ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View', 'nextgencompanion' ) . '</a>';
			} else {
				echo '—';
			}
			echo '</td><td>';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=ngt-edu-subjects&edit=' . rawurlencode( (string) $row['slug'] ) ) ) . '">' . esc_html__( 'Edit', 'nextgencompanion' ) . '</a> · ';
			echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ngc_subjects_delete&slug=' . rawurlencode( (string) $row['slug'] ) ), 'ngc_subjects_delete' ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this subject?', 'nextgencompanion' ) ) . '\');">' . esc_html__( 'Delete', 'nextgencompanion' ) . '</a>';
			echo '</td></tr>';
		}
		echo '</tbody></table>';

		// Live taxonomy counts (read-only context).
		$tax_rows = [];
		if ( function_exists( 'get_terms' ) && taxonomy_exists( 'tutor_subject' ) ) {
			$terms = get_terms(
				[
					'taxonomy'   => 'tutor_subject',
					'hide_empty' => false,
				]
			);
			if ( ! is_wp_error( $terms ) && $terms ) {
				foreach ( $terms as $term ) {
					$tax_rows[] = [ 'name' => $term->name, 'count' => (int) $term->count ];
				}
			}
		}
		if ( $tax_rows ) {
			echo '<div class="ngt-admin-card" style="margin-top:20px"><h3>' . esc_html__( 'Live tutor subject taxonomy', 'nextgencompanion' ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'These terms come from tutor profiles and are not edited here.', 'nextgencompanion' ) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Term', 'nextgencompanion' ) . '</th><th>' . esc_html__( 'Tutors', 'nextgencompanion' ) . '</th></tr></thead><tbody>';
			foreach ( $tax_rows as $t ) {
				echo '<tr><td>' . esc_html( (string) $t['name'] ) . '</td><td>' . esc_html( (string) $t['count'] ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
	}

	/**
	 * @param array<string, mixed> $row Subject.
	 * @param bool                 $is_new New flag.
	 */
	private static function render_form( array $row, $is_new = false ) {
		echo '<div class="ngt-admin-card" style="margin-bottom:20px">';
		echo '<h2>' . esc_html( $is_new ? __( 'Add subject', 'nextgencompanion' ) : __( 'Edit subject', 'nextgencompanion' ) ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="ngc_subjects_save" />';
		wp_nonce_field( 'ngc_subjects_save' );
		echo '<input type="hidden" name="page_id" value="' . esc_attr( (string) (int) ( $row['page_id'] ?? 0 ) ) . '" />';

		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_row( __( 'Title', 'nextgencompanion' ), '<input type="text" class="regular-text" name="title" required value="' . esc_attr( (string) ( $row['title'] ?? '' ) ) . '" />' );
		self::field_row( __( 'Slug', 'nextgencompanion' ), '<input type="text" class="regular-text" name="slug" ' . ( $is_new ? '' : 'readonly' ) . ' value="' . esc_attr( (string) ( $row['slug'] ?? '' ) ) . '" placeholder="mathematics" /><p class="description">' . esc_html__( 'URL key under /subjects/{slug}/. Leave blank on create to derive from title.', 'nextgencompanion' ) . '</p>' );
		self::field_row( __( 'Short description', 'nextgencompanion' ), '<textarea class="large-text" rows="2" name="desc">' . esc_textarea( (string) ( $row['desc'] ?? '' ) ) . '</textarea><p class="description">' . esc_html__( 'Used in marquee / track cards.', 'nextgencompanion' ) . '</p>' );
		self::field_row( __( 'Tab body', 'nextgencompanion' ), '<textarea class="large-text" rows="3" name="body">' . esc_textarea( (string) ( $row['body'] ?? '' ) ) . '</textarea><p class="description">' . esc_html__( 'Homepage subject explorer panel copy.', 'nextgencompanion' ) . '</p>' );
		self::field_row( __( 'Bullets', 'nextgencompanion' ), '<textarea class="large-text" rows="4" name="bullets">' . esc_textarea( implode( "\n", (array) ( $row['bullets'] ?? [] ) ) ) . '</textarea><p class="description">' . esc_html__( 'One bullet per line.', 'nextgencompanion' ) . '</p>' );
		self::field_row( __( 'Page lead', 'nextgencompanion' ), '<textarea class="large-text" rows="2" name="page_lead">' . esc_textarea( (string) ( $row['page_lead'] ?? '' ) ) . '</textarea>' );
		self::field_row( __( 'Page content', 'nextgencompanion' ), '<textarea class="large-text" rows="6" name="page_content">' . esc_textarea( (string) ( $row['page_content'] ?? '' ) ) . '</textarea><p class="description">' . esc_html__( 'Landing page body. Blank lines start new paragraphs. Synced into the WordPress page on “Sync subject pages”.', 'nextgencompanion' ) . '</p>' );
		self::field_row( __( 'Sort order', 'nextgencompanion' ), '<input type="number" name="sort_order" value="' . esc_attr( (string) (int) ( $row['sort_order'] ?? 100 ) ) . '" />' );
		self::field_row(
			__( 'Flags', 'nextgencompanion' ),
			'<label><input type="checkbox" name="enabled" value="1" ' . checked( ! empty( $row['enabled'] ), true, false ) . ' /> ' . esc_html__( 'Enabled', 'nextgencompanion' ) . '</label><br />'
			. '<label><input type="checkbox" name="show_in_tabs" value="1" ' . checked( ! empty( $row['show_in_tabs'] ), true, false ) . ' /> ' . esc_html__( 'Show in homepage tabs', 'nextgencompanion' ) . '</label><br />'
			. '<label><input type="checkbox" name="show_in_tracks" value="1" ' . checked( ! empty( $row['show_in_tracks'] ), true, false ) . ' /> ' . esc_html__( 'Show in tracks / marquee', 'nextgencompanion' ) . '</label>'
		);
		echo '</tbody></table>';
		submit_button( $is_new ? __( 'Create subject', 'nextgencompanion' ) : __( 'Update subject', 'nextgencompanion' ) );
		echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ngt-edu-subjects' ) ) . '">' . esc_html__( 'Cancel', 'nextgencompanion' ) . '</a>';
		echo '</form></div>';
	}

	/**
	 * @param string $label Label.
	 * @param string $control HTML control.
	 */
	private static function field_row( $label, $control ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . $control . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- controls are built with escaped values above.
	}
}
