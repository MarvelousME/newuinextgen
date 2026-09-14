<?php
/**
 * Plugin Name: NextGen Subjects Widget
 * Description: Searchable subject grid powered by Companion taxonomy/CMS, theme tracks, or manual lines.
 * Version: 1.1.0
 * Author: NextGen Tutors
 * Text Domain: nextgen-subjects-widget
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'NGSW_VERSION', '1.1.0' );
define( 'NGSW_FILE', __FILE__ );
define( 'NGSW_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGSW_URL', plugin_dir_url( __FILE__ ) );

require_once NGSW_DIR . 'includes/class-ngsw-catalog.php';

/**
 * Plugin bootstrap.
 */
final class NextGen_Subjects_Widget_Plugin {

	public static function init(): void {
		add_action( 'widgets_init', [ self::class, 'register_widget' ] );
		add_action( 'wp_enqueue_scripts', [ self::class, 'register_assets' ] );
		add_shortcode( 'nextgen_subjects', [ self::class, 'shortcode' ] );
	}

	public static function register_widget(): void {
		register_widget( NextGen_Subjects_Widget::class );
	}

	public static function register_assets(): void {
		wp_register_style(
			'nextgen-subjects-widget',
			NGSW_URL . 'assets/css/nextgen-subjects-widget.css',
			[],
			NGSW_VERSION
		);

		wp_register_script(
			'nextgen-subjects-widget',
			NGSW_URL . 'assets/js/nextgen-subjects-widget.js',
			[],
			NGSW_VERSION,
			true
		);
	}

