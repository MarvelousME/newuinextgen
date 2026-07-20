<?php
/**
 * Schema-driven Customizer registration.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BI_CUSTOMIZE_PRIORITY', 200 );

add_action( 'customize_register', 'bi_customizer_register_controls', 11 );
function bi_customizer_register_controls( $wp_customize ) {
    bi_load_theme_options();
    $options = bi_get_options_schema();
    $panels  = [];
    $sections = [];

    foreach ( $options as $id => $opt ) {
        if ( in_array( $opt['type'], [ 'panel', 'section', 'panel_end', 'section_end' ], true ) ) {
            if ( 'panel' === $opt['type'] ) {
                $wp_customize->add_panel(
                    $id,
                    [
                        'title'       => $opt['title'],
                        'description' => $opt['desc'] ?? '',
                        'priority'    => $opt['priority'] ?? BI_CUSTOMIZE_PRIORITY,
                    ]
                );
                $panels[] = $id;
            } elseif ( 'section' === $opt['type'] ) {
                $args = [
                    'title'       => $opt['title'],
                    'description' => $opt['desc'] ?? '',
                    'priority'    => $opt['priority'] ?? BI_CUSTOMIZE_PRIORITY,
                ];
                if ( ! empty( $opt['panel'] ) ) {
                    $args['panel'] = $opt['panel'];
                }
                $wp_customize->add_section( $id, $args );
                $sections[] = $id;
            }
            continue;
        }

        $sanitize = bi_customizer_sanitize_callback( $opt );
        $wp_customize->add_setting(
            $id,
            [
                'default'           => $opt['std'] ?? '',
                'sanitize_callback' => $sanitize,
                'transport'         => 'color_scheme' === $id ? 'postMessage' : 'refresh',
            ]
        );

        $control_args = [
            'label'       => $opt['title'],
            'description' => $opt['desc'] ?? '',
            'section'     => $opt['section'] ?? 'bi_section_contact',
        ];

        switch ( $opt['type'] ) {
            case 'checkbox':
                $control_args['type'] = 'checkbox';
                break;
            case 'number':
                $control_args['type'] = 'number';
                break;
            case 'select':
                $control_args['type']    = 'select';
                $control_args['choices'] = $opt['options'] ?? [];
                break;
            case 'color':
                $wp_customize->add_control(
                    new WP_Customize_Color_Control(
                        $wp_customize,
                        $id,
                        array_merge( $control_args, [ 'settings' => $id ] )
                    )
                );
                continue 2;
            default:
                $control_args['type'] = 'text';
        }

        $wp_customize->add_control( $id, $control_args );
    }
}

/**
 * @param array<string, mixed> $opt
 */
function bi_customizer_sanitize_callback( $opt ) {
    switch ( $opt['type'] ) {
        case 'checkbox':
            return function ( $value ) {
                return $value ? 1 : 0;
            };
        case 'number':
            return 'absint';
        case 'select':
            return function ( $value ) use ( $opt ) {
                $choices = array_keys( $opt['options'] ?? [] );
                return in_array( $value, $choices, true ) ? $value : ( $opt['std'] ?? '' );
            };
        default:
            return 'sanitize_text_field';
    }
}

add_action( 'customize_save_after', 'bi_customizer_save_refresh' );
function bi_customizer_save_refresh() {
    bi_storage_set( 'options_loaded', false );
    bi_load_theme_options();
    if ( function_exists( 'bi_customizer_save_css' ) ) {
        bi_customizer_save_css();
    }
}

add_action( 'customize_preview_init', 'bi_customizer_preview_assets' );
function bi_customizer_preview_assets() {
    wp_enqueue_script(
        'bi-customizer-preview',
        BI_URI . '/assets/js/bi-customizer-preview.js',
        [ 'customize-preview', 'jquery' ],
        BI_VERSION,
        true
    );
    $schemes = [];
    foreach ( (array) bi_storage_get( 'color_schemes', [] ) as $id => $scheme ) {
        $schemes[ $id ] = $scheme['colors'] ?? [];
    }
    wp_localize_script( 'bi-customizer-preview', 'biCustomizerPreview', [ 'schemes' => $schemes ] );
}
