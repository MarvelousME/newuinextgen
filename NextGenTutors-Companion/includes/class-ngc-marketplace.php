<?php
/**
 * Tutor marketplace — search, filters, shortcode, and REST-backed UI.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full marketplace engine (CPT queries + AJAX UI).
 */
class NGC_Marketplace {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
		add_action( 'admin_notices', [ __CLASS__, 'dependency_notice' ] );
	}

	/**
	 * Register shortcodes.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'ngc_tutor_carousel', [ __CLASS__, 'render_carousel' ] );
		add_shortcode( 'ngt_tutor_carousel', [ __CLASS__, 'render_carousel' ] );
		add_shortcode( 'ngc_tutor_marketplace', [ __CLASS__, 'render_marketplace' ] );
	}

	/**
	 * Register scripts (enqueued when shortcode renders).
	 */
	public static function register_assets() {
		$style_deps = defined( 'BI_VERSION' ) ? [ 'bi-nbi-infinity' ] : [];
		wp_register_style(
			'ngc-marketplace',
			NGC_PLUGIN_URL . 'assets/css/ngc-marketplace.css',
			$style_deps,
			NGC_VERSION
		);
		wp_register_script(
			'ngc-marketplace',
			NGC_PLUGIN_URL . 'assets/js/ngc-marketplace.js',
			[],
			NGC_VERSION,
			true
		);
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public static function query_tutors( $args = [] ) {
		if ( ! post_type_exists( 'tutors' ) ) {
			return [
				'items'    => [],
				'total'    => 0,
				'page'     => 1,
				'per_page' => 12,
				'pages'    => 0,
			];
		}

		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 48, (int) ( $args['per_page'] ?? 12 ) ) );
		$sort     = sanitize_key( (string) ( $args['sort'] ?? 'rating' ) );

		$query_args = [
			'post_type'      => 'tutors',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		];

		$demo_filter = self::exclude_demo_meta_query();
		if ( $demo_filter ) {
			$query_args['meta_query'] = [ $demo_filter ];
		}

		$tax_query = [];
		foreach (
			[
				'subject'  => sanitize_title( (string) ( $args['subject'] ?? '' ) ),
				'grade'    => sanitize_title( (string) ( $args['grade'] ?? '' ) ),
				'province' => sanitize_title( (string) ( $args['province'] ?? '' ) ),
			] as $tax => $slug
		) {
			if ( $slug ) {
				$tax_query[] = [
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => $slug,
				];
			}
		}
		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = array_merge( [ 'relation' => 'AND' ], $tax_query );
		}

		$format = sanitize_key( (string) ( $args['format'] ?? '' ) );
		if ( $format && taxonomy_exists( 'learning_format' ) ) {
			$query_args['tax_query']   = $query_args['tax_query'] ?? [];
			$query_args['tax_query'][] = [
				'taxonomy' => 'learning_format',
				'field'    => 'slug',
				'terms'    => $format,
			];
		}

		$min_price = isset( $args['min_price'] ) ? (int) $args['min_price'] : 0;
		$max_price = isset( $args['max_price'] ) ? (int) $args['max_price'] : 0;
		if ( $min_price > 0 || $max_price > 0 ) {
			$price_keys = [ 'hourly_rate', 'tutor_rate', '_ngc_hourly_rate' ];
			$price_or   = [ 'relation' => 'OR' ];
			foreach ( $price_keys as $rate_key ) {
				$clause = [
					'key'  => $rate_key,
					'type' => 'NUMERIC',
				];
				if ( $min_price > 0 && $max_price > 0 ) {
					$clause['value']   = [ $min_price, $max_price ];
					$clause['compare'] = 'BETWEEN';
				} elseif ( $min_price > 0 ) {
					$clause['value']   = $min_price;
					$clause['compare'] = '>=';
				} else {
					$clause['value']   = $max_price;
					$clause['compare'] = '<=';
				}
				$price_or[] = $clause;
			}
			$query_args['meta_query']   = $query_args['meta_query'] ?? [];
			$query_args['meta_query'][] = $price_or;
		}

		if ( ! empty( $args['verified'] ) ) {
			$query_args['meta_query']   = $query_args['meta_query'] ?? [];
			$query_args['meta_query'][] = [
				'relation' => 'OR',
				[
					'key'     => 'vetted',
					'value'   => '1',
					'compare' => '=',
				],
				[
					'key'     => 'tutor_vetted',
					'value'   => '1',
					'compare' => '=',
				],
				[
					'key'     => '_ngc_vetted',
					'value'   => '1',
					'compare' => '=',
				],
			];
		}

		$search = sanitize_text_field( (string) ( $args['q'] ?? '' ) );
		if ( $search ) {
			$query_args['s'] = $search;
		}

		switch ( $sort ) {
			case 'price_low':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = 'hourly_rate';
				$query_args['order']    = 'ASC';
				break;
			case 'price_high':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = 'hourly_rate';
				$query_args['order']    = 'DESC';
				break;
			case 'newest':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
			default:
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = 'tutor_average_rating';
				$query_args['order']    = 'DESC';
				break;
		}

		$q = new WP_Query( $query_args );
		$items = [];
		foreach ( $q->posts as $post ) {
			$items[] = self::format_tutor( $post );
		}

		return [
			'items'    => $items,
			'total'    => (int) $q->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'pages'    => (int) $q->max_num_pages,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function filter_options() {
		$opts = [
			'subjects'  => [],
			'grades'    => [],
			'provinces' => [],
			'formats'   => [],
			'sort'      => [
				[ 'value' => 'rating', 'label' => __( 'Top rated', 'nextgencompanion' ) ],
				[ 'value' => 'price_low', 'label' => __( 'Price: low to high', 'nextgencompanion' ) ],
				[ 'value' => 'price_high', 'label' => __( 'Price: high to low', 'nextgencompanion' ) ],
				[ 'value' => 'newest', 'label' => __( 'Newest', 'nextgencompanion' ) ],
			],
		];

		foreach ( [ 'subject', 'grade', 'province', 'learning_format' ] as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$terms = get_terms(
				[
					'taxonomy'   => $tax,
					'hide_empty' => false,
				]
			);
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			$key = 'learning_format' === $tax ? 'formats' : ( $tax . 's' );
			foreach ( $terms as $term ) {
				$opts[ $key ][] = [
					'value' => $term->slug,
					'label' => $term->name,
				];
			}
		}

		return $opts;
	}

	/**
	 * @param WP_Post $post Tutor post.
	 * @return array<string, mixed>
	 */
	public static function format_tutor( $post ) {
		if ( function_exists( 'bi_format_tutor_post' ) ) {
			return bi_format_tutor_post( $post );
		}

		$id       = $post->ID;
		$subjects = wp_get_post_terms( $id, 'subject', [ 'fields' => 'names' ] );

		return [
			'postId'     => $id,
			'name'       => $post->post_title,
			'imageUrl'   => get_the_post_thumbnail_url( $id, 'medium' ) ?: '',
			'rating'     => (float) get_post_meta( $id, 'tutor_average_rating', true ) ?: 4.8,
			'reviews'    => (int) get_post_meta( $id, 'tutor_reviews_count', true ),
			'hourlyRate' => (int) get_post_meta( $id, 'hourly_rate', true ) ?: 320,
			'subjects'   => is_wp_error( $subjects ) ? [] : (array) $subjects,
			'province'   => '',
			'groupType'  => (string) get_post_meta( $id, 'mode', true ) ?: 'both',
			'vetted'     => (bool) get_post_meta( $id, 'vetted', true ),
			'permalink'  => get_permalink( $id ),
			'url'        => get_permalink( $id ),
			'bio'        => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ), 30 ),
		];
	}

	/**
	 * Exclude demo-seeded tutors from public marketplace in production.
	 *
	 * @return array<string, mixed>|null Null when demo tutors should be visible.
	 */
	private static function exclude_demo_meta_query() {
		if ( class_exists( 'NGC_Tutor_Seeder' ) && NGC_Tutor_Seeder::demo_seed_allowed() ) {
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
	 * [ngc_tutor_marketplace] — AJAX filterable directory.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function render_marketplace( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'per_page' => '12',
			],
			is_array( $atts ) ? $atts : [],
			'ngc_tutor_marketplace'
		);

		wp_enqueue_style( 'ngc-marketplace' );
		if ( defined( 'BI_VERSION' ) ) {
			wp_enqueue_style( 'bi-nbi-infinity' );
			wp_enqueue_script( 'bi-nbi-infinity' );
		}
		wp_enqueue_script( 'ngc-marketplace' );
		wp_localize_script(
			'ngc-marketplace',
			'ngcMarketplace',
			[
				'restRoot'  => esc_url_raw( rest_url() ),
				'namespace' => 'ngc/v1',
				'perPage'   => (int) $atts['per_page'],
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'i18n'      => [
					'loading'  => __( 'Searching tutors…', 'nextgencompanion' ),
					'empty'    => __( 'No tutors matched your filters. Try broadening your search or request a personal match.', 'nextgencompanion' ),
					'error'    => __( 'Could not load tutors. Please try again.', 'nextgencompanion' ),
					'results'  => __( 'tutors found', 'nextgencompanion' ),
					'filters'  => __( 'Filters', 'nextgencompanion' ),
					'view'     => __( 'View profile', 'nextgencompanion' ),
					'book'     => __( 'Book Session', 'nextgencompanion' ),
					'match'    => __( 'Match', 'nextgencompanion' ),
					'available'=> __( 'Available', 'nextgencompanion' ),
					'unavailable' => __( 'Busy', 'nextgencompanion' ),
					'verified' => __( 'Verified', 'nextgencompanion' ),
				],
			]
		);

		ob_start();
		?>
		<div class="ngc-marketplace nbi-infinity-market" data-ngc-marketplace>
			<div class="ngc-marketplace__toolbar">
				<input type="search" class="ngc-marketplace__search" placeholder="<?php esc_attr_e( 'Subject, name, or keyword…', 'nextgencompanion' ); ?>" aria-label="<?php esc_attr_e( 'Search tutors', 'nextgencompanion' ); ?>" />
				<button type="button" class="ngc-marketplace__filters-toggle" aria-expanded="false"><?php esc_html_e( 'Filters', 'nextgencompanion' ); ?></button>
				<select class="ngc-marketplace__sort" aria-label="<?php esc_attr_e( 'Sort tutors', 'nextgencompanion' ); ?>">
					<option value="rating"><?php esc_html_e( 'Top rated', 'nextgencompanion' ); ?></option>
					<option value="price_low"><?php esc_html_e( 'Price: low to high', 'nextgencompanion' ); ?></option>
					<option value="price_high"><?php esc_html_e( 'Price: high to low', 'nextgencompanion' ); ?></option>
					<option value="newest"><?php esc_html_e( 'Newest', 'nextgencompanion' ); ?></option>
				</select>
			</div>
			<div class="ngc-marketplace__layout">
				<aside class="ngc-marketplace__filters" aria-label="<?php esc_attr_e( 'Tutor filters', 'nextgencompanion' ); ?>">
					<div class="ngc-marketplace__filter" data-filter="subject"><label><?php esc_html_e( 'Subject', 'nextgencompanion' ); ?></label><select data-field="subject"><option value=""><?php esc_html_e( 'Any', 'nextgencompanion' ); ?></option></select></div>
					<div class="ngc-marketplace__filter" data-filter="grade"><label><?php esc_html_e( 'Grade', 'nextgencompanion' ); ?></label><select data-field="grade"><option value=""><?php esc_html_e( 'Any', 'nextgencompanion' ); ?></option></select></div>
					<div class="ngc-marketplace__filter" data-filter="province"><label><?php esc_html_e( 'Province', 'nextgencompanion' ); ?></label><select data-field="province"><option value=""><?php esc_html_e( 'Any', 'nextgencompanion' ); ?></option></select></div>
					<div class="ngc-marketplace__filter" data-filter="format"><label><?php esc_html_e( 'Format', 'nextgencompanion' ); ?></label><select data-field="format"><option value=""><?php esc_html_e( 'Any', 'nextgencompanion' ); ?></option></select></div>
					<div class="ngc-marketplace__filter"><label><?php esc_html_e( 'Max price (R/hr)', 'nextgencompanion' ); ?></label><input type="number" min="0" step="50" data-field="max_price" placeholder="500" /></div>
					<label class="ngc-marketplace__check"><input type="checkbox" data-field="verified" value="1" /> <?php esc_html_e( 'Verified tutors only', 'nextgencompanion' ); ?></label>
				</aside>
				<div class="ngc-marketplace__results">
					<p class="ngc-marketplace__status" aria-live="polite"></p>
					<div class="ngc-marketplace__grid" role="list"></div>
					<nav class="ngc-marketplace__pagination" aria-label="<?php esc_attr_e( 'Pagination', 'nextgencompanion' ); ?>"></nav>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * [ngc_tutor_carousel] — delegates to theme carousel when available.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function render_carousel( $atts ) {
		$atts = is_array( $atts ) ? $atts : [];

		if ( shortcode_exists( 'bi_tutors_carousel' ) ) {
			return (string) do_shortcode( '[bi_tutors_carousel' . self::atts_to_string( $atts ) . ']' );
		}

		if ( function_exists( 'bi_render_tutors_carousel' ) ) {
			ob_start();
			bi_render_tutors_carousel(
				[
					'eyebrow'  => sanitize_text_field( $atts['eyebrow'] ?? '' ),
					'title'    => sanitize_text_field( $atts['title'] ?? __( 'Featured Tutors', 'nextgencompanion' ) ),
					'subtitle' => sanitize_textarea_field( $atts['subtitle'] ?? '' ),
					'limit'    => max( 3, min( 12, (int) ( $atts['limit'] ?? 8 ) ) ),
				]
			);
			return (string) ob_get_clean();
		}

		return '<p class="ngc-marketplace-missing">' . esc_html__( 'Tutor carousel requires the BeyondInfinity theme.', 'nextgencompanion' ) . '</p>';
	}

	/**
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	private static function atts_to_string( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}
		$parts = [];
		foreach ( $atts as $key => $value ) {
			$parts[] = sanitize_key( $key ) . '="' . esc_attr( (string) $value ) . '"';
		}
		return $parts ? ' ' . implode( ' ', $parts ) : '';
	}

	/**
	 * Admin notice when theme carousel is unavailable.
	 */
	public static function dependency_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( shortcode_exists( 'bi_tutors_carousel' ) || function_exists( 'bi_render_tutors_carousel' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		echo '<div class="notice notice-info"><p>';
		esc_html_e( 'NextGen Companion: [ngc_tutor_carousel] is registered but BeyondInfinity theme carousel helpers are not loaded.', 'nextgencompanion' );
		echo '</p></div>';
	}
}
