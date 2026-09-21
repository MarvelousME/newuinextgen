<?php
/** Default — WP theme setup (pages-to-review/wordpress-setup.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Setup & Demo Importer', 'beyondinfinity' ),
    __( 'Required plugins, launch page sync, companion activation and database tables for NextGen Tutors on WordPress.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate bi-center">
      <p class="bi-eyebrow"><?php esc_html_e( 'Compatible with all major builders', 'beyondinfinity' ); ?></p>
      <h2><?php esc_html_e( 'Page Builder Compatibility', 'beyondinfinity' ); ?></h2>
    </div>
    <?php
    bi_compat_grid( [
        [ '🎨', __( 'Elementor', 'beyondinfinity' ), __( 'Fully compatible', 'beyondinfinity' ) ],
        [ '🏗️', __( 'WPBakery', 'beyondinfinity' ), __( 'Fully compatible', 'beyondinfinity' ) ],
        [ '⚡', __( 'Gutenberg', 'beyondinfinity' ), __( 'Fully compatible', 'beyondinfinity' ) ],
        [ '📝', __( 'Classic Editor', 'beyondinfinity' ), __( 'Fully compatible', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Required Plugins', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Install before going live. The companion plugin provides shortcodes and REST; optional plugins extend booking and CRM.', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    bi_plugin_grid( [
        [ '🧩', __( 'NextGen Companion', 'beyondinfinity' ), __( 'Data layer', 'beyondinfinity' ), __( 'Registers all ngc_* shortcodes, CPTs, custom tables and REST API.', 'beyondinfinity' ), 'required' ],
        [ '📅', __( 'Amelia Booking', 'beyondinfinity' ), __( 'Scheduling', 'beyondinfinity' ), __( 'Session scheduling and tutor calendars when integrated.', 'beyondinfinity' ), 'optional' ],
        [ '🛒', __( 'WooCommerce', 'beyondinfinity' ), __( 'Payments', 'beyondinfinity' ), __( 'Checkout, orders and PayFast gateway for ZAR lesson payments.', 'beyondinfinity' ), 'optional' ],
        [ '📋', __( 'Fluent Forms', 'beyondinfinity' ), __( 'Forms', 'beyondinfinity' ), __( 'Can replace theme form fallbacks for advanced workflows.', 'beyondinfinity' ), 'optional' ],
        [ '📊', __( 'FluentCRM', 'beyondinfinity' ), __( 'CRM', 'beyondinfinity' ), __( 'Parent, student and tutor email automation.', 'beyondinfinity' ), 'optional' ],
        [ '🏆', __( 'GamiPress', 'beyondinfinity' ), __( 'Badges', 'beyondinfinity' ), __( 'Student and tutor achievement badges on dashboards.', 'beyondinfinity' ), 'optional' ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Launch Checklist', 'beyondinfinity' ); ?></h2></div>
    <div class="bi-grid-2">
      <div>
        <?php
        bi_vsteps( [
            [ 'title' => __( 'Install WordPress 6.0+', 'beyondinfinity' ), 'text' => __( 'PHP 8.0+ recommended on your SA hosting provider.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Upload & activate theme', 'beyondinfinity' ), 'text' => __( 'Upload beyondinfinity via Appearance → Themes → Add New.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Activate nextgencompanion', 'beyondinfinity' ), 'text' => __( 'Creates database tables and registers all ngc_* shortcodes.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Sync Launch Pages', 'beyondinfinity' ), 'text' => __( 'Appearance → Sync Launch Pages — creates all 23 pages from page-map.json.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Verify shortcodes', 'beyondinfinity' ), 'text' => __( 'On the sync screen, confirm all 11 ngc_* shortcodes show as registered.', 'beyondinfinity' ) ],
            [ 'title' => __( 'Configure PayFast', 'beyondinfinity' ), 'text' => __( 'When WooCommerce is active: Settings → Payments → PayFast. Use sandbox until launch.', 'beyondinfinity' ) ],
        ] );
        ?>
      </div>
      <div class="ngt-card ngt-animate" style="padding:28px">
        <h3 style="margin-bottom:16px"><?php esc_html_e( 'Quick actions', 'beyondinfinity' ); ?></h3>
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
          <p><a href="<?php echo esc_url( admin_url( 'themes.php?page=bi-sync-pages' ) ); ?>" class="ngt-btn ngt-btn--primary ngt-btn--block"><?php esc_html_e( 'Open Sync Launch Pages', 'beyondinfinity' ); ?></a></p>
          <p style="margin-top:12px"><a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--block"><?php esc_html_e( 'Manage Plugins', 'beyondinfinity' ); ?></a></p>
        <?php endif; ?>
        <div class="bi-setup-progress" style="margin-top:24px">
          <?php $progress = bi_setup_progress_percent(); ?>
          <p class="bi-setup-progress__label"><?php esc_html_e( 'Theme defaults ready', 'beyondinfinity' ); ?></p>
          <div class="bi-setup-progress__bar"><span style="width:<?php echo esc_attr( (string) $progress ); ?>%"></span></div>
          <p style="font-size:.8rem;color:var(--ngt-text-3);margin-top:8px"><?php echo esc_html( (string) $progress ); ?>% <?php esc_html_e( 'of launch pages have default content files on disk.', 'beyondinfinity' ); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Custom Database Tables', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_db_table_grid( [
        [ 'ngt_earnings', __( 'Tutor earnings per completed session.', 'beyondinfinity' ) ],
        [ 'ngt_ratings', __( 'Session reviews from students.', 'beyondinfinity' ) ],
        [ 'ngt_payouts', __( 'Monthly EFT payout records.', 'beyondinfinity' ) ],
        [ 'ngt_referrals', __( 'Referral credits and conversions.', 'beyondinfinity' ) ],
        [ 'ngt_session_logs', __( 'Attendance, notes and session metadata.', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-center">
    <div class="ngt-card ngt-animate" style="padding:40px;max-width:720px;background:linear-gradient(135deg,var(--ngt-primary),var(--ngt-secondary));color:#fff">
      <h2 style="color:#fff"><?php esc_html_e( 'Need setup help?', 'beyondinfinity' ); ?></h2>
      <p style="opacity:.9"><?php esc_html_e( 'We offer white-glove theme installation and WordPress migration support.', 'beyondinfinity' ); ?></p>
      <div class="bi-hero__actions" style="justify-content:center;margin-top:20px">
        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="ngt-btn ngt-btn--white"><?php esc_html_e( 'Get Setup Help', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/admin-dashboard' ) ); ?>" class="ngt-btn ngt-btn--outline" style="border-color:#fff;color:#fff"><?php esc_html_e( 'Admin Dashboard', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</section>
