<?php
/**
 * Content importer orchestrator.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports HTML content into WordPress pages.
 */
class RHI_Importer {

	/**
	 * Run import for scanned files.
	 *
	 * @param array<int, array<string, mixed>> $files   Scanned file records.
	 * @param array<string, mixed>            $options Import options.
	 * @return array<string, mixed>
	 */
	public static function run( $files, $options = [] ) {
		$dry_run       = ! isset( $options['dry_run'] ) || ! empty( $options['dry_run'] );
		$force         = ! empty( $options['force'] );
		$publish       = ! empty( $options['publish'] );
		$selected_only = $options['files'] ?? null;
		$min_confidence = (int) ( $options['min_confidence'] ?? 80 );

		$report = [
			'dry_run'              => $dry_run,
			'started_at'           => gmdate( 'c' ),
			'created'              => [],
			'updated'              => [],
			'skipped'              => [],
			'review_required'      => [],
			'missing_images'       => [],
			'uploaded_images'      => [],
			'broken_links'         => [],
			'sanitized_removed'    => [],
			'styling_issues'       => [],
			'mobile_layout_risks'  => [],
			'manual_qa'            => [],
			'errors'               => [],
		];

		foreach ( $files as $file ) {
			$rel = $file['relative_path'] ?? '';
			if ( is_array( $selected_only ) && ! in_array( $rel, $selected_only, true ) ) {
				continue;
			}

			$action     = $file['action'] ?? 'REVIEW_REQUIRED';
			$confidence = (int) ( $file['confidence'] ?? 0 );

			if ( 'SKIP' === $action ) {
				$report['skipped'][] = self::entry( $file, 'Marked SKIP' );
				continue;
			}

			if ( 'REVIEW_REQUIRED' === $action && $confidence < $min_confidence ) {
				$report['review_required'][] = self::entry( $file, $file['notes'] ?? '' );
				$report['manual_qa'][] = $rel;
				continue;
			}

			if ( $confidence < $min_confidence && 'REVIEW_REQUIRED' === $action ) {
				$report['review_required'][] = self::entry( $file, 'Below confidence threshold.' );
				continue;
			}

			$result = self::import_file( $file, $dry_run, $force, $publish, $report );
			if ( is_wp_error( $result ) ) {
				$report['errors'][] = [
					'file'  => $rel,
					'error' => $result->get_error_message(),
				];
				continue;
			}

			$type = $result['type'] ?? 'skipped';
			$report[ $type ][] = $result;
		}

		$report['finished_at'] = gmdate( 'c' );
		RHI_Logger::save_report( $report );
		return $report;
	}

