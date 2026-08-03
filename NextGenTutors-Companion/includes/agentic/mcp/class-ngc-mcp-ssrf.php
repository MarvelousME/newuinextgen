<?php
/**
 * SSRF / private-network guards for MCP endpoints.
 *
 * HTTP clients MUST NOT follow redirects when probing MCP endpoints
 * (use `redirection => 0` with wp_remote_*). Following Location headers
 * can bypass host checks by landing on private/metadata addresses.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks private, link-local, and metadata endpoints unless explicitly approved.
 */
final class NGC_Mcp_Ssrf {

	/**
	 * @param string $url         Candidate URL.
	 * @param bool   $allow_local Explicit local override (staging only).
	 * @return true|WP_Error
	 */
	public static function assert_safe_url( $url, $allow_local = false ) {
		$parts = wp_parse_url( (string) $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return new WP_Error( 'ngc_mcp_url', __( 'MCP endpoint must be an absolute http(s) URL.', 'nextgencompanion' ) );
		}
		$scheme = strtolower( (string) $parts['scheme'] );
		if ( ! in_array( $scheme, [ 'https', 'http' ], true ) ) {
			return new WP_Error( 'ngc_mcp_scheme', __( 'Only http/https transports are allowed.', 'nextgencompanion' ) );
		}
		if ( 'http' === $scheme && ! $allow_local ) {
			return new WP_Error( 'ngc_mcp_https', __( 'Production MCP endpoints must use HTTPS.', 'nextgencompanion' ) );
		}
		$host = strtolower( (string) $parts['host'] );
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return new WP_Error( 'ngc_mcp_creds', __( 'Credentials must not appear in the MCP URL.', 'nextgencompanion' ) );
		}
		// Reject hex / octal / dword encodings before DNS (they bypass FILTER_VALIDATE_IP).
		if ( self::is_encoded_ip_host( $host ) && ! $allow_local ) {
			return new WP_Error( 'ngc_mcp_ssrf_encoded', __( 'Encoded IP host forms are blocked.', 'nextgencompanion' ), [ 'host' => $host ] );
		}
		if ( self::is_blocked_host( $host ) && ! $allow_local ) {
			return new WP_Error( 'ngc_mcp_ssrf', __( 'Private/metadata hosts are blocked.', 'nextgencompanion' ), [ 'host' => $host ] );
		}
		$ips = self::resolve_ips( $host );
		foreach ( $ips as $ip ) {
			if ( self::is_blocked_ip( $ip ) && ! $allow_local ) {
				return new WP_Error( 'ngc_mcp_ssrf_ip', __( 'Resolved address is in a blocked range.', 'nextgencompanion' ), [ 'ip' => $ip ] );
			}
		}
		return true;
	}

	/**
	 * Assert URL safety and enforce no-redirect policy.
	 *
	 * Callers must use `redirection => 0` (already set in NGC_Mcp_Registry::health_check
	 * and NGC_Agent_Gateway_Client). If a Location header from a prior response is
	 * supplied, it is always rejected — do not chain-follow into a second hop.
	 *
	 * @param string      $url              Candidate URL.
	 * @param bool        $allow_local      Explicit local override (staging only).
	 * @param string|null $location_header  Optional Location header value; non-empty => reject.
	 * @return true|WP_Error
	 */
	public static function assert_safe_url_no_redirect( $url, $allow_local = false, $location_header = null ) {
		$safe = self::assert_safe_url( $url, $allow_local );
		if ( is_wp_error( $safe ) ) {
			return $safe;
		}
		if ( null !== $location_header && '' !== trim( (string) $location_header ) ) {
			return new WP_Error(
				'ngc_mcp_redirect',
				__( 'HTTP redirects must not be followed for MCP endpoints.', 'nextgencompanion' )
			);
		}
		return true;
	}

	/**
	 * Hex dword (0x7f000001), octal (017700000001), decimal dword (2130706433),
	 * and dotted hex/octal (0x7f.0.0.1 / 0177.0.0.1) bypass forms.
	 *
	 * @param string $host Host.
	 * @return bool
	 */
	private static function is_encoded_ip_host( $host ) {
		$host = strtolower( (string) $host );
		if ( preg_match( '/^0x[0-9a-f]+$/i', $host ) ) {
			return true;
		}
		if ( preg_match( '/^0[0-7]+$/', $host ) ) {
			return true;
		}
		if ( preg_match( '/^\d+$/', $host ) && (int) $host > 0 ) {
			return true;
		}
		// Dotted forms with at least one hex or leading-zero octal segment.
		if ( preg_match( '/^(?:0x[0-9a-f]+|0[0-7]*|\d+)(?:\.(?:0x[0-9a-f]+|0[0-7]*|\d+))+$/i', $host ) ) {
			$parts = explode( '.', $host );
			foreach ( $parts as $part ) {
				if ( preg_match( '/^0x/i', $part ) ) {
					return true;
				}
				if ( preg_match( '/^0[0-7]+$/', $part ) && '0' !== $part ) {
					return true;
				}
			}
			// Ambiguous short dotted numeric hosts (e.g. 127.1) that are not standard IPs.
			if ( count( $parts ) !== 4 && ! filter_var( $host, FILTER_VALIDATE_IP ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $host Host.
	 * @return bool
	 */
	private static function is_blocked_host( $host ) {
		$blocked = [ 'localhost', 'metadata.google.internal', 'metadata' ];
		if ( in_array( $host, $blocked, true ) ) {
			return true;
		}
		if ( preg_match( '/\.local$|\.internal$|\.localhost$/', $host ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $host Host.
	 * @return string[]
	 */
	private static function resolve_ips( $host ) {
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return [ $host ];
		}
		$records = @dns_get_record( $host, DNS_A + DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$ips     = [];
		if ( is_array( $records ) ) {
			foreach ( $records as $row ) {
				if ( ! empty( $row['ip'] ) ) {
					$ips[] = $row['ip'];
				}
				if ( ! empty( $row['ipv6'] ) ) {
					$ips[] = $row['ipv6'];
				}
			}
		}
		return array_values( array_unique( $ips ) );
	}

	/**
	 * @param string $ip IP.
	 * @return bool
	 */
	private static function is_blocked_ip( $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return true;
		}
		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}
}
