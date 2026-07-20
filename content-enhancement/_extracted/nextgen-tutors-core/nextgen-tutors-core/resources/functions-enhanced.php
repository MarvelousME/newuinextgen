<?php
/**
 * NextGen Tutors - Core System Functions
 *
 * @package NextGenTutors
 * @version 2.0.0
 * @author Enterprise Development Team
 * @license Proprietary
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Suppress all PHP errors to prevent header issues
error_reporting(0);
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);

// Suppress specific WordPress/plugin notices
add_filter('doing_it_wrong_trigger_error', '__return_false', 999);
add_filter('deprecated_function_trigger_error', '__return_false', 999);
add_filter('deprecated_argument_trigger_error', '__return_false', 999);
add_filter('deprecated_file_trigger_error', '__return_false', 999);
add_filter('deprecated_hook_trigger_error', '__return_false', 999);

// Disable _load_textdomain_just_in_time notices
add_action('init', function() {
    error_reporting(0);
    @ini_set('display_errors', 0);
}, 1);

// =============================================================================
// CONSTANTS & CONFIGURATION
// =============================================================================

define('NGT_VERSION', '2.0.0');
define('NGT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NGT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Platform Configuration
class NGT_Config {
    const PLATFORM_FEE_PERCENT = 15;
    const PAYOUT_DAY = 1;
    const MIN_PAYOUT_AMOUNT = 100;
    const CURRENCY = 'ZAR';
    const LOW_RATING_THRESHOLD = 4.0;
    const SESSION_REMINDER_24H = true;
    const SESSION_REMINDER_1H = true;
    
    // Database Table Names
    const TABLE_EARNINGS = 'ngt_earnings';
    const TABLE_RATINGS = 'ngt_ratings';
    const TABLE_PAYOUTS = 'ngt_payouts';
    const TABLE_REFERRALS = 'ngt_referrals';
    const TABLE_LOGS = 'ngt_logs';
    const TABLE_ACHIEVEMENTS = 'ngt_achievements';
    const TABLE_USER_ACHIEVEMENTS = 'ngt_user_achievements';
    const TABLE_SESSIONS = 'ngt_sessions';
}

// Achievement Definitions
class NGT_Achievements {
    const POPULAR_TUTOR = [
        'id' => 'popular_tutor',
        'name' => 'Popular Tutor',
        'description' => 'Received 50+ positive reviews',
        'icon' => 'star',
        'requirement' => 50,
        'type' => 'reviews'
    ];
    
    const TOP_RATED = [
        'id' => 'top_rated',
        'name' => 'Top Rated',
        'description' => 'Maintained 4.8+ rating for 3 months',
        'icon' => 'trophy',
        'requirement' => 4.8,
        'type' => 'rating'
    ];
    
    const SESSION_MASTER = [
        'id' => 'session_master',
        'name' => 'Session Master',
        'description' => 'Completed 100+ sessions',
        'icon' => 'calendar-check',
        'requirement' => 100,
        'type' => 'sessions'
    ];
    
    const EARNING_PRO = [
        'id' => 'earning_pro',
        'name' => 'Earning Pro',
        'description' => 'Earned R50,000+ on platform',
        'icon' => 'money-bill-wave',
        'requirement' => 50000,
        'type' => 'earnings'
    ];
    
    const REFERRAL_CHAMPION = [
        'id' => 'referral_champion',
        'name' => 'Referral Champion',
        'description' => 'Referred 10+ new users',
        'icon' => 'users',
        'requirement' => 10,
        'type' => 'referrals'
    ];
    
    const EARLY_ADOPTER = [
        'id' => 'early_adopter',
        'name' => 'Early Adopter',
        'description' => 'Joined in platform launch year',
        'icon' => 'rocket',
        'requirement' => '2024',
        'type' => 'date'
    ];
    
    const CONSISTENT_TUTOR = [
        'id' => 'consistent_tutor',
        'name' => 'Consistent Tutor',
        'description' => 'No cancellations for 6 months',
        'icon' => 'clock',
        'requirement' => 180,
        'type' => 'days_no_cancel'
    ];
    
    const SUBJECT_EXPERT = [
        'id' => 'subject_expert',
        'name' => 'Subject Expert',
        'description' => 'Top rated in specific subject',
        'icon' => 'book',
        'requirement' => 1,
        'type' => 'subject_leader'
    ];
    
    const STUDENT_FAVORITE = [
        'id' => 'student_favorite',
        'name' => 'Student Favorite',
        'description' => '5+ repeat students',
        'icon' => 'heart',
        'requirement' => 5,
        'type' => 'repeat_students'
    ];
    
    const VERIFIED_PRO = [
        'id' => 'verified_pro',
        'name' => 'Verified Pro',
        'description' => 'SACE verified with background check',
        'icon' => 'badge-check',
        'requirement' => true,
        'type' => 'verification'
    ];
    
    public static function get_all() {
        return [
            self::POPULAR_TUTOR,
            self::TOP_RATED,
            self::SESSION_MASTER,
            self::EARNING_PRO,
            self::REFERRAL_CHAMPION,
            self::EARLY_ADOPTER,
            self::CONSISTENT_TUTOR,
            self::SUBJECT_EXPERT,
            self::STUDENT_FAVORITE,
            self::VERIFIED_PRO
        ];
    }
}

// =============================================================================
// DATABASE TABLE CREATION
// =============================================================================

/**
 * Create all custom database tables on theme activation
 */
