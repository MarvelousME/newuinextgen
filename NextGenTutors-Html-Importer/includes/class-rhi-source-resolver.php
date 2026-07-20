<?php
/**
 * Resolves HTML source directory across Docker, Windows dev, and custom paths.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cross-platform source path resolver for webpages-content.
 */
class RHI_Source_Resolver {

	const OPTION_KEY   = 'rhi_source_directory';
	const DOCKER_MOUNT = '/var/www/html/wp-content/ngt-html-source';

	/**
	 * Candidate directories in priority order.
	 *
	 * @return string[]
	 */
	public static function candidates() {
		$candidates = [];

		if ( defined( 'RHI_HTML_SOURCE_DIR' ) && RHI_HTML_SOURCE_DIR ) {
			$candidates[] = (string) RHI_HTML_SOURCE_DIR;
		}

		$candidates[] = self::DOCKER_MOUNT;

		$env = getenv( 'NGT_HTML_SOURCE' );
		if ( is_string( $env ) && $env ) {
			$candidates[] = $env;
		}

		// REVAMP repo root: wp-content/plugins/NextGenTutors-Html-Importer → ../../webpages-content
		$repo_relative = wp_normalize_path( dirname( dirname( dirname( RHI_PLUGIN_DIR ) ) ) . '/webpages-content' );
		$candidates[]  = $repo_relative;

		$candidates[] = 'C:/Users/marvi/Music/REVAMP/webpages-content';

		$unique = [];
		foreach ( $candidates as $path ) {
			$path = wp_normalize_path( $path );
			if ( $path && ! in_array( $path, $unique, true ) ) {
				$unique[] = $path;
			}
		}

		return $unique;
	}

	/**
	 * Resolve the best readable source directory.
	 *
	 * @param bool $persist Save discovered path to options.
	 * @return string
	 */
	public static function resolve( $persist = true ) {
		$saved = (string) get_option( self::OPTION_KEY, '' );
		if ( $saved ) {
			$saved = wp_normalize_path( $saved );
			if ( is_dir( $saved ) && is_readable( $saved ) ) {
				return $saved;
			}
		}

		foreach ( self::candidates() as $path ) {
			if ( is_dir( $path ) && is_readable( $path ) ) {
				if ( $persist ) {
					update_option( self::OPTION_KEY, $path, false );
				}
				return $path;
			}
		}

		return $saved ?: self::DOCKER_MOUNT;
	}

	/**
	 * Scan and cache mappings on activation / Docker bootstrap.
	 *
	 * @return array<string, mixed>
	 */
	public static function bootstrap() {
		$dir = self::resolve( true );
		if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
			return [
				'ok'    => false,
				'path'  => $dir,
				'count' => 0,
				'error' => __( 'HTML source directory not found or not readable.', 'revamp-html-importer' ),
			];
		}

		$result = RHI_Scanner::scan( $dir );
		if ( is_wp_error( $result ) ) {
			return [
				'ok'    => false,
				'path'  => $dir,
				'count' => 0,
				'error' => $result->get_error_message(),
			];
		}

		set_transient( 'rhi_last_scan', $result, HOUR_IN_SECONDS );
		update_option( 'rhi_bootstrap_at', gmdate( 'c' ), false );

		return [
			'ok'    => true,
			'path'  => $dir,
			'count' => count( $result ),
		];
	}
}
