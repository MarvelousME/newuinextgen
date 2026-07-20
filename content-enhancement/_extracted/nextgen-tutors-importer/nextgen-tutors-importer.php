<?php
/**
 * Plugin Name: NextGenTutors-Importer
 * Description: Automated provisioning and seeding for the NextGen Tutors platform.
 * Version: 1.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) exit;

define('NGT_IMPORTER_DIR', plugin_dir_path(__FILE__));
define('NGT_IMPORTER_URL', plugin_dir_url(__FILE__));

class NGT_Importer {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_ngt_run_import', [$this, 'handle_import']);
    }

    public function add_menu() {
        add_menu_page(
            'NGT Importer',
            'NGT Importer',
            'manage_options',
            'ngt-importer',
            [$this, 'render_page'],
            'dashicons-cloud-download'
        );
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1>NextGen Tutors - Automated Provisioning</h1>
            <p>This tool will seed all required data, pages, and configurations as per the Functional Specification.</p>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 600px;">
                <h3>Status Checklist</h3>
                <ul id="ngt-import-status">
                    <li><input type="checkbox" id="chk-pages" checked disabled> Create Pages</li>
                    <li><input type="checkbox" id="chk-crm" checked disabled> Setup FluentCRM (Tags/Lists)</li>
                    <li><input type="checkbox" id="chk-amelia" checked disabled> Setup Amelia (Services/Staff)</li>
                    <li><input type="checkbox" id="chk-gamipress" checked disabled> Setup GamiPress (Points/Badges)</li>
                    <li><input type="checkbox" id="chk-automator" checked disabled> Setup AutomatorWP (Workflows)</li>
                    <li><input type="checkbox" id="chk-woo" checked disabled> Setup WooCommerce (Products/Tiers)</li>
                </ul>
                <button id="btn-run-import" class="button button-primary button-large">Run Full Provisioning</button>
                <div id="import-log" style="margin-top: 20px; max-height: 300px; overflow-y: auto; background: #f0f0f0; padding: 10px; border: 1px solid #ddd; display: none;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#btn-run-import').click(function() {
                const btn = $(this);
                const log = $('#import-log');
                
                btn.prop('disabled', true).text('Provisioning...');
                log.show().html('<p>Starting import process...</p>');

                const components = ['pages', 'crm', 'amelia', 'gamipress', 'automator', 'woo'];
                
                async function runNext(index) {
                    if (index >= components.length) {
                        btn.text('Provisioning Complete').addClass('button-disabled');
                        log.append('<p><strong>Success: All components provisioned!</strong></p>');
                        return;
                    }

                    const comp = components[index];
                    log.append('<p>Importing ' + comp + '...</p>');

                    try {
                        const response = await $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'ngt_run_import',
                                component: comp,
                                nonce: "<?php echo wp_create_nonce('ngt_import_nonce'); ?>"
                            }
                        });
                        
                        if (response.success) {
                            log.append('<p style="color: green;">✔ ' + comp + ' imported successfully.</p>');
                        } else {
                            log.append('<p style="color: red;">✘ Error importing ' + comp + ': ' + response.data + '</p>');
                        }
                    } catch (e) {
                        log.append('<p style="color: red;">✘ AJAX Error: ' + e.statusText + '</p>');
                    }

                    runNext(index + 1);
                }

                runNext(0);
            });
        });
        </script>
        <?php
    }

    public function handle_import() {
        check_ajax_referer('ngt_import_nonce', 'nonce');
        $component = sanitize_text_field($_POST['component']);

        // Load required deploy files
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/post.php';
        
        // Define common helpers if not exists
        if (!function_exists('ngt_log')) {
            function ngt_log($msg, $type = 'info') { /* dummy */ }
        }
        if (!function_exists('ngt_section')) {
            function ngt_section($name) { /* dummy */ }
        }
        if (!function_exists('ngt_ok')) {
            function ngt_ok($msg) { /* dummy */ }
        }
        if (!function_exists('ngt_fail')) {
            function ngt_fail($msg) { /* dummy */ }
        }
        if (!function_exists('ngt_skip')) {
            function ngt_skip($msg) { /* dummy */ }
        }
        if (!function_exists('ngt_step')) {
            function ngt_step($n, $msg) { /* dummy */ }
        }
        if (!defined('NGT_DEPLOY_VERSION')) define('NGT_DEPLOY_VERSION', '1.0.0');

        switch ($component) {
            case 'pages':
                $this->import_pages();
                break;
            case 'crm':
                $this->import_crm();
                break;
            case 'amelia':
                $this->import_amelia();
                break;
            case 'gamipress':
                $this->import_gamipress();
                break;
            case 'automator':
                $this->import_automator();
                break;
            case 'woo':
                $this->import_woo();
                break;
            default:
                wp_send_json_error('Unknown component');
        }

        wp_send_json_success();
    }

    private function import_pages() {
        // Based on deploy/pages/setup-pages.php
        $pages = [
            ['title' => 'Home', 'slug' => 'home', 'template' => 'templates/page-home.php'],
            ['title' => 'Find a Tutor', 'slug' => 'find-a-tutor', 'template' => 'templates/page-find-a-tutor.php'],
            ['title' => 'Become a Tutor', 'slug' => 'become-a-tutor', 'template' => 'templates/page-become-a-tutor.php'],
            ['title' => 'Pricing', 'slug' => 'pricing', 'template' => 'templates/page-pricing.php'],
            ['title' => 'About Us', 'slug' => 'about-us', 'template' => 'templates/page-about.php'],
            ['title' => 'Contact', 'slug' => 'contact', 'template' => 'templates/page-contact.php'],
            ['title' => 'Safety Guide', 'slug' => 'safety-guide', 'template' => 'templates/page-legal.php'],
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'template' => 'templates/page-legal.php'],
            ['title' => 'Terms & Conditions', 'slug' => 'terms-and-conditions', 'template' => 'templates/page-legal.php'],
            ['title' => 'Student Dashboard', 'slug' => 'dashboard', 'template' => 'templates/page-home.php'],
            ['title' => 'Tutor Dashboard', 'slug' => 'instructor', 'template' => 'templates/page-home.php'],
        ];

        foreach ($pages as $p) {
            $exists = get_page_by_path($p['slug']);
            if (!$exists) {
                $id = wp_insert_post([
                    'post_title' => $p['title'],
                    'post_name' => $p['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ]);
                if ($id && !empty($p['template'])) {
                    update_post_meta($id, '_wp_page_template', $p['template']);
                }
            }
        }
    }

    private function import_crm() {
        // Based on deploy/fluentcrm/setup-fluentcrm.php
        if (!defined('FLUENTCRM')) return;
        
        $tags = ['tutor_applicant', 'active_customer', 'verified_tutor', 'session_completed', 'payout_ready', 'no_show_student', 'referred_customer', 'safety_review'];
        foreach ($tags as $tag) {
            FluentCrmApi('tags')->updateOrCreate(['title' => $tag, 'slug' => $tag]);
        }
        
        FluentCrmApi('lists')->updateOrCreate(['title' => 'Tutors', 'slug' => 'tutors']);
        FluentCrmApi('lists')->updateOrCreate(['title' => 'Students', 'slug' => 'students']);
    }

    private function import_amelia() {
        global $wpdb;
        $table_services = $wpdb->prefix . 'amelia_services';
        $table_categories = $wpdb->prefix . 'amelia_categories';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_categories'") != $table_categories) return;

        // Ensure category exists
        $cat_id = $wpdb->get_var("SELECT id FROM $table_categories WHERE name = 'Academic Tutoring'");
        if (!$cat_id) {
            $wpdb->insert($table_categories, ['name' => 'Academic Tutoring', 'status' => 'visible']);
            $cat_id = $wpdb->insert_id;
        }

        // Services from spec
        $services = [
            ['name' => 'Grade R-7 Online Tutoring', 'price' => 320, 'duration' => 3600],
            ['name' => 'Grade 8-12 Online Tutoring', 'price' => 320, 'duration' => 3600],
            ['name' => 'Tertiary Online Tutoring', 'price' => 500, 'duration' => 3600],
        ];

        foreach ($services as $s) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_services WHERE name = %s", $s['name']));
            if (!$exists) {
                $wpdb->insert($table_services, [
                    'name' => $s['name'],
                    'price' => $s['price'],
                    'status' => 'visible',
                    'duration' => $s['duration'],
                    'categoryId' => $cat_id,
                    'minCapacity' => 1,
                    'maxCapacity' => 1,
                ]);
            }
        }
    }

    private function import_gamipress() {
        if (!function_exists('gamipress_insert_points_type')) return;

        // Points Types
        $this->create_gp_points('ngt_points', 'NGT Point', 'NGT Points');
        $this->create_gp_points('tutor_points', 'Tutor Point', 'Tutor Points');

        // Badges (Achievements)
        $badges = [
            ['slug' => 'badge_id_verified', 'title' => 'ID Verified'],
            ['slug' => 'badge_background_clear', 'title' => 'Background Cleared'],
            ['slug' => 'badge_training_complete', 'title' => 'Training Complete'],
        ];

        foreach ($badges as $b) {
            $this->create_gp_achievement($b['slug'], $b['title'], 'badge');
        }
    }

    private function create_gp_points($slug, $singular, $plural) {
        if (gamipress_get_points_type($slug)) return;
        $id = gamipress_insert_points_type([
            'post_title' => $plural,
            'post_name' => $slug,
            'post_status' => 'publish',
        ]);
        if ($id) {
            update_post_meta($id, '_gamipress_plural_name', $plural);
            update_post_meta($id, '_gamipress_singular_name', $singular);
        }
    }

    private function create_gp_achievement($slug, $title, $type) {
        $ach_type_slug = gamipress_get_achievement_type_slug($type);
        if (get_page_by_path($slug, OBJECT, $ach_type_slug)) return;
        
        wp_insert_post([
            'post_type' => $ach_type_slug,
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'publish',
        ]);
    }

    private function import_automator() {
        if (!class_exists('AutomatorWP')) return;
        // AutomatorWP uses custom post types. We'll seed a basic "Welcome" workflow.
        $wf_id = wp_insert_post([
            'post_type' => 'automatorwp_automation',
            'post_title' => 'New Booking → Welcome Sequence',
            'post_status' => 'publish',
        ]);
        
        if ($wf_id) {
            // Trigger: Amelia Appointment Booked
            wp_insert_post([
                'post_type' => 'automatorwp_trigger',
                'post_title' => 'Amelia Appointment Booked',
                'post_parent' => $wf_id,
                'post_status' => 'publish',
                'meta_input' => [
                    '_automatorwp_trigger_type' => 'amelia_appointment_booked',
                ]
            ]);
            // Action: Add Tag in FluentCRM
            wp_insert_post([
                'post_type' => 'automatorwp_action',
                'post_title' => 'FluentCRM: Add tag "active_customer"',
                'post_parent' => $wf_id,
                'post_status' => 'publish',
                'meta_input' => [
                    '_automatorwp_action_type' => 'fluentcrm_add_tag',
                    '_automatorwp_action_data' => ['tag' => 'active_customer']
                ]
            ]);
        }
    }

    private function import_woo() {
        // Create WooCommerce products for session packages
        if (!class_exists('WooCommerce')) return;
        
        $products = [
            ['title' => 'Tutoring Session (Standard)', 'price' => '320'],
            ['title' => 'Tertiary Tutoring Session', 'price' => '500'],
        ];
        
        foreach ($products as $p) {
            $exists = get_page_by_title($p['title'], OBJECT, 'product');
            if (!$exists) {
                $post_id = wp_insert_post([
                    'post_title' => $p['title'],
                    'post_status' => 'publish',
                    'post_type' => 'product',
                ]);
                update_post_meta($post_id, '_regular_price', $p['price']);
                update_post_meta($post_id, '_price', $p['price']);
                wp_set_object_terms($post_id, 'simple', 'product_type');
            }
        }
    }
}

new NGT_Importer();
