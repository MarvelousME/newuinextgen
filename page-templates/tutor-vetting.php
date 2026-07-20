<?php
/**
 * Template Name: Tutor Vetting
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');
?>

<!-- HERO -->
<section class="vetting-hero section" aria-labelledby="vetting-hero-title">
  <div class="container vetting-hero__inner">
    <span class="badge badge--lime">Trust &amp; Safety</span>
    <h1 id="vetting-hero-title">Every Tutor Is Vetted.<br>No Exceptions.</h1>
    <p>We believe trust is earned, not assumed. That's why every tutor on NextGen Tutors goes through a rigorous multi-step verification process before teaching a single session.</p>
  </div>
</section>

<!-- VETTING PIPELINE -->
<section class="vetting-pipeline section" aria-labelledby="vetting-pipeline-title">
  <div class="container">
    <h2 id="vetting-pipeline-title" class="section__title">The Vetting Process</h2>
    <p class="section__sub">5 mandatory steps. Average completion: 24–48 business hours.</p>
    <ol class="vetting-steps" role="list">
      <?php
      $steps = [
        [
          'Application Submission',
          'submitted',
          'Tutor completes the online application: subjects, qualifications, teaching experience, availability, and session formats.',
          ['Subjects taught', 'Grade levels', 'Teaching format (online/in-person)', 'Years of experience'],
        ],
        [
          'Identity Verification',
          'id_verified',
          'We verify the tutor\'s South African ID or passport. Non-SA nationals must provide a valid passport and work/study visa.',
          ['SA ID / Passport copy', 'Selfie for liveness check', 'Department of Home Affairs verification'],
        ],
        [
          'Qualification Review',
          'quals_verified',
          'All claimed qualifications are manually reviewed. Degrees, diplomas, and professional certifications are checked against issuing institutions.',
          ['Matric certificate (minimum)', 'Tertiary qualification (where applicable)', 'Teaching certifications', 'Subject-specific credentials'],
        ],
        [
          'Criminal Background Check',
          'bgcheck_done',
          'All tutors working with minors undergo a criminal record clearance check. Any relevant conviction results in automatic disqualification.',
          ['SAPS criminal record check', 'Sex offender register check', 'Results reviewed by our safety team'],
        ],
        [
          'Profile Approval',
          'approved',
          'Our team reviews the tutor\'s profile photo, bio, and subject descriptions. Profiles that meet our quality standards are approved and go live.',
          ['Professional profile photo', 'Accurate subject listings', 'Honest bio and experience claims', 'Final team sign-off'],
        ],
      ];
      foreach ($steps as $i => $step) : ?>
        <li class="vetting-step" data-status="<?php echo esc_attr($step[1]); ?>">
          <div class="vetting-step__num" aria-hidden="true"><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></div>
          <div class="vetting-step__body">
            <h3><?php echo esc_html($step[0]); ?></h3>
            <p><?php echo esc_html($step[2]); ?></p>
            <ul class="vetting-step__checks" role="list">
              <?php foreach ($step[3] as $check) : ?>
                <li><i data-lucide="check" aria-hidden="true"></i> <?php echo esc_html($check); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ONGOING STANDARDS -->
<section class="vetting-ongoing section section--alt" aria-labelledby="vetting-ongoing-title">
  <div class="container">
    <h2 id="vetting-ongoing-title" class="section__title">Ongoing Quality Standards</h2>
    <p class="section__sub">Approval is just the beginning. We monitor every active tutor continuously.</p>
    <ul class="vetting-standards-grid" role="list">
      <?php
      $standards = [
        ['Star Rating System', 'Every session is rated by the student. Tutors with sustained ratings below 3.5/5 are flagged for review.', 'star'],
        ['Complaint Resolution', 'All student complaints are investigated within 24 hours. Tutors with serious or repeated complaints are suspended pending review.', 'alert-triangle'],
        ['Annual Re-vetting', 'Tutors working with minors undergo annual background check renewal. Qualifications are re-confirmed every two years.', 'refresh-cw'],
        ['Conduct Policy', 'All tutors must adhere to our Code of Conduct. Breaches — including unsolicited contact, off-platform payments, or misconduct — result in immediate suspension.', 'file-text'],
      ];
      foreach ($standards as $s) : ?>
        <li class="vetting-standard" role="listitem">
          <span class="vetting-standard__icon" aria-hidden="true"><i data-lucide="<?php echo esc_attr($s[2]); ?>"></i></span>
          <h3><?php echo esc_html($s[0]); ?></h3>
          <p><?php echo esc_html($s[1]); ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- VERIFIED BADGE -->
<section class="vetting-badge section" aria-labelledby="vetting-badge-title">
  <div class="container vetting-badge__inner">
    <div class="vetting-badge__graphic" aria-hidden="true">
      <div class="verified-seal">
        <i data-lucide="shield-check"></i>
        <span>NextGen<br>Verified</span>
      </div>
    </div>
    <div class="vetting-badge__copy">
      <h2 id="vetting-badge-title">The Verified Badge</h2>
      <p>Every approved tutor on NextGen Tutors displays a <strong>Verified</strong> badge on their profile. This badge means:</p>
      <ul role="list">
        <li><i data-lucide="check-circle" aria-hidden="true"></i> Identity confirmed</li>
        <li><i data-lucide="check-circle" aria-hidden="true"></i> Qualifications verified</li>
        <li><i data-lucide="check-circle" aria-hidden="true"></i> Criminal clearance passed</li>
        <li><i data-lucide="check-circle" aria-hidden="true"></i> Profile approved by our team</li>
      </ul>
      <p class="vetting-badge__note">Unverified profiles cannot accept bookings or receive payments on the platform.</p>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="vetting-cta section section--lime" aria-labelledby="vetting-cta-title">
  <div class="container vetting-cta__inner">
    <h2 id="vetting-cta-title">Ready to Find a Verified Tutor?</h2>
    <p>Every tutor on our platform has passed our full vetting process.</p>
    <a href="<?php echo esc_url(ngt_get_page_url('find_tutor')); ?>" class="btn btn--navy btn--lg">Browse Verified Tutors</a>
  </div>
</section>

<script>
if (window.lucide) lucide.createIcons();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
