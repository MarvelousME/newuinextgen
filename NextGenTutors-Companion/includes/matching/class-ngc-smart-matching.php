<?php
/**
 * Smart tutor-to-student matching — CPT scoring wizard.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT-based matching engine with shortcode, REST, and AJAX.
 */
class NGC_Smart_Matching {

	const NONCE = 'ngc_match';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_enqueue_widget' ], 30 );
		add_action( 'wp_footer', [ __CLASS__, 'render_floating_widget' ], 45 );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
		add_action( 'wp_ajax_ngc_match_tutors', [ __CLASS__, 'ajax_match' ] );
		add_action( 'wp_ajax_nopriv_ngc_match_tutors', [ __CLASS__, 'ajax_match' ] );
	}

	/**
	 * Register shortcode.
	 */
	public static function register_shortcode() {
		add_shortcode( 'ngc_match_tutor', [ __CLASS__, 'shortcode' ] );
	}

	/**
	 * Register front-end assets.
	 */
	public static function register_assets() {
		wp_register_style( 'ngc-matching', NGC_PLUGIN_URL . 'assets/css/ngc-matching.css', [], NGC_VERSION );
		wp_register_script( 'ngc-matching', NGC_PLUGIN_URL . 'assets/js/ngc-matching.js', [], NGC_VERSION, true );
		wp_localize_script(
			'ngc-matching',
			'NGC_MATCH',
			[
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( self::NONCE ),
				'rest'  => esc_url_raw( rest_url( 'ngc/v1' ) ),
			]
		);
	}

	/**
	 * REST route for programmatic scoring.
	 */
	public static function register_rest() {
		register_rest_route(
			'ngc/v1',
			'/match/smart',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_match' ],
				'permission_callback' => [ __CLASS__, 'rest_permission_public_match' ],
				'args'                => [
					'subject'  => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'grade'    => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'province' => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'format'   => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'max_rate' => [ 'sanitize_callback' => 'absint' ],
				],
			]
		);
	}

	/**
	 * @return string
	 */
	public static function shortcode() {
		self::enqueue_matching_bundle();
		return self::render_wizard_markup( 'ngcm' );
	}

	/**
	 * Whether the floating match widget should render.
	 *
	 * @return bool
	 */
	public static function should_show_widget() {
		if ( is_admin() || wp_doing_ajax() ) {
			return false;
		}
		if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
			return false;
		}
		if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
			return false;
		}
		if ( is_page( 'find-a-tutor' ) ) {
			return false;
		}
		return (bool) apply_filters( 'ngc_show_match_widget', true );
	}

	/**
	 * Enqueue matching scripts/styles.
	 */
	public static function enqueue_matching_bundle() {
		NGC_Forms::enqueue_validation();
		wp_enqueue_style( 'ngc-matching' );
		wp_enqueue_script( 'ngc-matching' );
	}

	/**
	 * Enqueue floating widget assets on public pages.
	 */
	public static function maybe_enqueue_widget() {
		if ( ! self::should_show_widget() ) {
			return;
		}
		self::enqueue_matching_bundle();
		wp_enqueue_style( 'ngc-match-widget', NGC_PLUGIN_URL . 'assets/css/ngc-match-widget.css', [ 'ngc-matching', 'ngc-validation' ], NGC_VERSION );
		wp_enqueue_script( 'ngc-match-widget', NGC_PLUGIN_URL . 'assets/js/ngc-match-widget.js', [ 'ngc-matching', 'ngc-validation' ], NGC_VERSION, true );
		wp_localize_script(
			'ngc-match-widget',
			'NGC_MATCH_WIDGET',
			[
				'findUrl'  => home_url( '/find-a-tutor' ),
				'autoOpen' => isset( $_GET['match'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'prefill'  => self::widget_prefill(),
				'i18n'     => [
					'open'  => __( 'Find your tutor', 'nextgencompanion' ),
					'close' => __( 'Close matcher', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * URL-driven prefill for hero search → widget handoff.
	 *
	 * @return array<string, string>
	 */
	private static function widget_prefill() {
		$prefill = [];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subject = isset( $_GET['subject'] ) ? sanitize_text_field( wp_unslash( $_GET['subject'] ) ) : '';
		if ( $subject && function_exists( 'bi_subject_label_from_slug' ) ) {
			$prefill['subject'] = bi_subject_label_from_slug( $subject );
		} elseif ( $subject ) {
			$prefill['subject'] = ucwords( str_replace( '-', ' ', $subject ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['grade'] ) ) {
			$prefill['grade'] = sanitize_text_field( wp_unslash( $_GET['grade'] ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['location'] ) ) {
			$prefill['province'] = sanitize_text_field( wp_unslash( $_GET['location'] ) );
		}
		return $prefill;
	}

	/**
	 * Floating panel markup in footer.
	 */
	public static function render_floating_widget() {
		if ( ! self::should_show_widget() ) {
			return;
		}
		?>
		<div id="ngc-match-panel" class="ngc-match-panel" role="dialog" aria-labelledby="ngc-match-panel-title" aria-hidden="true">
			<div class="ngc-match-panel__head">
				<div>
					<div id="ngc-match-panel-title" class="ngc-match-panel__title"><?php esc_html_e( 'Find Your Tutor', 'nextgencompanion' ); ?></div>
					<div class="ngc-match-panel__sub"><?php esc_html_e( 'Live matching from our vetted tutor marketplace', 'nextgencompanion' ); ?></div>
				</div>
				<button type="button" class="ngc-match-panel__close" id="ngc-match-close" aria-label="<?php esc_attr_e( 'Close', 'nextgencompanion' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
				</button>
			</div>
			<div class="ngc-match-panel__body">
				<?php echo self::render_wizard_markup( 'ngcmw' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Matching wizard HTML.
	 *
	 * @param string $id_prefix Field ID prefix (ngcm | ngcmw).
	 * @return string
	 */
	public static function render_wizard_markup( $id_prefix = 'ngcm' ) {
		$lists = self::get_form_lists();

		ob_start();
		?>
		<div class="ngc-match-wizard" data-ngc-matcher>
			<div class="ngc-match-step is-active" data-step="1">
				<div class="ngc-match-step-head">
					<span class="ngc-match-step-num">01</span>
					<div>
						<h3><?php esc_html_e( 'Tell us what you need', 'nextgencompanion' ); ?></h3>
						<p><?php esc_html_e( 'We will find your best academic match within 60 seconds.', 'nextgencompanion' ); ?></p>
					</div>
				</div>
				<form class="ngc-form ngc-match-form" novalidate>
					<?php wp_nonce_field( self::NONCE, 'ngc_match_nonce' ); ?>
					<div class="ngc-field-row">
						<div class="ngc-field-group">
							<label for="<?php echo esc_attr( $id_prefix ); ?>-subject"><?php esc_html_e( 'Subject *', 'nextgencompanion' ); ?></label>
							<select id="<?php echo esc_attr( $id_prefix ); ?>-subject" name="subject" required data-validate="required" aria-required="true">
								<option value=""><?php esc_html_e( 'Select subject…', 'nextgencompanion' ); ?></option>
								<?php foreach ( $lists['subjects'] as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
								<?php endforeach; ?>
							</select>
							<span class="ngc-field-error" aria-live="polite"></span>
						</div>
						<div class="ngc-field-group">
							<label for="<?php echo esc_attr( $id_prefix ); ?>-grade"><?php esc_html_e( 'Grade / Level *', 'nextgencompanion' ); ?></label>
							<select id="<?php echo esc_attr( $id_prefix ); ?>-grade" name="grade" required data-validate="required" aria-required="true">
								<option value=""><?php esc_html_e( 'Select grade…', 'nextgencompanion' ); ?></option>
								<?php foreach ( $lists['grades'] as $g ) : ?>
									<option value="<?php echo esc_attr( $g ); ?>"><?php echo esc_html( $g ); ?></option>
								<?php endforeach; ?>
							</select>
							<span class="ngc-field-error" aria-live="polite"></span>
						</div>
					</div>
					<div class="ngc-field-row">
						<div class="ngc-field-group">
							<label for="<?php echo esc_attr( $id_prefix ); ?>-province"><?php esc_html_e( 'Province', 'nextgencompanion' ); ?></label>
							<select id="<?php echo esc_attr( $id_prefix ); ?>-province" name="province">
								<option value=""><?php esc_html_e( 'Any province', 'nextgencompanion' ); ?></option>
								<?php foreach ( $lists['provinces'] as $p ) : ?>
									<option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="ngc-field-group">
							<label for="<?php echo esc_attr( $id_prefix ); ?>-format"><?php esc_html_e( 'Learning format', 'nextgencompanion' ); ?></label>
							<select id="<?php echo esc_attr( $id_prefix ); ?>-format" name="format">
								<option value=""><?php esc_html_e( 'Any format', 'nextgencompanion' ); ?></option>
								<?php foreach ( $lists['formats'] as $f ) : ?>
									<option value="<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $f ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="ngc-field-row">
						<div class="ngc-field-group">
							<label for="<?php echo esc_attr( $id_prefix ); ?>-maxrate"><?php esc_html_e( 'Max hourly rate (R)', 'nextgencompanion' ); ?></label>
							<input type="number" id="<?php echo esc_attr( $id_prefix ); ?>-maxrate" name="max_rate" min="100" max="2000" step="50" placeholder="e.g. 500" data-validate="min:100" />
							<span class="ngc-field-error" aria-live="polite"></span>
						</div>
						<div class="ngc-field-group">
							<label for="<?php echo esc_attr( $id_prefix ); ?>-name"><?php esc_html_e( "Your / student's name *", 'nextgencompanion' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $id_prefix ); ?>-name" name="student_name" required data-validate="required|min-length:2" aria-required="true" />
							<span class="ngc-field-error" aria-live="polite"></span>
						</div>
					</div>
					<div class="ngc-field-group">
						<label for="<?php echo esc_attr( $id_prefix ); ?>-needs"><?php esc_html_e( 'Specific needs (optional)', 'nextgencompanion' ); ?></label>
						<textarea id="<?php echo esc_attr( $id_prefix ); ?>-needs" name="needs" rows="3" data-validate="no-script"></textarea>
					</div>
					<button type="submit" class="ngc-btn ngc-btn-accent ngc-match-submit">
						<span class="ngc-btn-label"><?php esc_html_e( 'Find My Tutor', 'nextgencompanion' ); ?></span>
						<span class="ngc-btn-spinner" hidden aria-hidden="true"></span>
					</button>
				</form>
			</div>
			<div class="ngc-match-step" data-step="2" hidden>
				<div class="ngc-match-step-head">
					<span class="ngc-match-step-num">02</span>
					<div>
						<h3 class="ngc-match-result-title"><?php esc_html_e( 'Your top matches', 'nextgencompanion' ); ?></h3>
						<p class="ngc-match-result-sub"></p>
					</div>
				</div>
				<div class="ngc-match-results" aria-live="polite"></div>
				<button type="button" class="ngc-btn ngc-btn-ghost ngc-match-restart"><?php esc_html_e( '← Refine search', 'nextgencompanion' ); ?></button>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Taxonomy option lists for the wizard.
	 *
	 * @return array<string, string[]>
	 */
	private static function get_form_lists() {
		return [
			'subjects'  => self::term_list( 'subject', [ 'Mathematics', 'Physical Sciences', 'English HL', 'Accounting', 'Life Sciences' ] ),
			'grades'    => self::term_list( 'grade', [ 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'Tertiary' ] ),
			'provinces' => self::term_list( 'province', [ 'Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State' ] ),
			'formats'   => self::term_list( 'learning_format', [ 'Online', 'In-Person', 'Hybrid' ] ),
		];
	}

	/**
	 * AJAX match handler.
	 */
	public static function ajax_match() {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'nextgencompanion' ) ], 403 );
		}

		if ( ! NGC_Rate_Limiter::check( 'ajax_match_tutors', 20, 600 ) ) {
			status_header( 429 );
			wp_send_json_error( [ 'message' => __( 'Too many requests. Please wait and try again.', 'nextgencompanion' ) ], 429 );
		}

		$params  = self::sanitize_params( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$matches = self::run_match( $params );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'tutor_match_run', 'match', 0, [ 'count' => count( $matches ), 'subject' => $params['subject'] ?? '' ] );
		}

		wp_send_json_success(
			[
				'count' => count( $matches ),
				'html'  => self::render_matches( $matches, $params ),
			]
		);
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function rest_match( $req ) {
		$matches = self::run_match( self::sanitize_params( $req->get_params() ) );
		$sanitized = array_map( [ __CLASS__, 'sanitize_match_row' ], $matches );
		return rest_ensure_response( [ 'count' => count( $sanitized ), 'matches' => $sanitized ] );
	}

	/**
	 * PUBLIC_SAFE — throttled anonymous matching.
	 *
	 * @return bool|WP_Error
	 */
	public static function rest_permission_public_match() {
		return NGC_Rest::public_throttled( 'rest_match_smart', 30, 600 );
	}

	/**
	 * @param array<string, mixed> $row Match row.
	 * @return array<string, mixed>
	 */
	public static function sanitize_match_row( $row ) {
		return [
			'id'       => (int) ( $row['id'] ?? 0 ),
			'title'    => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'excerpt'  => sanitize_textarea_field( (string) ( $row['excerpt'] ?? '' ) ),
			'url'      => esc_url_raw( (string) ( $row['url'] ?? '' ) ),
			'photo'    => esc_url_raw( (string) ( $row['photo'] ?? '' ) ),
			'rate'     => (int) ( $row['rate'] ?? 0 ),
			'rating'   => (float) ( $row['rating'] ?? 0 ),
			'reviews'  => (int) ( $row['reviews'] ?? 0 ),
			'vetted'   => ! empty( $row['vetted'] ),
			'score'    => (float) ( $row['score'] ?? 0 ),
			'subjects' => array_map( 'sanitize_text_field', (array) ( $row['subjects'] ?? [] ) ),
			'province' => array_map( 'sanitize_text_field', (array) ( $row['province'] ?? [] ) ),
			'formats'  => array_map( 'sanitize_text_field', (array) ( $row['formats'] ?? [] ) ),
			'is_demo'  => class_exists( 'NGC_Tutor_Cpt_Source' ) && NGC_Tutor_Cpt_Source::is_demo_tutor( (int) ( $row['id'] ?? 0 ) ),
		];
	}

	/**
	 * @param array<string, mixed> $raw Raw input.
	 * @return array<string, mixed>
	 */
	private static function sanitize_params( $raw ) {
		return [
			'subject'      => sanitize_text_field( wp_unslash( $raw['subject'] ?? '' ) ),
			'grade'        => sanitize_text_field( wp_unslash( $raw['grade'] ?? '' ) ),
			'province'     => sanitize_text_field( wp_unslash( $raw['province'] ?? '' ) ),
			'format'       => sanitize_text_field( wp_unslash( $raw['format'] ?? '' ) ),
			'max_rate'     => absint( $raw['max_rate'] ?? 0 ),
			'student_name' => sanitize_text_field( wp_unslash( $raw['student_name'] ?? '' ) ),
			'needs'        => sanitize_textarea_field( wp_unslash( $raw['needs'] ?? '' ) ),
		];
	}

	/**
	 * Score tutors CPT against criteria.
	 *
	 * @param array<string, mixed> $params Criteria.
	 * @return array<int, array<string, mixed>>
	 */
	public static function run_match( $params ) {
		if ( class_exists( 'NGC_Tutor_Seeder' ) && 0 === NGC_Tutor_Seeder::published_count() ) {
			NGC_Tutor_Seeder::ensure_seeded();
		} elseif ( function_exists( 'bi_ensure_live_tutor_cpt' ) ) {
			bi_ensure_live_tutor_cpt();
		}

		$query_args = [
			'post_type'      => 'tutors',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'no_found_rows'  => true,
		];

		if ( ! empty( $params['subject'] ) ) {
			$query_args['tax_query'] = [
				[
					'taxonomy' => 'subject',
					'field'    => 'name',
					'terms'    => $params['subject'],
				],
			];
		}

		$query   = new WP_Query( $query_args );
		$results = [];

		// If strict subject filter returns nothing, widen to all published tutors.
		if ( ! $query->have_posts() && ! empty( $params['subject'] ) ) {
			unset( $query_args['tax_query'] );
			$query = new WP_Query( $query_args );
		}

		foreach ( $query->posts as $post ) {
			$score = self::score_post( $post->ID, $params );
			if ( $score < 10 ) {
				continue;
			}
			$province = wp_get_post_terms( $post->ID, 'province', [ 'fields' => 'names' ] );
			$results[] = [
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'excerpt'  => wp_trim_words( $post->post_excerpt ?: $post->post_content, 20 ),
				'url'      => get_permalink( $post ),
				'photo'    => get_the_post_thumbnail_url( $post, 'thumbnail' ) ?: '',
				'rate'     => (int) self::tutor_meta( $post->ID, 'hourly_rate', 0 ),
				'rating'   => (float) self::tutor_meta( $post->ID, 'rating', 0 ),
				'reviews'  => (int) self::tutor_meta( $post->ID, 'reviews', 0 ),
				'vetted'   => (bool) self::tutor_meta( $post->ID, 'vetted', false ),
				'score'    => $score,
				'subjects' => wp_get_post_terms( $post->ID, 'subject', [ 'fields' => 'names' ] ),
				'province' => is_wp_error( $province ) ? [] : $province,
				'formats'  => taxonomy_exists( 'learning_format' ) ? wp_get_post_terms( $post->ID, 'learning_format', [ 'fields' => 'names' ] ) : [],
			];
		}
		wp_reset_postdata();

		usort(
			$results,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice( $results, 0, 6 );
	}

	/**
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $params  Criteria.
	 * @return int
	 */
	private static function score_post( $post_id, $params ) {
		$score = 0;

		if ( ! empty( $params['subject'] ) ) {
			$subjects = wp_get_post_terms( $post_id, 'subject', [ 'fields' => 'names' ] );
			$subjects = is_wp_error( $subjects ) ? [] : $subjects;
			if ( in_array( $params['subject'], $subjects, true ) ) {
				$score += 40;
			} elseif ( self::partial_match( $params['subject'], $subjects ) ) {
				$score += 20;
			}
		}

		if ( ! empty( $params['grade'] ) ) {
			$grades = wp_get_post_terms( $post_id, 'grade', [ 'fields' => 'names' ] );
			$grades = is_wp_error( $grades ) ? [] : $grades;
			if ( in_array( $params['grade'], $grades, true ) ) {
				$score += 25;
			} elseif ( self::partial_match( $params['grade'], $grades ) ) {
				$score += 12;
			}
		}

		if ( ! empty( $params['province'] ) ) {
			$provinces = wp_get_post_terms( $post_id, 'province', [ 'fields' => 'names' ] );
			$provinces = is_wp_error( $provinces ) ? [] : $provinces;
			if ( in_array( $params['province'], $provinces, true ) ) {
				$score += 15;
			}
		}

		if ( ! empty( $params['format'] ) && taxonomy_exists( 'learning_format' ) ) {
			$formats = wp_get_post_terms( $post_id, 'learning_format', [ 'fields' => 'names' ] );
			$formats = is_wp_error( $formats ) ? [] : $formats;
			if ( in_array( $params['format'], $formats, true ) ) {
				$score += 10;
			}
		}

		$rate = (int) self::tutor_meta( $post_id, 'hourly_rate', 0 );
		if ( ! empty( $params['max_rate'] ) && $rate > 0 ) {
			if ( $rate <= (int) $params['max_rate'] ) {
				$score += 10;
			}
		} elseif ( empty( $params['max_rate'] ) ) {
			$score += 5;
		}

		return $score;
	}

	/**
	 * @param string   $needle   Needle.
	 * @param string[] $haystack Haystack.
	 * @return bool
	 */
	private static function partial_match( $needle, $haystack ) {
		$needle_lower = strtolower( $needle );
		foreach ( $haystack as $item ) {
			if ( false !== stripos( $item, $needle_lower ) || false !== stripos( $needle_lower, strtolower( $item ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param string $key     Key group.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	private static function tutor_meta( $post_id, $key, $default = '' ) {
		if ( function_exists( 'bi_tutor_meta' ) ) {
			return bi_tutor_meta( $post_id, $key, $default );
		}
		$map = [
			'hourly_rate' => [ 'tutor_rate', '_ngc_hourly_rate' ],
			'rating'      => [ 'tutor_average_rating', '_ngc_rating' ],
			'reviews'     => [ 'tutor_review_count', '_ngc_reviews' ],
			'vetted'      => [ 'tutor_vetted', '_ngc_vetted' ],
		];
		foreach ( (array) ( $map[ $key ] ?? [ $key ] ) as $meta_key ) {
			$val = get_post_meta( $post_id, $meta_key, true );
			if ( '' !== $val && null !== $val ) {
				return $val;
			}
		}
		return $default;
	}

	/**
	 * @param array<int, array<string, mixed>> $matches Matches.
	 * @param array<string, mixed>             $params  Criteria.
	 * @return string
	 */
	private static function render_matches( $matches, $params ) {
		if ( empty( $matches ) ) {
			return '<div class="ngc-empty">' . esc_html__( 'No exact matches found. Submit an intake form and we will match you personally within 24 hours.', 'nextgencompanion' )
				. '<br><a class="ngc-btn" href="' . esc_url( home_url( '/find-a-tutor/' ) ) . '">' . esc_html__( 'Manual matching →', 'nextgencompanion' ) . '</a></div>';
		}

		ob_start();
		echo '<p class="ngc-match-summary">' . sprintf(
			/* translators: 1: count 2: subject 3: grade */
			esc_html__( 'Found %1$d matching tutors for %2$s at %3$s level:', 'nextgencompanion' ),
			count( $matches ),
			'<strong>' . esc_html( $params['subject'] ?: __( 'your subject', 'nextgencompanion' ) ) . '</strong>',
			'<strong>' . esc_html( $params['grade'] ?: __( 'your level', 'nextgencompanion' ) ) . '</strong>'
		) . '</p>';
		echo '<div class="ngc-match-grid">';
		foreach ( $matches as $t ) {
			$pct     = min( 100, (int) $t['score'] );
			$pct_cls = $pct >= 75 ? 'high' : ( $pct >= 50 ? 'medium' : 'fair' );
			?>
			<div class="ngc-match-card">
				<div class="ngc-match-card-head">
					<?php if ( ! empty( $t['photo'] ) ) : ?>
						<img src="<?php echo esc_url( $t['photo'] ); ?>" alt="" class="ngc-match-photo" loading="lazy" />
					<?php else : ?>
						<span class="ngc-match-photo ngc-match-photo--ph"><?php echo esc_html( mb_substr( $t['title'], 0, 1 ) ); ?></span>
					<?php endif; ?>
					<div class="ngc-match-card-id">
						<strong><?php echo esc_html( $t['title'] ); ?></strong>
						<?php if ( ! empty( $t['province'][0] ) ) : ?><span><?php echo esc_html( $t['province'][0] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $t['vetted'] ) ) : ?><span class="ngc-vetted">✓ <?php esc_html_e( 'Vetted', 'nextgencompanion' ); ?></span><?php endif; ?>
						<?php
						$show_demo_badge = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( class_exists( 'NGC_Platform_Demo' ) && NGC_Platform_Demo::is_enabled() );
						if ( $show_demo_badge && class_exists( 'NGC_Tutor_Cpt_Source' ) && NGC_Tutor_Cpt_Source::is_demo_tutor( (int) ( $t['id'] ?? 0 ) ) ) :
							?>
							<span class="ngc-demo-badge"><?php esc_html_e( 'Demo', 'nextgencompanion' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="ngc-match-score ngc-match-score--<?php echo esc_attr( $pct_cls ); ?>">
						<span class="ngc-score-pct"><?php echo esc_html( (string) $pct ); ?>%</span>
						<span class="ngc-score-label"><?php esc_html_e( 'match', 'nextgencompanion' ); ?></span>
					</div>
				</div>
				<div class="ngc-match-bar-wrap"><div class="ngc-match-bar ngc-match-bar--<?php echo esc_attr( $pct_cls ); ?>" style="width:<?php echo esc_attr( (string) $pct ); ?>%"></div></div>
				<p class="ngc-match-bio"><?php echo esc_html( $t['excerpt'] ); ?></p>
				<div class="ngc-tutor-foot">
					<span class="ngc-tutor-rating">⭐ <?php echo esc_html( $t['rating'] ? (string) $t['rating'] : __( 'New', 'nextgencompanion' ) ); ?></span>
					<span class="ngc-tutor-rate"><?php echo $t['rate'] ? 'R' . esc_html( (string) $t['rate'] ) . '<small>/hr</small>' : esc_html__( 'Rate on request', 'nextgencompanion' ); ?></span>
				</div>
				<div class="ngc-tutor-cta">
					<a class="ngc-btn ngc-btn-alt" href="<?php echo esc_url( $t['url'] ); ?>"><?php esc_html_e( 'View Profile', 'nextgencompanion' ); ?></a>
					<a class="ngc-btn ngc-btn-accent" href="<?php echo esc_url( home_url( '/find-a-tutor/?tutor=' . $t['id'] ) ); ?>"><?php esc_html_e( 'Book This Tutor', 'nextgencompanion' ); ?></a>
				</div>
			</div>
			<?php
		}
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * @param string   $tax      Taxonomy.
	 * @param string[] $fallback Fallback terms.
	 * @return string[]
	 */
	private static function term_list( $tax, $fallback ) {
		if ( ! taxonomy_exists( $tax ) ) {
			return $fallback;
		}
		$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'names', 'orderby' => 'name' ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $fallback;
		}
		return $terms;
	}
}
