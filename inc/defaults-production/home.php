<?php
/**
 * Kinetic homepage — amalgamated from nextgen-tutors-kinetic-homepage.html
 * with BeyondInfinity data, theme URLs, and UI anti-pattern fixes.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$find_url     = home_url( '/find-a-tutor' );
$become_url   = home_url( '/become-a-tutor' );
$pricing_url  = home_url( '/pricing' );
$contact_url  = home_url( '/contact' );
$guarantee_url = home_url( '/guarantee' );
$subject_tabs = bi_kinetic_subject_tabs();
$first_tab    = $subject_tabs[0];
$marquee      = array_merge( wp_list_pluck( bi_get_subject_tracks(), 'name' ), [ 'Exam Prep', 'University Support' ] );

$hero_stats = bi_real_stat_cards();

$cms_hero_badge = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'hero', 'badge', '' ) : '';
$cms_hero_title = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'hero', 'title', '' ) : '';
$cms_hero_accent = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'hero', 'title_accent', '' ) : '';
$cms_hero_lead = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'hero', 'lead', '' ) : '';
$cms_faqs = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'faq', 'items', [] ) : [];
$cms_trust      = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'trust_bar' ) : [];
$cms_subjects   = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'subject_explorer' ) : [];
$cms_journey    = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'how_it_works' ) : [];
$cms_pathways   = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'learning_modes' ) : [];
$cms_tutors     = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'featured_tutors' ) : [];
$cms_reviews    = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'success_stories' ) : [];
$cms_proof      = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'trust_safety' ) : [];
$cms_pricing    = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'pricing' ) : [];
$cms_faq_meta   = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'faq' ) : [];
$cms_cta        = function_exists( 'ngc_home_section' ) ? ngc_home_section( 'cta' ) : [];

$pricing_plans = [
    [
        'name'     => __( 'Online Classroom', 'beyondinfinity' ),
        'price'    => bi_format_rate( 'rate_online', 320 ),
        'featured' => false,
        'items'    => [ __( 'Online tutoring', 'beyondinfinity' ), __( 'Homework support', 'beyondinfinity' ), __( 'Progress notes', 'beyondinfinity' ) ],
    ],
    [
        'name'     => __( 'In-Person at Home', 'beyondinfinity' ),
        'price'    => bi_format_rate( 'rate_inperson', 350 ),
        'featured' => true,
        'items'    => [ __( 'Online + in-person', 'beyondinfinity' ), __( 'Priority matching', 'beyondinfinity' ), __( 'Parent reporting', 'beyondinfinity' ) ],
    ],
    [
        'name'     => __( 'Tertiary Subjects', 'beyondinfinity' ),
        'price'    => bi_format_rate( 'rate_tertiary', 500 ),
        'featured' => false,
        'items'    => [ __( 'University support', 'beyondinfinity' ), __( 'Project guidance', 'beyondinfinity' ), __( 'Exam preparation', 'beyondinfinity' ) ],
    ],
];

$journeys = [
    [ 'title' => __( 'Parent Journey', 'beyondinfinity' ), 'copy' => __( 'Book assessment, match tutor, track progress and manage payments.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Student Journey', 'beyondinfinity' ), 'copy' => __( 'View lessons, subjects, achievements and personal progress.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Tutor Journey', 'beyondinfinity' ), 'copy' => __( 'Manage bookings, learners, availability, reviews and earnings.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Admin Journey', 'beyondinfinity' ), 'copy' => __( 'Monitor CRM, workflows, bookings and platform health.', 'beyondinfinity' ) ],
];

if ( ! empty( $cms_pathways['modes'] ) && is_array( $cms_pathways['modes'] ) ) {
	$journeys = array_map(
		static function ( $mode ) {
			return [
				'title' => (string) ( $mode['title'] ?? '' ),
				'copy'  => (string) ( $mode['copy'] ?? '' ),
			];
		},
		$cms_pathways['modes']
	);
}

$faqs = [
    [
        'q' => __( 'Can parents track progress?', 'beyondinfinity' ),
        'a' => __( 'Yes. The parent dashboard highlights attendance, homework, upcoming lessons, tutor notes and progress reports after each session.', 'beyondinfinity' ),
    ],
    [
        'q' => __( 'Are tutors background-checked?', 'beyondinfinity' ),
        'a' => __( 'Every tutor passes ID verification, academic credential checks, subject assessments and criminal background clearance before appearing on the platform.', 'beyondinfinity' ),
    ],
    [
        'q' => __( 'What if the first lesson is not a fit?', 'beyondinfinity' ),
        'a' => sprintf(
            /* translators: %s: guarantee program label */
            __( 'Our %s guarantee covers your first lesson — we will rematch you with another tutor or refund you. No awkward conversations.', 'beyondinfinity' ),
            bi_guarantee_label()
        ),
    ],
];

if ( is_array( $cms_faqs ) && $cms_faqs ) {
	$faqs = array_map(
		static function ( $item ) {
			return [
				'q' => (string) ( $item['q'] ?? '' ),
				'a' => (string) ( $item['a'] ?? '' ),
			];
		},
		$cms_faqs
	);
}
?>

