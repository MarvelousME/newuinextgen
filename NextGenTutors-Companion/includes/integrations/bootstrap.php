<?php
/**
 * Integrations module bootstrap stub.
 *
 * Ownership (Agent J — Integrations + Adapters):
 * - includes/integrations/  Third-party / ecosystem bridges (Amelia, PayFast checkout/ITN,
 *   LMS/MasterStudy hooks, FluentCRM/Support, AutomatorWP, WooCommerce catalog, ecosystem
 *   platform bridges, POPIA consent, session reminders, referrals, integrate runtime).
 * - includes/adapters/      NGC_Integration_Adapter implementations that wrap those plugins
 *   for workflow/orchestrator use (Amelia, FluentCRM, MasterStudy, Jitsi, email, audit, etc.).
 *
 * Explicitly NOT owned here:
 * - Core matching / scoring (includes/matching/) — Agent G.
 * - AI model runtime / agentic (includes/ai/, includes/agentic/) — Agent I.
 * - Ledger / payout scheduler & export (includes/payments/; legacy copies under this folder
 *   if still present are owned by Agent H — do not edit or move from this module).
 *
 * No-op at runtime: distinct from NGC_Integrations_Bootstrap class; does not alter which
 * classes load. Domain classes continue via the existing plugin bootstrap / autoloader.
 * See MODULE.md in this directory.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Marker for NGC_Module_Registry lazy-load; ownership docs only — no class-load change.
return true;
