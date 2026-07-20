<?php
/**
 * NEXTGEN Beyond-Infinity design system — enqueue + render helpers.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether infinity design assets should load on this request.
 */
function bi_nbi_infinity_active() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return false;
	}
	if ( function_exists( 'bi_is_kinetic_home' ) && bi_is_kinetic_home() ) {
		return true;
	}
	if ( function_exists( 'bi_uses_kinetic_surface' ) && bi_uses_kinetic_surface() ) {
		return true;
	}
	if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
		return true;
	}
	$pages = [ 'find-a-tutor', 'tutor-marketplace', 'pricing', 'about', 'register', 'login' ];
	if ( is_page( $pages ) || is_post_type_archive( 'tutors' ) ) {
		return true;
	}
	global $post;
	if ( $post instanceof WP_Post ) {
		if ( has_shortcode( $post->post_content, 'ngc_tutor_marketplace' ) ) {
			return true;
		}
	}
	return (bool) apply_filters( 'bi_nbi_infinity_active', false );
}

/**
 * @param array<string, mixed> $classes Body classes.
 * @return array<string, mixed>
 */
function bi_nbi_infinity_body_class( $classes ) {
	if ( bi_nbi_infinity_active() ) {
		$classes[] = 'nbi-infinity';
	}
	if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
		$classes[] = 'nbi-command-surface';
	}
	return $classes;
}
add_filter( 'body_class', 'bi_nbi_infinity_body_class' );

/**
 * Register Beyond-Infinity assets (enqueue when active).
 */
