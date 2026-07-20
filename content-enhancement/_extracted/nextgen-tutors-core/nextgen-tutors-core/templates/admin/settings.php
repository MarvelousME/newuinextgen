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
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">Settings & Integrations</h1>
    </div>
    
    <div class="ngt-card">
        <h2>General Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ngt_site_name">Site Name</label></th>
                <td><input name="ngt_site_name" type="text" id="ngt_site_name" value="<?php echo esc_attr(get_option('ngt_site_name', 'NextGen Tutors')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row">Debug Mode</th>
                <td>
                    <label><input type="checkbox" name="ngt_debug_mode" value="1" <?php checked(get_option('ngt_debug_mode'), 1); ?>> Enable Debug Logging</label>
                </td>
            </tr>
        </table>
    </div>

    <div class="ngt-card">
        <h2>Integrations & API Keys</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ngt_payfast_merchant_id">PayFast Merchant ID</label></th>
                <td><input name="ngt_payfast_merchant_id" type="text" id="ngt_payfast_merchant_id" value="<?php echo esc_attr(get_option('ngt_payfast_merchant_id')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="ngt_payfast_merchant_key">PayFast Merchant Key</label></th>
                <td><input name="ngt_payfast_merchant_key" type="password" id="ngt_payfast_merchant_key" value="<?php echo esc_attr(get_option('ngt_payfast_merchant_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="ngt_zoom_api_key">Zoom API Key</label></th>
                <td><input name="ngt_zoom_api_key" type="text" id="ngt_zoom_api_key" value="<?php echo esc_attr(get_option('ngt_zoom_api_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="ngt_zoom_api_secret">Zoom API Secret</label></th>
                <td><input name="ngt_zoom_api_secret" type="password" id="ngt_zoom_api_secret" value="<?php echo esc_attr(get_option('ngt_zoom_api_secret')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="ngt_aws_s3_bucket">AWS S3 Bucket Name</label></th>
                <td><input name="ngt_aws_s3_bucket" type="text" id="ngt_aws_s3_bucket" value="<?php echo esc_attr(get_option('ngt_aws_s3_bucket')); ?>" class="regular-text"></td>
            </tr>
        </table>
    </div>
    
    <div class="ngt-card">
        <h2>System Workflows & Triggers</h2>
        <p>Your background task queues and system webhooks are fully orchestrated via the API.</p>
        <p><strong>Available Webhook Endpoints:</strong></p>
        <code><?php echo rest_url('ngt/v1/webhook/incoming'); ?></code>
        <br><br>
        <a href="?page=ngt-workflows" class="button button-secondary">Manage Advanced Workflows</a>
    </div>

    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
</div>

