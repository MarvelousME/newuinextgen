<?php
/**
 * Integration adapter contract.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapter interface for third-party plugin integrations.
 */
interface NGC_Integration_Adapter {

	/**
	 * Adapter slug.
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * Whether the target plugin/API is available.
	 *
	 * @return bool
	 */
	public function is_available();

	/**
	 * Run verification checks.
	 *
	 * @return array<string, mixed>
	 */
	public function verify();

	/**
	 * Create or update remote entity.
	 *
	 * @param string               $action  Action slug.
	 * @param array<string, mixed> $payload Mapped payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload );

	/**
	 * Get existing remote entity reference.
	 *
	 * @param array<string, mixed> $payload Context.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload );

	/**
	 * Map workflow context to adapter payload.
	 *
	 * @param string               $workflow Workflow key.
	 * @param array<string, mixed> $context  Raw context.
	 * @return array<string, mixed>
	 */
	public function map_payload( $workflow, $context );

	/**
	 * Normalize error result.
	 *
	 * @param string               $code    Error code.
	 * @param string               $message Message.
	 * @param array<string, mixed> $data    Extra data.
	 * @return array<string, mixed>
	 */
	public function handle_error( $code, $message, $data = [] );

	/**
	 * Write adapter audit entry.
	 *
	 * @param string               $event   Event slug.
	 * @param array<string, mixed> $result  Result payload.
	 * @param int                  $user_id Related user.
	 */
	public function audit_result( $event, $result, $user_id = 0 );
}