function ngt_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix;
    
    // Earnings Table
    $sql_earnings = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_earnings (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        tutor_id bigint(20) unsigned NOT NULL,
        session_id bigint(20) unsigned NOT NULL,
        student_id bigint(20) unsigned NOT NULL,
        session_date datetime NOT NULL,
        gross_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        platform_fee decimal(10,2) NOT NULL DEFAULT 0.00,
        penalty_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        bonus_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        net_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        status enum('pending','paid','disputed') NOT NULL DEFAULT 'pending',
        payout_id bigint(20) unsigned DEFAULT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY tutor_id (tutor_id),
        KEY session_id (session_id),
        KEY status (status),
        KEY payout_id (payout_id),
        KEY session_date (session_date)
    ) $charset_collate;";
    
    // Ratings Table
    $sql_ratings = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_ratings (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id bigint(20) unsigned NOT NULL,
        tutor_id bigint(20) unsigned NOT NULL,
        student_id bigint(20) unsigned NOT NULL,
        parent_id bigint(20) unsigned DEFAULT NULL,
        rating tinyint unsigned NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text text,
        categories json,
        is_public tinyint(1) DEFAULT 1,
        admin_response text,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_session_rating (session_id),
        KEY tutor_id (tutor_id),
        KEY student_id (student_id),
        KEY rating (rating)
    ) $charset_collate;";
    
    // Payouts Table
    $sql_payouts = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_payouts (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        tutor_id bigint(20) unsigned NOT NULL,
        payout_period varchar(7) NOT NULL,
        gross_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        platform_fees decimal(10,2) NOT NULL DEFAULT 0.00,
        penalties decimal(10,2) NOT NULL DEFAULT 0.00,
        bonuses decimal(10,2) NOT NULL DEFAULT 0.00,
        net_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        bank_account_hash varchar(64),
        reference_number varchar(50),
        status enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
        processed_at datetime,
        failed_reason text,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_period_tutor (tutor_id, payout_period),
        KEY status (status),
        KEY reference_number (reference_number)
    ) $charset_collate;";
    
    // Referrals Table
    $sql_referrals = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_referrals (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        referrer_id bigint(20) unsigned NOT NULL,
        referred_id bigint(20) unsigned NOT NULL,
        referral_code varchar(20),
        status enum('pending','converted','expired') NOT NULL DEFAULT 'pending',
        converted_at datetime,
        reward_amount decimal(10,2) DEFAULT 0.00,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_referral (referrer_id, referred_id),
        KEY referrer_id (referrer_id),
        KEY status (status)
    ) $charset_collate;";
    
    // Audit Logs Table
    $sql_logs = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_logs (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned,
        action varchar(100) NOT NULL,
        object_type varchar(50),
        object_id bigint(20),
        details json,
        ip_address varchar(45),
        user_agent text,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY action (action),
        KEY object_type_object_id (object_type, object_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Achievements Table
    $sql_achievements = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_achievements (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        achievement_id varchar(50) NOT NULL,
        name varchar(100) NOT NULL,
        description text,
        icon varchar(50),
        requirement_type varchar(50),
        requirement_value varchar(100),
        points int DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY achievement_id (achievement_id)
    ) $charset_collate;";
    
    // User Achievements Table
    $sql_user_achievements = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_user_achievements (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        achievement_id varchar(50) NOT NULL,
        earned_at timestamp DEFAULT CURRENT_TIMESTAMP,
        metadata json,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user_achievement (user_id, achievement_id),
        KEY user_id (user_id),
        KEY achievement_id (achievement_id)
    ) $charset_collate;";
    
    // Sessions Table (extends Amelia bookings)
    $sql_sessions = "CREATE TABLE IF NOT EXISTS {$prefix}ngt_sessions (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        amelia_booking_id bigint(20),
        tutor_id bigint(20) unsigned NOT NULL,
        student_id bigint(20) unsigned NOT NULL,
        subject varchar(100),
        grade varchar(20),
        session_date datetime NOT NULL,
        duration int unsigned NOT NULL,
        hourly_rate decimal(8,2) NOT NULL,
        total_amount decimal(10,2) NOT NULL,
        status enum('scheduled','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
        meeting_link varchar(255),
        recording_url varchar(255),
        notes text,
        cancellation_reason text,
        cancelled_by bigint(20),
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY amelia_booking_id (amelia_booking_id),
        KEY tutor_id (tutor_id),
        KEY student_id (student_id),
        KEY status (status),
        KEY session_date (session_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_earnings);
    dbDelta($sql_ratings);
    dbDelta($sql_payouts);
    dbDelta($sql_referrals);
    dbDelta($sql_logs);
    dbDelta($sql_achievements);
    dbDelta($sql_user_achievements);
    dbDelta($sql_sessions);
    
    // Seed achievements
    ngt_seed_achievements();
    
    // Store version for future upgrades
    update_option('ngt_db_version', NGT_VERSION);
    
    ngt_log_action(null, 'system', 'tables_created', null, ['version' => NGT_VERSION]);
}
add_action('after_switch_theme', 'ngt_create_tables');

/**
 * Seed achievements data
 */
function ngt_seed_achievements() {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_achievements';
    
    $achievements = NGT_Achievements::get_all();
    
    foreach ($achievements as $achievement) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE achievement_id = %s",
            $achievement['id']
        ));
        
        if (!$exists) {
            $wpdb->insert($table, [
                'achievement_id' => $achievement['id'],
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'icon' => $achievement['icon'],
                'requirement_type' => $achievement['type'],
                'requirement_value' => is_array($achievement['requirement']) ? json_encode($achievement['requirement']) : $achievement['requirement'],
                'points' => 100
            ]);
        }
    }
}

// =============================================================================
// USER REGISTRATION & ROLE MANAGEMENT
// =============================================================================

/**
 * Add custom user roles
 */
function ngt_add_custom_roles() {
    // Parent role (extends subscriber)
    add_role('ngt_parent', 'Parent', [
        'read' => true,
        'ngt_book_sessions' => true,
        'ngt_view_dashboard' => true,
        'ngt_manage_children' => true
    ]);
    
    // Student role (extends subscriber)
    add_role('ngt_student', 'Student', [
        'read' => true,
        'ngt_view_dashboard' => true,
        'ngt_join_sessions' => true,
        'ngt_submit_reviews' => true
    ]);
    
    // Tutor role (custom capabilities)
    add_role('ngt_tutor', 'Tutor', [
        'read' => true,
        'ngt_tutor_dashboard' => true,
        'ngt_manage_sessions' => true,
        'ngt_view_earnings' => true,
        'ngt_set_availability' => true,
        'upload_files' => true
    ]);
    
    // Admin role (extends administrator with NGT capabilities)
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('ngt_manage_all');
        $admin->add_cap('ngt_process_payouts');
        $admin->add_cap('ngt_view_analytics');
        $admin->add_cap('ngt_verify_tutors');
    }
}
add_action('init', 'ngt_add_custom_roles');

