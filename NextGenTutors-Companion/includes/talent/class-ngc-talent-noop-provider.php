<?php
/**
 * No-op talent provider — safe when DISABLED/DEGRADED.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Empty results; never throws into registration/booking paths.
 */
final class NGC_Talent_Noop_Provider implements NGC_Talent_Intelligence_Provider_Interface {

	/**
	 * @return string
	 */
	public function slug() {
		return 'noop';
	}

	/**
	 * @return array{ok:bool,mode:string,message:string,details?:array}
	 */
	public function health() {
		return [
			'ok'      => true,
			'mode'    => (string) ( NGC_Talent_Settings::get()['mode'] ?? 'DISABLED' ),
			'message' => 'Talent Intelligence inactive (noop).',
			'details' => [ 'provider' => 'noop', 'auto_approve_forbidden' => true ],
		];
	}

	/**
	 * @param array<string,mixed> $candidate    Candidate.
	 * @param array<string,mixed> $requirements Requirements.
	 * @param array<string,mixed> $options      Options.
	 * @return array<string,mixed>
	 */
	public function evaluate_match( array $candidate, array $requirements, array $options = [] ) {
		unset( $candidate, $requirements, $options );
		return [
			'ok'             => true,
			'score'          => null,
			'recommendation' => 'INSUFFICIENT_DATA',
			'components'     => [],
			'matchedCriteria'=> [],
			'gaps'           => [],
			'evidence'       => [],
			'warnings'       => [ 'Talent Intelligence disabled' ],
			'provider'       => 'noop',
			'modelVersion'   => NGC_Talent_Settings::MODEL_VERSION,
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $candidates   Candidates.
	 * @param array<string,mixed>            $requirements Requirements.
	 * @param array<string,mixed>            $options      Options.
	 * @return array<string,mixed>
	 */
	public function rank( array $candidates, array $requirements, array $options = [] ) {
		unset( $requirements, $options );
		return [
			'ok'       => true,
			'ranked'   => array_values( $candidates ),
			'provider' => 'noop',
			'warnings' => [ 'Talent Intelligence disabled — original order preserved' ],
		];
	}

	/**
	 * @param array<string,mixed> $evaluation Evaluation.
	 * @return array<string,mixed>
	 */
	public function explain( array $evaluation ) {
		return array_merge(
			[
				'ok'       => true,
				'provider' => 'noop',
			],
			$evaluation
		);
	}
}
