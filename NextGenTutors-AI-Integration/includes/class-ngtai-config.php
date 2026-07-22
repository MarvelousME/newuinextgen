<?php
/**
 * Integration configuration.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides constants-first configuration without exposing secrets publicly.
 */
final class NGTAI_Config {

	const OPTION_URL                  = 'ngtai_agents_api_url';
	const OPTION_KEY_ID               = 'ngtai_agents_api_key_id';
	const OPTION_SECRET               = 'ngtai_agents_api_secret_encrypted';
	const OPTION_ENABLED              = 'ngtai_enabled';
	const OPTION_DEMO_MODE            = 'ngtai_demo_mode';
	const OPTION_TIMEOUT              = 'ngtai_timeout_seconds';
	const OPTION_MAX_ATTEMPTS         = 'ngtai_max_attempts';
	const OPTION_RETRY_BASE           = 'ngtai_retry_base_seconds';
	const OPTION_SKEW                 = 'ngtai_callback_skew_seconds';
	const OPTION_NONCE_RETENTION_DAYS = 'ngtai_nonce_retention_days';
	const OPTION_GLOBAL_PAUSE         = 'ngtai_global_pause';
	const OPTION_ALLOWED_HOSTS        = 'ngtai_allowed_hosts';

	/*
	 * Compatibility aliases for callers written against the previous package.
	 */
	const OPTION_ENDPOINT           = self::OPTION_URL;
	const OPTION_MAX_SKEW           = self::OPTION_SKEW;
	const OPTION_ALLOW_INSECURE_DEV = 'ngtai_allow_insecure_dev';
	const OPTION_TENANT             = 'ngtai_tenant';

	/**
	 * Read a constant before its corresponding option.
	 *
	 * @param string $constant Constant name.
	 * @param string $option   Option name.
	 * @param mixed  $default  Default value.
	 * @return mixed
	 */
	private static function value( $constant, $option, $default = '' ) {
		if ( defined( $constant ) ) {
			return constant( $constant );
		}

		return function_exists( 'get_option' ) ? get_option( $option, $default ) : $default;
	}