/**
 * Custom registration handler with role assignment
 */
function ngt_handle_registration($user_id, $user_data) {
    $role = sanitize_text_field($user_data['user_role'] ?? 'ngt_parent');
    
    // Validate role
    $valid_roles = ['ngt_parent', 'ngt_student', 'ngt_tutor'];
    if (!in_array($role, $valid_roles)) {
        $role = 'ngt_parent';
    }
    
    $user = new WP_User($user_id);
    $user->set_role($role);
    
    // Store additional meta
    update_user_meta($user_id, 'ngt_registration_date', current_time('mysql'));
    update_user_meta($user_id, 'ngt_account_status', 'active');
    
    if ($role === 'ngt_tutor') {
        update_user_meta($user_id, 'ngt_verification_status', 'pending');
        update_user_meta($user_id, 'ngt_application_date', current_time('mysql'));
        
        // Trigger FluentCRM automation
        ngt_sync_tutor_to_fluentcrm($user_id, 'applicant');
    } else {
        // Sync to FluentCRM
        ngt_sync_parent_to_fluentcrm($user_id);
    }
    
    // Handle referral code
    if (!empty($user_data['referral_code'])) {
        ngt_process_referral_signup($user_id, sanitize_text_field($user_data['referral_code']));
    }
    
    // Generate referral code for new user
    ngt_generate_user_referral_code($user_id);
    
    ngt_log_action($user_id, 'user', 'registered', $user_id, ['role' => $role]);
}

// =============================================================================
// FLUENTCRM INTEGRATION
// =============================================================================

/**
 * Sync parent/student to FluentCRM
 */
function ngt_sync_parent_to_fluentcrm($user_id) {
    if (!function_exists('FluentCrmApi')) {
        return false;
    }
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $contact_api = FluentCrmApi('contacts');
    
    $contact_data = [
        'email' => $user->user_email,
        'first_name' => get_user_meta($user_id, 'first_name', true) ?: $user->display_name,
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'status' => 'subscribed',
        'lists' => [1], // Default list
        'tags' => ['Lead', 'Prospective Parent'],
        'custom_values' => [
            'source' => 'website',
            'user_id' => $user_id,
            'account_type' => ngt_get_user_role_label($user_id),
            'registration_date' => get_user_meta($user_id, 'ngt_registration_date', true)
        ]
    ];
    
    $contact = $contact_api->createOrUpdate($contact_data);
    
    if ($contact) {
        $contact->attachTagsByIds([1, 2]); // Lead, Prospective Parent
        $contact->attachListsByIds([1]); // Main list
        
        do_action('fluentcrm_contact_created', $contact);
        
        ngt_log_action($user_id, 'fluentcrm', 'contact_synced', $contact->id);
        return true;
    }
    
    return false;
}

/**
 * Sync tutor to FluentCRM
 */
function ngt_sync_tutor_to_fluentcrm($user_id, $status = 'verified') {
    if (!function_exists('FluentCrmApi')) {
        return false;
    }
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $contact_api = FluentCrmApi('contacts');
    
    $tags = [];
    $lists = [];
    
    if ($status === 'applicant') {
        $tags = ['Tutor Applicant'];
        $lists = [2]; // Applicants list
    } elseif ($status === 'verified') {
        $tags = ['Verified', 'Active Tutor'];
        $lists = [3]; // Verified Tutors list
    }
    
    $contact_data = [
        'email' => $user->user_email,
        'first_name' => get_user_meta($user_id, 'first_name', true) ?: $user->display_name,
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'status' => 'subscribed',
        'contact_type' => 'tutor',
        'custom_values' => [
            'tutor_type' => 'tutor',
            'user_id' => $user_id,
            'verification_status' => $status,
            'subjects' => get_user_meta($user_id, 'ngt_subjects', true),
            'hourly_rate' => get_user_meta($user_id, 'ngt_hourly_rate', true),
            'provinces' => get_user_meta($user_id, 'ngt_provinces', true)
        ]
    ];
    
    $contact = $contact_api->createOrUpdate($contact_data);
    
    if ($contact && !empty($tags)) {
        // Get tag IDs
        $tag_model = new \FluentCrm\App\Models\Tag();
        $tag_ids = $tag_model->whereIn('title', $tags)->pluck('id')->toArray();
        if (!empty($tag_ids)) {
            $contact->attachTagsByIds($tag_ids);
        }
        
        if (!empty($lists)) {
            $contact->attachListsByIds($lists);
        }
    }
    
    ngt_log_action($user_id, 'fluentcrm', 'tutor_synced', $contact->id ?? null, ['status' => $status]);
    return true;
}

/**
 * Update FluentCRM contact on booking
 */
function ngt_update_fluentcrm_on_booking($booking_id, $user_id) {
    if (!function_exists('FluentCrmApi')) return;
    
    $contact_api = FluentCrmApi('contacts');
    $user = get_userdata($user_id);
    
    if (!$user) return;
    
    $contact = $contact_api->getContact(['email' => $user->user_email]);
    
    if ($contact) {
        // Update status
        $contact->tags()->detach([1]); // Remove "Lead"
        
        $tag_model = new \FluentCrm\App\Models\Tag();
        $active_tag = $tag_model->where('title', 'Active Customer')->first();
        if ($active_tag) {
            $contact->attachTagsByIds([$active_tag->id]);
        }
        
        // Update custom fields
        $booking_count = ngt_get_user_booking_count($user_id);
        $contact->updateCustomValues([
            'first_booking_date' => current_time('mysql'),
            'bookings_count' => $booking_count
        ]);
    }
}

// =============================================================================
// EARNINGS CALCULATION ENGINE
// =============================================================================

/**
 * Calculate tutor earnings for a session
 */
