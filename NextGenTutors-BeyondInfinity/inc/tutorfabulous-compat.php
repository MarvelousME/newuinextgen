<?php
/**
 * NextgenTutors-TutorFabulous — brand + required-plugin compatibility.
 *
 * Keeps Text Domain `beyondinfinity` and `bi_*` APIs so Companion / NGT3D /
 * Elementor design-system plugins continue to work without code renames.
 *
 * @package TutorFabulous
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BI_THEME_BRAND' ) ) {
	define( 'BI_THEME_BRAND', 'NextgenTutors-TutorFabulous' );
}
if ( ! defined( 'BI_THEME_LEGACY_NAME' ) ) {
	define( 'BI_THEME_LEGACY_NAME', 'NextGenTutors-BeyondInfinity' );
}

/**
 * Required / recommended plugins for TutorFabulous.
 *
 * @return array<int, array{slug:string,name:string,file:string,required:bool}>
 */
function bi_tutorfabulous_required_plugins() {
	return [
		[
			'slug'     => 'elementor',
			'name'     => 'Elementor',
			'file'     => 'elementor/elementor.php',
			'required' => true,
		],
		[
			'slug'     => 'NextGenTutors-Companion/nextgencompanion',
			'name'     => 'NextGenTutors Companion',
			'file'     => 'NextGenTutors-Companion/nextgencompanion.php',
			'required' => true,
		],
		[
			'slug'     => 'nextgen-3d-scroll-manager/nextgen-3d-scroll-manager',
			'name'     => 'NextGen 3D Scroll Manager (NGT3D)',
			'file'     => 'nextgen-3d-scroll-manager/nextgen-3d-scroll-manager.php',
			'required' => true,
		],
		[
			'slug'     => 'NextGenTutors-AI-Integration/nextgentutors-ai-integration',
			'name'     => 'NextGen Tutors AI Integration',
			'file'     => 'NextGenTutors-AI-Integration/nextgentutors-ai-integration.php',
			'required' => false,
		],
		[
			'slug'     => 'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager',
			'name'     => 'NextGen Tutors Plugin Manager',
			'file'     => 'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php',
			'required' => false,
		],
		[
			'slug'     => 'NextGenTutors-Html-Importer/revamp-html-importer',
			'name'     => 'NextGen HTML Importer',
			'file'     => 'NextGenTutors-Html-Importer/revamp-html-importer.php',
			'required' => false,
		],
		[
			'slug'     => 'nextgen-3d-filmstrip/nextgen-3d-filmstrip',
			'name'     => 'NextGen 3D Filmstrip',
			'file'     => 'nextgen-3d-filmstrip/nextgen-3d-filmstrip.php',
			'required' => false,
		],
		[
			'slug'     => 'nextgen-subjects-widget/nextgen-subjects-widget',
			'name'     => 'NextGen Subjects Widget',
			'file'     => 'nextgen-subjects-widget/nextgen-subjects-widget.php',
			'required' => false,
		],
		[
			'slug'     => 'nextgen-automation-hub/nextgen-automation-hub',
			'name'     => 'NextGen Automation Hub',
			'file'     => 'nextgen-automation-hub/nextgen-automation-hub.php',
			'required' => false,
		],
	];
}

/**
 * @return array{ok:bool,missing_required:string[],missing_optional:string[],active:string[]}
 */
function bi_tutorfabulous_plugin_status() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$missing_required = [];
	$missing_optional = [];
	$active           = [];

	foreach ( bi_tutorfabulous_required_plugins() as $plugin ) {
		$file = $plugin['file'];
		$on   = is_plugin_active( $file ) || ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $file ) );
		// Companion may boot as class without standard activation path in some mounts.
		if ( ! $on && false !== strpos( $file, 'nextgencompanion' ) && class_exists( 'NGC_Plugin', false ) ) {
			$on = true;
		}
		if ( $on ) {
			$active[] = $plugin['name'];
			continue;
		}
		if ( ! empty( $plugin['required'] ) ) {
			$missing_required[] = $plugin['name'];
		} else {
			$missing_optional[] = $plugin['name'];
		}
	}

	return [
		'ok'               => empty( $missing_required ),
		'missing_required' => $missing_required,
		'missing_optional' => $missing_optional,
		'active'           => $active,
	];
}

/**
 * Admin notice when Companion / required plugins are missing.
 */
function bi_tutorfabulous_admin_plugin_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$status = bi_tutorfabulous_plugin_status();
	if ( $status['ok'] ) {
		return;
	}
	$list = esc_html( implode( ', ', $status['missing_required'] ) );
	echo '<div class="notice notice-error"><p><strong>' . esc_html( BI_THEME_BRAND ) . ':</strong> ';
	echo esc_html__( 'Required plugins are inactive or missing: ', 'beyondinfinity' );
	echo $list . '. ';
	echo esc_html__( 'Activate NextGenTutors-Companion and Elementor so marketplace shortcodes and dashboards work.', 'beyondinfinity' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'bi_tutorfabulous_admin_plugin_notice' );

/**
 * Expose brand + plugin health to front-end / Elementor editor.
 *
 * @param array $data Existing biLocalization / similar.
 * @return array
 */
function bi_tutorfabulous_localize_brand( $data ) {
	if ( ! is_array( $data ) ) {
		$data = [];
	}
	$data['themeBrand']   = BI_THEME_BRAND;
	$data['themeLegacy']  = BI_THEME_LEGACY_NAME;
	$data['companionOn']  = function_exists( 'bi_companion_active' ) ? bi_companion_active() : class_exists( 'NGC_Plugin', false );
	$data['pluginHealth'] = bi_tutorfabulous_plugin_status();
	return $data;
}
add_filter( 'bi_front_localize', 'bi_tutorfabulous_localize_brand' );

/**
 * Declare theme supports Companion needs.
 */
function bi_tutorfabulous_theme_supports() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'elementor' );
	add_theme_support( 'nextgen-companion' );
	add_theme_support( 'ngt3d' );
}
add_action( 'after_setup_theme', 'bi_tutorfabulous_theme_supports', 5 );

/**
 * Body class for CSS targeting.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function bi_tutorfabulous_body_class( $classes ) {
	$classes[] = 'theme-nextgentutors-tutorfabulous';
	$classes[] = 'theme-brand-tutorfabulous';
	if ( function_exists( 'bi_companion_active' ) && bi_companion_active() ) {
		$classes[] = 'ngc-companion-active';
	} else {
		$classes[] = 'ngc-companion-fallback';
	}
	return $classes;
}
add_filter( 'body_class', 'bi_tutorfabulous_body_class' );
