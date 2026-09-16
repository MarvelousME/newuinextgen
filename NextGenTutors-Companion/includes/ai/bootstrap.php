<?php
/**
 * AI module bootstrap.
 *
 * Module surface (lazy-loaded via NGC_Module_Registry):
 * - includes/ai/       — BYOK models, chat, supervised agents, crypto, BIA policy
 * - includes/agentic/  — governed tool gateway, MCP/A2A, social OAuth, leads/outreach,
 *                        publish worker (files stay under agentic/; do not relocate)
 *
 * Runtime: classes continue to load via ngc_autoload (ai/ + agentic/ + subdirs).
 * No domain init side effects here yet — marker for registry only.
 *
 * Transport (HTTP provider clients) stays in the AI-Integration plugin, not Companion.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Marker for NGC_Module_Registry lazy-load; domain wiring remains in existing plugin bootstrap.
return true;
