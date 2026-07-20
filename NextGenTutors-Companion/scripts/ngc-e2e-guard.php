<?php
/**
 * Guard E2E scripts from running on production stacks.
 *
 * @package NextGenCompanion
 */

if ( ! function_exists( 'ngc_e2e_require_demo_stack' ) ) {
	/**
	 * Exit unless demo seed is enabled or WP_DEBUG is on.
	 *
	 * @param string $script_label Script name for stderr message.
	 */
	function ngc_e2e_require_demo_stack( $script_label = 'E2E' ) {
		if ( ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED )
			|| ( defined( 'WP_DEBUG' ) && WP_DEBUG )
			|| file_exists( '/.dockerenv' ) ) {
			return;
		}

		fwrite(
			STDERR,
			sprintf(
				"%s is restricted to demo/Docker stacks (NGC_ALLOW_DEMO_SEED, WP_DEBUG, or /.dockerenv).\n",
				$script_label
			)
		);
		exit( 1 );
	}
}
