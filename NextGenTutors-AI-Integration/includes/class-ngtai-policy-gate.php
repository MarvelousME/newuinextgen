<?php
/**
 * Outbound policy gate.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces kill switches, event policy, and hard action boundaries.
 */
final class NGTAI_Policy_Gate {

	/**
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $context Policy context.
	 * @return array{decision:string,reason:string,requires_approval:bool}
	 */
	public static function evaluate( $event_type, array $context = [] ) {
		$action = (string) ( $context['action'] ?? 'agent.recommend' );
		if ( in_array( $action, [ 'finance.refund.execute', 'finance.payout.release', 'tutor.approve', 'tutor.reject', 'user.delete', 'deploy.production' ], true ) ) {
			return self::decision( 'DENY', 'execution_action_prohibited', false, $event_type, $context );
		}

		$paused = NGTAI_Config::global_pause();
		if ( class_exists( 'NGC_Agent_Control_Plane' ) && method_exists( 'NGC_Agent_Control_Plane', 'is_globally_paused' ) ) {
			$paused = $paused || NGC_Agent_Control_Plane::is_globally_paused();
		}
		if ( $paused ) {
			return self::decision( 'DENY', 'global_pause_active', false, $event_type, $context );
		}

		$schema = self::schema( $event_type );
		if ( empty( $schema ) || empty( $schema['external_delivery_allowed'] ) ) {
			return self::decision( 'DENY', 'external_delivery_not_allowed', false, $event_type, $context );
		}

		if ( class_exists( 'NGC_Agent_Policy_Engine' ) && method_exists( 'NGC_Agent_Policy_Engine', 'evaluate' ) ) {
			$result   = (array) NGC_Agent_Policy_Engine::evaluate( 'agent.recommend', $context );
			$decision = strtoupper( (string) ( $result['decision'] ?? 'DENY' ) );
			if ( 'DENY' === $decision || 'REQUIRE_APPROVAL' === $decision ) {
				return self::decision(
					$decision,
					(string) ( $result['reason'] ?? 'companion_policy' ),
					'REQUIRE_APPROVAL' === $decision,
					$event_type,
					$context
				);
			}
		} elseif ( ! empty( $schema['policy_required'] ) ) {
			return self::decision( 'REQUIRE_APPROVAL', 'policy_engine_unavailable', true, $event_type, $context );
		}

		return self::decision( 'ALLOW', 'policy_allowed', false, $event_type, $context );
	}

	/** @param string $event_type Type. @return array<string,mixed> */
	private static function schema( $event_type ) {
		if ( method_exists( 'NGTAI_Config', 'event_schemas' ) ) {
			$schemas = (array) NGTAI_Config::event_schemas();
		} else {
			$schemas = (array) include dirname( __DIR__ ) . '/config/event-schemas.php';
		}
		return (array) ( $schemas['events'][ $event_type ] ?? [] );
	}

	/**
	 * @param string              $decision Decision.
	 * @param string              $reason Reason.
	 * @param bool                $requires_approval Approval flag.
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $context Context.
	 * @return array{decision:string,reason:string,requires_approval:bool}
	 */
	private static function decision( $decision, $reason, $requires_approval, $event_type, array $context ) {
		if ( class_exists( 'NGTAI_Audit' ) ) {
			NGTAI_Audit::log(
				'DENY' === $decision ? 'ngtai_policy_denied' : 'ngtai_policy_decision',
				[ 'decision' => $decision, 'reason' => $reason, 'event_type' => $event_type, 'action' => (string) ( $context['action'] ?? '' ) ],
				(string) ( $context['correlation_id'] ?? '' )
			);
		}
		return [ 'decision' => $decision, 'reason' => $reason, 'requires_approval' => (bool) $requires_approval ];
	}
}
