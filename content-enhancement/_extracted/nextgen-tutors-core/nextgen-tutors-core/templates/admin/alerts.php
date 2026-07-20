<div class="wrap ngt-admin-wrap">
    <div class="ngt-brand-header" style="display: flex; align-items: center; margin-bottom: 20px; padding: 15px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-right: 20px;">
            <?php 
            if (function_exists('has_custom_logo') && has_custom_logo()) {
                $custom_logo_id = get_theme_mod('custom_logo');
                $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                if ($logo) {
                    echo '<img src="' . esc_url($logo[0]) . '" alt="NextGen Tutors" style="max-height: 50px;">';
                }
            } else {
                echo '<span style="background: #2563eb; color: #fff; padding: 10px 15px; border-radius: 5px; font-weight: bold; font-size: 20px; letter-spacing: 1px; box-shadow: 0 2px 4px rgba(37,99,235,0.3);">NEXTGEN TUTORS</span>';
            }
            ?>
        </div>
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">Alerts & Notifications</h1>
    </div>
    <div class="ngt-card">
        <h2>System Alerts</h2>
        <p>Manage system notifications and real-time alerts. Critical alerts are pushed directly to administrators.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Severity</th>
                    <th>Alert Message</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4">No active alerts found. System is running nominally.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

