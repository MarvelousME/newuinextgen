<?php
/**
 * Provider preset catalog + diagnostics facade over NGC_AI_Models.
 *
 * Single BYOK key store: NGC_AI_Models (see NextGen → AI Suite).
 * This class keeps preset endpoints for UI and routes diagnostics LLM calls
 * to the configured diagnostics model.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preset providers and diagnostics LLM facade.
 */
class NGC_Ai_Provider_Registry {

	public const DIAGNOSTICS_MODEL_OPTION = 'ngc_ai_diagnostics_model_id';

	/**
	 * Preset provider endpoints (catalog only — not a second key store).
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function providers() {
		return [
			'openai'       => [ 'label' => 'OpenAI', 'endpoint' => 'https://api.openai.com/v1' ],
			'anthropic'    => [ 'label' => 'Anthropic', 'endpoint' => 'https://api.anthropic.com/v1' ],
			'gemini'       => [ 'label' => 'Google Gemini', 'endpoint' => 'https://generativelanguage.googleapis.com/v1beta' ],
			'azure_openai' => [ 'label' => 'Azure OpenAI', 'endpoint' => '' ],
			'openrouter'   => [ 'label' => 'OpenRouter', 'endpoint' => 'https://openrouter.ai/api/v1' ],
			'groq'         => [ 'label' => 'Groq', 'endpoint' => 'https://api.groq.com/openai/v1' ],
			'deepseek'     => [ 'label' => 'DeepSeek', 'endpoint' => 'https://api.deepseek.com/v1' ],
			'mistral'      => [ 'label' => 'Mistral', 'endpoint' => 'https://api.mistral.ai/v1' ],
			'cohere'       => [ 'label' => 'Cohere', 'endpoint' => 'https://api.cohere.ai/v1' ],
			'perplexity'   => [ 'label' => 'Perplexity', 'endpoint' => 'https://api.perplexity.ai' ],
			'ollama'       => [ 'label' => 'Ollama', 'endpoint' => 'http://localhost:11434/v1' ],
			'local_llm'    => [ 'label' => 'Local LLM', 'endpoint' => '' ],
		];
	}

	/**
	 * Diagnostics model selection (delegates to AI Suite registry).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		return [
			'diagnostics_model_id' => sanitize_key( (string) get_option( self::DIAGNOSTICS_MODEL_OPTION, '' ) ),
			'models'               => class_exists( 'NGC_AI_Models' ) ? NGC_AI_Models::list() : [],
		];
	}

	/**
	 * @param array<string, mixed> $settings Settings with diagnostics_model_id.
	 */
	public static function save_settings( $settings ) {
		$model_id = sanitize_key( (string) ( $settings['diagnostics_model_id'] ?? '' ) );
		update_option( self::DIAGNOSTICS_MODEL_OPTION, $model_id, false );
	}

	/**
	 * Run a diagnostics LLM turn via NGC_AI_Models (single code path).
	 *
	 * @param string $prompt User prompt.
	 * @param string $system System prompt.
	 * @return array<string, mixed>
	 */
	public static function complete( $prompt, $system = '' ) {
		if ( ! class_exists( 'NGC_AI_Models' ) ) {
			return [
				'success'    => false,
				'error'      => __( 'AI Suite not loaded. Activate NextGenTutors-Companion.', 'nextgencompanion' ),
				'confidence' => 0,
			];
		}

		$model_id = self::resolve_diagnostics_model_id();
		if ( '' === $model_id ) {
			return [
				'success'    => false,
				'error'      => __( 'No AI model configured. Add a model in NextGen → AI Suite and select it below.', 'nextgencompanion' ),
				'confidence' => 0,
			];
		}

		$messages = [];
		if ( '' !== $system ) {
			$messages[] = [ 'role' => 'system', 'content' => $system ];
		}
		$messages[] = [ 'role' => 'user', 'content' => $prompt ];

		$result = NGC_AI_Models::complete( $model_id, $messages, [ 'max_tokens' => 800, 'temperature' => 0.2 ] );
		if ( is_wp_error( $result ) ) {
			return [
				'success'    => false,
				'error'      => $result->get_error_message(),
				'confidence' => 0,
			];
		}

		return [
			'success'    => true,
			'text'       => (string) ( $result['content'] ?? '' ),
			'confidence' => 0.75,
		];
	}

	/**
	 * @return string Model id or empty.
	 */
	public static function resolve_diagnostics_model_id() {
		$chosen = sanitize_key( (string) get_option( self::DIAGNOSTICS_MODEL_OPTION, '' ) );
		if ( '' !== $chosen && null !== NGC_AI_Models::get( $chosen ) ) {
			return $chosen;
		}
		foreach ( NGC_AI_Models::list() as $model ) {
			if ( ! empty( $model['has_key'] ) ) {
				return (string) $model['id'];
			}
		}
		return '';
	}
}
