<?php
/**
 * Template tags and reusable partials.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bi_get_phone() {
    return bi_get_theme_option( 'bi_phone', '081 334 0625' );
}

function bi_get_email() {
    return bi_get_theme_option( 'bi_email', 'admin@nextgentutors.co.za' );
}

function bi_get_support_email() {
    return bi_get_theme_option( 'bi_support_email', 'support@nextgentutors.co.za' );
}

function bi_get_whatsapp() {
    return bi_get_theme_option( 'bi_whatsapp', '27813340625' );
}

function bi_get_service_area() {
    return bi_get_theme_option( 'bi_service_area', 'Johannesburg launch, online support nationwide' );
}

function bi_whatsapp_url( $message = '' ) {
    $num = preg_replace( '/[^0-9]/', '', bi_get_whatsapp() );
    $url = 'https://wa.me/' . $num;
    if ( $message ) {
        $url .= '?text=' . rawurlencode( $message );
    }
    return $url;
}

function bi_provinces() {
    return [
        'gauteng'       => 'Gauteng',
        'western-cape'  => 'Western Cape',
        'kwazulu-natal' => 'KwaZulu-Natal',
        'eastern-cape'  => 'Eastern Cape',
        'free-state'    => 'Free State',
        'mpumalanga'    => 'Mpumalanga',
        'limpopo'       => 'Limpopo',
        'north-west'    => 'North West',
        'northern-cape' => 'Northern Cape',
    ];
}

/**
 * Resolve real platform metrics for public stats blocks.
 *
 * @return array<string, mixed>
 */
function bi_real_platform_metrics() {
    $cache_key = 'bi_real_platform_metrics_v1';
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $metrics = [
        'total_students'       => count( get_users( [ 'role' => 'student', 'fields' => 'ID' ] ) ),
        'total_tutors'         => count( get_users( [ 'role' => 'tutor', 'fields' => 'ID' ] ) ),
        'average_tutor_rating' => 0,
        'provinces_served'     => count( bi_provinces() ),
        'source'               => 'fallback',
    ];

    if ( class_exists( 'NGC_Platform_Analytics' ) ) {
        $snapshot = NGC_Platform_Analytics::snapshot();
        if ( is_array( $snapshot ) ) {
            $metrics['total_students']       = (int) ( $snapshot['total_students'] ?? $metrics['total_students'] );
            $metrics['total_tutors']         = (int) ( $snapshot['total_tutors'] ?? $metrics['total_tutors'] );
            $metrics['average_tutor_rating'] = (float) ( $snapshot['average_tutor_rating'] ?? 0 );
            $metrics['source']               = 'real';
        }
    }

    set_transient( $cache_key, $metrics, MINUTE_IN_SECONDS );
    return $metrics;
}

/**
 * Build hero/about stat cards from real data.
 *
 * @return array<int, array<string, mixed>>
 */
function bi_real_stat_cards() {
    $metrics = bi_real_platform_metrics();
    return [
        [
            'count'  => (int) $metrics['total_students'],
            'suffix' => $metrics['total_students'] > 0 ? '+' : '',
            'label'  => __( 'Learners linked', 'beyondinfinity' ),
        ],
        [
            'count'  => (int) $metrics['total_tutors'],
            'suffix' => $metrics['total_tutors'] > 0 ? '+' : '',
            'label'  => __( 'Vetted tutors', 'beyondinfinity' ),
        ],
        [
            'count'  => (float) $metrics['average_tutor_rating'],
            'suffix' => $metrics['average_tutor_rating'] > 0 ? '/5' : '',
            'label'  => __( 'Average tutor rating', 'beyondinfinity' ),
        ],
        [
            'count'  => (int) $metrics['provinces_served'],
            'suffix' => '',
            'label'  => __( 'Provinces served', 'beyondinfinity' ),
        ],
    ];
}

/**
 * Parse numeric value from formatted rate text (e.g. "R320/hr").
 *
 * @param string $rate Rate text.
 * @return float
 */
function bi_rate_to_number( $rate ) {
    $clean = preg_replace( '/[^0-9\.]/', '', (string) $rate );
    return $clean ? (float) $clean : 0.0;
}

/**
 * Shared marketing/onboarding KPIs from real platform sources.
 *
 * @return array<string, mixed>
 */
