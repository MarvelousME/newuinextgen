<?php
/**
 * Bridge-native tutoring suitability scorer (ngt-talent-suitability-v1).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multi-factor explainable scorer — decision support only.
 */
final class NGC_Talent_Bridge_Rules_Provider implements NGC_Talent_Intelligence_Provider_Interface {

	/**
	 * @return string
	 */
	public function slug() {
		return 'bridge_rules_v1';
	}

	/**
	 * @return array{ok:bool,mode:string,message:string,details?:array}
	 */
	public function health() {
		return [
			'ok'      => true,
			'mode'    => (string) ( NGC_Talent_Settings::get()['mode'] ?? NGC_Talent_Settings::MODE_NATIVE ),
			'message' => 'Bridge-native suitability scorer ready',
			'details' => [
				'provider'     => $this->slug(),
				'modelVersion' => NGC_Talent_Settings::MODEL_VERSION,
				'weightsVersion' => NGC_Talent_Settings::WEIGHTS_VERSION,
				'auto_approve_forbidden' => true,
			],
		];
	}

	/**
	 * @param array<string,mixed> $candidate    Candidate.
	 * @param array<string,mixed> $requirements Requirements.
	 * @param array<string,mixed> $options      Options.
	 * @return array<string,mixed>
	 */
	public function evaluate_match( array $candidate, array $requirements, array $options = [] ) {
		$scrub_c = NGC_Talent_Fairness::scrub( $candidate );
		$scrub_r = NGC_Talent_Fairness::scrub( $requirements );
		$candidate    = $scrub_c['clean'];
		$requirements = $scrub_r['clean'];
		$warnings     = [];
		if ( $scrub_c['stripped'] || $scrub_r['stripped'] ) {
			$warnings[] = 'Stripped protected fields: ' . implode( ',', array_merge( $scrub_c['stripped'], $scrub_r['stripped'] ) );
		}

		$weights = NGC_Talent_Settings::weights();
		$components = [];
		$matched = [];
		$gaps    = [];
		$evidence = [];

		$components[] = $this->coverage_component( 'subject', $weights['subject'], NGC_Talent_Profile_Helper::list_field( $requirements, 'subjects' ), NGC_Talent_Profile_Helper::list_field( $candidate, 'subjects' ), $matched, $gaps, $evidence );
		$components[] = $this->coverage_component( 'grade', $weights['grade'], NGC_Talent_Profile_Helper::list_field( $requirements, 'grades' ), NGC_Talent_Profile_Helper::list_field( $candidate, 'grades' ), $matched, $gaps, $evidence );
		$components[] = $this->coverage_component( 'curriculum', $weights['curriculum'], NGC_Talent_Profile_Helper::list_field( $requirements, 'curricula' ), NGC_Talent_Profile_Helper::list_field( $candidate, 'curricula' ), $matched, $gaps, $evidence, true );
		$components[] = $this->claim_component( 'qualification_claim', $weights['qualification_claim'], $requirements, $candidate, $matched, $gaps, $evidence );
		$components[] = $this->experience_component( $weights['teaching_experience'], $requirements, $candidate, $matched, $gaps, $evidence );
		$components[] = $this->coverage_component( 'skill', $weights['skill'], NGC_Talent_Profile_Helper::list_field( $requirements, 'skills' ), NGC_Talent_Profile_Helper::skills_of( $candidate ), $matched, $gaps, $evidence, true );
		$components[] = $this->coverage_component( 'language', $weights['language'], NGC_Talent_Profile_Helper::list_field( $requirements, 'languages' ), NGC_Talent_Profile_Helper::list_field( $candidate, 'languages' ), $matched, $gaps, $evidence, true );
		$components[] = $this->availability_component( $weights['availability'], $requirements, $candidate, $matched, $gaps, $evidence );
		$components[] = $this->location_delivery_component( $weights['location_delivery'], $requirements, $candidate, $matched, $gaps, $evidence );
		$components[] = $this->completeness_component( $weights['profile_completeness'], $candidate, $evidence );

		$num = 0.0;
		$den = 0.0;
		$present = 0;
		foreach ( $components as $c ) {
			if ( 'INSUFFICIENT_DATA' === ( $c['status'] ?? '' ) && empty( $options['score_missing_as_zero'] ) ) {
				$warnings[] = 'Component ' . $c['key'] . ' excluded (insufficient data)';
				continue;
			}
			$w = (float) ( $c['weight'] ?? 0 );
			$s = (float) ( $c['score'] ?? 0 );
			$num += $w * $s;
			$den += $w;
			++$present;
		}

		$structured = $den > 0 ? ( $num / $den ) : 0.0;
		$text_sim   = null;
		if ( ! empty( $options['text_similarity'] ) ) {
			$text_sim   = max( 0, min( 100, (float) $options['text_similarity'] ) );
			$structured = ( 0.85 * $structured ) + ( 0.15 * $text_sim );
			$evidence[] = [ 'type' => 'text_similarity', 'score' => $text_sim, 'blend' => '0.85 structured + 0.15 text' ];
		}

		$completeness = $this->profile_completeness_ratio( $candidate );
		$threshold    = (float) ( NGC_Talent_Settings::get()['completeness_threshold'] ?? 0.4 );
		$recommendation = $this->recommend( $structured, $completeness, $threshold, $gaps );

		$safeguarding = [
			'identity'   => (string) ( $candidate['safeguarding']['identity'] ?? 'NOT_APPLICABLE' ),
			'background' => (string) ( $candidate['safeguarding']['background'] ?? 'NOT_APPLICABLE' ),
			'references' => (string) ( $candidate['safeguarding']['references'] ?? 'NOT_APPLICABLE' ),
			'note'       => 'Safeguarding is independent of suitability score',
		];

		$result = [
			'ok'                   => true,
			'score'                => round( $structured, 2 ),
			'recommendation'       => $recommendation,
			'components'           => $components,
			'matchedCriteria'      => $matched,
			'gaps'                 => $gaps,
			'evidence'             => $evidence,
			'warnings'             => $warnings,
			'safeguarding'         => $safeguarding,
			'modelVersion'         => NGC_Talent_Settings::MODEL_VERSION,
			'weightConfigVersion'  => NGC_Talent_Settings::WEIGHTS_VERSION,
			'provider'             => $this->slug(),
			'autoApproveForbidden' => true,
		];

		$safe = NGC_Talent_Fairness::assert_explanation_safe( $result );
		if ( is_wp_error( $safe ) ) {
			$result['recommendation'] = 'INSUFFICIENT_DATA';
			$result['warnings'][]     = $safe->get_error_message();
			$result['score']          = null;
		}

		$input_hash = hash( 'sha256', (string) wp_json_encode( [ $candidate, $requirements, NGC_Talent_Settings::weights() ] ) );
		$result['inputSnapshotHash'] = 'sha256:' . $input_hash;
		return $result;
	}

