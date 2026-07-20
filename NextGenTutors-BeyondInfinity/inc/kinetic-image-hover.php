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
    $find    = home_url( '/find-a-tutor' );
    $become  = home_url( '/become-a-tutor' );
    $register = home_url( '/register' );

    return apply_filters(
        'bi_kinetic_image_hover_cards',
        [
            [
                'key'    => 'hover_parent',
                'kicker' => __( 'Parent Journey', 'beyondinfinity' ),
                'title'  => __( 'Find the right tutor faster', 'beyondinfinity' ),
                'copy'   => __( 'Search by subject, grade, province, learning format and availability.', 'beyondinfinity' ),
                'url'    => $find,
                'cta'    => __( 'Find a Tutor', 'beyondinfinity' ),
            ],
            [
                'key'    => 'hover_tutor',
                'kicker' => __( 'Tutor Journey', 'beyondinfinity' ),
                'title'  => __( 'Apply and teach with confidence', 'beyondinfinity' ),
                'copy'   => __( 'Showcase skills, availability, subjects, experience and lesson formats.', 'beyondinfinity' ),
                'url'    => $become,
                'cta'    => __( 'Become a Tutor', 'beyondinfinity' ),
            ],
            [
                'key'    => 'hover_online',
                'kicker' => __( 'Online Learning', 'beyondinfinity' ),
                'title'  => __( 'Book online or hybrid support', 'beyondinfinity' ),
                'copy'   => __( 'Make every lesson trackable with booking, CRM and dashboard touchpoints.', 'beyondinfinity' ),
                'url'    => $register,
                'cta'    => __( 'Register Now', 'beyondinfinity' ),
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
    $mode = sanitize_key( (string) bi_get_theme_option( 'home_image_hover_mode', 'zoom' ) );
    $allowed = [ 'zoom', 'slide', 'flip', 'blur', 'shine' ];
    return in_array( $mode, $allowed, true ) ? $mode : 'zoom';
}

/**
 * Render hero advanced search panel (preview pattern).
 */
function bi_kinetic_hero_search_panel() {
    $find = home_url( '/find-a-tutor' );
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
          <div class="ngi-eyebrow"><?php esc_html_e( 'Discovery cards', 'beyondinfinity' ); ?></div>
          <h2 class="ngi-heading"><?php esc_html_e( 'Premium tutor journeys with interactive hover.', 'beyondinfinity' ); ?></h2>
          <p class="ngi-subtitle"><?php esc_html_e( 'Preview hover modes for parent, tutor and online learning pathways.', 'beyondinfinity' ); ?></p>
        </div>
        <div class="ngi-hover-toolbar ngi-reveal" role="group" aria-label="<?php esc_attr_e( 'Image hover effect controls', 'beyondinfinity' ); ?>">
          <?php
          $modes = [
              'zoom'  => __( 'Zoom Overlay', 'beyondinfinity' ),
              'slide' => __( 'Slide Caption', 'beyondinfinity' ),
              'flip'  => __( 'Flip Card', 'beyondinfinity' ),
              'blur'  => __( 'Blur Focus', 'beyondinfinity' ),
              'shine' => __( 'Shine Sweep', 'beyondinfinity' ),
          ];
          foreach ( $modes as $slug => $label ) :
              ?>
            <button class="ngi-hover-control<?php echo $mode === $slug ? ' is-active' : ''; ?>" type="button" data-hover-mode="<?php echo esc_attr( $slug ); ?>" aria-pressed="<?php echo $mode === $slug ? 'true' : 'false'; ?>">
              <?php echo esc_html( $label ); ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="ngi-image-hover-grid ngi-hover-mode-<?php echo esc_attr( $mode ); ?>" data-ngi-hover-grid>
          <?php foreach ( $cards as $card ) :
              $img = function_exists( 'bi_get_theme_image_url' ) ? bi_get_theme_image_url( $card['key'] ) : '';
              if ( ! $img ) {
                  $img = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80';
              }
              ?>
            <article class="ngi-image-hover-card ngi-reveal">
              <div class="ngi-image-hover-media" style="--ngi-img:url('<?php echo esc_url( $img ); ?>')">
                <div class="ngi-image-hover-overlay">
                  <span class="ngi-hover-kicker"><?php echo esc_html( $card['kicker'] ); ?></span>
                  <h3><?php echo esc_html( $card['title'] ); ?></h3>
                  <p><?php echo esc_html( $card['copy'] ); ?></p>
                  <a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['cta'] ); ?></a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
}
