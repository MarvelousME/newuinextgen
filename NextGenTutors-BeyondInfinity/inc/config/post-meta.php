<?php
/**
 * REST-registered post meta for page option overrides.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'bi_register_bi_options_meta' );
function bi_register_bi_options_meta() {
    $post_types = apply_filters( 'bi_filter_allow_override_options', [ 'page', 'post' ] );

    foreach ( (array) $post_types as $post_type ) {
        register_post_meta(
            $post_type,
            'bi_options',
            [
                'type'              => 'object',
                'single'            => true,
                'show_in_rest'      => [
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => true,
                    ],
                ],
                'auth_callback'     => 'bi_bi_options_meta_auth',
                'sanitize_callback' => 'bi_sanitize_bi_options_meta',
            ]
        );
    }
}

function bi_bi_options_meta_auth( $allowed, $meta_key, $post_id ) {
    unset( $allowed, $meta_key );
    return current_user_can( 'edit_post', $post_id );
}

/**
 * @param mixed $value Raw meta value.
 * @return array<string, mixed>
 */
function bi_sanitize_bi_options_meta( $value ) {
    if ( ! is_array( $value ) ) {
        return [];
    }
    $schema = bi_get_override_options_schema();
    $clean  = [];
    foreach ( $value as $key => $raw ) {
        if ( ! isset( $schema[ $key ] ) ) {
            continue;
        }
        if ( bi_is_inherit( $raw ) ) {
            $clean[ $key ] = 'inherit';
            continue;
        }
        $opt = $schema[ $key ];
        if ( 'checkbox' === $opt['type'] ) {
            $clean[ $key ] = $raw ? 1 : 0;
        } elseif ( 'number' === $opt['type'] ) {
            $clean[ $key ] = absint( $raw );
        } elseif ( 'select' === $opt['type'] ) {
            $choices = array_keys( $opt['options'] ?? [] );
            $clean[ $key ] = in_array( $raw, $choices, true ) ? $raw : ( $opt['std'] ?? '' );
        } else {
            $clean[ $key ] = sanitize_text_field( (string) $raw );
        }
    }
    return $clean;
}