	/**
	 * Manual `subjects=` always wins when non-empty; `source=manual` forces parse-only.
	 *
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	public static function resolve_for_shortcode( string $subjects_attr, string $source_attr ): array {
		$source = strtolower( trim( $source_attr ) );
		$input  = str_replace( ',', PHP_EOL, $subjects_attr );

		if ( 'manual' === $source || '' !== trim( $subjects_attr ) ) {
			return NGSW_Catalog::resolve( $input, false );
		}

		return NGSW_Catalog::resolve( '', true );
	}

	/**
	 * @param array<string, mixed> $atts Shortcode attributes.
	 */
	public static function shortcode( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title'       => __( 'Explore Our Subjects', 'nextgen-subjects-widget' ),
				'subtitle'    => __( 'Find expert tutors across the subjects you need.', 'nextgen-subjects-widget' ),
				'subjects'    => '',
				'columns'     => '4',
				'show_search' => 'yes',
				'source'      => 'auto',
			],
			is_array( $atts ) ? $atts : [],
			'nextgen_subjects'
		);

		return self::render(
			self::resolve_for_shortcode( (string) $atts['subjects'], (string) $atts['source'] ),
			[
				'title'       => sanitize_text_field( (string) $atts['title'] ),
				'subtitle'    => sanitize_text_field( (string) $atts['subtitle'] ),
				'columns'     => self::sanitize_columns( $atts['columns'] ),
				'show_search' => 'yes' === $atts['show_search'],
			]
		);
	}

	/**
	 * @param mixed $columns Columns.
	 */
	public static function sanitize_columns( $columns ): int {
		$columns = absint( $columns );
		return in_array( $columns, [ 2, 3, 4, 5, 6 ], true ) ? $columns : 4;
	}

	/**
	 * @param array<int, array{name:string,description:string,icon:string,slug:string,url:string}> $subjects Subjects.
	 * @param array<string, mixed>                                                                  $settings Settings.
	 */
	public static function render( array $subjects, array $settings ): string {
		if ( ! $subjects ) {
			return '';
		}

		wp_enqueue_style( 'nextgen-subjects-widget' );
		wp_enqueue_script( 'nextgen-subjects-widget' );

		$instance_id = wp_unique_id( 'ng-subjects-' );
		$title       = sanitize_text_field( (string) ( $settings['title'] ?? __( 'Explore Our Subjects', 'nextgen-subjects-widget' ) ) );
		$subtitle    = sanitize_text_field( (string) ( $settings['subtitle'] ?? '' ) );
		$columns     = self::sanitize_columns( $settings['columns'] ?? 4 );
		$show_search = ! empty( $settings['show_search'] );

		ob_start();
		?>
		<section
			id="<?php echo esc_attr( $instance_id ); ?>"
			class="ng-subjects"
			data-nextgen-subjects
			style="--ng-subject-columns: <?php echo esc_attr( (string) $columns ); ?>;"
			aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-title"
		>
			<div class="ng-subjects__container">
				<header class="ng-subjects__header">
					<div class="ng-subjects__eyebrow">
						<span class="ng-subjects__eyebrow-dot" aria-hidden="true"></span>
						<?php esc_html_e( 'NextGen Tutors', 'nextgen-subjects-widget' ); ?>
					</div>

					<?php if ( '' !== $title ) : ?>
						<h2 id="<?php echo esc_attr( $instance_id ); ?>-title" class="ng-subjects__title">
							<?php echo esc_html( $title ); ?>
						</h2>
					<?php endif; ?>

					<?php if ( '' !== $subtitle ) : ?>
						<p class="ng-subjects__subtitle"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( $show_search ) : ?>
					<div class="ng-subjects__search-container">
						<label class="screen-reader-text" for="<?php echo esc_attr( $instance_id ); ?>-search">
							<?php esc_html_e( 'Search subjects', 'nextgen-subjects-widget' ); ?>
						</label>
						<span class="ng-subjects__search-icon" aria-hidden="true"><?php echo self::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input
							id="<?php echo esc_attr( $instance_id ); ?>-search"
							class="ng-subjects__search"
							type="search"
							placeholder="<?php esc_attr_e( 'Search subjects...', 'nextgen-subjects-widget' ); ?>"
							autocomplete="off"
							data-subject-search
						>
					</div>
				<?php endif; ?>

				<div class="ng-subjects__grid" data-subject-grid role="list">
					<?php foreach ( $subjects as $subject ) : ?>
						<?php
						$name        = $subject['name'];
						$description = $subject['description'];
						$url         = $subject['url'];
						$search      = strtolower( $name . ' ' . $description );
						?>
						<a
							class="ng-subject-card"
							href="<?php echo esc_url( $url ); ?>"
							data-subject-card
							data-subject-search-value="<?php echo esc_attr( $search ); ?>"
							role="listitem"
						>
							<span class="ng-subject-card__glow" aria-hidden="true"></span>
							<span class="ng-subject-card__icon" aria-hidden="true"><?php echo self::icon( $subject['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="ng-subject-card__content">
								<span class="ng-subject-card__title"><?php echo esc_html( $name ); ?></span>
								<span class="ng-subject-card__description"><?php echo esc_html( $description ); ?></span>
							</span>
							<span class="ng-subject-card__arrow" aria-hidden="true"><?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="ng-subjects__empty" data-subject-empty hidden>
					<div class="ng-subjects__empty-icon" aria-hidden="true"><?php echo self::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<strong><?php esc_html_e( 'No subjects found', 'nextgen-subjects-widget' ); ?></strong>
					<span><?php esc_html_e( 'Try another search term.', 'nextgen-subjects-widget' ); ?></span>
				</div>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	public static function icon( string $icon ): string {
		$icons = [
			'calculator' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8M8 10h2M14 10h2M8 14h2M14 14h2M8 18h2M14 18h2"/></svg>',
			'book'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>',
			'atom'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="1"/><path d="M20.2 7.8c1.9 3.3-1.8 8.5-6.2 11s-10 1.6-11.8-1.7 1.8-8.5 6.2-11 10-1.6 11.8 1.7Z"/><path d="M20.2 16.2c-1.9 3.3-7.4 2.2-11.8-.3s-8.1-7.7-6.2-11S9.6 2.7 14 5.2s8.1 7.7 6.2 11Z"/></svg>',
			'flask'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 18l-5-9V3"/><path d="M7.5 15h9"/></svg>',
			'leaf'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 4.4 20 5 20 5s.6 4.5-1.1 10.2A7 7 0 0 1 11 20Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6.94C9.2 13 12 12 16 12"/></svg>',
			'globe'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>',
			'landmark'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18M2 18h20"/></svg>',
			'chart'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 16 4-5 4 3 5-7"/></svg>',
			'code'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/></svg>',
			'cpu'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/></svg>',
			'message'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
			'search'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
			'arrow'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
		];

		return $icons[ $icon ] ?? $icons['book'];
	}
}

/**
 * Classic WP_Widget wrapper.
 */
final class NextGen_Subjects_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'nextgen_subjects_widget',
			__( 'NextGen Subjects', 'nextgen-subjects-widget' ),
			[
				'classname'   => 'nextgen-subjects-widget',
				'description' => __( 'Responsive searchable subjects from live catalog or manual lines.', 'nextgen-subjects-widget' ),
			]
		);
	}

	/**
	 * @param array<string, mixed> $args     Widget args.
	 * @param array<string, mixed> $instance Instance.
	 */
	public function widget( $args, $instance ): void {
		$use_live = ! isset( $instance['use_live'] ) || ! empty( $instance['use_live'] );
		$manual   = (string) ( $instance['subjects'] ?? '' );

		if ( '' !== trim( $manual ) ) {
			$subjects = NGSW_Catalog::resolve( $manual, false );
		} else {
			$subjects = NGSW_Catalog::resolve( '', $use_live );
		}

		$settings = [
			'title'       => sanitize_text_field( (string) ( $instance['title'] ?? __( 'Explore Our Subjects', 'nextgen-subjects-widget' ) ) ),
			'subtitle'    => sanitize_text_field( (string) ( $instance['subtitle'] ?? '' ) ),
			'columns'     => NextGen_Subjects_Widget_Plugin::sanitize_columns( $instance['columns'] ?? 4 ),
			'show_search' => ! empty( $instance['show_search'] ),
		];

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo NextGen_Subjects_Widget_Plugin::render( $subjects, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array<string, mixed> $new_instance New.
	 * @param array<string, mixed> $old_instance Old.
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return [
			'title'       => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'subtitle'    => sanitize_text_field( (string) ( $new_instance['subtitle'] ?? '' ) ),
			'subjects'    => sanitize_textarea_field( (string) ( $new_instance['subjects'] ?? '' ) ),
			'columns'     => NextGen_Subjects_Widget_Plugin::sanitize_columns( $new_instance['columns'] ?? 4 ),
			'show_search' => ! empty( $new_instance['show_search'] ) ? 1 : 0,
			'use_live'    => ! empty( $new_instance['use_live'] ) ? 1 : 0,
		];
	}

	/**
	 * @param array<string, mixed> $instance Instance.
	 */
	public function form( $instance ): void {
		$defaults = [
			'title'       => __( 'Explore Our Subjects', 'nextgen-subjects-widget' ),
			'subtitle'    => __( 'Expert tutors. Personalised learning. Better results.', 'nextgen-subjects-widget' ),
			'columns'     => 4,
			'show_search' => 1,
			'use_live'    => 1,
			'subjects'    => '',
		];
		$instance = wp_parse_args( $instance, $defaults );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'nextgen-subjects-widget' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( (string) $instance['title'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>"><?php esc_html_e( 'Subtitle:', 'nextgen-subjects-widget' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subtitle' ) ); ?>" type="text" value="<?php echo esc_attr( (string) $instance['subtitle'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Columns:', 'nextgen-subjects-widget' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>">
				<?php foreach ( [ 2, 3, 4, 5, 6 ] as $column ) : ?>
					<option value="<?php echo esc_attr( (string) $column ); ?>" <?php selected( (int) $instance['columns'], $column ); ?>><?php echo esc_html( (string) $column ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'use_live' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'use_live' ) ); ?>" value="1" <?php checked( ! empty( $instance['use_live'] ) ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'use_live' ) ); ?>"><?php esc_html_e( 'Use live catalog when Subjects field is empty (taxonomy → Companion CMS → theme tracks)', 'nextgen-subjects-widget' ); ?></label>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_search' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_search' ) ); ?>" value="1" <?php checked( ! empty( $instance['show_search'] ) ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_search' ) ); ?>"><?php esc_html_e( 'Show subject search', 'nextgen-subjects-widget' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'subjects' ) ); ?>"><?php esc_html_e( 'Subjects (optional override):', 'nextgen-subjects-widget' ); ?></label>
			<textarea class="widefat" rows="10" id="<?php echo esc_attr( $this->get_field_id( 'subjects' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subjects' ) ); ?>"><?php echo esc_textarea( (string) $instance['subjects'] ); ?></textarea>
			<small><?php esc_html_e( 'One per line: Subject|Description|icon. Leave empty to use the live catalog.', 'nextgen-subjects-widget' ); ?></small>
		</p>
		<?php
	}
}

NextGen_Subjects_Widget_Plugin::init();
