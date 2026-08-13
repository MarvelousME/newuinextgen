<?php
/**
 * Template tags (split from inc/template-tags.php).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bi_render_shortcode( $shortcode, $fallback_message = '' ) {
    $tag = trim( str_replace( [ '[', ']' ], '', $shortcode ) );
    if ( shortcode_exists( $tag ) ) {
        echo do_shortcode( $shortcode );
        return;
    }
    $message = $fallback_message ?: sprintf(
        __( 'This form is temporarily unavailable. Please contact us at %s or use WhatsApp for urgent support.', 'beyondinfinity' ),
        bi_get_support_email()
    );
    echo '<div class="bi-shortcode-fallback" role="alert">';
    echo '<p>' . esc_html( $message ) . '</p>';
    echo '<div class="bi-shortcode-fallback__actions">';
    echo '<a href="' . esc_url( bi_whatsapp_url( __( 'Hi, I need help with NextGen Tutors.', 'beyondinfinity' ) ) ) . '" class="ngt-btn ngt-btn--secondary" target="_blank" rel="noopener">WhatsApp Us</a>';
    echo '<a href="' . esc_url( home_url( '/contact' ) ) . '" class="ngt-btn ngt-btn--outline">Contact Support</a>';
    echo '</div></div>';
}

function bi_hero( $title, $subtitle = '', $class = '' ) {
	if ( function_exists( 'bi_render_modern_hero' ) ) {
		bi_render_modern_hero(
			$title,
			$subtitle,
			[
				'class'      => $class,
				'show_stats' => false,
				'show_trust' => true,
			]
		);
		return;
	}
    if ( function_exists( 'bi_should_show_page_title' ) && ! bi_should_show_page_title() ) {
        return;
    }
    $img_key  = function_exists( 'bi_hero_image_key' ) ? bi_hero_image_key( $class ) : 'hero_bg';
    $bg_url   = function_exists( 'bi_get_theme_image_url' ) ? bi_get_theme_image_url( $img_key ) : '';
    $bg_class = 'ngt-hero__bg bi-hero__bg';
    if ( $bg_url ) {
        $bg_class .= ' bi-hero__bg--image';
        if ( function_exists( 'bi_motion_enabled' ) && bi_motion_enabled() ) {
            $bg_class .= ' bi-hero__bg--parallax parallax-bg';
        }
    }
    $hero_class = 'ngt-hero bi-hero';
    if ( function_exists( 'bi_motion_enabled' ) && bi_motion_enabled() ) {
        $hero_class .= ' framer-motion';
    }
    ?>
    <section class="<?php echo esc_attr( trim( $hero_class . ' ' . $class ) ); ?>">
      <div class="<?php echo esc_attr( $bg_class ); ?>"<?php if ( $bg_url ) : ?> style="background-image:url(<?php echo esc_url( $bg_url ); ?>)"<?php if ( function_exists( 'bi_motion_enabled' ) && bi_motion_enabled() ) : ?> data-parallax-rate="0.3"<?php endif; ?><?php endif; ?> aria-hidden="true"></div>
      <div class="ngt-container bi-hero__inner">
        <div class="ngt-hero__content bi-hero__content"<?php if ( function_exists( 'bi_motion_enabled' ) && bi_motion_enabled() ) : ?> data-bi-motion="slide-up"<?php endif; ?>>
          <h1 class="bi-hero__title" data-bi-slide-title><?php echo esc_html( $title ); ?></h1>
          <?php if ( $subtitle ) : ?>
            <p class="bi-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php
}

function bi_steps( $steps, $title = '', $subtitle = '' ) {
    ?>
    <section class="ng-page-section ngt-section ngt-section--alt ng-reveal" id="how-it-works">
      <div class="ng-container ng-page-section__inner">
        <?php if ( $title ) : ?>
          <?php bi_page_heading( $title, $subtitle ); ?>
        <?php endif; ?>
        <ol class="bi-steps ng-page-grid">
          <?php foreach ( $steps as $i => $step ) : ?>
            <li class="bi-step ngt-animate ngt-animate--delay-<?php echo ( $i % 3 ) + 1; ?>">
              <div class="bi-step__num"><?php echo esc_html( (string) ( $i + 1 ) ); ?></div>
              <div>
                <?php if ( is_array( $step ) ) : ?>
                  <?php if ( ! empty( $step['title'] ) ) : ?>
                    <h3 class="bi-step__title"><?php echo esc_html( $step['title'] ); ?></h3>
                  <?php endif; ?>
                  <p class="bi-step__text"><?php echo esc_html( $step['text'] ?? '' ); ?></p>
                <?php else : ?>
                  <p class="bi-step__text"><?php echo esc_html( $step ); ?></p>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </section>
    <?php
}

function bi_bullets( $items, $light = false ) {
    echo '<ul class="bi-bullets' . ( $light ? ' bi-bullets--light' : '' ) . '">';
    foreach ( $items as $item ) {
        echo '<li>' . bi_bullet_mark( $light ) . '<span>' . esc_html( $item ) . '</span></li>';
    }
    echo '</ul>';
}

function bi_shortcode_block( $shortcode, $title = '', $fallback = '' ) {
    ?>
    <div class="ngt-card ngt-animate bi-shortcode-block ng-reveal bi-tilt-3d" data-bi-tilt data-bi-tilt-max="6">
      <?php if ( $title ) : ?>
        <h2 class="bi-shortcode-block__title"><?php echo esc_html( $title ); ?></h2>
      <?php endif; ?>
      <?php bi_render_shortcode( $shortcode, $fallback ); ?>
    </div>
    <?php
}

function bi_trust_badges() {
    $badges = [
        [ 'icon' => 'graduation', 'label' => 'Grade 1 – Tertiary Support' ],
        [ 'icon' => 'book-open', 'label' => 'CAPS, IEB & Cambridge' ],
        [ 'icon' => 'laptop', 'label' => 'Online, In-Person & Hybrid' ],
        [ 'icon' => 'lock', 'label' => 'Platform-Managed Payments' ],
        [ 'icon' => 'globe', 'label' => 'Available Across All 9 Provinces' ],
    ];
    echo '<section class="bi-trust-strip"><div class="ngt-container"><div class="bi-trust-strip__grid">';
    foreach ( $badges as $badge ) {
        echo '<div class="bi-trust-strip__item ngt-animate">';
        echo '<span class="bi-trust-strip__icon" aria-hidden="true">' . bi_ui_icon( $badge['icon'], 22 ) . '</span>';
        echo '<span class="bi-trust-strip__label">' . esc_html( $badge['label'] ) . '</span>';
        echo '</div>';
    }
    echo '</div></div></section>';
}

function bi_marquee_band() {
    $items = bi_get_marquee_items();
    echo '<div class="bi-marquee-band" aria-hidden="true"><div class="bi-marquee">';
    foreach ( array_merge( $items, $items ) as $item ) {
        echo '<span class="bi-marquee__pill"><span class="bi-marquee__e">' . esc_html( $item['e'] ) . '</span>' . esc_html( $item['t'] ) . '</span>';
    }
    echo '</div></div>';
}

/**
 * Render a compact contextual trust message.
 *
 * @param string               $label Trust statement.
 * @param string               $url Optional destination.
 * @param array<string, mixed> $args Optional icon, modifier and aria_label.
 */
