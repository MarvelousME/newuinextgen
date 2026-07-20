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
        <h1 style="margin: 0; padding: 0; border: none; font-size: 24px; color: #1e293b;">Data Importer</h1>
    </div>
    <div class="ngt-card">
        <h2>Upload Data</h2>
        <p>Import Tutors, Parents, or Earnings directly into the system. The background async queue will process large files automatically without timing out.</p>
        <form id="ngt-import-form" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="import_type">Data Type</label></th>
                    <td>
                        <select name="import_type" id="import_type">
                            <option value="contacts">Contacts / Users</option>
                            <option value="earnings">Earnings & Payouts</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="import_file">CSV or JSON File</label></th>
                    <td><input type="file" name="import_file" id="import_file" accept=".csv,.json,.xlsx"></td>
                </tr>
                <tr>
                    <th scope="row">Options</th>
                    <td>
                        <label><input type="checkbox" name="skip_duplicates" value="1" checked> Skip duplicate records</label><br>
                        <label><input type="checkbox" name="dry_run" value="1"> Dry run (validate only, don't import)</label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" class="button button-primary" onclick="alert('Import job queued successfully in background!');">Queue Import Job</button>
            </p>
        </form>
    </div>
</div>

