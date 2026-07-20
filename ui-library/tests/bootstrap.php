<?php
/**
 * Minimal WordPress stubs for ui-library render tests (no full WP bootstrap).
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/stub/' );
}

$ui_root = dirname( __DIR__ );
if ( ! defined( 'NGT_UI_LIBRARY_DIR' ) ) {
	define( 'NGT_UI_LIBRARY_DIR', $ui_root );
}
if ( ! defined( 'NGT_UI_LIBRARY_URL' ) ) {
	define( 'NGT_UI_LIBRARY_URL', 'https://example.test/wp-content/ngt-ui-library/' );
}
if ( ! defined( 'NGT_UI_LIBRARY_VERSION' ) ) {
	define( 'NGT_UI_LIBRARY_VERSION', 'test' );
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	function sanitize_hex_color( $color ) {
		$color = (string) $color;
		if ( preg_match( '/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color ) ) {
			return $color;
		}
		return '';
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return strip_tags( (string) $data, '<a><abbr><b><blockquote><br><code><em><i><li><ol><p><strong><ul><span><div>' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data, JSON_UNESCAPED_UNICODE );
	}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
	function wp_unique_id( $prefix = '' ) {
		static $id = 0;
		return $prefix . (string) ++$id;
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'did_action' ) ) {
	function did_action( $hook ) {
		unset( $hook );
		return false;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		unset( $capability );
		return false;
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, (int) $decimals );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
		unset( $shortcode );
		$atts = is_array( $atts ) ? $atts : array();
		$out  = array();
		foreach ( $pairs as $name => $default ) {
			$out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
		}
		return $out;
	}
}

if ( ! function_exists( 'wp_register_style' ) ) {
	function wp_register_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		unset( $handle, $src, $deps, $ver, $media );
	}
}

if ( ! function_exists( 'wp_register_script' ) ) {
	function wp_register_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		unset( $handle, $src, $deps, $ver, $in_footer );
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		unset( $handle, $src, $deps, $ver, $media );
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		unset( $handle, $src, $deps, $ver, $in_footer );
	}
}

if ( ! function_exists( 'wp_style_is' ) ) {
	function wp_style_is( $handle, $list = 'enqueued' ) {
		unset( $handle, $list );
		return false;
	}
}

if ( ! function_exists( 'wp_script_is' ) ) {
	function wp_script_is( $handle, $list = 'enqueued' ) {
		unset( $handle, $list );
		return false;
	}
}

require_once $ui_root . '/contracts/interface-ngt-ui-component.php';
require_once $ui_root . '/contracts/class-ngt-ui-component-base.php';
require_once $ui_root . '/registry/class-ngt-ui-registry.php';
require_once $ui_root . '/registry/class-ngt-ui-assets.php';
require_once $ui_root . '/rendering/class-ngt-ui-renderer.php';
require_once $ui_root . '/rendering/class-ngt-ui-kind-registry.php';
require_once $ui_root . '/components/class-ngt-ui-magic-card.php';
require_once $ui_root . '/components/class-ngt-ui-border-beam.php';
require_once $ui_root . '/components/class-ngt-ui-marquee.php';
require_once $ui_root . '/components/class-ngt-ui-income-calculator.php';
require_once $ui_root . '/tokens/class-ngt-ui-tokens.php';
require_once $ui_root . '/components/class-ngt-ui-catalog-component.php';
require_once $ui_root . '/components/class-ngt-ui-catalog-loader.php';

NGT_UI_Registry::register( new NGT_UI_Magic_Card() );
NGT_UI_Registry::register( new NGT_UI_Border_Beam() );
NGT_UI_Registry::register( new NGT_UI_Marquee() );
NGT_UI_Registry::register( new NGT_UI_Income_Calculator() );
NGT_UI_Catalog_Loader::register_all();
