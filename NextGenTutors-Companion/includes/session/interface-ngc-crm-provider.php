<?php
/**
 * CRM provider contract.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent CRM synchronization for sessions.
 */
interface NGC_Crm_Provider_Interface {

	/**
	 * @param array<string, mixed> $session Session.
	 * @param array<string, mixed> $context Context.
	 * @return true|WP_Error
	 */
	public function sync_session( array $session, array $context = [] );
}
