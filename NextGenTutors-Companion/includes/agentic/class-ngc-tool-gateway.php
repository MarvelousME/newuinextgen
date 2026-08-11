<?php
/**
 * Least-privilege tool gateway for agents (no SQL/shell/browser-login tools).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowlisted application tools only.
 */
final class NGC_Tool_Gateway {

	/**
	 * Approved tool catalogue.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalogue() {
		$base = [
			'crm.find_contact'              => [ 'cap' => 'manage_options', 'mutating' => false ],
			'crm.upsert_tutor_lead'         => [ 'cap' => 'manage_options', 'mutating' => true, 'approval' => true ],
			'crm.add_tag'                   => [ 'cap' => 'manage_options', 'mutating' => true ],
			'campaign.create_draft'         => [ 'cap' => 'manage_options', 'mutating' => true ],
			'social.list_connected_accounts'=> [ 'cap' => 'manage_options', 'mutating' => false ],
			'social.prepare_post'           => [ 'cap' => 'manage_options', 'mutating' => true ],
			'social.submit_for_approval'    => [ 'cap' => 'manage_options', 'mutating' => true ],
			'social.publish_approved_post'  => [ 'cap' => 'manage_options', 'mutating' => true, 'approval' => true ],
			'schedule.create_rule'          => [ 'cap' => 'manage_options', 'mutating' => true ],
			'schedule.preview_occurrences'  => [ 'cap' => 'manage_options', 'mutating' => false ],
			'recruitment.create_candidate'  => [ 'cap' => 'manage_options', 'mutating' => true, 'approval' => true ],
			'recruitment.record_consent'    => [ 'cap' => 'manage_options', 'mutating' => true ],
			'recruitment.record_reply'      => [ 'cap' => 'manage_options', 'mutating' => true ],
			'recruitment.request_human_handoff' => [ 'cap' => 'manage_options', 'mutating' => true ],
			// Talent Intelligence — decision support only (never approve/reject tutors).
			'talent.match.evaluate'         => [ 'cap' => 'ngc_manage_matches', 'mutating' => false, 'feature' => 'talent_agent_tools' ],
			'talent.match.explain'          => [ 'cap' => 'ngc_manage_matches', 'mutating' => false, 'feature' => 'talent_agent_tools' ],
			'talent.rank'                   => [ 'cap' => 'ngc_manage_matches', 'mutating' => false, 'feature' => 'talent_agent_tools' ],
		];
		/**
		 * @param array<string,array<string,mixed>> $base Catalogue.
		 */
		return apply_filters( 'ngc_tool_catalogue', $base );
	}

	/**
	 * @param string               $tool    Tool id.
	 * @param array<string, mixed> $args    Arguments.
	 * @param array<string, mixed> $context Actor/agent context.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function invoke( $tool, array $args = [], array $context = [] ) {
		$tool = sanitize_key( str_replace( '.', '_dot_', (string) $tool ) );
		$tool = str_replace( '_dot_', '.', $tool );
		$cat  = self::catalogue();
		if ( ! isset( $cat[ $tool ] ) ) {
			return new WP_Error( 'ngc_tool_denied', __( 'Tool is not on the allowlist.', 'nextgencompanion' ), [ 'tool' => $tool ] );
		}
		$meta = $cat[ $tool ];
		if ( 'talent_agent_tools' === ( $meta['feature'] ?? '' ) ) {
			if ( ! class_exists( 'NGC_Talent_Settings' ) || empty( NGC_Talent_Settings::get()['agent_tools_enabled'] ) || ! NGC_Talent_Settings::is_active() ) {
				return new WP_Error( 'ngc_tool_feature_off', __( 'Talent agent tools are disabled.', 'nextgencompanion' ) );
			}
		}
		$cap  = (string) ( $meta['cap'] ?? 'manage_options' );
		if ( empty( $context['bypass_cap'] ) && ! current_user_can( $cap ) && ! current_user_can( 'manage_options' ) && empty( $context['system'] ) ) {
			return new WP_Error( 'ngc_tool_forbidden', __( 'Insufficient capability for tool.', 'nextgencompanion' ) );
		}
		if ( ! empty( $meta['approval'] ) && empty( $context['approval_id'] ) && empty( $context['human_approved'] ) ) {
			return new WP_Error( 'ngc_tool_approval_required', __( 'Human approval required before this tool may run.', 'nextgencompanion' ), [ 'tool' => $tool ] );
		}

		$correlation = sanitize_text_field( (string) ( $context['correlation_id'] ?? wp_generate_uuid4() ) );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'tool_invoke',
				'tool',
				0,
				[
					'tool'           => $tool,
					'correlation_id' => $correlation,
					'agent'          => sanitize_key( (string) ( $context['agent_id'] ?? '' ) ),
					'mutating'       => ! empty( $meta['mutating'] ),
				]
			);
		}

		switch ( $tool ) {
			case 'social.list_connected_accounts':
				return [ 'ok' => true, 'accounts' => class_exists( 'NGC_Social_Connections' ) ? NGC_Social_Connections::list_public() : [] ];
			case 'schedule.preview_occurrences':
				if ( ! class_exists( 'NGC_Schedule_Rrule' ) ) {
					return new WP_Error( 'ngc_schedule_missing', __( 'Scheduler unavailable.', 'nextgencompanion' ) );
				}
				return NGC_Schedule_Rrule::preview( $args );
			case 'crm.upsert_tutor_lead':
				if ( ! class_exists( 'NGC_Tutor_Leads' ) ) {
					return new WP_Error( 'ngc_leads_missing', __( 'Lead service unavailable.', 'nextgencompanion' ) );
				}
				return NGC_Tutor_Leads::upsert_and_sync( $args, $context );
			case 'recruitment.create_candidate':
				if ( ! class_exists( 'NGC_Tutor_Leads' ) ) {
					return new WP_Error( 'ngc_leads_missing', __( 'Lead service unavailable.', 'nextgencompanion' ) );
				}
				return NGC_Tutor_Leads::create_from_discovery( $args, $context );
			case 'talent.match.evaluate':
				if ( ! class_exists( 'NGC_Talent_Service' ) ) {
					return new WP_Error( 'ngc_talent_missing', __( 'Talent Intelligence unavailable.', 'nextgencompanion' ) );
				}
				$candidate    = isset( $args['candidate'] ) && is_array( $args['candidate'] ) ? $args['candidate'] : [];
				$requirements = isset( $args['requirements'] ) && is_array( $args['requirements'] ) ? $args['requirements'] : NGC_Talent_Service::default_requirements();
				$out          = NGC_Talent_Service::evaluate_safe( $candidate, $requirements, [ 'persist' => ! empty( $args['persist'] ) ] );
				$out['autoApproveForbidden'] = true;
				return $out;
			case 'talent.match.explain':
				if ( ! class_exists( 'NGC_Talent_Repository' ) ) {
					return new WP_Error( 'ngc_talent_missing', __( 'Talent Intelligence unavailable.', 'nextgencompanion' ) );
				}
				$id  = (int) ( $args['evaluation_id'] ?? $args['evaluationId'] ?? 0 );
				$row = $id ? NGC_Talent_Repository::get( $id ) : null;
				if ( ! $row ) {
					return new WP_Error( 'ngc_talent_missing_eval', __( 'Evaluation not found.', 'nextgencompanion' ) );
				}
				return [ 'ok' => true, 'evaluation' => $row, 'autoApproveForbidden' => true ];
			case 'talent.rank':
				if ( ! class_exists( 'NGC_Talent_Service' ) ) {
					return new WP_Error( 'ngc_talent_missing', __( 'Talent Intelligence unavailable.', 'nextgencompanion' ) );
				}
				$candidates   = isset( $args['candidates'] ) && is_array( $args['candidates'] ) ? $args['candidates'] : [];
				$requirements = isset( $args['requirements'] ) && is_array( $args['requirements'] ) ? $args['requirements'] : [];
				$out          = NGC_Talent_Service::rank_safe( $candidates, $requirements );
				$out['autoApproveForbidden'] = true;
				return $out;
			default:
				return [
					'ok'      => true,
					'queued'  => true,
					'tool'    => $tool,
					'message' => __( 'Tool accepted; durable worker execution pending host configuration.', 'nextgencompanion' ),
					'correlation_id' => $correlation,
				];
		}
	}
}
