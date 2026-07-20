#!/usr/bin/env php
<?php
/**
 * NextGen Tutors — WP-CLI Bulk Importer
 * Usage: wp eval-file import-contacts.php contacts.csv --role=subscriber --list="Active Customers"
 */

if (!defined('WP_CLI') || !WP_CLI) die("Run via WP-CLI: wp eval-file import-contacts.php contacts.csv\n");

$file = WP_CLI::get_arguments()[0] ?? null;
if (!$file || !file_exists($file)) WP_CLI::error("CSV file not found: $file");

$role = WP_CLI\Utils\get_flag_value(WP_CLI::get_arguments(), '--role', 'subscriber');
$list_name = WP_CLI\Utils\get_flag_value(WP_CLI::get_arguments(), '--list', 'Active Customers');

$fp = fopen($file, 'r');
$header = fgetcsv($fp);
$count = 0;
$skipped = 0;

WP_CLI::log("🚀 Importing contacts from $file...");
$progress = \WP_CLI\Utils\make_progress_bar('Processing', 100);

while (($row = fgetcsv($fp)) !== false) {
    $data = array_combine($header, $row);
    $email = sanitize_email($data['Email'] ?? '');
    if (!is_email($email)) continue;

    // 1. Create/Get WP User
    $user_id = email_exists($email);
    if (!$user_id) {
        $user_id = wp_create_user($email, wp_generate_password(12), $email);
        if (is_wp_error($user_id)) { $skipped++; continue; }
        
        $wp_user = new WP_User($user_id);
        $wp_user->set_role($role);
        
        update_user_meta($user_id, 'first_name', sanitize_text_field($data['First Name'] ?? ''));
        update_user_meta($user_id, 'last_name', sanitize_text_field($data['Last Name'] ?? ''));
        update_user_meta($user_id, 'phone', sanitize_text_field($data['Phone'] ?? ''));
    }

    // 2. Sync to FluentCRM
    if (class_exists('FluentCRM\App\Models\Contact')) {
        $contact = \FluentCRM\App\Models\Contact::firstOrCreate(['email' => $email]);
        $contact->first_name = sanitize_text_field($data['First Name'] ?? '');
        $contact->last_name = sanitize_text_field($data['Last Name'] ?? '');
        $contact->save();
        
        // Assign List
        $list = \FluentCRM\App\Models\ContactList::where('title', $list_name)->first();
        if ($list) {
            $contact->attachLists([$list->id]);
        }
        
        // Assign Tags
        if (!empty($data['Tags'])) {
            $tags = array_map('trim', explode(',', $data['Tags']));
            foreach ($tags as $tag_title) {
                $tag = \FluentCRM\App\Models\ContactTag::firstOrCreate(['title' => $tag_title]);
                $contact->attachTags([$tag->id]);
            }
        }
    }
    
    $count++;
    if ($count % 10 === 0) $progress->tick();
}

fclose($fp);
$progress->finish();
WP_CLI::success("✅ Imported $count contacts. Skipped $skipped invalid rows.");