function bi_trust_chip( $label, $url = '', $args = [] ) {
    $args = wp_parse_args(
        $args,
        [
            'icon'       => 'shield',
            'modifier'   => '',
            'aria_label' => '',
        ]
    );
    $class = 'bi-trust-chip';
    if ( $args['modifier'] ) {
        $class .= ' bi-trust-chip--' . sanitize_html_class( $args['modifier'] );
    }
    $icon = bi_ui_icon( sanitize_key( $args['icon'] ), 16 );
    $tag  = $url ? 'a' : 'span';
    echo '<' . esc_attr( $tag ) . ' class="' . esc_attr( $class ) . '"';
    if ( $url ) {
        echo ' href="' . esc_url( $url ) . '"';
    }
    if ( $args['aria_label'] ) {
        echo ' aria-label="' . esc_attr( $args['aria_label'] ) . '"';
    }
    echo '>';
    if ( $icon ) {
        echo '<span class="bi-trust-chip__icon" aria-hidden="true">' . $icon . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    echo '<span>' . esc_html( $label ) . '</span>';
    if ( $url ) {
        echo '<span class="bi-trust-chip__arrow" aria-hidden="true">→</span>';
    }
    echo '</' . esc_attr( $tag ) . '>';
}

/**
 * Render live subject or province taxonomy coverage as filter links.
 *
 * Empty taxonomies render nothing so the public page never fabricates coverage.
 *
 * @param string $taxonomy subject|province.
 * @param int    $limit Maximum terms.
 * @return bool Whether the band rendered.
 */
function bi_coverage_band( $taxonomy, $limit = 9 ) {
    if ( ! in_array( $taxonomy, [ 'subject', 'province' ], true ) ) {
        return false;
    }
    $terms = [];
    if ( taxonomy_exists( $taxonomy ) ) {
        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
                'number'     => max( 1, min( 16, (int) $limit ) ),
                'orderby'    => 'count',
                'order'      => 'DESC',
            ]
        );
        if ( is_wp_error( $terms ) ) {
            $terms = [];
        }
    }

    // Empty taxonomy → build chips from demo roster so the band never blanks.
    if ( empty( $terms ) && function_exists( 'bi_demo_content_enabled' ) && bi_demo_content_enabled() && function_exists( 'bi_get_demo_tutors' ) ) {
        $counts = [];
        foreach ( bi_get_demo_tutors( 12 ) as $tutor ) {
            if ( 'subject' === $taxonomy ) {
                foreach ( (array) ( $tutor['subjects'] ?? [] ) as $name ) {
                    $slug = sanitize_title( (string) $name );
                    if ( ! $slug ) {
                        continue;
                    }
                    if ( ! isset( $counts[ $slug ] ) ) {
                        $counts[ $slug ] = [ 'name' => (string) $name, 'count' => 0 ];
                    }
                    $counts[ $slug ]['count']++;
                }
            } elseif ( ! empty( $tutor['province'] ) ) {
                $name = (string) $tutor['province'];
                $slug = sanitize_title( $name );
                if ( ! isset( $counts[ $slug ] ) ) {
                    $counts[ $slug ] = [ 'name' => $name, 'count' => 0 ];
                }
                $counts[ $slug ]['count']++;
            }
        }
        uasort(
            $counts,
            static function ( $a, $b ) {
                return (int) $b['count'] <=> (int) $a['count'];
            }
        );
        $i = 0;
        foreach ( $counts as $slug => $row ) {
            if ( $i >= (int) $limit ) {
                break;
            }
            $terms[] = (object) [
                'slug'  => $slug,
                'name'  => $row['name'],
                'count' => (int) $row['count'],
            ];
            $i++;
        }
    }

    $title = 'subject' === $taxonomy
        ? __( 'Popular subject coverage', 'beyondinfinity' )
        : __( 'Tutor coverage by province', 'beyondinfinity' );
    ?>
    <section class="bi-coverage-band" aria-labelledby="bi-coverage-<?php echo esc_attr( $taxonomy ); ?>">
      <div class="bi-coverage-band__header">
        <h2 id="bi-coverage-<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( $title ); ?></h2>
        <p><?php esc_html_e( 'Choose an area to apply it to the live tutor directory.', 'beyondinfinity' ); ?></p>
      </div>
      <div class="bi-coverage-band__chips" role="list">
        <?php if ( empty( $terms ) ) : ?>
          <p class="bi-coverage-band__empty" role="listitem"><?php esc_html_e( 'Coverage will appear here as vetted tutor profiles go live.', 'beyondinfinity' ); ?></p>
        <?php else : ?>
          <?php foreach ( $terms as $term ) : ?>
          <a
            class="bi-coverage-chip"
            role="listitem"
            href="<?php echo esc_url( add_query_arg( $taxonomy, $term->slug, home_url( '/find-a-tutor/' ) ) ); ?>"
            data-bi-marketplace-filter="<?php echo esc_attr( $taxonomy ); ?>"
            data-bi-marketplace-value="<?php echo esc_attr( $term->slug ); ?>"
          >
            <span><?php echo esc_html( $term->name ); ?></span>
            <span class="bi-coverage-chip__count" aria-label="<?php echo esc_attr( sprintf( _n( '%d tutor', '%d tutors', (int) $term->count, 'beyondinfinity' ), (int) $term->count ) ); ?>"><?php echo esc_html( (string) (int) $term->count ); ?></span>
          </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
    <?php
    return true;
}

