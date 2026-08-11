<?php
declare(strict_types=1);

namespace NGTBM\Domain\Authorization;

/**
 * Authorization: WP cap → optional NGC_Policy_Bridge → resource context.
 */
final class PolicyGate {

	/**
	 * @param array<string,mixed> $context
	 * @return true|\WP_Error
	 */
	public static function authorize( string $capability, array $context = [] ) {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return new \WP_Error( 'ngtbm_unauthenticated', __( 'Authentication required.', 'nextgentutors-beyond-measure' ), [ 'status' => 401 ] );
		}

		$allowed = current_user_can( $capability ) || current_user_can( 'manage_options' );
		if ( ! $allowed && class_exists( 'NGC_Authz_Matrix' ) ) {
			$allowed = \NGC_Authz_Matrix::can( $capability, (string) ( $context['resource'] ?? '' ), (int) ( $context['resource_id'] ?? 0 ) );
		}

		if ( ! $allowed ) {
			return new \WP_Error( 'ngtbm_forbidden', __( 'Capability denied.', 'nextgentutors-beyond-measure' ), [ 'status' => 403 ] );
		}

		// Object-level / contextual policy.
		$safeguard = strtoupper( (string) ( $context['safeguarding_state'] ?? '' ) );
		if ( $safeguard === 'LOCKED' && ! current_user_can( 'ngt_talent_override' ) && ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'ngtbm_policy_deny', __( 'Resource is safeguarding-locked.', 'nextgentutors-beyond-measure' ), [ 'status' => 403 ] );
		}

		if ( class_exists( 'NGC_Policy_Bridge' ) && ! empty( $context['capability_id'] ) ) {
			$decision = \NGC_Policy_Bridge::decide(
				(string) $context['capability_id'],
				array_merge(
					$context,
					[
						'actor_type'    => 'human',
						'actor_user_id' => $user_id,
						'policy_action' => (string) $context['capability_id'],
					]
				)
			);
			if ( ( $decision['decision'] ?? '' ) === \NGC_Policy_Bridge::DENY ) {
				return new \WP_Error( 'ngtbm_policy_deny', (string) ( $decision['reason'] ?? 'Denied by policy' ), [ 'status' => 403 ] );
			}
		}

		return true;
	}

	public static function require_cap( string $capability ): bool {
		$result = self::authorize( $capability );
		return ! is_wp_error( $result );
	}
}
