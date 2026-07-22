<?php
/**
 * Test bootstrap: constants, WordPress stubs, and an in-memory wpdb double.
 *
 * No WordPress installation is required. Production classes under includes/
 * run unmodified against these stubs.
 */

declare(strict_types=1);

$GLOBALS['ngtai_test_root'] = dirname( __DIR__ );

$failed                     = 0;
$passed                     = 0;
$ngtai_test_options         = [];
$ngtai_test_transients      = [];
$ngtai_test_actions         = [];
$ngtai_test_filters         = [];
$ngtai_test_can             = true;

function ngtai_assert( string $label, bool $ok ): void {
	global $failed, $passed;
	if ( $ok ) {
		echo "PASS  {$label}\n";
		$passed++;
		return;
	}
	echo "FAIL  {$label}\n";
	$failed++;
}

function ngtai_error_code( $value ): string {
	return is_wp_error( $value ) ? (string) $value->get_error_code() : '';
}

/*
 * Constants.
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/ngtai-tests/' );
}
if ( ! defined( 'NGTAI_PLUGIN_DIR' ) ) {
	define( 'NGTAI_PLUGIN_DIR', $GLOBALS['ngtai_test_root'] . '/' );
}
if ( ! defined( 'NGTAI_VERSION' ) ) {
	define( 'NGTAI_VERSION', '1.1.0' );
}
if ( ! defined( 'NGTAI_AGENTS_API_URL' ) ) {
	define( 'NGTAI_AGENTS_API_URL', 'https://agents.example.test' );
}
if ( ! defined( 'NGTAI_AGENTS_API_KEY_ID' ) ) {
	define( 'NGTAI_AGENTS_API_KEY_ID', 'test-key' );
}
if ( ! defined( 'NGTAI_AGENTS_API_SECRET' ) ) {
	define( 'NGTAI_AGENTS_API_SECRET', 'test-secret-value-not-real' );
}
if ( ! defined( 'NGTAI_TENANT' ) ) {
	define( 'NGTAI_TENANT', 'nextgentutors' );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

/*
 * WordPress function stubs.
 */
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		unset( $domain );
		return $text;
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ) ); }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $v ) { return sanitize_text_field( $v ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $flags = 0 ) { return json_encode( $data, $flags ); }
}
if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $v ) { return rtrim( (string) $v, '/\\' ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $v ) { return filter_var( (string) $v, FILTER_SANITIZE_URL ) ?: (string) $v; }
}
if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000, mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
		public function get_error_data() { return $this->data; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $k, $d = false ) {
		global $ngtai_test_options;
		return array_key_exists( $k, $ngtai_test_options ) ? $ngtai_test_options[ $k ] : $d;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $k, $v, $autoload = true ) {
		global $ngtai_test_options;
		unset( $autoload );
		$ngtai_test_options[ $k ] = $v;
		return true;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $k ) {
		global $ngtai_test_options;
		unset( $ngtai_test_options[ $k ] );
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		global $ngtai_test_transients;
		return $ngtai_test_transients[ $key ] ?? false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $ttl = 0 ) {
		global $ngtai_test_transients;
		unset( $ttl );
		$ngtai_test_transients[ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		global $ngtai_test_transients;
		unset( $ngtai_test_transients[ $key ] );
		return true;
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		unset( $gmt );
		return 'mysql' === $type ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'c' );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() { return 0; }
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		global $ngtai_test_can;
		unset( $cap );
		return (bool) $ngtai_test_can;
	}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		global $ngtai_test_actions;
		$ngtai_test_actions[] = [ $hook, $args ];
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $hook, $callback, $priority, $accepted_args );
		return true;
	}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $ngtai_test_filters;
		unset( $accepted_args );
		$ngtai_test_filters[ $hook ][] = [ 'priority' => (int) $priority, 'callback' => $callback ];
		return true;
	}
}
if ( ! function_exists( 'remove_all_filters' ) ) {
	function remove_all_filters( $hook ) {
		global $ngtai_test_filters;
		unset( $ngtai_test_filters[ $hook ] );
		return true;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		global $ngtai_test_filters;
		$handlers = $ngtai_test_filters[ $hook ] ?? [];
		usort( $handlers, static fn ( $a, $b ) => $a['priority'] <=> $b['priority'] );
		foreach ( $handlers as $handler ) {
			$value = call_user_func( $handler['callback'], $value, ...$args );
		}
		return $value;
	}
}
if ( ! function_exists( 'wp_remote_request' ) ) {
	/**
	 * Minimal HTTP transport honoring the pre_http_request short-circuit filter,
	 * mirroring WP_Http. Tests mock responses by registering that filter.
	 */
	function wp_remote_request( $url, $args = [] ) {
		$pre = apply_filters( 'pre_http_request', false, $args, $url );
		if ( false !== $pre ) {
			return $pre;
		}
		return new WP_Error( 'http_request_failed', 'No HTTP transport is available in tests.' );
	}
}
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0;
	}
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
	}
}
if ( ! function_exists( 'wp_remote_retrieve_header' ) ) {
	function wp_remote_retrieve_header( $response, $header ) {
		if ( ! is_array( $response ) ) {
			return '';
		}
		$headers = array_change_key_case( (array) ( $response['headers'] ?? [] ), CASE_LOWER );
		return $headers[ strtolower( (string) $header ) ] ?? '';
	}
}

