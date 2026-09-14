<?php
/**
 * Support centre prototype.
 *
 * @package NextGen_Tutors
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="pagehead">
  <div class="pagehead__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap pagehead__inner">
    <div class="pagehead__crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" data-internal>Home</a> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg> <span>Support</span></div>
    <span class="eyebrow pagehead__eyebrow">Help Centre</span>
    <h1 class="pagehead__title">We're Here <span class="accent">For You</span></h1>
    <p class="pagehead__sub">Open a ticket, browse FAQs, or chat with our placement team. Average first response under 4 business hours.</p>
  </div>
</section>

<section class="section">
  <div class="wrap contact-grid">
    <div class="contact-card" data-reveal-x style="--rx:-50px">
      <div class="contact-card__glow"></div>
      <h2 class="h-serif" style="color:#fff;font-size:26px;margin-bottom:6px;position:relative;z-index:1">Support Channels</h2>
      <p style="color:rgba(255,255,255,0.6);font-size:13.5px;line-height:1.6;margin-bottom:8px;position:relative;z-index:1;font-weight:500">Choose the fastest route for your issue — billing, scheduling, safety or tutor matching.</p>
      <div class="contact-method"><span class="contact-method__ico"><i data-lucide="life-buoy"></i></span><div><div class="contact-method__t">Live Chat</div><div class="contact-method__v">Use the floating widget (bottom-right)</div></div></div>
      <div class="contact-method"><span class="contact-method__ico"><i data-lucide="ticket"></i></span><div><div class="contact-method__t">Tickets</div><div class="contact-method__v">Fluent Support — tracked &amp; prioritised</div></div></div>
      <div class="contact-method"><span class="contact-method__ico"><i data-lucide="phone"></i></span><div><div class="contact-method__t">Phone</div><div class="contact-method__v"><?php echo esc_html( get_theme_mod( 'ngt_phone', '0800 123 4567' ) ); ?></div></div></div>
      <div class="contact-method"><span class="contact-method__ico"><i data-lucide="mail"></i></span><div><div class="contact-method__t">Email</div><div class="contact-method__v"><?php echo esc_html( get_theme_mod( 'ngt_email', 'support@nextgentutors.co.za' ) ); ?></div></div></div>
    </div>

    <div class="form-card" data-reveal-x style="--rx:50px">
      <h2 class="h-serif" style="font-size:24px;color:var(--navy);margin-bottom:6px;text-align:center">Open a Support Ticket</h2>
      <p style="font-size:13.5px;color:var(--slate-500);font-weight:500;margin-bottom:24px;text-align:center">Describe your issue and we'll route it to the right specialist.</p>
      <form id="contact-form">
        <div class="field-row">
          <div class="field"><label for="cf-name">Full Name</label><input type="text" id="cf-name" required placeholder="Your name" /></div>
          <div class="field"><label for="cf-email">Email</label><input type="email" id="cf-email" required placeholder="you@email.co.za" /></div>
        </div>
        <div class="field"><label for="cf-subject">Issue Type</label>
          <select id="cf-subject">
            <option>Billing &amp; Payments</option>
            <option>Scheduling / Bookings</option>
            <option>Tutor Matching</option>
            <option>Safety Concern</option>
            <option>Other</option>
          </select>
        </div>
        <div class="field"><label for="cf-msg">Details</label><textarea id="cf-msg" placeholder="How can we help?"></textarea></div>
        <button class="btn btn--primary btn--shine btn--block" type="submit">Submit Ticket</button>
        <div class="form-success" id="cf-success"><i data-lucide="check-circle-2"></i> Ticket received — we'll reply within one business day.</div>
      </form>
    </div>
  </div>
</section>

<section class="section section--tight" style="padding-top:0">
  <div class="wrap">
    <div class="shead shead--center" data-reveal><span class="eyebrow shead__eyebrow">Quick Answers</span><h2 class="h-serif shead__title">Common Support Topics</h2></div>
    <div class="faq" id="faq">
      <div class="faq-item"><button class="faq-q">How do I reschedule a session? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button><div class="faq-a"><div class="faq-a__inner">Log in to your dashboard, open Upcoming Sessions, and use Reschedule — or contact your tutor directly via chat at least 24 hours before the slot.</div></div></div>
      <div class="faq-item"><button class="faq-q">Where is my invoice? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button><div class="faq-a"><div class="faq-a__inner">All PayFast receipts and PDF invoices are listed under Billing &amp; Invoices on your student dashboard. WooCommerce order emails are sent automatically.</div></div></div>
      <div class="faq-item"><button class="faq-q">How does the satisfaction guarantee work? <span class="faq-q__ico"><i data-lucide="plus"></i></span></button><div class="faq-a"><div class="faq-a__inner">If your first session doesn't meet expectations, open a ticket within 48 hours and we'll credit a replacement session at no charge. See our Guarantee page for full terms.</div></div></div>
    </div>
  </div>
</section>
