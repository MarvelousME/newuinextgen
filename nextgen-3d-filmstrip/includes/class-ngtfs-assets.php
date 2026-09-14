<?php
/**
 * Asset registration.
 *
 * @package NextGen_3D_Filmstrip
 */

defined( 'ABSPATH' ) || exit;

/**
 * CSS/JS enqueue.
 */
final class NGTFS_Assets {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'register' ] );
	}

	public static function register(): void {
		wp_register_style(
			'ngtfs-filmstrip',
			NGTFS_URL . 'assets/css/filmstrip.css',
			[],
			NGTFS_VERSION
		);
		wp_register_script(
			'ngtfs-filmstrip',
			NGTFS_URL . 'assets/js/filmstrip.js',
			[],
			NGTFS_VERSION,
			true
		);
	}

	public static function enqueue(): void {
		wp_enqueue_style( 'ngtfs-filmstrip' );
		wp_enqueue_script( 'ngtfs-filmstrip' );
	}
}
