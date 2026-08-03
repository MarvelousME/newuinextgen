<?php
/**
 * Migrate Section CMS rows into Visual Builder documents.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent migrator: home sections → document tree.
 */
class NGC_Builder_Migrator {

	const FLAG = 'ngc_builder_migrated_v1';

	/**
	 * Run once (or force).
	 *
	 * @param bool $force Force re-migrate home draft if missing.
	 * @return array{created: string[], skipped: string[]}
	 */
	public static function migrate( $force = false ) {
		$result = [ 'created' => [], 'skipped' => [] ];

		if ( ! $force && get_option( self::FLAG ) ) {
			$existing = NGC_Builder_Repository::get_by_key( 'doc_home' );
			if ( $existing ) {
				$result['skipped'][] = 'doc_home';
				return $result;
			}
		}

		$doc = self::build_home_document();
		$saved = NGC_Builder_Repository::save(
			$doc,
			[
				'title'     => 'Home',
				'status'    => 'draft',
				'wp_post_id'=> (int) get_option( 'page_on_front' ),
			]
		);

		if ( ! is_wp_error( $saved ) ) {
			$result['created'][] = 'doc_home';
			update_option( self::FLAG, gmdate( 'c' ), false );
		}

		return $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function build_home_document() {
		$doc      = NGC_Builder_Document::blank( 'doc_home', 'Home', [ 'meta' => [ 'title' => 'Home', 'pageKey' => 'home' ] ] );
		$children = [];
		$keys     = class_exists( 'NGC_Section_CMS' ) ? NGC_Section_CMS::section_keys() : [];

		foreach ( $keys as $index => $section_key ) {
			$node_id = 'sec_' . sanitize_key( $section_key );
			$enabled = true;
			if ( class_exists( 'NGC_Section_CMS' ) && method_exists( 'NGC_Section_CMS', 'section_enabled' ) ) {
				$enabled = (bool) NGC_Section_CMS::section_enabled( $section_key );
			}
			$row_sort = $index;
			$doc['nodes'][ $node_id ] = [
				'id'           => $node_id,
				'type'         => 'theme.section',
				'component'    => 'home.' . $section_key,
				'name'         => ucwords( str_replace( '_', ' ', $section_key ) ),
				'children'     => [],
				'props'        => [
					'sectionKey' => $section_key,
					'enabled'    => $enabled,
					'sortOrder'  => $row_sort,
				],
				'layout'       => [],
				'style'        => [
					'paddingBlock' => 'token:spacing.6',
				],
				'bindings'     => [],
				'interactions' => [],
				'visibility'   => [
					'when' => $enabled ? 'always' : 'never',
				],
				'responsive'   => [],
				'contentRef'   => [
					'pageKey'    => 'home',
					'sectionKey' => $section_key,
				],
			];
			$children[] = $node_id;
		}

		$doc['nodes']['root']['children'] = $children;
		$doc['wpPostId'] = (int) get_option( 'page_on_front' ) ?: null;
		return NGC_Builder_Document::normalize( $doc );
	}
}
