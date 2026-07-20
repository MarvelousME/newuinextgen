<?php
/**
 * Framer-style motion pack + swappable theme images.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolved hero background video URL — local asset first, then Customizer, then CDN fallback.
 *
 * @return string
 */
function bi_get_hero_video_url() {
	$local = BI_DIR . '/assets/media/videos/hero-loop.mp4';
	if ( file_exists( $local ) ) {
		return esc_url( BI_URI . '/assets/media/videos/hero-loop.mp4' );
	}

	$custom = trim( (string) bi_get_theme_option( 'home_hero_video_url', '' ) );
	if ( $custom ) {
		return esc_url( $custom );
	}

	return esc_url( 'https://cdn.coverr.co/videos/coverr-student-studying-online-5308/1080p.mp4' );
}

/**
 * Image registry — local file, Customizer URL, or staging default.
 *
 * @return array<string, array{title: string, file: string, default: string, alt: string}>
 */
function bi_theme_image_registry() {
    $staging = 'https://nextgentutors.co.za/staging/wp-content/uploads/';
    return [
        'hero_bg' => [
            'title'   => __( 'Page hero background', 'beyondinfinity' ),
            'file'    => 'hero-bg.jpg',
            'default' => $staging . '2016/11/bg.jpg',
            'alt'     => __( 'Students learning with a tutor', 'beyondinfinity' ),
        ],
        'about_feature' => [
            'title'   => __( 'About page feature photo', 'beyondinfinity' ),
            'file'    => 'about-feature.jpg',
            'default' => $staging . '2016/11/img_al1.jpg',
            'alt'     => __( 'Tutor supporting a learner', 'beyondinfinity' ),
        ],
        'become_tutor' => [
            'title'   => __( 'Become a tutor photo', 'beyondinfinity' ),
            'file'    => 'become-tutor.jpg',
            'default' => $staging . '2016/11/1slide.jpg',
            'alt'     => __( 'Educator teaching online', 'beyondinfinity' ),
        ],
        'cta_bg' => [
            'title'   => __( 'Call-to-action band background', 'beyondinfinity' ),
            'file'    => 'cta-bg.jpg',
            'default' => $staging . '2016/12/bg_cta.jpg',
            'alt'     => '',
        ],
        'guarantee_bg' => [
            'title'   => __( 'Guarantee hero background', 'beyondinfinity' ),
            'file'    => 'guarantee-bg.jpg',
            'default' => $staging . '2016/12/bg_guarantee.jpg',
            'alt'     => '',
        ],
        'pricing_bg' => [
            'title'   => __( 'Pricing section background', 'beyondinfinity' ),
            'file'    => 'pricing-bg.jpg',
            'default' => $staging . '2016/12/bg5.jpg',
            'alt'     => '',
        ],
        'home_video' => [
            'title'   => __( 'Homepage video tile poster', 'beyondinfinity' ),
            'file'    => 'home-video.jpg',
            'default' => $staging . '2016/11/2slide.jpg',
            'alt'     => __( 'NextGen Tutors video preview', 'beyondinfinity' ),
        ],
        'hero_slide_2' => [
            'title'   => __( 'Secondary hero / slider image', 'beyondinfinity' ),
            'file'    => 'hero-slide-2.jpg',
            'default' => $staging . '2016/11/2slide.jpg',
            'alt'     => __( 'Online tutoring session', 'beyondinfinity' ),
        ],
        'hover_parent' => [
            'title'   => __( 'Hover card — parent journey', 'beyondinfinity' ),
            'file'    => 'hover-parent.jpg',
            'default' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80',
            'alt'     => __( 'Parent and student learning together', 'beyondinfinity' ),
        ],
        'hover_tutor' => [
            'title'   => __( 'Hover card — tutor journey', 'beyondinfinity' ),
            'file'    => 'hover-tutor.jpg',
            'default' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=80',
            'alt'     => __( 'Tutor teaching in classroom', 'beyondinfinity' ),
        ],
        'hover_online' => [
            'title'   => __( 'Hover card — online learning', 'beyondinfinity' ),
            'file'    => 'hover-online.jpg',
            'default' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80',
            'alt'     => __( 'Student learning online', 'beyondinfinity' ),
        ],
    ];
}

/**
 * Resolved image URL for a registry key.
 *
 * @param string $key Registry key.
 * @return string
 */
function bi_get_theme_image_url( $key ) {
    $registry = bi_theme_image_registry();
    if ( ! isset( $registry[ $key ] ) ) {
        return '';
    }

    $custom = trim( (string) bi_get_theme_option( 'bi_img_' . $key, '' ) );
    if ( $custom ) {
        return esc_url( $custom );
    }

    $local = BI_DIR . '/assets/images/' . $registry[ $key ]['file'];
    if ( file_exists( $local ) ) {
        return esc_url( BI_URI . '/assets/images/' . $registry[ $key ]['file'] );
    }

    return esc_url( $registry[ $key ]['default'] );
}

