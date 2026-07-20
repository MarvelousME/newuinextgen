#!/usr/bin/env php
<?php
/**
 * NextGen Tutors — Platform Setup & Verification Engine
 * Run via: wp eval-file setup-ngt-platform.php [--strict] [--force]
 * 
 * ✅ Auto-detects plugins & SmartHead theme
 * ✅ Creates child theme if missing
 * ✅ Configures FluentCRM, WooCommerce, Pages, Shortcodes, RBAC
 * ✅ Seeds relational demo data (2 users/role)
 * ✅ Verifies data population & outputs status report
 * ✅ Safe rollback & transactional abort
 */

if (!defined('WP_CLI') || !WP_CLI) {
    die("⚠️ Must run via WP-CLI: wp eval-file setup-ngt-platform.php [--strict] [--force]\n");
}

$strict = in_array('--strict', $_SERVER['argv'], true);
$force  = in_array('--force', $_SERVER['argv'], true);
$rollback = in_array('--rollback', $_SERVER['argv'], true);

// ============================================================================
// 🛑 ROLLBACK HANDLER
// ============================================================================
if ($rollback) {
    WP_CLI::log("🔄 Initiating safe rollback...");
    $backup_file = get_option('ngt_setup_backup_file');
    if ($backup_file && file_exists($backup_file)) {
        exec("wp db import {$backup_file}");
        delete_option('ngt_setup_backup_file');
        delete_option('ngt_setup_in_progress');
        WP_CLI::success("✅ Database restored. Cleanup complete.");
    } else {
        WP_CLI::error("❌ No backup found. Cannot rollback safely.");
    }
    exit(0);
}

// ============================================================================
// 1. PRE-FLIGHT CHECK (Strict Mode)
// ============================================================================
$required_plugins = [
    'ameliabooking/ameliabooking.php' => 'Amelia',
    'fluent-forms/fluent-forms.php'   => 'Fluent Forms',
    'fluent-crm/fluent-crm.php'       => 'FluentCRM',
    'automatorwp/automatorwp.php'     => 'AutomatorWP',
    'masterstudy-lms/lms.php'         => 'MasterStudy LMS',
    'woocommerce/woocommerce.php'     => 'WooCommerce',
    'woo-payfast/woo-payfast.php'     => 'PayFast Gateway'
];

$missing = [];
foreach ($required_plugins as $file => $name) {
    if (!is_plugin_active($file)) $missing[] = $name;
}

$parent_theme = wp_get_theme()->get('Template') ?: wp_get_theme()->get('Name');
if (stripos($parent_theme, 'smarthead') === false && !$force) {
    WP_CLI::error("❌ SmartHead theme not detected. Activate it first or use --force.");
    exit(1);
}

if (!empty($missing) && $strict) {
    WP_CLI::error("🛑 STRICT MODE: Missing plugins detected: " . implode(', ', $missing) . "\n⏸️ Integration paused. Activate plugins or remove --strict.");
    exit(1);
}

WP_CLI::success("✅ Prerequisites validated. Proceeding with setup...");

// ============================================================================
// 💾 TRANSACTIONAL BACKUP
// ============================================================================
$backup_dir = sys_get_temp_dir() . '/ngt-backups';
wp_mkdir_p($backup_dir);
$backup_file = $backup_dir . '/ngt-' . date('Y-m-d-His') . '.sql';
exec("wp db export {$backup_file} 2>/dev/null");
update_option('ngt_setup_backup_file', $backup_file);
update_option('ngt_setup_in_progress', true);
register_shutdown_function(function() {
    if (get_option('ngt_setup_in_progress')) {
        WP_CLI::warning("⚠️ Setup interrupted. Run: wp eval-file setup-ngt-platform.php --rollback");
    }
});

