<?php
/**
 * Supervised chat runner — single agent and sequential swarm.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conversation runner. Autonomous tool-calling loops are deliberately not executed.
 */
final class NGC_AI_Chat {

	/**
	 * @param string                             $agent_id Agent id.
	 * @param array<int,array<string,string>>    $history  Prior turns.
	 * @param string                             $message  User message.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function run_single( $agent_id, $history, $message ) {
		$gate = BIA_Policy::can( 'ai.chat' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$agent = NGC_AI_Agents::get( $agent_id );
		if ( null === $agent ) {
			return new WP_Error( 'ngc_agent', __( 'Agent not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$model_id = (string) ( $agent['model_id'] ?? '' );
		if ( '' === $model_id || null === NGC_AI_Models::get( $model_id ) ) {
			return new WP_Error( 'ngc_model', __( 'This agent has no valid model assigned.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}

		$messages = self::build_messages( $agent, $history, $message );
		$result   = NGC_AI_Models::complete( $model_id, $messages, [ 'skip_gate' => true ] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Optional async memory write — never fails the chat response.
		if ( class_exists( 'NGC_Memory_Service' ) && NGC_Memory_Settings::write_allowed() ) {
			NGC_Memory_Service::write_safe(
				[
					'bridge_user_id'  => (string) get_current_user_id(),
					'bridge_agent_id' => (string) ( $agent['id'] ?? $agent_id ),
					'session_id'      => (string) ( $agent['id'] ?? $agent_id ) . ':' . get_current_user_id(),
					'messages'        => [
						[ 'role' => 'user', 'content' => (string) $message ],
						[ 'role' => 'assistant', 'content' => (string) $result['content'] ],
					],
					'text'            => (string) $message,
					'async'           => true,
					'tutoring_data'   => ! empty( $agent['tutoring'] ),
				]
			);
		}

		return [
			'agent'   => $agent['name'],
			'content' => (string) $result['content'],
			'latency' => (int) ( $result['latency'] ?? 0 ),
		];
	}

	/**
	 * @param array<int,string> $agent_ids Agent ids in swarm order.
	 * @param string            $message   User message.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function run_swarm( $agent_ids, $message ) {
		$gate = BIA_Policy::can( 'ai.chat' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		$agents = [];
		foreach ( $agent_ids as $aid ) {
			$a = NGC_AI_Agents::get( (string) $aid );
			if ( $a ) {
				$agents[] = $a;
			}
		}
		if ( empty( $agents ) ) {
			return new WP_Error( 'ngc_swarm', __( 'No valid agents selected.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}

		$orchestrator = $agents[0];
		foreach ( $agents as $a ) {
			if ( 'orchestrator' === ( $a['role'] ?? 'worker' ) ) {
				$orchestrator = $a;
				break;
			}
		}
		$workers = array_filter(
			$agents,
			static function ( $a ) use ( $orchestrator ) {
				return $a['id'] !== $orchestrator['id'];
			}
		);

		$transcript = [];
		$context    = $message;

		foreach ( $workers as $worker ) {
			$model_id = (string) ( $worker['model_id'] ?? '' );
			if ( '' === $model_id || null === NGC_AI_Models::get( $model_id ) ) {
				$transcript[] = [ 'agent' => $worker['name'], 'content' => '[skipped: no valid model]' ];
				continue;
			}
			$messages = self::build_messages( $worker, [], $context );
			$res      = NGC_AI_Models::complete( $model_id, $messages, [ 'skip_gate' => true ] );
			$text     = is_wp_error( $res ) ? ( '[error: ' . $res->get_error_message() . ']' ) : (string) $res['content'];
			$transcript[] = [ 'agent' => $worker['name'], 'content' => $text ];
			$context .= "\n\n[" . $worker['name'] . ']: ' . $text;
		}

		$orch_model = (string) ( $orchestrator['model_id'] ?? '' );
		if ( '' === $orch_model || null === NGC_AI_Models::get( $orch_model ) ) {
			return new WP_Error( 'ngc_model', __( 'Orchestrator has no valid model.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		$synth_prompt = "Original request:\n{$message}\n\nWorker contributions:{$context}\n\nSynthesise a single, coherent final answer.";
		$messages     = self::build_messages( $orchestrator, [], $synth_prompt );
		$final        = NGC_AI_Models::complete( $orch_model, $messages, [ 'skip_gate' => true ] );
		if ( is_wp_error( $final ) ) {
			return $final;
		}

		return [
			'orchestrator' => $orchestrator['name'],
			'transcript'   => $transcript,
			'final'        => (string) $final['content'],
		];
	}

	/**
	 * @param array<string,mixed>                $agent    Agent config.
	 * @param array<int,array<string,string>>    $history  Prior turns.
	 * @param string                             $message  Latest user message.
	 * @return array<int,array<string,string>>
	 */
	private static function build_messages( $agent, $history, $message ) {
		$boundary = 'You operate strictly within the NextGen Tutors WordPress platform. '
			. 'Do not claim to take actions outside it. Do not request or output personal data about minors.';
		$system   = trim( (string) ( $agent['rules'] ?? '' ) ) . "\n\n" . $boundary;

		// Optional labeled memory context — empty on DISABLED/DEGRADED/timeout.
		if ( class_exists( 'NGC_Memory_Service' ) && NGC_Memory_Settings::retrieve_allowed() ) {
			$mem = NGC_Memory_Service::retrieve_safe(
				[
					'query'           => (string) $message,
					'bridge_user_id'  => (string) get_current_user_id(),
					'bridge_agent_id' => (string) ( $agent['id'] ?? '' ),
				]
			);
			$ctx = trim( (string) ( $mem['context_text'] ?? '' ) );
			if ( '' !== $ctx ) {
				$system .= "\n\n[Retrieved memory — optional context; may be incomplete]\n" . $ctx;
			}
		}

		$messages = [ [ 'role' => 'system', 'content' => $system ] ];
		foreach ( $history as $turn ) {
			$role       = ( 'assistant' === ( $turn['role'] ?? '' ) ) ? 'assistant' : 'user';
			$messages[] = [ 'role' => $role, 'content' => (string) ( $turn['content'] ?? '' ) ];
		}
		$messages[] = [ 'role' => 'user', 'content' => $message ];
		return $messages;
	}
}
