<?php
/**
 * Template Name: Support
 */
defined('ABSPATH') || exit;

// Redirect authenticated tutors/students with open tickets straight to dashboard support tab
if ( is_user_logged_in() ) {
    $user = wp_get_current_user();
    if ( in_array('tutor', (array) $user->roles, true) || in_array('student', (array) $user->roles, true) ) {
        // Let page render — dashboard has an embedded tab, but this page is the public-facing surface
    }
}

get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$phone    = get_theme_mod('ngt_contact_phone', '+27 10 123 4567');
$email    = get_theme_mod('ngt_contact_email', 'support@nextgentutors.co.za');
$whatsapp = get_theme_mod('ngt_contact_whatsapp', '');
$sla      = get_theme_mod('ngt_support_sla', '2 business hours');
?>

<section class="support-hero section" aria-labelledby="support-hero-title">
  <div class="container support-hero__inner">
    <span class="badge badge--lime">Support Centre</span>
    <h1 id="support-hero-title">How Can We Help?</h1>
    <p>Get help with bookings, payments, tutor issues, or your account. Average first response: <strong><?php echo esc_html($sla); ?></strong>.</p>
    <div class="support-channels" role="list">
      <a href="mailto:<?php echo esc_attr($email); ?>" class="support-channel-btn" role="listitem">
        <i data-lucide="mail" aria-hidden="true"></i>
        <span>Email Support</span>
        <small><?php echo esc_html($email); ?></small>
      </a>
      <?php if ($phone) : ?>
        <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>" class="support-channel-btn" role="listitem">
          <i data-lucide="phone" aria-hidden="true"></i>
          <span>Call Us</span>
          <small><?php echo esc_html($phone); ?></small>
        </a>
      <?php endif; ?>
      <?php if ($whatsapp) : ?>
        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" class="support-channel-btn support-channel-btn--green" target="_blank" rel="noopener noreferrer" role="listitem">
          <i data-lucide="message-circle" aria-hidden="true"></i>
          <span>WhatsApp</span>
          <small>Fastest response</small>
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- TICKET FORM via Fluent Support -->
<section class="support-tickets section" aria-labelledby="support-tickets-title">
  <div class="container support-tickets__inner">
    <h2 id="support-tickets-title" class="section__title">Submit a Support Ticket</h2>
    <p class="section__sub">Describe your issue and we'll get back to you within <?php echo esc_html($sla); ?>.</p>
    <?php
    if ( function_exists('fluentSupport') || shortcode_exists('fluent_support_portal') ) {
        $mailbox_id = (int) get_option( 'ngc_fluent_support_mailbox_id', 0 );
        $sc = '[fluent_support_portal show_logout="yes"';
        if ( $mailbox_id > 0 ) {
            $sc .= ' business_box_id="' . $mailbox_id . '"';
        }
        $sc .= ']';
        echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ( shortcode_exists('fluentform') ) {
        // Fallback: FluentForms support form (ID 3)
        echo do_shortcode('[fluentform id="3"]');
    } else {
        // Final fallback
        ?>
        <div class="support-fallback-form">
          <form class="support-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" novalidate>
            <?php wp_nonce_field('ngt_support_ticket', 'ngt_ticket_nonce'); ?>
            <input type="hidden" name="action" value="ngt_submit_support_ticket">
            <div class="form-field">
              <label for="sf-name">Your name <span aria-hidden="true">*</span></label>
              <input type="text" id="sf-name" name="name" required autocomplete="name">
            </div>
            <div class="form-field">
              <label for="sf-email">Email address <span aria-hidden="true">*</span></label>
              <input type="email" id="sf-email" name="email" required autocomplete="email">
            </div>
            <div class="form-field">
              <label for="sf-category">Issue type</label>
              <select id="sf-category" name="category">
                <option value="booking">Booking / Cancellation</option>
                <option value="payment">Payment / Refund</option>
                <option value="tutor">Tutor Complaint</option>
                <option value="account">Account Access</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-field">
              <label for="sf-subject">Subject <span aria-hidden="true">*</span></label>
              <input type="text" id="sf-subject" name="subject" required>
            </div>
            <div class="form-field">
              <label for="sf-message">Message <span aria-hidden="true">*</span></label>
              <textarea id="sf-message" name="message" rows="6" class="ngc-wysiwyg" required></textarea>
            </div>
            <div class="form-field form-field--booking-ref">
              <label for="sf-ref">Booking reference (if applicable)</label>
              <input type="text" id="sf-ref" name="booking_ref">
            </div>
            <button type="submit" class="btn btn--lime">Submit Ticket</button>
          </form>
        </div>
        <?php
    }
    ?>
  </div>
</section>

<!-- KNOWLEDGE BASE QUICK LINKS -->
<section class="support-kb section section--alt" aria-labelledby="support-kb-title">
  <div class="container">
    <h2 id="support-kb-title" class="section__title">Common Issues &amp; Quick Fixes</h2>
    <div class="kb-grid" role="list">
      <?php
      $kb_items = [
        ['Can\'t log in to my account',      'account',  'Try resetting your password via the login page. If the issue persists, email us with your registered email address.'],
        ['Payment failed or pending',         'payment',  'PayFast payments can take up to 2 hours to confirm. Check your spam folder for the receipt. If nothing after 2 hours, contact us with your payment reference.'],
        ['Need to cancel or reschedule',      'booking',  'Go to your dashboard, find the booking, and click "Cancel" or "Reschedule". Cancellations within 24 hours of the session may incur a fee.'],
        ['Haven\'t received my payout',       'payout',   'Payouts are processed on the 1st of each month. Allow 3–5 business days for your bank to reflect the transfer. Check your payout history in your Tutor Dashboard.'],
        ['My tutor application is pending',   'vetting',  'Most applications take 24–48 business hours. If it\'s been over 72 hours, check your spam folder for verification emails, or contact us.'],
        ['Student rated me unfairly',         'rating',   'We review all rating disputes. Submit a ticket with the session details and we\'ll investigate within 48 hours.'],
      ];
      foreach ($kb_items as $item) : ?>
        <div class="kb-card" role="listitem">
          <h3><?php echo esc_html($item[0]); ?></h3>
          <p><?php echo esc_html($item[2]); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
if (window.lucide) lucide.createIcons();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