	/**
	 * @param array<int,array<string,mixed>> $candidates   Candidates.
	 * @param array<string,mixed>            $requirements Requirements.
	 * @param array<string,mixed>            $options      Options.
	 * @return array<string,mixed>
	 */
	public function rank( array $candidates, array $requirements, array $options = [] ) {
		$ranked = [];
		foreach ( $candidates as $c ) {
			$eval = $this->evaluate_match( is_array( $c ) ? $c : [], $requirements, $options );
			$row  = is_array( $c ) ? $c : [];
			$row['talentScore']          = $eval['score'] ?? null;
			$row['talentRecommendation'] = $eval['recommendation'] ?? 'INSUFFICIENT_DATA';
			$row['talentEvaluation']     = $eval;
			$ranked[] = $row;
		}
		usort(
			$ranked,
			static function ( $a, $b ) {
				$sa = null === ( $a['talentScore'] ?? null ) ? -1 : (float) $a['talentScore'];
				$sb = null === ( $b['talentScore'] ?? null ) ? -1 : (float) $b['talentScore'];
				return $sb <=> $sa;
			}
		);
		return [
			'ok'       => true,
			'ranked'   => $ranked,
			'provider' => $this->slug(),
			'modelVersion' => NGC_Talent_Settings::MODEL_VERSION,
		];
	}

	/**
	 * @param array<string,mixed> $evaluation Evaluation.
	 * @return array<string,mixed>
	 */
	public function explain( array $evaluation ) {
		$evaluation['provider'] = $this->slug();
		$evaluation['ok']       = true;
		return $evaluation;
	}

