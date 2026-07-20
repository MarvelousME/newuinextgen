<?php
/**
 * Generic helpers shared across templates.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route map — mirrors the React app's `currentPage` routes.
 *
 * Used by inc/setup.php to auto-create pages on activation and documented in
 * README.md (Route → Template mapping table).
 *
 * @return array<string,array>
 */
function ngt_route_map() {
	return array(
		'home' => array(
			'title'    => 'Home',
			'template' => 'page-templates/template-home.php',
		),
		'find-a-tutor' => array(
			'title'    => 'Find a Tutor',
			'template' => 'page-templates/template-full-width.php',
			'content'  => '<!-- wp:paragraph --><p>The interactive tutor-match directory and intake form load below. To embed the live booking widget, add your shortcode here (e.g. [ngc_find_tutor_form]).</p><!-- /wp:paragraph -->',
		),
		'become-a-tutor' => array(
			'title'    => 'Become a Tutor',
			'template' => 'page-templates/template-full-width.php',
			'content'  => '<!-- wp:paragraph --><p>Educator onboarding &amp; earnings calculator. Application shortcode zone: [ngc_become_tutor_form].</p><!-- /wp:paragraph -->',
		),
		'pricing' => array(
			'title'    => 'Pricing',
			'template' => 'page-templates/template-full-width.php',
		),
		'vetting' => array(
			'title'    => 'Tutor Vetting',
			'template' => 'page-templates/template-full-width.php',
		),
		'safety' => array(
			'title'    => 'Safety Guide',
			'template' => 'page-templates/template-full-width.php',
		),
		'about' => array(
			'title'    => 'About',
			'template' => 'page-templates/template-full-width.php',
		),
		'contact' => array(
			'title'    => 'Contact',
			'template' => 'page-templates/template-full-width.php',
		),
		'login' => array(
			'title'    => 'Log In',
			'template' => 'page-templates/template-app.php',
		),
		'register' => array(
			'title'    => 'Register',
			'template' => 'page-templates/template-app.php',
		),
		'dashboard' => array(
			'title'    => 'My Dashboard',
			'template' => 'page-templates/template-app.php',
		),
	);
}

/**
 * Integration contract bridge.
 * Maps a page slug to the BeyondInfinity-Companion shortcode that should render
 * inside it. The theme renders these via ngt_render_route_app() with a graceful
 * static fallback when the plugin is inactive.
 *
 * @return array<string,string>
 */
function ngt_route_shortcodes() {
	return array(
		'find-a-tutor'   => '[ngc_match_tutor]',
		'become-a-tutor' => '[ngc_become_tutor_form]',
		'contact'        => '[ngc_contact_support_form]',
		'login'          => '[ngc_login_form]',
		'register'       => '[ngc_register_tabs]',
		'dashboard'      => '[ngc_dashboard]',
	);
}

/**
 * Resolve a marketing/app route slug to its canonical front-end URL.
 *
 * @param string $slug Route key from ngt_route_map() (e.g. find-a-tutor).
 * @return string
 */
function ngt_route_url( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug || 'home' === $slug ) {
		return home_url( '/' );
	}
	if ( isset( ngt_route_map()[ $slug ] ) ) {
		return home_url( '/' . $slug . '/' );
	}
	return home_url( '/' . ltrim( $slug, '/' ) . '/' );
}

/**
 * Default CTA label for a route slug.
 *
 * @param string $slug Route key.
 * @return string
 */
function ngt_cta_default_label( $slug ) {
	$labels = array(
		'find-a-tutor'   => __( 'Find a Tutor', 'nextgen-tutors' ),
		'become-a-tutor' => __( 'Become a Tutor', 'nextgen-tutors' ),
		'pricing'        => __( 'View Pricing', 'nextgen-tutors' ),
		'vetting'        => __( 'Our Vetting Process', 'nextgen-tutors' ),
		'contact'        => __( 'Contact Us', 'nextgen-tutors' ),
	);
	return isset( $labels[ $slug ] ) ? $labels[ $slug ] : __( 'Learn More', 'nextgen-tutors' );
}

/**
 * Render a primary CTA button linking to a theme route.
 *
 * @param string $slug  Route key from ngt_route_map().
 * @param string $label Optional button text; falls back to ngt_cta_default_label().
 */
function ngt_cta_button( $slug, $label = '' ) {
	$slug  = sanitize_key( (string) $slug );
	$label = '' !== $label ? $label : ngt_cta_default_label( $slug );
	printf(
		'<a class="ngt-btn ngt-btn-primary" href="%1$s">%2$s</a>',
		esc_url( ngt_route_url( $slug ) ),
		esc_html( $label )
	);
}