function ngt_calculate_tutor_earnings($session_id, $tutor_id, $student_id, $session_data) {
    global $wpdb;
    
    $hourly_rate = floatval($session_data['hourly_rate'] ?? get_user_meta($tutor_id, 'ngt_hourly_rate', true));
    $duration = intval($session_data['duration'] ?? 60);
    $rating = floatval($session_data['rating'] ?? 0);
    
    // Calculate gross
    $duration_hours = $duration / 60;
    $gross_amount = $hourly_rate * $duration_hours;
    
    // Platform fee (15%)
    $platform_fee = $gross_amount * (NGT_Config::PLATFORM_FEE_PERCENT / 100);
    
    // Calculate penalties and bonuses
    $penalty_amount = 0;
    $bonus_amount = 0;
    
    // Quality penalties
    if ($rating > 0 && $rating < NGT_Config::LOW_RATING_THRESHOLD) {
        $penalty_amount = $gross_amount * 0.10; // 10% penalty for low rating
    }
    
    // No-show penalty
    if (($session_data['status'] ?? '') === 'no_show' && ($session_data['no_show_by'] ?? '') === 'tutor') {
        $penalty_amount += $gross_amount * 0.50; // 50% penalty for no-show
    }
    
    // Excellence bonus
    if ($rating >= 4.8) {
        $bonus_amount = $gross_amount * 0.05; // 5% bonus for excellent rating
    }
    
    // Consistency bonus (10+ sessions this month)
    $month_sessions = ngt_get_tutor_monthly_sessions($tutor_id, date('Y-m'));
    if ($month_sessions >= 10) {
        $bonus_amount += $gross_amount * 0.03; // 3% consistency bonus
    }
    
    $net_amount = $gross_amount - $platform_fee - $penalty_amount + $bonus_amount;
    
    // Store earnings record
    $earnings_data = [
        'tutor_id' => $tutor_id,
        'session_id' => $session_id,
        'student_id' => $student_id,
        'session_date' => $session_data['session_date'] ?? current_time('mysql'),
        'gross_amount' => $gross_amount,
        'platform_fee' => $platform_fee,
        'penalty_amount' => $penalty_amount,
        'bonus_amount' => $bonus_amount,
        'net_amount' => $net_amount,
        'status' => 'pending'
    ];
    
    $table = $wpdb->prefix . 'ngt_earnings';
    $wpdb->insert($table, $earnings_data);
    $earnings_id = $wpdb->insert_id;
    
    // Update tutor balance
    $current_balance = floatval(get_user_meta($tutor_id, 'ngt_current_balance', true));
    update_user_meta($tutor_id, 'ngt_current_balance', $current_balance + $net_amount);
    
    // Update lifetime earnings
    $lifetime_earnings = floatval(get_user_meta($tutor_id, 'ngt_lifetime_earnings', true));
    update_user_meta($tutor_id, 'ngt_lifetime_earnings', $lifetime_earnings + $net_amount);
    
    ngt_log_action($tutor_id, 'earnings', 'calculated', $earnings_id, $earnings_data);
    
    return $earnings_id;
}

/**
 * Get tutor's monthly sessions count
 */
function ngt_get_tutor_monthly_sessions($tutor_id, $month) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_earnings';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table 
        WHERE tutor_id = %d 
        AND DATE_FORMAT(session_date, '%Y-%m') = %s",
        $tutor_id, $month
    ));
    
    return intval($count);
}

/**
 * Get user booking count
 */
function ngt_get_user_booking_count($user_id) {
    global $wpdb;
    // This would integrate with Amelia or WooCommerce
    // For now, return from meta
    return intval(get_user_meta($user_id, 'ngt_booking_count', true));
}

// =============================================================================
// PAYOUT SYSTEM
// =============================================================================

/**
 * Process monthly payouts for all tutors
 */
function ngt_process_monthly_payouts($month = null) {
    global $wpdb;
    
    if (!$month) {
        $month = date('Y-m', strtotime('last month'));
    }
    
    $tutors = get_users(['role' => 'ngt_tutor']);
    $processed = [];
    
    foreach ($tutors as $tutor) {
        $tutor_id = $tutor->ID;
        
        // Skip if tutor not verified
        if (get_user_meta($tutor_id, 'ngt_verification_status', true) !== 'verified') {
            continue;
        }
        
        $payout_data = ngt_calculate_monthly_payout($tutor_id, $month);
        
        if ($payout_data['net_amount'] >= NGT_Config::MIN_PAYOUT_AMOUNT) {
            $result = ngt_create_payout_record($tutor_id, $payout_data);
            if ($result) {
                $processed[] = [
                    'tutor_id' => $tutor_id,
                    'amount' => $payout_data['net_amount'],
                    'reference' => $result
                ];
                
                // Update earnings to paid status
                ngt_mark_earnings_paid($tutor_id, $month, $result);
                
                // Send payout notification
                ngt_send_payout_notification($tutor_id, $payout_data);
            }
        }
    }
    
    ngt_log_action(null, 'payout', 'batch_processed', null, [
        'month' => $month,
        'count' => count($processed),
        'total' => array_sum(array_column($processed, 'amount'))
    ]);
    
    return $processed;
}

/**
 * Calculate monthly payout for a tutor
 */
function ngt_calculate_monthly_payout($tutor_id, $month) {
    global $wpdb;
    $earnings_table = $wpdb->prefix . 'ngt_earnings';
    
    $results = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(gross_amount) as gross,
            SUM(platform_fee) as fees,
            SUM(penalty_amount) as penalties,
            SUM(bonus_amount) as bonuses,
            SUM(net_amount) as net
        FROM $earnings_table 
        WHERE tutor_id = %d 
        AND DATE_FORMAT(session_date, '%Y-%m') = %s
        AND status = 'pending'",
        $tutor_id, $month
    ));
    
    return [
        'tutor_id' => $tutor_id,
        'payout_period' => $month,
        'gross_amount' => floatval($results->gross ?? 0),
        'platform_fees' => floatval($results->fees ?? 0),
        'penalties' => floatval($results->penalties ?? 0),
        'bonuses' => floatval($results->bonuses ?? 0),
        'net_amount' => floatval($results->net ?? 0),
        'tax_amount' => 0 // Tax calculation would go here
    ];
}

