<?php
/**
 * Front-end renderer for published builder documents.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SSR output + token CSS on the public site.
 */
class NGC_Builder_Renderer {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_tokens' ], 20 );
		add_filter( 'the_content', [ __CLASS__, 'maybe_replace_content' ], 12 );
		add_action( 'wp_footer', [ __CLASS__, 'print_interactivity' ], 5 );
	}

	/**
	 * Emit global builder token CSS.
	 */
	public static function enqueue_tokens() {
		if ( is_admin() ) {
			return;
		}
		$css = NGC_Builder_Tokens::to_css();
		wp_register_style( 'ngc-builder-tokens', false, [], NGC_VERSION );
		wp_enqueue_style( 'ngc-builder-tokens' );
		wp_add_inline_style( 'ngc-builder-tokens', $css );
	}

	/**
	 * Replace page content when a published builder document is bound.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function maybe_replace_content( $content ) {
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		/**
		 * Disable automatic content replacement.
		 *
		 * @param bool $enabled Default true.
		 */
		if ( ! apply_filters( 'ngc_builder_replace_content', true ) ) {
			return $content;
		}

		$doc_row = NGC_Builder_Repository::get_for_post( get_the_ID() );
		if ( ! $doc_row || ( $doc_row['status'] ?? '' ) !== 'published' ) {
			return $content;
		}

		$compiled = NGC_Builder_Compiler::compile( $doc_row['document'] );
		self::stash_compiled( $compiled );

		$handle = 'ngc-builder-doc-' . sanitize_key( $doc_row['document_key'] );
		wp_register_style( $handle, false, [ 'ngc-builder-tokens' ], $compiled['hash'] );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $compiled['css'] );

		return $compiled['html'];
	}

	/**
	 * Render a document by key (for templates / shortcodes).
	 *
	 * @param string $key Document key.
	 * @return string
	 */
	public static function render_document_key( $key ) {
		$row = NGC_Builder_Repository::get_by_key( $key );
		if ( ! $row ) {
			return '';
		}
		$compiled = NGC_Builder_Compiler::compile( $row['document'] );
		self::stash_compiled( $compiled );
		return $compiled['html'];
	}

	/**
	 * Print Interactivity API config when present.
	 */
	public static function print_interactivity() {
		$compiled = self::stash_compiled( null );
		if ( empty( $compiled['interactivity'] ) ) {
			return;
		}
		if ( function_exists( 'wp_interactivity_config' ) ) {
			wp_interactivity_config( 'ngc/builder', [ 'nodes' => $compiled['interactivity'] ] );
		}
		$config = wp_json_encode( [ 'nodes' => $compiled['interactivity'] ] );
		echo '<script type="application/json" id="ngc-builder-ix">' . $config . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array|null $set Set or null to get.
	 * @return array|null
	 */
	private static function stash_compiled( $set ) {
		static $store = null;
		if ( null !== $set ) {
			$store = $set;
		}
		return $store;
	}
}