/**
 * Reusable section heading partial (eyebrow + title + sub).
 *
 * @param string $eyebrow Optional eyebrow text.
 * @param string $title   Section title.
 * @param string $sub     Optional subtitle.
 * @param string $align   center|left.
 */
function ngt_section_heading( $eyebrow, $title, $sub = '', $align = 'center' ) {
	get_template_part(
		'template-parts/components/section-heading',
		null,
		array(
			'eyebrow' => $eyebrow,
			'title'   => $title,
			'sub'     => $sub,
			'align'   => $align,
		)
	);
}

/**
 * Theme mod with documented defaults (Customizer bridge).
 *
 * @param string $key     Theme mod key.
 * @param string $default Fallback when unset.
 * @return string
 */
function ngt_mod( $key, $default = '' ) {
	$defaults = array(
		'ngt_cta_secondary' => __( 'Become a Tutor', 'nextgen-tutors' ),
	);
	$fallback = isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
	return (string) get_theme_mod( $key, $fallback );
}

/**
 * Is the BeyondInfinity-Companion plugin active and booted?
 *
 * @return bool
 */
function ngt_plugin_active() {
	return defined( 'NGC_VERSION' ) || class_exists( 'NGC_Plugin' );
}

/**
 * Render the plugin shortcode mapped to the current/queried page, if any.
 * Falls back to a friendly notice (admins) / nothing (visitors) when the plugin
 * is not active so the page never appears broken.
 *
 * @param string $slug Page slug.
 */
function ngt_render_route_app( $slug ) {
	$map = ngt_route_shortcodes();
	if ( empty( $map[ $slug ] ) ) {
		return;
	}
	$shortcode = $map[ $slug ];
	$tag       = trim( $shortcode, '[]' );
	$tag       = explode( ' ', $tag )[0];

	if ( ngt_plugin_active() && shortcode_exists( $tag ) ) {
		echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is escaped by the plugin.
		return;
	}

	// Graceful fallback — never a fatal or blank app screen.
	echo '<div class="ngt-plugin-fallback">';
	echo '<p>' . esc_html__( 'This interactive area is powered by the BeyondInfinity-Companion plugin.', 'nextgen-tutors' ) . '</p>';
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<p><strong>' . esc_html__( 'Admin notice:', 'nextgen-tutors' ) . '</strong> ' . esc_html__( 'Install &amp; activate “BeyondInfinity-Companion (Unified)” to enable forms, dashboards and the live tutor marketplace.', 'nextgen-tutors' ) . '</p>';
	}
	echo '</div>';
}

/**
 * Tutor data for template parts.
 *
 * Reads LIVE data from the tutors CPT when available. Demo roster is returned
 * only when NGC_Platform_Demo is enabled (or ngt_allow_demo_tutor_roster filter).
 *
 * @return array<int,array<string,mixed>>
 */
function ngt_demo_tutors_enabled() {
	if ( class_exists( 'NGC_Platform_Demo' ) && NGC_Platform_Demo::is_enabled() ) {
		return true;
	}
	return (bool) apply_filters( 'ngt_allow_demo_tutor_roster', false );
}

/**
 * Design-system demo roster — never use on production without demo mode.
 *
 * @return array<int,array<string,mixed>>
 */
