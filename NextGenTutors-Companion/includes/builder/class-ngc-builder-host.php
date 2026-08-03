<?php
/**
 * Theme host contract for the Visual Builder.
 *
 * Themes implement this interface and register via `ngc_builder_host` filter.
 * Companion never requires theme file paths — only stable section/component ids.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stable adapter between Companion Visual Builder and a rendering theme.
 */
interface NGC_Builder_Host {

	/**
	 * Contract version (semver string).
	 *
	 * @return string
	 */
	public function contract_version(): string;

	/**
	 * Editable slots on the page chrome (e.g. main, hero_overlay).
	 *
	 * @return array<string, array{label: string, description?: string}>
	 */
	public function slots(): array;

	/**
	 * Registered theme sections available as theme.section nodes.
	 *
	 * @return array<string, array{
	 *   id: string,
	 *   label: string,
	 *   pageKeys?: string[],
	 *   propSchema?: array,
	 *   defaultProps?: array
	 * }>
	 */
	public function sections(): array;

	/**
	 * Absolute filesystem path to the theme tokens CSS (optional).
	 *
	 * @return string
	 */
	public function tokens_css_path(): string;

	/**
	 * Render a theme section by id. Returns HTML string.
	 *
	 * @param string               $section_id Section registry id.
	 * @param array<string, mixed> $props      Node props + resolved content.
	 * @return string
	 */
	public function render_section( string $section_id, array $props = [] ): string;
}
