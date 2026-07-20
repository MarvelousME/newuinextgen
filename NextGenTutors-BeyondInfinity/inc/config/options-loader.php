<?php
/**
 * Load theme_mod values into options schema.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', 'bi_load_theme_options', 5 );
add_action( 'customize_register', 'bi_load_theme_options', 1 );

function bi_load_theme_options() {
    if ( bi_storage_get( 'options_loaded' ) ) {
        return;
    }
    $options = bi_get_options_schema();
    foreach ( $options as $k => $v ) {
        if ( ! isset( $v['std'] ) ) {
            continue;
        }
        $value = $v['std'];
        $mod   = get_theme_mod( $k, null );
        if ( null !== $mod ) {
            $value = $mod;
        }
        bi_storage_set_array2( 'options', $k, 'val', $value );
    }
    bi_storage_set( 'options_loaded', true );
    do_action( 'bi_action_load_options' );
}

add_action( 'wp', 'bi_prime_page_options_meta', 1 );
function bi_prime_page_options_meta() {
    if ( is_singular() ) {
        bi_storage_set( 'options_meta', get_post_meta( get_queried_object_id(), 'bi_options', true ) ?: [] );
    }
}