/**
 * Create payout record
 */
function ngt_create_payout_record($tutor_id, $payout_data) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_payouts';
    
    $reference = 'NGT-' . $payout_data['payout_period'] . '-' . str_pad($tutor_id, 5, '0', STR_PAD_LEFT);
    
    $bank_account = get_user_meta($tutor_id, 'ngt_bank_account', true);
    
    $insert_data = [
        'tutor_id' => $tutor_id,
        'payout_period' => $payout_data['payout_period'],
        'gross_amount' => $payout_data['gross_amount'],
        'platform_fees' => $payout_data['platform_fees'],
        'penalties' => $payout_data['penalties'],
        'bonuses' => $payout_data['bonuses'],
        'net_amount' => $payout_data['net_amount'],
        'tax_amount' => $payout_data['tax_amount'],
        'bank_account_hash' => $bank_account ? hash('sha256', $bank_account) : null,
        'reference_number' => $reference,
        'status' => 'pending'
    ];
    
    $wpdb->insert($table, $insert_data);
    $payout_id = $wpdb->insert_id;
    
    ngt_log_action($tutor_id, 'payout', 'created', $payout_id, $payout_data);
    
    return $reference;
}

/**
 * Mark earnings as paid
 */
function ngt_mark_earnings_paid($tutor_id, $month, $payout_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_earnings';
    
    $wpdb->query($wpdb->prepare(
        "UPDATE $table 
        SET status = 'paid', payout_id = %d 
        WHERE tutor_id = %d 
        AND DATE_FORMAT(session_date, '%Y-%m') = %s
        AND status = 'pending'",
        $payout_id, $tutor_id, $month
    ));
}

/**
 * Send payout notification
 */
function ngt_send_payout_notification($tutor_id, $payout_data) {
    $tutor = get_userdata($tutor_id);
    if (!$tutor) return;
    
    $subject = 'Your Monthly Payout - ' . $payout_data['payout_period'];
    
    $message = sprintf(
        "Hi %s,<br><br>
        Your monthly payout has been processed.<br><br>
        <strong>Payout Summary for %s:</strong><br>
        Gross Earnings: R%.2f<br>
        Platform Fees (15%%): -R%.2f<br>
        Penalties: -R%.2f<br>
        Bonuses: +R%.2f<br>
        <strong>Net Payout: R%.2f</strong><br><br>
        The funds will be transferred to your registered bank account on the 1st of the month.<br><br>
        Reference: NGT-%s-%s<br><br>
        Best regards,<br>
        NextGen Tutors Team",
        $tutor->display_name,
        $payout_data['payout_period'],
        $payout_data['gross_amount'],
        $payout_data['platform_fees'],
        $payout_data['penalties'],
        $payout_data['bonuses'],
        $payout_data['net_amount'],
        $payout_data['payout_period'],
        str_pad($tutor_id, 5, '0', STR_PAD_LEFT)
    );
    
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($tutor->user_email, $subject, $message, $headers);
    
    // Also update FluentCRM
    if (function_exists('FluentCrmApi')) {
        $contact_api = FluentCrmApi('contacts');
        $contact = $contact_api->getContact(['email' => $tutor->user_email]);
        if ($contact) {
            $contact->updateCustomValues([
                'last_payout_amount' => $payout_data['net_amount'],
                'last_payout_date' => current_time('mysql')
            ]);
        }
    }
}

// =============================================================================
// RATING SYSTEM
// =============================================================================

/**
 * Submit a rating for a session
 */
function ngt_submit_rating($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_ratings';
    
    // Validate required fields
    if (empty($data['session_id']) || empty($data['tutor_id']) || empty($data['student_id'])) {
        return new WP_Error('missing_data', 'Required fields are missing');
    }
    
    // Check if already rated
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE session_id = %d",
        $data['session_id']
    ));
    
    if ($existing) {
        return new WP_Error('already_rated', 'This session has already been rated');
    }
    
    $rating_data = [
        'session_id' => intval($data['session_id']),
        'tutor_id' => intval($data['tutor_id']),
        'student_id' => intval($data['student_id']),
        'parent_id' => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
        'rating' => intval($data['rating']),
        'review_text' => sanitize_textarea_field($data['review_text'] ?? ''),
        'categories' => !empty($data['categories']) ? json_encode($data['categories']) : null,
        'is_public' => isset($data['is_public']) ? intval($data['is_public']) : 1
    ];
    
    $wpdb->insert($table, $rating_data);
    $rating_id = $wpdb->insert_id;
    
    if ($rating_id) {
        // Update tutor's average rating
        ngt_update_tutor_average_rating($data['tutor_id']);
        
        // Update session with rating
        $session_table = $wpdb->prefix . 'ngt_sessions';
        $wpdb->update($session_table, 
            ['rating' => $data['rating']], 
            ['id' => $data['session_id']]
        );
        
        // Check for achievement
        ngt_check_rating_achievements($data['tutor_id']);
        
        // Send notification to tutor
        ngt_send_rating_notification($data['tutor_id'], $data);
        
        ngt_log_action($data['student_id'], 'rating', 'submitted', $rating_id, $rating_data);
        
        return $rating_id;
    }
    
    return false;
}

/**
 * Update tutor's average rating
 */
function ngt_update_tutor_average_rating($tutor_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_ratings';
    
    $avg = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM $table WHERE tutor_id = %d AND is_public = 1",
        $tutor_id
    ));
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE tutor_id = %d AND is_public = 1",
        $tutor_id
    ));
    
    update_user_meta($tutor_id, 'ngt_average_rating', round($avg, 2));
    update_user_meta($tutor_id, 'ngt_total_reviews', intval($count));
    
    // Update FluentCRM
    if (function_exists('FluentCrmApi')) {
        $tutor = get_userdata($tutor_id);
        if ($tutor) {
            $contact_api = FluentCrmApi('contacts');
            $contact = $contact_api->getContact(['email' => $tutor->user_email]);
            if ($contact) {
                $contact->updateCustomValues([
                    'average_rating' => round($avg, 2),
                    'total_reviews' => intval($count)
                ]);
            }
        }
    }
}

