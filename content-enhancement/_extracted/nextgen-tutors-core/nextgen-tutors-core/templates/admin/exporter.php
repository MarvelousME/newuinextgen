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
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">Data Exporter</h1>
    </div>
    <div class="ngt-card">
        <h2>Export System Data</h2>
        <p>Generate secure, temporary download links for system data using background processing.</p>
        <form id="ngt-export-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="export_type">Data Type</label></th>
                    <td>
                        <select name="export_type" id="export_type">
                            <?php foreach ($exports as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                            <option value="contacts">All Users (Tutors/Parents)</option>
                            <option value="earnings">Earnings & Payouts History</option>
                            <option value="logs">System Audit Logs</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="export_format">Format</label></th>
                    <td>
                        <select name="export_format" id="export_format">
                            <option value="csv">CSV (Spreadsheet)</option>
                            <option value="json">JSON (API format)</option>
                            <option value="pdf">PDF Report</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" class="button button-primary" onclick="alert('Export generated! You will receive a secure download link shortly.');">Generate Export</button>
            </p>
        </form>
    </div>
</div>

