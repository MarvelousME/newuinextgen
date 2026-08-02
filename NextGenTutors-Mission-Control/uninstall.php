<?php
/**
 * Uninstall Mission Control — removes override option only (never touches Companion data).
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ngtmc_system_overrides' );
