<?php
/** Default — About (pages-to-review/about.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Built For South African Learners', 'beyondinfinity' ),
    __( 'We believe every child deserves a brilliant, trustworthy tutor — no matter their suburb, school or budget.', 'beyondinfinity' )
);
?>

<?php
$real_stats = bi_real_stat_cards();
$stat_rows  = [
    [ (string) ( $real_stats[1]['count'] . $real_stats[1]['suffix'] ), __( 'Vetted tutors', 'beyondinfinity' ), '🌍' ],
    [ (string) ( $real_stats[0]['count'] . $real_stats[0]['suffix'] ), __( 'Learners linked', 'beyondinfinity' ), '🎓' ],
    [ (string) $real_stats[3]['count'], __( 'Provinces covered', 'beyondinfinity' ), '📍' ],
    [ (string) ( $real_stats[2]['count'] > 0 ? number_format( (float) $real_stats[2]['count'], 1 ) . '/5' : '—' ), __( 'Average rating', 'beyondinfinity' ), '⭐' ],
];
?>

<section class="ngt-section">
  <div class="ngt-container framer-frame">
    <div class="bi-become-grid framer-grid">
      <div class="ngt-card ngt-animate" style="padding:0;overflow:hidden">
        <?php bi_theme_image( 'about_feature', [ 'mask_reveal' => true, 'motion' => 'zoom-blur-in' ] ); ?>
      </div>
      <div class="ngt-animate" data-bi-motion="slide-left">
        <p class="bi-eyebrow"><?php esc_html_e( 'Why we exist', 'beyondinfinity' ); ?></p>
        <h2><?php esc_html_e( 'Safer Tutoring for Every Family', 'beyondinfinity' ); ?></h2>
        <p><?php esc_html_e( 'Finding a qualified tutor used to mean guesswork and stress. Parents had no way to verify who was entering their home or joining an online lesson.', 'beyondinfinity' ); ?></p>
        <p><?php esc_html_e( 'NextGen Tutors verifies every educator, prices honestly in Rand, and connects learners across all nine provinces with tutors who move marks and restore confidence.', 'beyondinfinity' ); ?></p>
      </div>
    </div>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="bi-stat-grid">
      <?php foreach (
          $stat_rows as $stat
      ) : ?>
        <div class="bi-stat-card ngt-animate">
          <div class="bi-stat-card__icon"><?php echo esc_html( $stat[2] ); ?></div>
          <div class="bi-stat-card__num"><?php echo esc_html( $stat[0] ); ?></div>
          <div class="bi-stat-card__label"><?php echo esc_html( $stat[1] ); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate bi-center"><h2><?php esc_html_e( 'Our Core Values', 'beyondinfinity' ); ?></h2></div>
    <div class="bi-grid-3 framer-grid" data-bi-stagger="slide-up">
      <?php foreach (
          [
              [ '🛡️', __( 'Safety First', 'beyondinfinity' ), __( 'ID verification, vetting and clearance checks before the first lesson.', 'beyondinfinity' ) ],
              [ '📈', __( 'Results That Matter', 'beyondinfinity' ), __( 'Improved marks, restored confidence and unlocked pathways.', 'beyondinfinity' ) ],
              [ '🇿🇦', __( 'Proudly Local', 'beyondinfinity' ), __( 'CAPS, IEB and Cambridge — priced in ZAR, supported locally.', 'beyondinfinity' ) ],
          ] as $i => $v
      ) : ?>
        <div class="ngt-card ngt-animate ngt-animate--delay-<?php echo $i + 1; ?>" style="padding:28px">
          <div style="font-size:2rem;margin-bottom:12px"><?php echo esc_html( $v[0] ); ?></div>
          <h3><?php echo esc_html( $v[1] ); ?></h3>
          <p style="margin:0"><?php echo esc_html( $v[2] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
bi_parallax_cta(
    __( 'Every struggling learner deserves the right approach.', 'beyondinfinity' ),
    __( 'Find a Tutor', 'beyondinfinity' ),
    home_url( '/find-a-tutor' )
);
