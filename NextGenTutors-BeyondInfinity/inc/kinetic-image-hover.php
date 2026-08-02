<?php
/**
 * Kinetic homepage — image hover cards and hero search panel.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return array<int, array<string, string>>
 */
function bi_kinetic_image_hover_cards() {
    $find     = home_url( '/find-a-tutor/' );
    $become   = home_url( '/become-a-tutor/' );
    $register = home_url( '/register/' );

    return apply_filters(
        'bi_kinetic_image_hover_cards',
        [
            [
                'key'    => 'hover_parent',
                'kicker' => __( 'For parents', 'beyondinfinity' ),
                'title'  => __( 'Match a vetted tutor to your child’s goals', 'beyondinfinity' ),
                'copy'   => __( 'Filter by CAPS, IEB or Cambridge, grade, province and lesson format — then book with confidence.', 'beyondinfinity' ),
                'url'    => $find,
            ],
            [
                'key'    => 'hover_tutor',
                'kicker' => __( 'For tutors', 'beyondinfinity' ),
                'title'  => __( 'Teach on a trusted SA tutoring network', 'beyondinfinity' ),
                'copy'   => __( 'Share your subjects, rates and availability. We handle matching, bookings and parent communication.', 'beyondinfinity' ),
                'url'    => $become,
            ],
            [
                'key'    => 'hover_online',
                'kicker' => __( 'Online & hybrid', 'beyondinfinity' ),
                'title'  => __( 'Learn online, in person, or both', 'beyondinfinity' ),
                'copy'   => __( 'Flexible sessions with progress tracking in your dashboard — wherever your learner works best.', 'beyondinfinity' ),
                'url'    => $register,
            ],
        ]
    );
}

/**
 * Default hover mode slug.
 *
 * @return string
 */
function bi_kinetic_hover_mode() {
    // Discovery cards always use shine sweep (mode picker removed from the homepage).
    return 'shine';
}

/**
 * Render hero advanced search panel (preview pattern).
 */
function bi_kinetic_hero_search_panel() {
    $find = home_url( '/find-a-tutor/' );
    ?>
    <div class="ngi-visual ngi-visual--search" data-kh-ambient-visual>
      <div class="ngi-glow" aria-hidden="true"></div>
      <?php bi_nbi_liquid_glass_search( $find ); ?>
    </div>
    <?php
}

/**
 * Render image hover discovery section.
 */
function bi_kinetic_render_image_hover_section() {
    $mode  = bi_kinetic_hover_mode();
    $cards = bi_kinetic_image_hover_cards();
    ?>
    <section class="ngi-section ngi-alt ngi-hover-lab" id="image-hover">
      <div class="ngi-wrap">
        <div class="ngi-section-head ngi-reveal">
          <div class="ngi-eyebrow"><?php esc_html_e( 'Choose your path', 'beyondinfinity' ); ?></div>
          <h2 class="ngi-heading"><?php esc_html_e( 'Whether you need a tutor, want to teach, or prefer online lessons.', 'beyondinfinity' ); ?></h2>
          <p class="ngi-subtitle"><?php esc_html_e( 'Explore parent matching, tutor applications and flexible online learning — each card opens the right next step.', 'beyondinfinity' ); ?></p>
        </div>
        <div class="ngi-image-hover-grid ngi-hover-mode-<?php echo esc_attr( $mode ); ?>" data-ngi-hover-grid data-hover-mode="<?php echo esc_attr( $mode ); ?>">
          <?php foreach ( $cards as $card ) :
              $img = function_exists( 'bi_get_theme_image_url' ) ? bi_get_theme_image_url( $card['key'] ) : '';
              if ( ! $img ) {
                  $img = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80';
              }
              ?>
            <article class="ngi-image-hover-card ngi-reveal">
              <a class="ngi-image-hover-link" href="<?php echo esc_url( $card['url'] ); ?>">
                <div class="ngi-image-hover-media" style="--ngi-img:url('<?php echo esc_url( $img ); ?>')">
                  <div class="ngi-image-hover-overlay">
                    <span class="ngi-hover-kicker"><?php echo esc_html( $card['kicker'] ); ?></span>
                    <h3><?php echo esc_html( $card['title'] ); ?></h3>
                    <p><?php echo esc_html( $card['copy'] ); ?></p>
                  </div>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
}