/**
 * Send rating notification to tutor
 */
function ngt_send_rating_notification($tutor_id, $data) {
    $tutor = get_userdata($tutor_id);
    if (!$tutor) return;
    
    $student = get_userdata($data['student_id']);
    $student_name = $student ? $student->display_name : 'A student';
    
    $subject = 'New Rating Received';
    $message = sprintf(
        "Hi %s,<br><br>
        You received a new %d-star rating from %s.<br><br>
        <strong>Review:</strong><br>
        %s<br><br>
        Your current average rating: %.2f stars<br><br>
        Best regards,<br>
        NextGen Tutors Team",
        $tutor->display_name,
        $data['rating'],
        $student_name,
        !empty($data['review_text']) ? nl2br(esc_html($data['review_text'])) : 'No written review provided.',
        get_user_meta($tutor_id, 'ngt_average_rating', true)
    );
    
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($tutor->user_email, $subject, $message, $headers);
}

// =============================================================================
// ACHIEVEMENT SYSTEM
// =============================================================================

/**
 * Award achievement to user
 */
function ngt_award_achievement($user_id, $achievement_id, $metadata = []) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_user_achievements';
    
    // Check if already awarded
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND achievement_id = %s",
        $user_id, $achievement_id
    ));
    
    if ($exists) {
        return false;
    }
    
    $insert_data = [
        'user_id' => $user_id,
        'achievement_id' => $achievement_id,
        'metadata' => !empty($metadata) ? json_encode($metadata) : null
    ];
    
    $wpdb->insert($table, $insert_data);
    $award_id = $wpdb->insert_id;
    
    if ($award_id) {
        // Update user's achievement count
        $count = intval(get_user_meta($user_id, 'ngt_achievement_count', true));
        update_user_meta($user_id, 'ngt_achievement_count', $count + 1);
        
        // Send notification
        ngt_send_achievement_notification($user_id, $achievement_id);
        
        ngt_log_action($user_id, 'achievement', 'awarded', $award_id, ['achievement_id' => $achievement_id]);
        
        return true;
    }
    
    return false;
}

/**
 * Check and award rating-based achievements
 */
function ngt_check_rating_achievements($tutor_id) {
    $total_reviews = intval(get_user_meta($tutor_id, 'ngt_total_reviews', true));
    $average_rating = floatval(get_user_meta($tutor_id, 'ngt_average_rating', true));
    
    // Popular Tutor - 50+ reviews
    if ($total_reviews >= 50) {
        ngt_award_achievement($tutor_id, 'popular_tutor', ['reviews' => $total_reviews]);
    }
    
    // Top Rated - 4.8+ rating
    if ($average_rating >= 4.8) {
        ngt_award_achievement($tutor_id, 'top_rated', ['rating' => $average_rating]);
    }
}

/**
 * Check and award session-based achievements
 */
function ngt_check_session_achievements($tutor_id) {
    global $wpdb;
    $earnings_table = $wpdb->prefix . 'ngt_earnings';
    
    // Session Master - 100+ sessions
    $total_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $earnings_table WHERE tutor_id = %d",
        $tutor_id
    ));
    
    if ($total_sessions >= 100) {
        ngt_award_achievement($tutor_id, 'session_master', ['sessions' => $total_sessions]);
    }
}

/**
 * Check and award earnings-based achievements
 */
function ngt_check_earnings_achievements($tutor_id) {
    $lifetime_earnings = floatval(get_user_meta($tutor_id, 'ngt_lifetime_earnings', true));
    
    // Earning Pro - R50,000+ earned
    if ($lifetime_earnings >= 50000) {
        ngt_award_achievement($tutor_id, 'earning_pro', ['earnings' => $lifetime_earnings]);
    }
}

/**
 * Send achievement notification
 */
function ngt_send_achievement_notification($user_id, $achievement_id) {
    global $wpdb;
    
    $achievement = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ngt_achievements WHERE achievement_id = %s",
        $achievement_id
    ));
    
    if (!$achievement) return;
    
    $user = get_userdata($user_id);
    if (!$user) return;
    
    $subject = '🏆 Achievement Unlocked: ' . $achievement->name;
    
    $message = sprintf(
        "Congratulations %s!<br><br>
        You have unlocked the <strong>%s</strong> achievement!<br><br>
        %s<br><br>
        <strong>You earned %d points!</strong><br><br>
        View all your achievements in your dashboard.<br><br>
        Best regards,<br>
        NextGen Tutors Team",
        $user->display_name,
        $achievement->name,
        $achievement->description,
        $achievement->points
    );
    
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($user->user_email, $subject, $message, $headers);
}

/**
 * Get user's achievements
 */
function ngt_get_user_achievements($user_id) {
    global $wpdb;
    
    $sql = $wpdb->prepare(
        "SELECT a.*, ua.earned_at, ua.metadata 
        FROM {$wpdb->prefix}ngt_user_achievements ua
        JOIN {$wpdb->prefix}ngt_achievements a ON ua.achievement_id = a.achievement_id
        WHERE ua.user_id = %d
        ORDER BY ua.earned_at DESC",
        $user_id
    );
    
    return $wpdb->get_results($sql);
}

// =============================================================================
// REFERRAL SYSTEM
// =============================================================================

/**
 * Generate referral code for user
 */
function ngt_generate_user_referral_code($user_id) {
    $existing = get_user_meta($user_id, 'ngt_referral_code', true);
    if ($existing) return $existing;
    
    $code = 'NGT' . strtoupper(substr(md5($user_id . time()), 0, 6));
    update_user_meta($user_id, 'ngt_referral_code', $code);
    
    return $code;
}

/**
 * Process referral signup
 */
