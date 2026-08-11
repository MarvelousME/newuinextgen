<?php
/**
 * TencentDB Agent Memory Core HTTP adapter (v3).
 *
 * Does NOT call Memory Proxy as an LLM gateway.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTTP client for Memory Core retrieve / write / health.
 */
final class NGC_Tencent_Memory_Adapter implements NGC_Memory_Provider_Interface {

	/**
	 * @return string
	 */
	public function slug() {
		return 'tencentdb';
	}

	/**
	 * @return array{ok:bool,mode:string,message:string,details?:array}
	 */
	public function health() {
		$cfg = NGC_Memory_Settings::get();
		$base = rtrim( (string) ( $cfg['core_base_url'] ?? '' ), '/' );
		if ( '' === $base ) {
			return [
				'ok'      => false,
				'mode'    => NGC_Memory_Settings::MODE_DEGRADED,
				'message' => 'core_base_url not configured',
				'details' => [ 'provider' => 'tencentdb' ],
			];
		}
		$res = $this->request( 'GET', $base . '/health', null, false );
		if ( is_wp_error( $res ) ) {
			return [
				'ok'      => false,
				'mode'    => NGC_Memory_Settings::MODE_DEGRADED,
				'message' => $res->get_error_message(),
				'details' => [ 'provider' => 'tencentdb' ],
			];
		}
		$code = (int) ( $res['code'] ?? 0 );
		$ok   = $code >= 200 && $code < 300;
		return [
			'ok'      => $ok,
			'mode'    => $ok ? NGC_Memory_Settings::MODE_HEALTHY : NGC_Memory_Settings::MODE_DEGRADED,
			'message' => $ok ? 'Memory Core reachable' : 'Memory Core unhealthy HTTP ' . $code,
			'details' => [
				'provider' => 'tencentdb',
				'http'     => $code,
				'body'     => is_array( $res['body'] ?? null ) ? $res['body'] : [],
			],
		];
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function write( array $context ) {
		$iso = $this->isolation( $context );
		if ( is_wp_error( $iso ) ) {
			return $iso;
		}
		$messages = isset( $context['messages'] ) && is_array( $context['messages'] ) ? $context['messages'] : [];
		$body     = array_merge(
			$iso,
			[
				'session_id' => (string) ( $context['session_id'] ?? wp_generate_uuid4() ),
				'messages'   => $messages,
			]
		);
		$path = $this->base() . '/v3/conversation/write';
		$res  = $this->request( 'POST', $path, $body, true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return [
			'ok'       => ( (int) ( $res['code'] ?? 0 ) ) < 300,
			'written'  => true,
			'provider' => 'tencentdb',
			'raw'      => $res['body'] ?? null,
		];
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function search( array $context ) {
		$iso = $this->isolation( $context );
		if ( is_wp_error( $iso ) ) {
			return $iso;
		}
		$body = array_merge(
			$iso,
			[
				'query' => (string) ( $context['query'] ?? '' ),
				'limit' => (int) ( $context['limit'] ?? 8 ),
			]
		);
		$res = $this->request( 'POST', $this->base() . '/v3/atomic/search', $body, true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$items = [];
		if ( is_array( $res['body'] ) ) {
			$items = $res['body']['items'] ?? $res['body']['results'] ?? $res['body']['data'] ?? [];
			if ( ! is_array( $items ) ) {
				$items = [];
			}
		}
		return [ 'ok' => true, 'items' => $items, 'provider' => 'tencentdb' ];
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function retrieve( array $context ) {
		$iso = $this->isolation( $context );
		if ( is_wp_error( $iso ) ) {
			return $iso;
		}
		$cfg  = NGC_Memory_Settings::get();
		$body = array_merge(
			$iso,
			[
				'query'      => (string) ( $context['query'] ?? '' ),
				'max_items'  => (int) ( $context['max_items'] ?? $cfg['max_retrieve_items'] ?? 8 ),
				'max_chars'  => (int) ( $context['max_chars'] ?? $cfg['max_retrieve_chars'] ?? 4000 ),
			]
		);
		// Prefer v3 core/retrieve; fall back to search assembly if endpoint missing.
		$res = $this->request( 'POST', $this->base() . '/v3/core/retrieve', $body, true );
		if ( is_wp_error( $res ) || ( (int) ( $res['code'] ?? 0 ) ) >= 400 ) {
			$search = $this->search( $context );
			if ( is_wp_error( $search ) ) {
				return $search;
			}
			$text = $this->items_to_text( $search['items'] ?? [], (int) ( $body['max_chars'] ) );
			return [
				'ok'           => true,
				'items'        => $search['items'] ?? [],
				'context_text' => $text,
				'provider'     => 'tencentdb',
				'fallback'     => 'search',
			];
		}
		$items = [];
		$text  = '';
		if ( is_array( $res['body'] ) ) {
			$items = $res['body']['items'] ?? $res['body']['memories'] ?? [];
			$text  = (string) ( $res['body']['context'] ?? $res['body']['context_text'] ?? '' );
			if ( '' === $text && is_array( $items ) ) {
				$text = $this->items_to_text( $items, (int) $body['max_chars'] );
			}
		}
		return [
			'ok'           => true,
			'items'        => is_array( $items ) ? $items : [],
			'context_text' => $text,
			'provider'     => 'tencentdb',
		];
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function forget( array $context ) {
		$iso = $this->isolation( $context );
		if ( is_wp_error( $iso ) ) {
			return $iso;
		}
		$body = array_merge( $iso, [ 'memory_id' => (string) ( $context['memory_id'] ?? '' ) ] );
		$res  = $this->request( 'POST', $this->base() . '/v3/atomic/delete', $body, true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return [ 'ok' => true, 'forgotten' => 1, 'provider' => 'tencentdb' ];
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function correct( array $context ) {
		$iso = $this->isolation( $context );
		if ( is_wp_error( $iso ) ) {
			return $iso;
		}
		$body = array_merge(
			$iso,
			[
				'memory_id' => (string) ( $context['memory_id'] ?? '' ),
				'content'   => (string) ( $context['content'] ?? '' ),
			]
		);
		$res = $this->request( 'POST', $this->base() . '/v3/atomic/update', $body, true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return [ 'ok' => true, 'corrected' => true, 'provider' => 'tencentdb' ];
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_memories( array $context ) {
		return $this->search( $context );
	}

	/**
	 * @return string
	 */
	private function base() {
		return rtrim( (string) ( NGC_Memory_Settings::get()['core_base_url'] ?? '' ), '/' );
	}

	/**
	 * Build isolation fields from Bridge context + identity map.
	 *
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>|WP_Error
	 */
	private function isolation( array $context ) {
		$service_id = (string) ( $context['service_id'] ?? NGC_Memory_Identity_Map::service_id_for_tenant() );
		$user_id    = (string) ( $context['remote_user_id'] ?? '' );
		$agent_id   = (string) ( $context['remote_agent_id'] ?? '' );
		$team_id    = (string) ( $context['remote_team_id'] ?? '' );

		if ( '' === $user_id && ! empty( $context['bridge_user_id'] ) ) {
			$row = NGC_Memory_Identity_Map::get( 'user', (string) $context['bridge_user_id'] );
			$user_id = $row ? (string) $row['remote_id'] : '';
		}
		if ( '' === $agent_id && ! empty( $context['bridge_agent_id'] ) ) {
			$row = NGC_Memory_Identity_Map::get( 'agent', (string) $context['bridge_agent_id'] );
			$agent_id = $row ? (string) $row['remote_id'] : (string) $context['bridge_agent_id'];
		}
		if ( '' === $team_id ) {
			$row = NGC_Memory_Identity_Map::get( 'team', 'default' );
			$team_id = $row ? (string) $row['remote_id'] : 'default';
		}

		return [
			'service_id' => $service_id,
			'team_id'    => $team_id !== '' ? $team_id : 'default',
			'agent_id'   => $agent_id !== '' ? $agent_id : 'bridge-default',
			'user_id'    => $user_id !== '' ? $user_id : 'bridge-anon',
		];
	}

	/**
	 * @param array<int,mixed> $items Items.
	 * @param int              $max   Max chars.
	 * @return string
	 */
	private function items_to_text( array $items, $max ) {
		$parts = [];
		foreach ( $items as $item ) {
			if ( is_string( $item ) ) {
				$parts[] = $item;
				continue;
			}
			if ( ! is_array( $item ) ) {
				continue;
			}
			$parts[] = (string) ( $item['content'] ?? $item['text'] ?? $item['summary'] ?? wp_json_encode( $item ) );
		}
		$text = implode( "\n", $parts );
		$max  = max( 0, (int) $max );
		if ( $max > 0 && strlen( $text ) > $max ) {
			$text = substr( $text, 0, $max );
		}
		return $text;
	}

	/**
	 * @param string               $method HTTP method.
	 * @param string               $url    URL.
	 * @param array<string,mixed>|null $body Body.
	 * @param bool                 $auth   Attach auth headers.
	 * @return array{code:int,body:mixed}|WP_Error
	 */
	private function request( $method, $url, $body, $auth ) {
		if ( '' === $url || 'http' !== substr( $url, 0, 4 ) ) {
			return new WP_Error( 'ngc_memory_url', 'Invalid Memory Core URL.' );
		}
		$cfg     = NGC_Memory_Settings::get();
		$timeout = max( 1, (int) round( ( (int) ( $cfg['timeout_ms'] ?? 2500 ) ) / 1000 ) );
		$headers = [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		];
		if ( $auth ) {
			$service = NGC_Memory_Identity_Map::service_id_for_tenant();
			$headers['x-tdai-service-id'] = $service;
			$bearer = NGC_Memory_Settings::gateway_bearer();
			if ( '' !== $bearer ) {
				$headers['Authorization'] = 'Bearer ' . $bearer;
			}
			$user_key = '';
			if ( ! empty( $body['bridge_user_id'] ) ) {
				$user_key = NGC_Memory_Identity_Map::user_key_for( (string) $body['bridge_user_id'] );
			}
			if ( '' === $user_key && ! empty( $cfg['admin_user_key_ref'] ) ) {
				$user_key = NGC_Memory_Settings::reveal_user_key( (string) $cfg['admin_user_key_ref'] );
			}
			if ( '' !== $user_key ) {
				$headers['x-tdai-user-key'] = $user_key;
			}
		}

		$args = [
			'method'  => $method,
			'timeout' => $timeout,
			'headers' => $headers,
		];
		if ( null !== $body ) {
			$safe = $body;
			unset( $safe['bridge_user_id'] );
			$args['body'] = wp_json_encode( $safe );
		}

		$retries = max( 0, (int) ( $cfg['retry'] ?? 1 ) );
		$last    = null;
		for ( $i = 0; $i <= $retries; $i++ ) {
			$response = wp_remote_request( $url, $args );
			if ( is_wp_error( $response ) ) {
				$last = $response;
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$raw  = (string) wp_remote_retrieve_body( $response );
			$decoded = json_decode( $raw, true );
			return [
				'code' => $code,
				'body' => null !== $decoded ? $decoded : $raw,
			];
		}
		return $last instanceof WP_Error ? $last : new WP_Error( 'ngc_memory_http', 'Memory Core request failed.' );
	}
}