	/**
	 * @param string               $key Component key.
	 * @param float                $weight Weight.
	 * @param string[]             $required Required.
	 * @param string[]             $have Have.
	 * @param array<int,mixed>     $matched Matched collector.
	 * @param array<int,mixed>     $gaps Gaps collector.
	 * @param array<int,mixed>     $evidence Evidence.
	 * @param bool                 $optional_empty If required empty → INSUFFICIENT_DATA.
	 * @return array<string,mixed>
	 */
	private function coverage_component( $key, $weight, array $required, array $have, array &$matched, array &$gaps, array &$evidence, $optional_empty = false ) {
		$required = NGC_Talent_Profile_Helper::normalize_list( $required );
		$have     = NGC_Talent_Profile_Helper::normalize_list( $have );
		if ( empty( $required ) ) {
			return [
				'key'    => $key,
				'weight' => $weight,
				'score'  => 0,
				'status' => $optional_empty ? 'INSUFFICIENT_DATA' : 'NOT_APPLICABLE',
			];
		}
		$hit = [];
		$miss = [];
		foreach ( $required as $r ) {
			if ( NGC_Talent_Profile_Helper::list_has( $have, $r ) ) {
				$hit[] = $r;
				$matched[] = [ 'criterion' => $key, 'value' => $r, 'status' => 'MATCH' ];
			} else {
				$miss[] = $r;
				$gaps[] = [ 'criterion' => $key, 'value' => $r, 'status' => 'MISSING' ];
			}
		}
		$score = ( count( $hit ) / max( 1, count( $required ) ) ) * 100;
		$status = 100.0 === (float) $score ? 'MATCH' : ( $score > 0 ? 'PARTIAL' : 'MISSING' );
		$evidence[] = [ 'type' => $key, 'matched' => $hit, 'missing' => $miss, 'score' => round( $score, 2 ) ];
		return [
			'key'    => $key,
			'weight' => $weight,
			'score'  => round( $score, 2 ),
			'status' => $status,
		];
	}

	/**
	 * @param string               $key Key.
	 * @param float                $weight Weight.
	 * @param array<string,mixed>  $requirements Requirements.
	 * @param array<string,mixed>  $candidate Candidate.
	 * @param array<int,mixed>     $matched Matched.
	 * @param array<int,mixed>     $gaps Gaps.
	 * @param array<int,mixed>     $evidence Evidence.
	 * @return array<string,mixed>
	 */
	private function claim_component( $key, $weight, array $requirements, array $candidate, array &$matched, array &$gaps, array &$evidence ) {
		$needed = NGC_Talent_Profile_Helper::list_field( $requirements, 'qualifications' );
		$claims = NGC_Talent_Profile_Helper::list_field( $candidate, 'qualifications' );
		$text   = strtolower( (string) ( $candidate['bio'] ?? $candidate['experience'] ?? '' ) );
		if ( empty( $needed ) ) {
			return [ 'key' => $key, 'weight' => $weight, 'score' => 0, 'status' => 'INSUFFICIENT_DATA' ];
		}
		$hit = 0;
		foreach ( $needed as $n ) {
			$nlow = strtolower( (string) $n );
			$found = NGC_Talent_Profile_Helper::list_has( $claims, $nlow ) || ( '' !== $nlow && false !== strpos( $text, $nlow ) );
			if ( $found ) {
				++$hit;
				$matched[] = [ 'criterion' => $key, 'value' => $n, 'status' => 'CLAIMED' ];
			} else {
				$gaps[] = [ 'criterion' => $key, 'value' => $n, 'status' => 'NOT_VERIFIED' ];
			}
		}
		$score = ( $hit / count( $needed ) ) * 100;
		$evidence[] = [ 'type' => $key, 'note' => 'Claims only — never VERIFIED via NLP', 'score' => round( $score, 2 ) ];
		return [
			'key'    => $key,
			'weight' => $weight,
			'score'  => round( $score, 2 ),
			'status' => $hit ? 'CLAIMED' : 'NOT_VERIFIED',
		];
	}