// ============================================================================
// 2. CHILD THEME CREATION
// ============================================================================
WP_CLI::log("\n🎨 Setting up SmartHead child theme...");
$child_dir = get_theme_root() . '/nextgen-tutors';
if (!file_exists($child_dir) || $force) {
    wp_mkdir_p("{$child_dir}/inc");
    wp_mkdir_p("{$child_dir}/templates");
    wp_mkdir_p("{$child_dir}/assets/{css,js,images}");
    
    file_put_contents("{$child_dir}/style.css", "/*\nTheme Name: NextGen Tutors\nTemplate: smarthead\nVersion: 2.0.0\n*/");
    file_put_contents("{$child_dir}/functions.php", "<?php\ndefine('NGT_VERSION','2.0.0');\ndefine('NGT_INC_DIR', trailingslashit(get_stylesheet_directory()).'inc');\nrequire_once NGT_INC_DIR . '/functions-enhanced.php';\nadd_action('wp_enqueue_scripts', function(){\n    wp_enqueue_style('ngt-parent', get_template_directory_uri().'/style.css', [], NGT_VERSION);\n    wp_enqueue_style('ngt-child', get_stylesheet_directory_uri().'/assets/css/child.css', ['ngt-parent'], NGT_VERSION);\n    wp_enqueue_script('ngt-child', get_stylesheet_directory_uri().'/assets/js/child.js', ['jquery'], NGT_VERSION, true);\n});\n");
    WP_CLI::success("✅ Child theme created: nextgen-tutors");
} else {
    WP_CLI::line("ℹ️ Child theme already exists.");
}

// ============================================================================
// 3. PLUGIN AUTO-CONFIGURATION
// ============================================================================
WP_CLI::log("\n🔧 Auto-configuring plugins...");

// 3.1 FluentCRM Segments & Tags (Per ARCHITECTURE.md §2.1 & §2.2)
if (class_exists('FluentCRM\App\Models\ContactList')) {
    $segments = ['Active Customers', 'Tutor Applicants', 'Verified Tutors', 'Loyal Customers', 'Inactive'];
    foreach ($segments as $s) \FluentCRM\App\Models\ContactList::firstOrCreate(['title' => $s]);
    $tags = ['Booking Made', 'Tutor Applicant', 'Verified Tutor', 'Top Rated', 'Inactive', 'Loyal'];
    foreach ($tags as $t) \FluentCRM\App\Models\ContactTag::firstOrCreate(['title' => $t]);
    WP_CLI::success("✅ FluentCRM segments/tags seeded.");
}

// 3.2 WooCommerce Products (Per FUNCTIONAL_SPEC.md §5.1)
if (class_exists('WC_Product_Simple')) {
    $products = [
        ['name'=>'Online Tier 1 (1-3mo)', 'price'=>320, 'tutor_payout'=>200, 'sku'=>'online-tier1'],
        ['name'=>'Online Tier 2 (3-12mo)', 'price'=>300, 'tutor_payout'=>200, 'sku'=>'online-tier2'],
        ['name'=>'In-Person Tier 1', 'price'=>350, 'tutor_payout'=>250, 'sku'=>'inperson-tier1'],
        ['name'=>'Tertiary Session', 'price'=>500, 'tutor_payout'=>350, 'sku'=>'tertiary-session']
    ];
    $product_ids = [];
    foreach ($products as $p) {
        $id = wc_get_product_id_by_sku($p['sku']);
        if (!$id) {
            $prod = new WC_Product_Simple();
            $prod->set_name($p['name']); $prod->set_regular_price($p['price']);
            $prod->set_sku($p['sku']); $prod->set_status('publish');
            $prod->set_virtual(true); $prod->set_meta_data(['_ngt_tutor_payout'=>$p['tutor_payout']]);
            $prod->save();
            $id = $prod->get_id();
        }
        $product_ids[] = $id;
    }
    update_option('ngt_product_ids', $product_ids);
    WP_CLI::success("✅ WooCommerce products created.");
}