function bi_nbi_infinity_register() {
	if ( is_admin() ) {
		return;
	}

	$deps = [ 'bi-style' ];
	if ( wp_style_is( 'ng-ui-components', 'registered' ) ) {
		$deps[] = 'ng-ui-components';
	}

	wp_register_style(
		'bi-nbi-infinity',
		BI_URI . '/assets/css/nbi-infinity.css',
		$deps,
		BI_VERSION
	);

	wp_register_script(
		'bi-nbi-infinity',
		BI_URI . '/assets/js/nbi-infinity.js',
		[],
		BI_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bi_nbi_infinity_register', 20 );

/**
 * Enqueue Beyond-Infinity design system assets.
 */
function bi_nbi_infinity_assets() {
	if ( ! bi_nbi_infinity_active() ) {
		return;
	}

	wp_enqueue_style( 'bi-nbi-infinity' );
	wp_enqueue_script( 'bi-nbi-infinity' );

	wp_localize_script(
		'bi-nbi-infinity',
		'nbiInfinity',
		[
			'i18n' => [
				'findTutor'   => __( 'Find Your Perfect Tutor', 'beyondinfinity' ),
				'bookSession' => __( 'Book Session', 'beyondinfinity' ),
				'close'       => __( 'Close', 'beyondinfinity' ),
			],
		]
	);
}
add_action( 'wp_enqueue_scripts', 'bi_nbi_infinity_assets', 38 );

/**
 * Aurora constellation canvas for cinematic heroes.
 *
 * @param array<string, mixed> $args Optional id/class.
 */
function bi_nbi_render_constellation( $args = [] ) {
	$args = wp_parse_args(
		$args,
		[
			'id'    => 'nbi-constellation',
			'class' => 'nbi-constellation',
		]
	);
	printf(
		'<canvas id="%s" class="%s" aria-hidden="true" data-nbi-constellation></canvas>',
		esc_attr( sanitize_html_class( $args['id'] ) ),
		esc_attr( $args['class'] )
	);
}

/**
 * Liquid-glass AI search overlay (hero marketing).
 *
 * @param string $action Form action URL.
 */
function bi_nbi_liquid_glass_search( $action = '' ) {
	$action = $action ? $action : home_url( '/find-a-tutor' );
	?>
	<div class="nbi-liquid-glass nbi-glass-search nbi-reveal" data-nbi-glass-search>
		<p class="nbi-glass-search__kicker"><?php esc_html_e( 'AI-assisted matching', 'beyondinfinity' ); ?></p>
		<h2 class="nbi-glass-search__title"><?php esc_html_e( 'Find Your Perfect Tutor', 'beyondinfinity' ); ?></h2>
		<form class="nbi-glass-search__form" action="<?php echo esc_url( $action ); ?>" method="get" role="search">
			<label class="nbi-sr-only" for="nbi-glass-q"><?php esc_html_e( 'Search tutors', 'beyondinfinity' ); ?></label>
			<input id="nbi-glass-q" name="q" type="search" class="nbi-glass-search__input" placeholder="<?php esc_attr_e( 'Subject, grade, or tutor name…', 'beyondinfinity' ); ?>" autocomplete="off" />
			<button type="submit" class="nbi-btn nbi-btn--magnetic nbi-glass-search__submit"><?php esc_html_e( 'Search', 'beyondinfinity' ); ?></button>
		</form>
		<div class="nbi-glass-search__chips" aria-label="<?php esc_attr_e( 'Popular subjects', 'beyondinfinity' ); ?>">
			<?php
			foreach ( [ 'Mathematics', 'Physical Science', 'English', 'Accounting', 'Programming' ] as $chip ) :
				?>
				<a class="nbi-chip" href="<?php echo esc_url( add_query_arg( 'q', rawurlencode( $chip ), $action ) ); ?>"><?php echo esc_html( $chip ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Bento dashboard shell — REST client hydrates inner widgets.
 *
 * @param string $role student|parent|tutor|admin.
 */
function bi_nbi_bento_shell( $role = 'student' ) {
	$role = sanitize_key( $role );
	$labels = [
		'wallet'   => __( 'Lesson Wallet', 'beyondinfinity' ),
		'progress' => __( 'Progress', 'beyondinfinity' ),
		'homework' => __( 'Homework Timeline', 'beyondinfinity' ),
	];
	?>
	<div class="nbi-bento nbi-bento--<?php echo esc_attr( $role ); ?>" data-nbi-bento aria-label="<?php esc_attr_e( 'Dashboard overview', 'beyondinfinity' ); ?>">
		<article class="nbi-bento__cell nbi-bento__cell--wallet nbi-glass-panel">
			<h2 class="nbi-bento__heading"><?php echo esc_html( $labels['wallet'] ); ?></h2>
			<p class="nbi-bento__mono" data-nbi-wallet-balance>—</p>
			<div class="nbi-bento__spark" aria-hidden="true"></div>
		</article>
		<article class="nbi-bento__cell nbi-bento__cell--rings nbi-glass-panel">
			<h2 class="nbi-bento__heading"><?php echo esc_html( $labels['progress'] ); ?></h2>
			<div class="nbi-bento__rings" data-nbi-progress-rings>
				<div class="nbi-ring" data-nbi-ring="tasks"><span class="nbi-ring__label"><?php esc_html_e( 'Tasks', 'beyondinfinity' ); ?></span><canvas width="120" height="120" aria-hidden="true"></canvas></div>
				<div class="nbi-ring" data-nbi-ring="sessions"><span class="nbi-ring__label"><?php esc_html_e( 'Sessions', 'beyondinfinity' ); ?></span><canvas width="120" height="120" aria-hidden="true"></canvas></div>
			</div>
		</article>
		<article class="nbi-bento__cell nbi-bento__cell--timeline nbi-glass-panel">
			<h2 class="nbi-bento__heading"><?php echo esc_html( $labels['homework'] ); ?></h2>
			<ol class="nbi-timeline" data-nbi-homework-timeline>
				<li class="nbi-timeline__item"><h3><?php esc_html_e( 'Upcoming', 'beyondinfinity' ); ?></h3><p data-nbi-timeline-upcoming>—</p></li>
				<li class="nbi-timeline__item"><h3><?php esc_html_e( 'In progress', 'beyondinfinity' ); ?></h3><p data-nbi-timeline-active>—</p></li>
				<li class="nbi-timeline__item"><h3><?php esc_html_e( 'Completed', 'beyondinfinity' ); ?></h3><p data-nbi-timeline-done>—</p></li>
			</ol>
		</article>
	</div>
	<?php
}
