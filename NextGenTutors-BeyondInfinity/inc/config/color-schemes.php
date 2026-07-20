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
                'title'  => __( 'NextGen Default', 'beyondinfinity' ),
                'colors' => [
                    '--ngt-primary'        => '#0066CC',
                    '--ngt-primary-dark'   => '#004A99',
                    '--ngt-primary-light'  => '#E8F1FB',
                    '--ngt-secondary'      => '#00C896',
                    '--ngt-secondary-dark' => '#00A67D',
                    '--ngt-secondary-light'=> '#E6FAF5',
                    '--ngt-accent'         => '#FF6B35',
                    '--ngt-bg'             => '#FAFBFC',
                    '--ngt-text'           => '#1A202C',
                    '--ngt-text-2'         => '#4A5568',
                    '--ngt-text-3'         => '#718096',
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
