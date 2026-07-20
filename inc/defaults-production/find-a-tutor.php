<?php
/** Default — Find a Tutor (pages-to-review + intake form) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Find Your Perfect Tutor', 'beyondinfinity' ),
    __( 'Filter by subject, format and budget. Every tutor is vetted, ID-verified and background-checked.', 'beyondinfinity' )
);
$marketing_kpis = bi_real_marketing_kpis();
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-stat-grid" style="margin-bottom:32px">
      <?php foreach (
          [
              [ $marketing_kpis['average_rating'], __( 'Average rating', 'beyondinfinity' ) ],
              [ $marketing_kpis['satisfaction'], __( 'Satisfaction', 'beyondinfinity' ) ],
              [ $marketing_kpis['first_booking_window'], __( 'Typical first booking', 'beyondinfinity' ) ],
          ] as $s
      ) : ?>
        <div class="bi-stat-card ngt-animate">
          <div class="bi-stat-card__num"><?php echo esc_html( $s[0] ); ?></div>
          <div class="bi-stat-card__label"><?php echo esc_html( $s[1] ); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ( function_exists( 'ng_ui_component' ) && class_exists( 'NGC_UI_Library' ) ) : ?>
<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Browse Vetted Tutors', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Live directory powered by the tutor marketplace — ratings, subjects and availability from your database.', 'beyondinfinity' ); ?></p>
    </div>
    <?php if ( shortcode_exists( 'ngc_tutor_marketplace' ) ) : ?>
      <?php echo do_shortcode( '[ngc_tutor_marketplace per_page="12"]' ); ?>
    <?php else : ?>
      <?php ng_ui_component( 'tutor-card', [ 'limit' => 12 ] ); ?>
    <?php endif; ?>
  </div>
</section>
<?php else : ?>
<?php if ( shortcode_exists( 'ngc_tutor_marketplace' ) ) : ?>
<section class="ngt-section">
  <div class="ngt-container">
    <?php echo do_shortcode( '[ngc_tutor_marketplace per_page="12"]' ); ?>
  </div>
</section>
<?php else : ?>
<?php bi_render_tutor_directory( 12 ); ?>
<?php endif; ?>
<?php endif; ?>

<section class="ngt-section">  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Request a Personal Match', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Prefer guidance? Complete the intake form and our team will recommend suitable tutors.', 'beyondinfinity' ); ?></p>
    </div>
    <?php bi_shortcode_block( '[ngc_find_tutor_form]', __( 'Request Academic Support', 'beyondinfinity' ) ); ?>
    <?php bi_safety_notice( 'parent' ); ?>
    <div class="ngt-card ngt-animate bi-reassurance" style="margin-top:24px">
      <p style="margin:0"><strong><?php esc_html_e( 'Reassurance:', 'beyondinfinity' ); ?></strong> <?php esc_html_e( 'Parents pay NextGen Tutors directly. The platform manages tutor payments — no awkward cash handling.', 'beyondinfinity' ); ?></p>
    </div>
  </div>
</section>

<?php
bi_steps(
    [
        [ 'title' => __( 'Choose or request a tutor', 'beyondinfinity' ), 'text' => __( 'Browse the directory or submit the intake form.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Book a session', 'beyondinfinity' ), 'text' => __( 'Pick a slot and pay securely through the platform.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Start learning', 'beyondinfinity' ), 'text' => __( 'Online whiteboard or in-person — your choice.', 'beyondinfinity' ) ],
    ],
    __( 'How Booking Works', 'beyondinfinity' )
);
?>
