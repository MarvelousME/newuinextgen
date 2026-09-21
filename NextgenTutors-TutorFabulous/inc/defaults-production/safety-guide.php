<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
  __( 'Safe Tutoring Guidelines', 'beyondinfinity' ),
  __( 'Industry-leading safety measures for every NextGen tutoring session — so families do not have to worry.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <div class="ngt-card ngt-animate" style="padding:32px;margin-bottom:24px">
      <p><?php esc_html_e( 'We understand that inviting someone into your child\'s learning journey requires trust. NextGen Tutors has built safety systems to give South African families peace of mind.', 'beyondinfinity' ); ?></p>
      <p style="margin-bottom:0"><?php esc_html_e( 'From ID-verified tutors to monitored sessions and full parent oversight, we have thought of everything so you do not have to.', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    bi_info_card( __( 'Platform Security', 'beyondinfinity' ), [
      __( 'Encrypted sessions — end-to-end encrypted video and audio', 'beyondinfinity' ),
      __( 'Secure payments — PCI-compliant processing via trusted SA providers', 'beyondinfinity' ),
      __( 'POPIA compliant — full protection of personal information', 'beyondinfinity' ),
      __( 'Optional recording — session recording for parent review, with consent', 'beyondinfinity' ),
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Parent Oversight', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Parents should always be able to see, observe and control how tutoring happens.', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    bi_value_cards( [
      [ 'icon' => 'chart', 'title' => __( 'Dashboard Access', 'beyondinfinity' ), 'text' => __( 'View session history, tutor feedback, progress reports, attendance and recordings.', 'beyondinfinity' ) ],
      [ 'icon' => 'users', 'title' => __( 'Real-Time Features', 'beyondinfinity' ), 'text' => __( 'Join sessions as a silent observer, get start/end notifications and direct tutor messaging.', 'beyondinfinity' ) ],
      [ 'icon' => 'layout', 'title' => __( 'Control Options', 'beyondinfinity' ), 'text' => __( 'Approve or decline tutors, set scheduling parameters and pause or cancel anytime.', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Session Safety', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_value_cards( [
      [ 'icon' => 'shield', 'title' => __( 'AI Moderation', 'beyondinfinity' ), 'text' => __( 'Automated systems flag concerning interactions in real time for human review.', 'beyondinfinity' ) ],
      [ 'icon' => 'clipboard', 'title' => __( 'Quality Audits', 'beyondinfinity' ), 'text' => __( 'Our team conducts random reviews of session recordings to uphold standards.', 'beyondinfinity' ) ],
      [ 'icon' => 'phone', 'title' => __( '24/7 Response Team', 'beyondinfinity' ), 'text' => __( 'A dedicated safety team responds to every report within two hours, day or night.', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Verification Badges', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_badge_table( [
      [ 'badge' => __( 'ID Verified', 'beyondinfinity' ), 'desc' => __( 'Full identity confirmed with South African authorities.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Background Cleared', 'beyondinfinity' ), 'desc' => __( 'Clean criminal-record check via an accredited agency.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Reference Checked', 'beyondinfinity' ), 'desc' => __( 'Professional and personal references verified.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Training Complete', 'beyondinfinity' ), 'desc' => __( 'NextGen safety and ethics training passed.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Curriculum Trained', 'beyondinfinity' ), 'desc' => __( 'South African educational-system specialist.', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Common Safety Questions', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_faq_list( [
      [
        'q' => __( 'How do you verify tutor identities?', 'beyondinfinity' ),
        'a' => __( 'We require South African ID documents, verified through official channels. Every tutor passes a comprehensive criminal background check via accredited SA screening agencies.', 'beyondinfinity' ),
      ],
      [
        'q' => __( 'Can I monitor my child\'s sessions?', 'beyondinfinity' ),
        'a' => __( 'Yes. Parents can join any session as a silent observer, view session recordings, and receive detailed session notes after each meeting.', 'beyondinfinity' ),
      ],
      [
        'q' => __( 'What happens if I have a safety concern?', 'beyondinfinity' ),
        'a' => __( 'Our dedicated safety team responds to all reports within two hours. Use the in-app report button, email safety@nextgentutors.co.za, or call our 24/7 hotline.', 'beyondinfinity' ),
      ],
      [
        'q' => __( 'Are sessions recorded?', 'beyondinfinity' ),
        'a' => __( 'Session recording is optional and disabled by default. Parents can enable it in their dashboard. All recordings are encrypted and stored securely.', 'beyondinfinity' ),
      ],
      [
        'q' => __( 'How do you protect my child\'s data?', 'beyondinfinity' ),
        'a' => __( 'We are fully POPIA compliant. Your data is encrypted, access-controlled and never shared with third parties without consent.', 'beyondinfinity' ),
      ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container bi-narrow">
    <div class="ngt-card ngt-animate" style="padding:32px;background:var(--ngt-primary);color:#fff">
      <h2 style="color:#fff;margin-bottom:16px"><?php esc_html_e( 'Emergency Safety Contacts', 'beyondinfinity' ); ?></h2>
      <p style="opacity:.85;margin-bottom:20px"><?php esc_html_e( 'If you ever have a concern, reach our safety team instantly — we respond within two hours, guaranteed.', 'beyondinfinity' ); ?></p>
      <ul class="bi-bullets bi-bullets--light">
        <?php foreach (
            [
                [ 'icon' => 'phone', 'label' => __( 'Safety Hotline: 0800 639 8436', 'beyondinfinity' ), 'href' => 'tel:08006398436' ],
                [ 'icon' => 'mail', 'label' => __( 'Email: safety@nextgentutors.co.za', 'beyondinfinity' ), 'href' => 'mailto:safety@nextgentutors.co.za' ],
                [ 'icon' => 'message', 'label' => __( 'WhatsApp support', 'beyondinfinity' ), 'href' => bi_whatsapp_url( __( 'Safety concern — please assist.', 'beyondinfinity' ) ) ],
            ] as $row
        ) : ?>
          <li>
            <span class="bi-bullets__mark bi-bullets__mark--light" aria-hidden="true"><?php echo bi_ui_icon( $row['icon'], 18 ); // phpcs:ignore ?></span>
            <span><a href="<?php echo esc_url( $row['href'] ); ?>" style="color:inherit"><?php echo esc_html( $row['label'] ); ?></a></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <p class="bi-center" style="margin-top:24px">
      <a href="<?php echo esc_url( home_url( '/child-safety' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Read Child Safety Policy', 'beyondinfinity' ); ?></a>
    </p>
  </div>
</section>
