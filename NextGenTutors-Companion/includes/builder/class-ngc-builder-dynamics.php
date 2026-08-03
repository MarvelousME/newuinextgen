<?php
/**
 * Dynamic data node compilation (ACF / Meta Box / CPT / Woo / loops).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phase 5 dynamic bindings.
 */
class NGC_Builder_Dynamics {

	/**
	 * @param array<string, mixed> $node    Node.
	 * @param string               $class   Class list.
	 * @param string               $node_id Node id.
	 * @return string
	 */
	public static function compile( array $node, $class, $node_id ) {
		$variant = sanitize_key( (string) ( $node['props']['variant'] ?? $node['props']['source'] ?? 'query_loop' ) );
		$props   = is_array( $node['props'] ?? null ) ? $node['props'] : [];

		switch ( $variant ) {
			case 'acf_field':
				return self::acf_field( $props, $class, $node_id );
			case 'meta_box':
				return self::meta_box_field( $props, $class, $node_id );
			case 'woo_product':
				return self::woo_product( $props, $class, $node_id );
			case 'cpt':
			case 'query_loop':
			case 'repeater':
			default:
				return self::query_loop( $props, $class, $node_id );
		}
	}

	/**
	 * Binding catalog for the editor.
	 *
	 * @return array<string, mixed>
	 */
	public static function catalog() {
		return [
			'sources'   => [
				[ 'id' => 'query_loop', 'label' => 'Query Loop', 'requires' => [] ],
				[ 'id' => 'acf_field', 'label' => 'ACF Field', 'requires' => [ 'acf' ] ],
				[ 'id' => 'meta_box', 'label' => 'Meta Box', 'requires' => [ 'rwmb' ] ],
				[ 'id' => 'woo_product', 'label' => 'WooCommerce Product', 'requires' => [ 'woocommerce' ] ],
				[ 'id' => 'cpt', 'label' => 'Custom Post Type', 'requires' => [] ],
				[ 'id' => 'repeater', 'label' => 'Repeater', 'requires' => [] ],
			],
			'available' => [
				'acf'         => function_exists( 'get_field' ),
				'rwmb'        => function_exists( 'rwmb_meta' ),
				'woocommerce' => class_exists( 'WooCommerce' ),
			],
			'postTypes' => array_values(
				array_map(
					static function ( $pt ) {
						$obj = get_post_type_object( $pt );
						return [
							'slug'  => $pt,
							'label' => $obj ? $obj->labels->singular_name : $pt,
						];
					},
					get_post_types( [ 'public' => true ], 'names' )
				)
			),
		];
	}

	/**
	 * @param array  $props Props.
	 * @param string $class Class.
	 * @param string $id    Id.
	 * @return string
	 */
	private static function query_loop( array $props, $class, $id ) {
		$pt    = sanitize_key( (string) ( $props['postType'] ?? 'post' ) );
		$count = max( 1, min( 24, (int) ( $props['perPage'] ?? 6 ) ) );
		$q     = new WP_Query(
			[
				'post_type'      => $pt,
				'posts_per_page' => $count,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			]
		);
		$html = '<div class="' . esc_attr( $class ) . ' ngc-b-loop" data-ngc-node="' . esc_attr( $id ) . '">';
		if ( $q->have_posts() ) {
			$html .= '<ul class="ngc-b-loop__list">';
			while ( $q->have_posts() ) {
				$q->the_post();
				$html .= '<li class="ngc-b-loop__item"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
			}
			$html .= '</ul>';
			wp_reset_postdata();
		} else {
			$html .= '<p class="ngc-b-loop__empty">' . esc_html__( 'No items found.', 'nextgencompanion' ) . '</p>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * @param array  $props Props.
	 * @param string $class Class.
	 * @param string $id    Id.
	 * @return string
	 */
	private static function acf_field( array $props, $class, $id ) {
		$field = sanitize_text_field( (string) ( $props['field'] ?? '' ) );
		$val   = '';
		if ( $field && function_exists( 'get_field' ) ) {
			$raw = get_field( $field );
			$val = is_scalar( $raw ) ? (string) $raw : wp_json_encode( $raw );
		}
		return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $id ) . '" data-ngc-bind="acf">' . esc_html( $val ) . '</div>';
	}

	/**
	 * @param array  $props Props.
	 * @param string $class Class.
	 * @param string $id    Id.
	 * @return string
	 */
	private static function meta_box_field( array $props, $class, $id ) {
		$field = sanitize_text_field( (string) ( $props['field'] ?? '' ) );
		$val   = '';
		if ( $field && function_exists( 'rwmb_meta' ) ) {
			$raw = rwmb_meta( $field );
			$val = is_scalar( $raw ) ? (string) $raw : wp_json_encode( $raw );
		}
		return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $id ) . '" data-ngc-bind="metabox">' . esc_html( $val ) . '</div>';
	}

	/**
	 * @param array  $props Props.
	 * @param string $class Class.
	 * @param string $id    Id.
	 * @return string
	 */
	private static function woo_product( array $props, $class, $id ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $id ) . '"><!-- WooCommerce inactive --></div>';
		}
		$pid     = (int) ( $props['productId'] ?? 0 );
		$product = $pid ? wc_get_product( $pid ) : null;
		if ( ! $product ) {
			return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $id ) . '"><!-- product missing --></div>';
		}
		return '<div class="' . esc_attr( $class ) . ' ngc-b-woo" data-ngc-node="' . esc_attr( $id ) . '">' .
			'<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a>' .
			'<span class="ngc-b-woo__price">' . wp_kses_post( $product->get_price_html() ) . '</span>' .
			'</div>';
	}
}
