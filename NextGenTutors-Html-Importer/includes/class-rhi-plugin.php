<?php
/**
 * Plugin bootstrap.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton.
 */
final class RHI_Plugin {

	/** @var RHI_Plugin|null */
	private static $instance = null;

	/** @return RHI_Plugin */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Plugin activation — resolve source path and cache scan.
	 */
	public static function activate() {
		if ( class_exists( 'RHI_Source_Resolver' ) ) {
			RHI_Source_Resolver::bootstrap();
		}
	}

	public function init() {
		if ( is_admin() ) {
			require_once RHI_PLUGIN_DIR . 'admin/class-rhi-admin.php';
			RHI_Admin::init();
		}
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_content_css' ], 30 );
		add_filter( 'body_class', [ $this, 'body_class' ] );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		if ( is_singular( 'page' ) && get_post_meta( get_queried_object_id(), '_revamp_source_html_file', true ) ) {
			$classes[] = 'rhi-imported-page';
		}
		return $classes;
	}

	/**
	 * Enqueue scoped content CSS on pages imported by this plugin.
	 */
	public function maybe_enqueue_content_css() {
		if ( ! is_singular( 'page' ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! get_post_meta( $post_id, '_revamp_source_html_file', true ) ) {
			return;
		}
		wp_enqueue_style(
			'rhi-imported-content',
			RHI_PLUGIN_URL . 'assets/importer-content.css',
			[],
			RHI_VERSION
		);
	}
}
