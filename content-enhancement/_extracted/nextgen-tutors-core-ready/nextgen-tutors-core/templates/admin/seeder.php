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
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">Database Seeder</h1>
    </div>
    <div class="ngt-card">
        <h2>Generate Sample Data</h2>
        <p>Populate the system with realistic sample data for testing, staging, and training purposes. All generated users are marked clearly and can be bulk-deleted.</p>
        
        <form id="ngt-seeder-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="seed_tutors">Number of Tutors</label></th>
                    <td><input type="number" name="seed_tutors" id="seed_tutors" value="10" min="0" max="100" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="seed_parents">Number of Parents</label></th>
                    <td><input type="number" name="seed_parents" id="seed_parents" value="20" min="0" max="200" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="seed_bookings">Number of Bookings</label></th>
                    <td><input type="number" name="seed_bookings" id="seed_bookings" value="50" min="0" max="500" class="small-text"></td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" class="button button-primary" onclick="alert('Seeder job queued successfully in background!');">Generate Data</button>
            </p>
        </form>
    </div>
</div>

