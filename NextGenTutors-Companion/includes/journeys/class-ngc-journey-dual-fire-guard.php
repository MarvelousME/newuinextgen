<?php
/**
 * Blocks AutomatorWP / Hub mutations for core journeys when Ecosystem owns them.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dual-fire guard for migrated core side effects.
 */
final class NGC_Journey_Dual_Fire_Guard {

	/**
	 * AutomatorWP / Hub event keys that must not execute core mutations.
	 *
	 * @return string[]
	 */
	public static function blocked_core_events() {
		return apply_filters(
			'ngc_journey_blocked_core_events',
			[
				'payment.received',
				'payment.completed',
				'order.completed',
				'woocommerce.order.completed',
				'ngt.payment.received',
				'booking.created',
				'amelia.booking.created',
				'tutor.approved',
				'ngt.tutor.approved',
				'payout.calculated',
				'payout.processed',
				'safeguarding.alert.raised',
			]
		);
	}

	/**
	 * Init filters.
	 */
	public static function init() {
		add_filter( 'ngc_automatorwp_should_execute_side_effects', [ __CLASS__, 'filter_automatorwp' ], 4, 3 );
		add_filter( 'ngc_hub_should_execute_side_effects', [ __CLASS__, 'filter_hub' ], 4, 3 );
		add_filter( 'ngc_studio_should_execute_side_effects', [ __CLASS__, 'filter_studio_imported' ], 4, 4 );
	}

	/**
	 * @param bool                 $should Default.
	 * @param string               $event  Event.
	 * @param array<string, mixed> $payload Payload.
	 * @return bool
	 */
	public static function filter_automatorwp( $should, $event = '', $payload = [] ) {
		if ( ! NGC_Business_Rules::get( 'ngt.journey.disable_automatorwp_core' ) ) {
			return $should;
		}
		if ( ! class_exists( 'NGC_Platform' ) || ! NGC_Platform::authority_enabled() ) {
			return $should;
		}
		$event = (string) $event;
		$canon = class_exists( 'NGC_Journey_Events' ) ? NGC_Journey_Events::resolve( $event ) : '';
		if ( in_array( $event, self::blocked_core_events(), true ) || $canon ) {
			return false;
		}
		return $should;
	}

	/**
	 * Hub producers: allow notification-only; block mutation-heavy events when authority on.
	 *
	 * @param bool   $should Default.
	 * @param mixed  $a      Unused / event.
	 * @param mixed  $b      Unused.
	 * @return bool
	 */
	public static function filter_hub( $should, $a = null, $b = null ) {
		if ( ! class_exists( 'NGC_Platform' ) || ! NGC_Platform::authority_enabled() ) {
			return $should;
		}
		// When authority is on, Workflow_Authority already returns false at priority 5.
		// Keep this filter as documentation + extra safety for hub mutation events.
		return $should;
	}

	/**
	 * Prevent imported studio graphs from double-executing when unpublished authority path preferred.
	 *
	 * @param bool                 $should  Default.
	 * @param array<string, mixed> $wf      Workflow.
	 * @param array<string, mixed> $context Context.
	 * @param string               $key     Trigger.
	 * @return bool
	 */
	public static function filter_studio_imported( $should, $wf = [], $context = [], $key = '' ) {
		$source = (string) ( $wf['settings']['source'] ?? '' );
		if ( in_array( $source, [ 'hub', 'integrate', 'orchestrator', 'templates' ], true ) ) {
			// Imported documentation graphs should not execute as a second runtime when authority on.
			if ( class_exists( 'NGC_Platform' ) && NGC_Platform::authority_enabled() ) {
				$status = (string) ( $wf['status'] ?? '' );
				if ( 'published' !== $status ) {
					return false;
				}
				// Published imported cores still go through authority (filter_producer_execute).
			}
		}
		return $should;
	}
}
