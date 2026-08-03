<?php
/**
 * Persistence for Visual Builder documents + revisions.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD against wp_ngc_builder_documents / revisions.
 */
class NGC_Builder_Repository {

	/**
	 * @return string
	 */
	public static function documents_table() {
		return NGC_Database::table( 'builder_documents' );
	}

	/**
	 * @return string
	 */
	public static function revisions_table() {
		return NGC_Database::table( 'builder_revisions' );
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_documents( array $args = [] ) {
		global $wpdb;
		$table = self::documents_table();
		if ( ! $table ) {
			return [];
		}
		$kind   = isset( $args['kind'] ) ? sanitize_key( $args['kind'] ) : '';
		$status = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$sql    = "SELECT id, document_key, kind, title, wp_post_id, status, schema_version, updated_at, published_at FROM {$table} WHERE 1=1";
		$params = [];
		if ( $kind ) {
			$sql     .= ' AND kind = %s';
			$params[] = $kind;
		}
		if ( $status ) {
			$sql     .= ' AND status = %s';
			$params[] = $status;
		}
		$sql .= ' ORDER BY updated_at DESC LIMIT 200';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param string $key Document key.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_key( $key ) {
		global $wpdb;
		$table = self::documents_table();
		if ( ! $table ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE document_key = %s LIMIT 1", sanitize_key( $key ) ),
			ARRAY_A
		);
		return self::hydrate( $row );
	}

	/**
	 * @param int $id Row id.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$table = self::documents_table();
		if ( ! $table ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $id ),
			ARRAY_A
		);
		return self::hydrate( $row );
	}

	/**
	 * @param int $post_id WP post id.
	 * @return array<string, mixed>|null Live published document for post, else draft.
	 */
	public static function get_for_post( $post_id ) {
		global $wpdb;
		$table = self::documents_table();
		if ( ! $table ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE wp_post_id = %d AND status = 'published' ORDER BY published_at DESC LIMIT 1",
				(int) $post_id
			),
			ARRAY_A
		);
		if ( ! $row ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE wp_post_id = %d ORDER BY updated_at DESC LIMIT 1",
					(int) $post_id
				),
				ARRAY_A
			);
		}
		return self::hydrate( $row );
	}

	/**
	 * Create or update draft document.
	 *
	 * @param array<string, mixed> $document Document JSON.
	 * @param array<string, mixed> $meta     Row meta (title, status, wp_post_id).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function save( array $document, array $meta = [] ) {
		global $wpdb;
		$table = self::documents_table();
		if ( ! $table ) {
			return new WP_Error( 'ngc_builder_db', __( 'Builder tables missing.', 'nextgencompanion' ) );
		}

		$document = NGC_Builder_Document::normalize( $document );
		$valid    = NGC_Builder_Document::validate( $document );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$key      = sanitize_key( (string) $document['id'] );
		$existing = self::get_by_key( $key );
		$now      = current_time( 'mysql', true );
		$user_id  = get_current_user_id();
		$title    = sanitize_text_field( (string) ( $meta['title'] ?? $document['meta']['title'] ?? $key ) );
		$status   = sanitize_key( (string) ( $meta['status'] ?? ( $existing['status'] ?? 'draft' ) ) );
		$wp_post  = isset( $meta['wp_post_id'] ) ? (int) $meta['wp_post_id'] : (int) ( $document['wpPostId'] ?? 0 );
		$kind     = sanitize_key( (string) ( $document['kind'] ?? 'page' ) );

		$payload = [
			'document_key'   => $key,
			'kind'           => $kind,
			'title'          => $title,
			'wp_post_id'     => $wp_post > 0 ? $wp_post : 0,
			'status'         => $status ?: 'draft',
			'schema_version' => (int) $document['schemaVersion'],
			'document_json'  => wp_json_encode( $document ),
			'updated_by'     => $user_id,
			'updated_at'     => $now,
		];

		if ( $existing ) {
			$wpdb->update( $table, $payload, [ 'id' => (int) $existing['id'] ] );
			return self::get_by_id( (int) $existing['id'] );
		}

		$payload['created_by'] = $user_id;
		$payload['created_at'] = $now;
		$wpdb->insert( $table, $payload );
		return self::get_by_id( (int) $wpdb->insert_id );
	}

	/**
	 * Publish document: snapshot revision + mark published.
	 *
	 * @param string $key Document key.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function publish( $key ) {
		global $wpdb;
		$row = self::get_by_key( $key );
		if ( ! $row ) {
			return new WP_Error( 'ngc_builder_missing', __( 'Document not found.', 'nextgencompanion' ) );
		}

		$rev_table = self::revisions_table();
		$docs      = self::documents_table();
		$now       = current_time( 'mysql', true );
		$version   = self::next_revision_version( (int) $row['id'] );

		$wpdb->insert(
			$rev_table,
			[
				'document_id'   => (int) $row['id'],
				'version'       => $version,
				'document_json' => wp_json_encode( $row['document'] ),
				'compiled_json' => wp_json_encode( NGC_Builder_Compiler::compile( $row['document'] ) ),
				'published_by'  => get_current_user_id(),
				'published_at'  => $now,
			]
		);

		$wpdb->update(
			$docs,
			[
				'status'       => 'published',
				'published_at' => $now,
				'updated_at'   => $now,
				'updated_by'   => get_current_user_id(),
			],
			[ 'id' => (int) $row['id'] ]
		);

		return self::get_by_id( (int) $row['id'] );
	}

	/**
	 * @param int $document_id Document row id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_revisions( $document_id ) {
		global $wpdb;
		$table = self::revisions_table();
		if ( ! $table ) {
			return [];
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, document_id, version, published_by, published_at FROM {$table} WHERE document_id = %d ORDER BY version DESC LIMIT 50",
				(int) $document_id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Restore a revision into the draft document.
	 *
	 * @param int $revision_id Revision id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function restore_revision( $revision_id ) {
		global $wpdb;
		$table = self::revisions_table();
		$rev   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $revision_id ),
			ARRAY_A
		);
		if ( ! $rev ) {
			return new WP_Error( 'ngc_builder_rev', __( 'Revision not found.', 'nextgencompanion' ) );
		}
		$doc = json_decode( (string) $rev['document_json'], true );
		if ( ! is_array( $doc ) ) {
			return new WP_Error( 'ngc_builder_rev', __( 'Revision payload invalid.', 'nextgencompanion' ) );
		}
		$parent = self::get_by_id( (int) $rev['document_id'] );
		if ( ! $parent ) {
			return new WP_Error( 'ngc_builder_rev', __( 'Parent document missing.', 'nextgencompanion' ) );
		}
		return self::save(
			$doc,
			[
				'title'  => $parent['title'],
				'status' => 'draft',
			]
		);
	}

	/**
	 * @param int $document_id Document id.
	 * @return int
	 */
	private static function next_revision_version( $document_id ) {
		global $wpdb;
		$table = self::revisions_table();
		$max   = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT MAX(version) FROM {$table} WHERE document_id = %d", (int) $document_id )
		);
		return $max + 1;
	}

	/**
	 * @param array<string, mixed>|null $row DB row.
	 * @return array<string, mixed>|null
	 */
	private static function hydrate( $row ) {
		if ( ! is_array( $row ) ) {
			return null;
		}
		$doc = json_decode( (string) ( $row['document_json'] ?? '' ), true );
		$row['document'] = is_array( $doc ) ? $doc : NGC_Builder_Document::blank( $row['document_key'] ?? 'doc' );
		unset( $row['document_json'] );
		return $row;
	}
}
