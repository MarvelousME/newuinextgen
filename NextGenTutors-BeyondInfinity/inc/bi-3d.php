<?php
/**
 * BeyondInfinity — 3D hover, stacked cards, carousel enhancements.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether 3D effects are enabled (Customizer).
 */
function bi_3d_enabled() {
    return (bool) bi_get_theme_option( 'bi_3d_enabled', 1 );
}

/**
 * Use 3D spinning carousel on kinetic homepage tutors section.
 */
function bi_3d_home_carousel_enabled() {
    return bi_3d_enabled() && (bool) bi_get_theme_option( 'home_tutors_carousel_3d', 1 );
}

/**
 * Clamp tilt intensity from Customizer (0–20 degrees).
 */
function bi_3d_get_tilt_max() {
    $max = (int) bi_get_theme_option( 'bi_3d_tilt_max', 10 );
    return max( 0, min( 20, $max ) );
}

/**
 * Whether front-end 3D assets should load on this request.
 */
function bi_page_needs_3d_assets() {
    if ( ! bi_3d_enabled() ) {
        return false;
    }
    if ( bi_is_kinetic_home() ) {
        return true;
    }
    if ( is_front_page() || is_page( [ 'home', 'find-a-tutor', 'pricing', 'about' ] ) ) {
        return true;
    }
    if ( bi_page_needs_carousel_assets() ) {
        return true;
    }
    global $post;
    if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'bi_tutors_carousel' ) ) {
        return true;
    }
    return (bool) apply_filters( 'bi_enqueue_3d_assets', false );
}

add_filter( 'body_class', 'bi_3d_body_class' );
function bi_3d_body_class( $classes ) {
    if ( bi_3d_enabled() ) {
        $classes[] = 'bi-3d-enabled';
    }
    return $classes;
}

add_action( 'wp_enqueue_scripts', 'bi_enqueue_3d_assets', 16 );
function bi_enqueue_3d_assets() {
    if ( is_admin() || bi_is_builder_edit_mode() || ! bi_page_needs_3d_assets() ) {
        return;
    }

    wp_enqueue_style(
        'bi-3d',
        BI_URI . '/assets/css/bi-3d.css',
        [ 'bi-style', 'bi-components' ],
        BI_VERSION
    );

    $deps = [];
    if ( wp_script_is( 'bi-tutors-carousel', 'registered' ) || bi_page_needs_carousel_assets() ) {
        $deps[] = 'bi-tutors-carousel';
    }

    wp_enqueue_script(
        'bi-3d',
        BI_URI . '/assets/js/bi-3d.js',
        $deps,
        BI_VERSION,
        true
    );

    wp_localize_script(
        'bi-3d',
        'bi3d',
        [
            'tiltMax'      => bi_3d_get_tilt_max(),
            'carouselSpin' => (bool) bi_get_theme_option( 'bi_3d_carousel_spin', 1 ),
        ]
    );
}

add_filter( 'bi_enqueue_carousel_assets', 'bi_3d_force_carousel_on_kinetic_home' );
function bi_3d_force_carousel_on_kinetic_home( $enqueue ) {
    if ( $enqueue ) {
        return true;
    }
    return bi_is_kinetic_home() && bi_3d_home_carousel_enabled();
}

/**
 * Render a fanning 3D card stack.
 *
 * @param array<int, array<string, string>> $items Each item: title, body, optional meta, optional featured.
 * @param array<string, mixed>              $args  id, class, aria_label.
 */
