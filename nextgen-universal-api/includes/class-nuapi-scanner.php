<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Scans installed/active plugins, verifies which ones expose a REST
 * namespace that is actually live (never guessed), discovers each
 * plugin's database tables, and produces one registry consumed by the
 * admin UI and the CRUD engine.
 */
class NUAPI_Scanner {

	const CACHE_KEY = 'nuapi_registry_cache';
	const CACHE_TTL = 300;

	/** Tables that must NEVER be exposed for generated CRUD, no matter what. */
	private static $hard_blocklist = array(
		'users', 'usermeta', 'options', 'site', 'sitemeta', 'blogs',
		'signups', 'registration_log', 'user_roles', 'nuapi_audit_log',
	);

	/** Known slug => candidate REST namespace(s). Verified live, never trusted blindly. */
	private static $known_namespaces = array(
		'woocommerce'                                     => array( 'wc/v3', 'wc/v2', 'wc-analytics' ),
		'woocommerce-payments'                             => array( 'wc/v3/payments' ),
		'jwt-authentication-for-wp-api'                    => array( 'jwt-auth/v1' ),
		'simple-jwt-login'                                 => array( 'simple-jwt-login/v1' ),
		'fluent-support'                                   => array( 'fluent-support/v2' ),
		'fluentcrm'                                        => array( 'fluent-crm/v2', 'fluentcrm/v2' ),
		'fluentform'                                       => array( 'fluentform/v1' ),
		'masterstudy-lms-learning-management-system'       => array( 'lp/v1' ),
		'masterstudy-lms-learning-management-system-pro'   => array( 'lp/v1' ),
		'ameliabooking'                                    => array( 'amelia/v1' ),
		'gamipress'                                        => array( 'gamipress/v1' ),
		'automatorwp'                                      => array( 'automatorwp/v1' ),
		'wp-graphql'                                       => array( 'graphql' ),
		'wordpress-seo'                                    => array( 'yoast/v1' ),
		'mailchimp-for-wp'                                 => array( 'mc4wp/v1' ),
		'ai-engine'                                        => array( 'mwai/v1', 'mwai-ui/v1' ),
		'contact-form-7'                                   => array( 'contact-form-7/v1' ),
		'litespeed-cache'                                  => array( 'litespeed/v1' ),
	);

	/** Table-prefix hints for plugins whose tables don't obviously match their slug. */
	private static $known_table_hints = array(
		'ameliabooking'                               => array( 'amelia_' ),
		'fluent-support'                               => array( 'fluent_support_', 'fluent_' ),
		'fluentcrm'                                    => array( 'fc_', 'fluentcrm_' ),
		'fluentform'                                   => array( 'fluentform_' ),
		'gamipress'                                    => array( 'gamipress_' ),
		'automatorwp'                                  => array( 'automatorwp_' ),
		'loginizer'                                    => array( 'loginizer_' ),
		'masterstudy-lms-learning-management-system'   => array( 'stm_lms_' ),
	);

	public static function invalidate_registry() {
		delete_transient( self::CACHE_KEY );
	}

	public static function get_registry( $force_rescan = false ) {
		if ( ! $force_rescan ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$registry = self::build_registry();
		set_transient( self::CACHE_KEY, $registry, self::CACHE_TTL );
		return $registry;
	}

	private static function build_registry() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins     = get_plugins();
		$active_plugins  = (array) get_option( 'active_plugins', array() );
		$live_namespaces = self::get_live_rest_namespaces();
		$all_tables      = self::get_all_db_tables();

		$registry = array();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			if ( ! in_array( $plugin_file, $active_plugins, true ) ) {
				continue;
			}
			$slug      = self::slug_from_file( $plugin_file );
			$native_ns = self::detect_native_namespace( $slug, $live_namespaces );
			$tables    = self::detect_plugin_tables( $slug, $all_tables );

			$registry[ $slug ] = array(
				'name'            => $plugin_data['Name'],
				'version'         => $plugin_data['Version'],
				'slug'            => $slug,
				'active'          => true,
				'native_api'      => $native_ns,
				'has_native_api'  => ! empty( $native_ns ),
				'tables'          => $tables,
				'needs_generated' => empty( $native_ns ) && ! empty( $tables ),
			);
		}

