<?php
/**
 * Third-party integrations (Amelia, WooCommerce, Companion).
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Integrations {

	public static function register_hooks(): void {
		add_action( 'amelia_after_booking_added', [ __CLASS__, 'on_amelia_booking' ], 10, 1 );
		add_filter( 'ngt_dashboard_widget_data', [ __CLASS__, 'enrich_dashboard' ], 5, 2 );
	}

	/**
	 * @param mixed $booking Amelia booking payload.
	 */
	public static function on_amelia_booking( $booking ): void {
		$data = is_array( $booking ) ? $booking : (array) $booking;
		NGT_Hub::fire_event(
			'amelia.booking.created',
			'amelia',
			0,
			(int) ( $data['id'] ?? $data['bookingId'] ?? 0 ),
			[
				'booking_id'  => (int) ( $data['id'] ?? $data['bookingId'] ?? 0 ),
				'service_id'  => (int) ( $data['serviceId'] ?? 0 ),
				'employee_id' => (int) ( $data['providerId'] ?? $data['employeeId'] ?? 0 ),
			]
		);
	}

	/**
	 * @param array<string, mixed> $widgets Widget data.
	 * @return array<string, mixed>
	 */
	public static function enrich_dashboard( array $widgets, string $role ): array {
		if ( class_exists( 'NGC_Automation_Hub_Bridge' ) ) {
			return NGC_Automation_Hub_Bridge::inject_live_widget_data( $widgets, $role );
		}
		return $widgets;
	}
}