<?php if ( ! bi_use_kinetic_home() ) : ?>
<section class="ngt-section ngi-home ngi-home--classic">
  <div class="ngt-container">
    <div class="ngt-card" style="padding:48px;text-align:center">
      <h1><?php esc_html_e( 'NextGen Tutors', 'beyondinfinity' ); ?></h1>
      <p><?php esc_html_e( 'Personalised tutoring across South Africa — online, in-person, and hybrid.', 'beyondinfinity' ); ?></p>
      <div class="ngi-actions" style="justify-content:center;margin-top:24px">
        <a class="ngt-btn ngt-btn--primary" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
        <a class="ngt-btn ngt-btn--outline" href="<?php echo esc_url( $become_url ); ?>"><?php esc_html_e( 'Become a Tutor', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</section>
<?php return; endif; ?>

<div class="ngi-home" id="nextgen-home">
  <?php /* Floating Book CTA removed — right-hand float dock covers global actions. */ ?>

  <?php if ( bi_home_section_enabled( 'hero' ) ) : ?>
  <section class="ngi-hero ngi-hero--theme ngi-hero--cinematic<?php echo function_exists( 'bi_get_hero_video_url' ) && bi_get_hero_video_url() ? ' ngi-hero--has-video' : ''; ?>" aria-label="<?php esc_attr_e( 'NextGen Tutors homepage hero', 'beyondinfinity' ); ?>">
    <?php
    $hero_video  = function_exists( 'bi_get_hero_video_url' ) ? bi_get_hero_video_url() : esc_url( (string) bi_get_theme_option( 'home_hero_video_url', '' ) );
    $hero_poster = function_exists( 'bi_get_hero_video_poster_url' ) ? bi_get_hero_video_poster_url() : ( function_exists( 'bi_get_theme_image_url' ) ? bi_get_theme_image_url( 'home_video' ) : '' );
    if ( $hero_video ) :
        ?>
      <video
        class="ngi-hero-video"
        id="ngiHeroVideo"
        data-bi-cinematic
        muted
        loop
        playsinline
        preload="metadata"
        <?php echo $hero_poster ? 'poster="' . esc_url( $hero_poster ) . '"' : ''; ?>
        aria-hidden="true"
      >
        <source src="<?php echo esc_url( $hero_video ); ?>" type="video/mp4" />
      </video>
    <?php endif; ?>
    <div class="ngi-kh-mesh" aria-hidden="true"></div>
    <div class="ngi-wrap">
      <div class="ngi-hero-grid">
        <div>
          <div class="ngi-badge ngi-reveal">
            <?php echo bi_kinetic_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span><?php echo $cms_hero_badge ? esc_html( $cms_hero_badge ) : esc_html__( 'Premium online, in-person and hybrid tutoring', 'beyondinfinity' ); ?></span>
          </div>
          <h1 class="ngi-title ngi-reveal">
            <?php echo $cms_hero_title ? esc_html( $cms_hero_title ) : esc_html__( 'Your Tutor. Your Pace.', 'beyondinfinity' ); ?>
            <span class="ngi-accent"><?php echo $cms_hero_accent ? esc_html( $cms_hero_accent ) : esc_html__( 'Your Results.', 'beyondinfinity' ); ?></span>
          </h1>
          <p class="ngi-lead ngi-reveal">
            <?php echo $cms_hero_lead ? esc_html( $cms_hero_lead ) : esc_html__( 'Connect with background-checked tutors for CAPS, IEB and Cambridge — online or in-person, from Grade 1 to varsity.', 'beyondinfinity' ); ?>
          </p>
          <div class="ngi-actions ngi-reveal">
            <a class="ngi-btn ngi-btn-primary ngi-magnetic" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Find My Tutor', 'beyondinfinity' ); ?></a>
            <a class="ngi-btn ngi-btn-secondary" href="<?php echo esc_url( $become_url ); ?>"><?php esc_html_e( 'Become a Tutor', 'beyondinfinity' ); ?></a>
          </div>
          <div class="ngi-stats ngi-reveal" aria-label="<?php esc_attr_e( 'Platform statistics', 'beyondinfinity' ); ?>">
            <?php foreach ( $hero_stats as $stat ) : ?>
              <div class="ngi-stat">
                <strong data-count="<?php echo esc_attr( (string) $stat['count'] ); ?>" data-suffix="<?php echo esc_attr( $stat['suffix'] ); ?>">0<?php echo esc_html( $stat['suffix'] ); ?></strong>
                <small><?php echo esc_html( $stat['label'] ); ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if ( 'search' === bi_get_theme_option( 'home_hero_right_panel', 'progress' ) ) : ?>
          <?php bi_kinetic_hero_search_panel(); ?>
        <?php else : ?>
        <div class="ngi-visual" data-kh-ambient-visual>
          <div class="ngi-glow" aria-hidden="true"></div>
          <div class="ngi-float-row ngi-float-row--top" aria-hidden="true">
            <div class="ngi-float ngi-f1"><?php echo bi_kinetic_icon( 'book' ); // phpcs:ignore ?><span><?php esc_html_e( 'Maths Rescue', 'beyondinfinity' ); ?></span></div>
            <div class="ngi-float ngi-f4"><?php echo bi_kinetic_icon( 'shield' ); // phpcs:ignore ?><span><?php esc_html_e( 'Verified Match', 'beyondinfinity' ); ?></span></div>
          </div>
          <div class="ngi-panel ngi-reveal" data-kh-motion-container>
            <div class="ngi-panel-head">
              <div>
                <h2 style="margin:0"><?php esc_html_e( 'Parent Dashboard', 'beyondinfinity' ); ?></h2>
                <small><?php esc_html_e( 'Live progress preview', 'beyondinfinity' ); ?></small>
              </div>
              <div class="ngi-kpi-pill"><?php esc_html_e( 'Online + In-person', 'beyondinfinity' ); ?></div>
            </div>
            <div class="ngi-progress-card">
              <div class="ngi-progress-title"><span id="ngiCourseName"><?php esc_html_e( 'Mathematics', 'beyondinfinity' ); ?></span><span id="ngiCourseScore">82%</span></div>
              <div class="ngi-bar" role="progressbar" aria-valuenow="82" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'Mathematics progress', 'beyondinfinity' ); ?>"><span id="ngiCourseBar"></span></div>
            </div>
            <div class="ngi-progress-card">
              <div class="ngi-progress-title"><span><?php esc_html_e( 'Homework Completion', 'beyondinfinity' ); ?></span><span id="ngiHomeworkScore">76%</span></div>
              <div class="ngi-bar" role="progressbar" aria-valuenow="76" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'Homework completion', 'beyondinfinity' ); ?>"><span id="ngiHomeworkBar"></span></div>
            </div>
            <div class="ngi-chips" role="group" aria-label="<?php esc_attr_e( 'Subject preview controls', 'beyondinfinity' ); ?>">
              <button class="ngi-chip is-active" type="button" aria-pressed="true" data-course="<?php esc_attr_e( 'Mathematics', 'beyondinfinity' ); ?>" data-score="82" data-homework="76"><?php esc_html_e( 'Maths', 'beyondinfinity' ); ?></button>
              <button class="ngi-chip" type="button" aria-pressed="false" data-course="<?php esc_attr_e( 'Physical Science', 'beyondinfinity' ); ?>" data-score="74" data-homework="69"><?php esc_html_e( 'Science', 'beyondinfinity' ); ?></button>
              <button class="ngi-chip" type="button" aria-pressed="false" data-course="<?php esc_attr_e( 'English', 'beyondinfinity' ); ?>" data-score="88" data-homework="91"><?php esc_html_e( 'English', 'beyondinfinity' ); ?></button>
              <button class="ngi-chip" type="button" aria-pressed="false" data-course="<?php esc_attr_e( 'Accounting', 'beyondinfinity' ); ?>" data-score="79" data-homework="84"><?php esc_html_e( 'Accounting', 'beyondinfinity' ); ?></button>
            </div>
            <div class="ngi-dashgrid">
              <div class="ngi-dashitem">
                <?php echo bi_kinetic_icon( 'calendar' ); // phpcs:ignore ?>
                <div class="ngi-dashitem__body"><span class="ngi-dashitem__label"><?php esc_html_e( 'Next lesson', 'beyondinfinity' ); ?></span><b><?php esc_html_e( 'Today 17:00', 'beyondinfinity' ); ?></b></div>
              </div>
              <div class="ngi-dashitem">
                <?php echo bi_kinetic_icon( 'check' ); // phpcs:ignore ?>
                <div class="ngi-dashitem__body"><span class="ngi-dashitem__label"><?php esc_html_e( 'Homework', 'beyondinfinity' ); ?></span><b><?php esc_html_e( '4/5 complete', 'beyondinfinity' ); ?></b></div>
              </div>
              <div class="ngi-dashitem">
                <?php echo bi_kinetic_icon( 'user' ); // phpcs:ignore ?>
                <div class="ngi-dashitem__body"><span class="ngi-dashitem__label"><?php esc_html_e( 'Tutor match', 'beyondinfinity' ); ?></span><b><?php esc_html_e( 'Verified', 'beyondinfinity' ); ?></b></div>
              </div>
              <div class="ngi-dashitem">
                <?php echo bi_kinetic_icon( 'star' ); // phpcs:ignore ?>
                <div class="ngi-dashitem__body"><span class="ngi-dashitem__label"><?php esc_html_e( 'Rating', 'beyondinfinity' ); ?></span><b><?php esc_html_e( '4.9 / 5', 'beyondinfinity' ); ?></b></div>
              </div>
            </div>
          </div>
          <div class="ngi-float-row ngi-float-row--bottom" aria-hidden="true">
            <div class="ngi-float ngi-f2"><?php echo bi_kinetic_icon( 'target' ); // phpcs:ignore ?><span><?php esc_html_e( 'Exam Prep', 'beyondinfinity' ); ?></span></div>
            <div class="ngi-float ngi-f3"><?php echo bi_kinetic_icon( 'chart' ); // phpcs:ignore ?><span><?php esc_html_e( 'Parent Reports', 'beyondinfinity' ); ?></span></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="ngi-shape" aria-hidden="true"></div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'trust' ) ) : ?>
  <section class="ngi-section" id="trust">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_trust['eyebrow'] ?? __( 'Trusted learning ecosystem', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_trust['heading'] ?? __( 'Everything parents need to move from worry to progress.', 'beyondinfinity' ) ); ?></h2>
        <p class="ngi-subtitle"><?php echo esc_html( $cms_trust['subtitle'] ?? __( 'Registration, tutor matching, booking, CRM follow-up, payment status, dashboards and verification — built for South African families.', 'beyondinfinity' ) ); ?></p>
      </div>
      <div class="ngi-card-grid">
        <article class="ngi-card ngi-reveal bi-tilt-3d bi-tilt-3d--decorative" data-bi-tilt data-bi-tilt-max="8">
          <div class="bi-tilt-3d__inner">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'compass' ); // phpcs:ignore ?></div>
          <h3><?php esc_html_e( 'Guided tutor matching', 'beyondinfinity' ); ?></h3>
          <p><?php esc_html_e( 'Select subject, grade, province and format — then move into the right registration and placement journey.', 'beyondinfinity' ); ?></p>
          </div>
        </article>
        <article class="ngi-card ngi-reveal bi-tilt-3d bi-tilt-3d--decorative" data-bi-tilt data-bi-tilt-max="8">
          <div class="bi-tilt-3d__inner">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'users' ); // phpcs:ignore ?></div>
          <h3><?php esc_html_e( 'Verified tutor profiles', 'beyondinfinity' ); ?></h3>
          <p><?php esc_html_e( 'Credentials, subjects, availability, reviews and booking CTAs with a premium marketplace feel.', 'beyondinfinity' ); ?></p>
          </div>
        </article>
        <article class="ngi-card ngi-reveal bi-tilt-3d bi-tilt-3d--decorative" data-bi-tilt data-bi-tilt-max="8">
          <div class="bi-tilt-3d__inner">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'layout' ); // phpcs:ignore ?></div>
          <h3><?php esc_html_e( 'Dashboard-first experience', 'beyondinfinity' ); ?></h3>
          <p><?php esc_html_e( 'Students, parents and tutors see lessons, assignments, progress, bookings and payment status in one place.', 'beyondinfinity' ); ?></p>
          </div>
        </article>
      </div>
      <div class="ngi-marquee" data-kh-marquee aria-hidden="true">
        <div class="ngi-marquee-track">
          <?php foreach ( array_merge( $marquee, $marquee ) as $chip ) : ?>
            <span><?php echo esc_html( $chip ); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( function_exists( 'ng_ui_component' ) && bi_home_section_enabled( 'trust' ) ) : ?>
  <section class="ngi-section ngi-alt" id="ng-ui-stats" aria-label="<?php esc_attr_e( 'Platform statistics', 'beyondinfinity' ); ?>">
    <div class="ngi-wrap">
      <?php ng_ui_component( 'stats-band' ); ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'subjects' ) ) : ?>
  <section class="ngi-section ngi-alt" id="subjects">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_subjects['eyebrow'] ?? __( 'Subject explorer', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_subjects['title'] ?? __( 'Click a subject and watch the learning plan adapt.', 'beyondinfinity' ) ); ?></h2>
        <p class="ngi-subtitle"><?php echo esc_html( $cms_subjects['subtitle'] ?? __( 'Every track is mapped to CAPS, IEB and Cambridge outcomes.', 'beyondinfinity' ) ); ?></p>
      </div>
      <div class="ngi-subject-shell">
        <div class="ngi-subject-tabs ngi-reveal" role="tablist" aria-label="<?php esc_attr_e( 'Subjects', 'beyondinfinity' ); ?>">
          <?php foreach ( $subject_tabs as $i => $tab ) : ?>
            <button class="ngi-tab<?php echo 0 === $i ? ' is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
              data-title="<?php echo esc_attr( $tab['title'] ); ?>"
              data-body="<?php echo esc_attr( $tab['body'] ); ?>"
              data-bullets="<?php echo esc_attr( implode( '|', $tab['bullets'] ) ); ?>">
              <?php echo esc_html( $tab['title'] ); ?> <span aria-hidden="true">→</span>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="ngi-subject-panel ngi-reveal" role="tabpanel">
          <h3 id="ngiSubjectTitle"><?php echo esc_html( $first_tab['title'] ); ?></h3>
          <p id="ngiSubjectBody"><?php echo esc_html( $first_tab['body'] ); ?></p>
          <div class="ngi-bullet-grid" id="ngiSubjectBullets">
            <?php foreach ( $first_tab['bullets'] as $bullet ) : ?>
              <div class="ngi-bullet"><?php echo esc_html( $bullet ); ?></div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:26px">
            <a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Get Subject Help', 'beyondinfinity' ); ?></a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'journey' ) ) : ?>
  <section class="ngi-section" id="journey">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_journey['eyebrow'] ?? __( 'Learner journey', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_journey['title'] ?? __( 'A clear path from assessment to measurable improvement.', 'beyondinfinity' ) ); ?></h2>
      </div>
      <div class="ngi-steps">
        <?php
        $steps = [];
        if ( ! empty( $cms_journey['steps'] ) && is_array( $cms_journey['steps'] ) ) {
            foreach ( $cms_journey['steps'] as $step ) {
                $steps[] = [ (string) ( $step['title'] ?? '' ), (string) ( $step['copy'] ?? '' ) ];
            }
        }
        if ( ! $steps ) {
            $steps = [
                [ __( 'Assessment', 'beyondinfinity' ), __( 'Identify gaps.', 'beyondinfinity' ) ],
                [ __( 'Tutor Match', 'beyondinfinity' ), __( 'Assign fit.', 'beyondinfinity' ) ],
                [ __( 'Learning Plan', 'beyondinfinity' ), __( 'Set goals.', 'beyondinfinity' ) ],
                [ __( 'Weekly Lessons', 'beyondinfinity' ), __( 'Track work.', 'beyondinfinity' ) ],
                [ __( 'Reports', 'beyondinfinity' ), __( 'Show progress.', 'beyondinfinity' ) ],
            ];
        }
        foreach ( $steps as $i => $step ) :
            ?>
          <div class="ngi-step ngi-reveal">
            <div class="ngi-num" aria-hidden="true"><?php echo (int) ( $i + 1 ); ?></div>
            <b><?php echo esc_html( $step[0] ); ?></b>
            <p><?php echo esc_html( $step[1] ); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'narrative' ) && function_exists( 'bi_render_tutoring_narrative_scroll' ) ) : ?>
    <?php bi_render_tutoring_narrative_scroll(); ?>
  <?php endif; ?>

  <div class="ngi-scroll-divider" aria-hidden="true"><span id="ngiScrollDivider"></span></div>

  <?php if ( bi_home_section_enabled( 'highlights' ) ) : ?>
  <section class="ngi-section ngi-aura" id="platform-highlights">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php esc_html_e( 'Why families choose us', 'beyondinfinity' ); ?></div>
        <h2 class="ngi-heading ngi-kinetic-text" data-bi-slide-title><?php esc_html_e( 'Clear proof, safer matching, better first lessons.', 'beyondinfinity' ); ?></h2>
        <p class="ngi-subtitle"><?php esc_html_e( 'From vetted tutors to parent dashboards and a first-lesson guarantee — every step is built for confidence.', 'beyondinfinity' ); ?></p>
      </div>
      <div class="ngi-feature-grid">
        <article class="ngi-card ngi-kinetic-box ngi-reveal">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'box' ); // phpcs:ignore ?></div>
          <h3><?php esc_html_e( 'Verified trust signals', 'beyondinfinity' ); ?></h3>
          <p><?php esc_html_e( 'See ID checks, background screening, subject credentials and live availability before you book.', 'beyondinfinity' ); ?></p>
          <a class="ngi-btn ngi-btn-primary ngi-magnetic" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
        </article>
        <article class="ngi-card ngi-reveal">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'type' ); // phpcs:ignore ?></div>
          <h3><?php esc_html_e( 'Progress you can follow', 'beyondinfinity' ); ?></h3>
          <p><?php esc_html_e( 'Session notes, goals and report cards stay visible in your dashboard — even when motion effects are turned off.', 'beyondinfinity' ); ?></p>
          <p class="ngi-kh-note"><?php esc_html_e( 'Accessible by design: every update remains readable without animation.', 'beyondinfinity' ); ?></p>
        </article>
        <article class="ngi-card ngi-reveal">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'sparkles' ); // phpcs:ignore ?></div>
          <h3><?php esc_html_e( 'Fast next steps', 'beyondinfinity' ); ?></h3>
          <p><?php esc_html_e( 'Match a tutor, request an assessment, or apply to teach — clear CTAs for parents, students and educators.', 'beyondinfinity' ); ?></p>
          <button class="ngi-btn ngi-btn-primary ngi-magnetic" data-ngi-open type="button"><?php esc_html_e( 'Book Assessment', 'beyondinfinity' ); ?></button>
        </article>
        <article class="ngi-card ngi-reveal">
          <div class="ngi-icon"><?php echo bi_kinetic_icon( 'shield' ); // phpcs:ignore ?></div>
          <h3><?php echo esc_html( sprintf( __( '%s guarantee', 'beyondinfinity' ), bi_guarantee_label() ) ); ?></h3>
          <p><?php esc_html_e( 'Love the first lesson or we rematch — or refund. No questions asked.', 'beyondinfinity' ); ?></p>
          <a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $guarantee_url ); ?>"><?php esc_html_e( 'View Guarantee', 'beyondinfinity' ); ?></a>
        </article>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'proof' ) ) : ?>
  <section class="ngi-section ngi-alt" id="learning-proof">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_proof['eyebrow'] ?? __( 'Before / After', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_proof['title'] ?? __( 'Show the transformation parents want to see.', 'beyondinfinity' ) ); ?></h2>
      </div>
      <div class="ngi-before-after ngi-reveal" aria-label="<?php esc_attr_e( 'Before and after progress comparison', 'beyondinfinity' ); ?>">
        <div class="ngi-ba-layer ngi-ba-before"><?php esc_html_e( 'Before: Confused, behind, anxious', 'beyondinfinity' ); ?></div>
        <div class="ngi-ba-layer ngi-ba-after" id="ngiBaAfter"><?php esc_html_e( 'After: Confident, supported, improving', 'beyondinfinity' ); ?></div>
        <input class="ngi-ba-range" id="ngiBaRange" type="range" min="0" max="100" value="50" aria-label="<?php esc_attr_e( 'Compare before and after learning progress', 'beyondinfinity' ); ?>" />
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'video' ) ) : ?>
  <?php
  $story_video  = function_exists( 'bi_tutoring_video_url' ) ? bi_tutoring_video_url( 'video-story' ) : '';
  $story_poster = function_exists( 'bi_tutoring_video_poster_url' ) ? bi_tutoring_video_poster_url( 'video-story' ) : ( function_exists( 'bi_get_theme_image_url' ) ? bi_get_theme_image_url( 'home_video' ) : '' );
  ?>
  <section class="ngi-section bi-cinematic-band<?php echo $story_video ? ' bi-cinematic-band--has-video' : ''; ?>" id="video-story" aria-labelledby="ngi-story-heading">
    <div class="bi-cinematic-band__media" aria-hidden="true">
      <?php if ( $story_poster ) : ?>
        <div class="bi-cinematic-band__poster" style="background-image:url(<?php echo esc_url( $story_poster ); ?>)"></div>
      <?php endif; ?>
      <?php if ( $story_video ) : ?>
        <video
          class="bi-cinematic-video"
          data-bi-cinematic
          muted
          loop
          playsinline
          preload="metadata"
          <?php echo $story_poster ? 'poster="' . esc_url( $story_poster ) . '"' : ''; ?>
        >
          <source src="<?php echo esc_url( $story_video ); ?>" type="video/mp4" />
        </video>
      <?php endif; ?>
      <div class="bi-cinematic-band__scrim"></div>
    </div>
    <div class="ngi-wrap bi-cinematic-band__inner">
      <div class="ngi-reveal">
        <p class="ngi-eyebrow" style="color:rgba(255,255,255,.75)"><?php esc_html_e( 'Our story', 'beyondinfinity' ); ?></p>
        <h2 id="ngi-story-heading" class="bi-cinematic-band__title" style="font-size:clamp(1.75rem,3vw,2.5rem);margin:0 0 0.75rem"><?php esc_html_e( 'See how NextGen works', 'beyondinfinity' ); ?></h2>
        <p><?php esc_html_e( 'Real online lessons, verified tutors, and families who finally feel in control of the learning journey.', 'beyondinfinity' ); ?></p>
        <div style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:0.75rem">
          <button class="ngi-btn ngi-btn-primary" id="ngiOpenVideo" type="button" aria-label="<?php esc_attr_e( 'Open NextGen Tutors story video', 'beyondinfinity' ); ?>">
            <?php echo bi_kinetic_icon( 'play' ); // phpcs:ignore ?>
            <span><?php esc_html_e( 'Watch trailer', 'beyondinfinity' ); ?></span>
          </button>
        </div>
      </div>
      <div>
        <article class="ngi-split-item ngi-reveal"><h3><?php esc_html_e( 'Parent confidence', 'beyondinfinity' ); ?></h3><p><?php esc_html_e( 'Pinned media stays visible while content explains the tutoring journey step by step.', 'beyondinfinity' ); ?></p></article>
        <article class="ngi-split-item ngi-reveal" style="margin-top:0.85rem"><h3><?php esc_html_e( 'Verified tutor matching', 'beyondinfinity' ); ?></h3><p><?php esc_html_e( 'Subject fit, grade support, province availability and learning format — all visible before you book.', 'beyondinfinity' ); ?></p></article>
        <article class="ngi-split-item ngi-reveal" style="margin-top:0.85rem"><h3><?php esc_html_e( 'CRM-ready follow-up', 'beyondinfinity' ); ?></h3><p><?php esc_html_e( 'Each CTA connects to Fluent Forms, FluentCRM, Amelia and NextGenCompanion workflows.', 'beyondinfinity' ); ?></p></article>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'image_hover' ) && function_exists( 'bi_kinetic_render_image_hover_section' ) ) : ?>
    <?php bi_kinetic_render_image_hover_section(); ?>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'pathways' ) ) : ?>
  <section class="ngi-section ngi-alt" id="cursor-reveal">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_pathways['eyebrow'] ?? __( 'Learning pathways', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_pathways['title'] ?? __( 'Interactive discovery for every role.', 'beyondinfinity' ) ); ?></h2>
      </div>
      <?php
        $pathways_3d = function_exists( 'bi_3d_enabled' ) && bi_3d_enabled();
        $pathways_class = $pathways_3d ? 'ngi-pathways-split ngi-reveal' : 'ngi-reveal';
      ?>
      <div class="<?php echo esc_attr( $pathways_class ); ?>">
        <?php
        if ( $pathways_3d ) {
            $stack_items = array_map(
                static function ( $j ) {
                    return [
                        'title' => $j['title'] ?? '',
                        'body'  => $j['copy'] ?? '',
                    ];
                },
                $journeys
            );
            bi_render_3d_card_stack(
                $stack_items,
                [
                    'aria_label' => __( 'Learning pathway cards — hover or tap to fan', 'beyondinfinity' ),
                    'class'      => 'bi-stack-3d--pathways',
                ]
            );
        }
        ?>
      <div class="ngi-cursor-list ngi-reveal">
        <div role="tablist" aria-label="<?php esc_attr_e( 'User journeys', 'beyondinfinity' ); ?>">
          <?php foreach ( $journeys as $i => $j ) : ?>
            <button class="ngi-cursor-item<?php echo 0 === $i ? ' is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
              data-title="<?php echo esc_attr( $j['title'] ); ?>"
              data-copy="<?php echo esc_attr( $j['copy'] ); ?>">
              <?php echo esc_html( $j['title'] ); ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="ngi-cursor-preview" id="ngiCursorPreview">
          <div>
            <h3 id="ngiCursorTitle"><?php echo esc_html( $journeys[0]['title'] ); ?></h3>
            <p id="ngiCursorCopy"><?php echo esc_html( $journeys[0]['copy'] ); ?></p>
          </div>
        </div>
      </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'tutors' ) ) : ?>
    <?php
    $carousel_tutors = bi_get_carousel_tutors( 8 );
    if ( empty( $carousel_tutors ) ) :
        if ( function_exists( 'ng_ui_component' ) ) :
            ?>
  <section class="ngi-section ngi-alt" id="tutors">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_tutors['eyebrow'] ?? __( 'Featured tutors', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_tutors['title'] ?? __( 'Vetted educators ready to book.', 'beyondinfinity' ) ); ?></h2>
      </div>
      <?php ng_ui_component( 'tutor-card', [ 'limit' => 6 ] ); ?>
    </div>
  </section>
            <?php
        else :
            bi_render_tutors_empty_state();
        endif;
    elseif ( function_exists( 'bi_3d_home_carousel_enabled' ) && bi_3d_home_carousel_enabled() ) :
      bi_render_kinetic_tutors_3d( [ 'limit' => count( $carousel_tutors ) ] );
    else :
    ?>
  <section class="ngi-section ngi-alt" id="tutors">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_tutors['eyebrow'] ?? __( 'Featured tutors', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_tutors['title'] ?? __( 'Vetted educators ready to book.', 'beyondinfinity' ) ); ?></h2>
        <p class="ngi-subtitle"><?php echo esc_html( $cms_tutors['subtitle'] ?? __( 'Verified tutors from our directory — CAPS, IEB and Cambridge support.', 'beyondinfinity' ) ); ?></p>
      </div>
      <div class="ngi-tutor-grid">
        <?php foreach ( $carousel_tutors as $tutor ) : ?>
          <article class="ngi-card ngi-tutor ngi-reveal">
            <div class="ngi-tutor-visual">
              <div class="ngi-avatar">
                <?php if ( ! empty( $tutor['imageUrl'] ) ) : ?>
                  <img src="<?php echo esc_url( $tutor['imageUrl'] ); ?>" alt="<?php echo esc_attr( $tutor['name'] ); ?>" loading="lazy" width="74" height="74" />
                <?php else : ?>
                  <?php echo esc_html( bi_kinetic_initials( $tutor['name'] ) ); ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="ngi-tutor-body">
              <h3><?php echo esc_html( $tutor['name'] ); ?></h3>
              <div class="ngi-tutor-rating" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'beyondinfinity' ), number_format( (float) ( $tutor['rating'] ?? 4.8 ), 1 ) ) ); ?>">
                <?php echo bi_kinetic_icon( 'star' ); // phpcs:ignore ?>
                <span><?php echo esc_html( number_format( (float) ( $tutor['rating'] ?? 4.8 ), 1 ) ); ?></span>
              </div>
              <p><?php echo esc_html( wp_strip_all_tags( $tutor['bio'] ?? '' ) ); ?></p>
              <?php if ( ! empty( $tutor['subjects'] ) ) : ?>
                <div class="ngi-tagline">
                  <?php foreach ( array_slice( (array) $tutor['subjects'], 0, 3 ) as $subject ) : ?>
                    <span class="ngi-tag"><?php echo esc_html( $subject ); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Book Session', 'beyondinfinity' ); ?></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'pricing' ) ) : ?>
  <section class="ngi-section" id="pricing">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_pricing['eyebrow'] ?? __( 'Transparent pricing', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_pricing['title'] ?? __( 'Flat Monthly Packages.', 'beyondinfinity' ) ); ?></h2>
        <?php if ( ! empty( $cms_pricing['subtitle'] ) ) : ?>
          <p class="ngi-subtitle"><?php echo esc_html( $cms_pricing['subtitle'] ); ?></p>
        <?php endif; ?>
      </div>
      <div class="ngi-pricing<?php echo ( function_exists( 'bi_3d_enabled' ) && bi_3d_enabled() ) ? ' ngi-pricing--3d' : ''; ?>">
        <?php
        if ( function_exists( 'bi_render_3d_card_stack' ) && bi_3d_enabled() ) {
            $stack_plans = array_map(
                static function ( $plan ) {
                    return [
                        'title'    => $plan['name'] ?? '',
                        'body'     => ( $plan['price'] ?? '' ) . ' / hr — ' . implode( ', ', array_slice( (array) ( $plan['items'] ?? [] ), 0, 2 ) ),
                        'featured' => ! empty( $plan['featured'] ),
                        'meta'     => __( 'Plan', 'beyondinfinity' ),
                    ];
                },
                $pricing_plans
            );
            bi_render_3d_card_stack(
                $stack_plans,
                [
                    'aria_label' => __( 'Pricing plans — hover to fan cards', 'beyondinfinity' ),
                    'class'      => 'bi-stack-3d--pricing',
                ]
            );
        }
        ?>
        <div class="ngi-pricing-grid">
        <?php foreach ( $pricing_plans as $plan ) : ?>
          <article class="ngi-card ngi-price ngi-reveal bi-tilt-3d<?php echo $plan['featured'] ? ' is-featured' : ''; ?>" data-bi-tilt data-bi-tilt-max="7">
            <div class="bi-tilt-3d__inner">
            <h3><?php echo esc_html( $plan['name'] ); ?></h3>
            <strong><?php echo esc_html( $plan['price'] ); ?></strong>
            <p><?php esc_html_e( 'per hour', 'beyondinfinity' ); ?></p>
            <ul>
              <?php foreach ( $plan['items'] as $item ) : ?>
                <li><?php echo esc_html( $item ); ?></li>
              <?php endforeach; ?>
            </ul>
            <a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $pricing_url ); ?>"><?php esc_html_e( 'View Plans', 'beyondinfinity' ); ?></a>
            </div>
          </article>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'reviews' ) ) : ?>
  <section class="ngi-section ngi-section--testimonials" id="reviews">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_reviews['eyebrow'] ?? __( 'Happy clients · real marks', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_reviews['title'] ?? __( 'The Joy of Achievement', 'beyondinfinity' ) ); ?></h2>
        <p class="ngi-subtitle"><?php echo esc_html( $cms_reviews['subtitle'] ?? bi_testimonials_source_label() ); ?></p>
        <?php if ( bi_testimonials_use_demo_data() ) : ?>
          <p class="ngi-demo-note" role="note"><?php esc_html_e( 'Demo stories — replace with verified reviews from your testimonial directory after launch.', 'beyondinfinity' ); ?></p>
        <?php endif; ?>
      </div>
      <div class="ngi-testimonials-marquee" data-bi-testimonial-marquee>
        <div class="ngi-testimonials-marquee__viewport">
          <div class="ngi-testimonials-marquee__track">
            <?php
            $testimonials = bi_get_featured_testimonials();
            // Duplicate once for a seamless infinite loop.
            $loop_items = array_merge( $testimonials, $testimonials );
            foreach ( $loop_items as $i => $t ) :
              $avatar = ! empty( $t['avatar'] ) ? (string) $t['avatar'] : '';
              ?>
              <article class="ngi-card ngi-testimonial"<?php echo $i >= count( $testimonials ) ? ' aria-hidden="true"' : ''; ?>>
                <?php bi_kinetic_stars( $t['stars'] ); ?>
                <blockquote class="ngi-testimonial__quote">"<?php echo esc_html( $t['quote'] ); ?>"</blockquote>
                <div class="ngi-testimonial__head">
                  <?php if ( $avatar ) : ?>
                    <img
                      class="ngi-testimonial__avatar ngi-testimonial__avatar--photo"
                      src="<?php echo esc_url( $avatar ); ?>"
                      alt=""
                      width="48"
                      height="48"
                      loading="lazy"
                      decoding="async"
                    />
                  <?php else : ?>
                    <div class="ngi-testimonial__avatar" aria-hidden="true"><?php echo esc_html( bi_kinetic_initials( $t['author'] ) ); ?></div>
                  <?php endif; ?>
                  <div>
                    <div class="ngi-testimonial__author"><?php echo esc_html( $t['author'] ); ?></div>
                    <div class="ngi-testimonial__role"><?php echo esc_html( $t['role'] ); ?></div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div style="text-align:center;margin-top:32px" class="ngi-reveal">
        <a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Find a Tutor Like These Families', 'beyondinfinity' ); ?></a>
      </div>
      <?php if ( function_exists( 'ng_ui_component' ) ) : ?>
        <div class="ngi-reveal" style="margin-top:28px">
          <?php ng_ui_component( 'review-card', [ 'limit' => 3 ] ); ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'faq' ) ) : ?>
  <section class="ngi-section ngi-alt" id="faq">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow"><?php echo esc_html( $cms_faq_meta['eyebrow'] ?? __( 'Questions', 'beyondinfinity' ) ); ?></div>
        <h2 class="ngi-heading" data-bi-slide-title><?php echo esc_html( $cms_faq_meta['title'] ?? __( 'Common Questions', 'beyondinfinity' ) ); ?></h2>
      </div>
      <div class="ngi-faq ngi-reveal">
        <?php foreach ( $faqs as $faq ) : ?>
          <div class="ngi-faq-item">
            <button class="ngi-faq-q" type="button" aria-expanded="false">
              <?php echo esc_html( $faq['q'] ); ?>
              <span class="ngi-faq-toggle" aria-hidden="true"><?php echo bi_kinetic_icon( 'plus' ); // phpcs:ignore ?></span>
            </button>
            <div class="ngi-faq-a"><p><?php echo esc_html( $faq['a'] ); ?></p></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( bi_home_section_enabled( 'cta' ) ) : ?>
  <section class="ngi-section">
    <div class="ngi-wrap">
      <div class="ngi-cta ngi-reveal">
        <div>
          <h2><?php echo esc_html( $cms_cta['title'] ?? __( 'Ready to turn tutoring into a premium digital experience?', 'beyondinfinity' ) ); ?></h2>
          <p><?php echo esc_html( $cms_cta['subtitle'] ?? __( 'Launch a homepage that guides parents into the correct registration and booking journey.', 'beyondinfinity' ) ); ?></p>
          <div class="ngi-actions">
            <button class="ngi-btn ngi-btn-primary" data-ngi-open type="button"><?php echo esc_html( $cms_cta['button_text'] ?? __( 'Book Free Assessment', 'beyondinfinity' ) ); ?></button>
            <a class="ngi-btn ngi-btn-secondary" href="#subjects"><?php esc_html_e( 'Explore Subjects', 'beyondinfinity' ); ?></a>
          </div>
        </div>
        <div class="ngi-cta-panel">
          <h3><?php echo esc_html( $cms_cta['become_title'] ?? __( 'Want to Become a Tutor?', 'beyondinfinity' ) ); ?></h3>
          <p><?php echo esc_html( $cms_cta['guarantee_line'] ?? __( 'Love the lesson — or your first hour is on us.', 'beyondinfinity' ) ); ?></p>
          <a class="ngi-btn ngi-btn-secondary" href="<?php echo esc_url( $become_url ); ?>"><?php echo esc_html( $cms_cta['become_button'] ?? __( 'Apply to Teach', 'beyondinfinity' ) ); ?></a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <div class="ngi-modal" id="ngiVideoModal" role="dialog" aria-modal="true" aria-labelledby="ngiVideoTitle">
    <div class="ngi-modal-card ngi-video-modal">
      <button class="ngi-close" data-ngi-video-close type="button" aria-label="<?php esc_attr_e( 'Close video modal', 'beyondinfinity' ); ?>"><?php echo bi_kinetic_icon( 'close' ); // phpcs:ignore ?></button>
      <h2 id="ngiVideoTitle"><?php esc_html_e( 'NextGen Tutors Story', 'beyondinfinity' ); ?></h2>
      <iframe id="ngiVideoFrame" title="<?php esc_attr_e( 'NextGen Tutors video', 'beyondinfinity' ); ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>

  <div class="ngi-modal" id="ngiBookingModal" role="dialog" aria-modal="true" aria-labelledby="ngiModalTitle">
    <div class="ngi-modal-card">
      <button class="ngi-close" data-ngi-close type="button" aria-label="<?php esc_attr_e( 'Close booking modal', 'beyondinfinity' ); ?>"><?php echo bi_kinetic_icon( 'close' ); // phpcs:ignore ?></button>
      <h2 id="ngiModalTitle"><?php esc_html_e( 'Book Free Assessment', 'beyondinfinity' ); ?></h2>
      <p style="color:var(--ngi-muted)"><?php esc_html_e( 'Tell us about your learner and we will recommend vetted tutors within 48 hours.', 'beyondinfinity' ); ?></p>
      <?php
      if ( shortcode_exists( 'ngc_find_tutor_form' ) ) {
          echo do_shortcode( '[ngc_find_tutor_form]' );
      } else {
          ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
          <input type="hidden" name="action" value="ngt_find_tutor" />
          <?php wp_nonce_field( 'ngt_find_tutor', 'ngt_nonce' ); ?>
          <label for="ngi_parent_name"><?php esc_html_e( 'Parent name', 'beyondinfinity' ); ?></label>
          <input id="ngi_parent_name" name="parent_name" type="text" required autocomplete="name" />
          <label for="ngi_parent_email"><?php esc_html_e( 'Email address', 'beyondinfinity' ); ?></label>
          <input id="ngi_parent_email" name="email" type="email" required autocomplete="email" />
          <label for="ngi_subject"><?php esc_html_e( 'Subject', 'beyondinfinity' ); ?></label>
          <select id="ngi_subject" name="subject">
            <option><?php esc_html_e( 'Mathematics', 'beyondinfinity' ); ?></option>
            <option><?php esc_html_e( 'Physical Science', 'beyondinfinity' ); ?></option>
            <option><?php esc_html_e( 'English', 'beyondinfinity' ); ?></option>
            <option><?php esc_html_e( 'Programming', 'beyondinfinity' ); ?></option>
          </select>
          <button class="ngi-btn ngi-btn-primary" style="width:100%;margin-top:10px" type="submit"><?php esc_html_e( 'Submit Assessment Request', 'beyondinfinity' ); ?></button>
        </form>
          <?php
      }
      ?>
    </div>
  </div>
</div>