function bi_safety_notice( $context = 'parent' ) {
    $url = home_url( '/safety-guide' );
    ?>
    <aside class="bi-safety-notice ngt-animate" role="note">
      <strong><?php esc_html_e( 'Safe tutoring:', 'beyondinfinity' ); ?></strong>
      <?php if ( 'tutor' === $context ) : ?>
        <?php esc_html_e( 'Review our tutor safety guidelines before your first session.', 'beyondinfinity' ); ?>
      <?php else : ?>
        <?php esc_html_e( 'Read our safety guide before your learner’s first session.', 'beyondinfinity' ); ?>
      <?php endif; ?>
      <a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View Safety Guide', 'beyondinfinity' ); ?> →</a>
    </aside>
    <?php
}

function bi_vetting_badges() {
    $badges = [
        [ 'icon' => 'id-card', 'title' => 'Identity Verified', 'text' => 'Every tutor profile is checked before approval.' ],
        [ 'icon' => 'clipboard', 'title' => 'Qualifications Reviewed', 'text' => 'Subjects, grades, and experience are manually assessed.' ],
        [ 'icon' => 'check', 'title' => 'Manual Approval', 'text' => 'No tutor is matched until our team approves their profile.' ],
        [ 'icon' => 'shield', 'title' => 'Ongoing Monitoring', 'text' => 'Parent feedback and performance are tracked over time.' ],
    ];
    echo '<div class="bi-badge-grid">';
    foreach ( $badges as $i => $badge ) {
        echo '<div class="ngt-card bi-badge-card ngt-animate ngt-animate--delay-' . esc_attr( (string) ( ( $i % 3 ) + 1 ) ) . '">';
        echo '<div class="bi-badge-card__icon" aria-hidden="true">' . bi_ui_icon( $badge['icon'], 28 ) . '</div>';
        echo '<h3>' . esc_html( $badge['title'] ) . '</h3>';
        echo '<p>' . esc_html( $badge['text'] ) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Value card grid (safety, about, etc.).
 *
 * @param array<int, array{icon:string,title:string,text:string}> $cards Cards.
 */
function bi_value_cards( $cards ) {
    echo '<div class="bi-value-grid">';
    foreach ( $cards as $i => $card ) {
        $icon_html = bi_ui_icon( $card['icon'], 28 );
        if ( ! $icon_html && function_exists( 'bi_kinetic_icon' ) ) {
            $icon_html = bi_kinetic_icon( $card['icon'] );
        }
        echo '<div class="ngt-card bi-value-card ngt-animate ngt-animate--delay-' . esc_attr( (string) ( ( $i % 3 ) + 1 ) ) . '">';
        echo '<div class="bi-value-card__icon" aria-hidden="true">' . $icon_html . '</div>';
        echo '<h3>' . esc_html( $card['title'] ) . '</h3>';
        echo '<p>' . esc_html( $card['text'] ) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * FAQ accordion list.
 *
 * @param array<int, array{q:string,a:string}> $items FAQ rows.
 */
function bi_faq_list( $items ) {
    echo '<div class="bi-faq-list">';
    foreach ( $items as $item ) {
        echo '<details class="ngt-card bi-faq ngt-animate">';
        echo '<summary>' . esc_html( $item['q'] ) . '</summary>';
        echo '<p>' . esc_html( $item['a'] ) . '</p>';
        echo '</details>';
    }
    echo '</div>';
}

/**
 * Verification badge comparison table.
 *
 * @param array<int, array{badge:string,desc:string}> $rows Table rows.
 */
function bi_badge_table( $rows ) {
    echo '<div class="bi-table-wrap ngt-animate"><table class="bi-cmp-table"><thead><tr><th>' . esc_html__( 'Badge', 'beyondinfinity' ) . '</th><th>' . esc_html__( 'Description', 'beyondinfinity' ) . '</th></tr></thead><tbody>';
    foreach ( $rows as $row ) {
        echo '<tr><td><strong>' . esc_html( $row['badge'] ) . '</strong></td><td>' . esc_html( $row['desc'] ) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

/**
 * Numbered verification steps (vetting page).
 *
 * @param array<int, array{title:string,text:string}> $steps Steps.
 */
function bi_vsteps( $steps ) {
    echo '<div class="bi-vsteps">';
    foreach ( $steps as $i => $step ) {
        echo '<div class="bi-vstep ngt-animate ngt-animate--delay-' . esc_attr( (string) ( ( $i % 3 ) + 1 ) ) . '">';
        echo '<span class="bi-vstep__n">' . esc_html( (string) ( $i + 1 ) ) . '</span>';
        echo '<div><div class="bi-vstep__t">' . esc_html( $step['title'] ) . '</div>';
        echo '<p class="bi-vstep__d">' . esc_html( $step['text'] ) . '</p></div></div>';
    }
    echo '</div>';
}

/**
 * Metric stat grid.
 *
 * @param array<int, array{value:string,label:string}> $metrics Metrics.
 */
function bi_metric_grid( $metrics ) {
    echo '<div class="bi-metric-grid">';
    foreach ( $metrics as $i => $metric ) {
        echo '<div class="ngt-card bi-metric ngt-animate ngt-animate--delay-' . esc_attr( (string) ( ( $i % 4 ) + 1 ) ) . '">';
        echo '<div class="bi-metric__n">' . esc_html( $metric['value'] ) . '</div>';
        echo '<div class="bi-metric__l">' . esc_html( $metric['label'] ) . '</div>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Highlight info card with bullet list.
 *
 * @param string                $title Card heading.
 * @param array<int, string>    $items List items (plain or "label — detail").
 * @param string                $modifier Optional BEM modifier e.g. alert.
 */
function bi_info_card( $title, $items, $modifier = '' ) {
    $class = 'ngt-card bi-info-card ngt-animate';
    if ( $modifier ) {
        $class .= ' bi-info-card--' . sanitize_html_class( $modifier );
    }
    echo '<div class="' . esc_attr( $class ) . '">';
    echo '<h3 class="bi-info-card__title">' . esc_html( $title ) . '</h3>';
    echo '<ul class="bi-info-card__list">';
    foreach ( $items as $item ) {
        echo '<li><span class="bi-info-card__mark" aria-hidden="true">' . bi_ui_icon( 'check', 16 ) . '</span><span>' . esc_html( $item ) . '</span></li>';
    }
    echo '</ul></div>';
}

function bi_matric_banner() {
    $month = (int) gmdate( 'n' );
    if ( $month < 3 || $month > 11 ) {
        return;
    }
    ?>
    <section class="bi-matric-banner ngt-animate" aria-label="<?php esc_attr_e( 'Matric exam preparation', 'beyondinfinity' ); ?>">
      <div class="ngt-container bi-matric-banner__inner">
        <div>
          <p class="bi-matric-banner__eyebrow"><?php esc_html_e( 'Matric Season Support', 'beyondinfinity' ); ?></p>
          <h2><?php esc_html_e( 'Exam prep with past papers, weak-area focus, and confidence building', 'beyondinfinity' ); ?></h2>
        </div>
        <a href="<?php echo esc_url( home_url( '/find-a-tutor?focus=matric' ) ); ?>" class="ngt-btn ngt-btn--white ngt-btn--lg"><?php esc_html_e( 'Get Matric Support', 'beyondinfinity' ); ?></a>
      </div>
    </section>
    <?php
}

function bi_sticky_mobile_cta() {
    if ( ! apply_filters( 'bi_show_sticky_cta', true ) ) {
        return;
    }
    if ( is_page( [ 'login', 'register', 'parent-dashboard', 'student-dashboard', 'tutor-dashboard', 'admin-dashboard' ] ) ) {
        return;
    }
    if ( bi_is_elementor_canvas_template() || bi_is_builder_edit_mode() ) {
        return;
    }

    $auth_url   = home_url( '/login/' );
    $auth_label = __( 'Login', 'beyondinfinity' );
    if ( is_user_logged_in() ) {
        $roles      = (array) wp_get_current_user()->roles;
        $auth_label = __( 'Dashboard', 'beyondinfinity' );
        if ( array_intersect( $roles, [ 'administrator', 'ngc_finance', 'ngc_support' ] ) ) {
            $auth_url = home_url( '/admin-dashboard/' );
        } elseif ( array_intersect( $roles, [ 'tutor' ] ) ) {
            $auth_url = home_url( '/tutor-dashboard/' );
        } elseif ( array_intersect( $roles, [ 'parent', 'parent_guardian' ] ) ) {
            $auth_url = home_url( '/parent-dashboard/' );
        } elseif ( array_intersect( $roles, [ 'student', 'subscriber' ] ) ) {
            $auth_url = home_url( '/student-dashboard/' );
        } else {
            $auth_url = home_url( '/' );
        }
    }
    ?>
    <nav class="bi-sticky-cta ngt-sticky-actions" aria-label="<?php esc_attr_e( 'Quick actions', 'beyondinfinity' ); ?>">
      <a href="<?php echo esc_url( home_url( '/find-a-tutor/' ) ); ?>" class="ngt-sticky-actions__link ngt-sticky-actions__link--primary"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
      <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="ngt-sticky-actions__link"><?php esc_html_e( 'Pricing', 'beyondinfinity' ); ?></a>
      <a href="<?php echo esc_url( $auth_url ); ?>" class="ngt-sticky-actions__link"><?php echo esc_html( $auth_label ); ?></a>
    </nav>
    <?php
}

function bi_whatsapp_fab() {
    // Superseded by the always-on right-hand float dock (includes WhatsApp).
    if ( apply_filters( 'bi_use_float_dock', true ) ) {
        return;
    }
    $post_id = is_singular() ? get_queried_object_id() : 0;
    if ( bi_theme_option_is_on( 'hide_whatsapp_fab', $post_id ) ) {
        return;
    }
    if ( ! apply_filters( 'bi_show_whatsapp_fab', bi_theme_option_is_on( 'bi_show_whatsapp' ) ) ) {
        return;
    }
    if ( bi_is_elementor_canvas_template() || bi_is_builder_edit_mode() ) {
        return;
    }
    $message = bi_get_theme_option( 'bi_whatsapp_message', 'Hi NextGen Tutors, I need help finding a tutor.' );
    ?>
    <a href="<?php echo esc_url( bi_whatsapp_url( $message ) ); ?>" class="bi-whatsapp-fab" data-testid="bi-whatsapp-fab" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'beyondinfinity' ); ?>">
      <?php echo bi_ui_icon( 'whatsapp', 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </a>
    <?php
}

/**
 * Mark body when the global float dock is enabled.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function bi_float_dock_body_class( $classes ) {
    if ( apply_filters( 'bi_use_float_dock', true ) ) {
        $classes[] = 'has-float-dock';
    }
    return $classes;
}
add_filter( 'body_class', 'bi_float_dock_body_class' );

/**
 * Always-on right-hand floating dock: back-to-top, match, support, chat, WhatsApp.
 */
function bi_float_dock() {
    if ( is_admin() || bi_is_elementor_canvas_template() || bi_is_builder_edit_mode() ) {
        return;
    }
    if ( ! apply_filters( 'bi_use_float_dock', true ) ) {
        return;
    }

    $wa_message = bi_get_theme_option( 'bi_whatsapp_message', 'Hi NextGen Tutors, I need help' );
    $wa_url     = function_exists( 'bi_whatsapp_url' ) ? bi_whatsapp_url( $wa_message ) : 'https://wa.me/27813340625';
    $find_url   = home_url( '/find-a-tutor/' );
    ?>
    <div class="float-dock float-dock--sticky-fab" id="float-dock" data-testid="float-dock" role="group" aria-label="<?php esc_attr_e( 'Quick actions', 'beyondinfinity' ); ?>">
      <button type="button" class="fdock-btn fdock-btn--toggle" id="fab-toggle" aria-expanded="false" aria-controls="fab-menu" aria-label="<?php esc_attr_e( 'Open quick actions', 'beyondinfinity' ); ?>">
        <svg class="fdock-icon-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z"/></svg>
        <svg class="fdock-icon-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
      <a class="fdock-btn fdock-btn--wa float-dock__persist" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'WhatsApp', 'beyondinfinity' ); ?>" title="<?php esc_attr_e( 'WhatsApp', 'beyondinfinity' ); ?>" data-testid="wa-dock-btn">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        <span class="fdock-tooltip"><?php esc_html_e( 'WhatsApp', 'beyondinfinity' ); ?></span>
      </a>
      <div class="float-dock__actions" id="fab-menu" hidden>
        <button type="button" class="fdock-btn fdock-btn--top float-dock__item" id="back-to-top" data-fab-index="0" aria-label="<?php esc_attr_e( 'Back to top', 'beyondinfinity' ); ?>" title="<?php esc_attr_e( 'Back to top', 'beyondinfinity' ); ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
          <span class="fdock-tooltip"><?php esc_html_e( 'Back to top', 'beyondinfinity' ); ?></span>
        </button>
        <a class="fdock-btn fdock-btn--match has-pulse float-dock__item" id="match-dock-btn" data-fab-index="1" href="<?php echo esc_url( $find_url ); ?>" aria-label="<?php esc_attr_e( 'Find a tutor match', 'beyondinfinity' ); ?>" title="<?php esc_attr_e( 'Match Tutor', 'beyondinfinity' ); ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.8 5.7 21l2.3-7.2-6-4.6h7.6z"/></svg>
          <span class="fdock-tooltip"><?php esc_html_e( 'Match Tutor', 'beyondinfinity' ); ?></span>
        </a>
        <button type="button" class="fdock-btn fdock-btn--support float-dock__item" id="support-dock-btn" data-fab-index="2" aria-label="<?php esc_attr_e( 'Support', 'beyondinfinity' ); ?>" title="<?php esc_attr_e( 'Support Centre', 'beyondinfinity' ); ?>" data-testid="support-dock-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
          <span class="fdock-tooltip"><?php esc_html_e( 'Support', 'beyondinfinity' ); ?></span>
        </button>
        <button type="button" class="fdock-btn fdock-btn--livechat float-dock__item" id="chat-dock-btn" data-fab-index="3" aria-label="<?php esc_attr_e( 'Live Chat', 'beyondinfinity' ); ?>" title="<?php esc_attr_e( 'Live Chat', 'beyondinfinity' ); ?>" data-testid="chat-dock-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span class="fdock-tooltip"><?php esc_html_e( 'Live Chat', 'beyondinfinity' ); ?></span>
        </button>
      </div>
    </div>
    <?php
}

function bi_dashboard_panel( $shortcode, $fallback_sections ) {
    $tag  = trim( str_replace( [ '[', ']' ], '', $shortcode ) );
    $type = bi_dashboard_type_from_shortcode( $shortcode );

    if ( shortcode_exists( $tag ) ) {
        echo '<div class="bi-dashboard-live">';
        echo do_shortcode( $shortcode );
        echo '</div>';
        return;
    }

    if ( $type && bi_dashboard_rest_available() ) {
        echo '<div class="bi-dashboard-rest ngt-card" data-dashboard="' . esc_attr( $type ) . '" role="region" aria-live="polite" aria-busy="true">';
        echo '<p class="bi-dashboard-rest__loading">' . esc_html__( 'Loading your dashboard…', 'beyondinfinity' ) . '</p>';
        echo '</div>';
        return;
    }

    echo '<div class="ngt-card bi-dashboard-fallback ngt-animate" style="padding:32px;margin-bottom:32px">';
    echo '<h2 style="margin-bottom:20px">' . esc_html__( 'Dashboard Sections', 'beyondinfinity' ) . '</h2>';
    bi_bullets( $fallback_sections );
    $hint = bi_companion_active()
        ? __( 'Dashboard data will load when REST endpoints are available.', 'beyondinfinity' )
        : __( 'Connect the platform plugin to load live dashboard data here.', 'beyondinfinity' );
    echo '<p style="margin-top:20px;font-size:.875rem;color:var(--ngt-text-3)">' . esc_html( $hint ) . '</p>';
    echo '</div>';
}