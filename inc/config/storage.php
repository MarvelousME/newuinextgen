<?php
/**
 * Theme configuration storage (SmartHead-style registry).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return mixed
 */
function bi_storage_get( $var_name, $default = '' ) {
    global $BI_STORAGE;
    return isset( $BI_STORAGE[ $var_name ] ) ? $BI_STORAGE[ $var_name ] : $default;
}

/**
 * @param mixed $value
 */
function bi_storage_set( $var_name, $value ) {
    global $BI_STORAGE;
    $BI_STORAGE[ $var_name ] = $value;
}

function bi_storage_empty( $var_name, $key = '', $key2 = '' ) {
    global $BI_STORAGE;
    if ( $key && $key2 ) {
        return empty( $BI_STORAGE[ $var_name ][ $key ][ $key2 ] );
    }
    if ( $key ) {
        return empty( $BI_STORAGE[ $var_name ][ $key ] );
    }
    return empty( $BI_STORAGE[ $var_name ] );
}

function bi_storage_isset( $var_name, $key = '', $key2 = '' ) {
    global $BI_STORAGE;
    if ( $key && $key2 ) {
        return isset( $BI_STORAGE[ $var_name ][ $key ][ $key2 ] );
    }
    if ( $key ) {
        return isset( $BI_STORAGE[ $var_name ][ $key ] );
    }
    return isset( $BI_STORAGE[ $var_name ] );
}

/**
 * @return mixed
 */
function bi_storage_get_array( $var_name, $key, $key2 = '', $default = '' ) {
    global $BI_STORAGE;
    if ( $key2 ) {
        return isset( $BI_STORAGE[ $var_name ][ $key ][ $key2 ] ) ? $BI_STORAGE[ $var_name ][ $key ][ $key2 ] : $default;
    }
    return isset( $BI_STORAGE[ $var_name ][ $key ] ) ? $BI_STORAGE[ $var_name ][ $key ] : $default;
}

/**
 * @param mixed $value
 */
function bi_storage_set_array2( $var_name, $key, $key2, $value ) {
    global $BI_STORAGE;
    if ( ! isset( $BI_STORAGE[ $var_name ] ) ) {
        $BI_STORAGE[ $var_name ] = [];
    }
    if ( ! isset( $BI_STORAGE[ $var_name ][ $key ] ) ) {
        $BI_STORAGE[ $var_name ][ $key ] = [];
    }
    $BI_STORAGE[ $var_name ][ $key ][ $key2 ] = $value;
}

/**
 * @param mixed $value
 */
function bi_storage_set_array( $var_name, $key, $value ) {
    global $BI_STORAGE;
    if ( ! isset( $BI_STORAGE[ $var_name ] ) ) {
        $BI_STORAGE[ $var_name ] = [];
    }
    $BI_STORAGE[ $var_name ][ $key ] = $value;
}
