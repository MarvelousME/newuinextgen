<?php
/**
 * Template Name: Become a Tutor
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$platform_fee = (int) get_theme_mod('ngt_platform_fee_percent', 15);
$min_payout   = (int) get_theme_mod('ngt_min_payout_zar', 500);
$ref_reward   = (int) get_theme_mod('ngt_referral_reward_zar', 100);
$tutor_share  = 100 - $platform_fee;
?>

<!-- HERO -->
<section class="bat-hero" aria-labelledby="bat-hero-title">
  <div class="container">
    <div class="bat-hero__inner">
      <div class="bat-hero__copy">
        <span class="badge badge--lime">Earn on Your Terms</span>
        <h1 id="bat-hero-title" data-bi-slide-title>Share Your Knowledge.<br>Build Your Income.</h1>
        <p>Join over 50,000 tutors across South Africa teaching online and in-person on NextGen Tutors. Set your own rates, choose your subjects, work when you want.</p>
        <div class="bat-hero__stats">
          <div class="stat-pill"><strong>R320–R500</strong><span>avg hourly rate</span></div>
          <div class="stat-pill"><strong><?php echo $tutor_share; ?>%</strong><span>you keep</span></div>
          <div class="stat-pill"><strong>52,100+</strong><span>students waiting</span></div>
        </div>
        <a href="#apply" class="btn btn--lime btn--lg">Apply Now — Free</a>
      </div>
      <div class="bat-hero__visual" aria-hidden="true">
        <div class="earnings-preview-card">
          <div class="epc__label">Monthly Earnings Potential</div>
          <div class="epc__amount" id="hero-earning-preview">R0</div>
          <div class="epc__sessions" id="hero-session-preview">0 sessions / month</div>
          <input class="epc__slider" id="hero-slider" type="range" min="4" max="80" value="20" aria-label="Adjust sessions per month">
          <div class="epc__note">Based on R320/hr, <?php echo $tutor_share; ?>% share</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="bat-steps section" aria-labelledby="bat-steps-title">
  <div class="container">
    <h2 id="bat-steps-title" class="section__title">How It Works</h2>
    <p class="section__sub">From application to earning in 3 simple steps.</p>
    <ol class="bat-steps__list" role="list">
      <li class="bat-step">
        <div class="bat-step__num" aria-hidden="true">01</div>
        <div class="bat-step__body">
          <h3>Apply Online</h3>
          <p>Complete the form below. Tell us your subjects, qualifications, and availability. Takes about 5 minutes.</p>
        </div>
      </li>
      <li class="bat-step">
        <div class="bat-step__num" aria-hidden="true">02</div>
        <div class="bat-step__body">
          <h3>Vetting &amp; Approval</h3>
          <p>We verify your ID, qualifications, and conduct a background check. Most tutors are approved within 48 hours.</p>
        </div>
      </li>
      <li class="bat-step">
        <div class="bat-step__num" aria-hidden="true">03</div>
        <div class="bat-step__body">
          <h3>Start Teaching</h3>
          <p>Your profile goes live. Students book directly. You get paid monthly straight to your bank account.</p>
        </div>
      </li>
    </ol>
  </div>
</section>

<!-- EARNINGS CALCULATOR -->
<section class="bat-calc section section--alt" aria-labelledby="bat-calc-title">
  <div class="container">
    <h2 id="bat-calc-title" class="section__title">Earnings Calculator</h2>
    <p class="section__sub">See what you could earn teaching on NextGen Tutors.</p>
    <div class="calc-widget" role="form" aria-label="Earnings calculator">
      <div class="calc-widget__controls">
        <div class="calc-field">
          <label for="calc-rate">Your hourly rate (ZAR)</label>
          <input type="number" id="calc-rate" class="calc-input" value="320" min="100" max="2000" step="10" aria-describedby="calc-rate-hint">
          <span id="calc-rate-hint" class="calc-hint">Min R100 · Avg R320–R500</span>
        </div>
        <div class="calc-field">
          <label for="calc-sessions">Sessions per week</label>
          <input type="range" id="calc-sessions" class="calc-slider" value="5" min="1" max="40" aria-valuetext="5 sessions">
          <output id="calc-sessions-output" for="calc-sessions">5 sessions/week</output>
        </div>
        <div class="calc-field">
          <label for="calc-duration">Session duration</label>
          <select id="calc-duration" class="calc-select">
            <option value="1">1 hour</option>
            <option value="1.5">1.5 hours</option>
            <option value="2">2 hours</option>
          </select>
        </div>
      </div>
      <div class="calc-widget__result" aria-live="polite" aria-atomic="true">
        <div class="calc-result__row">
          <span>Weekly earnings</span>
          <strong id="calc-weekly">R0</strong>
        </div>
        <div class="calc-result__row">
          <span>Monthly earnings</span>
          <strong id="calc-monthly">R0</strong>
        </div>
        <div class="calc-result__row calc-result__row--platform">
          <span>Platform fee (<?php echo $platform_fee; ?>%)</span>
          <strong id="calc-fee">−R0</strong>
        </div>
        <div class="calc-result__row calc-result__row--total">
          <span>Your take-home (monthly)</span>
          <strong id="calc-takehome">R0</strong>
        </div>
        <p class="calc-result__note">Payouts processed monthly. Minimum payout: R<?php echo number_format($min_payout); ?>.</p>
      </div>
    </div>
  </div>
</section>

<!-- BENEFITS -->
<section class="bat-benefits section" aria-labelledby="bat-benefits-title">
  <div class="container">
    <h2 id="bat-benefits-title" class="section__title">Why Tutors Choose Us</h2>
    <ul class="bat-benefits__grid" role="list">
      <li class="benefit-card">
        <span class="benefit-card__icon" aria-hidden="true"><i data-lucide="calendar"></i></span>
        <h3>Full Flexibility</h3>
        <p>Set your own schedule, accept or decline bookings. No minimum hours, no penalties.</p>
      </li>
      <li class="benefit-card">
        <span class="benefit-card__icon" aria-hidden="true"><i data-lucide="shield-check"></i></span>
        <h3>Verified Students</h3>
        <p>Every student profile is verified. You always know who you're teaching before accepting.</p>
      </li>
      <li class="benefit-card">
        <span class="benefit-card__icon" aria-hidden="true"><i data-lucide="banknote"></i></span>
        <h3>Reliable Payments</h3>
        <p>Monthly payouts via EFT. <?php echo $tutor_share; ?>% of every session goes straight to you, no delays.</p>
      </li>
      <li class="benefit-card">
        <span class="benefit-card__icon" aria-hidden="true"><i data-lucide="users"></i></span>
        <h3>Referral Rewards</h3>
        <p>Earn R<?php echo number_format($ref_reward); ?> for every tutor you refer who gets approved and completes their first session.</p>
      </li>
      <li class="benefit-card">
        <span class="benefit-card__icon" aria-hidden="true"><i data-lucide="bar-chart-2"></i></span>
        <h3>Tutor Dashboard</h3>
        <p>Real-time earnings, upcoming sessions, student ratings, and payout history in one place.</p>
      </li>
      <li class="benefit-card">
        <span class="benefit-card__icon" aria-hidden="true"><i data-lucide="award"></i></span>
        <h3>Badge &amp; Ranking</h3>
        <p>Build your reputation. Top-rated tutors get featured placement and priority in search results.</p>
      </li>
    </ul>
  </div>
</section>

<!-- APPLICATION FORM -->
<section id="apply" class="bat-apply section section--alt" aria-labelledby="bat-apply-title">
  <div class="container bat-apply__container">
    <div class="bat-apply__header">
      <h2 id="bat-apply-title" class="section__title">Apply to Become a Tutor</h2>
      <p class="section__sub">Free to apply. No commitment until you're approved.</p>
    </div>
    <?php
    if ( function_exists('do_shortcode') ) {
        // FluentForms: form ID 1 is the tutor application form seeded during demo import
        echo do_shortcode('[fluentform id="1"]');
    } else {
        echo '<p class="notice notice--info">Application form loading...</p>';
    }
    ?>
  </div>
</section>

<!-- FAQ -->
<section class="bat-faq section" aria-labelledby="bat-faq-title">
  <div class="container bat-faq__container">
    <h2 id="bat-faq-title" class="section__title">Frequently Asked Questions</h2>
    <dl class="faq-list" id="bat-faq-list">
      <?php
      $faqs = [
        ['Is it free to sign up?', 'Yes. Creating a tutor profile and applying is completely free. NextGen Tutors takes a ' . $platform_fee . '% platform fee only when you complete a paid session.'],
        ['How quickly will I be approved?', 'Most tutors are approved within 24–48 business hours after submitting all required documents. You\'ll receive an email at each step.'],
        ['What documents do I need?', 'South African ID or passport, your highest qualification certificate, and a clear profile photo. Some subjects may require additional proof of expertise.'],
        ['How do I get paid?', 'Payouts are processed on the 1st of each month via EFT to your South African bank account. Minimum payout is R' . number_format($min_payout) . '.'],
        ['Can I teach both online and in-person?', 'Yes. You choose your teaching format when you set up your profile. You can offer one or both, and change at any time.'],
        ['What subjects can I teach?', 'Any subject from Grade 1 to Tertiary level, including university preparation, coding, languages, and exam revision. See our full subject list on the Find a Tutor page.'],
        ['What if a student cancels?', 'Sessions cancelled less than 24 hours before the start time are covered by our No-Surprise Guarantee. You\'ll receive 50% of the session fee.'],
      ];
      foreach ($faqs as $i => $faq) : ?>
        <div class="faq-item" data-index="<?php echo $i; ?>">
          <dt class="faq-item__q">
            <button class="faq-item__btn" aria-expanded="false" aria-controls="bat-faq-<?php echo $i; ?>" id="bat-faq-btn-<?php echo $i; ?>">
              <?php echo esc_html($faq[0]); ?>
              <span aria-hidden="true"><i data-lucide="chevron-down"></i></span>
            </button>
          </dt>
          <dd class="faq-item__a" id="bat-faq-<?php echo $i; ?>" role="region" aria-labelledby="bat-faq-btn-<?php echo $i; ?>" hidden>
            <p><?php echo esc_html($faq[1]); ?></p>
          </dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<script>
(function () {
  // Earnings calculator
  const rateInput     = document.getElementById('calc-rate');
  const sessionSlider = document.getElementById('calc-sessions');
  const sessionOutput = document.getElementById('calc-sessions-output');
  const durationSel   = document.getElementById('calc-duration');
  const platformFee   = <?php echo $platform_fee; ?> / 100;

  function updateCalc() {
    const rate     = parseFloat(rateInput.value) || 0;
    const sessions = parseInt(sessionSlider.value, 10);
    const duration = parseFloat(durationSel.value);
    const weekly   = rate * duration * sessions;
    const monthly  = weekly * 4.33;
    const fee      = monthly * platformFee;
    const takehome = monthly - fee;

    sessionOutput.textContent = sessions + ' session' + (sessions === 1 ? '' : 's') + '/week';
    sessionSlider.setAttribute('aria-valuetext', sessions + ' sessions');
    document.getElementById('calc-weekly').textContent    = 'R' + weekly.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('calc-monthly').textContent   = 'R' + monthly.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('calc-fee').textContent       = '−R' + fee.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('calc-takehome').textContent  = 'R' + takehome.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  [rateInput, sessionSlider, durationSel].forEach(el => el.addEventListener('input', updateCalc));
  updateCalc();

  // Hero slider preview
  const heroSlider  = document.getElementById('hero-slider');
  const heroAmt     = document.getElementById('hero-earning-preview');
  const heroSess    = document.getElementById('hero-session-preview');

  function updateHero() {
    const sessions = parseInt(heroSlider.value, 10);
    const monthly  = 320 * sessions * (1 - platformFee);
    heroAmt.textContent  = 'R' + monthly.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    heroSess.textContent = sessions + ' sessions / month';
  }
  if (heroSlider) { heroSlider.addEventListener('input', updateHero); updateHero(); }

  // FAQ accordion
  document.querySelectorAll('.faq-item__btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll('.faq-item__btn').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        document.getElementById(b.getAttribute('aria-controls')).hidden = true;
      });
      if (!expanded) {
        this.setAttribute('aria-expanded', 'true');
        document.getElementById(this.getAttribute('aria-controls')).hidden = false;
      }
    });
  });

  if (window.lucide) lucide.createIcons();
})();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
