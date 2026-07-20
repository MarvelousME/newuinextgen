<?php
/**
 * Template Name: Safety Guide
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');
?>

<!-- HERO -->
<section class="sg-hero section" aria-labelledby="sg-hero-title">
  <div class="container sg-hero__inner">
    <span class="badge badge--lime"><i data-lucide="shield" aria-hidden="true"></i> Safety First</span>
    <h1 id="sg-hero-title">Your Safety Is Our Priority</h1>
    <p>NextGen Tutors is committed to maintaining a safe, respectful, and secure environment for students, parents, and tutors across South Africa.</p>
    <div class="sg-hero__badges" role="list">
      <div class="sg-badge" role="listitem"><i data-lucide="id-card" aria-hidden="true"></i><span>ID Verified</span></div>
      <div class="sg-badge" role="listitem"><i data-lucide="search" aria-hidden="true"></i><span>Background Checked</span></div>
      <div class="sg-badge" role="listitem"><i data-lucide="lock" aria-hidden="true"></i><span>POPIA Compliant</span></div>
      <div class="sg-badge" role="listitem"><i data-lucide="credit-card" aria-hidden="true"></i><span>Secure Payments</span></div>
    </div>
  </div>
</section>

<!-- TUTOR VETTING -->
<section class="sg-section section" aria-labelledby="sg-vetting-title">
  <div class="container sg-section__inner">
    <div class="sg-section__copy">
      <h2 id="sg-vetting-title">How We Vet Every Tutor</h2>
      <p>No tutor goes live on NextGen Tutors without passing our multi-step verification process. Here's exactly what we check:</p>
      <ol class="sg-steps" role="list">
        <li class="sg-step">
          <span class="sg-step__num" aria-hidden="true">1</span>
          <div>
            <h3>Identity Verification</h3>
            <p>South African ID or passport verified against the Department of Home Affairs database. Non-SA nationals provide passport and valid visa.</p>
          </div>
        </li>
        <li class="sg-step">
          <span class="sg-step__num" aria-hidden="true">2</span>
          <div>
            <h3>Qualification Check</h3>
            <p>All claimed qualifications are manually reviewed. University degrees and teaching diplomas are verified with issuing institutions where possible.</p>
          </div>
        </li>
        <li class="sg-step">
          <span class="sg-step__num" aria-hidden="true">3</span>
          <div>
            <h3>Criminal Background Check</h3>
            <p>All tutors working with minors undergo a criminal record check. Tutors with relevant convictions are not approved.</p>
          </div>
        </li>
        <li class="sg-step">
          <span class="sg-step__num" aria-hidden="true">4</span>
          <div>
            <h3>Profile Review</h3>
            <p>Profile photo, bio, and subject claims are reviewed by our team before the profile goes live.</p>
          </div>
        </li>
        <li class="sg-step">
          <span class="sg-step__num" aria-hidden="true">5</span>
          <div>
            <h3>Ongoing Monitoring</h3>
            <p>All sessions are subject to student ratings. Tutors with sustained low ratings or complaints are suspended pending review.</p>
          </div>
        </li>
      </ol>
    </div>
    <div class="sg-section__visual" aria-hidden="true">
      <div class="sg-shield-graphic">
        <i data-lucide="shield-check"></i>
        <span>Verified</span>
      </div>
    </div>
  </div>
</section>

<!-- SAFE SESSIONS -->
<section class="sg-sessions section section--alt" aria-labelledby="sg-sessions-title">
  <div class="container">
    <h2 id="sg-sessions-title" class="section__title">Safe Session Guidelines</h2>
    <p class="section__sub">Tips for students, parents, and tutors to keep every session safe.</p>
    <div class="sg-guidelines-grid" role="list">
      <?php
      $groups = [
        [
          'For Students',
          'student',
          [
            'Never share your home address in chat — use the platform booking system for in-person session address.',
            'Keep payment on the platform. Never pay a tutor directly.',
            'If a tutor asks to move communication off the platform, report it immediately.',
            'You can rate and review every session. Use it — your feedback protects other students.',
          ],
        ],
        [
          'For Parents',
          'parent',
          [
            'Verify your child\'s tutor profile before their first session, including the Verified badge.',
            'For in-home sessions, be present or in the building during the session.',
            'Review session logs in your Parent Dashboard after each session.',
            'Report any concerning behaviour through our platform immediately.',
          ],
        ],
        [
          'For Tutors',
          'tutor',
          [
            'Only accept session payments through the NextGen Tutors platform.',
            'Do not share personal contact details (phone, personal email) with students before vetting.',
            'For in-person sessions: meet in public or shared spaces where possible for new students.',
            'Report any student behaviour you find concerning through your Tutor Dashboard.',
          ],
        ],
      ];
      foreach ($groups as [$title, $icon, $tips]) : ?>
        <div class="sg-guideline-card" role="listitem">
          <h3><i data-lucide="<?php echo esc_attr($icon === 'student' ? 'graduation-cap' : ($icon === 'parent' ? 'users' : 'briefcase')); ?>" aria-hidden="true"></i> <?php echo esc_html($title); ?></h3>
          <ul role="list">
            <?php foreach ($tips as $tip) : ?>
              <li><?php echo esc_html($tip); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- REPORTING -->
<section class="sg-report section" aria-labelledby="sg-report-title">
  <div class="container sg-report__inner">
    <h2 id="sg-report-title">Report a Safety Concern</h2>
    <p>If you experience or witness behaviour that violates our safety standards, report it immediately. All reports are confidential and investigated within 24 hours.</p>
    <div class="sg-report__actions">
      <a href="<?php echo esc_url(ngt_get_page_url('contact')); ?>" class="btn btn--lime">Report an Incident</a>
      <?php $wa = get_theme_mod('ngt_contact_whatsapp', ''); ?>
      <?php if ($wa) : ?>
        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $wa)); ?>" class="btn btn--outline" target="_blank" rel="noopener noreferrer">
          <i data-lucide="message-circle" aria-hidden="true"></i> WhatsApp Us
        </a>
      <?php endif; ?>
    </div>
    <p class="sg-report__note"><strong>Emergency:</strong> If you or someone else is in immediate danger, call <a href="tel:10111">10111</a> (SAPS) or <a href="tel:112">112</a> from a mobile.</p>
  </div>
</section>

<!-- POPIA -->
<section class="sg-popia section section--alt" aria-labelledby="sg-popia-title">
  <div class="container sg-popia__inner">
    <h2 id="sg-popia-title">Data Privacy &amp; POPIA</h2>
    <p>NextGen Tutors is fully compliant with the Protection of Personal Information Act (POPIA). Here's how we protect your data:</p>
    <ul class="sg-popia__list" role="list">
      <li><i data-lucide="check-circle" aria-hidden="true"></i> We collect only the data necessary to provide our service.</li>
      <li><i data-lucide="check-circle" aria-hidden="true"></i> Personal data is never sold to third parties.</li>
      <li><i data-lucide="check-circle" aria-hidden="true"></i> All data is encrypted in transit (TLS 1.3) and at rest.</li>
      <li><i data-lucide="check-circle" aria-hidden="true"></i> Minor users (under 18) require guardian consent for account creation.</li>
      <li><i data-lucide="check-circle" aria-hidden="true"></i> You can request deletion of your data at any time.</li>
    </ul>
    <a href="<?php echo esc_url(ngt_get_page_url('privacy')); ?>" class="btn btn--outline btn--sm">Read Our Privacy Policy</a>
  </div>
</section>

<script>
if (window.lucide) lucide.createIcons();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
