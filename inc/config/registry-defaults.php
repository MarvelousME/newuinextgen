<?php
/**
 * Resolve registry defaults for page slugs.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return mixed|null
 */
function bi_get_registry_page_default( $name, $post_id ) {
    if ( ! $post_id || 'page' !== get_post_type( $post_id ) ) {
        return null;
    }
    $slug = get_post_field( 'post_name', $post_id );
    if ( ! $slug || ! function_exists( 'bi_pages_registry' ) ) {
        return null;
    }
    $registry = bi_pages_registry();
    return $registry[ $slug ]['config_defaults'][ $name ] ?? null;
}
