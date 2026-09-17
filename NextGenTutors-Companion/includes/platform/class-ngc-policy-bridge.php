<?php
/**
 * RAD Policy Bridge — privileged capability invokes → policy engines (default DENY).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps capability invocations to NGC_Agent_Policy_Engine / NGC_Authz_Matrix.
 */
final class NGC_Policy_Bridge {

	public const ALLOW             = 'ALLOW';
	public const DENY              = 'DENY';
	public const CHALLENGE         = 'CHALLENGE';
	public const REQUIRE_APPROVAL  = 'REQUIRE_APPROVAL';
	public const ALLOW_WITH_LIMITS = 'ALLOW_WITH_LIMITS';
	public const ESCALATE          = 'ESCALATE';

	/**
	 * Bootstrap hooks.
	 */
	public static function init() {
		// Proof path: agent control-plane actions also evaluate via bridge when capability exists.
		add_filter( 'ngc_agent_pre_policy', [ __CLASS__, 'filter_agent_pre_policy' ], 10, 3 );
	}

	/**
	 * Evaluate whether an actor may invoke a capability.
	 *
	 * @param string               $capability_id Capability id.
	 * @param array<string, mixed> $context       Actor/tenant/resource context.
	 * @return array{decision:string,reason:string,capability:?array,policy_version:string}
	 */
	public static function decide( $capability_id, array $context = [] ) {
		$capability_id = is_string( $capability_id ) ? $capability_id : '';
		$cap             = class_exists( 'NGC_Capability_Registry' ) ? NGC_Capability_Registry::get( $capability_id ) : null;

		if ( ! $cap ) {
			return self::result( self::DENY, 'Unknown capability (default DENY)', null );
		}

		$actor_type = (string) ( $context['actor_type'] ?? 'human' );
		$operation  = (string) ( $context['operation'] ?? 'invoke' );
		$actor_id   = (int) ( $context['actor_user_id'] ?? 0 );
		if ( $actor_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$actor_id = (int) get_current_user_id();
		}

		// Machine/agent path: reuse agent policy engine when action maps 1:1 or via alias.
		if ( in_array( $actor_type, [ 'agent', 'service', 'machine' ], true ) && class_exists( 'NGC_Agent_Policy_Engine' ) ) {
			$action = (string) ( $context['policy_action'] ?? $capability_id );
			$eval   = NGC_Agent_Policy_Engine::evaluate(
				$action,
				[
					'agent_id'       => (string) ( $context['actor'] ?? $context['agent_id'] ?? '' ),
					'autonomy_level' => (int) ( $context['autonomy_level'] ?? 0 ),
					'environment'    => (string) ( $context['environment'] ?? 'production' ),
					'actor_user_id'  => (int) ( $context['actor_user_id'] ?? get_current_user_id() ),
				]
			);
			$decision = (string) ( $eval['decision'] ?? self::DENY );
			return self::result( $decision, (string) ( $eval['reason'] ?? 'agent policy' ), $cap, $eval );
		}

		// Human path: capability requiredPermissions + authz matrix.
		$perms = (array) ( $cap['requiredPermissions'] ?? [] );
		foreach ( $perms as $perm ) {
			$perm = (string) $perm;
			if ( $perm === '' || $perm === 'read' ) {
				continue;
			}
			if ( class_exists( 'NGC_Authz_Matrix' ) && ! NGC_Authz_Matrix::can( $perm, $operation ) ) {
				if ( ! self::actor_can( $actor_id, $perm ) && ! self::actor_can( $actor_id, 'manage_options' ) ) {
					return self::result( self::DENY, 'Missing permission: ' . $perm, $cap );
				}
			} elseif ( ! self::actor_can( $actor_id, $perm ) && ! self::actor_can( $actor_id, 'manage_options' ) ) {
				return self::result( self::DENY, 'Missing capability: ' . $perm, $cap );
			}
		}

		/**
		 * Filter final bridge decision.
		 *
		 * @param array                $result Decision payload.
		 * @param string               $capability_id Capability.
		 * @param array<string, mixed> $context Context.
		 */
		$result = self::result( self::ALLOW, 'Authorized', $cap );
		return apply_filters( 'ngc_policy_bridge_decide', $result, $capability_id, $context );
	}

