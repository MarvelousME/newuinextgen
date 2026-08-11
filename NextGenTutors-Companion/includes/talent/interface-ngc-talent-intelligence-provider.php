<?php
/**
 * Talent Intelligence provider port.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaceable suitability provider — never auto-approves tutors.
 */
interface NGC_Talent_Intelligence_Provider_Interface {

	/**
	 * @return string
	 */
	public function slug();

	/**
	 * @return array{ok:bool,mode:string,message:string,details?:array}
	 */
	public function health();

	/**
	 * @param array<string,mixed> $candidate   Normalized candidate.
	 * @param array<string,mixed> $requirements Requirement profile.
	 * @param array<string,mixed> $options      Options.
	 * @return array<string,mixed>|WP_Error
	 */
	public function evaluate_match( array $candidate, array $requirements, array $options = [] );

	/**
	 * @param array<int,array<string,mixed>> $candidates   Eligible candidates only.
	 * @param array<string,mixed>            $requirements Requirements.
	 * @param array<string,mixed>            $options      Options.
	 * @return array<string,mixed>|WP_Error
	 */
	public function rank( array $candidates, array $requirements, array $options = [] );

	/**
	 * @param array<string,mixed> $evaluation Evaluation or id context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function explain( array $evaluation );
}