function bi_real_marketing_kpis() {
    $metrics = bi_real_platform_metrics();
    $kpis    = [
        'average_rating'          => $metrics['average_tutor_rating'] > 0 ? number_format( (float) $metrics['average_tutor_rating'], 1 ) . '★' : __( 'EMPTY STATE', 'beyondinfinity' ),
        'satisfaction'            => __( 'EMPTY STATE', 'beyondinfinity' ),
        'first_booking_window'    => '48h',
        'acceptance_rate'         => __( 'EMPTY STATE', 'beyondinfinity' ),
        'credential_accuracy'     => __( 'EMPTY STATE', 'beyondinfinity' ),
        'onboarding_total'        => (string) ( (int) $metrics['total_tutors'] + (int) $metrics['total_students'] ),
        'onboarding_completion'   => __( 'EMPTY STATE', 'beyondinfinity' ),
        'onboarding_overdue'      => __( 'EMPTY STATE', 'beyondinfinity' ),
        'onboarding_certified'    => (string) (int) $metrics['total_tutors'],
        'top_monthly_earnings'    => (float) ( class_exists( 'NGC_Platform_Analytics' ) ? ( NGC_Platform_Analytics::snapshot()['tutor_payouts'] ?? 0 ) : 0 ),
    ];

    if ( class_exists( 'NGC_Platform_Repository' ) ) {
        $all_apps  = (int) NGC_Platform_Repository::count( 'audit', [ 'object_type' => 'tutor_application' ] );
        $approved  = (int) NGC_Platform_Repository::count( 'audit', [ 'action' => 'tutor_approved' ] );
        if ( $all_apps > 0 ) {
            $kpis['acceptance_rate'] = round( ( $approved / $all_apps ) * 100 ) . '%';
        }

        $conv_total = (int) NGC_Platform_Repository::count( 'conversions' );
        if ( $conv_total > 0 ) {
            $paid = (int) NGC_Platform_Repository::count( 'conversions', [ 'event_key' => 'payment_completed' ] );
            $kpis['satisfaction'] = round( ( $paid / $conv_total ) * 100 ) . '%';
        }

        $profiles = NGC_Platform_Repository::list( 'user_profiles', [ 'limit' => 500 ] );
        if ( ! empty( $profiles ) ) {
            $complete = 0;
            $overdue  = 0;
            foreach ( $profiles as $profile ) {
                $pct = (int) ( $profile['profile_completeness'] ?? 0 );
                if ( $pct >= 80 ) {
                    ++$complete;
                }
                if ( $pct > 0 && $pct < 50 ) {
                    ++$overdue;
                }
            }
            $kpis['onboarding_completion'] = round( ( $complete / count( $profiles ) ) * 100 ) . '%';
            $kpis['onboarding_overdue']    = (string) $overdue;
        }
    }

    return $kpis;
}

/**
 * Policy/SLA labels (configurable, non-analytic values).
 *
 * @return array<string, string>
 */
