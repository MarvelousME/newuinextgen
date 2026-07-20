<?php
/**
 * HTML file scanner.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recursively scans a directory for HTML files.
 */
class RHI_Scanner {

	/**
	 * @param string $directory Absolute directory path.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public static function scan( $directory ) {
		$directory = wp_normalize_path( $directory );
		if ( ! is_dir( $directory ) || ! is_readable( $directory ) ) {
			return new WP_Error( 'rhi_invalid_dir', __( 'Directory not found or not readable.', 'revamp-html-importer' ) );
		}

		$files = [];
		$it    = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $it as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$ext = strtolower( $file->getExtension() );
			if ( ! in_array( $ext, [ 'html', 'htm' ], true ) ) {
				continue;
			}
			$path = wp_normalize_path( $file->getPathname() );
			$files[] = self::analyze_file( $path, $directory );
		}

		usort(
			$files,
			static function ( $a, $b ) {
				return strcmp( $a['relative_path'], $b['relative_path'] );
			}
		);

		return $files;
	}

	/**
	 * @param string $path      Absolute file path.
	 * @param string $base_dir  Scan base directory.
	 * @return array<string, mixed>
	 */
	public static function analyze_file( $path, $base_dir ) {
		$raw      = file_get_contents( $path );
		$hash     = hash( 'sha256', (string) $raw );
		$relative = ltrim( str_replace( wp_normalize_path( $base_dir ), '', wp_normalize_path( $path ) ), '/' );

		$parser  = new RHI_Html_Parser( $path );
		$parsed  = $parser->parse();
		$matched = RHI_Page_Matcher::match_file( $relative, $parsed );

		return array_merge(
			[
				'absolute_path' => $path,
				'relative_path' => $relative,
				'filename'      => basename( $path ),
				'source_hash'   => $hash,
				'filesize'      => filesize( $path ),
				'modified'      => gmdate( 'c', filemtime( $path ) ),
			],
			$parsed,
			$matched
		);
	}
}
