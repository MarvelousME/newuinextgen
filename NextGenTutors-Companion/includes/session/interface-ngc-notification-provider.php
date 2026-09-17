<?php
/**
 * Notification provider contract.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent session notifications.
 */
interface NGC_Notification_Provider_Interface {

	/**
	 * @param string               $event   Event slug.
	 * @param array<string, mixed> $session Session.
	 * @param array<string, mixed> $context Extra.
	 * @return true|WP_Error
	 */
	public function notify( $event, array $session, array $context = [] );
}
