<?php
/**
 * Environment + demo-seed defaults from getenv (bind-mounted; applies to existing wp-config).
 *
 * Non-local / production hosts cannot enable demo seed even if wp-config still defines
 * NGC_ALLOW_DEMO_SEED true. Env NGC_ALLOW_DEMO_SEED=0 always wins.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ngt_env = getenv( 'WP_ENVIRONMENT_TYPE' );
if ( is_string( $ngt_env ) && '' !== $ngt_env && ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
	define( 'WP_ENVIRONMENT_TYPE', $ngt_env );
}

/**
 * @return bool True when demo seed must be forced off.
 */
function ngt_mu_demo_seed_forbidden() {
	$env = defined( 'WP_ENVIRONMENT_TYPE' )
		? strtolower( (string) WP_ENVIRONMENT_TYPE )
		: strtolower( (string) getenv( 'WP_ENVIRONMENT_TYPE' ) );

	if ( in_array( $env, [ 'production', 'prod' ], true ) ) {
		return true;
	}

	$flag = getenv( 'NGC_ALLOW_DEMO_SEED' );
	if ( is_string( $flag ) && in_array( strtolower( trim( $flag ) ), [ '0', 'false', 'no', 'off' ], true ) ) {
		return true;
	}

	// Public staging hosts: require explicit local/development env to seed.
	if ( 'staging' === $env ) {
		return true;
	}

	return false;
}

add_filter(
	'ngc_allow_demo_tutor_seed',
	static function ( $allow ) {
		if ( ngt_mu_demo_seed_forbidden() ) {
			return false;
		}
		return $allow;
	},
	9999
);

add_filter(
	'ngc_demo_seed_allowed',
	static function ( $allow ) {
		if ( ngt_mu_demo_seed_forbidden() ) {
			return false;
		}
		return $allow;
	},
	9999
);

if ( ! defined( 'DISALLOW_FILE_EDIT' ) && defined( 'WP_ENVIRONMENT_TYPE' ) && 'production' === WP_ENVIRONMENT_TYPE ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
