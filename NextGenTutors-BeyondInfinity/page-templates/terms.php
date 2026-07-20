<?php
/**
 * Template Name: Terms of Service
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$email   = get_theme_mod('ngt_contact_email', 'legal@nextgentutors.co.za');
$updated = '1 January 2025';
?>

<article class="legal-page section" aria-labelledby="terms-title">
  <div class="container legal-page__inner">

    <!-- SIDEBAR NAV -->
    <aside class="legal-nav" aria-label="Terms sections">
      <nav>
        <h2 class="legal-nav__title">Contents</h2>
        <ol class="legal-nav__list" role="list">
          <?php
          $sections = [
            'terms-intro'         => '1. Introduction',
            'terms-definitions'   => '2. Definitions',
            'terms-eligibility'   => '3. Eligibility',
            'terms-accounts'      => '4. User Accounts',
            'terms-tutors'        => '5. Tutor Obligations',
            'terms-students'      => '6. Student Obligations',
            'terms-payments'      => '7. Payments &amp; Fees',
            'terms-bookings'      => '8. Bookings &amp; Cancellations',
            'terms-guarantee'     => '9. Guarantee',
            'terms-ip'            => '10. Intellectual Property',
            'terms-liability'     => '11. Limitation of Liability',
            'terms-termination'   => '12. Termination',
            'terms-governing-law' => '13. Governing Law',
            'terms-changes'       => '14. Changes to Terms',
            'terms-contact'       => '15. Contact',
          ];
          foreach ($sections as $id => $label) : ?>
            <li><a href="#<?php echo esc_attr($id); ?>"><?php echo $label; ?></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>
    </aside>

    <!-- CONTENT -->
    <div class="legal-content">
      <h1 id="terms-title">Terms of Service</h1>
      <p class="legal-meta">Last updated: <time datetime="2025-01-01"><?php echo esc_html($updated); ?></time></p>

      <section id="terms-intro" aria-labelledby="terms-intro-h">
        <h2 id="terms-intro-h">1. Introduction</h2>
        <p>These Terms of Service ("Terms") govern your use of the NextGen Tutors platform ("Platform"), operated at nextgentutors.co.za. By creating an account or using any part of the Platform, you agree to be bound by these Terms.</p>
        <p>The Platform is operated by NextGen Tutors. References to "we", "us", or "our" mean NextGen Tutors.</p>
      </section>

      <section id="terms-definitions" aria-labelledby="terms-def-h">
        <h2 id="terms-def-h">2. Definitions</h2>
        <dl class="legal-defs">
          <dt>Platform</dt><dd>The NextGen Tutors website at nextgentutors.co.za and any associated mobile applications.</dd>
          <dt>Tutor</dt><dd>A user who has applied for, and been approved to, provide tutoring services through the Platform.</dd>
          <dt>Student</dt><dd>A user who books and receives tutoring sessions through the Platform.</dd>
          <dt>Session</dt><dd>A tutoring appointment booked and completed through the Platform.</dd>
          <dt>Session Fee</dt><dd>The amount paid by a Student for a Session, denominated in South African Rand (ZAR).</dd>
          <dt>Platform Fee</dt><dd>The percentage of the Session Fee retained by NextGen Tutors as a service charge.</dd>
        </dl>
      </section>

      <section id="terms-eligibility" aria-labelledby="terms-elig-h">
        <h2 id="terms-elig-h">3. Eligibility</h2>
        <p>You must be at least 18 years old to create an account. Users under 18 may use the Platform only under the supervision of a parent or legal guardian who accepts these Terms on their behalf.</p>
        <p>By using the Platform, you represent that you have the legal capacity to enter into a binding contract under South African law.</p>
      </section>

      <section id="terms-accounts" aria-labelledby="terms-acc-h">
        <h2 id="terms-acc-h">4. User Accounts</h2>
        <p>You are responsible for maintaining the confidentiality of your account credentials. You agree to notify us immediately at <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a> of any unauthorised access to your account.</p>
        <p>You may not create more than one account per role (Tutor, Student, or Parent). We reserve the right to suspend or terminate duplicate accounts.</p>
      </section>

      <section id="terms-tutors" aria-labelledby="terms-tut-h">
        <h2 id="terms-tut-h">5. Tutor Obligations</h2>
        <p>As a Tutor, you agree to:</p>
        <ul>
          <li>Provide accurate information during the application and vetting process.</li>
          <li>Deliver sessions in a professional, punctual, and competent manner.</li>
          <li>Not solicit payment from Students outside of the Platform.</li>
          <li>Not share personal contact information with Students prior to an approved booking.</li>
          <li>Comply with our Tutor Code of Conduct at all times.</li>
          <li>Notify us of any changes to your qualifications, criminal record status, or working rights.</li>
        </ul>
      </section>

      <section id="terms-students" aria-labelledby="terms-stu-h">
        <h2 id="terms-stu-h">6. Student Obligations</h2>
        <p>As a Student (or Parent booking on behalf of a Student), you agree to:</p>
        <ul>
          <li>Treat Tutors with respect. Abusive or harassing behaviour will result in account suspension.</li>
          <li>Attend booked sessions on time. Sessions missed without 24-hour notice may incur a cancellation fee.</li>
          <li>Pay for sessions only through the Platform using PayFast.</li>
          <li>Not share session recordings or materials without the Tutor's written consent.</li>
        </ul>
      </section>

      <section id="terms-payments" aria-labelledby="terms-pay-h">
        <h2 id="terms-pay-h">7. Payments &amp; Fees</h2>
        <p>All payments are processed in South African Rand (ZAR) through PayFast, our designated payment processor. By making a payment, you agree to PayFast's terms and conditions.</p>
        <p>NextGen Tutors deducts a platform fee from each completed Session Fee before disbursing the remainder to the Tutor. The current platform fee percentage is displayed on our Pricing page.</p>
        <p>Tutor payouts are processed on the 1st of each calendar month for sessions completed in the prior month, subject to a minimum payout threshold.</p>
      </section>

      <section id="terms-bookings" aria-labelledby="terms-book-h">
        <h2 id="terms-book-h">8. Bookings &amp; Cancellations</h2>
        <p><strong>Student cancellations:</strong> Cancellations made more than 24 hours before the session start time receive a full refund. Cancellations within 24 hours are charged a fee of 50% of the session fee.</p>
        <p><strong>Tutor cancellations:</strong> Tutors who cancel with less than 24 hours notice may be subject to a penalty applied to their next payout and a notation on their profile.</p>
        <p><strong>Rescheduling:</strong> Either party may request a reschedule up to 24 hours before the session. Reschedules are subject to mutual agreement.</p>
      </section>

      <section id="terms-guarantee" aria-labelledby="terms-guar-h">
        <h2 id="terms-guar-h">9. Guarantee</h2>
        <p>Our No-Surprise Guarantee applies to qualifying sessions as described on our <a href="<?php echo esc_url(ngt_get_page_url('guarantee')); ?>">Guarantee page</a>. Claims must be submitted within 24 hours of session completion. We reserve the right to investigate all claims before approving remediation.</p>
      </section>

      <section id="terms-ip" aria-labelledby="terms-ip-h">
        <h2 id="terms-ip-h">10. Intellectual Property</h2>
        <p>All content on the Platform — including text, images, logos, and software — is owned by or licensed to NextGen Tutors. You may not reproduce, distribute, or create derivative works without our written consent.</p>
        <p>Session materials shared by Tutors remain the Tutor's intellectual property. Students receive a personal, non-transferable licence to use shared materials for their own study purposes only.</p>
      </section>

      <section id="terms-liability" aria-labelledby="terms-liab-h">
        <h2 id="terms-liab-h">11. Limitation of Liability</h2>
        <p>NextGen Tutors is a marketplace that facilitates connections between Tutors and Students. We are not responsible for the quality, accuracy, or outcomes of individual tutoring sessions beyond what is covered by our Guarantee.</p>
        <p>To the maximum extent permitted by South African law, our total liability to you shall not exceed the amount you paid for the session giving rise to the claim.</p>
      </section>

      <section id="terms-termination" aria-labelledby="terms-term-h">
        <h2 id="terms-term-h">12. Termination</h2>
        <p>We may suspend or terminate your account at any time for breach of these Terms, fraudulent activity, or conduct that we reasonably believe poses a risk to other users or the integrity of the Platform.</p>
        <p>You may close your account at any time by contacting us. Outstanding payout balances will be settled within 30 days of account closure.</p>
      </section>

      <section id="terms-governing-law" aria-labelledby="terms-gov-h">
        <h2 id="terms-gov-h">13. Governing Law</h2>
        <p>These Terms are governed by the laws of the Republic of South Africa. Any disputes arising from these Terms or your use of the Platform shall be subject to the exclusive jurisdiction of the courts of South Africa.</p>
      </section>

      <section id="terms-changes" aria-labelledby="terms-changes-h">
        <h2 id="terms-changes-h">14. Changes to Terms</h2>
        <p>We may update these Terms from time to time. We will notify registered users of material changes by email at least 14 days before they take effect. Continued use of the Platform after the effective date constitutes acceptance of the revised Terms.</p>
      </section>

      <section id="terms-contact" aria-labelledby="terms-con-h">
        <h2 id="terms-con-h">15. Contact</h2>
        <p>For questions about these Terms, contact us at <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a> or through our <a href="<?php echo esc_url(ngt_get_page_url('contact')); ?>">Contact page</a>.</p>
      </section>
    </div>

  </div>
</article>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