	/**
	 * @param float               $weight Weight.
	 * @param array<string,mixed> $requirements Requirements.
	 * @param array<string,mixed> $candidate Candidate.
	 * @param array<int,mixed>    $matched Matched.
	 * @param array<int,mixed>    $gaps Gaps.
	 * @param array<int,mixed>    $evidence Evidence.
	 * @return array<string,mixed>
	 */
	private function experience_component( $weight, array $requirements, array $candidate, array &$matched, array &$gaps, array &$evidence ) {
		$min = isset( $requirements['experience_years_min'] ) ? (float) $requirements['experience_years_min'] : null;
		$yrs = isset( $candidate['experience_years'] ) ? (float) $candidate['experience_years'] : NGC_Talent_Profile_Helper::extract_years( (string) ( $candidate['bio'] ?? $candidate['experience'] ?? '' ) );
		if ( null === $min ) {
			return [ 'key' => 'teaching_experience', 'weight' => $weight, 'score' => 0, 'status' => 'INSUFFICIENT_DATA' ];
		}
		if ( null === $yrs ) {
			$gaps[] = [ 'criterion' => 'teaching_experience', 'value' => 'years unknown', 'status' => 'INSUFFICIENT_DATA' ];
			return [ 'key' => 'teaching_experience', 'weight' => $weight, 'score' => 0, 'status' => 'INSUFFICIENT_DATA' ];
		}
		if ( $yrs >= $min ) {
			$matched[] = [ 'criterion' => 'teaching_experience', 'value' => $yrs . 'y', 'status' => 'MATCH' ];
			$score = 100;
			$status = 'MATCH';
		} elseif ( $yrs >= max( 0, $min - 2 ) ) {
			$matched[] = [ 'criterion' => 'teaching_experience', 'value' => $yrs . 'y', 'status' => 'PARTIAL' ];
			$score = 60;
			$status = 'PARTIAL';
		} else {
			$gaps[] = [ 'criterion' => 'teaching_experience', 'value' => $yrs . 'y < ' . $min, 'status' => 'MISSING' ];
			$score = max( 0, ( $yrs / max( 1, $min ) ) * 100 );
			$status = 'MISSING';
		}
		$evidence[] = [ 'type' => 'teaching_experience', 'years' => $yrs, 'required_min' => $min ];
		return [ 'key' => 'teaching_experience', 'weight' => $weight, 'score' => round( $score, 2 ), 'status' => $status ];
	}

	/**
	 * @param float               $weight Weight.
	 * @param array<string,mixed> $requirements Requirements.
	 * @param array<string,mixed> $candidate Candidate.
	 * @param array<int,mixed>    $matched Matched.
	 * @param array<int,mixed>    $gaps Gaps.
	 * @param array<int,mixed>    $evidence Evidence.
	 * @return array<string,mixed>
	 */
	private function availability_component( $weight, array $requirements, array $candidate, array &$matched, array &$gaps, array &$evidence ) {
		unset( $evidence );
		if ( empty( $requirements['availability'] ) ) {
			return [ 'key' => 'availability', 'weight' => $weight, 'score' => 0, 'status' => 'INSUFFICIENT_DATA' ];
		}
		$ok = ! empty( $candidate['availability'] ) && ( true === $candidate['availability'] || 'available' === $candidate['availability'] || ! empty( $candidate['availability_slots'] ) );
		if ( $ok ) {
			$matched[] = [ 'criterion' => 'availability', 'status' => 'MATCH' ];
			return [ 'key' => 'availability', 'weight' => $weight, 'score' => 100, 'status' => 'MATCH' ];
		}
		$gaps[] = [ 'criterion' => 'availability', 'status' => 'UNKNOWN' ];
		return [ 'key' => 'availability', 'weight' => $weight, 'score' => 40, 'status' => 'PARTIAL' ];
	}