	/**
	 * Import a single file record.
	 *
	 * @param array<string, mixed> $file    File record.
	 * @param bool                 $dry_run Dry run.
	 * @param bool                 $force   Force update.
	 * @param bool                 $publish Publish status.
	 * @param array<string, mixed> $report  Report (by ref aggregate).
	 * @return array<string, mixed>|WP_Error
	 */
	private static function import_file( $file, $dry_run, $force, $publish, &$report ) {
		$path = $file['absolute_path'] ?? '';
		if ( ! $path || ! file_exists( $path ) ) {
			return new WP_Error( 'rhi_missing_file', 'Source file missing.' );
		}

		$parser = new RHI_Html_Parser( $path );
		$parsed = $parser->parse();
		$html   = $parsed['content'] ?? '';

		if ( '' === trim( $html ) ) {
			return new WP_Error( 'rhi_empty_content', 'No extractable content.' );
		}

		$base_dir = dirname( $path );
		$media    = new RHI_Media_Importer();
		if ( ! $dry_run ) {
			$media_result = $media->import_images( $html, $base_dir, $parsed );
			$html = $media_result['html'];
			$report['uploaded_images'] = array_merge( $report['uploaded_images'], $media_result['uploaded'] );
			$report['missing_images']  = array_merge( $report['missing_images'], $media_result['missing'] );
		}

		$adopted = RHI_Css_Adoption::adopt( $html );
		$html    = RHI_Css_Adoption::rewrite_links( $adopted['html'] );
		$report['styling_issues'] = array_merge( $report['styling_issues'], $adopted['issues'] );

		RHI_Sanitizer::reset_removed_log();
		$html = RHI_Sanitizer::sanitize_content( $html );
		$report['sanitized_removed'] = array_merge( $report['sanitized_removed'], RHI_Sanitizer::get_removed_log() );

		$broken = self::detect_broken_links( $html );
		$report['broken_links'] = array_merge( $report['broken_links'], $broken );

		$wp_page_id = (int) ( $file['wp_page_id'] ?? 0 );
		$slug       = $file['suggested_slug'] ?? '';
		$title      = self::page_title_from_parsed( $parsed );

		if ( $wp_page_id && ! $force ) {
			$stored = get_post_meta( $wp_page_id, '_revamp_source_hash', true );
			if ( $stored && $stored === ( $parsed['source_hash'] ?? '' ) ) {
				return self::entry( $file, 'Unchanged hash', 'skipped' );
			}
		}

		if ( $wp_page_id && RHI_Page_Matcher::has_page_builder( $wp_page_id ) && ! $force ) {
			$report['manual_qa'][] = $file['relative_path'] . ' (page builder detected)';
			return self::entry( $file, 'Page builder present — skipped without force.', 'skipped' );
		}

		if ( $dry_run ) {
			return [
				'type'        => ( $wp_page_id ? 'updated' : 'created' ),
				'file'        => $file['relative_path'],
				'slug'        => $slug,
				'title'       => $title,
				'dry_run'     => true,
				'content_len' => strlen( $html ),
				'hero_image'  => $parsed['hero_image'] ?? '',
			];
		}

		$postarr = [
			'post_type'    => 'page',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $html,
			'post_status'  => $publish ? 'publish' : 'draft',
		];

		if ( $wp_page_id ) {
			RHI_Rollback::backup( $wp_page_id );
			$postarr['ID'] = $wp_page_id;
			$post_id = wp_update_post( $postarr, true );
			$type    = 'updated';
		} else {
			$post_id = wp_insert_post( $postarr, true );
			$type    = 'created';
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_revamp_source_html_file', $file['relative_path'] );
		update_post_meta( $post_id, '_revamp_source_hash', $parsed['source_hash'] ?? '' );
		update_post_meta( $post_id, '_revamp_last_imported_at', gmdate( 'c' ) );
		update_post_meta( $post_id, '_revamp_mapping_confidence', (int) ( $file['confidence'] ?? 0 ) );

		if ( ! empty( $parsed['hero_image'] ) && ! has_post_thumbnail( $post_id ) ) {
			$hero_url = $parsed['hero_image'];
			if ( preg_match( '#^https?://#', $hero_url ) ) {
				RHI_Media_Importer::set_featured_from_url( $post_id, $hero_url );
			}
		}

		if ( 'home' === $slug && $publish ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $post_id );
		}

		RHI_Logger::log( 'info', 'Imported page', [
			'post_id' => $post_id,
			'file'    => $file['relative_path'],
			'type'    => $type,
		] );

		return [
			'type'     => $type,
			'post_id'  => $post_id,
			'file'     => $file['relative_path'],
			'slug'     => $slug,
			'title'    => $title,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
		];
	}

	/**
	 * @param array<string, mixed> $parsed Parsed HTML.
	 * @return string
	 */
	private static function page_title_from_parsed( $parsed ) {
		$title = $parsed['title'] ?? '';
		$title = preg_replace( '/\s*[—–-]\s*NextGen Tutors.*$/i', '', $title );
		if ( $title ) {
			return trim( $title );
		}
		return $parsed['h1'] ?: 'Untitled Page';
	}

	/**
	 * @param string $html HTML.
	 * @return array<int, string>
	 */
	private static function detect_broken_links( $html ) {
		$broken = [];
		if ( preg_match_all( '/href=(["\'])([^"\']+)\1/i', $html, $m ) ) {
			foreach ( $m[2] as $href ) {
				if ( preg_match( '#^(mailto:|tel:|#|javascript:)#i', $href ) ) {
					continue;
				}
				if ( preg_match( '#\.html?$#i', $href ) ) {
					$broken[] = $href;
				}
			}
		}
		return array_unique( $broken );
	}

	/**
	 * @param array<string, mixed> $file    File.
	 * @param string               $reason  Reason.
	 * @param string               $type    Report bucket.
	 * @return array<string, mixed>
	 */
	private static function entry( $file, $reason, $type = 'skipped' ) {
		return [
			'type'   => $type,
			'file'   => $file['relative_path'] ?? '',
			'slug'   => $file['suggested_slug'] ?? '',
			'reason' => $reason,
		];
	}
}