function ngt_process_referral_signup($referred_id, $code) {
    global $wpdb;
    
    // Find referrer by code
    $referrer = get_users([
        'meta_key' => 'ngt_referral_code',
        'meta_value' => $code,
        'number' => 1
    ]);
    
    if (empty($referrer)) return false;
    
    $referrer_id = $referrer[0]->ID;
    
    // Don't allow self-referral
    if ($referrer_id == $referred_id) return false;
    
    $table = $wpdb->prefix . 'ngt_referrals';
    
    $wpdb->insert($table, [
        'referrer_id' => $referrer_id,
        'referred_id' => $referred_id,
        'referral_code' => $code,
        'status' => 'converted',
        'converted_at' => current_time('mysql'),
        'reward_amount' => 100.00 // R100 referral bonus
    ]);
    
    // Update referrer's referral count
    $count = intval(get_user_meta($referrer_id, 'ngt_referral_count', true));
    update_user_meta($referrer_id, 'ngt_referral_count', $count + 1);
    
    // Check for Referral Champion achievement
    if ($count + 1 >= 10) {
        ngt_award_achievement($referrer_id, 'referral_champion', ['referrals' => $count + 1]);
    }
    
    ngt_log_action($referred_id, 'referral', 'converted', $wpdb->insert_id, [
        'referrer_id' => $referrer_id,
        'code' => $code
    ]);
    
    return true;
}

/**
 * Get user's referral stats
 */
function ngt_get_user_referral_stats($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ngt_referrals';
    
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE referrer_id = %d",
        $user_id
    ));
    
    $converted = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE referrer_id = %d AND status = 'converted'",
        $user_id
    ));
    
    $total_earnings = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(reward_amount) FROM $table WHERE referrer_id = %d AND status = 'converted'",
        $user_id
    ));
    
    return [
        'total_referrals' => intval($total),
        'converted' => intval($converted),
        'total_earnings' => floatval($total_earnings ?? 0),
        'referral_code' => get_user_meta($user_id, 'ngt_referral_code', true),
        'referral_link' => home_url('/register/?ref=' . get_user_meta($user_id, 'ngt_referral_code', true))
    ];
}

// =============================================================================
// AUDIT LOGGING
// =============================================================================

/**
 * Log an action
 */
function ngt_log_action($user_id, $object_type, $action, $object_id = null, $details = []) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ngt_logs';
    
    $log_data = [
        'user_id' => $user_id,
        'action' => $action,
        'object_type' => $object_type,
        'object_id' => $object_id,
        'details' => !empty($details) ? json_encode($details) : null,
        'ip_address' => ngt_get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    $wpdb->insert($table, $log_data);
    
    return $wpdb->insert_id;
}

/**
 * Get client IP address
 */
function ngt_get_client_ip() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    
    return '0.0.0.0';
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Get user role label
 */
function ngt_get_user_role_label($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return 'Unknown';
    
    $roles = [
        'ngt_parent' => 'Parent',
        'ngt_student' => 'Student',
        'ngt_tutor' => 'Tutor',
        'administrator' => 'Admin'
    ];
    
    foreach ($user->roles as $role) {
        if (isset($roles[$role])) {
            return $roles[$role];
        }
    }
    
    return 'User';
}

/**
 * Format currency
 */
function ngt_format_currency($amount) {
    return 'R' . number_format($amount, 2);
}

/**
 * Get tutor stats
 */
function ngt_get_tutor_stats($tutor_id) {
    return [
        'total_earnings' => floatval(get_user_meta($tutor_id, 'ngt_lifetime_earnings', true)),
        'current_balance' => floatval(get_user_meta($tutor_id, 'ngt_current_balance', true)),
        'average_rating' => floatval(get_user_meta($tutor_id, 'ngt_average_rating', true)),
        'total_reviews' => intval(get_user_meta($tutor_id, 'ngt_total_reviews', true)),
        'total_sessions' => ngt_get_tutor_total_sessions($tutor_id),
        'achievement_count' => intval(get_user_meta($tutor_id, 'ngt_achievement_count', true))
    ];
}

/**
 * Get tutor total sessions
 */
function ngt_get_tutor_total_sessions($tutor_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_earnings';
    
    return intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE tutor_id = %d",
        $tutor_id
    )));
}

/**
 * Get student stats
 */
function ngt_get_student_stats($student_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_sessions';
    
    $total_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE student_id = %d AND status = 'completed'",
        $student_id
    ));
    
    $total_spent = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_amount) FROM $table WHERE student_id = %d AND status = 'completed'",
        $student_id
    ));
    
    return [
        'total_sessions' => intval($total_sessions),
        'total_spent' => floatval($total_spent ?? 0),
        'achievement_count' => intval(get_user_meta($student_id, 'ngt_achievement_count', true)),
        'referral_stats' => ngt_get_user_referral_stats($student_id)
    ];
}

// =============================================================================
// CRON JOBS
// =============================================================================

/**
 * Schedule cron jobs on activation
 */
function ngt_schedule_cron_jobs() {
    if (!wp_next_scheduled('ngt_monthly_payout')) {
        wp_schedule_event(strtotime('first day of next month 02:00:00'), 'monthly', 'ngt_monthly_payout');
    }
    
    if (!wp_next_scheduled('ngt_daily_maintenance')) {
        wp_schedule_event(time(), 'daily', 'ngt_daily_maintenance');
    }
    
    if (!wp_next_scheduled('ngt_session_reminders')) {
        wp_schedule_event(time(), 'hourly', 'ngt_session_reminders');
    }
}
add_action('init', 'ngt_schedule_cron_jobs');

/**
 * Monthly payout cron
 */
add_action('ngt_monthly_payout', function() {
    $month = date('Y-m', strtotime('last month'));
    ngt_process_monthly_payouts($month);
});

/**
 * Daily maintenance cron
 */
add_action('ngt_daily_maintenance', function() {
    // Archive old logs
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->prefix}ngt_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    
    // Update tutor stats
    $tutors = get_users(['role' => 'ngt_tutor']);
    foreach ($tutors as $tutor) {
        ngt_update_tutor_average_rating($tutor->ID);
    }
    
    ngt_log_action(null, 'system', 'daily_maintenance', null, ['date' => current_time('mysql')]);
});

/**
 * Session reminders cron
 */
add_action('ngt_session_reminders', function() {
    // Send 24-hour and 1-hour reminders
    // Implementation would integrate with SMS gateway
    ngt_log_action(null, 'system', 'reminders_sent', null, ['timestamp' => time()]);
});