	/**
	 * Sanitize a text configuration value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function text( $value ) {
		$value = (string) $value;
		return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : trim( strip_tags( $value ) );
	}

	/**
	 * Normalize a configuration value to boolean.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function boolean( $value ) {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), [ '1', 'true', 'yes', 'on' ], true );
		}
		return (bool) $value;
	}

	/**
	 * Get the agents API base URL.
	 *
	 * @return string
	 */
	public static function url() {
		$raw   = trim( (string) self::value( 'NGTAI_AGENTS_API_URL', self::OPTION_URL, '' ) );
		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $raw ) : parse_url( $raw );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return '';
		}

		$url = $scheme . '://' . (string) $parts['host'];
		if ( isset( $parts['port'] ) ) {
			$url .= ':' . (int) $parts['port'];
		}
		$url .= isset( $parts['path'] ) ? (string) $parts['path'] : '';

		if ( function_exists( 'esc_url_raw' ) ) {
			$url = esc_url_raw( $url );
		}

		return rtrim( (string) $url, '/' );
	}

	/**
	 * Get the signing key identifier.
	 *
	 * @return string
	 */
	public static function key_id() {
		return self::text( self::value( 'NGTAI_AGENTS_API_KEY_ID', self::OPTION_KEY_ID, '' ) );
	}

	/**
	 * Get the decrypted signing secret.
	 *
	 * @return string
	 */
	public static function secret() {
		if ( defined( 'NGTAI_AGENTS_API_SECRET' ) ) {
			return (string) constant( 'NGTAI_AGENTS_API_SECRET' );
		}

		$encrypted = (string) self::value( '', self::OPTION_SECRET, '' );
		if ( '' === $encrypted || ! class_exists( 'NGTAI_Crypto' ) ) {
			return '';
		}

		$plain = NGTAI_Crypto::decrypt( $encrypted );
		return false === $plain ? '' : (string) $plain;
	}

	/**
	 * Determine whether delivery is enabled.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return self::boolean( self::value( 'NGTAI_ENABLED', self::OPTION_ENABLED, 1 ) );
	}

	/**
	 * Determine whether demo mode is active.
	 *
	 * @return bool
	 */
	public static function demo_mode() {
		return self::boolean( self::value( 'NGTAI_DEMO_MODE', self::OPTION_DEMO_MODE, 0 ) );
	}

	/**
	 * Get HTTP timeout seconds.
	 *
	 * @return int
	 */
	public static function timeout() {
		return max( 2, min( 60, (int) self::value( 'NGTAI_TIMEOUT_SECONDS', self::OPTION_TIMEOUT, 10 ) ) );
	}

	/**
	 * Get maximum delivery attempts.
	 *
	 * @return int
	 */
	public static function max_attempts() {
		return max( 1, (int) self::value( 'NGTAI_MAX_ATTEMPTS', self::OPTION_MAX_ATTEMPTS, 5 ) );
	}

	/**
	 * Get base retry delay seconds.
	 *
	 * @return int
	 */
	public static function retry_base() {
		return max( 1, (int) self::value( 'NGTAI_RETRY_BASE_SECONDS', self::OPTION_RETRY_BASE, 30 ) );
	}

	/**
	 * Get allowed callback timestamp skew.
	 *
	 * @return int
	 */
	public static function skew() {
		return max( 30, min( 900, (int) self::value( 'NGTAI_CALLBACK_SKEW_SECONDS', self::OPTION_SKEW, 300 ) ) );
	}

	/**
	 * Get durable nonce retention days.
	 *
	 * @return int
	 */
	public static function nonce_retention_days() {
		return max( 1, (int) self::value( 'NGTAI_NONCE_RETENTION_DAYS', self::OPTION_NONCE_RETENTION_DAYS, 30 ) );
	}

	/**
	 * Determine whether all agent delivery is paused.
	 *
	 * @return bool
	 */
	public static function global_pause() {
		return self::boolean( self::value( 'NGTAI_GLOBAL_PAUSE', self::OPTION_GLOBAL_PAUSE, 0 ) );
	}

	/**
	 * Get tenant identifier.
	 *
	 * @return string
	 */
	public static function tenant() {
		if ( defined( 'NGTAI_TENANT' ) ) {
			$tenant = self::text( constant( 'NGTAI_TENANT' ) );
			return '' === $tenant ? 'nextgentutors' : $tenant;
		}
		return 'nextgentutors';
	}

	/**
	 * Determine whether required credentials are configured.
	 *
	 * @return bool
	 */
	public static function configured() {
		return '' !== self::url() && '' !== self::key_id() && '' !== self::secret();
	}

	/**
	 * Encrypt and persist a signing secret.
	 *
	 * @param string $plaintext Plaintext secret.
	 * @return true|WP_Error
	 */
	public static function store_secret( $plaintext ) {
		if ( ! class_exists( 'NGTAI_Crypto' ) ) {
			return new WP_Error( 'ngtai_crypto_unavailable', 'Secure secret storage is unavailable.' );
		}

		$encrypted = NGTAI_Crypto::encrypt( (string) $plaintext );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		if ( ! function_exists( 'update_option' ) || ! update_option( self::OPTION_SECRET, $encrypted, false ) ) {
			return new WP_Error( 'ngtai_secret_store_failed', 'The encrypted secret could not be saved.' );
		}

		return true;
	}

	/**
	 * Get safe configuration status.
	 *
	 * @return array<string,mixed>
	 */
	public static function public_status() {
		$key_id = self::key_id();
		return [
			'url'                  => self::url(),
			'key_id'               => '' === $key_id ? '' : substr( $key_id, 0, 4 ) . '…',
			'enabled'              => self::enabled(),
			'demo_mode'            => self::demo_mode(),
			'timeout_seconds'      => self::timeout(),
			'max_attempts'         => self::max_attempts(),
			'retry_base_seconds'   => self::retry_base(),
			'callback_skew_seconds'=> self::skew(),
			'nonce_retention_days' => self::nonce_retention_days(),
			'global_pause'         => self::global_pause(),
			'tenant'               => self::tenant(),
			'configured'           => self::configured(),
			'allowed_hosts'        => self::allowed_hosts(),
		];
	}

	/**
	 * Get explicitly allowed outbound hosts.
	 *
	 * @return string[]
	 */
	public static function allowed_hosts() {
		$default = '';
		$url     = self::url();
		if ( '' !== $url ) {
			$host    = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url, PHP_URL_HOST ) : parse_url( $url, PHP_URL_HOST );
			$default = is_string( $host ) ? $host : '';
		}

		$raw   = self::value( 'NGTAI_ALLOWED_HOSTS', self::OPTION_ALLOWED_HOSTS, $default );
		$hosts = is_array( $raw ) ? $raw : preg_split( '/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );
		$out   = [];
		foreach ( (array) $hosts as $host ) {
			$host = strtolower( trim( self::text( $host ) ) );
			if ( '' !== $host && preg_match( '/^(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|[a-f0-9:]+)$/', $host ) ) {
				$out[] = $host;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Compatibility alias for url().
	 *
	 * @return string
	 */
	public static function endpoint() {
		return self::url();
	}

	/**
	 * Compatibility alias for skew().
	 *
	 * @return int
	 */
	public static function max_skew() {
		return self::skew();
	}

	/**
	 * Legacy development setting retained for existing callers.
	 *
	 * @return bool
	 */
	public static function allow_insecure_dev() {
		return self::boolean( self::value( 'NGTAI_ALLOW_INSECURE_DEV', self::OPTION_ALLOW_INSECURE_DEV, 0 ) );
	}

	/**
	 * Load event schema configuration for existing consumers.
	 *
	 * @return array<string,mixed>
	 */
	public static function event_schemas() {
		$fallback = [ 'schema_version' => 1, 'events' => [], 'model_policies' => [] ];
		if ( ! defined( 'NGTAI_PLUGIN_DIR' ) ) {
			return $fallback;
		}
		$path = NGTAI_PLUGIN_DIR . 'config/event-schemas.php';
		if ( ! is_readable( $path ) ) {
			return $fallback;
		}
		$data = include $path;
		return is_array( $data ) ? $data : $fallback;
	}
}
