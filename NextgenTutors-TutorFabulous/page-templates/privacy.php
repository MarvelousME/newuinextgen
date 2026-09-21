<?php
/**
 * Template Name: Privacy Policy
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$email   = get_theme_mod('ngt_contact_email', 'privacy@nextgentutors.co.za');
$updated = '1 January 2025';
?>

<article class="legal-page section" aria-labelledby="privacy-title">
  <div class="container legal-page__inner">

    <aside class="legal-nav" aria-label="Privacy policy sections">
      <nav>
        <h2 class="legal-nav__title">Contents</h2>
        <ol class="legal-nav__list" role="list">
          <?php
          $sections = [
            'priv-intro'       => '1. Introduction',
            'priv-who'         => '2. Who We Are',
            'priv-collect'     => '3. What We Collect',
            'priv-use'         => '4. How We Use Your Data',
            'priv-share'       => '5. Who We Share Data With',
            'priv-minors'      => '6. Children &amp; Minors',
            'priv-retention'   => '7. Retention',
            'priv-security'    => '8. Security',
            'priv-rights'      => '9. Your Rights (POPIA)',
            'priv-cookies'     => '10. Cookies',
            'priv-transfers'   => '11. International Transfers',
            'priv-changes'     => '12. Changes to This Policy',
            'priv-contact'     => '13. Contact',
          ];
          foreach ($sections as $id => $label) : ?>
            <li><a href="#<?php echo esc_attr($id); ?>"><?php echo $label; ?></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>
    </aside>

    <div class="legal-content">
      <h1 id="privacy-title">Privacy Policy</h1>
      <p class="legal-meta">Last updated: <time datetime="2025-01-01"><?php echo esc_html($updated); ?></time></p>

      <section id="priv-intro" aria-labelledby="priv-intro-h">
        <h2 id="priv-intro-h">1. Introduction</h2>
        <p>NextGen Tutors ("we", "us", "our") is committed to protecting your personal information in accordance with the Protection of Personal Information Act 4 of 2013 (POPIA). This Privacy Policy explains what personal data we collect, how we use it, and what rights you have.</p>
      </section>

      <section id="priv-who" aria-labelledby="priv-who-h">
        <h2 id="priv-who-h">2. Who We Are</h2>
        <p>NextGen Tutors is an online tutoring marketplace operating at nextgentutors.co.za, connecting students and tutors across South Africa. We are the responsible party (as defined in POPIA) for personal data collected through this Platform.</p>
      </section>

      <section id="priv-collect" aria-labelledby="priv-coll-h">
        <h2 id="priv-coll-h">3. What We Collect</h2>
        <p><strong>Account data:</strong> Name, email address, phone number, role (Student, Tutor, Parent), province.</p>
        <p><strong>Identity and verification data (Tutors only):</strong> SA ID number or passport number, qualification documents, profile photograph, criminal clearance certificate.</p>
        <p><strong>Transaction data:</strong> Session bookings, PayFast transaction references, payout history.</p>
        <p><strong>Usage data:</strong> Pages visited, search queries, session activity, login timestamps. Collected via cookies and server logs.</p>
        <p><strong>Communications:</strong> Support messages, ratings, and reviews submitted through the Platform.</p>
      </section>

      <section id="priv-use" aria-labelledby="priv-use-h">
        <h2 id="priv-use-h">4. How We Use Your Data</h2>
        <ul>
          <li>To create and manage your account.</li>
          <li>To verify tutor identities and qualifications.</li>
          <li>To process bookings and payments via PayFast.</li>
          <li>To send transactional emails (booking confirmations, receipts, payout notifications).</li>
          <li>To provide customer support and resolve disputes.</li>
          <li>To send marketing communications — only with your explicit consent, and only where you have opted in.</li>
          <li>To comply with legal obligations under South African law.</li>
        </ul>
        <p>We process your data on the following legal bases: performance of a contract (account and booking services), legitimate interests (fraud prevention, platform security), compliance with legal obligations, and consent (marketing).</p>
      </section>

      <section id="priv-share" aria-labelledby="priv-share-h">
        <h2 id="priv-share-h">5. Who We Share Data With</h2>
        <p><strong>PayFast:</strong> Payment processing. PayFast receives only the data necessary to process the transaction.</p>
        <p><strong>Email service provider:</strong> Used for transactional and marketing emails. Data is not used by them for their own purposes.</p>
        <p><strong>Background check provider:</strong> Tutor identity and criminal clearance verification only.</p>
        <p><strong>Law enforcement:</strong> Where required by applicable South African law or a court order.</p>
        <p>We do not sell your personal data to any third party.</p>
      </section>

      <section id="priv-minors" aria-labelledby="priv-min-h">
        <h2 id="priv-min-h">6. Children &amp; Minors</h2>
        <p>Users under 18 may use the Platform only with the verified consent of a parent or legal guardian. We take particular care with the data of minor users. Tutors working with minors undergo enhanced background checks. We do not use the personal data of minors for any purpose other than providing the tutoring service.</p>
        <p>If you believe a minor has created an account without appropriate guardian consent, contact us at <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a> and we will investigate promptly.</p>
      </section>

      <section id="priv-retention" aria-labelledby="priv-ret-h">
        <h2 id="priv-ret-h">7. Retention</h2>
        <p>We retain your personal data for as long as your account is active and for a period of 5 years after account closure, in line with South African tax and consumer protection law requirements.</p>
        <p>Tutor verification documents are retained for the duration of the tutoring relationship and for 3 years thereafter.</p>
        <p>You may request earlier deletion of your data, subject to any legal retention obligations (see Section 9).</p>
      </section>

      <section id="priv-security" aria-labelledby="priv-sec-h">
        <h2 id="priv-sec-h">8. Security</h2>
        <p>We implement industry-standard security measures including:</p>
        <ul>
          <li>TLS 1.3 encryption for all data in transit.</li>
          <li>Encrypted storage for sensitive personal data.</li>
          <li>Role-based access controls limiting staff access to personal data on a need-to-know basis.</li>
          <li>Regular security assessments of our Platform.</li>
        </ul>
        <p>No method of transmission over the internet is 100% secure. If we become aware of a data breach affecting your personal data, we will notify you and the Information Regulator as required by POPIA.</p>
      </section>

      <section id="priv-rights" aria-labelledby="priv-rights-h">
        <h2 id="priv-rights-h">9. Your Rights (POPIA)</h2>
        <p>Under POPIA, you have the right to:</p>
        <ul>
          <li><strong>Access:</strong> Request a copy of the personal data we hold about you.</li>
          <li><strong>Correction:</strong> Request correction of inaccurate personal data.</li>
          <li><strong>Deletion:</strong> Request deletion of your personal data (subject to legal retention requirements).</li>
          <li><strong>Objection:</strong> Object to processing of your personal data for direct marketing purposes.</li>
          <li><strong>Withdraw consent:</strong> Withdraw consent for marketing communications at any time.</li>
          <li><strong>Complain:</strong> Lodge a complaint with the Information Regulator of South Africa at <a href="https://inforegulator.org.za" target="_blank" rel="noopener noreferrer">inforegulator.org.za</a>.</li>
        </ul>
        <p>To exercise any of these rights, contact us at <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>.</p>
      </section>

      <section id="priv-cookies" aria-labelledby="priv-cook-h">
        <h2 id="priv-cook-h">10. Cookies</h2>
        <p>We use the following categories of cookies:</p>
        <ul>
          <li><strong>Strictly necessary:</strong> Session management, login state, CSRF protection. These cannot be disabled.</li>
          <li><strong>Functional:</strong> Remembering your preferences (e.g., subject filter, province). These can be disabled.</li>
          <li><strong>Analytics:</strong> Aggregate, anonymised usage data to improve the Platform. Only set with your consent.</li>
        </ul>
        <p>We do not use advertising cookies or third-party tracking cookies.</p>
      </section>

      <section id="priv-transfers" aria-labelledby="priv-trans-h">
        <h2 id="priv-trans-h">11. International Transfers</h2>
        <p>Our Platform is hosted in South Africa. Where we use third-party service providers that process data outside of South Africa, we ensure that appropriate safeguards are in place as required by POPIA.</p>
      </section>

      <section id="priv-changes" aria-labelledby="priv-chg-h">
        <h2 id="priv-chg-h">12. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Material changes will be communicated by email to registered users at least 14 days before they take effect. The current version is always available at this page.</p>
      </section>

      <section id="priv-contact" aria-labelledby="priv-con-h">
        <h2 id="priv-con-h">13. Contact</h2>
        <p>Information Officer: NextGen Tutors<br>
        Email: <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
        <p>You can also submit a request through our <a href="<?php echo esc_url(ngt_get_page_url('contact')); ?>">Contact page</a>.</p>
      </section>
    </div>

  </div>
</article>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
