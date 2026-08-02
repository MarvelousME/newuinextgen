<?php
/**
 * Theme option getter with override cascade.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bi_is_inherit( $value ) {
    return 'inherit' === $value || '' === $value || null === $value;
}

/**
 * @param mixed $defa
 * @return mixed
 */
function bi_get_theme_option( $name, $defa = '', $post_id = 0 ) {
    bi_load_theme_options();

    $rez           = $defa;
    $from_post     = false;
    $post_id       = $post_id ?: ( is_singular() ? get_queried_object_id() : 0 );
    $options_meta  = [];

    if ( $post_id > 0 ) {
        if ( ! bi_storage_isset( 'post_options_meta', (string) $post_id ) ) {
            bi_storage_set_array( 'post_options_meta', (string) $post_id, get_post_meta( $post_id, 'bi_options', true ) ?: [] );
        }
        $options_meta = bi_storage_get_array( 'post_options_meta', (string) $post_id, '', [] );
        if ( isset( $options_meta[ $name ] ) && ! bi_is_inherit( $options_meta[ $name ] ) ) {
            $rez       = $options_meta[ $name ];
            $from_post = true;
        }
    }

    if ( ! $from_post && $post_id > 0 && is_singular() && (int) $post_id === get_queried_object_id() ) {
        $runtime_meta = bi_storage_get( 'options_meta', [] );
        if ( isset( $runtime_meta[ $name ] ) && ! bi_is_inherit( $runtime_meta[ $name ] ) ) {
            $rez       = $runtime_meta[ $name ];
            $from_post = true;
        }
    }

    if ( ! $from_post && $post_id > 0 ) {
        $registry_default = bi_get_registry_page_default( $name, $post_id );
        if ( null !== $registry_default ) {
            $meta = get_post_meta( $post_id, 'bi_options', true );
            $meta = is_array( $meta ) ? $meta : [];
            if ( ! isset( $meta[ $name ] ) || bi_is_inherit( $meta[ $name ] ) ) {
                $rez = $registry_default;
            }
        }
    }

    if ( ! $from_post && bi_storage_isset( 'options', $name, 'val' ) ) {
        $rez = bi_storage_get_array( 'options', $name, 'val', $defa );
    } elseif ( ! $from_post ) {
        $mod = get_theme_mod( $name, null );
        if ( null !== $mod ) {
            $rez = $mod;
        } elseif ( bi_storage_isset( 'options', $name, 'std' ) ) {
            $rez = bi_storage_get_array( 'options', $name, 'std', $defa );
        }
    }

    return apply_filters( "bi_theme_option_{$name}", apply_filters( 'bi_theme_option', $rez, $name, $post_id ), $post_id );
}

/**
 * Persist a theme option (Customizer / theme_mod) and refresh in-memory cache.
 *
 * @param string $name  Option key (e.g. bi_phone).
 * @param mixed  $value Value to store.
 * @return bool
 */
function bi_update_theme_option( $name, $value ) {
	$name = (string) $name;
	if ( '' === $name ) {
		return false;
	}
	set_theme_mod( $name, $value );
	if ( function_exists( 'bi_storage_set_array2' ) ) {
		bi_storage_set_array2( 'options', $name, 'val', $value );
	}
	return true;
}

function bi_theme_option_is_on( $name, $post_id = 0 ) {
    $val = bi_get_theme_option( $name, 0, $post_id );
    if ( is_numeric( $val ) ) {
        return (int) $val === 1;
    }
    return in_array( $val, [ 'yes', 'on', true, '1' ], true );
}

/**
 * Sanitized header/footer template slug.
 */
function bi_get_header_style( $post_id = 0 ) {
	$post_id = $post_id ?: ( is_singular() ? (int) get_queried_object_id() : 0 );
	$allowed = array_keys( bi_get_list_header_styles() );

	if ( $post_id > 0 ) {
		$slug = (string) get_post_field( 'post_name', $post_id );
		if ( $slug && function_exists( 'bi_page_type' ) && in_array( bi_page_type( $slug ), [ 'dashboard', 'admin' ], true ) ) {
			return 'minimal';
		}

		$meta = get_post_meta( $post_id, 'bi_options', true );
		$meta = is_array( $meta ) ? $meta : [];
		if ( isset( $meta['header_style'] ) && ! bi_is_inherit( $meta['header_style'] ) ) {
			$style = sanitize_key( $meta['header_style'] );
			if ( in_array( $style, $allowed, true ) ) {
				return $style;
			}
		}

		$registry = bi_get_registry_page_default( 'header_style', $post_id );
		if ( null !== $registry ) {
			$style = sanitize_key( $registry );
			if ( in_array( $style, $allowed, true ) ) {
				return $style;
			}
		}
	}

	$style = sanitize_key( bi_get_theme_option( 'header_style', 'transparent', $post_id ) );
	return in_array( $style, $allowed, true ) ? $style : 'transparent';
}

function bi_get_footer_style( $post_id = 0 ) {
    $style = sanitize_key( bi_get_theme_option( 'footer_style', 'default', $post_id ) );
    $allowed = array_keys( bi_get_list_footer_styles() );
    return in_array( $style, $allowed, true ) ? $style : 'default';
}
