<?php
/**
 * Template Name: Pricing
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$platform_fee = (int) get_theme_mod('ngt_platform_fee_percent', 15);

// Fetch WC products for pricing cards
$products = wc_get_products([
    'status'   => 'publish',
    'limit'    => 10,
    'orderby'  => 'menu_order',
    'order'    => 'ASC',
    'category' => [],
]);
?>

<!-- HERO -->
<section class="pricing-hero section" aria-labelledby="pricing-hero-title">
  <div class="container pricing-hero__inner">
    <span class="badge badge--lime">Simple Pricing</span>
    <h1 id="pricing-hero-title">Pay Per Session.<br>No Subscriptions. No Hidden Fees.</h1>
    <p>Choose the session type that works for you. Pay securely through PayFast. Cancel anytime.</p>
    <div class="pricing-hero__trust">
      <span><i data-lucide="shield-check" aria-hidden="true"></i> PayFast secured</span>
      <span><i data-lucide="refresh-cw" aria-hidden="true"></i> Money-back guarantee</span>
      <span><i data-lucide="lock" aria-hidden="true"></i> No lock-in contracts</span>
    </div>
  </div>
</section>

<!-- PRICING CARDS -->
<section class="pricing-cards-section section" aria-labelledby="pricing-cards-title">
  <div class="container">
    <h2 id="pricing-cards-title" class="sr-only">Pricing Plans</h2>
    <div class="pricing-grid" role="list">
      <?php if (!empty($products)) :
        foreach ($products as $product) :
          $sku        = $product->get_sku();
          $featured   = ($sku === 'NGT-INPERSON-350');
          $price      = $product->get_price();
          $name       = $product->get_name();
          $desc       = $product->get_short_description();
          $link       = $product->get_permalink();
          $format     = (strpos($sku, 'ONLINE') !== false) ? 'Online' : ((strpos($sku, 'TERTIARY') !== false) ? 'Tertiary' : 'In-Person');
      ?>
        <article class="price-card<?php echo $featured ? ' price-card--featured' : ''; ?>" role="listitem" aria-label="<?php echo esc_attr($name); ?> pricing">
          <?php if ($featured) : ?><div class="price-card__badge">Most Popular</div><?php endif; ?>
          <div class="price-card__header">
            <h3><?php echo esc_html($name); ?></h3>
            <span class="price-card__format"><?php echo esc_html($format); ?></span>
          </div>
          <div class="price-card__price">
            <span class="price-card__currency">R</span>
            <span class="price-card__amount"><?php echo esc_html(number_format((float)$price, 0)); ?></span>
            <span class="price-card__period">/ hour</span>
          </div>
          <?php if ($desc) : ?>
            <p class="price-card__desc"><?php echo wp_kses_post($desc); ?></p>
          <?php endif; ?>
          <ul class="price-card__features" role="list">
            <li><i data-lucide="check" aria-hidden="true"></i> Vetted, verified tutor</li>
            <li><i data-lucide="check" aria-hidden="true"></i> Any subject, all grades</li>
            <li><i data-lucide="check" aria-hidden="true"></i> Instant booking confirmation</li>
            <li><i data-lucide="check" aria-hidden="true"></i> Session notes &amp; resources</li>
            <li><i data-lucide="check" aria-hidden="true"></i> Rating &amp; review system</li>
          </ul>
          <a href="<?php echo esc_url(ngt_get_page_url('find_tutor')); ?>" class="btn<?php echo $featured ? ' btn--lime' : ' btn--outline'; ?> btn--block">Find a Tutor</a>
        </article>
      <?php endforeach;
      else :
        // Fallback if WC products not seeded yet
        $fallback = [
          ['Online Session', 'NGT-ONLINE-320', 320, false],
          ['In-Person Session', 'NGT-INPERSON-350', 350, true],
          ['Tertiary Session', 'NGT-TERTIARY-500', 500, false],
        ];
        foreach ($fallback as [$name, $sku, $price, $featured]) : ?>
          <article class="price-card<?php echo $featured ? ' price-card--featured' : ''; ?>" role="listitem">
            <?php if ($featured) : ?><div class="price-card__badge">Most Popular</div><?php endif; ?>
            <div class="price-card__header">
              <h3><?php echo esc_html($name); ?></h3>
            </div>
            <div class="price-card__price">
              <span class="price-card__currency">R</span>
              <span class="price-card__amount"><?php echo number_format($price); ?></span>
              <span class="price-card__period">/ hour</span>
            </div>
            <ul class="price-card__features" role="list">
              <li><i data-lucide="check" aria-hidden="true"></i> Vetted, verified tutor</li>
              <li><i data-lucide="check" aria-hidden="true"></i> Any subject, all grades</li>
              <li><i data-lucide="check" aria-hidden="true"></i> Instant booking confirmation</li>
              <li><i data-lucide="check" aria-hidden="true"></i> Session notes &amp; resources</li>
            </ul>
            <a href="<?php echo esc_url(ngt_get_page_url('find_tutor')); ?>" class="btn<?php echo $featured ? ' btn--lime' : ' btn--outline'; ?> btn--block">Find a Tutor</a>
          </article>
        <?php endforeach;
      endif; ?>
    </div>
  </div>
</section>

<!-- LIVE COST CALCULATOR -->
<section class="pricing-calc section section--alt" aria-labelledby="pricing-calc-title">
  <div class="container">
    <h2 id="pricing-calc-title" class="section__title">Calculate Your Monthly Cost</h2>
    <p class="section__sub">See exactly what you'll pay before you book.</p>
    <div class="cost-calc" role="form" aria-label="Monthly cost calculator">
      <div class="cost-calc__controls">
        <div class="cost-calc__field">
          <label for="cc-type">Session type</label>
          <select id="cc-type" class="calc-select">
            <option value="320">Online (R320/hr)</option>
            <option value="350">In-Person (R350/hr)</option>
            <option value="500">Tertiary (R500/hr)</option>
          </select>
        </div>
        <div class="cost-calc__field">
          <label for="cc-sessions">Sessions per week</label>
          <input type="range" id="cc-sessions" class="calc-slider" min="1" max="20" value="2" aria-valuetext="2 sessions">
          <output id="cc-sessions-out" for="cc-sessions">2 sessions/week</output>
        </div>
        <div class="cost-calc__field">
          <label for="cc-duration">Duration</label>
          <select id="cc-duration" class="calc-select">
            <option value="1">1 hour</option>
            <option value="1.5">1.5 hours</option>
            <option value="2">2 hours</option>
          </select>
        </div>
      </div>
      <div class="cost-calc__result" aria-live="polite" aria-atomic="true">
        <div class="calc-result__row"><span>Per session</span><strong id="cc-per-session">R0</strong></div>
        <div class="calc-result__row"><span>Per week</span><strong id="cc-per-week">R0</strong></div>
        <div class="calc-result__row calc-result__row--total"><span>Per month (est.)</span><strong id="cc-per-month">R0</strong></div>
        <p class="calc-result__note">All payments processed securely via PayFast in ZAR.</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="pricing-faq section" aria-labelledby="pricing-faq-title">
  <div class="container pricing-faq__container">
    <h2 id="pricing-faq-title" class="section__title">Pricing FAQs</h2>
    <dl class="faq-list" id="pricing-faq-list">
      <?php
      $faqs = [
        ['How do I pay?', 'All payments are processed securely through PayFast — South Africa\'s leading payment gateway. You can pay by credit/debit card, EFT, or Ozow instant EFT.'],
        ['Is there a subscription or monthly fee?', 'No. You pay per session only. There are no monthly fees, no subscriptions, and no contracts.'],
        ['What\'s the cancellation policy?', 'Cancel for free up to 24 hours before a session. Late cancellations (under 24h) are charged at 50% of the session fee.'],
        ['Can I get a refund?', 'Yes. Our No-Surprise Guarantee means if a session doesn\'t meet our quality standard, you get a full refund or a free replacement session.'],
        ['Do prices vary by tutor?', 'Rates shown are base rates. Individual tutors may charge more based on experience, qualifications, or demand. All rates are clearly shown on the tutor\'s profile before booking.'],
        ['Are there discounts for bulk sessions?', 'We\'re working on bundle pricing. Sign up to our newsletter to be notified when packages launch.'],
      ];
      foreach ($faqs as $i => $faq) : ?>
        <div class="faq-item" data-index="<?php echo $i; ?>">
          <dt class="faq-item__q">
            <button class="faq-item__btn" aria-expanded="false" aria-controls="pf-<?php echo $i; ?>" id="pf-btn-<?php echo $i; ?>">
              <?php echo esc_html($faq[0]); ?>
              <span aria-hidden="true"><i data-lucide="chevron-down"></i></span>
            </button>
          </dt>
          <dd class="faq-item__a" id="pf-<?php echo $i; ?>" role="region" aria-labelledby="pf-btn-<?php echo $i; ?>" hidden>
            <p><?php echo esc_html($faq[1]); ?></p>
          </dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<!-- GUARANTEE BAND -->
<section class="pricing-guarantee section section--lime" aria-labelledby="pricing-guar-title">
  <div class="container pricing-guarantee__inner">
    <i data-lucide="shield-check" class="pricing-guarantee__icon" aria-hidden="true"></i>
    <div>
      <h2 id="pricing-guar-title">Our No-Surprise Guarantee</h2>
      <p>If you're not satisfied after your first session, we'll give you a full refund or a free replacement session — no questions asked.</p>
      <a href="<?php echo esc_url(ngt_get_page_url('guarantee')); ?>" class="btn btn--navy btn--sm">Learn More</a>
    </div>
  </div>
</section>

<script>
(function () {
  const typeEl    = document.getElementById('cc-type');
  const sessEl    = document.getElementById('cc-sessions');
  const durEl     = document.getElementById('cc-duration');
  const sessOut   = document.getElementById('cc-sessions-out');

  function fmt(n) { return 'R' + Math.round(n).toLocaleString('en-ZA'); }

  function updateCost() {
    const rate     = parseFloat(typeEl.value);
    const sessions = parseInt(sessEl.value, 10);
    const duration = parseFloat(durEl.value);
    const perSess  = rate * duration;
    const perWeek  = perSess * sessions;
    const perMonth = perWeek * 4.33;

    sessOut.textContent = sessions + ' session' + (sessions === 1 ? '' : 's') + '/week';
    sessEl.setAttribute('aria-valuetext', sessions + ' sessions');
    document.getElementById('cc-per-session').textContent = fmt(perSess);
    document.getElementById('cc-per-week').textContent    = fmt(perWeek);
    document.getElementById('cc-per-month').textContent   = fmt(perMonth);
  }

  [typeEl, sessEl, durEl].forEach(el => el.addEventListener('input', updateCost));
  updateCost();

  // FAQ accordion
  document.querySelectorAll('.faq-item__btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll('.faq-item__btn').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        const panel = document.getElementById(b.getAttribute('aria-controls'));
        if (panel) panel.hidden = true;
      });
      if (!expanded) {
        this.setAttribute('aria-expanded', 'true');
        const panel = document.getElementById(this.getAttribute('aria-controls'));
        if (panel) panel.hidden = false;
      }
    });
  });

  if (window.lucide) lucide.createIcons();
})();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
