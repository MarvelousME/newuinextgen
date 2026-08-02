<?php
/**
 * Unified export adapter for admin grids.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps NGC_Export_Formats for entity grid exports.
 */
final class NGC_Admin_Export {

	/**
	 * Export entity rows.
	 *
	 * @param string               $entity_key Entity.
	 * @param string               $format     csv|json|excel|pdf.
	 * @param array<string, mixed> $args       List args + selected ids.
	 * @return array{ok:bool,content?:string,filename?:string,mime?:string,message?:string}
	 */
	public static function export_entity( $entity_key, $format, array $args = [] ) {
		$entity = NGC_Admin_Entity_Registry::get( $entity_key );
		if ( ! $entity ) {
			return [ 'ok' => false, 'message' => 'unknown_entity' ];
		}
		$cap = (string) ( $entity['capability'] ?? 'manage_options' );
		if ( ! current_user_can( $cap ) && ! current_user_can( 'manage_options' ) ) {
			return [ 'ok' => false, 'message' => 'forbidden' ];
		}

		$args['per_page'] = max( 1, min( 5000, (int) ( $args['per_page'] ?? 1000 ) ) );
		$args['page']     = 1;
		$list             = NGC_Admin_Crud::list_items( $entity_key, $args );
		if ( empty( $list['ok'] ) ) {
			return [ 'ok' => false, 'message' => $list['message'] ?? 'list_failed' ];
		}
		$rows = (array) ( $list['rows'] ?? [] );
		if ( ! empty( $args['ids'] ) && is_array( $args['ids'] ) ) {
			$ids  = array_map( 'intval', $args['ids'] );
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $r ) use ( $ids ) {
						return in_array( (int) ( $r['id'] ?? 0 ), $ids, true );
					}
				)
			);
		}

		$columns = [];
		foreach ( (array) $entity['columns'] as $col ) {
			$columns[] = (string) ( $col['key'] ?? '' );
		}
		$columns = array_values( array_filter( $columns ) );
		$format  = sanitize_key( $format );
		$body    = class_exists( 'NGC_Export_Formats' )
			? NGC_Export_Formats::render( $rows, $columns, $format )
			: wp_json_encode( $rows );

		$mime = [
			'csv'   => 'text/csv',
			'json'  => 'application/json',
			'excel' => 'application/vnd.ms-excel',
			'pdf'   => 'application/pdf',
		];

		if ( class_exists( 'NGC_Audit' ) && method_exists( 'NGC_Audit', 'log' ) ) {
			NGC_Audit::log(
				'admin_export',
				[
					'entity' => $entity_key,
					'format' => $format,
					'count'  => count( $rows ),
				]
			);
		}

		return [
			'ok'       => true,
			'content'  => $body,
			'filename' => $entity_key . '-' . gmdate( 'Ymd-His' ) . '.' . ( 'excel' === $format ? 'xls' : $format ),
			'mime'     => $mime[ $format ] ?? 'application/octet-stream',
		];
	}
}
