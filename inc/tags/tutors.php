<?php
/**
 * Template tags (split from inc/template-tags.php).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
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