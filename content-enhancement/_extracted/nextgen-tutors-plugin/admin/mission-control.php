<?php
/**
 * Mission Control admin page – visual dashboard and settings.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Register the menu only for users with the custom capability.
add_action('admin_menu', function () {
    add_menu_page(
        'NextGen Tutors',               // page title
        'NextGen Tutors',               // menu title
        'ngt_manage',                  // capability required
        'ngt-mission-control',         // menu slug
        'ngt_render_mission_control',  // callback
        'dashicons-admin-generic',     // icon (you can replace with custom logo later)
        80                              // position
    );
});

/** Render the Mission Control dashboard */
function ngt_render_mission_control() {
    // Enqueue assets.
    wp_register_style('ngt-admin-css', NGT_PLUGIN_URL . 'admin/admin.css', [], '1.0');
    wp_enqueue_style('ngt-admin-css');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true);
    wp_register_script('ngt-admin-js', NGT_PLUGIN_URL . 'admin/admin.js', ['jquery', 'chart-js'], '1.0', true);
    wp_enqueue_script('ngt-admin-js');

    // Pass some data to JS.
    wp_localize_script('ngt-admin-js', 'NGT_DATA', [
        'apiBase'   => esc_url_raw(rest_url('ngt/v1')),
        'nonce'     => wp_create_nonce('wp_rest'),
        'logoUrl'   => NGT_PLUGIN_URL . 'assets/nextgentutors_logo.png',
        'pluginUrl' => NGT_PLUGIN_URL,
        'primaryColor' => '#1abc9c',
        'darkColor'    => '#2c3e50',
        'alertEmails'  => get_option('ngt_alert_emails', ''),
    ]);

    // Simple HTML skeleton – the heavy lifting is done by admin.js.
    ?>
    <div class="ngt-dashboard">
        <header class="ngt-header">
            <img src="<?php echo esc_url(NGT_PLUGIN_URL . 'assets/nextgentutors_logo.png'); ?>" alt="NextGen Tutors" class="ngt-logo" />
            <h1>NextGen Tutors – Mission Control</h1>
            <button id="run-payout-batch" class="btn-primary">Run Payout Batch</button>
        </header>
        <section id="ngt-tabs" class="ngt-tabs">
            <button class="tab-btn active" data-tab="overview">Overview</button>
            <button class="tab-btn" data-tab="analytics">Analytics</button>
            <button class="tab-btn" data-tab="triggers">Triggers</button>
            <button class="tab-btn" data-tab="workflows">Workflows</button>
            <button class="tab-btn" data-tab="integrations">Integrations</button>
            <button class="tab-btn" data-tab="settings">Settings</button>
        </section>
        <section id="ngt-content" class="ngt-content">
            <!-- Content injected by admin.js -->
        </section>
    </div>
    <?php
}
?>