		return $registry;
	}

	private static function slug_from_file( $plugin_file ) {
		return ( strpos( $plugin_file, '/' ) !== false ) ? dirname( $plugin_file ) : basename( $plugin_file, '.php' );
	}

	private static function get_live_rest_namespaces() {
		if ( ! function_exists( 'rest_get_server' ) ) {
			return array();
		}
		global $wp_rest_server;
		if ( empty( $wp_rest_server ) ) {
			do_action( 'rest_api_init' );
		}
		$server = rest_get_server();
		return $server ? array_keys( $server->get_namespaces() ) : array();
	}

	private static function detect_native_namespace( $slug, array $live_namespaces ) {
		$found = array();

		if ( isset( self::$known_namespaces[ $slug ] ) ) {
			foreach ( self::$known_namespaces[ $slug ] as $candidate ) {
				if ( 'graphql' === $candidate ) {
					if ( class_exists( 'WPGraphQL' ) ) {
						$found[] = 'graphql (POST /graphql)';
					}
					continue;
				}
				if ( in_array( $candidate, $live_namespaces, true ) ) {
					$found[] = $candidate;
				}
			}
			return $found;
		}

		$tokens = array_filter( preg_split( '/[-_]/', $slug ), function ( $t ) {
			return strlen( $t ) >= 5 && ! in_array( $t, array( 'wordpress', 'plugin', 'addon', 'addons' ), true );
		} );
		foreach ( $live_namespaces as $ns ) {
			foreach ( $tokens as $token ) {
				if ( false !== stripos( $ns, $token ) ) {
					$found[] = $ns;
					break;
				}
			}
		}
		return array_unique( $found );
	}

	private static function get_all_db_tables() {
		global $wpdb;
		$rows = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) );
		return is_array( $rows ) ? $rows : array();
	}

	private static function detect_plugin_tables( $slug, array $all_tables ) {
		global $wpdb;
		$prefix     = $wpdb->prefix;
		$matches    = array();
		$hints      = isset( self::$known_table_hints[ $slug ] ) ? self::$known_table_hints[ $slug ] : array();
		$slugTokens = array_filter( preg_split( '/[-_]/', $slug ), function ( $t ) { return strlen( $t ) >= 4; } );

		foreach ( $all_tables as $table ) {
			$short = preg_replace( '/^' . preg_quote( $prefix, '/' ) . '/', '', $table );

			if ( in_array( $short, self::$hard_blocklist, true ) || self::is_core_table( $short ) ) {
				continue;
			}

			$hit = false;
			foreach ( $hints as $hint ) {
				if ( 0 === stripos( $short, $hint ) ) { $hit = true; break; }
			}
			if ( ! $hit ) {
				foreach ( $slugTokens as $token ) {
					if ( false !== stripos( $short, $token ) ) { $hit = true; break; }
				}
			}
			if ( $hit ) {
				$matches[ $table ] = self::describe_table( $table );
			}
		}
		return $matches;
	}

	private static function is_core_table( $short_name ) {
		$core = array(
			'posts', 'postmeta', 'comments', 'commentmeta', 'terms', 'termmeta',
			'term_relationships', 'term_taxonomy', 'links', 'users', 'usermeta',
			'options', 'site', 'sitemeta', 'blogs',
		);
		return in_array( $short_name, $core, true );
	}

	/** Table name here comes exclusively from SHOW TABLES output above, never from user input. */
	private static function describe_table( $table ) {
		global $wpdb;
		$cols        = $wpdb->get_results( "DESCRIBE `{$table}`" );
		$primary_key = null;
		$columns     = array();

		foreach ( (array) $cols as $col ) {
			$columns[] = array( 'name' => $col->Field, 'type' => $col->Type, 'null' => $col->Null, 'key' => $col->Key );
			if ( 'PRI' === $col->Key ) {
				$primary_key = $col->Field;
			}
		}
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		return array(
			'columns'     => $columns,
			'primary_key' => $primary_key ? $primary_key : 'id',
			'row_count'   => $count,
		);
	}
}
