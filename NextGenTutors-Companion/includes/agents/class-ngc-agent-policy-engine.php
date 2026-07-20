<?php
/**
 * Agent policy engine — ALLOW / ALLOW_WITH_LIMITS / REQUIRE_APPROVAL / DENY / ESCALATE.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Versioned policy evaluation for autonomous agents.
 */
final class NGC_Agent_Policy_Engine {

	public const ALLOW               = 'ALLOW';
	public const ALLOW_WITH_LIMITS   = 'ALLOW_WITH_LIMITS';
	public const REQUIRE_APPROVAL    = 'REQUIRE_APPROVAL';
	public const DENY                = 'DENY';
	public const ESCALATE            = 'ESCALATE';

	/**
	 * Built-in policies (versioned). Override via filter ngc_agent_policies.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function policies() {
		$policies = [
			'agent.observe'              => [ 'decision' => self::ALLOW, 'max_autonomy' => 0 ],
			'agent.recommend'            => [ 'decision' => self::ALLOW, 'max_autonomy' => 1 ],
			'agent.task.create'          => [ 'decision' => self::ALLOW, 'max_autonomy' => 1 ],
			'agent.case.create'          => [ 'decision' => self::ALLOW_WITH_LIMITS, 'max_autonomy' => 2 ],
			'agent.rate_limit.source'    => [ 'decision' => self::ALLOW_WITH_LIMITS, 'max_autonomy' => 3 ],
			'agent.mfa.challenge'        => [ 'decision' => self::ALLOW_WITH_LIMITS, 'max_autonomy' => 3 ],
			'finance.refund.execute'     => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4, 'approver_roles' => [ 'administrator', 'ngc_finance' ] ],
			'finance.payout.release'     => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4, 'approver_roles' => [ 'administrator', 'ngc_finance' ] ],
			'finance.adjustment.post'    => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4 ],
			'tutor.approve'              => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4 ],
			'tutor.reject'               => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4 ],
			'user.suspend.permanent'     => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4 ],
			'data.delete'                => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4 ],
			'safeguarding.escalate'      => [ 'decision' => self::ESCALATE, 'max_autonomy' => 3 ],
			'agent.secret.exfiltrate'     => [ 'decision' => self::DENY, 'max_autonomy' => 5 ],
			'agent.audit.disable'        => [ 'decision' => self::DENY, 'max_autonomy' => 5 ],
			'agent.permission.self_grant'=> [ 'decision' => self::DENY, 'max_autonomy' => 5 ],
			'deploy.production'          => [ 'decision' => self::REQUIRE_APPROVAL, 'max_autonomy' => 4 ],
			'shell.unrestricted'         => [ 'decision' => self::DENY, 'max_autonomy' => 5 ],
		];

		return apply_filters( 'ngc_agent_policies', $policies );
	}

	/**
	 * Sanitize policy / agent identifiers (allows dots — unlike sanitize_key).
	 *
	 * @param string $id Raw id.
	 * @return string
	 */
	public static function sanitize_id( $id ) {
		$id = strtolower( (string) $id );
		return (string) preg_replace( '/[^a-z0-9._\-]/', '', $id );
	}

	/**
	 * Evaluate an agent action.
	 *
	 * @param string               $action_id Action policy key.
	 * @param array<string, mixed> $context   agent_id, autonomy_level, environment, financial_impact, etc.
	 * @return array{decision: string, reason: string, policy_id: string, requires_approval: bool}
	 */
	public static function evaluate( $action_id, $context = [] ) {
		$action_id = self::sanitize_id( $action_id );
		$policies  = self::policies();

		if ( class_exists( 'NGC_Agent_Control_Plane' ) && NGC_Agent_Control_Plane::is_globally_paused() ) {
			$result = [
				'decision'          => self::DENY,
				'reason'            => 'global_kill_switch',
				'policy_id'         => $action_id,
				'requires_approval' => false,
			];
			self::log_decision( $result, $context );
			return $result;
		}

		$agent_id = self::sanitize_id( (string) ( $context['agent_id'] ?? '' ) );
		if ( $agent_id && class_exists( 'NGC_Agent_Control_Plane' ) && NGC_Agent_Control_Plane::is_agent_paused( $agent_id ) ) {
			$result = [
				'decision'          => self::DENY,
				'reason'            => 'agent_paused',
				'policy_id'         => $action_id,
				'requires_approval' => false,
			];
			self::log_decision( $result, $context );
			return $result;
		}

		if ( ! isset( $policies[ $action_id ] ) ) {
			$result = [
				'decision'          => self::DENY,
				'reason'            => 'unknown_policy',
				'policy_id'         => $action_id,
				'requires_approval' => false,
			];
			self::log_decision( $result, $context );
			return $result;
		}

		$policy   = $policies[ $action_id ];
		$decision = (string) ( $policy['decision'] ?? self::DENY );
		$autonomy = (int) ( $context['autonomy_level'] ?? 0 );
		$max      = (int) ( $policy['max_autonomy'] ?? 0 );

		if ( self::DENY === $decision ) {
			$result = [
				'decision'          => self::DENY,
				'reason'            => 'policy_deny',
				'policy_id'         => $action_id,
				'requires_approval' => false,
			];
			self::log_decision( $result, $context );
			return $result;
		}

		if ( $autonomy > $max ) {
			$result = [
				'decision'          => self::REQUIRE_APPROVAL,
				'reason'            => 'autonomy_exceeds_policy',
				'policy_id'         => $action_id,
				'requires_approval' => true,
			];
			self::log_decision( $result, $context );
			return $result;
		}

		$env = sanitize_key( (string) ( $context['environment'] ?? 'local' ) );
		if ( 'production' === $env && in_array( $decision, [ self::ALLOW, self::ALLOW_WITH_LIMITS ], true ) && $max >= 3 && empty( $context['production_authorized'] ) ) {
			if ( in_array( $action_id, [ 'agent.rate_limit.source', 'agent.mfa.challenge' ], true ) ) {
				// Operational Level 3 still allowed when policy says so.
			} elseif ( $max >= 4 ) {
				$decision = self::REQUIRE_APPROVAL;
			}
		}

		$result = [
			'decision'          => $decision,
			'reason'            => 'ok',
			'policy_id'         => $action_id,
			'requires_approval' => in_array( $decision, [ self::REQUIRE_APPROVAL, self::ESCALATE ], true ),
			'approver_roles'    => $policy['approver_roles'] ?? [ 'administrator' ],
		];
		self::log_decision( $result, $context );
		return $result;
	}

	/**
	 * @param array<string, mixed> $result  Decision.
	 * @param array<string, mixed> $context Context.
	 */
	private static function log_decision( array $result, array $context ) {
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'agent_policy_decision',
				'agent_policy',
				0,
				[
					'decision'  => $result['decision'],
					'reason'    => $result['reason'],
					'policy_id' => $result['policy_id'],
					'agent_id'  => $context['agent_id'] ?? '',
					'action'    => $result['policy_id'],
				],
				(int) ( $context['actor_user_id'] ?? 0 )
			);
		}
		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info(
				'agent_policy',
				sprintf( 'Policy %s → %s (%s)', $result['policy_id'], $result['decision'], $result['reason'] ),
				$result
			);
		}
	}
}
