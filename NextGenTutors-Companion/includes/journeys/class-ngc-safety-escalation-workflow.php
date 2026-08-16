<?php
/**
 * Safety escalation — wraps NGC_Safeguarding with canonical events.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures AI flags create cases via safeguarding SoR (not AutomatorWP).
 */
final class NGC_Safety_Escalation_Workflow {

	/**
	 * Init.
	 */
	public static function init() {
		if ( ! class_exists( 'NGC_Business_Rules' ) ) {
			return;
		}
		add_action( 'ngc_safeguarding_case_created', [ __CLASS__, 'on_case_created' ], 10, 2 );
		add_filter( 'ngc_safeguarding_sla_hours_critical', [ __CLASS__, 'filter_critical_sla' ] );
	}

	/**
	 * Align critical SLA with business rule.
	 *
	 * @param int $hours Default.
	 * @return int
	 */
	public static function filter_critical_sla( $hours ) {
		return (int) NGC_Business_Rules::get( 'ngt.safety.high_priority_response_sla' );
	}

	/**
	 * @param int                  $case_id Case ID.
	 * @param array<string, mixed> $data    Case data.
	 */
	public static function on_case_created( $case_id, $data = [] ) {
		if ( ! NGC_Business_Rules::journey_enabled( 'safety' ) ) {
			return;
		}
		if ( class_exists( 'NGC_Journey_Events' ) ) {
			NGC_Journey_Events::emit(
				NGC_Journey_Events::SAFETY_CASE_CREATED,
				array_merge(
					is_array( $data ) ? $data : [],
					[ 'case_id' => (int) $case_id ]
				),
				false
			);
			if ( ! empty( $data['ai_signal'] ) ) {
				NGC_Journey_Events::emit(
					NGC_Journey_Events::SAFETY_FLAG_RAISED,
					[
						'case_id'   => (int) $case_id,
						'flag_id'   => (int) $case_id,
						'severity' => (string) ( $data['priority'] ?? 'high' ),
					],
					false
				);
			}
		}
	}

	/**
	 * Raise a synthetic/admin safety flag (AI must not adjudicate).
	 *
	 * @param array<string, mixed> $data Flag payload.
	 * @return int Case ID or 0.
	 */
	public static function raise_flag( array $data ) {
		if ( ! class_exists( 'NGC_Safeguarding' ) ) {
			return 0;
		}
		$data['ai_signal'] = ! empty( $data['ai_signal'] ) ? 1 : 0;
		$data['priority']  = sanitize_key( $data['priority'] ?? 'high' );
		$data['source']    = sanitize_key( $data['source'] ?? 'journey' );
		$data['summary']   = sanitize_text_field( $data['summary'] ?? 'Safety flag raised' );
		$case_id = (int) NGC_Safeguarding::create_case( $data );
		// create_case already fires ngc_safeguarding_case_created.
		return $case_id;
	}
}
