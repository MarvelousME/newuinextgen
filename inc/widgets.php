<?php
/**
 * Widgets and shortcodes — tutors carousel.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'bi_tutors_carousel', 'bi_shortcode_tutors_carousel' );
add_shortcode( 'ngc_tutor_carousel', 'bi_shortcode_tutors_carousel' );
add_shortcode( 'ngt_tutor_carousel', 'bi_shortcode_tutors_carousel' );
/**
 * Shortcode: [bi_tutors_carousel title="" subtitle="" limit="8"]
 *
 * @param array|string $atts Attributes.
 * @return string
 */
function bi_shortcode_tutors_carousel( $atts ) {
    $atts = shortcode_atts( [
        'eyebrow'  => '',
        'title'    => '',
        'subtitle' => '',
        'limit'    => 8,
    ], $atts, 'bi_tutors_carousel' );

    $args = [ 'limit' => (int) $atts['limit'] ];
    if ( $atts['eyebrow'] ) {
        $args['eyebrow'] = $atts['eyebrow'];
    }
    if ( $atts['title'] ) {
        $args['title'] = $atts['title'];
    }
    if ( $atts['subtitle'] ) {
        $args['subtitle'] = $atts['subtitle'];
    }

    ob_start();
    bi_render_tutors_carousel( $args );
    return ob_get_clean();
}

add_action( 'widgets_init', 'bi_register_widgets' );
function bi_register_widgets() {
    register_widget( 'BI_Tutors_Carousel_Widget' );
}

/**
 * Featured tutors 3D carousel widget.
 */
class BI_Tutors_Carousel_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bi_tutors_carousel',
            __( 'NextGen Tutors Carousel', 'beyondinfinity' ),
            [ 'description' => __( '3D carousel of vetted tutors with swipe navigation.', 'beyondinfinity' ) ]
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        bi_render_tutors_carousel( [
            'eyebrow'  => $instance['eyebrow'] ?? '',
            'title'    => $instance['title'] ?? __( 'Tutors Who Change Trajectories', 'beyondinfinity' ),
            'subtitle' => $instance['subtitle'] ?? '',
            'limit'    => isset( $instance['limit'] ) ? (int) $instance['limit'] : 6,
        ] );
        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function form( $instance ) {
        $eyebrow  = $instance['eyebrow'] ?? __( 'Meet a Few of Our Stars', 'beyondinfinity' );
        $title    = $instance['title'] ?? __( 'Tutors Who Change Trajectories', 'beyondinfinity' );
        $subtitle = $instance['subtitle'] ?? __( 'Every tutor is vetted and rated by real South African families.', 'beyondinfinity' );
        $limit    = isset( $instance['limit'] ) ? (int) $instance['limit'] : 6;
        ?>
        <p>
          <label for="<?php echo esc_attr( $this->get_field_id( 'eyebrow' ) ); ?>"><?php esc_html_e( 'Eyebrow', 'beyondinfinity' ); ?></label>
          <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'eyebrow' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'eyebrow' ) ); ?>" type="text" value="<?php echo esc_attr( $eyebrow ); ?>" />
        </p>
        <p>
          <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'beyondinfinity' ); ?></label>
          <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
          <label for="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>"><?php esc_html_e( 'Subtitle', 'beyondinfinity' ); ?></label>
          <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subtitle' ) ); ?>" rows="3"><?php echo esc_textarea( $subtitle ); ?></textarea>
        </p>
        <p>
          <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of tutors', 'beyondinfinity' ); ?></label>
          <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="3" max="12" value="<?php echo esc_attr( (string) $limit ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        return [
            'eyebrow'  => sanitize_text_field( $new_instance['eyebrow'] ?? '' ),
            'title'    => sanitize_text_field( $new_instance['title'] ?? '' ),
            'subtitle' => sanitize_textarea_field( $new_instance['subtitle'] ?? '' ),
            'limit'    => max( 3, min( 12, (int) ( $new_instance['limit'] ?? 6 ) ) ),
        ];
    }
}