/**
 * Render an img element from the image registry.
 *
 * @param string               $key  Registry key.
 * @param array<string, mixed> $args Optional attributes.
 */
function bi_theme_image( $key, $args = [] ) {
    $registry = bi_theme_image_registry();
    if ( ! isset( $registry[ $key ] ) ) {
        return;
    }

    $url = bi_get_theme_image_url( $key );
    if ( ! $url ) {
        return;
    }

    $alt    = $args['alt'] ?? $registry[ $key ]['alt'];
    $class  = 'bi-theme-image' . ( ! empty( $args['class'] ) ? ' ' . $args['class'] : '' );
    $lazy   = ! isset( $args['loading'] ) || 'eager' !== $args['loading'];
    $wrap   = ! empty( $args['mask_reveal'] );
    $motion = $args['motion'] ?? 'slide-up';

    if ( $wrap ) {
        echo '<div class="bi-theme-image-wrap mask-reveal framer-motion" data-bi-motion="' . esc_attr( $motion ) . '">';
    }

    printf(
        '<img src="%s" alt="%s" class="%s"%s />',
        esc_url( $url ),
        esc_attr( $alt ),
        esc_attr( $class ),
        $lazy ? ' loading="lazy"' : ''
    );

    if ( $wrap ) {
        echo '</div>';
    }
}

/**
 * @return bool
 */
function bi_motion_enabled() {
    if ( bi_is_builder_edit_mode() ) {
        return false;
    }
    return (bool) bi_get_theme_option( 'bi_motion_enabled', 1 );
}

/**
 * Hero background image key from modifier class.
 *
 * @param string $class Hero modifier classes.
 * @return string
 */
function bi_hero_image_key( $class = '' ) {
    if ( str_contains( $class, 'guarantee' ) ) {
        return 'guarantee_bg';
    }
    return 'hero_bg';
}

/**
 * Parallax CTA band with swappable background.
 *
 * @param string $title   Heading.
 * @param string $button  Button label.
 * @param string $url     Button URL.
 * @param string $image_key Registry key for background.
 */
function bi_parallax_cta( $title, $button, $url, $image_key = 'cta_bg' ) {
    $bg = bi_get_theme_image_url( $image_key );
    ?>
    <section class="bi-parallax-cta framer-motion">
      <div class="bi-parallax-cta__bg parallax-bg" data-parallax-rate="0.25" style="background-image:url(<?php echo esc_url( $bg ); ?>)" aria-hidden="true"></div>
      <div class="bi-parallax-cta__overlay" aria-hidden="true"></div>
      <div class="bi-parallax-cta__inner" data-bi-motion="slide-up">
        <h2><?php echo esc_html( $title ); ?></h2>
        <a href="<?php echo esc_url( $url ); ?>" class="ngt-btn ngt-btn--white ngt-btn--lg btn-ripple hover-glow"><?php echo esc_html( $button ); ?></a>
      </div>
    </section>
    <?php
}

add_filter( 'body_class', 'bi_motion_body_class' );
function bi_motion_body_class( $classes ) {
    if ( bi_motion_enabled() ) {
        $classes[] = 'bi-motion-enabled';
    }
    if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
        $classes[] = 'bi-mission-dashboard';
    }
    return $classes;
}

add_action( 'wp_enqueue_scripts', 'bi_enqueue_motion_assets', 15 );
function bi_enqueue_motion_assets() {
    if ( is_admin() || bi_is_builder_edit_mode() ) {
        return;
    }

    $motion_dir = BI_URI . '/assets/css/motion/';
    $files      = [
        'bi-motion-base'       => '01-base.css',
        'bi-motion-entrance'   => '02-entrance.css',
        'bi-motion-sections'   => '03-sections.css',
        'bi-motion-hover'      => '04-hover.css',
        'bi-motion-parallax'   => '05-parallax.css',
        'bi-motion-responsive' => '06-responsive.css',
        'bi-motion-stagger'    => '07-stagger.css',
        'bi-motion-subtle'     => '08-subtle.css',
        'bi-motion-bridge'     => '09-theme-bridge.css',
    ];

    $deps = [ 'bi-style' ];
    foreach ( $files as $handle => $file ) {
        wp_enqueue_style( $handle, $motion_dir . $file, $deps, BI_VERSION );
        $deps = [ $handle ];
    }

    if ( bi_motion_enabled() ) {
        wp_enqueue_script( 'bi-motion', BI_URI . '/assets/js/motion.js', [], BI_VERSION, true );
    }
}