/**
 * In-memory wpdb double covering every table the plugin persists to:
 * callback_nonces, idempotency, agent_results, approvals, deliveries, audit.
 */
final class NGTAI_Test_WPDB {
	public $prefix    = 'wp_';
	public $insert_id = 0;

	private $nonces           = [];
	private $idempotency      = [];
	private $agent_results    = [];
	private $approvals        = [];
	private $approval_auto_id = 0;
	private $deliveries       = [];
	private $delivery_events  = [];
	private $delivery_auto_id = 0;
	private $audit            = [];

	public function agent_result_count(): int {
		return count( $this->agent_results );
	}

	public function audit_count(): int {
		return count( $this->audit );
	}

	/**
	 * Test helper: age an active delivery lock so recover_locks() can reclaim it.
	 */
	public function age_delivery_lock( int $id, int $seconds ): void {
		if ( isset( $this->deliveries[ $id ] ) && null !== $this->deliveries[ $id ]['locked_at'] ) {
			$this->deliveries[ $id ]['locked_at'] = gmdate( 'Y-m-d H:i:s', time() - $seconds );
		}
	}

	/**
	 * Test helper: make a scheduled retry due immediately.
	 */
	public function force_delivery_due( int $id ): void {
		if ( isset( $this->deliveries[ $id ] ) ) {
			$this->deliveries[ $id ]['next_attempt_at'] = gmdate( 'Y-m-d H:i:s', time() - 1 );
		}
	}

