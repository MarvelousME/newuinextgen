<?php
/**
 * Stage 5 smoke: full Magic UI registry + shortcode HTML.
 */
$out = array(
	'ui_dir'     => defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : null,
	'ui_url'     => defined( 'NGT_UI_LIBRARY_URL' ) ? NGT_UI_LIBRARY_URL : null,
	'booted'     => did_action( 'ngt_ui_library_booted' ) > 0,
	'registry'   => class_exists( 'NGT_UI_Registry' ) ? array_keys( NGT_UI_Registry::all() ) : array(),
	'shortcodes' => array(
		'ngt_ui'         => shortcode_exists( 'ngt_ui' ),
		'ngt_magic_card' => shortcode_exists( 'ngt_magic_card' ),
		'ngt_aurora_text'=> shortcode_exists( 'ngt_aurora_text' ),
		'ngt_particles'  => shortcode_exists( 'ngt_particles' ),
	),
);

$out['count'] = count( $out['registry'] );

$samples = array(
	'magic-card'   => '[ngt_magic_card title="Find a Tutor" content="Match with vetted educators near you."]',
	'border-beam'  => '[ngt_border_beam]Secure bookings with live Companion data.[/ngt_border_beam]',
	'marquee'      => '[ngt_marquee items="Math|Science|English|Accounting|Physics"]',
	'aurora-text'  => '[ngt_ui component="aurora-text" text="Aurora Learning"]',
	'shimmer-button'=> '[ngt_ui component="shimmer-button" label="Book a session"]',
	'number-ticker'=> '[ngt_ui component="number-ticker" from="0" to="1280"]',
	'particles'    => '[ngt_ui component="particles"]',
	'iphone'       => '[ngt_ui component="iphone" text="Student app"]',
	'bento-grid'   => '[ngt_ui component="bento-grid" items="Tutors|Subjects|Bookings|Progress|Parents|Live"]',
	'terminal'     => '[ngt_ui component="terminal" items="ngt ui build|✓ 76 components|Ready on :8900"]',
);

$out['html']     = array();
$out['markers']  = array();
$failures        = array();

foreach ( $samples as $slug => $shortcode ) {
	$html = do_shortcode( $shortcode );
	$out['html'][ $slug ] = $html;
	$ok = false !== strpos( $html, 'data-ngt-ui="' . $slug . '"' );
	$out['markers'][ $slug ] = $ok;
	if ( ! $ok ) {
		$failures[] = $slug;
	}
}

// Spot-check every registered component renders a marker.
$out['render_failures'] = array();
foreach ( $out['registry'] as $slug ) {
	$html = ngt_render_ui_component( $slug, array( 'text' => 'Demo', 'label' => 'Demo', 'items' => 'A|B|C' ) );
	if ( false === strpos( $html, 'data-ngt-ui="' . $slug . '"' ) && false === strpos( $html, 'data-ngt-ui="' . $slug . '"' ) ) {
		// Dedicated components also use data-ngt-ui.
		if ( false === strpos( $html, 'data-ngt-ui=' ) ) {
			$out['render_failures'][] = $slug;
		}
	}
}

$blocks = array();
$blocks[] = "<!-- wp:heading {\"level\":1} -->\n<h1>NGT UI Library — Magic UI (76)</h1>\n<!-- /wp:heading -->";
$blocks[] = "<!-- wp:paragraph -->\n<p>Canonical renderer + shortcodes. No React iframes.</p>\n<!-- /wp:paragraph -->";
foreach ( array( 'magic-card', 'border-beam', 'marquee', 'aurora-text', 'animated-gradient-text', 'shimmer-button', 'rainbow-button', 'neon-gradient-card', 'shine-border', 'meteors', 'ripple', 'retro-grid', 'dot-pattern', 'flickering-grid', 'number-ticker', 'typing-animation', 'word-rotate', 'morphing-text', 'particles', 'orbiting-circles', 'animated-beam', 'bento-grid', 'avatar-circles', 'dock', 'file-tree', 'terminal', 'code-comparison', 'iphone', 'safari', 'android', 'scroll-progress', 'animated-circular-progress-bar', 'confetti', 'globe', 'tweet-card', 'highlighter', 'comic-text', 'spinning-text', 'light-rays', 'dotted-map' ) as $slug ) {
	$blocks[] = "<!-- wp:heading {\"level\":3} -->\n<h3>" . esc_html( $slug ) . "</h3>\n<!-- /wp:heading -->";
	$blocks[] = "<!-- wp:shortcode -->\n[ngt_ui component=\"" . esc_attr( $slug ) . "\" text=\"" . esc_attr( $slug ) . "\" label=\"" . esc_attr( $slug ) . "\" items=\"Math|Science|English|Design|Build|Ship\" from=\"0\" to=\"100\" value=\"72\"]\n<!-- /wp:shortcode -->";
}
$content = implode( "\n\n", $blocks );

$page = get_page_by_path( 'ngt-ui-demo' );
if ( ! $page ) {
	$id = wp_insert_post(
		array(
			'post_title'   => 'NGT UI Demo',
			'post_name'    => 'ngt-ui-demo',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $content,
		)
	);
	$out['demo_page_id'] = $id;
	$out['demo_updated'] = true;
} else {
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $content,
		)
	);
	$out['demo_page_id'] = $page->ID;
	$out['demo_updated'] = true;
}
$out['demo_url'] = home_url( '/ngt-ui-demo/' );
$out['sample_failures'] = $failures;
$out['ok'] = empty( $failures ) && empty( $out['render_failures'] ) && $out['count'] >= 76;

echo wp_json_encode( $out, JSON_PRETTY_PRINT ) . "\n";