	/**
	 * @param float               $weight Weight.
	 * @param array<string,mixed> $requirements Requirements.
	 * @param array<string,mixed> $candidate Candidate.
	 * @param array<int,mixed>    $matched Matched.
	 * @param array<int,mixed>    $gaps Gaps.
	 * @param array<int,mixed>    $evidence Evidence.
	 * @return array<string,mixed>
	 */
	private function location_delivery_component( $weight, array $requirements, array $candidate, array &$matched, array &$gaps, array &$evidence ) {
		$score = 0;
		$parts = 0;
		$loc_req = (string) ( $requirements['location'] ?? $requirements['province'] ?? '' );
		$loc_have = (string) ( $candidate['location'] ?? $candidate['province'] ?? '' );
		if ( '' !== $loc_req ) {
			++$parts;
			if ( '' !== $loc_have && strtolower( $loc_req ) === strtolower( $loc_have ) ) {
				$score += 50;
				$matched[] = [ 'criterion' => 'location', 'value' => $loc_have, 'status' => 'MATCH' ];
			} else {
				$gaps[] = [ 'criterion' => 'location', 'value' => $loc_req, 'status' => 'MISSING' ];
			}
		}
		$modes_req = NGC_Talent_Profile_Helper::list_field( $requirements, 'deliveryModes' );
		if ( empty( $modes_req ) ) {
			$modes_req = NGC_Talent_Profile_Helper::list_field( $requirements, 'delivery_modes' );
		}
		$modes_have = NGC_Talent_Profile_Helper::list_field( $candidate, 'deliveryModes' );
		if ( empty( $modes_have ) ) {
			$modes_have = NGC_Talent_Profile_Helper::list_field( $candidate, 'delivery_modes' );
		}
		if ( ! empty( $modes_req ) ) {
			++$parts;
			$hit = false;
			foreach ( $modes_req as $m ) {
				if ( NGC_Talent_Profile_Helper::list_has( $modes_have, $m ) ) {
					$hit = true;
					$matched[] = [ 'criterion' => 'delivery_mode', 'value' => $m, 'status' => 'MATCH' ];
					break;
				}
			}
			if ( $hit ) {
				$score += 50;
			} else {
				$gaps[] = [ 'criterion' => 'delivery_mode', 'status' => 'MISSING' ];
			}
		}
		if ( 0 === $parts ) {
			return [ 'key' => 'location_delivery', 'weight' => $weight, 'score' => 0, 'status' => 'INSUFFICIENT_DATA' ];
		}
		$evidence[] = [ 'type' => 'location_delivery', 'score' => $score ];
		return [
			'key'    => 'location_delivery',
			'weight' => $weight,
			'score'  => (float) $score,
			'status' => $score >= 100 ? 'MATCH' : ( $score > 0 ? 'PARTIAL' : 'MISSING' ),
		];
	}

	/**
	 * @param float               $weight Weight.
	 * @param array<string,mixed> $candidate Candidate.
	 * @param array<int,mixed>    $evidence Evidence.
	 * @return array<string,mixed>
	 */
	private function completeness_component( $weight, array $candidate, array &$evidence ) {
		$ratio = $this->profile_completeness_ratio( $candidate );
		$evidence[] = [ 'type' => 'profile_completeness', 'ratio' => $ratio ];
		return [
			'key'    => 'profile_completeness',
			'weight' => $weight,
			'score'  => round( $ratio * 100, 2 ),
			'status' => $ratio >= 0.7 ? 'MATCH' : ( $ratio >= 0.4 ? 'PARTIAL' : 'INSUFFICIENT_DATA' ),
		];
	}

	/**
	 * @param array<string,mixed> $candidate Candidate.
	 * @return float
	 */
	private function profile_completeness_ratio( array $candidate ) {
		$fields = [ 'subjects', 'grades', 'bio', 'experience', 'province', 'location', 'languages', 'deliveryModes', 'delivery_modes', 'qualifications' ];
		$filled = 0;
		foreach ( $fields as $f ) {
			if ( ! empty( $candidate[ $f ] ) ) {
				++$filled;
			}
		}
		return $filled / count( $fields );
	}

	/**
	 * @param float  $score Score.
	 * @param float  $completeness Completeness.
	 * @param float  $threshold Threshold.
	 * @param array  $gaps Gaps.
	 * @return string
	 */
	private function recommend( $score, $completeness, $threshold, array $gaps ) {
		if ( $completeness < $threshold ) {
			return 'INSUFFICIENT_DATA';
		}
		$critical = 0;
		foreach ( $gaps as $g ) {
			if ( in_array( ( $g['criterion'] ?? '' ), [ 'subject', 'grade' ], true ) && 'MISSING' === ( $g['status'] ?? '' ) ) {
				++$critical;
			}
		}
		if ( $score >= 75 && 0 === $critical ) {
			return 'RECOMMENDED_FOR_REVIEW';
		}
		if ( $score >= 50 ) {
			return 'PARTIAL_MATCH';
		}
		if ( $score >= 25 ) {
			return 'LOW_MATCH';
		}
		return 'LOW_MATCH';
	}
}
