<?php
/**
 * Elementor Template Library source: NextGen Tutors.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only library source.
 */
class BI_EL_Template_Source extends \Elementor\TemplateLibrary\Source_Base {

	/**
	 * @return string
	 */
	public function get_id() {
		return 'nextgen-tutors';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'NextGen Tutors', 'beyondinfinity' );
	}

	/**
	 * @return void
	 */
	public function register_data() {}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	public function get_items( $args = [] ) {
		$items = [];
		foreach ( BI_EL_Templates::catalog() as $id => $row ) {
			$items[] = [
				'template_id'     => $id,
				'source'          => $this->get_id(),
				'title'           => $row['title'],
				'type'            => 'page',
				'subtype'         => 'page',
				'url'             => '',
				'hasPageSettings' => false,
			];
		}
		return $items;
	}

	/**
	 * @param int $template_id Id.
	 * @return array
	 */
	public function get_item( $template_id ) {
		$cat = BI_EL_Templates::catalog();
		$id  = sanitize_key( (string) $template_id );
		if ( empty( $cat[ $id ] ) ) {
			return [];
		}
		return [
			'template_id' => $id,
			'source'      => $this->get_id(),
			'title'       => $cat[ $id ]['title'],
			'type'        => 'page',
		];
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	public function get_data( array $args ) {
		$id      = sanitize_key( (string) ( $args['template_id'] ?? '' ) );
		$content = BI_EL_Templates::document( $id );
		return [
			'content'       => $this->replace_elements_ids( $content ),
			'page_settings' => [],
		];
	}

	/**
	 * @param int $template_id Id.
	 * @return bool|\WP_Error
	 */
	public function delete_template( $template_id ) {
		return new \WP_Error( 'invalid', __( 'NextGen library templates cannot be deleted.', 'beyondinfinity' ) );
	}

	/**
	 * @param array $template_data Data.
	 * @return bool|\WP_Error
	 */
	public function save_item( $template_data ) {
		return new \WP_Error( 'invalid', __( 'NextGen library templates are read-only. Save a copy to My Templates.', 'beyondinfinity' ) );
	}

	/**
	 * @param array $new_data Data.
	 * @return bool|\WP_Error
	 */
	public function update_item( $new_data ) {
		return new \WP_Error( 'invalid', __( 'NextGen library templates are read-only.', 'beyondinfinity' ) );
	}

	/**
	 * @param int $template_id Id.
	 * @return bool|\WP_Error
	 */
	public function export_template( $template_id ) {
		$id = sanitize_key( (string) $template_id );
		return [
			'name'    => $id,
			'content' => wp_json_encode( BI_EL_Templates::document( $id ) ),
		];
	}
}
