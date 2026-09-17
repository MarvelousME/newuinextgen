<?php
/**
 * Parent vs adult-student commercial identity.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces who owns the WooCommerce customer record.
 */
class NGC_Session_Identity {

	/**
	 * Whether the user is treated as a minor learner (cannot be billing customer).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_minor_learner( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		if ( function_exists( 'get_user_meta' ) ) {
			if ( get_user_meta( $user_id, 'ngc_is_minor', true ) || get_user_meta( $user_id, 'ngt_is_minor', true ) ) {
				return true;
			}
			$parent = (int) get_user_meta( $user_id, 'ngc_parent_user_id', true );
			if ( ! $parent ) {
				$parent = (int) get_user_meta( $user_id, 'ngt_parent_user_id', true );
			}
			if ( $parent > 0 && $parent !== $user_id ) {
				return true;
			}
		}
		if ( function_exists( 'get_userdata' ) ) {
			$user = get_userdata( $user_id );
			if ( $user && in_array( 'child_learner', (array) $user->roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Resolve billing customer for a tutoring purchase.
	 *
	 * @param int $actor_user_id Logged-in purchaser.
	 * @param int $student_id    Learner.
	 * @return array{customer_user_id:int,student_user_id:int,parent_user_id:int,mode:string}
	 */
	public static function resolve_parties( $actor_user_id, $student_id = 0 ) {
		$actor   = (int) $actor_user_id;
		$student = (int) $student_id ?: $actor;
		$parent  = 0;

		if ( function_exists( 'get_user_meta' ) && $student ) {
			$parent = (int) get_user_meta( $student, 'ngc_parent_user_id', true );
			if ( ! $parent ) {
				$parent = (int) get_user_meta( $student, 'ngt_parent_user_id', true );
			}
		}

		if ( self::is_minor_learner( $student ) ) {
			$customer = $parent ?: $actor;
			return [
				'customer_user_id' => $customer,
				'student_user_id'  => $student,
				'parent_user_id'   => $customer,
				'mode'             => 'parent_pays_for_child',
			];
		}

		if ( $parent && $parent === $actor && $student !== $actor ) {
			return [
				'customer_user_id' => $actor,
				'student_user_id'  => $student,
				'parent_user_id'   => $actor,
				'mode'             => 'parent_pays_for_child',
			];
		}

		return [
			'customer_user_id' => $actor,
			'student_user_id'  => $student,
			'parent_user_id'   => $parent,
			'mode'             => 'adult_student_self_purchase',
		];
	}

	/**
	 * Reject using a minor as WooCommerce customer.
	 *
	 * @param int $customer_user_id Proposed customer.
	 * @return true
	 * @throws NGC_Session_Validation_Exception Minor billing customer.
	 */
	public static function assert_billing_customer( $customer_user_id ) {
		if ( self::is_minor_learner( (int) $customer_user_id ) ) {
			throw new NGC_Session_Validation_Exception(
				'A child learner cannot be the billing customer.',
				'ngc_child_cannot_be_customer',
				[ 'customer_user_id' => (int) $customer_user_id ]
			);
		}
		return true;
	}
}
