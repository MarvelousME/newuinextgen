<?php
/**
 * Template Name: Guarantee
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');
?>

<section class="guar-hero section" aria-labelledby="guar-hero-title">
  <div class="container guar-hero__inner">
    <div class="guar-hero__copy">
      <span class="badge badge--lime">Our Promise</span>
      <h1 id="guar-hero-title" data-bi-slide-title>The NextGen Tutors<br>No-Surprise Guarantee</h1>
      <p>We guarantee the quality of every tutoring session booked through our platform. If you're not satisfied — for any reason — we will make it right.</p>
      <a href="<?php echo esc_url(ngt_get_page_url('find_tutor')); ?>" class="btn btn--lime btn--lg">Find a Tutor</a>
    </div>
  </div>
</section>

<!-- WHAT WE GUARANTEE -->
<section class="guar-coverage section" aria-labelledby="guar-cov-title">
  <div class="container">
    <h2 id="guar-cov-title" class="section__title" data-bi-slide-title>What We Cover</h2>
    <p class="section__sub">Our guarantee applies to every session booked and paid through the NextGen Tutors platform.</p>
    <ul class="guar-cards" role="list">
      <?php
      $covers = [
        [
          'Tutor Doesn\'t Show',
          'If your booked tutor doesn\'t arrive (online or in-person) within 15 minutes of the scheduled start time and hasn\'t contacted you, you get a full refund automatically.',
          'user-x',
          'Full Refund',
        ],
        [
          'Session Quality Issues',
          'If the session doesn\'t meet our quality standard — tutor is unprepared, session is significantly cut short, or conduct is unprofessional — you can request a refund or free replacement within 24 hours.',
          'thumbs-down',
          'Refund or Replacement',
        ],
        [
          'Technical Failure (Online)',
          'If an online session is interrupted by a platform-side technical issue for more than 20% of the session duration, you\'re entitled to a proportional credit.',
          'wifi-off',
          'Pro-rata Credit',
        ],
        [
          'Wrong Tutor Match',
          'If the tutor\'s actual expertise doesn\'t match their profile for the subject you booked, we\'ll find you a better-matched tutor at no additional cost.',
          'search-x',
          'Free Replacement',
        ],
      ];
      foreach ($covers as $c) : ?>
        <li class="guar-card" role="listitem">
          <span class="guar-card__icon" aria-hidden="true"><i data-lucide="<?php echo esc_attr($c[2]); ?>"></i></span>
          <div class="guar-card__body">
            <h3><?php echo esc_html($c[0]); ?></h3>
            <p><?php echo esc_html($c[1]); ?></p>
          </div>
          <span class="guar-card__outcome"><?php echo esc_html($c[3]); ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- HOW TO CLAIM -->
<section class="guar-claim section section--alt" aria-labelledby="guar-claim-title">
  <div class="container guar-claim__inner">
    <h2 id="guar-claim-title" class="section__title">How to Make a Claim</h2>
    <ol class="guar-claim-steps" role="list">
      <li>
        <span class="guar-claim-steps__num" aria-hidden="true">1</span>
        <div>
          <h3>Rate the Session</h3>
          <p>After your session ends, you'll receive an automatic prompt to rate your experience. A rating below 3 stars triggers our guarantee review process.</p>
        </div>
      </li>
      <li>
        <span class="guar-claim-steps__num" aria-hidden="true">2</span>
        <div>
          <h3>Contact Us Within 24 Hours</h3>
          <p>Email <a href="mailto:<?php echo esc_attr(get_theme_mod('ngt_contact_email', 'support@nextgentutors.co.za')); ?>">support</a> or use the chat widget on this page. Include your booking reference number.</p>
        </div>
      </li>
      <li>
        <span class="guar-claim-steps__num" aria-hidden="true">3</span>
        <div>
          <h3>We Review Within 24 Hours</h3>
          <p>Our support team investigates the issue with the tutor and any available session data. We respond with our resolution decision within 24 business hours.</p>
        </div>
      </li>
      <li>
        <span class="guar-claim-steps__num" aria-hidden="true">4</span>
        <div>
          <h3>Resolution Applied</h3>
          <p>Approved refunds are returned to your original PayFast payment method within 3–5 business days. Replacement sessions are scheduled within 48 hours.</p>
        </div>
      </li>
    </ol>
  </div>
</section>

<!-- EXCLUSIONS -->
<section class="guar-exclusions section" aria-labelledby="guar-excl-title">
  <div class="container guar-exclusions__inner">
    <h2 id="guar-excl-title" class="section__title">What's Not Covered</h2>
    <p class="section__sub">Our guarantee is designed to be fair to both students and tutors. The following are not covered:</p>
    <ul class="guar-excl-list" role="list">
      <li><i data-lucide="x" aria-hidden="true"></i> Sessions cancelled less than 24 hours before start by the student (late cancellation fee applies)</li>
      <li><i data-lucide="x" aria-hidden="true"></i> Sessions where the student was more than 15 minutes late</li>
      <li><i data-lucide="x" aria-hidden="true"></i> Sessions paid for outside the NextGen Tutors platform</li>
      <li><i data-lucide="x" aria-hidden="true"></i> Claims submitted more than 24 hours after the session ended</li>
      <li><i data-lucide="x" aria-hidden="true"></i> Subjective dissatisfaction where the tutor demonstrably delivered the agreed session</li>
    </ul>
  </div>
</section>

<!-- STATS BAND -->
<section class="guar-stats section section--lime" aria-label="Guarantee statistics">
  <div class="container guar-stats__inner" role="list">
    <div class="guar-stat" role="listitem">
      <strong>94%</strong><span>sessions rated 4★ or above</span>
    </div>
    <div class="guar-stat" role="listitem">
      <strong>&lt;1%</strong><span>of sessions result in a claim</span>
    </div>
    <div class="guar-stat" role="listitem">
      <strong>100%</strong><span>of valid claims resolved</span>
    </div>
    <div class="guar-stat" role="listitem">
      <strong>24h</strong><span>average claim resolution</span>
    </div>
  </div>
</section>

<script>
if (window.lucide) lucide.createIcons();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
