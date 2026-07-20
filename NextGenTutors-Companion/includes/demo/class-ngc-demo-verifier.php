<?php
/**
 * Demo relational integrity verifier (Phase 14 §14.27).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fails when required personas / relationships / labels are missing.
 */
final class NGC_Demo_Verifier {

	/**
	 * Probe CRM / LMS / Amelia adapters so synchronization is always verifiable.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function verify_integrations() {
		$out = [];

		if ( class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			$adapter = new NGC_Fluentcrm_Adapter();
			$result  = $adapter->verify();
			$out['crm'] = array_merge(
				is_array( $result ) ? $result : [],
				[
					'adapter'    => 'NGC_Fluentcrm_Adapter',
					'verifiable' => true,
				]
			);
		} else {
			$out['crm'] = [
				'ok'         => false,
				'active'     => false,
				'status'     => 'UNAVAILABLE — FluentCRM adapter class missing',
				'verifiable' => true,
				'adapter'    => 'NGC_Fluentcrm_Adapter',
			];
		}

		if ( class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			$adapter = new NGC_Masterstudy_Adapter();
			$result  = $adapter->verify();
			$out['lms'] = array_merge(
				is_array( $result ) ? $result : [],
				[
					'adapter'    => 'NGC_Masterstudy_Adapter',
					'verifiable' => true,
				]
			);
		} else {
			$out['lms'] = [
				'ok'         => false,
				'active'     => false,
				'status'     => 'UNAVAILABLE — MasterStudy adapter class missing',
				'verifiable' => true,
				'adapter'    => 'NGC_Masterstudy_Adapter',
			];
		}

		if ( class_exists( 'NGC_Amelia_Adapter' ) ) {
			$adapter = new NGC_Amelia_Adapter();
			$result  = $adapter->verify();
			$out['booking_provider'] = array_merge(
				is_array( $result ) ? $result : [],
				[
					'adapter'    => 'NGC_Amelia_Adapter',
					'verifiable' => true,
				]
			);
		} else {
			$out['booking_provider'] = [
				'ok'         => false,
				'active'     => false,
				'status'     => 'UNAVAILABLE — Amelia adapter class missing',
				'verifiable' => true,
				'adapter'    => 'NGC_Amelia_Adapter',
			];
		}

		return $out;
	}

	/**
	 * @return array{ok:bool,failures:string[],checks:array<string,bool|string>,integrations?:array<string,mixed>}
	 */
	public static function verify() {
		$failures = [];
		$checks   = [];

		$checks['demo_mode'] = NGC_Demo_Env::is_demo_mode();
		if ( ! $checks['demo_mode'] ) {
			$failures[] = 'Demo mode is not enabled';
		}

		$map = get_option( 'ngc_demo_user_map', [] );
		foreach ( array_keys( NGC_Demo_Registry::personas() ) as $stable_id ) {
			$key = 'persona_' . $stable_id;
			$uid = (int) ( $map[ $stable_id ] ?? NGC_Demo_Registry::user_id( $stable_id ) );
			$checks[ $key ] = $uid > 0;
			if ( ! $checks[ $key ] ) {
				$failures[] = 'Missing persona ' . $stable_id;
				continue;
			}
			$user = get_userdata( $uid );
			$spec = NGC_Demo_Registry::personas()[ $stable_id ];
			$role_ok = $user && in_array( $spec['role'], (array) $user->roles, true );
			$checks[ $key . '_role' ] = (bool) $role_ok;
			if ( ! $role_ok ) {
				$failures[] = 'Incorrect role for ' . $stable_id;
			}
			$demo_flag = get_user_meta( $uid, 'ngc_is_demo_user', true );
			$checks[ $key . '_demo_flag' ] = ( '1' === (string) $demo_flag || 1 === (int) $demo_flag );
			if ( ! $checks[ $key . '_demo_flag' ] ) {
				$failures[] = 'Missing is_demo flag for ' . $stable_id;
			}
		}

		$parent = NGC_Demo_Registry::user_id( 'NGT-DEMO-P0001' );
		$children = class_exists( 'NGC_Child_Learners' ) ? NGC_Child_Learners::for_parent( $parent ) : [];
		$checks['parent_child_link'] = count( $children ) >= 2;
		if ( ! $checks['parent_child_link'] ) {
			$failures[] = 'Primary parent must have at least 2 child learners';
		}

		$graph = get_option( NGC_Demo_Seeder::OPTION_GRAPH, [] );
		$checks['match_001'] = ! empty( $graph['matches']['MATCH-001'] );
		$checks['booking_confirmed'] = ! empty( $graph['bookings']['BOOK-001'] );
		$checks['booking_completed'] = ! empty( $graph['bookings']['BOOK-COMPLETED'] );
		$checks['fraud_case'] = ! empty( $graph['fraud']['case'] );
		$checks['safeguarding_case'] = ! empty( $graph['safeguarding']['case'] );
		$checks['notifications'] = count( NGC_Demo_Notifications::all() ) > 0;
		$checks['events_recorded'] = ! empty( $graph['events'] );

		$suspended = NGC_Demo_Registry::user_id( 'NGT-DEMO-T0004' );
		$checks['suspended_flag'] = $suspended && 'suspended' === get_user_meta( $suspended, 'ngc_tutor_status', true );
		if ( ! $checks['suspended_flag'] ) {
			$failures[] = 'Suspended tutor missing suspension status';
		}

		foreach ( [ 'match_001', 'booking_confirmed', 'booking_completed', 'fraud_case', 'safeguarding_case', 'notifications', 'events_recorded' ] as $k ) {
			if ( empty( $checks[ $k ] ) ) {
				$failures[] = 'Failed check: ' . $k;
			}
		}

		$integrations = self::verify_integrations();
		$checks['crm_sync_verifiable']              = ! empty( $integrations['crm']['status'] );
		$checks['lms_sync_verifiable']              = ! empty( $integrations['lms']['status'] );
		$checks['booking_provider_sync_verifiable'] = ! empty( $integrations['booking_provider']['status'] );
		$checks['crm_sync_status']                  = (string) ( $integrations['crm']['status'] ?? '' );
		$checks['lms_sync_status']                  = (string) ( $integrations['lms']['status'] ?? '' );
		$checks['booking_provider_sync_status']     = (string) ( $integrations['booking_provider']['status'] ?? '' );

		foreach (
			[
				'crm'              => [ 'CRM', 'crm_sync_verifiable' ],
				'lms'              => [ 'LMS', 'lms_sync_verifiable' ],
				'booking_provider' => [ 'Booking-provider', 'booking_provider_sync_verifiable' ],
			] as $key => $meta
		) {
			$label     = $meta[0];
			$check_key = $meta[1];
			if ( empty( $checks[ $check_key ] ) ) {
				$failures[] = $label . ' synchronization is not verifiable (no adapter status)';
			}
			if ( ! empty( $integrations[ $key ]['active'] ) && empty( $integrations[ $key ]['ok'] ) ) {
				$failures[] = $label . ' synchronization active but not VERIFIED: ' . ( $integrations[ $key ]['status'] ?? 'unknown' );
			}
		}

		$journeys = class_exists( 'NGC_Demo_Journeys' ) ? NGC_Demo_Journeys::list_journeys() : [];
		$checks['journey_catalogue_count'] = count( $journeys );
		$checks['journey_catalogue_min']   = count( $journeys ) >= 29;
		if ( ! $checks['journey_catalogue_min'] ) {
			$failures[] = 'Journey catalogue incomplete (need MATCH/BOOK/FIN + umbrella; found ' . count( $journeys ) . ')';
		}

		return [
			'ok'           => empty( $failures ),
			'failures'     => $failures,
			'checks'       => $checks,
			'integrations' => $integrations,
			'seed'         => get_option( NGC_Demo_Seeder::OPTION_STATUS, [] ),
		];
	}
}
