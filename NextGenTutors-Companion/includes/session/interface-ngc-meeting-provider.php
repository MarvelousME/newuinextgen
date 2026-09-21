<?php
/**
 * Meeting provider contract — realtime communication truth.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent live-meeting provisioning.
 */
interface NGC_Meeting_Provider_Interface {

	/**
	 * @param array<string, mixed> $session Session row as array.
	 * @param array<string, mixed> $context Context.
	 * @return array{provider:string,meeting_id:string,reference:string,join_url:string,created:bool}|WP_Error
	 */
	public function ensure_meeting( array $session, array $context = [] );

	/**
	 * Personalized join URL after authorization.
	 *
	 * @param array<string, mixed> $session Session.
	 * @param int                  $user_id Viewer.
	 * @return string|WP_Error
	 */
	public function join_url_for_user( array $session, $user_id );

	/**
	 * @param array<string, mixed> $session Session.
	 * @return true|WP_Error
	 */
	public function revoke( array $session );
}
