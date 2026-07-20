<?php
/**
 * Per-page theme option overrides (inherit / custom).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes', 'bi_register_override_metabox' );
function bi_register_override_metabox() {
    $post_types = apply_filters( 'bi_filter_allow_override_options', [ 'page', 'post' ] );
    foreach ( (array) $post_types as $post_type ) {
        add_meta_box(
            'bi_override_options',
            __( 'BeyondInfinity Page Options', 'beyondinfinity' ),
            'bi_render_override_metabox',
            $post_type,
            'normal',
            'default'
        );
    }
}

function bi_render_override_metabox( $post ) {
    wp_nonce_field( 'bi_save_override_options', 'bi_override_nonce' );
    $saved   = get_post_meta( $post->ID, 'bi_options', true );
    $saved   = is_array( $saved ) ? $saved : [];
    $schema  = bi_get_override_options_schema();
    echo '<div class="bi-override-options">';
    echo '<p class="description">' . esc_html__( 'Leave on Inherit to use Customizer values. Unlock to override for this page only.', 'beyondinfinity' ) . '</p>';
    foreach ( $schema as $id => $opt ) {
        $inherit = ! isset( $saved[ $id ] ) || bi_is_inherit( $saved[ $id ] ?? 'inherit' );
        $val     = $inherit ? ( bi_get_theme_option( $id, $opt['std'] ?? '' ) ) : $saved[ $id ];
        $class   = $inherit ? 'bi-override--inherit' : 'bi-override--custom';
        echo '<div class="bi-override-item ' . esc_attr( $class ) . '" data-option="' . esc_attr( $id ) . '">';
        echo '<div class="bi-override-item__head">';
        echo '<strong>' . esc_html( $opt['title'] ) . '</strong>';
        echo '<button type="button" class="button bi-override-toggle" aria-expanded="' . ( $inherit ? 'false' : 'true' ) . '" aria-label="' . esc_attr__( 'Toggle custom value for this option', 'beyondinfinity' ) . '">';
        echo $inherit ? esc_html__( 'Inherit', 'beyondinfinity' ) : esc_html__( 'Custom', 'beyondinfinity' );
        echo '</button></div>';
        if ( ! empty( $opt['desc'] ) ) {
            echo '<p class="description">' . esc_html( $opt['desc'] ) . '</p>';
        }
        echo '<input type="hidden" name="bi_options_inherit[' . esc_attr( $id ) . ']" value="' . ( $inherit ? '1' : '0' ) . '" class="bi-override-inherit-flag" />';
        echo '<div class="bi-override-field"' . ( $inherit ? ' hidden' : '' ) . '>';
        bi_render_override_field( $id, $opt, $val );
        echo '</div></div>';
    }
    echo '</div>';
}

/**
 * @param array<string, mixed> $opt
 * @param mixed              $val
 */
function bi_render_override_field( $id, $opt, $val ) {
    $name = 'bi_options_field[' . esc_attr( $id ) . ']';
    switch ( $opt['type'] ) {
        case 'checkbox':
            printf(
                '<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
                $name,
                checked( (int) $val, 1, false ),
                esc_html__( 'Enabled', 'beyondinfinity' )
            );
            break;
        case 'select':
            echo '<select name="' . esc_attr( $name ) . '">';
            foreach ( (array) ( $opt['options'] ?? [] ) as $k => $label ) {
                if ( 'inherit' === $k ) {
                    continue;
                }
                printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $val, $k, false ), esc_html( $label ) );
            }
            echo '</select>';
            break;
        case 'number':
            printf( '<input type="number" name="%s" value="%s" min="0" step="1" />', $name, esc_attr( (string) $val ) );
            break;
        default:
            printf( '<input type="text" class="widefat" name="%s" value="%s" />', $name, esc_attr( (string) $val ) );
    }
}

add_action( 'save_post', 'bi_save_override_options' );
function bi_save_override_options( $post_id ) {
    if ( ! isset( $_POST['bi_override_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bi_override_nonce'] ) ), 'bi_save_override_options' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $allowed_types = apply_filters( 'bi_filter_allow_override_options', [ 'page', 'post' ] );
    if ( ! in_array( get_post_type( $post_id ), (array) $allowed_types, true ) ) {
        return;
    }
    $schema  = bi_get_override_options_schema();
    $inherit = isset( $_POST['bi_options_inherit'] ) && is_array( $_POST['bi_options_inherit'] ) ? wp_unslash( $_POST['bi_options_inherit'] ) : [];
    $fields  = isset( $_POST['bi_options_field'] ) && is_array( $_POST['bi_options_field'] ) ? wp_unslash( $_POST['bi_options_field'] ) : [];
    $meta    = [];
    foreach ( array_keys( $schema ) as $id ) {
        if ( ! empty( $inherit[ $id ] ) ) {
            $meta[ $id ] = 'inherit';
            continue;
        }
        $opt = $schema[ $id ];
        if ( 'checkbox' === $opt['type'] ) {
            $meta[ $id ] = ! empty( $fields[ $id ] ) ? 1 : 0;
            continue;
        }
        if ( ! isset( $fields[ $id ] ) ) {
            $meta[ $id ] = 'inherit';
            continue;
        }
        $raw = $fields[ $id ];
        if ( 'number' === $opt['type'] ) {
            $meta[ $id ] = absint( $raw );
        } elseif ( 'select' === $opt['type'] ) {
            $choices = array_keys( $opt['options'] ?? [] );
            $meta[ $id ] = in_array( $raw, $choices, true ) ? $raw : ( $opt['std'] ?? '' );
        } else {
            $meta[ $id ] = sanitize_text_field( $raw );
        }
    }
    update_post_meta( $post_id, 'bi_options', $meta );
}

add_action( 'admin_enqueue_scripts', 'bi_override_admin_assets' );
function bi_override_admin_assets( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    wp_enqueue_style( 'bi-options-admin', BI_URI . '/assets/css/options-admin.css', [], BI_VERSION );
    wp_enqueue_script( 'bi-options-admin', BI_URI . '/assets/js/bi-options-admin.js', [ 'jquery' ], BI_VERSION, true );
}
