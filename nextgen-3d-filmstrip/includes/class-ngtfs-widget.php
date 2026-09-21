<?php
/**
 * WP_Widget for filmstrip.
 *
 * @package NextGen_3D_Filmstrip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classic widget.
 */
final class NGTFS_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'ngtfs_filmstrip',
			__( 'NextGen 3D Filmstrip', 'nextgen-3d-filmstrip' ),
			[
				'classname'   => 'ngtfs-widget',
				'description' => __( 'Perspective 3D filmstrip for tutors or subjects.', 'nextgen-3d-filmstrip' ),
			]
		);
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @param array<string, mixed> $instance Instance.
	 */
	public function widget( $args, $instance ): void {
		$source = sanitize_key( (string) ( $instance['source'] ?? 'tutors' ) );
		$cards  = NGTFS_Data::get_cards(
			[
				'source'  => $source,
				'limit'   => absint( $instance['limit'] ?? 8 ),
				'subject' => sanitize_text_field( (string) ( $instance['subject'] ?? '' ) ),
				'manual'  => (string) ( $instance['manual'] ?? '' ),
			]
		);

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo NGTFS_Renderer::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$cards,
			[
				'title'    => sanitize_text_field( (string) ( $instance['title'] ?? '' ) ),
				'subtitle' => sanitize_text_field( (string) ( $instance['subtitle'] ?? '' ) ),
				'source'   => $source,
				'autoplay' => ! empty( $instance['autoplay'] ),
				'loop'     => ! isset( $instance['loop'] ) || ! empty( $instance['loop'] ),
			]
		);
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array<string, mixed> $new_instance New.
	 * @param array<string, mixed> $old_instance Old.
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return [
			'title'    => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'subtitle' => sanitize_text_field( (string) ( $new_instance['subtitle'] ?? '' ) ),
			'source'   => sanitize_key( (string) ( $new_instance['source'] ?? 'tutors' ) ),
			'limit'    => max( 1, min( 24, absint( $new_instance['limit'] ?? 8 ) ) ),
			'subject'  => sanitize_text_field( (string) ( $new_instance['subject'] ?? '' ) ),
			'manual'   => sanitize_textarea_field( (string) ( $new_instance['manual'] ?? '' ) ),
			'autoplay' => ! empty( $new_instance['autoplay'] ) ? 1 : 0,
			'loop'     => ! empty( $new_instance['loop'] ) ? 1 : 0,
		];
	}

	/**
	 * @param array<string, mixed> $instance Instance.
	 */
	public function form( $instance ): void {
		$instance = wp_parse_args(
			$instance,
			[
				'title'    => __( 'Tutors who change trajectories', 'nextgen-3d-filmstrip' ),
				'subtitle' => __( 'Drag, swipe, or use arrows.', 'nextgen-3d-filmstrip' ),
				'source'   => 'tutors',
				'limit'    => 8,
				'subject'  => '',
				'manual'   => '',
				'autoplay' => 1,
				'loop'     => 1,
			]
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'nextgen-3d-filmstrip' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( (string) $instance['title'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>"><?php esc_html_e( 'Subtitle', 'nextgen-3d-filmstrip' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subtitle' ) ); ?>" type="text" value="<?php echo esc_attr( (string) $instance['subtitle'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'source' ) ); ?>"><?php esc_html_e( 'Source', 'nextgen-3d-filmstrip' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'source' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'source' ) ); ?>">
				<?php foreach ( [ 'tutors' => 'Tutors', 'subjects' => 'Subjects', 'manual' => 'Manual' ] as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $instance['source'], $val ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'nextgen-3d-filmstrip' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="24" value="<?php echo esc_attr( (string) $instance['limit'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'subject' ) ); ?>"><?php esc_html_e( 'Filter tutors by subject slug (optional)', 'nextgen-3d-filmstrip' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subject' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subject' ) ); ?>" type="text" value="<?php echo esc_attr( (string) $instance['subject'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'manual' ) ); ?>"><?php esc_html_e( 'Manual cards (Title|Subtitle|ImageURL|Link)', 'nextgen-3d-filmstrip' ); ?></label>
			<textarea class="widefat" rows="5" id="<?php echo esc_attr( $this->get_field_id( 'manual' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'manual' ) ); ?>"><?php echo esc_textarea( (string) $instance['manual'] ); ?></textarea>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'autoplay' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'autoplay' ) ); ?>" value="1" <?php checked( ! empty( $instance['autoplay'] ) ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'autoplay' ) ); ?>"><?php esc_html_e( 'Autoplay', 'nextgen-3d-filmstrip' ); ?></label>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'loop' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'loop' ) ); ?>" value="1" <?php checked( ! empty( $instance['loop'] ) ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'loop' ) ); ?>"><?php esc_html_e( 'Loop', 'nextgen-3d-filmstrip' ); ?></label>
		</p>
		<?php
	}
}
