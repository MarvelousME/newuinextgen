<?php
/** Auto-extracted from become-a-tutor.html — do not edit DOM structure. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>


<section class="pagehead">
  <div class="pagehead__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap pagehead__inner">
    <div class="pagehead__crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" data-internal>Home</a> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg> <span>Become a Tutor</span></div>
    <span class="eyebrow pagehead__eyebrow">Join 50,000+ Educators</span>
    <h1 class="pagehead__title">Teach On <span class="accent">Your</span> Terms</h1>
    <p class="pagehead__sub">Set your own rates and hours, get matched with learners near you, and grow a reputation backed by verified reviews. We handle billing, scheduling and PayFast payouts.</p>
    <div class="pagehead__stats">
      <div class="pagehead__stat"><div class="n">R25k</div><div class="l">Top Monthly Earnings</div></div>
      <div class="pagehead__stat"><div class="n">Weekly</div><div class="l">PayFast Payouts</div></div>
      <div class="pagehead__stat"><div class="n">48hr</div><div class="l">First Booking</div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap bt">
    <div class="bt__visual" data-reveal-x style="--rx:-50px">
      <div class="bt__img"><img src="https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=900" alt="A tutor smiling during an online lesson" referrerpolicy="no-referrer" /></div>
      <div class="bt__earn float"><div class="l">Earn up to</div><div class="v">R25,000<span style="font-size:13px;font-style:normal">/mo</span></div></div>
    </div>
    <div data-reveal-x style="--rx:50px">
      <span class="eyebrow shead__eyebrow">Why Tutor With Us</span>
      <h2 class="h-serif" style="font-size:clamp(28px,3.8vw,46px);margin:12px 0 18px">Real Income, Real Flexibility</h2>
      <p class="lead">Whether you're a varsity student, qualified teacher or industry expert, NextGen gives you the tools and the learners to build a thriving tutoring practice.</p>
      <div class="bt__list">
        <div class="bt__item"><span class="bt__item-ico"><i data-lucide="wallet"></i></span><div><div class="bt__item-t">Reliable Monthly Payouts</div><div class="bt__item-d">R200–R350/hr depending on level &amp; format. Earnings are calculated automatically on the 1st of each month and paid by EFT.</div></div></div>
        <div class="bt__item"><span class="bt__item-ico"><i data-lucide="calendar-clock"></i></span><div><div class="bt__item-t">Total Schedule Freedom</div><div class="bt__item-d">Accept only the slots that fit your life — full-time or a few hours a week. Amelia syncs your calendar automatically.</div></div></div>
        <div class="bt__item"><span class="bt__item-ico"><i data-lucide="badge-check"></i></span><div><div class="bt__item-t">Verified Profile &amp; Reviews</div><div class="bt__item-d">Build trust fast with SACE verification, and earn badges &amp; visibility boosts from 5-star ratings.</div></div></div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="calc calc--ngt-ui" data-reveal-scale>
      <div class="guarantee__glow"></div>
      <div class="calc__inner calc__inner--shortcode">
        <?php
        if ( shortcode_exists( 'ngt_income_calculator' ) ) {
          echo do_shortcode( '[ngt_income_calculator title="Earnings Calculator" hours_per_week="12" hourly_rate="225" platform_fee="15" class="ngt-ui-ic--become-a-tutor"]' );
        } else {
          echo '<p class="lead">' . esc_html__( 'Income calculator unavailable — enable NextGen Companion UI library.', 'nextgentutors-beyondinfinity' ) . '</p>';
        }
        ?>
        <p class="calc__cta-wrap">
          <a class="btn btn--lime btn--block btn--shine" href="<?php echo esc_url( ngt_get_page_url( 'contact' ) ); ?>" data-internal><?php esc_html_e( 'Apply to Tutor →', 'nextgentutors-beyondinfinity' ); ?></a>
        </p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--slate-50)">
  <div class="wrap">
    <div class="shead" data-reveal>
      <span class="eyebrow shead__eyebrow">Getting Started</span>
      <h2 class="h-serif shead__title">From Application to First Lesson</h2>
    </div>
    <div class="steps">
      <article class="step icon-pop" data-reveal><span class="step__n">01</span><span class="step__ico"><i data-lucide="file-text"></i></span><h3 class="step__title">Apply Online</h3><p class="step__desc">Submit your subjects, qualifications and availability in under 10 minutes.</p></article>
      <article class="step icon-pop" data-reveal><span class="step__n">02</span><span class="step__ico"><i data-lucide="shield-check"></i></span><h3 class="step__title">Get Verified</h3><p class="step__desc">We confirm your SACE status, ID and police clearance — your safety badge unlocks bookings.</p></article>
      <article class="step icon-pop" data-reveal><span class="step__n">03</span><span class="step__ico"><i data-lucide="graduation-cap"></i></span><h3 class="step__title">Start Earning</h3><p class="step__desc">Get matched within 48 hours, teach online or in-person, and get paid weekly.</p></article>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="shead shead--center" data-reveal><span class="eyebrow shead__eyebrow">Tutor FAQ</span><h2 class="h-serif shead__title">Common Questions</h2></div>
    <div class="faq" id="faq">
      <div class="faq-item"><button class="faq-q">What qualifications do I need? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button><div class="faq-a"><div class="faq-a__inner">You'll need proof of subject expertise (a relevant degree, current enrolment, or teaching qualification) plus a valid SA ID for verification. Strong subject knowledge and a passion for teaching matter most.</div></div></div>
      <div class="faq-item"><button class="faq-q">How and when do I get paid? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button><div class="faq-a"><div class="faq-a__inner">Your earnings from completed sessions are calculated automatically on the 1st of each month — gross session fees minus platform fees, plus any bonuses — and paid out by EFT. You always see your exact payout rate before accepting a booking.</div></div></div>
      <div class="faq-item"><button class="faq-q">Can I tutor part-time? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button><div class="faq-a"><div class="faq-a__inner">Absolutely. You set your own availability and accept only the slots you want — many of our tutors teach just a few hours a week around their studies or job.</div></div></div>
    </div>
  </div>
</section><script src="https://cdn.jsdelivr.net/npm/lenis@1.1.14/dist/lenis.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>