function bi_render_3d_card_stack( $items, $args = [] ) {
    if ( empty( $items ) || ! bi_3d_enabled() ) {
        return;
    }

    $args = wp_parse_args(
        $args,
        [
            'id'         => 'bi-stack-' . wp_unique_id(),
            'class'      => '',
            'aria_label' => __( 'Stacked cards', 'beyondinfinity' ),
        ]
    );

    $id    = sanitize_html_class( $args['id'] );
    $count = count( $items );
    ?>
    <div
      class="bi-stack-3d ngi-reveal <?php echo esc_attr( $args['class'] ); ?>"
      id="<?php echo esc_attr( $id ); ?>"
      data-bi-stack-3d
      data-stack-count="<?php echo esc_attr( (string) $count ); ?>"
      role="group"
      aria-label="<?php echo esc_attr( $args['aria_label'] ); ?>"
    >
      <?php foreach ( $items as $i => $item ) : ?>
        <article
          class="bi-stack-3d__card ngi-card<?php echo ! empty( $item['featured'] ) ? ' is-featured' : ''; ?><?php echo 0 === $i ? ' is-active' : ''; ?>"
          data-index="<?php echo esc_attr( (string) $i ); ?>"
          tabindex="0"
          aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"
        >
          <?php if ( ! empty( $item['meta'] ) ) : ?>
            <span class="bi-stack-3d__meta"><?php echo esc_html( $item['meta'] ); ?></span>
          <?php endif; ?>
          <h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
          <?php if ( ! empty( $item['body'] ) ) : ?>
            <p><?php echo esc_html( $item['body'] ); ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Kinetic homepage tutors section using the 3D carousel.
 *
 * @param array<string, mixed> $args Section copy overrides.
 */
function bi_render_kinetic_tutors_3d( $args = [] ) {
    $args = wp_parse_args(
        $args,
        [
            'eyebrow'  => __( 'Featured tutors', 'beyondinfinity' ),
            'title'    => __( 'Vetted educators ready to book.', 'beyondinfinity' ),
            'subtitle' => __( 'Verified CAPS, IEB and Cambridge tutors — use the arrows or swipe to browse.', 'beyondinfinity' ),
            'limit'    => 8,
        ]
    );

    $tutors = bi_get_carousel_tutors( (int) $args['limit'] );
    if ( empty( $tutors ) ) {
        return;
    }

    $cid       = 'bi-kinetic-carousel-' . wp_unique_id();
    $spin_attr = bi_get_theme_option( 'bi_3d_carousel_spin', 1 ) ? ' data-carousel-spin' : '';
    ?>
    <section class="ngi-section ngi-alt ngi-section--tutors-3d" id="tutors">
      <div class="ngi-wrap ngi-wrap--wide">
        <div class="ngi-section-head ngi-reveal">
          <div class="ngi-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></div>
          <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $args['title'] ); ?></h2>
          <?php if ( $args['subtitle'] ) : ?>
            <p class="ngi-subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="bi-carousel-3d-shell">
        <div
          class="carousel-3d bi-carousel-3d bi-carousel-3d--kinetic"
          id="<?php echo esc_attr( $cid ); ?>"
          data-carousel<?php echo esc_attr( $spin_attr ); ?>
          tabindex="0"
          role="region"
          aria-roledescription="<?php esc_attr_e( 'carousel', 'beyondinfinity' ); ?>"
          aria-label="<?php esc_attr_e( 'Featured tutors carousel', 'beyondinfinity' ); ?>"
        >
          <div class="bi-carousel-3d__glow" aria-hidden="true"></div>
          <div class="carousel-3d__stage">
            <?php foreach ( $tutors as $i => $tutor ) : ?>
              <div class="tutor-card3d bi-tilt-3d bi-tilt-3d--carousel" data-index="<?php echo esc_attr( (string) $i ); ?>" data-bi-tilt data-bi-tilt-max="6">
                <div class="bi-tilt-3d__inner">
                  <?php bi_render_tutor_carousel_card( $tutor ); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <p class="screen-reader-text bi-carousel-live" aria-live="polite" aria-atomic="true" data-carousel-live="<?php echo esc_attr( $cid ); ?>"></p>
        </div>
        <div class="carousel-nav bi-carousel-nav--kinetic" data-carousel-nav="<?php echo esc_attr( $cid ); ?>">
          <button type="button" class="carousel-arrow" data-dir="-1" aria-label="<?php esc_attr_e( 'Previous tutor', 'beyondinfinity' ); ?>">‹</button>
          <div class="carousel-dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose tutor', 'beyondinfinity' ); ?>">
            <?php foreach ( $tutors as $i => $tutor ) : ?>
              <button type="button" class="cdot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr( (string) $i ); ?>" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Tutor %d', 'beyondinfinity' ), $i + 1 ) ); ?>"></button>
            <?php endforeach; ?>
          </div>
          <button type="button" class="carousel-arrow" data-dir="1" aria-label="<?php esc_attr_e( 'Next tutor', 'beyondinfinity' ); ?>">›</button>
        </div>
      </div>
    </section>
    <?php
}