	public function insert( $table, $data, $formats = [] ) {
		unset( $formats );
		if ( false !== strpos( $table, 'ngtai_idempotency' ) ) {
			$key = (string) ( $data['idempotency_key'] ?? '' );
			if ( isset( $this->idempotency[ $key ] ) ) {
				return false;
			}
			$this->idempotency[ $key ] = $data;
			return 1;
		}
		if ( false !== strpos( $table, 'ngtai_audit' ) ) {
			$this->audit[] = $data;
			return 1;
		}
		if ( false !== strpos( $table, 'ngtai_callback_nonces' ) ) {
			$nonce = (string) ( $data['nonce'] ?? '' );
			if ( isset( $this->nonces[ $nonce ] ) ) {
				return false;
			}
			$this->nonces[ $nonce ] = $data;
			return 1;
		}
		if ( false !== strpos( $table, 'ngtai_agent_results' ) ) {
			$key = (string) ( $data['agent_run_id'] ?? '' ) . ':' . (int) ( $data['result_version'] ?? 0 );
			if ( isset( $this->agent_results[ $key ] ) ) {
				return false;
			}
			$this->insert_id             = count( $this->agent_results ) + 1;
			$data['id']                  = $this->insert_id;
			$this->agent_results[ $key ] = $data;
			return 1;
		}
		if ( false !== strpos( $table, 'ngtai_approvals' ) ) {
			$key = (string) ( $data['approval_id'] ?? '' );
			if ( isset( $this->approvals[ $key ] ) ) {
				return false;
			}
			$this->insert_id          = ++$this->approval_auto_id;
			$data['id']               = $this->insert_id;
			$this->approvals[ $key ]  = $data;
			return 1;
		}
		if ( false !== strpos( $table, 'ngtai_deliveries' ) ) {
			$event_id = (string) ( $data['event_id'] ?? '' );
			if ( isset( $this->delivery_events[ $event_id ] ) ) {
				return false;
			}
			$id  = ++$this->delivery_auto_id;
			$row = array_merge(
				[
					'locked_at'     => null,
					'locked_by'     => '',
					'http_status'   => 0,
					'last_error'    => '',
					'response_hash' => '',
					'delivered_at'  => null,
				],
				$data,
				[ 'id' => $id ]
			);
			$this->deliveries[ $id ]             = $row;
			$this->delivery_events[ $event_id ]  = $id;
			$this->insert_id                     = $id;
			return 1;
		}
		return 1;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		unset( $format, $where_format );
		if ( false !== strpos( $table, 'ngtai_agent_results' ) && isset( $where['id'] ) ) {
			foreach ( $this->agent_results as &$row ) {
				if ( (int) $row['id'] === (int) $where['id'] ) {
					$row = array_merge( $row, $data );
					return 1;
				}
			}
			unset( $row );
			return 0;
		}
		if ( false !== strpos( $table, 'ngtai_approvals' ) && isset( $where['id'] ) ) {
			foreach ( $this->approvals as &$row ) {
				if ( (int) $row['id'] !== (int) $where['id'] ) {
					continue;
				}
				if ( isset( $where['status'] ) && (string) $row['status'] !== (string) $where['status'] ) {
					return 0;
				}
				$row = array_merge( $row, $data );
				return 1;
			}
			unset( $row );
			return 0;
		}
		if ( false !== strpos( $table, 'ngtai_deliveries' ) && isset( $where['id'] ) ) {
			$id = (int) $where['id'];
			if ( ! isset( $this->deliveries[ $id ] ) ) {
				return 0;
			}
			$this->deliveries[ $id ] = array_merge( $this->deliveries[ $id ], $data );
			return 1;
		}
		return 0;
	}

	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		return [ 'query' => (string) $query, 'args' => array_values( $args ) ];
	}

	public function query( $prepared ) {
		if ( ! is_array( $prepared ) ) {
			return 0;
		}
		$sql  = $prepared['query'];
		$args = $prepared['args'];

		// Nonce purge: DELETE FROM callback_nonces WHERE expires_at < %s.
		if ( false !== strpos( $sql, 'ngtai_callback_nonces' ) && false !== stripos( $sql, 'DELETE' ) ) {
			$cutoff  = (string) ( $args[0] ?? '' );
			$removed = 0;
			foreach ( $this->nonces as $nonce => $row ) {
				if ( isset( $row['expires_at'] ) && (string) $row['expires_at'] < $cutoff ) {
					unset( $this->nonces[ $nonce ] );
					$removed++;
				}
			}
			return $removed;
		}

		if ( false === strpos( $sql, 'ngtai_deliveries' ) ) {
			return 0;
		}

		// Lock recovery: UPDATE ... WHERE status='processing' AND locked_at < cutoff.
		if ( false !== strpos( $sql, 'locked_at <' ) ) {
			list( $to_status, $now, $from_status, $cutoff ) = array_pad( $args, 4, '' );
			$recovered = 0;
			foreach ( $this->deliveries as &$row ) {
				if ( (string) $row['status'] === (string) $from_status && null !== $row['locked_at'] && (string) $row['locked_at'] < (string) $cutoff ) {
					$row['status']     = (string) $to_status;
					$row['locked_at']  = null;
					$row['locked_by']  = '';
					$row['updated_at'] = (string) $now;
					$recovered++;
				}
			}
			unset( $row );
			return $recovered;
		}

		// Claim: UPDATE ... WHERE id=%d AND status IN (pending,retry_pending) AND due.
		if ( false !== strpos( $sql, 'WHERE id=%d' ) ) {
			list( $status, $locked_at, $locked_by, $updated_at, $id, $from_a, $from_b, $now ) = array_pad( $args, 8, '' );
			$id = (int) $id;
			if ( ! isset( $this->deliveries[ $id ] ) ) {
				return 0;
			}
			$row = $this->deliveries[ $id ];
			$due = null === $row['next_attempt_at'] || (string) $row['next_attempt_at'] <= (string) $now;
			if ( ! in_array( (string) $row['status'], [ (string) $from_a, (string) $from_b ], true ) || ! $due ) {
				return 0;
			}
			$this->deliveries[ $id ] = array_merge(
				$row,
				[
					'status'     => (string) $status,
					'locked_at'  => (string) $locked_at,
					'locked_by'  => (string) $locked_by,
					'updated_at' => (string) $updated_at,
				]
			);
			return 1;
		}

		return 0;
	}

	public function get_col( $prepared ) {
		if ( ! is_array( $prepared ) || false === strpos( $prepared['query'], 'ngtai_deliveries' ) ) {
			return [];
		}
		// claim_due: SELECT id ... WHERE status IN (%s,%s) AND due ORDER BY id LIMIT %d.
		list( $status_a, $status_b, $now, $limit ) = array_pad( $prepared['args'], 4, '' );
		$ids = [];
		ksort( $this->deliveries );
		foreach ( $this->deliveries as $id => $row ) {
			$due = null === $row['next_attempt_at'] || (string) $row['next_attempt_at'] <= (string) $now;
			if ( in_array( (string) $row['status'], [ (string) $status_a, (string) $status_b ], true ) && $due ) {
				$ids[] = $id;
			}
			if ( count( $ids ) >= (int) $limit ) {
				break;
			}
		}
		return $ids;
	}

	public function get_var( $prepared ) {
		if ( ! is_array( $prepared ) ) {
			return null;
		}
		if ( false !== strpos( $prepared['query'], 'ngtai_idempotency' ) ) {
			$key = (string) ( $prepared['args'][0] ?? '' );
			return isset( $this->idempotency[ $key ] ) ? 1 : null;
		}
		if ( false !== strpos( $prepared['query'], 'ngtai_approvals' ) && false !== strpos( $prepared['query'], 'approval_id' ) ) {
			$key = (string) ( $prepared['args'][0] ?? '' );
			return isset( $this->approvals[ $key ] ) ? ( $this->approvals[ $key ]['id'] ?? 1 ) : null;
		}
		return null;
	}

	public function get_row( $prepared, $output = OBJECT ) {
		unset( $output );
		if ( ! is_array( $prepared ) ) {
			return null;
		}
		$sql  = $prepared['query'];
		$args = $prepared['args'];

		if ( false !== strpos( $sql, 'ngtai_agent_results' ) ) {
			if ( false !== strpos( $sql, 'agent_run_id' ) ) {
				$key = (string) ( $args[0] ?? '' ) . ':' . (int) ( $args[1] ?? 0 );
				return $this->agent_results[ $key ] ?? null;
			}
			$id = (int) ( $args[0] ?? 0 );
			foreach ( $this->agent_results as $row ) {
				if ( (int) $row['id'] === $id ) {
					return $row;
				}
			}
			return null;
		}
		if ( false !== strpos( $sql, 'ngtai_approvals' ) ) {
			$key = (string) ( $args[0] ?? '' );
			return $this->approvals[ $key ] ?? null;
		}
		if ( false !== strpos( $sql, 'ngtai_deliveries' ) ) {
			if ( false !== strpos( $sql, 'event_id' ) ) {
				$event_id = (string) ( $args[0] ?? '' );
				$id       = $this->delivery_events[ $event_id ] ?? 0;
				return $this->deliveries[ $id ] ?? null;
			}
			return $this->deliveries[ (int) ( $args[0] ?? 0 ) ] ?? null;
		}
		return null;
	}

	public function get_results( $prepared, $output = OBJECT ) {
		unset( $output );
		if ( ! is_array( $prepared ) ) {
			return [];
		}
		$sql  = $prepared['query'];
		$args = $prepared['args'];

		if ( false !== strpos( $sql, 'ngtai_deliveries' ) && false !== strpos( $sql, 'GROUP BY status' ) ) {
			$counts = [];
			foreach ( $this->deliveries as $row ) {
				$status = (string) $row['status'];
				if ( in_array( $status, array_map( 'strval', $args ), true ) ) {
					$counts[ $status ] = ( $counts[ $status ] ?? 0 ) + 1;
				}
			}
			$out = [];
			foreach ( $counts as $status => $total ) {
				$out[] = [ 'status' => $status, 'total' => $total ];
			}
			return $out;
		}
		if ( false !== strpos( $sql, 'ngtai_deliveries' ) && false !== strpos( $sql, 'ORDER BY id DESC' ) ) {
			$rows = array_values( $this->deliveries );
			usort( $rows, static fn ( $a, $b ) => (int) $b['id'] <=> (int) $a['id'] );
			$limit = (int) end( $args );
			return array_slice( $rows, 0, $limit > 0 ? $limit : 50 );
		}
		if ( false !== strpos( $sql, 'ngtai_agent_results' ) && false !== strpos( $sql, 'event_id' ) ) {
			$event_id = (string) ( $args[0] ?? '' );
			$rows     = [];
			foreach ( $this->agent_results as $row ) {
				if ( (string) ( $row['event_id'] ?? '' ) === $event_id ) {
					$rows[] = $row;
				}
			}
			usort( $rows, static fn ( $a, $b ) => (int) $b['result_version'] <=> (int) $a['result_version'] );
			return $rows;
		}
		return [];
	}
}

$wpdb = new NGTAI_Test_WPDB();