function bi_policy_sla_labels() {
    return [
        'claim_window'        => (string) bi_get_theme_option( 'bi_claim_window_label', '24h' ),
        'rematch_window'      => (string) bi_get_theme_option( 'bi_rematch_window_label', '48h' ),
        'first_booking_target'=> (string) bi_get_theme_option( 'bi_first_booking_label', '48h' ),
        'background_refresh'  => (string) bi_get_theme_option( 'bi_background_refresh_label', '24mo' ),
    ];
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
        echo '<div class="bi-dashboard-rest ngt-card ngt-animate" data-dashboard="' . esc_attr( $type ) . '" role="region" aria-live="polite" aria-busy="true">';
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

/**
 * Render one tutor card for the 3D carousel.
 *
 * @param array<string, mixed> $tutor Tutor data.
 */
function bi_render_tutor_carousel_card( $tutor ) {
    $online = in_array( $tutor['groupType'] ?? '', [ 'online', 'both' ], true );
    $home   = in_array( $tutor['groupType'] ?? '', [ 'personal', 'in-person', 'both' ], true );
    $link   = $tutor['permalink'] ?? home_url( '/find-a-tutor' );
    $rate   = (int) ( $tutor['hourlyRate'] ?? 0 );
    $rating = number_format( (float) ( $tutor['rating'] ?? 4.8 ), 2 );

    $tutor_post_id = (int) ( $tutor['postId'] ?? 0 );
    $book_url      = $tutor_post_id && function_exists( 'bi_tutor_booking_url' )
        ? bi_tutor_booking_url( $tutor_post_id )
        : home_url( '/find-a-tutor/' );
    ?>
    <div class="tutor-card">
      <div class="tutor-card__photo">
        <?php if ( ! empty( $tutor['imageUrl'] ) ) : ?>
          <img src="<?php echo esc_url( $tutor['imageUrl'] ); ?>" alt="<?php echo esc_attr( $tutor['name'] ); ?>" loading="lazy" referrerpolicy="no-referrer" />
        <?php endif; ?>
        <div class="tutor-badges">
          <?php if ( $online ) : ?><span class="tutor-badge tutor-badge--online">Online</span><?php endif; ?>
          <?php if ( $home ) : ?><span class="tutor-badge tutor-badge--home">In-Person</span><?php endif; ?>
          <?php if ( ! empty( $tutor['vetted'] ) ) : ?><span class="tutor-badge tutor-badge--vetted"><?php esc_html_e( 'Verified', 'beyondinfinity' ); ?></span><?php endif; ?>
        </div>
        <?php if ( $rate > 0 ) : ?>
          <div class="tutor-price"><span class="r">R</span><span class="n"><?php echo esc_html( (string) $rate ); ?></span><span class="h">/hr</span></div>
        <?php endif; ?>
      </div>
      <div class="tutor-card__body">
        <div class="tutor-card__top">
          <h3 class="tutor-card__name"><?php echo esc_html( $tutor['name'] ); ?></h3>
          <span class="tutor-rating">★ <?php echo esc_html( $rating ); ?></span>
        </div>
        <?php if ( ! empty( $tutor['degree'] ) ) : ?>
          <div class="tutor-card__degree"><?php echo esc_html( $tutor['degree'] ); ?></div>
        <?php endif; ?>
        <?php if ( ! empty( $tutor['bio'] ) ) : ?>
          <p class="tutor-card__bio">“<?php echo esc_html( wp_trim_words( $tutor['bio'], 22 ) ); ?>”</p>
        <?php endif; ?>
        <?php if ( ! empty( $tutor['subjects'] ) ) : ?>
          <div class="tutor-tags">
            <?php foreach ( array_slice( (array) $tutor['subjects'], 0, 3 ) as $subject ) : ?>
              <span class="tutor-tag"><?php echo esc_html( $subject ); ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="tutor-card__btns">
          <a class="ngt-btn ngt-btn--outline ngt-btn--sm" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View Profile', 'beyondinfinity' ); ?></a>
          <a
            class="ngt-btn ngt-btn--secondary ngt-btn--sm bi-book-lesson-trigger"
            href="<?php echo esc_url( $book_url ); ?>"
            <?php if ( $tutor_post_id ) : ?>
              data-bi-booking-drawer="1"
              data-tutor-id="<?php echo esc_attr( (string) $tutor_post_id ); ?>"
              data-tutor-name="<?php echo esc_attr( (string) $tutor['name'] ); ?>"
            <?php endif; ?>
          ><?php esc_html_e( 'Book Session', 'beyondinfinity' ); ?></a>
        </div>
      </div>
    </div>
    <?php
}

/**
 * Tutors 3D carousel section.
 *
 * @param array<string, mixed> $args title, subtitle, eyebrow, limit, id.
 */
function bi_render_tutors_carousel( $args = [] ) {
    $args = wp_parse_args( $args, [
        'eyebrow'  => __( 'Meet a Few of Our Stars', 'beyondinfinity' ),
        'title'    => __( 'Tutors Who Change Trajectories', 'beyondinfinity' ),
        'subtitle' => __( 'Drag, swipe or use the arrows to explore. Every tutor is vetted and rated by real South African families.', 'beyondinfinity' ),
        'limit'    => 8,
        'id'       => 'bi-tutor-carousel-' . wp_unique_id(),
    ] );

    $tutors = bi_get_carousel_tutors( (int) $args['limit'] );
    if ( empty( $tutors ) ) {
        return;
    }

    $cid = sanitize_html_class( $args['id'] );
    ?>
    <section class="ngt-section bi-tutors-section" id="tutors">
      <div class="ngt-container">
        <div class="ngt-section__header ngt-animate bi-center">
          <?php if ( $args['eyebrow'] ) : ?>
            <p class="bi-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
          <?php endif; ?>
          <h2><?php echo esc_html( $args['title'] ); ?></h2>
          <?php if ( $args['subtitle'] ) : ?>
            <p><?php echo esc_html( $args['subtitle'] ); ?></p>
          <?php endif; ?>
        </div>
        <div class="carousel-3d bi-carousel-3d" id="<?php echo esc_attr( $cid ); ?>" data-carousel>
          <div class="carousel-3d__stage">
            <?php foreach ( $tutors as $i => $tutor ) : ?>
              <div class="tutor-card3d<?php echo ( function_exists( 'bi_3d_enabled' ) && bi_3d_enabled() ) ? ' bi-tilt-3d' : ''; ?>" data-index="<?php echo esc_attr( (string) $i ); ?>"<?php echo ( function_exists( 'bi_3d_enabled' ) && bi_3d_enabled() ) ? ' data-bi-tilt data-bi-tilt-max="6"' : ''; ?>>
                <?php if ( function_exists( 'bi_3d_enabled' ) && bi_3d_enabled() ) : ?>
                <div class="bi-tilt-3d__inner">
                <?php endif; ?>
                <?php bi_render_tutor_carousel_card( $tutor ); ?>
                <?php if ( function_exists( 'bi_3d_enabled' ) && bi_3d_enabled() ) : ?>
                </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="carousel-nav" data-carousel-nav="<?php echo esc_attr( $cid ); ?>">
          <button type="button" class="carousel-arrow" data-dir="-1" aria-label="<?php esc_attr_e( 'Previous tutor', 'beyondinfinity' ); ?>">‹</button>
          <div class="carousel-dots">
            <?php foreach ( $tutors as $i => $tutor ) : ?>
              <button type="button" class="cdot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr( (string) $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Tutor %d', 'beyondinfinity' ), $i + 1 ) ); ?>"></button>
            <?php endforeach; ?>
          </div>
          <button type="button" class="carousel-arrow" data-dir="1" aria-label="<?php esc_attr_e( 'Next tutor', 'beyondinfinity' ); ?>">›</button>
        </div>
      </div>
    </section>
    <?php
}

/**
 * Tutor directory grid (find-a-tutor page).
 *
 * @param int $limit Max tutors.
 */
function bi_render_tutor_directory( $limit = 12 ) {
    $subject  = bi_get_search_query_arg( 'subject' );
    $province = bi_get_search_query_arg( 'province' );
    $location = bi_get_search_query_arg( 'location' );
    $need_pool = $subject || $province || $location;
    $tutors   = bi_get_carousel_tutors( $need_pool ? $limit * 3 : $limit );

    if ( $subject && function_exists( 'bi_filter_tutors_by_subject' ) ) {
        $tutors = bi_filter_tutors_by_subject( $tutors, $subject );
    }
    if ( $province ) {
        $tutors = array_values(
            array_filter(
                $tutors,
                static function ( $tutor ) use ( $province ) {
                    $value = (string) ( $tutor['province'] ?? $tutor['location'] ?? '' );
                    return $value && ( sanitize_title( $value ) === $province || false !== stripos( $value, str_replace( '-', ' ', $province ) ) );
                }
            )
        );
    }
    if ( $need_pool ) {
        $tutors = array_slice( $tutors, 0, $limit );
    }

    if ( empty( $tutors ) && ! $subject && ! $province && ! $location ) {
        return;
    }

    $subject_label = $subject && function_exists( 'bi_subject_label_from_slug' )
        ? bi_subject_label_from_slug( $subject )
        : '';
    $province_label = '';
    if ( $province ) {
        $map = bi_provinces();
        $province_label = $map[ $province ] ?? ucwords( str_replace( '-', ' ', $province ) );
    }
    $place_label = $province_label ?: $location;
    ?>
    <section class="ngt-section ngt-section--alt" id="tutor-directory">
      <div class="ngt-container">
        <?php if ( $subject_label || $place_label ) : ?>
          <div class="bi-search-context ngt-card ngt-animate" style="padding:16px 20px;margin-bottom:20px">
            <p style="margin:0">
              <?php if ( $subject_label && $place_label ) : ?>
                <?php printf( esc_html__( 'Showing tutors for %1$s near %2$s.', 'beyondinfinity' ), esc_html( $subject_label ), esc_html( $place_label ) ); ?>
              <?php elseif ( $subject_label ) : ?>
                <?php printf( esc_html__( 'Showing tutors for %s.', 'beyondinfinity' ), esc_html( $subject_label ) ); ?>
              <?php else : ?>
                <?php printf( esc_html__( 'Showing tutors near %s.', 'beyondinfinity' ), esc_html( $place_label ) ); ?>
              <?php endif; ?>
              <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>"><?php esc_html_e( 'Clear search', 'beyondinfinity' ); ?></a>
            </p>
          </div>
        <?php endif; ?>
        <?php if ( empty( $tutors ) ) : ?>
          <div class="ngt-card ngt-animate bi-center" style="padding:32px">
            <p style="margin:0"><?php esc_html_e( 'No tutors matched this search yet. Complete the intake form below and we will match you personally.', 'beyondinfinity' ); ?></p>
          </div>
        <?php else : ?>
        <div class="ngt-section__header ngt-animate">
          <p class="bi-eyebrow"><?php esc_html_e( 'Browse vetted educators', 'beyondinfinity' ); ?></p>
          <h2><?php esc_html_e( 'Tutors Matching Your Search', 'beyondinfinity' ); ?></h2>
        </div>
        <div class="bi-dir-filters ngt-animate" role="group" aria-label="<?php esc_attr_e( 'Filter format', 'beyondinfinity' ); ?>">
          <button type="button" class="bi-fchip is-active" data-format="all"><?php esc_html_e( 'All', 'beyondinfinity' ); ?></button>
          <button type="button" class="bi-fchip" data-format="online"><?php esc_html_e( 'Online', 'beyondinfinity' ); ?></button>
          <button type="button" class="bi-fchip" data-format="personal"><?php esc_html_e( 'In-Person', 'beyondinfinity' ); ?></button>
        </div>
        <p class="bi-dir-count ngt-animate"><strong id="bi-dir-count"><?php echo esc_html( (string) count( $tutors ) ); ?></strong> <?php esc_html_e( 'tutors available', 'beyondinfinity' ); ?></p>
        <div class="bi-dir-grid" id="bi-dir-grid">
          <?php foreach ( $tutors as $tutor ) :
              $subject_slugs = array_map(
                  static function ( $s ) {
                      return sanitize_title( (string) $s );
                  },
                  (array) ( $tutor['subjects'] ?? [] )
              );
              ?>
            <div class="bi-dir-card ngt-card ngt-animate" data-format="<?php echo esc_attr( $tutor['groupType'] ?? 'both' ); ?>" data-subjects="<?php echo esc_attr( implode( ' ', $subject_slugs ) ); ?>">
              <?php bi_render_tutor_carousel_card( $tutor ); ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
}

/**
 * Full tutor profile layout (single tutors CPT).
 *
 * @param array<string, mixed> $tutor Tutor data from bi_format_tutor_post or demo roster.
 */
function bi_render_tutor_profile( $tutor ) {
    if ( empty( $tutor['name'] ) ) {
        return;
    }

    $rate    = (int) ( $tutor['hourlyRate'] ?? 0 );
    $rating  = number_format( (float) ( $tutor['rating'] ?? 4.8 ), 2 );
    $reviews = (int) ( $tutor['reviewsCount'] ?? 0 );
    $badges  = [
        __( 'ID Verified', 'beyondinfinity' ),
        __( 'Degree Certified', 'beyondinfinity' ),
        __( 'Background Cleared', 'beyondinfinity' ),
        __( 'Trial Passed', 'beyondinfinity' ),
        __( 'Curriculum Trained', 'beyondinfinity' ),
    ];
    $tutor_user_id = 0;
    if ( ! empty( $tutor['postId'] ) ) {
        $tutor_user_id = (int) get_post_meta( (int) $tutor['postId'], 'tutor_user_id', true );
        if ( ! $tutor_user_id ) {
            $post_obj = get_post( (int) $tutor['postId'] );
            if ( $post_obj ) {
                $tutor_user_id = (int) $post_obj->post_author;
            }
        }
    }
    $approved  = ! empty( $tutor['vetted'] ) || ( $tutor_user_id ? ( (bool) get_user_meta( $tutor_user_id, 'ngc_tutor_verified', true ) || (bool) get_user_meta( $tutor_user_id, 'ngt_tutor_verified', true ) ) : false );
    $suspended = $tutor_user_id ? ( (bool) get_user_meta( $tutor_user_id, 'ngc_tutor_suspended', true ) || (bool) get_user_meta( $tutor_user_id, 'ngt_tutor_suspended', true ) ) : false;
    $incomplete = empty( $tutor['bio'] ) || empty( $tutor['subjects'] );

    $profile_book_url = ! empty( $tutor['postId'] ) && function_exists( 'bi_tutor_booking_url' )
        ? bi_tutor_booking_url( (int) $tutor['postId'] )
        : home_url( '/find-a-tutor/' );
    $profile_message_url = ! empty( $tutor['postId'] )
        ? add_query_arg( 'ngc_tutor_id', (int) $tutor['postId'], home_url( '/contact/' ) )
        : home_url( '/contact/' );
    ?>
    <section class="bi-profile-hero ngt-hero" style="min-height:auto;padding:64px 0 48px">
      <div class="ngt-hero__bg" style="background:linear-gradient(135deg,var(--ngt-primary),#0a3d6b)"></div>
      <div class="ngt-container bi-profile-hero__inner ngt-animate">
        <div class="bi-profile-hero__photo">
          <?php if ( ! empty( $tutor['imageUrl'] ) ) : ?>
            <img src="<?php echo esc_url( $tutor['imageUrl'] ); ?>" alt="<?php echo esc_attr( $tutor['name'] ); ?>" loading="eager" referrerpolicy="no-referrer" />
          <?php endif; ?>
        </div>
        <div class="bi-profile-hero__body">
          <p class="bi-profile-hero__crumb"><a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a> / <?php echo esc_html( $tutor['name'] ); ?></p>
          <h1 class="bi-profile-hero__name"><?php echo esc_html( $tutor['name'] ); ?></h1>
          <?php if ( ! empty( $tutor['degree'] ) ) : ?>
            <p class="bi-profile-hero__degree"><?php echo esc_html( $tutor['degree'] ); ?></p>
          <?php endif; ?>
          <div class="bi-profile-hero__meta">
            <span>★ <?php echo esc_html( $rating ); ?><?php echo $reviews ? ' · ' . esc_html( (string) $reviews ) . ' ' . esc_html__( 'reviews', 'beyondinfinity' ) : ''; ?></span>
            <?php if ( $rate > 0 ) : ?>
              <span><?php echo esc_html( 'R' . $rate . '/hr' ); ?></span>
            <?php endif; ?>
          </div>
          <?php if ( $approved ) : ?>
            <div class="bi-profile-badges" aria-label="<?php esc_attr_e( 'Tutor verification', 'beyondinfinity' ); ?>">
              <?php foreach ( $badges as $badge ) : ?>
                <span class="bi-profile-badge"><?php echo esc_html( $badge ); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="bi-profile-hero__cta">
            <a
              href="<?php echo esc_url( $profile_book_url ); ?>"
              class="ngt-btn ngt-btn--secondary ngt-btn--lg bi-book-lesson-trigger"
              <?php if ( ! empty( $tutor['postId'] ) ) : ?>
                data-bi-booking-drawer="1"
                data-tutor-id="<?php echo esc_attr( (string) (int) $tutor['postId'] ); ?>"
                data-tutor-name="<?php echo esc_attr( (string) $tutor['name'] ); ?>"
              <?php endif; ?>
            ><?php esc_html_e( 'Book a Session', 'beyondinfinity' ); ?></a>
            <a href="<?php echo esc_url( $profile_message_url ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--lg" style="border-color:#fff;color:#fff"><?php esc_html_e( 'Message Tutor', 'beyondinfinity' ); ?></a>
          </div>
        </div>
      </div>
    </section>

    <section class="ngt-section">
      <div class="ngt-container bi-profile-grid">
        <div class="bi-profile-main">
          <div class="ngt-card ngt-animate" style="padding:32px;margin-bottom:24px">
            <h2 style="margin-bottom:16px"><?php esc_html_e( 'About', 'beyondinfinity' ); ?> <?php echo esc_html( $tutor['name'] ); ?></h2>
            <p style="margin:0;line-height:1.7;color:var(--ngt-text-2)"><?php echo esc_html( $tutor['bio'] ?? __( 'Patient, prepared tutoring tailored to South African curricula.', 'beyondinfinity' ) ); ?></p>
            <?php if ( ! empty( $tutor['subjects'] ) ) : ?>
              <div class="bi-profile-tags" style="margin-top:18px">
                <?php foreach ( (array) $tutor['subjects'] as $subject ) : ?>
                  <span class="bi-profile-tag"><?php echo esc_html( $subject ); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <?php
          bi_vsteps( [
            [ 'title' => __( 'Application Review', 'beyondinfinity' ), 'text' => __( 'Educational background, subject specialisation and teaching philosophy reviewed and approved.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Documentation Verified', 'beyondinfinity' ), 'text' => __( 'SA ID, university degree and academic transcript confirmed through official channels.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Subject Competency', 'beyondinfinity' ), 'text' => __( 'Written assessment on CAPS, IEB and Cambridge curriculum topics.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Teaching Trial', 'beyondinfinity' ), 'text' => __( 'Mock sessions and student simulation with our training team.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Background Check', 'beyondinfinity' ), 'text' => __( 'Criminal background check cleared via an accredited SA agency.', 'beyondinfinity' ) ],
          ] );
          ?>
          <div id="book" class="bi-profile-booking">
          <?php
          $calendar_template = trailingslashit( get_stylesheet_directory() ) . 'templates/tutor/calendar.php';
          $calendar_rendered = false;
          if ( file_exists( $calendar_template ) ) {
              $args = [
                  'tutor_id'   => ! empty( $tutor['postId'] ) ? (int) $tutor['postId'] : (int) $tutor_user_id,
                  'approved'   => $approved,
                  'suspended'  => $suspended,
                  'incomplete' => $incomplete,
              ];
              ob_start();
              include $calendar_template;
              $calendar_html = (string) ob_get_clean();
              if ( trim( $calendar_html ) ) {
                  $calendar_rendered = true;
                  echo $calendar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              }
          } elseif ( function_exists( 'ng_ui_component' ) && ! empty( $tutor['postId'] ) ) {
              $calendar_rendered = true;
              ?>
              <div class="ngt-card ngt-animate" style="padding:28px;margin-top:24px">
                <h2 style="margin-bottom:16px"><?php esc_html_e( 'Availability', 'beyondinfinity' ); ?></h2>
                <?php ng_ui_component( 'calendar-grid', [ 'tutor_id' => (int) $tutor['postId'], 'limit' => 12 ] ); ?>
              </div>
              <?php
          }

          // Booking must stay reachable even before this tutor publishes availability.
          if ( ! $calendar_rendered ) :
              ?>
              <div class="ngt-card ngt-animate bi-tutor-booking-fallback" style="padding:28px;margin-top:24px">
                <h2 style="margin-bottom:12px"><?php esc_html_e( 'Book a Session', 'beyondinfinity' ); ?></h2>
                <p style="margin:0 0 18px;color:var(--ngt-text-2)"><?php esc_html_e( 'This tutor has no published calendar slots yet. Send a booking request and our matching team will confirm a time within 24 hours.', 'beyondinfinity' ); ?></p>
                <a
                  href="<?php echo esc_url( $profile_book_url ); ?>"
                  class="ngt-btn ngt-btn--primary bi-book-lesson-trigger"
                  <?php if ( ! empty( $tutor['postId'] ) ) : ?>
                    data-bi-booking-drawer="1"
                    data-tutor-id="<?php echo esc_attr( (string) (int) $tutor['postId'] ); ?>"
                    data-tutor-name="<?php echo esc_attr( (string) $tutor['name'] ); ?>"
                  <?php endif; ?>
                ><?php esc_html_e( 'Request a booking', 'beyondinfinity' ); ?></a>
              </div>
              <?php
          endif;
          ?>
          </div>
        </div>
        <aside class="bi-profile-side">
          <div class="ngt-card ngt-animate" style="padding:28px;margin-bottom:20px">
            <h3 style="margin-bottom:14px"><?php esc_html_e( 'At a Glance', 'beyondinfinity' ); ?></h3>
            <?php bi_bullets( [
              __( 'Online & in-person sessions available', 'beyondinfinity' ),
              __( 'Grades 8–12 & tertiary support', 'beyondinfinity' ),
              __( 'CAPS, IEB & Cambridge curricula', 'beyondinfinity' ),
              __( 'Platform-managed payments only', 'beyondinfinity' ),
            ] ); ?>
            <a href="<?php echo esc_url( home_url( '/tutor-vetting' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--block" style="margin-top:20px"><?php esc_html_e( 'How We Vet Tutors', 'beyondinfinity' ); ?></a>
          </div>
          <div class="ngt-card ngt-animate bi-center" style="padding:28px;background:var(--ngt-primary-light)">
            <p style="margin:0 0 16px;font-weight:600"><?php esc_html_e( 'NextGen100 — love the first lesson or it is free.', 'beyondinfinity' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/guarantee' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'View Guarantee', 'beyondinfinity' ); ?></a>
          </div>
        </aside>
      </div>
    </section>
    <?php
}

/**
 * Hero subject/location search (pages-to-review/index.html).
 */
function bi_get_search_query_arg( $key ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( empty( $_GET[ $key ] ) ) {
        return '';
    }
    $raw = wp_unslash( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( 'location' === $key ) {
        return sanitize_text_field( $raw );
    }
    return sanitize_title( $raw );
}

/**
 * Hero subject/location search (pages-to-review/index.html).
 */
function bi_hero_search_form() {
    $subjects         = function_exists( 'bi_get_subject_tracks' ) ? bi_get_subject_tracks() : [];
    $selected_subject = bi_get_search_query_arg( 'subject' );
    $location         = bi_get_search_query_arg( 'location' );
    ?>
    <form class="bi-hero-search ngt-animate" action="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" method="get">
      <div class="bi-hero-search__field">
        <label class="screen-reader-text" for="bi-hero-subject"><?php esc_html_e( 'Subject', 'beyondinfinity' ); ?></label>
        <select id="bi-hero-subject" name="subject">
          <option value=""><?php esc_html_e( 'Choose a subject…', 'beyondinfinity' ); ?></option>
          <?php foreach ( $subjects as $subject ) :
              $slug = sanitize_title( $subject['name'] );
              ?>
            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $selected_subject, $slug ); ?>><?php echo esc_html( $subject['name'] ); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="bi-hero-search__field">
        <label class="screen-reader-text" for="bi-hero-location"><?php esc_html_e( 'Location', 'beyondinfinity' ); ?></label>
        <input type="text" id="bi-hero-location" name="location" value="<?php echo esc_attr( $location ); ?>" placeholder="<?php esc_attr_e( 'Your city or suburb', 'beyondinfinity' ); ?>" />
      </div>
      <button type="submit" class="ngt-btn ngt-btn--secondary"><?php esc_html_e( 'Search', 'beyondinfinity' ); ?></button>
    </form>
    <?php
}

/**
 * POPIA trust badges for legal pages.
 */
function bi_popia_badges() {
    ?>
    <div class="bi-popia-badges ngt-animate">
      <?php foreach (
          [
              __( 'POPIA-aligned practices', 'beyondinfinity' ),
              __( 'Secure hosting', 'beyondinfinity' ),
              __( 'Data never sold', 'beyondinfinity' ),
          ] as $badge
      ) : ?>
        <span class="bi-popia-badge"><?php echo esc_html( $badge ); ?></span>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Learner/parent dashboard intro + KPI strip (pages-to-review/dashboard.html).
 *
 * @param string $role student|parent
 */
function bi_learner_dashboard_intro( $role = 'student' ) {
    $user = wp_get_current_user();
    $name = $user->exists() ? $user->display_name : ( 'parent' === $role ? __( 'Parent', 'beyondinfinity' ) : __( 'Learner', 'beyondinfinity' ) );
    $eyebrow = 'parent' === $role
        ? __( 'Family Learning Hub', 'beyondinfinity' )
        : __( 'Your Learning Dashboard', 'beyondinfinity' );
    $sub = 'parent' === $role
        ? __( 'Manage your children, tutors and billing in one place.', 'beyondinfinity' )
        : __( 'Track your progress and manage your tutoring sessions.', 'beyondinfinity' );

    $metrics = 'parent' === $role
        ? [
            [ '📚', '3', __( 'Active Learners', 'beyondinfinity' ) ],
            [ '📅', '2', __( 'Upcoming Lessons', 'beyondinfinity' ) ],
            [ '💰', 'R1,280', __( 'This Month', 'beyondinfinity' ) ],
            [ '⭐', '4.9', __( 'Avg Tutor Rating', 'beyondinfinity' ) ],
        ]
        : [
            [ '📚', '18', __( 'Sessions Completed', 'beyondinfinity' ) ],
            [ '⭐', '4.9', __( 'Avg Tutor Rating', 'beyondinfinity' ) ],
            [ '💰', 'R540', __( 'Account Balance', 'beyondinfinity' ) ],
            [ '🏆', '3', __( 'Achievements', 'beyondinfinity' ) ],
        ];
    ?>
    <div class="bi-dash-head ngt-animate">
      <div>
        <p class="bi-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
        <h2 class="bi-dash-head__title"><?php echo esc_html( sprintf( __( 'Welcome back, %s', 'beyondinfinity' ), $name ) ); ?></h2>
        <p class="bi-dash-head__sub"><?php echo esc_html( $sub ); ?></p>
      </div>
    </div>
    <div class="bi-kpi-grid ngt-animate">
      <?php foreach ( $metrics as $m ) : ?>
        <div class="bi-kpi-card">
          <span class="bi-kpi-card__ico" aria-hidden="true"><?php echo esc_html( $m[0] ); ?></span>
          <div>
            <div class="bi-kpi-card__val"><?php echo esc_html( $m[1] ); ?></div>
            <div class="bi-kpi-card__lbl"><?php echo esc_html( $m[2] ); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="bi-dash-demo-note ngt-animate"><?php esc_html_e( 'Sample figures shown until companion REST data loads below.', 'beyondinfinity' ); ?></p>
    <?php
}

/**
 * Page builder compatibility grid (wordpress-setup.html).
 *
 * @param array<int, array{0:string,1:string,2:string}> $items
 */
function bi_compat_grid( $items ) {
    ?>
    <div class="bi-compat-grid">
      <?php foreach ( $items as $i => $item ) : ?>
        <div class="ngt-card bi-compat-card ngt-animate ngt-animate--delay-<?php echo esc_attr( (string) ( ( $i % 4 ) + 1 ) ); ?>">
          <span class="bi-compat-card__ico" aria-hidden="true"><?php echo esc_html( $item[0] ); ?></span>
          <h3><?php echo esc_html( $item[1] ); ?></h3>
          <p><?php echo esc_html( $item[2] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Required/optional plugin cards (wordpress-setup.html).
 *
 * @param array<int, array{0:string,1:string,2:string,3:string,4:string}> $plugins
 */
function bi_plugin_grid( $plugins ) {
    ?>
    <div class="bi-plugin-grid">
      <?php foreach ( $plugins as $i => $p ) : ?>
        <div class="ngt-card bi-plugin-card ngt-animate ngt-animate--delay-<?php echo esc_attr( (string) ( ( $i % 3 ) + 1 ) ); ?>">
          <div class="bi-plugin-card__head">
            <span aria-hidden="true"><?php echo esc_html( $p[0] ); ?></span>
            <span class="bi-plugin-card__tag bi-plugin-card__tag--<?php echo esc_attr( $p[4] ); ?>"><?php echo esc_html( $p[2] ); ?></span>
          </div>
          <h3><?php echo esc_html( $p[1] ); ?></h3>
          <p><?php echo esc_html( $p[3] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Custom DB table reference grid.
 *
 * @param array<int, array{0:string,1:string}> $tables
 */
function bi_db_table_grid( $tables ) {
    ?>
    <div class="bi-db-grid">
      <?php foreach ( $tables as $row ) : ?>
        <div class="ngt-card bi-db-card ngt-animate">
          <code class="bi-db-card__name"><?php echo esc_html( $row[0] ); ?></code>
          <p><?php echo esc_html( $row[1] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Percent of registry pages with default PHP files on disk.
 */
function bi_setup_progress_percent() {
    if ( ! function_exists( 'bi_pages_registry' ) ) {
        return 0;
    }
    $registry = bi_pages_registry();
    $total    = count( $registry );
    $ok       = 0;
    foreach ( $registry as $meta ) {
        $path = BI_DIR . '/inc/defaults/' . ( $meta['default'] ?? '' );
        if ( file_exists( $path ) ) {
            ++$ok;
        }
    }
    return $total ? (int) round( ( $ok / $total ) * 100 ) : 0;
}

/**
 * Static tutor dashboard intro strip (live data loads via REST below).
 */
function bi_tutor_dashboard_intro() {
    $user     = wp_get_current_user();
    $name     = $user->exists() ? $user->display_name : __( 'Tutor', 'beyondinfinity' );
    $statuses = [];

    if ( $user->exists() ) {
        $verified = (bool) get_user_meta( $user->ID, 'ngt_tutor_verified', true );
        if ( function_exists( 'ngc_get_tutor_post_by_user_id' ) ) {
            $tutor_post = ngc_get_tutor_post_by_user_id( $user->ID );
            if ( $tutor_post ) {
                $verified = $verified || (bool) get_post_meta( $tutor_post->ID, 'tutor_verified', true );
            }
        }
        $statuses[] = $verified
            ? [ 'label' => __( 'Vetting approved', 'beyondinfinity' ), 'ok' => true ]
            : [ 'label' => __( 'Vetting in progress', 'beyondinfinity' ), 'ok' => false ];
    }
    ?>
    <div class="bi-tdash-intro ngt-animate">
      <div>
        <p class="bi-eyebrow" style="color:var(--ngt-secondary-dark)"><?php esc_html_e( 'Tutor Portal', 'beyondinfinity' ); ?></p>
        <h2 class="bi-tdash-intro__title"><?php echo esc_html( sprintf( __( 'Welcome, %s', 'beyondinfinity' ), $name ) ); ?></h2>
        <p class="bi-tdash-intro__sub"><?php esc_html_e( 'Track earnings, sessions and account standing.', 'beyondinfinity' ); ?></p>
      </div>
      <?php if ( ! empty( $statuses ) ) : ?>
      <div class="bi-tdash-statuses">
        <?php foreach ( $statuses as $status ) : ?>
          <span class="bi-tdash-status<?php echo ! empty( $status['ok'] ) ? ' bi-tdash-status--ok' : ''; ?>"><?php echo esc_html( $status['label'] ); ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php
}

