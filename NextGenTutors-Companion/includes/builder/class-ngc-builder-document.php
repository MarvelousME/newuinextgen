<?php
/**
 * Document factory + normalization for Visual Builder JSON.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and validates schemaVersion 1 documents.
 */
class NGC_Builder_Document {

	const SCHEMA_VERSION = 1;

	/**
	 * Empty page document shell.
	 *
	 * @param string               $doc_id Document id.
	 * @param string               $title  Title.
	 * @param array<string, mixed> $extra  Extra top-level keys.
	 * @return array<string, mixed>
	 */
	public static function blank( $doc_id, $title = 'Untitled', array $extra = [] ) {
		$root_id = 'root';
		$doc     = [
			'schemaVersion' => self::SCHEMA_VERSION,
			'id'            => sanitize_key( $doc_id ),
			'kind'          => 'page',
			'wpPostId'      => null,
			'slot'          => 'main',
			'breakpoints'   => [
				'base'   => 0,
				'tablet' => 768,
				'mobile' => 480,
			],
			'tokensRef'     => 'global',
			'rootId'        => $root_id,
			'nodes'         => [
				$root_id => [
					'id'           => $root_id,
					'type'         => 'container',
					'tag'          => 'main',
					'name'         => 'Page',
					'children'     => [],
					'layout'       => [
						'display'   => 'flex',
						'direction' => 'column',
						'gap'       => 'token:space.4',
					],
					'style'        => [],
					'props'        => [],
					'bindings'     => [],
					'interactions' => [],
					'visibility'   => [ 'when' => 'always' ],
					'responsive'   => [],
				],
			],
			'meta'          => [
				'title'     => sanitize_text_field( $title ),
				'updatedAt' => gmdate( 'c' ),
			],
		];

		return array_merge( $doc, $extra );
	}

	/**
	 * Normalize a document array (ensure required keys, drop illegal inline style strings as source).
	 *
	 * @param array<string, mixed> $doc Raw document.
	 * @return array<string, mixed>
	 */
	public static function normalize( array $doc ) {
		if ( empty( $doc['schemaVersion'] ) ) {
			$doc['schemaVersion'] = self::SCHEMA_VERSION;
		}
		if ( empty( $doc['id'] ) ) {
			$doc['id'] = 'doc_' . wp_generate_password( 8, false, false );
		}
		$doc['id']   = sanitize_key( (string) $doc['id'] );
		$doc['kind'] = sanitize_key( (string) ( $doc['kind'] ?? 'page' ) );
		if ( empty( $doc['rootId'] ) ) {
			$doc['rootId'] = 'root';
		}
		if ( empty( $doc['nodes'] ) || ! is_array( $doc['nodes'] ) ) {
			$blank         = self::blank( $doc['id'], $doc['meta']['title'] ?? 'Untitled' );
			$doc['nodes']  = $blank['nodes'];
			$doc['rootId'] = $blank['rootId'];
		}
		if ( empty( $doc['breakpoints'] ) || ! is_array( $doc['breakpoints'] ) ) {
			$doc['breakpoints'] = [ 'base' => 0, 'tablet' => 768, 'mobile' => 480 ];
		}
		foreach ( $doc['nodes'] as $id => $node ) {
			if ( ! is_array( $node ) ) {
				unset( $doc['nodes'][ $id ] );
				continue;
			}
			$node['id'] = $id;
			if ( empty( $node['children'] ) || ! is_array( $node['children'] ) ) {
				$node['children'] = [];
			}
			if ( isset( $node['style'] ) && is_string( $node['style'] ) ) {
				// Forbidden as source of truth — discard raw CSS strings.
				$node['style'] = [];
			}
			$doc['nodes'][ $id ] = $node;
		}
		$doc['meta']              = is_array( $doc['meta'] ?? null ) ? $doc['meta'] : [];
		$doc['meta']['updatedAt'] = gmdate( 'c' );

		/**
		 * Filter normalized builder documents.
		 *
		 * @param array $doc Document.
		 */
		return apply_filters( 'ngc_builder_document_schema', $doc );
	}

	/**
	 * Lightweight structural validation.
	 *
	 * @param array<string, mixed> $doc Document.
	 * @return true|WP_Error
	 */
	public static function validate( array $doc ) {
		if ( (int) ( $doc['schemaVersion'] ?? 0 ) !== self::SCHEMA_VERSION ) {
			return new WP_Error( 'ngc_builder_schema', __( 'Unsupported document schemaVersion.', 'nextgencompanion' ) );
		}
		if ( empty( $doc['id'] ) || empty( $doc['rootId'] ) || empty( $doc['nodes'][ $doc['rootId'] ] ) ) {
			return new WP_Error( 'ngc_builder_structure', __( 'Document missing root node.', 'nextgencompanion' ) );
		}
		return true;
	}
}
