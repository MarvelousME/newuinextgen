<?php
/**
 * NextGen Tutors - Comprehensive Data Seeder
 * Generates relationally mapped data for Tutors, Students, Courses, Lessons, Amelia, and FluentCRM.
 * Usage: wp eval-file seed-demo-data.php
 */

// Allow execution via browser for shared hosting (loads WordPress core)
if (!defined('ABSPATH')) {
    $wp_load = dirname(__FILE__, 5) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        die("Could not locate wp-load.php. Please run via WP-CLI.");
    }
}

// Security check: Only Administrators or CLI can run this
if (php_sapi_name() !== 'cli' && !current_user_can('manage_options')) {
    wp_die("Unauthorized access. You must be logged in as an Administrator.");
}

// Ensure output is readable in the browser
if (php_sapi_name() !== 'cli') {
    echo "<pre style='background:#1e1e1e; color:#0f0; padding:20px; font-family:monospace; font-size:14px;'>";
}

echo "=================================================\n";
echo "🚀 NEXTGEN TUTORS - DEMO DATA SEEDER INITIATED\n";
echo "=================================================\n\n";

// Ensure roles exist
if (!get_role('tutor')) {
    add_role('tutor', 'Tutor', ['read' => true]);
}
if (!get_role('student')) {
    add_role('student', 'Student', ['read' => true]);
}

// 1. GENERATE TUTORS
$tutors = [
    [
        'user_login' => 'tutor_david',
        'user_email' => 'david.tutor@nextgentutors.co.za',
        'first_name' => 'David',
        'last_name'  => 'Okafor',
        'role'       => 'tutor',
        'subject'    => 'Advanced Mathematics'
    ],
    [
        'user_login' => 'tutor_sarah',
        'user_email' => 'sarah.tutor@nextgentutors.co.za',
        'first_name' => 'Sarah',
        'last_name'  => 'Nkosi',
        'role'       => 'tutor',
        'subject'    => 'Physical Science'
    ]
];

$created_tutors = [];
foreach ($tutors as $t) {
    $user_id = username_exists($t['user_login']);
    if (!$user_id) {
        $user_id = wp_insert_user([
            'user_login' => $t['user_login'],
            'user_pass'  => 'Password123!',
            'user_email' => $t['user_email'],
            'first_name' => $t['first_name'],
            'last_name'  => $t['last_name'],
            'role'       => $t['role']
        ]);
        echo "✅ Created Tutor: {$t['first_name']} {$t['last_name']} (ID: $user_id)\n";
    } else {
        echo "⚠️ Tutor already exists: {$t['first_name']} {$t['last_name']} (ID: $user_id)\n";
    }
    
    // Add MasterStudy Instructor role
    $user = new WP_User($user_id);
    $user->add_role('stm_lms_instructor');
    
    $created_tutors[] = ['id' => $user_id, 'data' => $t];
}

// 2. GENERATE STUDENTS
$students = [
    ['user_login' => 'student_lebo', 'user_email' => 'lebo@student.com', 'first_name' => 'Lebo', 'last_name' => 'M.'],
    ['user_login' => 'student_jason', 'user_email' => 'jason@student.com', 'first_name' => 'Jason', 'last_name' => 'S.'],
    ['user_login' => 'student_thabo', 'user_email' => 'thabo@student.com', 'first_name' => 'Thabo', 'last_name' => 'K.'],
    ['user_login' => 'student_emma', 'user_email' => 'emma@student.com', 'first_name' => 'Emma', 'last_name' => 'W.']
];

$created_students = [];
foreach ($students as $s) {
    $user_id = username_exists($s['user_login']);
    if (!$user_id) {
        $user_id = wp_insert_user([
            'user_login' => $s['user_login'],
            'user_pass'  => 'Password123!',
            'user_email' => $s['user_email'],
            'first_name' => $s['first_name'],
            'last_name'  => $s['last_name'],
            'role'       => 'student'
        ]);
        echo "✅ Created Student: {$s['first_name']} {$s['last_name']} (ID: $user_id)\n";
    }
    $created_students[] = $user_id;
}

