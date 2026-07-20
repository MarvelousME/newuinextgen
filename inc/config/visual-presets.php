<?php
/**
 * Visual preset registry — skins that swap tokens only, never component CSS.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'bi_register_visual_presets', 1 );

/**
 * Register available visual presets (skins).
 */
function bi_register_visual_presets() {
	$presets = apply_filters(
		'bi_filter_visual_presets',
		[
			'beyond-infinity' => [
				'title'       => __( 'Beyond Infinity', 'beyondinfinity' ),
				'description' => __( 'Default BI design system — Sora/Inter, navy & emerald.', 'beyondinfinity' ),
				'skin_file'   => 'beyond-infinity.css',
				'ngt_skin'    => false,
				'default_scheme' => 'default',
			],
			'ngt-marketing' => [
				'title'       => __( 'NGT Marketing', 'beyondinfinity' ),
				'description' => __( 'Prototype navy/lime look with NGT component skin.', 'beyondinfinity' ),
				'skin_file'   => 'ngt-marketing.css',
				'ngt_skin'    => true,
				'default_scheme' => 'default',
			],
			'classic-indigo' => [
				'title'       => __( 'Classic Indigo', 'beyondinfinity' ),
				'description' => __( 'Indigo/emerald marketing palette — Syne/DM Sans.', 'beyondinfinity' ),
				'skin_file'   => 'classic-indigo.css',
				'ngt_skin'    => false,
				'default_scheme' => 'default',
			],
		]
	);
	bi_storage_set( 'visual_presets', $presets );
}

/**
 * @return array<string, array<string, mixed>>
 */
function bi_get_visual_presets() {
	if ( ! bi_storage_isset( 'visual_presets' ) ) {
		bi_register_visual_presets();
	}
	return (array) bi_storage_get( 'visual_presets', [] );
}

/**
 * @param string $id Preset id.
 * @return array<string, mixed>|null
 */
function bi_get_visual_preset( $id = '' ) {
	$presets = bi_get_visual_presets();
	if ( '' === $id ) {
		$id = bi_resolve_visual_preset_id();
	}
	return $presets[ $id ] ?? $presets['beyond-infinity'] ?? null;
}

/**
 * Resolve active preset: preview cookie → theme option → default.
 *
 * @return string
 */
function bi_resolve_visual_preset_id() {
	$presets = bi_get_visual_presets();
	$default = 'beyond-infinity';

	if ( ! empty( $_COOKIE['bi_skin_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cookie = sanitize_key( wp_unslash( $_COOKIE['bi_skin_preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $presets[ $cookie ] ) ) {
			return $cookie;
		}
	}

	$option = sanitize_key( (string) bi_get_theme_option( 'visual_preset', $default ) );
	if ( isset( $presets[ $option ] ) ) {
		return $option;
	}

	return $default;
}

/**
 * Whether the NGT overlay skin should load for the active preset.
 *
 * @return bool
 */
function bi_visual_preset_uses_ngt_skin() {
	$preset = bi_get_visual_preset();
	return ! empty( $preset['ngt_skin'] );
}