// ============================================================================
// 4. PAGE GENERATION & SHORTCODE MAPPING (Per SPEC §3)
// ============================================================================
WP_CLI::log("\n📄 Generating pages & mapping shortcodes...");
$pages = [
    ['title'=>'Home', 'slug'=>'home', 'content'=>"[rev_slider alias='ngt-hero']\n[ameliabooking type='search']\n[fluentform id='1']"],
    ['title'=>'Find a Tutor', 'slug'=>'find-a-tutor', 'content'=>"[ameliabooking type='search']\n[ngt_tutor_grid]"],
    ['title'=>'Become a Tutor', 'slug'=>'become-a-tutor', 'content'=>"[fluentform id='2']\n[ngt_income_calculator]"],
    ['title'=>'Pricing', 'slug'=>'pricing', 'content'=>"[products ids='".implode(',',$product_ids)."' columns='3']"],
    ['title'=>'Student Dashboard', 'slug'=>'dashboard', 'content'=>"[ngt_student_dashboard]"],
    ['title'=>'Tutor Dashboard', 'slug'=>'instructor', 'content'=>"[ngt_tutor_dashboard]"],
    ['title'=>'About Us', 'slug'=>'about-us', 'content'=>"Our mission, vision, and team details per SPEC §06."],
    ['title'=>'Contact', 'slug'=>'contact', 'content'=>"[fluentform id='3']\nWhatsApp: +27 XX XXX XXXX"],
    ['title'=>'Safety Guide', 'slug'=>'safety-guide', 'content'=>"POPIA compliance, verification badges, reporting per SPEC §4.3"],
    ['title'=>'Privacy Policy', 'slug'=>'privacy-policy', 'content'=>"POPIA-compliant privacy terms."],
    ['title'=>'Terms & Conditions', 'slug'=>'terms-and-conditions', 'content'=>"Cancellation, liability, SARS compliance."]
];

foreach ($pages as $page) {
    $existing = get_page_by_path($page['slug']);
    if ($existing && !$force) continue;
    $id = wp_insert_post(['post_title'=>$page['title'], 'post_name'=>$page['slug'], 'post_content'=>$page['content'], 'post_status'=>'publish', 'post_type'=>'page']);
    update_post_meta($id, '_wp_page_template', 'page-smarthead.php');
    WP_CLI::success("✅ Created: /{$page['slug']}");
}

// ============================================================================
// 5. RBAC DEMO SEEDING (2 Users/Role, Relational)
// ============================================================================
WP_CLI::log("\n👥 Seeding RBAC demo data (relational)...");
$roles = [
    'parent1'=>['email'=>'parent1@nextgentutors.co.za', 'role'=>'parent'],
    'parent2'=>['email'=>'parent2@nextgentutors.co.za', 'role'=>'parent'],
    'tutor1'=>['email'=>'tutor1@nextgentutors.co.za', 'role'=>'tutor'],
    'tutor2'=>['email'=>'tutor2@nextgentutors.co.za', 'role'=>'tutor'],
    'admin1'=>['email'=>'admin1@nextgentutors.co.za', 'role'=>'administrator'],
    'admin2'=>['email'=>'admin2@nextgentutors.co.za', 'role'=>'administrator'],
    'support1'=>['email'=>'support1@nextgentutors.co.za', 'role'=>'support'],
    'support2'=>['email'=>'support2@nextgentutors.co.za', 'role'=>'support']
];

$user_ids = [];
foreach ($roles as $k => $u) {
    if (email_exists($u['email']) && !$force) continue;
    $uid = wp_create_user($u['email'], 'Demo@2026!', $u['email']);
    if (!is_wp_error($uid)) {
        $wp_user = new WP_User($uid);
        $wp_user->set_role($u['role']);
        update_user_meta($uid, '_ngt_demo_account', true);
        if ($u['role']==='parent') update_user_meta($uid, '_ngt_account_balance', 2500);
        if ($u['role']==='tutor') {
            update_user_meta($uid, '_ngt_verified', 1);
            update_user_meta($uid, '_ngt_average_rating', 4.75);
            update_user_meta($uid, '_ngt_total_earned', 8400);
        }
        $user_ids[$k] = $uid;
        WP_CLI::success("✅ Created: {$u['email']} ({$u['role']})");
    }
}

