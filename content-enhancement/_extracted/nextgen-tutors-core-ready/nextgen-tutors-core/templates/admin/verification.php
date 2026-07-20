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
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">System Verification</h1>
    </div>
    <div class="ngt-card">
        <h2>Real-Time Health Status</h2>
        <p>System Health Score: <strong><?php echo esc_html($health['health_score'] ?? '100'); ?>%</strong></p>
        <p>Current Status: <strong><?php echo esc_html(strtoupper($health['status'] ?? 'HEALTHY')); ?></strong></p>
        <button class="button button-primary" onclick="location.reload();">Run System Check Now</button>
    </div>

    <div class="ngt-card">
        <h2>Verification Details</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Component Check</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($health['checks'])): ?>
                    <?php foreach ($health['checks'] as $name => $data): ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($name)); ?></strong></td>
                            <td style="color: <?php echo $data['status'] === 'pass' ? 'green' : 'red'; ?>;">
                                <?php echo esc_html(strtoupper($data['status'])); ?>
                            </td>
                            <td><?php echo esc_html($data['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No checks have been run yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

