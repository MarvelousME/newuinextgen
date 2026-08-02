<?php
/**
 * Enterprise Data Grid renderer.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared grid mount for metadata-driven entities.
 */
final class NGC_Admin_Grid {

	/**
	 * Enqueue grid assets when needed.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'ngt-admin-grid', NGC_PLUGIN_URL . 'assets/css/admin-grid.css', [ 'ngt-admin-tokens' ], NGC_VERSION );
		wp_enqueue_script( 'ngt-admin-grid', NGC_PLUGIN_URL . 'assets/js/admin-grid.js', [ 'ngt-admin-shell' ], NGC_VERSION, true );
		wp_localize_script(
			'ngt-admin-grid',
			'ngtAdminGrid',
			[
				'restRoot' => esc_url_raw( rest_url( 'ngc/v1/admin' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Render grid mount point.
	 *
	 * @param string               $entity_key Entity key.
	 * @param array<string, mixed> $opts       Options.
	 */
	public static function render( $entity_key, array $opts = [] ) {
		self::enqueue();
		$entity = NGC_Admin_Entity_Registry::get( $entity_key );
		if ( ! $entity ) {
			echo '<p>' . esc_html__( 'Unknown entity.', 'nextgencompanion' ) . '</p>';
			return;
		}
		$config = wp_json_encode(
			[
				'entity'  => $entity_key,
				'label'   => $entity['label'],
				'columns' => $entity['columns'],
				'filters' => $entity['filters'],
				'actions' => $entity['actions'] ?? [],
				'export'  => ! empty( $entity['export_key'] ),
			]
		);
		echo '<div class="ngt-admin-grid" data-testid="ngt-admin-grid" data-entity="' . esc_attr( $entity_key ) . '" data-config="' . esc_attr( (string) $config ) . '">';
		echo '<div class="ngt-admin-grid__toolbar">';
		echo '<input type="search" class="ngt-admin-grid__search" placeholder="' . esc_attr__( 'Search…', 'nextgencompanion' ) . '" data-testid="ngt-admin-grid-search" />';
		echo '<button type="button" class="button ngt-admin-grid__cols">' . esc_html__( 'Columns', 'nextgencompanion' ) . '</button>';
		echo '<button type="button" class="button ngt-admin-grid__export" data-testid="ngt-admin-grid-export">' . esc_html__( 'Export', 'nextgencompanion' ) . '</button>';
		echo '<select class="ngt-admin-grid__export-format"><option value="csv">CSV</option><option value="json">JSON</option><option value="excel">Excel</option><option value="pdf">PDF</option></select>';
		echo '</div>';
		echo '<div class="ngt-admin-grid__filters"></div>';
		echo '<div class="ngt-admin-grid__table-wrap" role="region" aria-label="' . esc_attr( (string) $entity['label'] ) . '">';
		echo '<table class="ngt-admin-grid__table" role="grid"><thead></thead><tbody></tbody></table>';
		echo '</div>';
		echo '<div class="ngt-admin-grid__footer"><span class="ngt-admin-grid__meta"></span>';
		echo '<button type="button" class="button ngt-admin-grid__prev">' . esc_html__( 'Previous', 'nextgencompanion' ) . '</button>';
		echo '<button type="button" class="button ngt-admin-grid__next">' . esc_html__( 'Next', 'nextgencompanion' ) . '</button></div>';
		echo '<aside class="ngt-admin-grid__detail" hidden data-testid="ngt-admin-grid-detail"></aside>';
		echo '</div>';
	}
}
