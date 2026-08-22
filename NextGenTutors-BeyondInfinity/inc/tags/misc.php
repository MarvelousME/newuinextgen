<?php
/**
 * Template tags (split from inc/template-tags.php).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

