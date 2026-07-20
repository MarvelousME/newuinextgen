<?php
/**
 * Media importer — sideload images into WordPress.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports images referenced in HTML content.
 */
class RHI_Media_Importer {

	/** @var array<string, string> */
	private $url_map = [];

	/** @var array<int, string> */
	private $missing = [];

	/** @var array<int, string> */
	private $uploaded = [];

	/**
	 * @param string               $html     HTML content.
	 * @param string               $base_dir Base directory for relative paths.
	 * @param array<string, mixed> $parsed   Parser output with images array.
	 * @return array{html:string,uploaded:array<int,string>,missing:array<int,string>}
	 */
	public function import_images( $html, $base_dir, $parsed = [] ) {
		$this->url_map  = [];
		$this->missing  = [];
		$this->uploaded = [];

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$images = $parsed['images'] ?? [];
		foreach ( $images as $img ) {
			$src = $img['src'] ?? '';
			if ( ! $src || isset( $this->url_map[ $src ] ) ) {
				continue;
			}
			$new_url = $this->import_single( $src, $base_dir, $img['alt'] ?? '' );
			if ( $new_url ) {
				$this->url_map[ $src ] = $new_url;
				$html = str_replace( $src, $new_url, $html );
			}
		}

		return [
			'html'     => $html,
			'uploaded' => $this->uploaded,
			'missing'  => $this->missing,
		];
	}

	/**
	 * @param string $src      Image source.
	 * @param string $base_dir Base directory.
	 * @param string $alt      Alt text.
	 * @return string New URL or empty.
	 */
	private function import_single( $src, $base_dir, $alt = '' ) {
		// Remote URL.
		if ( preg_match( '#^https?://#i', $src ) ) {
			$existing = $this->find_existing_by_url( $src );
			if ( $existing ) {
				$this->uploaded[] = $src . ' (existing)';
				return $existing;
			}
			$id = media_sideload_image( $src, 0, $alt, 'src' );
			if ( is_wp_error( $id ) ) {
				$this->missing[] = $src . ': ' . $id->get_error_message();
				RHI_Logger::log( 'warning', 'Image sideload failed', [ 'src' => $src, 'error' => $id->get_error_message() ] );
				return '';
			}
			$url = wp_get_attachment_url( (int) $id );
			if ( $url ) {
				$this->uploaded[] = $src;
				update_post_meta( (int) $id, '_revamp_source_url', esc_url_raw( $src ) );
			}
			return $url ?: '';
		}

		// Local relative path.
		$local = wp_normalize_path( $base_dir . '/' . ltrim( $src, './' ) );
		if ( ! file_exists( $local ) ) {
			$this->missing[] = $src . ': file not found at ' . $local;
			return '';
		}

		$filename = basename( $local );
		$upload   = wp_upload_bits( $filename, null, file_get_contents( $local ) );
		if ( ! empty( $upload['error'] ) ) {
			$this->missing[] = $src . ': ' . $upload['error'];
			return '';
		}

		$filetype = wp_check_filetype( $filename );
		$attachment = [
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];
		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $attach_id ) ) {
			$this->missing[] = $src . ': ' . $attach_id->get_error_message();
			return '';
		}
		$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $meta );
		if ( $alt ) {
			update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}
		update_post_meta( $attach_id, '_revamp_source_path', $local );

		$url = wp_get_attachment_url( $attach_id );
		$this->uploaded[] = $src;
		return $url ?: '';
	}

	/**
	 * @param string $url Source URL.
	 * @return string
	 */
	private function find_existing_by_url( $url ) {
		$posts = get_posts(
			[
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'meta_key'       => '_revamp_source_url',
				'meta_value'     => esc_url_raw( $url ),
				'fields'         => 'ids',
			]
		);
		if ( $posts ) {
			return wp_get_attachment_url( (int) $posts[0] ) ?: '';
		}
		return '';
	}

	/**
	 * Set featured image from hero URL.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $hero_url Hero image URL (already in media library).
	 */
	public static function set_featured_from_url( $post_id, $hero_url ) {
		if ( ! $hero_url || ! $post_id ) {
			return;
		}
		$attach_id = attachment_url_to_postid( $hero_url );
		if ( $attach_id ) {
			set_post_thumbnail( $post_id, $attach_id );
		}
	}
}
