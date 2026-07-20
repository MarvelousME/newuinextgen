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
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">System Logs</h1>
    </div>
    <div class="ngt-card">
        <h2>Audit & Debug Logs</h2>
        <p>Review background processes, API requests, and debug data. Logs older than 30 days are automatically purged.</p>
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="log_level">
                    <option value="all">All Levels</option>
                    <option value="error">Errors</option>
                    <option value="warning">Warnings</option>
                    <option value="info">Info</option>
                </select>
                <button class="button">Filter</button>
            </div>
            <div class="alignright actions">
                <button class="button button-secondary" onclick="alert('Exporting logs as CSV...');">Export as CSV</button>
            </div>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Level</th>
                    <th>Context</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4">Loading logs...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

