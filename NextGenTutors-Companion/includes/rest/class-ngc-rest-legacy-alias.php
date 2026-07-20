<?php
/**
 * Back-compat REST alias: ngt/v1 mirrors ngc/v1 routes.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers duplicate route handlers under ngt/v1.
 */
class NGC_Rest_Legacy_Alias {

	const LEGACY_NAMESPACE = 'ngt/v1';

	/**
	 * Hook registration (no-op; aliases register from NGC_Rest::register_routes).
	 */
	public static function init() {
		// Intentionally empty — kept for backward compatibility with integrate-test.
	}

	/**
	 * Mirror all ngc/v1 routes to ngt/v1.
	 */
	public static function register_alias_routes() {
		$server = rest_get_server();
		$routes = $server->get_routes();

		foreach ( $routes as $route => $endpoints ) {
			if ( 0 !== strpos( $route, '/ngc/v1' ) ) {
				continue;
			}
			$relative = substr( $route, strlen( '/ngc/v1' ) );
			if ( '' === $relative ) {
				$relative = '/';
			}

			foreach ( $endpoints as $endpoint ) {
				if ( empty( $endpoint['callback'] ) ) {
					continue;
				}
				register_rest_route(
					self::LEGACY_NAMESPACE,
					$relative,
					[
						'methods'             => self::normalize_endpoint_methods( $endpoint['methods'] ),
						'callback'            => $endpoint['callback'],
						'permission_callback' => $endpoint['permission_callback'] ?? '__return_true',
						'args'                => $endpoint['args'] ?? [],
					]
				);
			}
		}
	}

	/**
	 * get_routes() returns methods as verb maps; register_rest_route needs verbs or a bitmask.
	 *
	 * @param mixed $methods Route methods from WP_REST_Server::get_routes().
	 * @return string|int
	 */
	private static function normalize_endpoint_methods( $methods ) {
		if ( is_string( $methods ) || is_int( $methods ) ) {
			return $methods;
		}
		if ( is_array( $methods ) ) {
			$verbs = array_keys( array_filter( $methods ) );
			if ( empty( $verbs ) ) {
				return WP_REST_Server::READABLE;
			}
			return implode( ',', $verbs );
		}
		return WP_REST_Server::READABLE;
	}

	/**
	 * @return bool
	 */
	public static function aliases_registered() {
		$routes = rest_get_server()->get_routes();
		foreach ( array_keys( $routes ) as $route ) {
			if ( 0 === strpos( $route, '/ngt/v1/' ) ) {
				return true;
			}
		}
		return false;
	}
}
