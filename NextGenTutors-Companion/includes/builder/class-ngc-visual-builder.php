<?php
/**
 * Visual Builder module bootstrap.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Companion Visual Builder — edits theme via JSON documents + host adapter.
 */
class NGC_Visual_Builder {

	const CAP = 'ngc_builder_edit';

	/**
	 * Hook registration.
	 */
	public static function init() {
		NGC_Builder_Registry::init();
		NGC_Builder_Renderer::init();

		add_action( 'admin_init', [ __CLASS__, 'ensure_tables' ], 5 );
		add_action( 'admin_init', [ __CLASS__, 'maybe_migrate' ], 20 );
		add_filter( 'user_has_cap', [ __CLASS__, 'map_cap' ], 10, 4 );
		add_shortcode( 'ngc_builder_document', [ __CLASS__, 'shortcode_document' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_runtime' ], 30 );
	}

	/**
	 * Front-end interactivity / Lottie / reduced-motion helpers.
	 */
	public static function enqueue_runtime() {
		if ( is_admin() ) {
			return;
		}
		$path = NGC_PLUGIN_DIR . 'assets/builder/builder-runtime.js';
		if ( ! file_exists( $path ) ) {
			return;
		}
		wp_enqueue_script(
			'ngc-builder-runtime',
			NGC_PLUGIN_URL . 'assets/builder/builder-runtime.js',
			[],
			(string) filemtime( $path ),
			true
		);
	}

	/**
	 * [ngc_builder_document key="doc_home"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode_document( $atts ) {
		$atts = shortcode_atts( [ 'key' => '' ], $atts, 'ngc_builder_document' );
		$key  = sanitize_key( (string) $atts['key'] );
		if ( ! $key ) {
			return '';
		}
		return NGC_Builder_Renderer::render_document_key( $key );
	}

	/**
	 * Grant builder cap to manage_options users.
	 *
	 * @param array $allcaps All caps.
	 * @param array $caps    Requested.
	 * @param array $args    Args.
	 * @param WP_User $user  User.
	 * @return array
	 */
	public static function map_cap( $allcaps, $caps, $args, $user ) {
		if ( empty( $args[0] ) || self::CAP !== $args[0] ) {
			return $allcaps;
		}
		if ( ! empty( $allcaps['manage_options'] ) ) {
			$allcaps[ self::CAP ] = true;
		}
		return $allcaps;
	}

	/**
	 * Ensure builder tables exist after plugin updates.
	 */
	public static function ensure_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$table = NGC_Database::table( 'builder_documents' );
		if ( ! $table ) {
			return;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			NGC_Database::create_tables();
		}
	}

	/**
	 * Idempotent section → document migration.
	 */
	public static function maybe_migrate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		NGC_Builder_Migrator::migrate( false );
	}

	/**
	 * Resolved theme host (or null).
	 *
	 * @return NGC_Builder_Host|null
	 */
	public static function host() {
		/**
		 * Provide a theme host implementing NGC_Builder_Host.
		 *
		 * @param NGC_Builder_Host|null $host Host.
		 */
		$host = apply_filters( 'ngc_builder_host', null );
		return $host instanceof NGC_Builder_Host ? $host : null;
	}

	/**
	 * Host status for the editor shell.
	 *
	 * @return array<string, mixed>
	 */
	public static function host_status() {
		$host = self::host();
		if ( ! $host ) {
			return [
				'ok'      => false,
				'readOnly'=> true,
				'message' => __( 'No theme host registered. Install/activate BeyondInfinity with builder adapter.', 'nextgencompanion' ),
			];
		}
		return [
			'ok'              => true,
			'readOnly'        => false,
			'contractVersion' => $host->contract_version(),
			'slots'           => $host->slots(),
			'sectionCount'    => count( $host->sections() ),
		];
	}

	/**
	 * Permission helper.
	 *
	 * @return bool
	 */
	public static function can_edit() {
		return current_user_can( self::CAP ) || current_user_can( 'manage_options' );
	}
}
