<?php
/** Auto-extracted from find-a-tutor.html — do not edit DOM structure. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>


<section class="pagehead">
  <div class="pagehead__bg" aria-hidden="true">
    <div class="pagehead__mesh"></div>
    <div class="pagehead__grid"></div>
  </div>
  <div class="wrap pagehead__inner">
    <div class="pagehead__crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" data-internal>Home</a> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg> <span>Find a Tutor</span></div>
    <span class="eyebrow pagehead__eyebrow">500+ Verified Educators</span>
    <h1 class="pagehead__title">Find Your <span class="accent">Perfect</span> Tutor</h1>
    <p class="pagehead__sub">Filter by subject, teaching format and budget. Every tutor is SACE-registered, ID-verified and background-checked — so you can book with total confidence.</p>
    <div class="pagehead__stats">
      <div class="pagehead__stat"><div class="n">4.8★</div><div class="l">Average Rating</div></div>
      <div class="pagehead__stat"><div class="n">94%</div><div class="l">Satisfaction</div></div>
      <div class="pagehead__stat"><div class="n">48hr</div><div class="l">First Booking</div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap directory">
    <aside class="filters" id="filters">
      <div class="filters__h"><i data-lucide="sliders-horizontal"></i> Refine Results</div>
      <div class="filter-group">
        <div class="filter-group__label">Subject</div>
        <div class="filter-chips" id="filter-subjects"></div>
      </div>
      <div class="filter-group">
        <div class="filter-group__label">Format</div>
        <div class="filter-chips" id="filter-format">
          <button class="fchip is-active" data-format="all">All</button>
          <button class="fchip" data-format="online">Online</button>
          <button class="fchip" data-format="personal">In-Person</button>
        </div>
      </div>
      <div class="filter-group">
        <div class="filter-group__label">Max Rate / Hour</div>
        <input type="range" class="filter-range" id="filter-price" min="300" max="500" step="10" value="500" />
        <div class="filter-range-val" id="price-val">R500</div>
      </div>
      <button class="btn btn--ghost btn--block" id="clear-filters" style="background:var(--slate-100);color:var(--navy);border-color:var(--slate-200);margin-top:8px">Clear Filters</button>
    </aside>

    <div>
      <div class="dir-bar">
        <div class="dir-count"><b id="result-count">0</b> tutors match your search</div>
        <div class="filter-chips">
          <button class="fchip is-active" data-sort="rating">Top Rated</button>
          <button class="fchip" data-sort="price-low">Price ↑</button>
          <button class="fchip" data-sort="price-high">Price ↓</button>
        </div>
      </div>
      <div class="dir-grid" id="dir-grid"></div>
    </div>
  </div>
</section>

<section class="section section--tight" style="padding-top:0">
  <div class="wrap">
    <div class="shead" data-reveal>
      <span class="eyebrow shead__eyebrow">After You Choose</span>
      <h2 class="h-serif shead__title">How Booking Works</h2>
    </div>
    <div class="steps">
      <article class="step icon-pop" data-reveal><span class="step__n">01</span><span class="step__ico"><i data-lucide="calendar-plus"></i></span><h3 class="step__title">Pick a Slot</h3><p class="step__desc">Choose a time from your tutor's live availability — online whiteboard or in-person at home.</p></article>
      <article class="step icon-pop" data-reveal><span class="step__n">02</span><span class="step__ico"><i data-lucide="shield-check"></i></span><h3 class="step__title">Pay Securely</h3><p class="step__desc">Checkout is protected end-to-end by PayFast in South African Rand. Your booking is confirmed instantly.</p></article>
      <article class="step icon-pop" data-reveal><span class="step__n">03</span><span class="step__ico"><i data-lucide="bell-ring"></i></span><h3 class="step__title">Get Reminded</h3><p class="step__desc">Automatic reminders land 24 hours, 1 hour and 15 minutes before — with the join link and prep notes, so no one misses a session.</p></article>
    </div>
  </div>
</section>

<section class="section section--tight">
  <div class="wrap">
    <div class="cta-band" data-reveal-scale>
      <div class="cta-band__glow"></div>
      <div class="cta-band__c">
        <span class="eyebrow" style="color:var(--lime)">Not sure who to pick?</span>
        <h2 class="h-serif" style="color:#fff;font-size:clamp(26px,3.4vw,40px);margin:12px 0 10px">Let Our Matchmaker Help</h2>
        <p class="lead" style="color:rgba(255,255,255,0.7);max-width:540px">Tell us your subject, grade and goals — we'll hand-match you to the ideal vetted tutor, free of charge.</p>
      </div>
      <a class="btn btn--lime btn--shine" href="<?php echo esc_url( ngt_get_page_url( 'contact' ) ); ?>" data-internal style="position:relative;z-index:1">Get Matched Free</a>
    </div>
  </div>
</section><script src="https://cdn.jsdelivr.net/npm/lenis@1.1.14/dist/lenis.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>