// =============================================================================
// ACTIVATION / DEACTIVATION
// =============================================================================

/**
 * Theme activation hook
 */
function ngt_theme_activation() {
    ngt_create_tables();
    ngt_add_custom_roles();
    ngt_schedule_cron_jobs();
    
    // Create necessary pages
    ngt_create_required_pages();
    
    flush_rewrite_rules();
}

/**
 * Create required pages
 */
function ngt_create_required_pages() {
    $pages = [
        'student-dashboard' => [
            'title' => 'Student Dashboard',
            'template' => 'templates/student-dashboard.php'
        ],
        'tutor-dashboard' => [
            'title' => 'Tutor Dashboard',
            'template' => 'templates/tutor-dashboard.php'
        ],
        'find-tutor' => [
            'title' => 'Find a Tutor',
            'template' => 'templates/find-tutor.php'
        ]
    ];
    
    foreach ($pages as $slug => $page_data) {
        $existing = get_page_by_path($slug);
        if (!$existing) {
            wp_insert_post([
                'post_title' => $page_data['title'],
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page'
            ]);
        }
    }
}

/**
 * Add custom cron schedules
 */
function ngt_cron_schedules($schedules) {
    $schedules['monthly'] = [
        'interval' => 2592000, // 30 days
        'display' => __('Once Monthly')
    ];
    return $schedules;
}
add_filter('cron_schedules', 'ngt_cron_schedules');

// =============================================================================
// AJAX HANDLERS
// =============================================================================

/**
 * AJAX: Submit rating
 */
function ngt_ajax_submit_rating() {
    check_ajax_referer('ngt_rating_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $data = [
        'session_id' => intval($_POST['session_id'] ?? 0),
        'tutor_id' => intval($_POST['tutor_id'] ?? 0),
        'student_id' => intval($_POST['student_id'] ?? get_current_user_id()),
        'rating' => intval($_POST['rating'] ?? 0),
        'review_text' => sanitize_textarea_field($_POST['review_text'] ?? ''),
        'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : []
    ];
    
    $result = ngt_submit_rating($data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(['rating_id' => $result]);
}
add_action('wp_ajax_ngt_submit_rating', 'ngt_ajax_submit_rating');

/**
 * AJAX: Get tutor earnings
 */
function ngt_ajax_get_tutor_earnings() {
    check_ajax_referer('ngt_earnings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    if (!in_array('ngt_tutor', $user->roles)) {
        wp_send_json_error('Not a tutor');
    }
    
    $month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
    
    global $wpdb;
    $earnings_table = $wpdb->prefix . 'ngt_earnings';
    
    $earnings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $earnings_table 
        WHERE tutor_id = %d 
        AND DATE_FORMAT(session_date, '%Y-%m') = %s
        ORDER BY session_date DESC",
        $user_id, $month
    ));
    
    $stats = ngt_get_tutor_stats($user_id);
    
    wp_send_json_success([
        'earnings' => $earnings,
        'stats' => $stats,
        'month' => $month
    ]);
}
add_action('wp_ajax_ngt_get_tutor_earnings', 'ngt_ajax_get_tutor_earnings');

/**
 * AJAX: Get user achievements
 */
function ngt_ajax_get_user_achievements() {
    check_ajax_referer('ngt_achievements_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $user_id = get_current_user_id();
    $achievements = ngt_get_user_achievements($user_id);
    
    wp_send_json_success([
        'achievements' => $achievements,
        'count' => count($achievements)
    ]);
}
add_action('wp_ajax_ngt_get_user_achievements', 'ngt_ajax_get_user_achievements');

/**
 * AJAX: Get referral stats
 */
function ngt_ajax_get_referral_stats() {
    check_ajax_referner('ngt_referral_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $user_id = get_current_user_id();
    $stats = ngt_get_user_referral_stats($user_id);
    
    wp_send_json_success($stats);
}
add_action('wp_ajax_ngt_get_referral_stats', 'ngt_ajax_get_referral_stats');

// =============================================================================
// ENQUEUE ASSETS
// =============================================================================

/**
 * Enqueue frontend scripts
 */
function ngt_enqueue_assets() {
    // Main styles
    wp_enqueue_style('ngt-style', get_template_directory_uri() . '/assets/css/ngt-style.css', [], NGT_VERSION);
    
    // Chart.js for dashboards
    if (is_page(['student-dashboard', 'tutor-dashboard'])) {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
    }
    
    // Main JS
    wp_enqueue_script('ngt-main', get_template_directory_uri() . '/assets/js/ngt-main.js', ['jquery'], NGT_VERSION, true);
    
    // Localize script
    wp_localize_script('ngt-main', 'ngt_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ngt_ajax_nonce'),
        'user_id' => get_current_user_id(),
        'user_role' => ngt_get_user_role_label(get_current_user_id()),
        'currency' => NGT_Config::CURRENCY
    ]);
}
add_action('wp_enqueue_scripts', 'ngt_enqueue_assets');

/**
 * Enqueue admin assets
 */
function ngt_enqueue_admin_assets($hook) {
    if (strpos($hook, 'ngt-') === false) return;
    
    wp_enqueue_style('ngt-admin-style', get_template_directory_uri() . '/assets/css/ngt-admin.css', [], NGT_VERSION);
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
    wp_enqueue_script('ngt-admin', get_template_directory_uri() . '/assets/js/ngt-admin.js', ['jquery', 'chartjs'], NGT_VERSION, true);
}
add_action('admin_enqueue_scripts', 'ngt_enqueue_admin_assets');

// =============================================================================
// INITIALIZATION
// =============================================================================

// Activation hook
add_action('after_switch_theme', 'ngt_theme_activation');

// Log that functions loaded
ngt_log_action(null, 'system', 'functions_loaded', null, ['version' => NGT_VERSION, 'time' => current_time('mysql')]);

// =============================================================================
// INCLUDE ADDITIONAL FILES
// =============================================================================

// Admin Dashboard
require_once get_template_directory() . '/admin-dashboard.php';

// REST API
require_once get_template_directory() . '/ngt-rest-api.php';
