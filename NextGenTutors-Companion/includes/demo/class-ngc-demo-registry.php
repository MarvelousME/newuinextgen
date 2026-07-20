<?php
/**
 * Demo persona directory + credentials (Phase 14 §14.5–14.6).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stable demo identifiers and login catalogue.
 */
final class NGC_Demo_Registry {

	/**
	 * Persona catalogue (deterministic emails + stable IDs).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function personas() {
		return [
			'NGT-DEMO-P0001' => [
				'email' => 'demo.parent@nextgen.local',
				'name'  => 'Thandi Molefe',
				'role'  => 'parent_guardian',
				'scenario' => 'primary-parent',
				'landing' => 'parent-dashboard',
				'state' => 'active_with_children',
			],
			'NGT-DEMO-P0002' => [
				'email' => 'demo.parent.new@nextgen.local',
				'name'  => 'Johan van der Berg',
				'role'  => 'parent',
				'scenario' => 'new-parent',
				'landing' => 'parent-dashboard',
				'state' => 'onboarding',
			],
			'NGT-DEMO-P0003' => [
				'email' => 'demo.parent.pending@nextgen.local',
				'name'  => 'Ayesha Khan',
				'role'  => 'parent',
				'scenario' => 'parent-pending-verify',
				'landing' => 'parent-dashboard',
				'state' => 'email_pending',
			],
			'NGT-DEMO-S0001' => [
				'email' => 'demo.student.adult@nextgen.local',
				'name'  => 'Sipho Dlamini',
				'role'  => 'student',
				'scenario' => 'adult-student',
				'landing' => 'student-dashboard',
				'state' => 'active',
			],
			'NGT-DEMO-S0002' => [
				'email' => 'demo.child.a@nextgen.local',
				'name'  => 'Lerato Molefe',
				'role'  => 'child_learner',
				'scenario' => 'minor-a',
				'landing' => 'student-dashboard',
				'state' => 'linked_minor',
			],
			'NGT-DEMO-S0003' => [
				'email' => 'demo.child.b@nextgen.local',
				'name'  => 'Kagiso Molefe',
				'role'  => 'child_learner',
				'scenario' => 'minor-b',
				'landing' => 'student-dashboard',
				'state' => 'linked_minor',
			],
			'NGT-DEMO-T0001' => [
				'email' => 'demo.tutor.approved@nextgen.local',
				'name'  => 'Dr Nomsa Khumalo',
				'role'  => 'tutor',
				'scenario' => 'approved-tutor',
				'landing' => 'tutor-dashboard',
				'state' => 'verified_active',
				'subjects' => [ 'Mathematics', 'Physical Sciences' ],
			],
			'NGT-DEMO-T0002' => [
				'email' => 'demo.tutor.online@nextgen.local',
				'name'  => 'Pieter Botha',
				'role'  => 'tutor',
				'scenario' => 'online-tutor',
				'landing' => 'tutor-dashboard',
				'state' => 'verified_active',
				'subjects' => [ 'English', 'Afrikaans' ],
			],
			'NGT-DEMO-T0003' => [
				'email' => 'demo.tutor.budget@nextgen.local',
				'name'  => 'Fatima Patel',
				'role'  => 'tutor',
				'scenario' => 'budget-tutor',
				'landing' => 'tutor-dashboard',
				'state' => 'verified_active',
				'subjects' => [ 'Accounting', 'Economics', 'Business Studies' ],
			],
			'NGT-DEMO-T0004' => [
				'email' => 'demo.tutor.suspended@nextgen.local',
				'name'  => 'Suspended Tutor',
				'role'  => 'tutor',
				'scenario' => 'suspended-tutor',
				'landing' => 'tutor-dashboard',
				'state' => 'suspended',
				'subjects' => [ 'Life Sciences' ],
			],
			'NGT-DEMO-T0005' => [
				'email' => 'demo.tutor.draft@nextgen.local',
				'name'  => 'Draft Applicant',
				'role'  => 'tutor_applicant',
				'scenario' => 'tutor-draft',
				'landing' => 'tutor-dashboard',
				'state' => 'application_draft',
			],
			'NGT-DEMO-T0006' => [
				'email' => 'demo.tutor.submitted@nextgen.local',
				'name'  => 'Submitted Applicant',
				'role'  => 'tutor_applicant',
				'scenario' => 'tutor-submitted',
				'landing' => 'tutor-dashboard',
				'state' => 'application_submitted',
			],
			'NGT-DEMO-T0007' => [
				'email' => 'demo.tutor.resubmit@nextgen.local',
				'name'  => 'Resubmit Applicant',
				'role'  => 'tutor_applicant',
				'scenario' => 'tutor-resubmit',
				'landing' => 'tutor-dashboard',
				'state' => 'resubmission_required',
			],
			'NGT-DEMO-A0001' => [
				'email' => 'demo.admin@nextgen.local',
				'name'  => 'Demo Administrator',
				'role'  => 'administrator',
				'scenario' => 'admin',
				'landing' => 'wp-admin',
				'state' => 'active',
			],
			'NGT-DEMO-F0001' => [
				'email' => 'demo.finance@nextgen.local',
				'name'  => 'Demo Finance Officer',
				'role'  => 'ngc_finance',
				'scenario' => 'finance',
				'landing' => 'finance-dashboard',
				'state' => 'active',
			],
			'NGT-DEMO-C0001' => [
				'email' => 'demo.compliance@nextgen.local',
				'name'  => 'Demo Compliance Officer',
				'role'  => 'ngc_support',
				'scenario' => 'compliance',
				'landing' => 'ops-dashboard',
				'state' => 'active',
				'persona_type' => 'compliance',
			],
			'NGT-DEMO-SFG0001' => [
				'email' => 'demo.safeguarding@nextgen.local',
				'name'  => 'Demo Safeguarding Officer',
				'role'  => 'ngc_support',
				'scenario' => 'safeguarding',
				'landing' => 'ngc-safeguarding',
				'state' => 'active',
				'persona_type' => 'safeguarding',
			],
			'NGT-DEMO-SUP0001' => [
				'email' => 'demo.support@nextgen.local',
				'name'  => 'Demo Support Agent',
				'role'  => 'ngc_support',
				'scenario' => 'support',
				'landing' => 'ops-dashboard',
				'state' => 'active',
			],
			'NGT-DEMO-SEC0001' => [
				'email' => 'demo.security@nextgen.local',
				'name'  => 'Demo Security Analyst',
				'role'  => 'ngc_support',
				'scenario' => 'security',
				'landing' => 'ops-dashboard',
				'state' => 'active',
				'persona_type' => 'security',
			],
			'NGT-DEMO-FRD0001' => [
				'email' => 'demo.fraud@nextgen.local',
				'name'  => 'Demo Fraud Analyst',
				'role'  => 'ngc_support',
				'scenario' => 'fraud',
				'landing' => 'ngc-fraud-cases',
				'state' => 'active',
				'persona_type' => 'fraud',
			],
			'NGT-DEMO-AUD0001' => [
				'email' => 'demo.auditor@nextgen.local',
				'name'  => 'Demo Read-Only Auditor',
				'role'  => 'ngc_support',
				'scenario' => 'auditor',
				'landing' => 'audit-log',
				'state' => 'read_only',
				'persona_type' => 'auditor',
			],
			'NGT-DEMO-AI0001' => [
				'email' => 'demo.aiops@nextgen.local',
				'name'  => 'Demo AI Operations Admin',
				'role'  => 'administrator',
				'scenario' => 'ai-ops',
				'landing' => 'agent-ops',
				'state' => 'active',
				'persona_type' => 'ai_ops',
			],
		];
	}

	/**
	 * Ensure all personas exist with known password + demo meta.
	 *
	 * @return array<string, int> Stable ID → user ID.
	 */
	public static function ensure_users() {
		$password = NGC_Demo_Env::demo_password();
		$map      = [];
		foreach ( self::personas() as $stable_id => $spec ) {
			$user_id = 0;
			if ( class_exists( 'NGC_Workflow_Orchestrator' ) ) {
				$user_id = (int) NGC_Workflow_Orchestrator::ensure_user_role(
					$spec['email'],
					$spec['name'],
					$spec['role']
				);
			}
			if ( ! $user_id ) {
				$existing = get_user_by( 'email', $spec['email'] );
				$user_id  = $existing ? (int) $existing->ID : 0;
			}
			if ( ! $user_id ) {
				continue;
			}
			wp_set_password( $password, $user_id );
			$user = get_user_by( 'id', $user_id );
			if ( $user && ! in_array( $spec['role'], (array) $user->roles, true ) ) {
				$user->set_role( $spec['role'] );
			}
			$meta = array_merge(
				NGC_Demo_Env::demo_meta( (string) $spec['scenario'] ),
				[
					'ngc_is_demo_user'     => '1',
					'ngc_demo_stable_id'   => $stable_id,
					'ngc_demo_persona'     => $spec['persona_type'] ?? $spec['scenario'],
					'ngc_journey_state'    => $spec['state'],
					'ngc_demo_landing'     => $spec['landing'],
					'phone'               => '+2782000' . substr( preg_replace( '/\D/', '', $stable_id ) ?: '1000', -4 ),
					'province'            => 'Gauteng',
				]
			);
			foreach ( $meta as $k => $v ) {
				update_user_meta( $user_id, $k, $v );
			}
			if ( ! empty( $spec['subjects'] ) ) {
				update_user_meta( $user_id, 'ngc_subjects', $spec['subjects'] );
				update_user_meta( $user_id, 'tutor_subjects', implode( ', ', $spec['subjects'] ) );
			}
			if ( 'suspended' === ( $spec['state'] ?? '' ) ) {
				update_user_meta( $user_id, 'ngc_tutor_status', 'suspended' );
				update_user_meta( $user_id, 'ngc_suspension_reason', 'Demo suspension — policy review' );
			}
			if ( 'verified_active' === ( $spec['state'] ?? '' ) ) {
				update_user_meta( $user_id, 'ngc_tutor_status', 'verified' );
				update_user_meta( $user_id, 'ngc_verification_status', 'approved' );
			}
			$map[ $stable_id ] = $user_id;
		}
		update_option( 'ngc_demo_user_map', $map, false );
		return $map;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function directory_for_admin() {
		$password = NGC_Demo_Env::is_demo_mode() ? NGC_Demo_Env::demo_password() : '(hidden — enable demo mode)';
		$map      = get_option( 'ngc_demo_user_map', [] );
		$rows     = [];
		foreach ( self::personas() as $stable_id => $spec ) {
			$user_id = (int) ( $map[ $stable_id ] ?? 0 );
			if ( ! $user_id ) {
				$user = get_user_by( 'email', $spec['email'] );
				$user_id = $user ? (int) $user->ID : 0;
			}
			$rows[] = [
				'stable_id'   => $stable_id,
				'email'       => $spec['email'],
				'name'        => $spec['name'],
				'role'        => $spec['role'],
				'scenario'    => $spec['scenario'],
				'state'       => $spec['state'],
				'landing'     => $spec['landing'],
				'user_id'     => $user_id,
				'password'    => $password,
				'login_url'   => wp_login_url(),
			];
		}
		return $rows;
	}

	/**
	 * @param string $stable_id Stable ID.
	 * @return int
	 */
	public static function user_id( $stable_id ) {
		$map = get_option( 'ngc_demo_user_map', [] );
		if ( ! empty( $map[ $stable_id ] ) ) {
			return (int) $map[ $stable_id ];
		}
		$personas = self::personas();
		if ( empty( $personas[ $stable_id ] ) ) {
			return 0;
		}
		$user = get_user_by( 'email', $personas[ $stable_id ]['email'] );
		return $user ? (int) $user->ID : 0;
	}
}
