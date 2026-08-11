<?php
/**
 * Talent Intelligence façade — policy, provider, persistence, safe degrade.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability-facing talent service. Never auto-approves tutors.
 */
final class NGC_Talent_Service {

	/** @var NGC_Talent_Intelligence_Provider_Interface|null */
	private static $provider = null;

	/**
	 * Bootstrap.
	 */
	public static function init() {
		self::ensure_interface();
		NGC_Talent_Repository::ensure_schema();
		add_filter( 'ngc_queue_handle_talentevaluate', [ 'NGC_Talent_Ingestion_Worker', 'handle' ], 10, 3 );
		add_action( 'ngc_tutor_application_submitted', [ __CLASS__, 'on_application_event' ], 20, 2 );
		add_action( 'ngc_tutor_application_updated', [ __CLASS__, 'on_application_event' ], 20, 2 );
	}

	/**
	 * Load interface file.
	 */
	private static function ensure_interface() {
		if ( interface_exists( 'NGC_Talent_Intelligence_Provider_Interface', false ) ) {
			return;
		}
		$path = NGC_PLUGIN_DIR . 'includes/talent/interface-ngc-talent-intelligence-provider.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * @return NGC_Talent_Intelligence_Provider_Interface
	 */
	public static function provider() {
		self::ensure_interface();
		if ( self::$provider instanceof NGC_Talent_Intelligence_Provider_Interface ) {
			return self::$provider;
		}
		if ( ! NGC_Talent_Settings::is_active() ) {
			self::$provider = new NGC_Talent_Noop_Provider();
			return self::$provider;
		}
		self::$provider = new NGC_Talent_Bridge_Rules_Provider();
		return self::$provider;
	}

	/**
	 * Reset cached provider.
	 */
	public static function reset_provider() {
		self::$provider = null;
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function health() {
		try {
			$h = self::provider()->health();
			$h['settings'] = [
				'enabled'               => (bool) NGC_Talent_Settings::get()['enabled'],
				'mode'                  => (string) NGC_Talent_Settings::get()['mode'],
				'evaluate_applications' => NGC_Talent_Settings::evaluate_applications_allowed(),
				'rank_find_tutor'       => NGC_Talent_Settings::rank_find_tutor_allowed(),
				'nlp_sidecar_enabled'   => NGC_Talent_Settings::nlp_allowed(),
				'auto_approve_forbidden'=> true,
			];
			return $h;
		} catch ( Exception $e ) {
			return [
				'ok'      => false,
				'mode'    => NGC_Talent_Settings::MODE_DEGRADED,
				'message' => 'Talent health degraded',
			];
		}
	}

	/**
	 * Evaluate and optionally persist. Never calls lifecycle approve/reject.
	 *
	 * @param array<string,mixed> $candidate Candidate.
	 * @param array<string,mixed> $requirements Requirements.
	 * @param array<string,mixed> $options Options.
	 * @return array<string,mixed>
	 */
	public static function evaluate_safe( array $candidate, array $requirements, array $options = [] ) {
		if ( ! NGC_Talent_Settings::is_active() ) {
			return self::provider()->evaluate_match( $candidate, $requirements, $options );
		}

		$decision = self::policy( 'talent.match.evaluate', $options );
		$dec      = (string) ( $decision['decision'] ?? 'DENY' );
		if ( ! in_array( $dec, [ 'ALLOW', 'ALLOW_WITH_LIMITS' ], true ) ) {
			return [
				'ok'             => true,
				'score'          => null,
				'recommendation' => 'INSUFFICIENT_DATA',
				'warnings'       => [ 'policy_deny' ],
				'denied'         => true,
				'autoApproveForbidden' => true,
			];
		}

		$opts = $options;
		if ( NGC_Talent_Settings::nlp_allowed() ) {
			$sim = NGC_Talent_Nlp_Client::similarity(
				(string) ( $candidate['bio'] ?? $candidate['experience'] ?? '' ),
				(string) ( $requirements['narrative'] ?? $requirements['description'] ?? '' )
			);
			if ( null !== $sim ) {
				$opts['text_similarity'] = $sim;
			}
		}

		try {
			$result = self::provider()->evaluate_match( $candidate, $requirements, $opts );
			if ( is_wp_error( $result ) ) {
				self::metric( 'talent_degraded_total', [ 'op' => 'evaluate' ] );
				return [
					'ok'             => true,
					'degraded'       => true,
					'recommendation' => 'INSUFFICIENT_DATA',
					'warnings'       => [ $result->get_error_message() ],
					'autoApproveForbidden' => true,
				];
			}
			self::metric( 'talent_evaluate_total', [ 'op' => 'evaluate' ] );

			if ( ! empty( $options['persist'] ) ) {
				$id = NGC_Talent_Repository::save_evaluation(
					[
						'candidate_type'        => (string) ( $options['candidate_type'] ?? 'application' ),
						'candidate_id'          => (string) ( $options['candidate_id'] ?? '' ),
						'requirement_id'        => (string) ( $options['requirement_id'] ?? 'default' ),
						'score'                 => $result['score'] ?? null,
						'recommendation'        => $result['recommendation'] ?? '',
						'model_version'         => $result['modelVersion'] ?? NGC_Talent_Settings::MODEL_VERSION,
						'weight_config_version' => $result['weightConfigVersion'] ?? NGC_Talent_Settings::WEIGHTS_VERSION,
						'input_snapshot_hash'   => $result['inputSnapshotHash'] ?? '',
						'result'                => $result,
						'idempotency_key'       => (string) ( $options['idempotency_key'] ?? '' ),
						'correlation_id'        => (string) ( $options['correlation_id'] ?? '' ),
					]
				);
				if ( ! is_wp_error( $id ) ) {
					$result['evaluationId'] = $id;
				}
			}

			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log(
					'talent_evaluation',
					'talent',
					(int) ( $result['evaluationId'] ?? 0 ),
					[
						'candidate_id'   => (string) ( $options['candidate_id'] ?? '' ),
						'recommendation' => (string) ( $result['recommendation'] ?? '' ),
						'model'          => (string) ( $result['modelVersion'] ?? '' ),
						'score'          => $result['score'] ?? null,
					]
				);
			}

			$result['autoApproveForbidden'] = true;
			return $result;
		} catch ( Exception $e ) {
			self::metric( 'talent_degraded_total', [ 'op' => 'evaluate' ] );
			return [
				'ok'             => true,
				'degraded'       => true,
				'recommendation' => 'INSUFFICIENT_DATA',
				'autoApproveForbidden' => true,
			];
		}
	}

	/**
	 * Rank eligible candidates only.
	 *
	 * @param array<int,array<string,mixed>> $candidates Candidates.
	 * @param array<string,mixed>            $requirements Requirements.
	 * @param array<string,mixed>            $options Options.
	 * @return array<string,mixed>
	 */
	public static function rank_safe( array $candidates, array $requirements, array $options = [] ) {
		if ( ! NGC_Talent_Settings::is_active() ) {
			return [
				'ok'       => true,
				'ranked'   => array_values( $candidates ),
				'warnings' => [ 'talent_disabled' ],
			];
		}
		try {
			$out = self::provider()->rank( $candidates, $requirements, $options );
			self::metric( 'talent_rank_total', [ 'op' => 'rank' ] );
			return is_wp_error( $out )
				? [ 'ok' => true, 'ranked' => array_values( $candidates ), 'degraded' => true ]
				: $out;
		} catch ( Exception $e ) {
			self::metric( 'talent_degraded_total', [ 'op' => 'rank' ] );
			return [ 'ok' => true, 'ranked' => array_values( $candidates ), 'degraded' => true ];
		}
	}

	/**
	 * Normalize application row to candidate profile.
	 *
	 * @param object|array $application Application.
	 * @return array<string,mixed>
	 */
	public static function profile_from_application( $application ) {
		$a = is_object( $application ) ? (array) $application : (array) $application;
		$subjects = $a['subjects'] ?? '';
		if ( is_string( $subjects ) ) {
			$subjects = array_filter( array_map( 'trim', explode( ',', $subjects ) ) );
		}
		return [
			'subjects'   => array_values( (array) $subjects ),
			'grades'     => [],
			'province'   => (string) ( $a['province'] ?? '' ),
			'location'   => (string) ( $a['province'] ?? '' ),
			'bio'        => (string) ( $a['bio'] ?? '' ),
			'experience' => (string) ( $a['bio'] ?? '' ),
			'safeguarding' => [
				'identity'   => 'PENDING',
				'background' => 'PENDING',
				'references' => 'PENDING',
			],
		];
	}

	/**
	 * Default requirement profile from admin settings / empty.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_requirements() {
		$stored = NGC_Talent_Repository::get_requirement_profile( 'default' );
		if ( $stored && ! empty( $stored['profile'] ) ) {
			return $stored['profile'];
		}
		return [
			'title'     => 'Default tutor requirement',
			'subjects'  => [],
			'grades'    => [],
			'curricula' => [],
			'skills'    => [],
			'languages' => [],
			'deliveryModes' => [],
		];
	}

	/**
	 * Queue evaluation for application.
	 *
	 * @param int|string          $application_id Application id.
	 * @param array<string,mixed> $application Application data.
	 */
	public static function on_application_event( $application_id, $application = [] ) {
		if ( ! NGC_Talent_Settings::evaluate_applications_allowed() ) {
			return;
		}
		if ( ! class_exists( 'NGC_Durable_Queue' ) ) {
			$candidate = self::profile_from_application( $application );
			self::evaluate_safe(
				$candidate,
				self::default_requirements(),
				[
					'persist'       => true,
					'candidate_type'=> 'application',
					'candidate_id'  => (string) $application_id,
					'idempotency_key' => 'app-' . $application_id . '-' . NGC_Talent_Settings::MODEL_VERSION . '-' . NGC_Talent_Settings::WEIGHTS_VERSION,
				]
			);
			return;
		}
		NGC_Durable_Queue::enqueue(
			'talent',
			[
				'type'           => 'talent.evaluate',
				'candidate_type' => 'application',
				'candidate_id'   => (string) $application_id,
				'application'    => is_array( $application ) ? $application : [],
			],
			[
				'idempotency_key' => 'talent-app-' . $application_id . '-' . NGC_Talent_Settings::MODEL_VERSION,
				'priority'        => 60,
			]
		);
	}

	/**
	 * Extract skills (structured + optional text tokens from tutoring vocabulary).
	 *
	 * @param array<string,mixed> $profile Profile.
	 * @return array<string,mixed>
	 */
	public static function extract_skills( array $profile ) {
		$scrub = NGC_Talent_Fairness::scrub( $profile );
		$profile = $scrub['clean'];
		$skills = [];
		foreach ( [ 'skills', 'subjects' ] as $k ) {
			$v = $profile[ $k ] ?? [];
			if ( is_string( $v ) ) {
				$v = array_filter( array_map( 'trim', explode( ',', $v ) ) );
			}
			foreach ( (array) $v as $s ) {
				$skills[] = strtolower( (string) $s );
			}
		}
		$text = strtolower( (string) ( $profile['bio'] ?? '' ) );
		foreach ( [ 'mathematics', 'math', 'english', 'science', 'physics', 'chemistry', 'biology', 'accounting', 'afrikaans', 'isiZulu', 'caps', 'ieb' ] as $tok ) {
			if ( '' !== $tok && false !== strpos( $text, strtolower( $tok ) ) ) {
				$skills[] = $tok;
			}
		}
		$skills = array_values( array_unique( array_filter( $skills ) ) );
		return [ 'ok' => true, 'skills' => $skills, 'stripped' => $scrub['stripped'] ];
	}

	/**
	 * @param string              $capability Capability.
	 * @param array<string,mixed> $context Context.
	 * @return array{decision:string,reason:string}
	 */
	private static function policy( $capability, array $context ) {
		if ( ! class_exists( 'NGC_Policy_Bridge' ) ) {
			return [ 'decision' => 'ALLOW', 'reason' => 'no_policy_bridge' ];
		}
		return NGC_Policy_Bridge::decide(
			$capability,
			array_merge(
				[
					'actor_type' => (string) ( $context['actor_type'] ?? 'human' ),
					'operation'  => 'invoke',
				],
				$context
			)
		);
	}

	/**
	 * @param string              $name Metric.
	 * @param array<string,mixed> $tags Tags.
	 */
	private static function metric( $name, array $tags = [] ) {
		if ( class_exists( 'NGC_Metrics' ) && method_exists( 'NGC_Metrics', 'inc' ) ) {
			NGC_Metrics::inc( $name, 1, $tags );
		}
	}
}
