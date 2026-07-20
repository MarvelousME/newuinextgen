<?php
/**
 * Verify NextGen UI Library — hardcoded dynamic values + provider wiring.
 *
 * Usage: php NextGenTutors-Companion/scripts/verify-ui-library.php
 *
 * @package NextGenCompanion
 */

$root         = dirname( __DIR__, 2 );
$theme_ui     = $root . '/NextGenTutors-BeyondInfinity/template-parts/ui-library';
$companion_ui = $root . '/NextGenTutors-Companion/includes/ui-library';
$warnings     = [];

$patterns = [
	'Sipho Ndlovu'       => 'hardcoded tutor name',
	'Chantal du Plessis' => 'hardcoded tutor name',
	'R320'               => 'hardcoded price',
	'rating.*4\.9'       => 'hardcoded rating',
];

$files = array_merge(
	glob( $theme_ui . '/*.php' ) ?: [],
	glob( $companion_ui . '/*.php' ) ?: [],
	glob( $companion_ui . '/providers/*.php' ) ?: []
);

$issues = [];
foreach ( $files as $file ) {
	$content = file_get_contents( $file );
	foreach ( $patterns as $pattern => $label ) {
		if ( preg_match( '/' . $pattern . '/i', $content ) ) {
			$issues[] = [
				'file'  => str_replace( $root . '/', '', $file ),
				'label' => $label,
			];
		}
	}
}

$helpers = $root . '/NextGenTutors-BeyondInfinity/inc/helpers.php';
if ( is_readable( $helpers ) ) {
	$content = file_get_contents( $helpers );
	if ( preg_match( '/function ngt_demo_tutors_enabled/', $content ) && preg_match( '/function ngt_get_demo_tutor_roster/', $content ) ) {
		$warnings[] = [
			'file'     => 'NextGenTutors-BeyondInfinity/inc/helpers.php',
			'label'    => 'Demo roster gated via ngt_demo_tutors_enabled() — OK',
			'severity' => 'info',
		];
	} elseif ( preg_match( '/Static fallback — design-system tutor roster/', $content ) ) {
		$warnings[] = [
			'file'     => 'NextGenTutors-BeyondInfinity/inc/helpers.php',
			'label'    => 'ngt_get_tutors() static demo roster not gated',
			'severity' => 'warning',
		];
	}
}

$required_providers = [
	'class-ngc-ui-company-data-provider.php',
	'class-ngc-ui-tutor-data-provider.php',
	'class-ngc-ui-page-content-provider.php',
	'class-ngc-ui-pricing-data-provider.php',
	'class-ngc-ui-review-data-provider.php',
	'class-ngc-ui-booking-data-provider.php',
	'class-ngc-ui-calendar-data-provider.php',
];
foreach ( $required_providers as $provider_file ) {
	if ( ! is_readable( $companion_ui . '/providers/' . $provider_file ) ) {
		$issues[] = [
			'file'  => 'providers/' . $provider_file,
			'label' => 'missing required provider',
		];
	}
}

if ( ! function_exists( 'ngc_get_pricing_tiers' ) ) {
	$warnings[] = [
		'file'     => 'NextGenTutors-Companion/includes/class-ngc-section-cms.php',
		'label'    => 'ngc_get_pricing_tiers() not loaded (run inside WP bootstrap)',
		'severity' => 'info',
	];
}

$inventory = $root . '/docs/ui-library/inventories/ANALYSIS-SUMMARY.json';
$summary   = is_readable( $inventory ) ? json_decode( file_get_contents( $inventory ), true ) : null;

$report = [
	'checked_at'      => gmdate( 'c' ),
	'files_scanned'   => count( $files ),
	'hardcode_issues' => $issues,
	'warnings'        => $warnings,
	'inventory'       => $summary,
	'providers'       => count( glob( $companion_ui . '/providers/*.php' ) ?: [] ),
	'partials'        => count( glob( $theme_ui . '/*.php' ) ?: [] ),
	'pass'            => empty( $issues ),
];

echo json_encode( $report, JSON_PRETTY_PRINT ) . PHP_EOL;
exit( $report['pass'] ? 0 : 1 );
