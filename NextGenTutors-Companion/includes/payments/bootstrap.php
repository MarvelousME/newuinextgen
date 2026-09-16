<?php
/**
 * Payments module bootstrap.
 *
 * Owns WooCommerce payment settlement, tutor payout scheduling, and payout export.
 * Classes load via ngc_autoload (includes/payments/); this file is the registry marker.
 *
 * Key classes:
 * - NGC_Payments          — WC order settlement (wallet, invoice, booking hooks)
 * - NGC_Payout_Scheduler  — monthly / bi-weekly payout cron batches
 * - NGC_Payout_Export     — PayFast-compatible payout CSV export
 *
 * Does not own: matching/scoring, AI runtime, Amelia/PayFast gateway adapters
 * (gateway still lives under integrations/; it calls NGC_Payments::settle_order).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return true;
