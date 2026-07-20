<?php
/**
 * Rollback support for imported pages.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and restores previous page content.
 */
class RHI_Rollback {

	/**
	 * Backup post content before update.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function backup( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		update_post_meta( $post_id, '_revamp_previous_post_content', $post->post_content );
		update_post_meta( $post_id, '_revamp_previous_post_modified', $post->post_modified_gmt );
		update_post_meta( $post_id, '_revamp_backup_at', gmdate( 'c' ) );
	}

	/**
	 * Rollback a single page.
	 *
	 * @param int $post_id Post ID.
	 * @return bool|WP_Error
	 */
	public static function rollback_page( $post_id ) {
		$content = get_post_meta( $post_id, '_revamp_previous_post_content', true );
		if ( '' === $content && false === $content ) {
			return new WP_Error( 'rhi_no_backup', __( 'No rollback content stored for this page.', 'revamp-html-importer' ) );
		}
		$modified = get_post_meta( $post_id, '_revamp_previous_post_modified', true );
		$result = wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $content,
			],
			true
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( $modified ) {
			wp_update_post(
				[
					'ID'                => $post_id,
					'post_modified'     => $modified,
					'post_modified_gmt' => $modified,
				]
			);
		}
		RHI_Logger::log( 'info', 'Rolled back page', [ 'post_id' => $post_id ] );
		return true;
	}

	/**
	 * Rollback all pages that have backup meta.
	 *
	 * @return array<string, mixed>
	 */
	public static function rollback_all() {
		$posts = get_posts(
			[
				'post_type'      => 'page',
				'posts_per_page' => -1,
				'meta_key'       => '_revamp_previous_post_content',
				'fields'         => 'ids',
			]
		);
		$ok = 0;
		$fail = 0;
		foreach ( $posts as $post_id ) {
			$r = self::rollback_page( (int) $post_id );
			if ( is_wp_error( $r ) ) {
				++$fail;
			} else {
				++$ok;
			}
		}
		return [ 'restored' => $ok, 'failed' => $fail ];
	}
}