function ngt_get_demo_tutor_roster() {
	return array(
		array(
			'name'        => 'Sipho Ndlovu',
			'avatar'      => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop&crop=face',
			'subjects'    => array( 'Mathematics', 'Physical Sciences' ),
			'grades'      => 'Grade 10 – 12',
			'location'    => 'Soweto, Johannesburg',
			'province'    => 'Gauteng',
			'rate'        => 320,
			'bio'         => 'BSc Electrical Engineering graduate from Wits. Patient tutor specializing in breaking down complex calculus and mechanics into simple, relatable examples.',
			'rating'      => 4.9,
			'reviews'     => 24,
		),
		array(
			'name'        => 'Chantal du Plessis',
			'avatar'      => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=300&h=300&fit=crop&crop=face',
			'subjects'    => array( 'English Home Language', 'History' ),
			'grades'      => 'Grade 8 – 12',
			'location'    => 'Randburg, Johannesburg',
			'province'    => 'Gauteng',
			'rate'        => 300,
			'bio'         => 'BA Honors in English Literature from UJ. Passionate about helping students write with confidence, structure arguments, and excel in IEB/CAPS exams.',
			'rating'      => 4.8,
			'reviews'     => 18,
		),
		array(
			'name'        => 'Amara Okafor',
			'avatar'      => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=300&h=300&fit=crop&crop=face',
			'subjects'    => array( 'Life Sciences', 'Chemistry' ),
			'grades'      => 'Grade 10 – Tertiary',
			'location'    => 'Rondebosch, Cape Town',
			'province'    => 'Western Cape',
			'rate'        => 350,
			'bio'         => 'MSc Biochemistry student at UCT. Focused on conceptual understanding rather than rote learning. Experience preparing students for Cambridge & IEB.',
			'rating'      => 5.0,
			'reviews'     => 15,
		),
		array(
			'name'        => 'Tshepo Mokwena',
			'avatar'      => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&h=300&fit=crop&crop=face',
			'subjects'    => array( 'Accounting', 'Business Studies' ),
			'grades'      => 'Grade 10 – 12',
			'location'    => 'Pretoria East, Pretoria',
			'province'    => 'Gauteng',
			'rate'        => 320,
			'bio'         => 'BCom Accounting (Cum Laude) from UP. I bring accounting to life with real-world scenarios. Expert in balance sheets, ledger accounts, and tax basics.',
			'rating'      => 4.7,
			'reviews'     => 12,
		),
		array(
			'name'        => 'Sarah Jenkins',
			'avatar'      => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=300&h=300&fit=crop&crop=face',
			'subjects'    => array( 'Mathematics', 'English' ),
			'grades'      => 'Grade 1 – 7',
			'location'    => 'Durban North, Durban',
			'province'    => 'KwaZulu-Natal',
			'rate'        => 300,
			'bio'         => 'Qualified Primary School educator with 8 years classroom experience. Expert in early childhood literacy, foundation phase numeracy, and confidence building.',
			'rating'      => 4.9,
			'reviews'     => 31,
		),
	);
}

/**
 * @return array<int,array<string,mixed>>
 */
function ngt_get_tutors() {
	if ( ngt_plugin_active() && post_type_exists( 'tutors' ) ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'tutors',
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'no_found_rows'  => true,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_ngc_rating',
				'order'          => 'DESC',
			)
		);

		$tutors = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$id       = get_the_ID();
			$subjects = wp_get_post_terms( $id, 'subject', array( 'fields' => 'names' ) );
			$province = wp_get_post_terms( $id, 'province', array( 'fields' => 'names' ) );
			$grades   = wp_get_post_terms( $id, 'grade', array( 'fields' => 'names' ) );
			$formats  = wp_get_post_terms( $id, 'learning_format', array( 'fields' => 'names' ) );

			$tutors[] = array(
				'name'     => get_the_title(),
				'avatar'   => get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: '',
				'subjects' => is_wp_error( $subjects ) ? array() : $subjects,
				'grades'   => is_wp_error( $grades ) ? '' : implode( ', ', array_slice( (array) $grades, 0, 3 ) ),
				'location' => is_wp_error( $province ) || empty( $province ) ? '' : $province[0],
				'province' => is_wp_error( $province ) || empty( $province ) ? '' : $province[0],
				'formats'  => is_wp_error( $formats ) ? array() : $formats,
				'rate'     => (int) get_post_meta( $id, '_ngc_hourly_rate', true ),
				'bio'      => wp_trim_words( get_the_excerpt(), 25 ),
				'rating'   => (float) get_post_meta( $id, '_ngc_rating', true ),
				'reviews'  => (int) get_post_meta( $id, '_ngc_reviews', true ),
				'url'      => get_permalink(),
				'vetted'   => (bool) get_post_meta( $id, '_ngc_vetted', true ),
			);
		}
		wp_reset_postdata();

		if ( ! empty( $tutors ) ) {
			return apply_filters( 'ngt_tutors', $tutors );
		}
	}

	if ( ! ngt_demo_tutors_enabled() ) {
		return apply_filters( 'ngt_tutors', array() );
	}

	return apply_filters( 'ngt_tutors', ngt_get_demo_tutor_roster() );
}

/**
 * Is the compiled React application bundle present in the theme?
 * When true, interactive pages can mount the full SPA (dashboards / simulation hub).
 *
 * @return bool
 */
function ngt_has_react_bundle() {
	return file_exists( NGT_DIR . '/assets/js/app.bundle.js' );
}

/**
 * Star rating string (e.g. 4.9 -> "★★★★★").
 *
 * @param float $rating Rating value.
 * @return string
 */
function ngt_stars( $rating ) {
	$full = (int) round( $rating );
	$full = max( 0, min( 5, $full ) );
	return str_repeat( '★', $full ) . str_repeat( '☆', 5 - $full );
}
