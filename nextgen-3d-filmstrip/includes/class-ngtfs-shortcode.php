<?php
/**
 * Shortcode [ngt_filmstrip].
 *
 * @package NextGen_3D_Filmstrip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode bridge.
 */
final class NGTFS_Shortcode {

	public static function init(): void {
		add_shortcode( 'ngt_filmstrip', [ self::class, 'render' ] );
		add_shortcode( 'nextgen_filmstrip', [ self::class, 'render' ] );
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'source'   => 'tutors',
				'limit'    => '8',
				'subject'  => '',
				'title'    => '',
				'subtitle' => '',
				'autoplay' => 'yes',
				'loop'     => 'yes',
				'manual'   => '',
				'class'    => '',
			],
			is_array( $atts ) ? $atts : [],
			'ngt_filmstrip'
		);

		$source = sanitize_key( (string) $atts['source'] );
		if ( ! in_array( $source, [ 'tutors', 'subjects', 'manual' ], true ) ) {
			$source = 'tutors';
		}

		$defaults = [
			'tutors'   => [
				'title'    => __( 'Tutors who change trajectories', 'nextgen-3d-filmstrip' ),
				'subtitle' => __( 'Drag, swipe, or use arrows — every educator is vetted.', 'nextgen-3d-filmstrip' ),
			],
			'subjects' => [
				'title'    => __( 'Explore subjects', 'nextgen-3d-filmstrip' ),
				'subtitle' => __( 'Pick a subject and meet matching tutors.', 'nextgen-3d-filmstrip' ),
			],
			'manual'   => [
				'title'    => __( 'Featured', 'nextgen-3d-filmstrip' ),
				'subtitle' => '',
			],
		];

		$title    = '' !== trim( (string) $atts['title'] ) ? (string) $atts['title'] : $defaults[ $source ]['title'];
		$subtitle = '' !== trim( (string) $atts['subtitle'] ) ? (string) $atts['subtitle'] : $defaults[ $source ]['subtitle'];

		$cards = NGTFS_Data::get_cards(
			[
				'source'  => $source,
				'limit'   => absint( $atts['limit'] ),
				'subject' => sanitize_text_field( (string) $atts['subject'] ),
				'manual'  => (string) $atts['manual'],
			]
		);

		return NGTFS_Renderer::render(
			$cards,
			[
				'title'    => sanitize_text_field( $title ),
				'subtitle' => sanitize_text_field( $subtitle ),
				'source'   => $source,
				'autoplay' => in_array( strtolower( (string) $atts['autoplay'] ), [ 'yes', '1', 'true' ], true ),
				'loop'     => in_array( strtolower( (string) $atts['loop'] ), [ 'yes', '1', 'true' ], true ),
				'class'    => sanitize_html_class( (string) $atts['class'] ),
			]
		);
	}
}
