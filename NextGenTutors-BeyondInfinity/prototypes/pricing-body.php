<?php
/** Auto-extracted from pricing.html — do not edit DOM structure. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>


<section class="pagehead">
  <div class="pagehead__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap pagehead__inner">
    <div class="pagehead__crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" data-internal>Home</a> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg> <span>Pricing</span></div>
    <span class="eyebrow pagehead__eyebrow">✦ Transparent Pricing Engine</span>
    <h1 class="pagehead__title">Unmatched <span class="accent">Value</span></h1>
    <p class="pagehead__sub">Top-tier, background-cleared educators at half the standard agency cost. Premium 1-on-1 tutoring, priced honestly in South African Rand.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="price-grid">
      <article class="price-card" data-reveal>
        <span class="price-tag">Online Classroom</span>
        <h3 class="price-name">Grade 1–12 Online</h3>
        <p class="price-desc">Digital whiteboards, past-paper training and instant recording vaults of the full CAPS syllabus.</p>
        <div class="price-amount"><span class="r">R</span><span class="n">320</span><span class="per">/ hour</span></div>
        <ul class="price-feats">
          <li><i data-lucide="check-circle-2"></i> CAPS / IEB / Cambridge syllabi</li>
          <li><i data-lucide="check-circle-2"></i> Full recorded session links</li>
          <li><i data-lucide="check-circle-2"></i> Tutor payout: R200/hr</li>
          <li><i data-lucide="check-circle-2"></i> Flexible weekly scheduling</li>
        </ul>
        <a class="btn btn--primary btn--block" href="<?php echo esc_url( ngt_get_page_url( 'find-a-tutor' ) ); ?>" data-internal>Choose Online</a>
      </article>

      <article class="price-card price-card--feature" data-reveal>
        <span class="price-tag">Most Popular</span>
        <h3 class="price-name">In-Person At Home</h3>
        <p class="price-desc">A vetted tutor travels directly to your home. Safe, direct feedback with physical workflow sheets.</p>
        <div class="price-amount"><span class="r">R</span><span class="n">350</span><span class="per">/ hour</span></div>
        <ul class="price-feats">
          <li><i data-lucide="check-circle-2"></i> Gauteng &amp; Cape suburb coverage</li>
          <li><i data-lucide="check-circle-2"></i> ID &amp; police-clearance vetted</li>
          <li><i data-lucide="check-circle-2"></i> Tutor payout: R250/hr</li>
          <li><i data-lucide="check-circle-2"></i> Love the lesson or it's free</li>
        </ul>
        <a class="btn btn--lime btn--block btn--shine" href="<?php echo esc_url( ngt_get_page_url( 'find-a-tutor' ) ); ?>" data-internal>Book In-Person</a>
      </article>

      <article class="price-card" data-reveal>
        <span class="price-tag">University Core</span>
        <h3 class="price-name">Tertiary Subjects</h3>
        <p class="price-desc">Undergraduate engineering, financial accounting, advanced statistics and computer science.</p>
        <div class="price-amount"><span class="r">R</span><span class="n">500</span><span class="per">/ hour</span></div>
        <ul class="price-feats">
          <li><i data-lucide="check-circle-2"></i> Financial maths &amp; engineering</li>
          <li><i data-lucide="check-circle-2"></i> Honours-level specialists</li>
          <li><i data-lucide="check-circle-2"></i> Tutor payout: R350/hr</li>
          <li><i data-lucide="check-circle-2"></i> Flexible scheduling slots</li>
        </ul>
        <a class="btn btn--primary btn--block" href="<?php echo esc_url( ngt_get_page_url( 'find-a-tutor' ) ); ?>" data-internal>Choose Tertiary</a>
      </article>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="shead" data-reveal>
      <span class="eyebrow shead__eyebrow">Plan Your Budget</span>
      <h2 class="h-serif shead__title">Live Rate Calculator</h2>
    </div>
    <div class="calc" data-reveal-scale>
      <div class="guarantee__glow"></div>
      <div class="calc__inner">
        <div class="calc__panel">
          <div class="calc-field">
            <span class="calc-field__label">Academic Level</span>
            <div class="seg seg--3" id="calc-level">
              <button data-level="primary">Grade 1–7</button>
              <button class="is-active" data-level="high">Grade 8–12</button>
              <button data-level="tertiary">Varsity</button>
            </div>
          </div>
          <div class="calc-field" id="calc-format-field">
            <span class="calc-field__label">Delivery Format</span>
            <div class="seg seg--2" id="calc-format">
              <button class="is-active" data-format="online">Online</button>
              <button data-format="inperson">In-Person (JHB)</button>
            </div>
          </div>
          <div class="calc-field" id="calc-commit-field">
            <span class="calc-field__label">Commitment Length</span>
            <div class="seg seg--3" id="calc-commit">
              <button class="is-active" data-commit="1-3">1–3 Months</button>
              <button data-commit="3-12">3–12 Months</button>
              <button data-commit="12+">12+ / Volume</button>
            </div>
          </div>
          <div class="calc-field">
            <span class="calc-field__label">Sessions / Month: <span id="calc-lessons-val" style="color:var(--lime)">4</span></span>
            <input type="range" class="calc-range" id="calc-lessons" min="2" max="24" value="4" />
            <div class="calc-range-labels"><span>2 / mo</span><span>12 (discount)</span><span>24 / mo</span></div>
          </div>
        </div>

        <div class="calc-out">
          <span class="price-tag" style="background:var(--blue);color:var(--lime);align-self:flex-start">Calculated Monthly Plan</span>
          <div class="calc-out__row">
            <span class="calc-out__label">Hourly client rate</span>
            <span class="calc-out__big"><span class="r">R</span><span id="calc-rate">320</span></span>
          </div>
          <div class="calc-out__row">
            <span class="calc-out__label">Tutor payout</span>
            <span style="font-weight:800;color:var(--lime)">R<span id="calc-payout">200</span>/hr</span>
          </div>
          <div class="calc-out__row" style="padding-top:16px;border-top:1px solid rgba(255,255,255,0.1)">
            <span class="calc-out__label">Total / month</span>
            <span class="calc-out__total">R<span id="calc-total">1,280</span></span>
          </div>
          <div class="calc-save" id="calc-save" style="display:none">
            <span class="l">Volume Savings</span>
            <span class="v">R<span id="calc-saved">0</span> saved</span>
          </div>
          <a class="btn btn--lime btn--block btn--shine" href="<?php echo esc_url( ngt_get_page_url( 'find-a-tutor' ) ); ?>" data-internal>Find Tutors at This Rate</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--slate-50)">
  <div class="wrap">
    <div class="shead shead--center" data-reveal>
      <span class="eyebrow shead__eyebrow">Good To Know</span>
      <h2 class="h-serif shead__title">Pricing Questions</h2>
    </div>
    <div class="faq" id="faq">
      <div class="faq-item">
        <button class="faq-q">Are there any hidden fees or contracts? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button>
        <div class="faq-a"><div class="faq-a__inner">Never. You pay per session or per package via PayFast — no lock-in contracts, no joining fees, no surprises. Bundles simply lower your hourly rate.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-q">How does the volume discount work? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button>
        <div class="faq-a"><div class="faq-a__inner">Book 12 or more sessions in a month and your rate drops to a flat R300/hr immediately, regardless of your commitment length. Use the calculator above to see your exact savings.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-q">What is your cancellation policy? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button>
        <div class="faq-a"><div class="faq-a__inner">We require 24 hours' notice to reschedule or cancel. Cancellations under 4 hours mean the tutor receives 50% of their base payout to compensate for prep and travel.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-q">Is the first lesson really guaranteed? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button>
        <div class="faq-a"><div class="faq-a__inner">Yes — our NextGen100 guarantee means if you're not satisfied with your first lesson, we'll match you with another tutor or refund you completely. No questions asked.</div></div>
      </div>
    </div>
  </div>
</section><script src="https://cdn.jsdelivr.net/npm/lenis@1.1.14/dist/lenis.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>