	/**
	 * @param int    $user_id User.
	 * @param string $cap     Capability.
	 * @return bool
	 */
	private static function actor_can( $user_id, $cap ) {
		$user_id = (int) $user_id;
		$cap     = (string) $cap;
		if ( '' === $cap ) {
			return false;
		}
		if ( $user_id > 0 && function_exists( 'user_can' ) ) {
			return user_can( $user_id, $cap );
		}
		return function_exists( 'current_user_can' ) && current_user_can( $cap );
	}

	/**
	 * Invoke a capability only if policy allows (does not execute domain work — returns decision + cap).
	 *
	 * @param string               $capability_id Capability id.
	 * @param array<string, mixed> $context       Context.
	 * @return array|WP_Error
	 */
	public static function authorize_invoke( $capability_id, array $context = [] ) {
		$decision = self::decide( $capability_id, $context );
		$deny     = [ self::DENY ];
		if ( in_array( $decision['decision'], $deny, true ) ) {
			return new WP_Error( 'ngc_policy_deny', $decision['reason'], [ 'status' => 403, 'decision' => $decision ] );
		}
		return $decision;
	}

	/**
	 * Domain entry authorization (TD-RAD-001).
	 *
	 * - Normal path: {@see authorize_invoke()}.
	 * - Trusted system path (WooCommerce ITN / cron): capability must exist; no interactive user ACL.
	 *
	 * @param string               $capability_id Capability id.
	 * @param array<string, mixed> $context       Context (`trusted_system` => true for webhooks).
	 * @return array|WP_Error
	 */
	public static function authorize_domain( $capability_id, array $context = [] ) {
		if ( ! empty( $context['trusted_system'] ) ) {
			$cap = class_exists( 'NGC_Capability_Registry' ) ? NGC_Capability_Registry::get( $capability_id ) : null;
			if ( ! $cap ) {
				return new WP_Error(
					'ngc_policy_deny',
					'Unknown capability (default DENY)',
					[ 'status' => 403 ]
				);
			}
			return [
				'decision'       => self::ALLOW,
				'reason'         => 'Trusted system invoke',
				'capability'     => $cap,
				'policy_version' => 'rad-bridge-1.0',
			];
		}
		return self::authorize_invoke( $capability_id, $context );
	}

	/**
	 * Optional pre-policy filter hook for agents (identity passthrough unless capability mapped).
	 *
	 * @param null|array           $pre     Existing.
	 * @param string               $action  Action id.
	 * @param array<string, mixed> $context Context.
	 * @return null|array
	 */
	public static function filter_agent_pre_policy( $pre, $action, $context ) {
		if ( null !== $pre ) {
			return $pre;
		}
		if ( ! class_exists( 'NGC_Capability_Registry' ) || ! NGC_Capability_Registry::has( $action ) ) {
			return $pre;
		}
		$ctx                = is_array( $context ) ? $context : [];
		$ctx['actor_type']  = 'agent';
		$ctx['policy_action'] = $action;
		return self::decide( $action, $ctx );
	}

	/**
	 * @param string                    $decision Decision.
	 * @param string                    $reason   Reason.
	 * @param array<string, mixed>|null $cap      Capability.
	 * @param array<string, mixed>      $extra    Extra.
	 * @return array{decision:string,reason:string,capability:?array,policy_version:string}
	 */
	private static function result( $decision, $reason, $cap, array $extra = [] ) {
		return array_merge(
			[
				'decision'       => $decision,
				'reason'         => $reason,
				'capability'     => $cap,
				'policy_version' => 'rad-bridge-1.0',
			],
			$extra
		);
	}
}
