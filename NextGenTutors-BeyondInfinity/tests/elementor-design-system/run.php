<?php
/**
 * NextGen Elementor Design System tests (no live WordPress required).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/stub/' );
}
if ( ! defined( 'BI_DIR' ) ) {
	define( 'BI_DIR', dirname( __DIR__, 2 ) );
}
if ( ! defined( 'BI_URI' ) ) {
	define( 'BI_URI', 'https://example.test/theme' );
}
if ( ! defined( 'BI_VERSION' ) ) {
	define( 'BI_VERSION', 'test' );
}

$fail = 0;
$pass = 0;

function bi_el_assert( $cond, $msg ) {
	global $fail, $pass;
	if ( $cond ) {
		$pass++;
		echo "PASS  $msg\n";
		return;
	}
	$fail++;
	echo "FAIL  $msg\n";
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $class ) {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $t, $d = '' ) {
		return $t;
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $t ) {
		return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $t ) {
		return esc_html( $t );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $u ) {
		return $u;
	}
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $t, $d = '' ) {
		return esc_attr( $t );
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $t, $d = '' ) {
		return esc_html( $t );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $d ) {
		return json_encode( $d );
	}
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $p = '' ) {
		return 'https://example.test' . $p;
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $k, $v, $url ) {
		return $url . '?' . rawurlencode( $k ) . '=' . rawurlencode( $v );
	}
}
if ( ! function_exists( 'shortcode_exists' ) ) {
	function shortcode_exists( $t ) {
		return false;
	}
}
if ( ! function_exists( 'do_shortcode' ) ) {
	function do_shortcode( $c ) {
		return $c;
	}
}
if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $id, $key, $single = false ) {
		return $GLOBALS['bi_el_test_meta'][ $id ][ $key ] ?? '';
	}
}

require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-presets.php';
require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-inventory.php';
require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-motion.php';
require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-data.php';
require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-renders.php';
require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-templates.php';
require_once BI_DIR . '/inc/elementor-design-system/class-bi-el-happy-addons.php';

bi_el_assert( 23 === BI_EL_Inventory::count(), 'widget registration inventory is 23' );

$ids = array_keys( BI_EL_Inventory::all() );
foreach ( [ 'hero', 'find-tutor', 'tutor-grid', 'tutor-card', 'subject-grid', 'subject-card', 'tutor-filmstrip', 'testimonials', 'pricing', 'stats', 'faq', 'booking-cta', 'animated-heading', 'gsap-text-reveal', '3d-tilt-card', '3d-scene', 'scroll-mask', 'zoom-reveal', 'horizontal-scroll', 'sticky-story', 'particle-background', 'orb-background', 'webgl-background' ] as $need ) {
	bi_el_assert( in_array( $need, $ids, true ), "inventory has $need" );
}

$settings_off = [ 'ngt_motion_enable' => '' ];
bi_el_assert( null === BI_EL_Motion::compile_rule( $settings_off, 'abc' ), 'motion disabled compiles to null' );

$settings_on = [
	'ngt_motion_enable'  => 'yes',
	'ngt_motion_preset'  => 'depth-hero',
	'ngt_scroll_trigger' => 'yes',
	'ngt_start'          => 'top 80%',
	'ngt_end'            => 'bottom top',
	'ngt_scrub'          => 1,
	'ngt_perspective'    => [ 'size' => 1400 ],
	'ngt_depth'          => 90,
	'ngt_x'              => 0,
	'ngt_y'              => 24,
	'ngt_z'              => 40,
	'ngt_rotate_x'       => 8,
	'ngt_rotate_y'       => 0,
	'ngt_rotate_z'       => 0,
	'ngt_scale'          => 0.92,
	'ngt_opacity'        => 0,
	'ngt_stagger'        => 0.1,
	'ngt_mouse'          => 'yes',
	'ngt_reduced_motion' => 'yes',
	'ngt_disable_tablet' => '',
	'ngt_disable_mobile' => 'yes',
];
$rule = BI_EL_Motion::compile_rule( $settings_on, 'el123' );
bi_el_assert( is_array( $rule ), 'motion enabled compiles a rule' );
bi_el_assert( false !== strpos( (string) $rule['animation_names'], 'depth-hero' ), 'rule uses NGT3D preset id' );
bi_el_assert( false !== strpos( (string) $rule['animation_names'], 'tilt-3d' ), 'mouse interaction adds tilt-3d' );
bi_el_assert( 'disabled' === $rule['mobile_mode'], 'disable on mobile maps to NGT3D mobileMode' );
bi_el_assert( '.elementor-element-el123 .bi-el' === $rule['target_selector'], 'selector targets Elementor element' );
bi_el_assert( 'elementor-design-system' === $rule['animation_options']['source'], 'rule tagged as Elementor DS' );

$attrs = BI_EL_Motion::html_attrs( $settings_on, 'el123' );
bi_el_assert( '1' === $attrs['data-ngt-el-motion'], 'html attrs enable motion' );
bi_el_assert( '1' === $attrs['data-ngt-reduced'], 'reduced motion honored in attrs' );

$GLOBALS['bi_el_test_meta'][42]['_elementor_data'] = wp_json_encode( [
	[
		'id'       => 'sec1',
		'elType'   => 'container',
		'elements' => [
			[
				'id'         => 'w1',
				'elType'     => 'widget',
				'widgetType' => 'bi_el_hero',
				'settings'   => $settings_on,
			],
		],
	],
] );
$from_doc = BI_EL_Motion::rules_from_elementor_document( 42 );
bi_el_assert( 1 === count( $from_doc ), 'parses Elementor document for compiled rules' );
bi_el_assert( BI_EL_Motion::page_has_widgets( 42 ), 'page_has_widgets detects bi_el_ widgets' );

bi_el_assert( [] === BI_EL_Data::tutors(), 'missing Companion returns no fake tutors' );
bi_el_assert( [] === BI_EL_Data::reviews(), 'missing Companion returns no fake testimonials' );
bi_el_assert( false === BI_EL_Happy_Addons::is_active(), 'Happy Addons absent is non-fatal' );

$modes = [];
foreach ( [ 'editor', 'preview', 'frontend' ] as $mode ) {
	ob_start();
	BI_EL_Renders::render( 'animated-heading', [ 'heading' => 'Hello', 'heading_tag' => 'h2' ], [ 'mode' => $mode ] );
	$html = ob_get_clean();
	$modes[ $mode ] = $html;
	bi_el_assert( false !== strpos( $html, 'Hello' ), "frontend rendering in $mode contains heading" );
	bi_el_assert( false !== strpos( $html, 'data-motion-text-fallback' ), "$mode uses NGT3D text fallback hook" );
}

ob_start();
BI_EL_Renders::render( '3d-scene', [ 'webgl_quality' => 'low', 'heading' => 'Scene' ], [ 'mode' => 'frontend' ] );
$webgl = ob_get_clean();
bi_el_assert( false !== strpos( $webgl, 'data-bi-el-webgl' ), 'WebGL widget emits lazy host' );
bi_el_assert( false !== strpos( $webgl, 'bi-el__webgl-fallback' ), 'missing Three.js still has CSS fallback' );
bi_el_assert( false !== strpos( $webgl, '<noscript>' ), 'no-JS message present' );

ob_start();
BI_EL_Renders::render( 'tutor-grid', [ 'limit' => 6 ], [ 'mode' => 'frontend' ] );
$grid = ob_get_clean();
bi_el_assert( false === strpos( strtolower( $grid ), 'jane doe' ), 'production path has no fake tutor names' );

$cat = BI_EL_Templates::catalog();
bi_el_assert( 10 === count( $cat ), 'template library has 10 pages' );
foreach ( array_keys( $cat ) as $tid ) {
	$doc = BI_EL_Templates::document( $tid );
	bi_el_assert( ! empty( $doc ), "template $tid is non-empty Elementor JSON" );
	$json = wp_json_encode( $doc );
	bi_el_assert( false !== strpos( $json, '"elType":"widget"' ), "template $tid is editable Elementor widgets" );
}

$presets = BI_EL_Presets::choices();
bi_el_assert( 10 === count( $presets ), '10 visual presets registered' );
bi_el_assert( 'glass' === BI_EL_Presets::sanitize( 'nope' ), 'unknown preset falls back to glass' );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
