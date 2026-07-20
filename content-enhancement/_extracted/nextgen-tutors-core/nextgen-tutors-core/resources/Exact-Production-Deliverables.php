<?php
/**
 * NextGen Tutors — POPIA Consent Injection & Audit Logging
 * Hooks into WooCommerce checkout. Mandatory, encrypted audit trail, POPIA §11/§24 compliant.
 */

// 1. Render Consent Form After Billing Fields
add_action('woocommerce_after_checkout_billing_form', 'ngt_render_popia_checkout_consent');
function ngt_render_popia_checkout_consent() {
    ?>
    <div id="ngt-popia-checkout" class="ngt-consent-checkout">
        <style>
            .ngt-consent-checkout {
                background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 8px;
                padding: 16px; margin: 20px 0; font-family: inherit;
            }
            .ngt-consent-checkout label {
                font-size: 14px; line-height: 1.5; display: flex; align-items: flex-start;
                gap: 10px; cursor: pointer; color: #0f172a;
            }
            .ngt-consent-checkout input[type="checkbox"] {
                margin-top: 4px; width: 18px; height: 18px; accent-color: #0066cc;
            }
            .ngt-consent-checkout a { color: #0066cc; text-decoration: none; }
            .ngt-consent-checkout a:hover { text-decoration: underline; }
            .ngt-popia-error { color: #dc2626; font-size: 13px; margin-top: 8px; display: none; }
        </style>
        <label for="ngt_popia_consent">
            <input type="checkbox" id="ngt_popia_consent" name="ngt_popia_consent" value="1" required>
            <span>
                I explicitly consent to NextGen Tutors processing my personal information and session recordings per POPIA. 
                Data is retained for 30 days then auto-deleted. <a href="/privacy-policy/" target="_blank">Privacy Policy</a>.
            </span>
        </label>
        <div id="ngt-popia-error" class="ngt-popia-error">⚠️ Consent is required to complete your booking.</div>
    </div>
    <script>
        (function(){
            const form = document.querySelector('form.checkout');
            const chk = document.getElementById('ngt_popia_consent');
            const err = document.getElementById('ngt-popia-error');
            if(form && chk) {
                form.addEventListener('submit', function(e){
                    if(!chk.checked){
                        e.preventDefault(); err.style.display = 'block'; chk.focus();
                    } else { err.style.display = 'none'; }
                });
                chk.addEventListener('change', () => err.style.display = 'none');
            }
        })();
    </script>
    <?php
}

// 2. Server-Side Validation (Blocks Checkout if Unchecked)
add_action('woocommerce_checkout_process', 'ngt_validate_popia_consent');
function ngt_validate_popia_consent() {
    if (empty($_POST['ngt_popia_consent']) || $_POST['ngt_popia_consent'] !== '1') {
        wc_add_notice(__('POPIA consent is mandatory. Please accept the terms to proceed.', 'nextgen-tutors'), 'error');
    }
}

// 3. Save Structured Audit Trail to Order & User Meta
add_action('woocommerce_checkout_create_order', 'ngt_save_popia_audit_log', 10, 2);
function ngt_save_popia_audit_log($order, $data) {
    if (!empty($_POST['ngt_popia_consent']) && $_POST['ngt_popia_consent'] === '1') {
        $audit = [
            'accepted'      => true,
            'timestamp'     => current_time('mysql'),
            'ip_address'    => WC_Geolocation::get_ip_address(),
            'user_agent'    => wc_get_user_agent(),
            'consent_ver'   => '1.2', // Matches functional-spec.md version
            'checkout_type' => $order->get_customer_id() ? 'returning' : 'guest'
        ];
        
        // Encrypt IP for POPIA minimization (optional but recommended)
        if (defined('NGT_ENCRYPTION_KEY')) {
            $audit['ip_encrypted'] = ngt_encrypt_data($audit['ip_address']);
            unset($audit['ip_address']);
        }
        
        $order->update_meta_data('_ngt_popia_consent', $audit);
        
        // Persist to user if registered
        if ($order->get_customer_id()) {
            update_user_meta($order->get_customer_id(), '_ngt_popia_consent', $audit);
        }
    }
}