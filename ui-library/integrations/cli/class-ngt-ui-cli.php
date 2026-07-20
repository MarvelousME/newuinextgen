<?php
/**
 * WP-CLI: wp ngt ui …
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * NGT UI Library CLI.
 */
class NGT_UI_CLI {

	/**
	 * List registered UI components.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table or json
	 * ---
	 * default: table
	 * ---
	 *
	 * @param array<int, string>   $args       Args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function list( $args, $assoc_args ) {
		if ( ! class_exists( 'NGT_UI_Registry' ) ) {
			WP_CLI::error( 'UI library not loaded.' );
		}
		$rows = array();
		foreach ( NGT_UI_Registry::all() as $slug => $component ) {
			$rows[] = array(
				'slug'     => $slug,
				'label'    => $component->get_label(),
				'category' => $component->get_category(),
			);
		}
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( $rows, JSON_PRETTY_PRINT ) );
			return;
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'slug', 'label', 'category' ) );
		WP_CLI::success( count( $rows ) . ' components' );
	}

	/**
	 * Validate that every component renders a data-ngt-ui marker.
	 *
	 * @param array<int, string>   $args       Args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function validate( $args, $assoc_args ) {
		if ( ! function_exists( 'ngt_render_ui_component' ) ) {
			WP_CLI::error( 'Renderer missing.' );
		}
		$fail = array();
		foreach ( NGT_UI_Registry::all() as $slug => $component ) {
			$html = ngt_render_ui_component( $slug, array( 'text' => 'CLI', 'label' => 'CLI', 'items' => 'A|B' ) );
			if ( false === strpos( $html, 'data-ngt-ui=' ) ) {
				$fail[] = $slug;
			}
		}
		if ( $fail ) {
			WP_CLI::error( 'Render failures: ' . implode( ', ', $fail ) );
		}
		WP_CLI::success( 'All components rendered markers.' );
	}
}

WP_CLI::add_command( 'ngt ui', 'NGT_UI_CLI' );
