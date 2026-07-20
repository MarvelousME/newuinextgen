<?php
/**
 * NextGen Tutors Payouts & Billing
 */
?>
<div class="wrap ngt-admin-wrap">
    <div class="ngt-dashboard-header">
        <h1>Payouts & Billing Management</h1>
        <div class="ngt-quick-actions">
            <button id="batch-pay-now" class="button button-primary"><span class="dashicons dashicons-money-alt"></span> Pay Now (Batch Override)</button>
        </div>
    </div>

    <div class="ngt-card">
        <h3>Pending Tutor Payouts</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></th>
                    <th>Tutor</th>
                    <th>Pending Amount</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="payout-list">
                <tr>
                    <th class="check-column"><input type="checkbox" name="tutor_ids[]" value="1"></th>
                    <td><strong>John Doe</strong></td>
                    <td>R1,250.00</td>
                    <td>2 hours ago</td>
                    <td><span class="ngt-badge warning">Pending</span></td>
                    <td><button class="button generate-invoice" data-id="1">Invoice</button></td>
                </tr>
                <tr>
                    <th class="check-column"><input type="checkbox" name="tutor_ids[]" value="2"></th>
                    <td><strong>Jane Smith</strong></td>
                    <td>R2,800.00</td>
                    <td>5 hours ago</td>
                    <td><span class="ngt-badge warning">Pending</span></td>
                    <td><button class="button generate-invoice" data-id="2">Invoice</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.ngt-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.ngt-badge.warning { background: #fff8e1; color: #ffb900; border: 1px solid #ffe082; }
</style>

<script>
(function($) {
    'use strict';
    
    $(function() {
        $('#batch-pay-now').on('click', function() {
            const selected = $('input[name="tutor_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (selected.length === 0) {
                alert('Please select at least one tutor.');
                return;
            }

            if (confirm(`Are you sure you want to override and pay ${selected.length} tutors now?`)) {
                $.ajax({
                    url: ngtSettings.rest_url + 'ngt/v1/payouts/batch',
                    method: 'POST',
                    beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', ngtSettings.rest_nonce),
                    data: JSON.stringify({ tutor_ids: selected }),
                    contentType: 'application/json',
                    success: (response) => {
                        alert('Batch Payout Override Executed Successfully!');
                        location.reload();
                    }
                });
            }
        });

        $('.generate-invoice').on('click', function() {
            const id = $(this).data('id');
            window.location.href = ngtSettings.rest_url + 'ngt/v1/billing/invoice?order_id=' + id + '&_wpnonce=' + ngtSettings.rest_nonce;
        });
    });
})(jQuery);
</script>

