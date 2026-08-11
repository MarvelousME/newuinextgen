<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\Integrations;

/**
 * Companion REST client — no private class coupling.
 */
final class CompanionClient {

	/**
	 * @return array<string,mixed>
	 */
	public static function talent_stats(): array {
		$remote = self::internal_get( '/ngc/v1/talent/health' );
		$evals  = self::internal_get( '/ngc/v1/talent/evaluations', [ 'per_page' => 1 ] );
		$total  = is_array( $evals ) ? (int) ( $evals['total'] ?? $evals['meta']['total'] ?? 0 ) : 0;
		return [
			'tutorsScored'    => $total > 0 ? $total : (int) apply_filters( 'ngtbm_talent_tutors_scored', 0 ),
			'avgSuitability'  => (float) apply_filters( 'ngtbm_talent_avg_suitability', 0 ),
			'needReview'      => (int) apply_filters( 'ngtbm_talent_need_review', 0 ),
			'provider'        => is_array( $remote ) ? $remote : [ 'status' => 'degraded', 'message' => 'Companion talent API unavailable' ],
		];
	}

	/**
	 * @return array{items:list<array<string,mixed>>,total:int}
	 */
	public static function talent_evaluations( \WP_REST_Request $request ): array {
		$query = [
			'per_page' => (int) ( $request->get_param( 'per_page' ) ?: 25 ),
			'page'     => (int) ( $request->get_param( 'page' ) ?: 1 ),
			'subject'  => (string) ( $request->get_param( 'subject' ) ?: '' ),
		];
		$remote = self::internal_get( '/ngc/v1/talent/evaluations', $query );
		if ( is_array( $remote ) && isset( $remote['items'] ) ) {
			return [
				'items' => array_values( (array) $remote['items'] ),
				'total' => (int) ( $remote['total'] ?? count( (array) $remote['items'] ) ),
			];
		}
		if ( is_array( $remote ) && array_is_list( $remote ) ) {
			return [ 'items' => $remote, 'total' => count( $remote ) ];
		}
		// Demo workstation sample when Companion has no rows yet.
		return [
			'items' => [
				[
					'id'             => 'demo-028',
					'tutor'          => 'NGT-T028 Sipho M.',
					'score'          => 94,
					'recommendation' => 'strong',
					'modelVersion'   => 'ngt-talent-suitability-v1',
					'evaluatedAt'    => gmdate( 'c' ),
					'status'         => 'Strong',
				],
				[
					'id'             => 'demo-103',
					'tutor'          => 'NGT-T103 Sarah L.',
					'score'          => 87,
					'recommendation' => 'strong',
					'modelVersion'   => 'ngt-talent-suitability-v1',
					'evaluatedAt'    => gmdate( 'c' ),
					'status'         => 'Strong',
				],
				[
					'id'             => 'demo-052',
					'tutor'          => 'NGT-T052 Peter J.',
					'score'          => 74,
					'recommendation' => 'review',
					'modelVersion'   => 'ngt-talent-suitability-v1',
					'evaluatedAt'    => gmdate( 'c' ),
					'status'         => 'Review',
				],
			],
			'total' => 3,
			'demo'  => true,
		];
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function talent_explain( string $id ) {
		$remote = self::internal_get( '/ngc/v1/talent/evaluations/' . rawurlencode( $id ) . '/explain' );
		if ( is_array( $remote ) ) {
			return $remote;
		}
		if ( str_starts_with( $id, 'demo-' ) ) {
			return self::demo_explain( $id );
		}
		return new \WP_Error( 'TALENT_PROVIDER_UNAVAILABLE', __( 'Talent evaluation is temporarily unavailable.', 'nextgentutors-beyond-measure' ), [ 'status' => 503 ] );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function queue_snapshot(): array {
		$remote = self::internal_get( '/ngc/v1/platform/queue/stats' );
		if ( is_array( $remote ) ) {
			return $remote;
		}
		return [
			'pending' => (int) apply_filters( 'ngtbm_queue_pending', 0 ),
			'dlq'     => (int) apply_filters( 'ngtbm_queue_dlq', 0 ),
			'source'  => 'local-filters',
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>|list<mixed>|null
	 */
	private static function internal_get( string $path, array $query = [] ) {
		$request = new \WP_REST_Request( 'GET', $path );
		foreach ( $query as $k => $v ) {
			if ( $v === '' || $v === null ) {
				continue;
			}
			$request->set_param( $k, $v );
		}
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return null;
		}
		$data = $response->get_data();
		return is_array( $data ) ? $data : null;
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function demo_explain( string $id ): array {
		$map = [
			'demo-028' => [ 'Sipho M.', 94 ],
			'demo-103' => [ 'Sarah L.', 87 ],
			'demo-052' => [ 'Peter J.', 74 ],
		];
		[ $name, $score ] = $map[ $id ] ?? [ 'Tutor', 80 ];
		return [
			'tutorId'        => strtoupper( str_replace( 'demo-', 'NGT-T', $id ) ),
			'tutorName'      => $name,
			'overall'        => $score,
			'components'     => [
				[ 'label' => 'Subject', 'score' => 100 ],
				[ 'label' => 'Grade', 'score' => 95 ],
				[ 'label' => 'Curriculum', 'score' => 100 ],
				[ 'label' => 'Qualification', 'score' => 90 ],
				[ 'label' => 'Experience', 'score' => 85 ],
				[ 'label' => 'Availability', 'score' => 95 ],
				[ 'label' => 'Language', 'score' => 100 ],
			],
			'matched'        => [ 'Mathematics', 'Grade 10–12', 'CAPS', 'Online', 'English' ],
			'requiresReview' => [ 'Qualification document verification' ],
			'safeguarding'   => [
				[ 'label' => 'Identity verified', 'status' => 'ok' ],
				[ 'label' => 'References verified', 'status' => 'ok' ],
				[ 'label' => 'Background check pending', 'status' => 'pending' ],
			],
			'recommendation' => 'RECOMMENDED FOR HUMAN REVIEW',
			'demo'           => true,
		];
	}
}
