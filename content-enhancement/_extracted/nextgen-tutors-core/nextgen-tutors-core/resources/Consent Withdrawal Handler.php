// Add to functions-enhanced.php
add_action('wp', 'ngt_handle_popia_withdrawal');
function ngt_handle_popia_withdrawal() {
    if (!isset($_GET['ngt_withdraw_popia']) || !wp_verify_nonce($_GET['_wpnonce'], 'withdraw_popia')) return;
    
    $user_id = get_current_user_id();
    if (!$user_id) return;
    
    update_user_meta($user_id, '_ngt_popia_consent', ['given'=>false, 'withdrawn_at'=>current_time('mysql'), 'withdrawn_ip'=>$_SERVER['REMOTE_ADDR']]);
    
    // Update FluentCRM
    if (class_exists('FluentCRM\App\Models\Contact')) {
        $contact = \FluentCRM\App\Models\Contact::where('user_id', $user_id)->first();
        if ($contact) {
            $contact->custom_fields['popia_consent_given'] = false;
            $contact->removeFromLists(['Active Customers', 'Marketing Opt-In']);
            $contact->attachTags(['POPIA Withdrawn', 'Do Not Market']);
            $contact->save();
        }
    }
    
    wp_redirect(home_url('/dashboard/?popia_withdrawn=1'));
    exit;
}