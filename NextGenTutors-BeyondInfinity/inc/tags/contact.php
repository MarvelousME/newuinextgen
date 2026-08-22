<?php
/**
 * Template tags (split from inc/template-tags.php).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bi_get_phone() {
    return bi_get_theme_option( 'bi_phone', '081 334 0625' );
}

function bi_get_email() {
    return bi_get_theme_option( 'bi_email', 'admin@nextgentutors.co.za' );
}

function bi_get_support_email() {
    return bi_get_theme_option( 'bi_support_email', 'support@nextgentutors.co.za' );
}

function bi_get_whatsapp() {
    return bi_get_theme_option( 'bi_whatsapp', '27813340625' );
}

function bi_get_service_area() {
    return bi_get_theme_option( 'bi_service_area', 'Johannesburg launch, online support nationwide' );
}

function bi_whatsapp_url( $message = '' ) {
    $num = preg_replace( '/[^0-9]/', '', bi_get_whatsapp() );
    $url = 'https://wa.me/' . $num;
    if ( $message ) {
        $url .= '?text=' . rawurlencode( $message );
    }
    return $url;
}

function bi_provinces() {
    return [
        'gauteng'       => 'Gauteng',
        'western-cape'  => 'Western Cape',
        'kwazulu-natal' => 'KwaZulu-Natal',
        'eastern-cape'  => 'Eastern Cape',
        'free-state'    => 'Free State',
        'mpumalanga'    => 'Mpumalanga',
        'limpopo'       => 'Limpopo',
        'north-west'    => 'North West',
        'northern-cape' => 'Northern Cape',
    ];
}
