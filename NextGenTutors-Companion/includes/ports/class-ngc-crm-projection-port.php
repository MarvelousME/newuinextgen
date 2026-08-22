<?php
/**
 * Thin CRM projection port — FluentCRM adapter only.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRM projections for journey workflows.
 */
final class NGC_Crm_Projection_Port {

	/**
	 * Tag contact as active customer after payment.
	 *
	 * @param int $user_id  User ID.
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public static function project_active_customer( $user_id, $order_id = 0 ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user || ! $user->user_email ) {
			return 'crm_skipped_no_email';
		}
		if ( ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			return 'crm_adapter_absent';
		}
		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return 'crm_unavailable';
		}
		$result = $adapter->create_or_update(
			'upsert_contact',
			[
				'email'         => $user->user_email,
				'user_id'       => $user_id,
				'first_name'    => $user->first_name,
				'last_name'     => $user->last_name,
				'lists'         => [ 'Active Customers' ],
				'tags'          => [ 'Parent Paid', 'Engaged Customer' ],
				'detach_tags'   => [ 'Prospective Parent', 'Parent Enquiry' ],
				'workflow'      => 'LearnerBookingConfirmation',
				'custom_fields' => [
					'first_booking_date' => get_user_meta( $user_id, 'ngt_first_booking_reward_awarded', true )
						? (string) get_user_meta( $user_id, 'ngt_first_booking_reward_awarded', true )
						: gmdate( 'Y-m-d' ),
					'last_order_id'      => (string) (int) $order_id,
				],
			]
		);
		return ! empty( $result['ok'] ) || ! empty( $result['success'] ) || empty( $result['error'] )
			? 'crm_active_customer_projected'
			: 'crm_projection_failed';
	}
}
