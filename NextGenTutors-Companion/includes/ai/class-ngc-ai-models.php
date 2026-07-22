<?php
/**
 * BYOK multi-model registry — OpenAI-compatible endpoints with encrypted keys.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic add/remove model endpoints for supervised agents.
 */
final class NGC_AI_Models {

	private const MODELS_OPTION = 'ngc_ai_models';
	private const KEYS_OPTION   = 'ngc_ai_keys';

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function list() {
		$models = get_option( self::MODELS_OPTION, [] );
		if ( ! is_array( $models ) ) {
			return [];
		}
		$out = [];
		foreach ( $models as $id => $m ) {
			$out[] = [
				'id'       => (string) $id,
				'label'    => (string) ( $m['label'] ?? $id ),
				'base_url' => (string) ( $m['base_url'] ?? '' ),
				'model'    => (string) ( $m['model'] ?? '' ),
				'has_key'  => self::has_key( (string) $id ),
				'created'  => (string) ( $m['created'] ?? '' ),
			];
		}
		return $out;
	}

	/**
	 * Resolve an id to its stored canonical form.
	 *
	 * Legacy ids contained uppercase characters while REST input was lowercased
	 * by sanitize_key(), so lookups must be case-insensitive.
	 *
	 * @param string $id Model id (any case).
	 * @return string Canonical stored id, or '' when not found.
	 */
	public static function resolve_id( $id ) {
		$id = trim( (string) $id );
		if ( '' === $id ) {
			return '';
		}
		$models = get_option( self::MODELS_OPTION, [] );
		if ( ! is_array( $models ) ) {
			return '';
		}
		if ( isset( $models[ $id ] ) ) {
			return $id;
		}
		foreach ( array_keys( $models ) as $stored ) {
			if ( 0 === strcasecmp( (string) $stored, $id ) ) {
				return (string) $stored;
			}
		}
		return '';
	}

	/**
	 * @param string $id Model id.
	 * @return array<string,mixed>|null
	 */
	public static function get( $id ) {
		$id = self::resolve_id( $id );
		if ( '' === $id ) {
			return null;
		}
		$models = get_option( self::MODELS_OPTION, [] );
		return isset( $models[ $id ] ) && is_array( $models[ $id ] ) ? $models[ $id ] : null;
	}