// 3. GENERATE MASTERSTUDY COURSES AND LESSONS
echo "\n📚 Generating Courses and Lessons...\n";
foreach ($created_tutors as $tutor) {
    $course_title = "Masterclass: " . $tutor['data']['subject'];
    
    // Check if course exists
    $existing = get_page_by_title($course_title, OBJECT, 'stm-courses');
    if ($existing) {
        $course_id = $existing->ID;
        echo "⚠️ Course already exists: $course_title\n";
    } else {
        $course_id = wp_insert_post([
            'post_title'  => $course_title,
            'post_type'   => 'stm-courses',
            'post_status' => 'publish',
            'post_author' => $tutor['id'],
            'post_content' => "This is a rigorous, curriculum-aligned course for " . $tutor['data']['subject'] . "."
        ]);
        update_post_meta($course_id, 'price', 450); // R450
        update_post_meta($course_id, 'level', 'Advanced');
        echo "✅ Created Course: $course_title (Assigned to Tutor ID: {$tutor['id']})\n";
        
        // Generate Lessons for this course
        $lessons = ["Introduction to " . $tutor['data']['subject'], "Core Fundamentals", "Advanced Problem Solving", "Exam Preparation"];
        $lesson_ids = [];
        foreach ($lessons as $lesson_title) {
            $lesson_id = wp_insert_post([
                'post_title'  => $lesson_title,
                'post_type'   => 'stm-lessons',
                'post_status' => 'publish',
                'post_author' => $tutor['id'],
                'post_content' => "Lesson material and curriculum goes here."
            ]);
            $lesson_ids[] = $lesson_id;
            echo "   -> Created Lesson: $lesson_title\n";
        }
        
        // Map lessons to course curriculum (MasterStudy format)
        $curriculum = implode(',', $lesson_ids);
        update_post_meta($course_id, 'curriculum', $curriculum);
    }
}

// 4. MAP TO AMELIA BOOKING SYSTEM
echo "\n📅 Mapping Users to Amelia Booking System...\n";
global $wpdb;
$amelia_table = $wpdb->prefix . 'amelia_users';

// Only run if Amelia table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$amelia_table'") == $amelia_table) {
    // Map Tutors as Providers
    foreach ($created_tutors as $tutor) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $amelia_table WHERE externalId = %d", $tutor['id']));
        if (!$exists) {
            $wpdb->insert($amelia_table, [
                'type'       => 'provider',
                'firstName'  => $tutor['data']['first_name'],
                'lastName'   => $tutor['data']['last_name'],
                'email'      => $tutor['data']['user_email'],
                'externalId' => $tutor['id'],
                'status'     => 'visible'
            ]);
            echo "✅ Mapped Tutor {$tutor['data']['first_name']} to Amelia as Provider.\n";
        }
    }
    // Map Students as Customers
    foreach ($students as $idx => $student) {
        $user_id = $created_students[$idx];
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $amelia_table WHERE externalId = %d", $user_id));
        if (!$exists) {
            $wpdb->insert($amelia_table, [
                'type'       => 'customer',
                'firstName'  => $student['first_name'],
                'lastName'   => $student['last_name'],
                'email'      => $student['user_email'],
                'externalId' => $user_id,
                'status'     => 'visible'
            ]);
            echo "✅ Mapped Student {$student['first_name']} to Amelia as Customer.\n";
        }
    }
} else {
    echo "⚠️ Amelia tables not found. Skipping Amelia mapping.\n";
}

// 5. MAP TO FLUENT CRM
echo "\n📧 Mapping Users to FluentCRM...\n";
if (class_exists('\FluentCrm\App\Models\Contact')) {
    // Tutors
    foreach ($created_tutors as $tutor) {
        $contact = \FluentCrm\App\Models\Contact::updateOrCreate(
            ['email' => $tutor['data']['user_email']],
            [
                'first_name' => $tutor['data']['first_name'],
                'last_name'  => $tutor['data']['last_name'],
                'user_id'    => $tutor['id'],
                'status'     => 'subscribed'
            ]
        );
        $contact->attachTags(['Active Tutor', 'Verified']);
        echo "✅ Synced Tutor {$tutor['data']['first_name']} to FluentCRM with tags.\n";
    }
    // Students
    foreach ($students as $idx => $student) {
        $user_id = $created_students[$idx];
        $contact = \FluentCrm\App\Models\Contact::updateOrCreate(
            ['email' => $student['user_email']],
            [
                'first_name' => $student['first_name'],
                'last_name'  => $student['last_name'],
                'user_id'    => $user_id,
                'status'     => 'subscribed'
            ]
        );
        $contact->attachTags(['Active Student', 'Enrolled']);
        echo "✅ Synced Student {$student['first_name']} to FluentCRM with tags.\n";
    }
} else {
    echo "⚠️ FluentCRM not active. Skipping CRM mapping.\n";
}

echo "\n🎉 SEEDING COMPLETE! The system is now populated with fully relational data.\n";
echo "You can view the new Users, MasterStudy Courses/Lessons, Amelia Providers/Customers, and FluentCRM contacts in the WP Admin.\n";

if (php_sapi_name() !== 'cli') {
    echo "</pre>";
}
