<?php
/**
 * Template Name: Contact
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$phone    = get_theme_mod('ngt_contact_phone', '+27 10 123 4567');
$whatsapp = get_theme_mod('ngt_contact_whatsapp', '+27 10 123 4567');
$email    = get_theme_mod('ngt_contact_email', 'support@nextgentutors.co.za');
$address  = get_theme_mod('ngt_contact_address', 'Johannesburg, Gauteng, South Africa');
$hours    = get_theme_mod('ngt_contact_hours', 'Mon–Fri: 8am–8pm · Sat: 9am–5pm');
$maps_url = get_theme_mod('ngt_contact_maps_url', 'https://maps.google.com');
?>

<!-- HERO -->
<section class="contact-hero section" aria-labelledby="contact-hero-title">
  <div class="container contact-hero__inner">
    <div class="contact-hero__copy">
      <span class="badge badge--lime"><?php esc_html_e( 'Get in Touch', 'beyondinfinity' ); ?></span>
      <h1 id="contact-hero-title" data-bi-slide-title><?php esc_html_e( "We're Here to Help", 'beyondinfinity' ); ?></h1>
      <p><?php esc_html_e( "We're here to help with any questions about tutoring. Questions about your account or the platform? We typically respond within one business day.", 'beyondinfinity' ); ?></p>
    </div>
  </div>
</section>

<!-- CONTACT MAIN -->
<section class="contact-main section" aria-label="Contact options and form">
  <div class="container contact-main__inner">

    <!-- CONTACT CHANNELS -->
    <aside class="contact-channels" aria-label="Contact channels">
      <div class="contact-channel">
        <span class="contact-channel__icon" aria-hidden="true"><i data-lucide="phone"></i></span>
        <div>
          <h3>Phone</h3>
          <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
          <p class="contact-channel__note"><?php echo esc_html($hours); ?></p>
        </div>
      </div>
      <div class="contact-channel">
        <span class="contact-channel__icon contact-channel__icon--green" aria-hidden="true"><i data-lucide="message-circle"></i></span>
        <div>
          <h3>WhatsApp</h3>
          <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a>
          <p class="contact-channel__note">Fastest response</p>
        </div>
      </div>
      <div class="contact-channel">
        <span class="contact-channel__icon" aria-hidden="true"><i data-lucide="mail"></i></span>
        <div>
          <h3>Email</h3>
          <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
          <p class="contact-channel__note">Response within 24 hours</p>
        </div>
      </div>
      <div class="contact-channel">
        <span class="contact-channel__icon" aria-hidden="true"><i data-lucide="map-pin"></i></span>
        <div>
          <h3>Office</h3>
          <p><?php echo esc_html($address); ?></p>
          <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer" class="contact-channel__link">View on Google Maps <i data-lucide="external-link" aria-hidden="true"></i></a>
        </div>
      </div>

      <!-- SLA badge -->
      <div class="contact-sla" role="status">
        <i data-lucide="clock" aria-hidden="true"></i>
        <span>Average first response: <strong>under 2 hours</strong></span>
      </div>
    </aside>

    <!-- CONTACT FORM -->
    <div class="contact-form-wrap" role="main">
      <h2>Send Us a Message</h2>
      <?php
      if (function_exists('do_shortcode')) {
          // FluentForms: form ID 2 is the general contact form
          echo do_shortcode('[fluentform id="2"]');
      } else {
          echo '<p class="notice notice--info">Contact form loading...</p>';
      }
      ?>
    </div>

  </div>
</section>

<!-- FAQ -->
<section class="contact-faq section section--alt" aria-labelledby="contact-faq-title">
  <div class="container contact-faq__container">
    <h2 id="contact-faq-title" class="section__title">Common Questions</h2>
    <dl class="faq-list">
      <?php
      $faqs = [
        ['How do I book a tutor?', 'Search for a tutor on our Find a Tutor page, select their profile, choose a date and time, and complete payment via PayFast. You\'ll receive a confirmation email instantly.'],
        ['How long does tutor approval take?', 'Most applications are reviewed within 24–48 business hours. You\'ll receive email updates at every stage of the vetting process.'],
        ['Can I change or cancel a booking?', 'Yes. You can reschedule or cancel up to 24 hours before your session from your Student Dashboard at no charge.'],
        ['What if my tutor doesn\'t show up?', 'Contact us immediately. Under our No-Surprise Guarantee, you\'ll receive a full refund or a free replacement session.'],
        ['Is my payment information secure?', 'Yes. All payments are processed by PayFast, a PCI-DSS compliant payment gateway. We never store your card details.'],
        ['How do I report a problem with a session?', 'Use the contact form on this page, or email us directly. Include your booking reference number for fastest resolution.'],
      ];
      foreach ($faqs as $i => $faq) : ?>
        <div class="faq-item">
          <dt class="faq-item__q">
            <button class="faq-item__btn" aria-expanded="false" aria-controls="cf-<?php echo $i; ?>" id="cf-btn-<?php echo $i; ?>">
              <?php echo esc_html($faq[0]); ?>
              <span aria-hidden="true"><i data-lucide="chevron-down"></i></span>
            </button>
          </dt>
          <dd class="faq-item__a" id="cf-<?php echo $i; ?>" role="region" aria-labelledby="cf-btn-<?php echo $i; ?>" hidden>
            <p><?php echo esc_html($faq[1]); ?></p>
          </dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<script>
(function () {
  document.querySelectorAll('.faq-item__btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll('.faq-item__btn').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        const p = document.getElementById(b.getAttribute('aria-controls'));
        if (p) p.hidden = true;
      });
      if (!expanded) {
        this.setAttribute('aria-expanded', 'true');
        const p = document.getElementById(this.getAttribute('aria-controls'));
        if (p) p.hidden = false;
      }
    });
  });
  if (window.lucide) lucide.createIcons();
})();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
