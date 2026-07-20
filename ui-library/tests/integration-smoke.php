<?php
/**
 * Integration smoke — registry, WPBakery adapter shortcode, interactive vendor markers.
 *
 * Usage: php ui-library/tests/integration-smoke.php
 *
 * @package NGT_UI
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

require_once NGT_UI_LIBRARY_DIR . '/integrations/wpbakery/class-ngt-ui-wpbakery.php';

$errors = 0;

/**
 * @param string $label Assertion label.
 * @param bool   $ok    Result.
 */
function ngt_integration_assert( string $label, bool $ok ): void {
	global $errors;
	if ( ! $ok ) {
		echo "FAIL: {$label}\n";
		++$errors;
	}
}

$registry = NGT_UI_Registry::all();
ngt_integration_assert( 'registry has 78 components', count( $registry ) >= 78 );

$wpb_html = NGT_UI_WPBakery::render(
	array(
		'component' => 'magic-card',
		'title'     => 'WPBakery smoke',
	),
	'Adapter content'
);
ngt_integration_assert( 'wpbakery adapter renders magic-card', false !== strpos( $wpb_html, 'data-ngt-ui="magic-card"' ) );

$globe = NGT_UI_Renderer::render( 'globe' );
ngt_integration_assert( 'globe has canvas marker', false !== strpos( $globe, 'data-ngt-canvas="globe"' ) );
ngt_integration_assert( 'globe requests three vendor', false !== strpos( $globe, 'data-ngt-needs-three="1"' ) );

$confetti = NGT_UI_Renderer::render( 'confetti' );
ngt_integration_assert( 'confetti interactive shell', false !== strpos( $confetti, 'data-ngt-interactive="confetti"' ) );

$vendor_loader = NGT_UI_LIBRARY_DIR . '/assets/js/ngt-ui-vendor-loader.js';
ngt_integration_assert( 'vendor loader file exists', is_readable( $vendor_loader ) );
$vendor_src = (string) file_get_contents( $vendor_loader );
ngt_integration_assert( 'vendor loader exposes ensureThree', false !== strpos( $vendor_src, 'ensureThree' ) );
ngt_integration_assert( 'vendor loader exposes ensureGsap', false !== strpos( $vendor_src, 'ensureGsap' ) );

$interactive_js = NGT_UI_LIBRARY_DIR . '/assets/js/components/catalog-interactive.js';
ngt_integration_assert( 'catalog-interactive file exists', is_readable( $interactive_js ) );
$interactive_src = (string) file_get_contents( $interactive_js );
ngt_integration_assert( 'interactive init calls ensureThree', false !== strpos( $interactive_src, 'ensureThree' ) );

if ( $errors > 0 ) {
	echo "\n{$errors} integration check(s) failed\n";
	exit( 1 );
}

echo "OK — integration smoke passed\n";
exit( 0 );
