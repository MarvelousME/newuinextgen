<?php
/**
 * Color scheme definitions → CSS custom properties.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', 'bi_setup_color_schemes', 1 );
function bi_setup_color_schemes() {
    $schemes = apply_filters(
        'bi_filter_color_schemes',
        [
            'default' => [
                'title'  => __( 'Beyond Infinity', 'beyondinfinity' ),
                'colors' => [
                    '--ngt-primary'        => '#07172f',
                    '--ngt-primary-dark'   => '#031126',
                    '--ngt-primary-light'  => '#e8f4ff',
                    '--ngt-secondary'      => '#28c7f7',
                    '--ngt-secondary-dark' => '#1aa8d4',
                    '--ngt-secondary-light'=> '#d6f4ff',
                    '--ngt-accent'         => '#ffb703',
                    '--ngt-bg'             => '#f5f8ff',
                    '--ngt-text'           => '#10213f',
                    '--ngt-text-2'         => '#687386',
                    '--ngt-text-3'         => '#94a3b8',
                ],
            ],
            'ocean' => [
                'title'  => __( 'Ocean', 'beyondinfinity' ),
                'colors' => [
                    '--ngt-primary'        => '#0284C7',
                    '--ngt-primary-dark'   => '#0369A1',
                    '--ngt-primary-light'  => '#E0F2FE',
                    '--ngt-secondary'      => '#14B8A6',
                    '--ngt-secondary-dark' => '#0D9488',
                    '--ngt-secondary-light'=> '#CCFBF1',
                    '--ngt-accent'         => '#F97316',
                    '--ngt-bg'             => '#F8FAFC',
                    '--ngt-text'           => '#0F172A',
                    '--ngt-text-2'         => '#475569',
                    '--ngt-text-3'         => '#64748B',
                ],
            ],
            'midnight' => [
                'title'  => __( 'Midnight', 'beyondinfinity' ),
                'colors' => [
                    '--ngt-primary'        => '#28C7F7',
                    '--ngt-primary-dark'   => '#123C7C',
                    '--ngt-primary-light'  => '#0A2540',
                    '--ngt-secondary'      => '#FFB703',
                    '--ngt-secondary-dark' => '#E6A500',
                    '--ngt-secondary-light'=> '#FFF8E6',
                    '--ngt-accent'         => '#FF7A1A',
                    '--ngt-bg'             => '#F5F8FF',
                    '--ngt-text'           => '#10213F',
                    '--ngt-text-2'         => '#475569',
                    '--ngt-text-3'         => '#687386',
                ],
            ],
        ]
    );
    bi_storage_set( 'color_schemes', $schemes );
}

/**
 * Active scheme token map.
 *
 * @return array<string, string>
 */
function bi_get_active_color_tokens() {
    $scheme_id = bi_get_theme_option( 'color_scheme', 'default' );
    $schemes   = bi_storage_get( 'color_schemes', [] );
    if ( isset( $schemes[ $scheme_id ]['colors'] ) ) {
        return $schemes[ $scheme_id ]['colors'];
    }
    return $schemes['default']['colors'] ?? [];
}