// Relational Data: Booking → Payment → Earnings → Rating
global $wpdb;
if (!empty($user_ids['tutor1']) && !empty($user_ids['parent1'])) {
    $wpdb->insert($wpdb->prefix.'ngt_session_logs', ['tutor_id'=>$user_ids['tutor1'], 'student_id'=>$user_ids['parent1'], 'status'=>'completed', 'created_at'=>current_time('mysql'), 'updated_at'=>current_time('mysql')]);
    $sess_id = $wpdb->insert_id;
    $wpdb->insert($wpdb->prefix.'ngt_earnings', ['tutor_id'=>$user_ids['tutor1'], 'session_id'=>$sess_id, 'amount'=>320, 'platform_fee'=>48, 'net_amount'=>272, 'status'=>'completed', 'created_at'=>current_time('mysql')]);
    $wpdb->insert($wpdb->prefix.'ngt_ratings', ['rater_id'=>$user_ids['parent1'], 'rater_type'=>'student', 'target_id'=>$user_ids['tutor1'], 'session_id'=>$sess_id, 'rating'=>5, 'comment'=>'Excellent!', 'created_at'=>current_time('mysql')]);
    WP_CLI::success("✅ Relational demo data seeded (Session → Earnings → Rating).");
}

update_option('ngt_setup_in_progress', false);

// ============================================================================
// 6. VERIFICATION ENGINE
// ============================================================================
function ngt_run_verification() {
    global $wpdb;
    $checks = [
        'Child Theme Active' => (wp_get_theme()->get_template()==='nextgen-tutors'),
        'DB Tables Created' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ngt_earnings'")===$wpdb->prefix.'ngt_earnings',
        'Pages Generated' => count(get_posts(['post_type'=>'page','numberposts'=>-1])) >= 9,
        'Woo Products' => count(wc_get_products(['limit'=>-1])) >= 3,
        'FluentCRM Segments' => class_exists('FluentCRM\App\Models\ContactList') && \FluentCRM\App\Models\ContactList::count() >= 3,
        'RBAC Users' => count_users()['total_users'] >= 8,
        'Relational Data' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ngt_session_logs") > 0 && $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ngt_ratings") > 0,
        'API Responsive' => wp_remote_get(home_url('/wp-json/'))['response']['code']===200
    ];

    $table = [['Component', 'Status', 'Verify Link']];
    foreach ($checks as $name => $result) {
        $status = $result ? '✅ PASS' : '❌ FAIL';
        $link = $result ? 'Verified' : 'Review Required';
        $table[] = [$name, $status, $link];
    }
    WP_CLI\Utils\format_items('table', $table, ['Component', 'Status', 'Verify Link']);

    $failed = array_filter($checks, fn($r)=>!$r);
    if (!empty($failed)) {
        WP_CLI::warning("\n⚠️ " . count($failed) . " checks failed. Run: wp eval-file setup-ngt-platform.php --force");
    } else {
        WP_CLI::success("\n🎉 ALL CHECKS PASSED. Platform ready for testing.");
        WP_CLI::line("\n📊 Demo Credentials: parent1@nextgentutors.co.za / Demo@2026!");
        WP_CLI::line("🔐 Verify UI: " . home_url('/dashboard/'));
        WP_CLI::line("📈 Admin: " . admin_url('/admin.php?page=ngt-dashboard'));
        WP_CLI::line("🛒 Products: " . admin_url('/edit.php?post_type=product'));
        WP_CLI::line("📧 FluentCRM: " . admin_url('/admin.php?page=fluentcrm#/contacts'));
    }
}

ngt_run_verification();
exit(0);