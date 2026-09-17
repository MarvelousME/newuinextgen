<?php
/**
 * CRM adapter for session lifecycle.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentCRM sync adapter.
 */
class NGC_Session_Crm_Adapter implements NGC_Crm_Provider_Interface {

	/**
	 * @param array<string, mixed> $session Session.
	 * @param array<string, mixed> $context Context.
	 * @return true|WP_Error
	 */
	public function sync_session( array $session, array $context = [] ) {
		$key = 'crm:session:' . (string) ( $session['correlation_id'] ?? $session['id'] ?? '' );
		if ( class_exists( 'NGC_Idempotency' ) ) {
			$begun = NGC_Idempotency::begin( $key, $key, 'session_crm' );
			if ( is_wp_error( $begun ) ) {
				return true;
			}
			if ( 'replay' === ( $begun['status'] ?? '' ) ) {
				NGC_Session_Observability::success( 'duplicate_event_suppressed_total', $session + [ 'operation' => 'crm' ] );
				return true;
			}
		}
		if ( class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			$adapter = new NGC_Fluentcrm_Adapter();
			if ( $adapter->is_available() ) {
				$parent  = (int) ( $session['parent_user_id'] ?? 0 );
				$student = (int) ( $session['student_user_id'] ?? 0 );
				foreach ( [ $parent, $student ] as $uid ) {
					if ( $uid > 0 ) {
						$adapter->create_or_update(
							'upsert_contact',
							[
								'user_id' => $uid,
								'tags'    => [ 'Active Learner', 'Parent Paid' ],
							]
						);
					}
				}
			}
		}
		if ( class_exists( 'NGC_Idempotency' ) ) {
			NGC_Idempotency::commit( $key, [ 'ok' => true ] );
		}
		unset( $context );
		return true;
	}
}
