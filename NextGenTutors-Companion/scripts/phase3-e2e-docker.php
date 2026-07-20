<?php
/**
 * Phase 3 Docker E2E — REST legacy alias, Studio dashboards, workflow specs.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress.\n" );
	exit( 1 );
}

$errors = 0;
$checks = [];

/**
 * @param string $name   Check.
 * @param bool   $ok     Pass.
 * @param string $detail Detail.
 */
function ngc_p3_assert( $name, $ok, $detail = '' ) {
	global $checks, $errors;
	$checks[] = [ 'name' => $name, 'ok' => $ok, 'detail' => $detail ];
	if ( ! $ok ) {
		++$errors;
	}
}

// 1. ngt/v1 REST alias
NGC_Rest::register_routes();
NGC_Rest_Legacy_Alias::register_alias_routes();
$routes = rest_get_server()->get_routes();
ngc_p3_assert( 'ngt_alias_sections', NGC_Rest_Legacy_Alias::aliases_registered(), '' );
ngc_p3_assert( 'ngt_alias_platform', isset( $routes['/ngt/v1/platform/analytics'] ) || isset( $routes['/ngt/v1/platform/verify'] ), '' );
ngc_p3_assert( 'ngt_alias_registered', NGC_Rest_Legacy_Alias::aliases_registered(), '' );

// 2. Studio Phase 3 — dashboards
ngc_p3_assert( 'studio_dashboards_class', class_exists( 'NGC_Studio_Dashboards' ), '' );
ngc_p3_assert( 'studio_dashboard_widgets', count( NGC_Studio_Dashboards::widget_catalog() ) >= 10, (string) count( NGC_Studio_Dashboards::widget_catalog() ) );
ngc_p3_assert( 'studio_dashboard_rest', isset( $routes['/ngc/v1/studio/dashboards'] ), '' );
ngc_p3_assert( 'studio_sse_live', method_exists( 'NGC_Studio_Stream', 'render_sse' ), '' );

// 3. Integrate workflow pack WF-01..05
$specs = NGC_Workflow_Spec_Registry::verify();
ngc_p3_assert( 'integrate_specs', ! empty( $specs['ok'] ), wp_json_encode( $specs ) );
foreach ( range( 1, 5 ) as $n ) {
	$path = NGC_PLUGIN_DIR . 'integrate/workflow-0' . $n . '-';
	$glob = glob( $path . '*.json' );
	ngc_p3_assert( 'workflow_0' . $n . '_json', ! empty( $glob ), '' );
}

// 4. Shortcodes health
$health = NGC_Shortcodes::health();
ngc_p3_assert( 'shortcodes_registered', ! empty( $health['ok'] ), wp_json_encode( $health['missing'] ?? [] ) );

// 5. Child learner + MasterStudy from Phase 2 still OK
ngc_p3_assert( 'child_learners_api', method_exists( 'NGC_Child_Learners', 'provision_wp_user' ), '' );

echo "NextGen Companion — Phase 3 Docker E2E\n";
echo str_repeat( '-', 40 ) . "\n";
foreach ( $checks as $c ) {
	echo ( $c['ok'] ? 'OK  ' : 'FAIL' ) . ' ' . $c['name'];
	if ( $c['detail'] ) {
		echo ' — ' . $c['detail'];
	}
	echo "\n";
}
echo str_repeat( '-', 40 ) . "\n";
echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — Phase 3 checks passed\n";
exit( $errors ? 1 : 0 );