	/**
	 * @param array<string,mixed> $data Model payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function save( $data ) {
		$gate = BIA_Policy::can( 'ai.model.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		$id    = sanitize_key( (string) ( $data['id'] ?? '' ) );
		$label = sanitize_text_field( (string) ( $data['label'] ?? '' ) );
		$raw   = trim( (string) ( $data['base_url'] ?? '' ) );
		$model = sanitize_text_field( (string) ( $data['model'] ?? '' ) );

		if ( '' !== $raw && ! preg_match( '#^https?://#i', $raw ) ) {
			$raw = 'https://' . $raw;
		}
		$base = esc_url_raw( $raw );

		if ( '' === $raw ) {
			return new WP_Error( 'ngc_model', __( 'Base URL is required (e.g. https://api.openai.com/v1).', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		if ( '' === $base ) {
			return new WP_Error( 'ngc_model', __( 'The base URL is not a valid URL. Use the provider API root, e.g. https://api.openai.com/v1.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		if ( '' === $model ) {
			return new WP_Error( 'ngc_model', __( 'Model id is required (e.g. gpt-4o-mini).', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		if ( '' !== $id ) {
			$existing = self::resolve_id( $id );
			if ( '' !== $existing ) {
				$id = $existing;
			}
		}
		if ( '' === $id ) {
			$id = sanitize_key( $label ?: $model ) . '-' . strtolower( wp_generate_password( 4, false, false ) );
		}

		$models = get_option( self::MODELS_OPTION, [] );
		if ( ! is_array( $models ) ) {
			$models = [];
		}
		$models[ $id ] = [
			'label'    => $label ?: $model,
			'base_url' => untrailingslashit( $base ),
			'model'    => $model,
			'created'  => $models[ $id ]['created'] ?? current_time( 'mysql' ),
		];
		update_option( self::MODELS_OPTION, $models, false );

		BIA_Policy::allow_host( $base );

		if ( ! empty( $data['api_key'] ) ) {
			$set = self::set_key( $id, (string) $data['api_key'] );
			if ( is_wp_error( $set ) ) {
				return $set;
			}
		}

		BIA_Policy::audit( 'ai.model.manage', 'success', [ 'op' => 'save', 'id' => $id ] );
		return [ 'id' => $id ];
	}

	/**
	 * @param string $id Model id.
	 * @return bool|WP_Error
	 */
	public static function delete( $id ) {
		$gate = BIA_Policy::can( 'ai.model.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$id     = self::resolve_id( $id ) ?: $id;
		$models = get_option( self::MODELS_OPTION, [] );
		$keys   = get_option( self::KEYS_OPTION, [] );
		unset( $models[ $id ], $keys[ $id ] );
		update_option( self::MODELS_OPTION, $models, false );
		update_option( self::KEYS_OPTION, $keys, false );
		BIA_Policy::audit( 'ai.model.manage', 'success', [ 'op' => 'delete', 'id' => $id ] );
		return true;
	}

	/**
	 * @param string $id  Model id.
	 * @param string $key API key plaintext.
	 * @return bool|WP_Error
	 */
	public static function set_key( $id, $key ) {
		$gate = BIA_Policy::can( 'ai.model.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$id = self::resolve_id( $id ) ?: $id;
		if ( '' === $id ) {
			return new WP_Error( 'ngc_model', __( 'Model not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$cipher = NGC_Crypto::encrypt( $key );
		if ( is_wp_error( $cipher ) ) {
			return $cipher;
		}
		$keys = get_option( self::KEYS_OPTION, [] );
		if ( ! is_array( $keys ) ) {
			$keys = [];
		}
		$keys[ $id ] = $cipher;
		update_option( self::KEYS_OPTION, $keys, false );
		return true;
	}

	/**
	 * @param string $id Model id.
	 * @return bool
	 */
	public static function has_key( $id ) {
		$keys = get_option( self::KEYS_OPTION, [] );
		if ( ! is_array( $keys ) ) {
			return false;
		}
		if ( ! empty( $keys[ $id ] ) ) {
			return true;
		}
		foreach ( array_keys( $keys ) as $stored ) {
			if ( 0 === strcasecmp( (string) $stored, (string) $id ) && ! empty( $keys[ $stored ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $id Model id.
	 * @return string|WP_Error
	 */
	private static function key_for( $id ) {
		$keys = get_option( self::KEYS_OPTION, [] );
		if ( ! is_array( $keys ) ) {
			$keys = [];
		}
		if ( empty( $keys[ $id ] ) ) {
			foreach ( array_keys( $keys ) as $stored ) {
				if ( 0 === strcasecmp( (string) $stored, (string) $id ) && ! empty( $keys[ $stored ] ) ) {
					$id = (string) $stored;
					break;
				}
			}
		}
		if ( empty( $keys[ $id ] ) ) {
			return new WP_Error( 'ngc_key', __( 'No API key set for this model.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		return NGC_Crypto::decrypt( (string) $keys[ $id ] );
	}

	/**
	 * @param string $id Model id.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function test( $id ) {
		$gate = BIA_Policy::can( 'ai.model.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$result = self::complete( $id, [ [ 'role' => 'user', 'content' => 'ping' ] ], [ 'max_tokens' => 5, 'skip_gate' => true ] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return [
			'ok'      => true,
			'latency' => $result['latency'] ?? 0,
			'sample'  => mb_substr( (string) ( $result['content'] ?? '' ), 0, 120 ),
		];
	}

	/**
	 * @param string                             $id       Model id.
	 * @param array<int,array<string,string>>    $messages Chat messages.
	 * @param array<string,mixed>                $opts     Options.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function complete( $id, $messages, $opts = [] ) {
		if ( empty( $opts['skip_gate'] ) ) {
			$gate = BIA_Policy::can( 'ai.chat' );
			if ( is_wp_error( $gate ) ) {
				return $gate;
			}
		}

		$resolved = self::resolve_id( $id );
		if ( '' !== $resolved ) {
			$id = $resolved;
		}
		$model = self::get( $id );
		if ( null === $model ) {
			return new WP_Error( 'ngc_model', __( 'Model not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$base = (string) $model['base_url'];
		if ( ! BIA_Policy::host_allowed( $base ) ) {
			return new WP_Error( 'ngc_egress', __( 'This endpoint host is not on the egress allowlist.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$key = self::key_for( $id );
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$clean = [];
		foreach ( $messages as $msg ) {
			$role    = (string) ( $msg['role'] ?? 'user' );
			$content = (string) ( $msg['content'] ?? '' );
			if ( 'user' === $role ) {
				$content = BIA_Policy::redact( $content );
			}
			$clean[] = [ 'role' => $role, 'content' => $content ];
		}

		$endpoint = self::endpoint( $base );
		$payload  = [
			'model'       => (string) $model['model'],
			'messages'    => $clean,
			'temperature' => isset( $opts['temperature'] ) ? (float) $opts['temperature'] : 0.7,
			'max_tokens'  => isset( $opts['max_tokens'] ) ? (int) $opts['max_tokens'] : 800,
		];

		$start = microtime( true );
		$res   = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 45,
			]
		);
		$latency = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $res ) ) {
			BIA_Policy::audit( 'ai.chat', 'error', [ 'id' => $id, 'message' => $res->get_error_message() ] );
			return new WP_Error( 'ngc_http', $res->get_error_message(), [ 'status' => 502 ] );
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );

		if ( $code < 200 || $code >= 300 ) {
			$detail = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : ( 'HTTP ' . $code );
			/* translators: 1: provider HTTP status code, 2: provider error detail. */
			$message = sprintf( __( 'AI provider error (HTTP %1$d): %2$s', 'nextgencompanion' ), $code, $detail );
			BIA_Policy::audit( 'ai.chat', 'error', [ 'id' => $id, 'provider_status' => $code, 'message' => $detail ] );
			// Always surface provider failures as 502 so they are not mistaken for missing local REST routes.
			return new WP_Error( 'ngc_provider', $message, [ 'status' => 502 ] );
		}

		$content = '';
		if ( is_array( $body ) && isset( $body['choices'][0]['message']['content'] ) ) {
			$content = (string) $body['choices'][0]['message']['content'];
		}

		BIA_Policy::audit( 'ai.chat', 'success', [ 'id' => $id, 'latency' => $latency ] );
		return [ 'content' => $content, 'latency' => $latency ];
	}

	/**
	 * @param string $base Base URL.
	 * @return string
	 */
	private static function endpoint( $base ) {
		$base = untrailingslashit( $base );
		if ( false !== strpos( $base, '/chat/completions' ) ) {
			return $base;
		}
		// OpenAI-compatible APIs live under /v1; add it when the base URL is a bare host
		// (e.g. https://api.anthropic.com → https://api.anthropic.com/v1/chat/completions).
		$path = (string) wp_parse_url( $base, PHP_URL_PATH );
		if ( '' === $path || '/' === $path ) {
			$base .= '/v1';
		}
		return $base . '/chat/completions';
	}
}
