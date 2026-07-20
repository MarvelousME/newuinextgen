<?php
/**
 * AI-powered diagnostics platform.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI diagnosis, confidence scoring, and repair recommendations.
 */
class NGC_Ai_Diagnostics {

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Invoked via REST and admin.
	}

	/**
	 * Run AI-powered health diagnosis.
	 *
	 * @param bool $include_ai Whether to call LLM provider.
	 * @return array<string, mixed>
	 */
	public static function diagnose( $include_ai = true ) {
		$scan     = NGC_Health_Scanner::full_scan();
		$drift    = NGC_Health_Scanner::detect_drift();
		$plan     = NGC_Repair_Engine::build_plan( true );
		$provider = NGC_Ai_Provider_Registry::resolve_diagnostics_model_id();

		$diagnosis = [
			'scan'          => $scan,
			'drift_issues'  => $drift,
			'repair_plan'   => $plan,
			'confidence'    => $scan['ok'] ? 0.95 : 0.6,
			'root_cause'    => self::infer_root_cause( $drift ),
			'suggested_repair' => $plan['actions'],
			'rollback_plan' => NGC_Repair_Engine::rollback_plan( 'pending' ),
			'verification_plan' => [
				'Re-run NGC_Health_Scanner::full_scan()',
				'Check REST endpoints',
				'Verify audit log entry for repair',
			],
		];

		if ( $include_ai ) {
			$ai = self::ai_analyze( $scan, $drift );
			if ( ! empty( $ai['success'] ) ) {
				$diagnosis['ai_diagnosis']  = $ai['text'];
				$diagnosis['confidence']    = max( $diagnosis['confidence'], (float) ( $ai['confidence'] ?? 0 ) );
			} else {
				$diagnosis['ai_diagnosis'] = $ai['error'] ?? __( 'AI analysis unavailable — configure a model in NextGen → AI Suite.', 'nextgencompanion' );
				$diagnosis['ai_fallback']  = true;
			}
		}

		self::log_diagnosis( $diagnosis, $provider );

		NGC_Audit::log( 'health_scan_executed', 'system', 0, [
			'ok'         => $scan['ok'],
			'issue_count'=> count( $drift ),
		], get_current_user_id(), [ 'workflow_key' => 'ai_diagnostics' ] );

		return $diagnosis;
	}

	/**
	 * @param array<string, mixed> $scan  Scan results.
	 * @param array<int, mixed>    $drift Drift issues.
	 * @return array<string, mixed>
	 */
	private static function ai_analyze( $scan, $drift ) {
		$prompt = "Analyze this WordPress tutoring platform health scan and provide: 1) diagnosis 2) root cause 3) suggested repair steps 4) risk assessment.\n\n";
		$prompt .= wp_json_encode( [ 'scan_summary' => array_map( static function ( $v ) {
			return is_array( $v ) ? ( $v['ok'] ?? $v ) : $v;
		}, $scan ), 'drift' => $drift ] );

		return NGC_Ai_Provider_Registry::complete(
			$prompt,
			'You are a WordPress platform diagnostics expert for an education/tutoring SaaS. Be concise and actionable.'
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $drift Drift issues.
	 * @return string
	 */
	private static function infer_root_cause( $drift ) {
		if ( empty( $drift ) ) {
			return 'No configuration drift detected.';
		}
		$types = array_column( $drift, 'type' );
		if ( in_array( 'missing_tables', $types, true ) ) {
			return 'Database schema incomplete — plugin activation or migration may have failed.';
		}
		if ( in_array( 'missing_pages', $types, true ) ) {
			return 'Core pages were deleted or never created during setup.';
		}
		if ( in_array( 'missing_roles', $types, true ) ) {
			return 'Custom roles not installed — run role repair.';
		}
		return 'Multiple configuration issues detected.';
	}

	/**
	 * @param array<string, mixed> $diagnosis Diagnosis.
	 * @param string               $provider  Provider.
	 */
	private static function log_diagnosis( $diagnosis, $provider ) {
		global $wpdb;
		$wpdb->insert(
			NGC_Database::table( 'ai_diagnostics_log' ),
			[
				'scan_type'     => 'health',
				'provider'      => sanitize_key( $provider ?: 'rules_engine' ),
				'diagnosis'     => wp_json_encode( $diagnosis ),
				'confidence'    => (float) ( $diagnosis['confidence'] ?? 0 ),
				'root_cause'    => sanitize_text_field( (string) ( $diagnosis['root_cause'] ?? '' ) ),
				'repair_plan'   => wp_json_encode( $diagnosis['repair_plan'] ?? [] ),
				'rollback_plan' => wp_json_encode( $diagnosis['rollback_plan'] ?? [] ),
				'status'        => 'completed',
				'created_by'    => get_current_user_id(),
				'created_at'    => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
	}
}
