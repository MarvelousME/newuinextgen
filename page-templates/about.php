<?php
/**
 * Template Name: About
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');
?>

<!-- HERO -->
<section class="about-hero section" aria-labelledby="about-hero-title">
  <div class="container about-hero__inner">
    <div class="about-hero__copy">
      <span class="badge badge--lime">Our Story</span>
      <h1 id="about-hero-title">Connecting South Africa's Best Tutors with Students Who Need Them</h1>
      <p>NextGen Tutors was built to fix a broken system — where quality tutoring was expensive, hard to find, and unevenly distributed across provinces. We changed that.</p>
    </div>
    <div class="about-hero__stats" role="list">
      <div class="about-stat" role="listitem">
        <strong class="counter" data-target="50000">0</strong>
        <span>Active Tutors</span>
      </div>
      <div class="about-stat" role="listitem">
        <strong class="counter" data-target="52100">0</strong>
        <span>Students Enrolled</span>
      </div>
      <div class="about-stat" role="listitem">
        <strong class="counter" data-target="9">0</strong>
        <span>Provinces Covered</span>
      </div>
      <div class="about-stat" role="listitem">
        <strong>94%</strong>
        <span>Satisfaction Rate</span>
      </div>
    </div>
  </div>
</section>

<!-- MISSION -->
<section class="about-mission section section--alt" aria-labelledby="about-mission-title">
  <div class="container about-mission__inner">
    <div class="about-mission__copy">
      <h2 id="about-mission-title">Our Mission</h2>
      <p>Every South African student deserves access to a great tutor — regardless of where they live, what school they attend, or what their family can afford.</p>
      <p>We built NextGen Tutors as a marketplace that puts quality first. Tutors are vetted, background-checked, and reviewed by real students. Rates are transparent. Booking takes 60 seconds. Payments are secure through PayFast.</p>
      <p>Whether you're in Soweto or Stellenbosch, Durban or Polokwane — we've got you covered.</p>
    </div>
    <div class="about-mission__values" role="list">
      <?php
      $values = [
        ['Access', 'Tutoring for every grade, every subject, all 9 provinces.', 'map-pin'],
        ['Quality', 'Every tutor is vetted. Every session is guaranteed.', 'shield-check'],
        ['Fairness', 'Transparent pricing. Tutors keep <?php echo (100 - (int) get_theme_mod("ngt_platform_fee_percent", 15)); ?>% of every session.', 'scale'],
        ['Safety', 'ID-verified tutors, secure payments, POPIA-compliant platform.', 'lock'],
      ];
      foreach ($values as $v) : ?>
        <div class="value-card" role="listitem">
          <span class="value-card__icon" aria-hidden="true"><i data-lucide="<?php echo esc_attr($v[2]); ?>"></i></span>
          <h3><?php echo esc_html($v[0]); ?></h3>
          <p><?php echo esc_html($v[1]); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TEAM -->
<section class="about-team section" aria-labelledby="about-team-title">
  <div class="container">
    <h2 id="about-team-title" class="section__title">The Team Behind NextGen</h2>
    <p class="section__sub">Educators, engineers, and parents working together to improve tutoring access across South Africa.</p>
    <ul class="team-grid" role="list">
      <?php
      $team = [
        ['Nomsa Dlamini', 'CEO & Co-founder', 'Former educator with 15 years in curriculum development across Gauteng schools.'],
        ['Sipho Khumalo', 'CTO', 'Full-stack engineer and EdTech veteran. Built learning platforms serving 200k+ students.'],
        ['Priya Naidoo', 'Head of Tutor Success', 'Manages the vetting pipeline and tutor community. Based in Durban.'],
        ['Andile Mokoena', 'Head of Growth', 'Drives student acquisition and provincial expansion across all 9 provinces.'],
      ];
      foreach ($team as $member) : ?>
        <li class="team-card">
          <div class="team-card__avatar" aria-hidden="true">
            <span><?php echo esc_html(mb_substr($member[0], 0, 1)); ?></span>
          </div>
          <h3><?php echo esc_html($member[0]); ?></h3>
          <span class="team-card__role"><?php echo esc_html($member[1]); ?></span>
          <p><?php echo esc_html($member[2]); ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- PROVINCES MAP -->
<section class="about-provinces section section--alt" aria-labelledby="about-provinces-title">
  <div class="container">
    <h2 id="about-provinces-title" class="section__title">Active in All 9 Provinces</h2>
    <?php
    $provinces = [
      'Gauteng' => ['18200', '16400'],
      'KwaZulu-Natal' => ['9100', '8800'],
      'Western Cape' => ['7400', '6900'],
      'Eastern Cape' => ['4800', '4500'],
      'Limpopo' => ['3900', '3700'],
      'Mpumalanga' => ['2800', '2600'],
      'North West' => ['1900', '1800'],
      'Free State' => ['1600', '1500'],
      'Northern Cape' => ['300', '300'],
    ];
    ?>
    <ul class="province-list" role="list">
      <?php foreach ($provinces as $name => [$tutors, $students]) : ?>
        <li class="province-row" role="listitem">
          <span class="province-row__name"><?php echo esc_html($name); ?></span>
          <div class="province-row__bar-wrap" aria-hidden="true">
            <div class="province-row__bar" style="width:<?php echo min(100, round($tutors / 200)); ?>%"></div>
          </div>
          <span class="province-row__count"><?php echo number_format((int)$tutors); ?> tutors</span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- PARTNERS / TRUST -->
<section class="about-trust section" aria-labelledby="about-trust-title">
  <div class="container">
    <h2 id="about-trust-title" class="section__title">Trusted &amp; Compliant</h2>
    <ul class="trust-badges" role="list">
      <li class="trust-badge"><i data-lucide="shield" aria-hidden="true"></i><span>POPIA Compliant</span></li>
      <li class="trust-badge"><i data-lucide="credit-card" aria-hidden="true"></i><span>PayFast Secure Payments</span></li>
      <li class="trust-badge"><i data-lucide="check-circle" aria-hidden="true"></i><span>ID Verified Tutors</span></li>
      <li class="trust-badge"><i data-lucide="book-open" aria-hidden="true"></i><span>CAPS Curriculum Aligned</span></li>
      <li class="trust-badge"><i data-lucide="lock" aria-hidden="true"></i><span>SSL Encrypted</span></li>
    </ul>
  </div>
</section>

<!-- CTA -->
<section class="about-cta section section--navy" aria-labelledby="about-cta-title">
  <div class="container about-cta__inner">
    <h2 id="about-cta-title">Ready to Get Started?</h2>
    <p>Find a tutor in your area today, or join our growing network of vetted educators.</p>
    <div class="about-cta__actions">
      <a href="<?php echo esc_url(ngt_get_page_url('find_tutor')); ?>" class="btn btn--lime btn--lg">Find a Tutor</a>
      <a href="<?php echo esc_url(ngt_get_page_url('become_tutor')); ?>" class="btn btn--outline-white btn--lg">Become a Tutor</a>
    </div>
  </div>
</section>

<script>
(function () {
  // Counter animation
  const counters = document.querySelectorAll('.counter[data-target]');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el     = entry.target;
      const target = parseInt(el.dataset.target, 10);
      const step   = Math.ceil(target / 60);
      let current  = 0;
      const timer  = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString('en-ZA') + (target >= 1000 ? '' : '');
        if (current >= target) clearInterval(timer);
      }, 25);
      observer.unobserve(el);
    });
  }, { threshold: 0.3 });
  counters.forEach(c => observer.observe(c));

  if (window.lucide) lucide.createIcons();
})();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
