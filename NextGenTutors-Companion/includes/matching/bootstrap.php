<?php
/**
 * Matching module bootstrap.
 *
 * Ownership (scoring / match lifecycle — not payments or payouts):
 *   - NGC_Matching          — DB match create/score/accept/reject (includes/matching/class-ngc-matching.php)
 *   - NGC_Smart_Matching    — CPT scoring wizard, shortcode, AJAX/REST helpers
 *   - NGC_Tutor_Cpt_Source  — canonical tutor CPT discovery + scoring source
 *   - NGC_Rest_Matching     — /matches REST surface (this directory)
 *
 * Domain classes continue to load via ngc_autoload / plugin bootstrap.
 * Boundary docs: MODULE.md in this directory.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Marker for NGC_Module_Registry lazy-load; wire domain init here when migrating off NGC_Plugin::$modules.
return true;
