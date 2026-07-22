<?php
/**
 * Command palette (Ctrl/Cmd+K) — global navigation for logged-in users.
 *
 * Routes are assembled server-side (role-aware) and consumed by
 * assets/js/bi-command.js which renders an accessible dialog.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'bi_command_palette_enqueue', 30 );
function bi_command_palette_enqueue() {
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }
    if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
        return;
    }

    wp_enqueue_style( 'bi-command', BI_URI . '/assets/css/bi-command.css', [], BI_VERSION );
    wp_enqueue_script( 'bi-focus-trap', BI_URI . '/assets/js/bi-focus-trap.js', [], BI_VERSION, true );
    wp_enqueue_script( 'bi-command', BI_URI . '/assets/js/bi-command.js', [ 'bi-focus-trap', 'bi-scheme' ], BI_VERSION, true );

    wp_localize_script(
        'bi-command',
        'biCommand',
        [
            'routes' => bi_command_palette_routes(),
            'i18n'   => [
                'placeholder' => __( 'Search pages and actions…', 'beyondinfinity' ),
                'empty'       => __( 'No results found.', 'beyondinfinity' ),
                'title'       => __( 'Command palette', 'beyondinfinity' ),
                'hint'        => __( 'Type to search · Enter to open · Esc to close', 'beyondinfinity' ),
            ],
        ]
    );
}

/**
 * Role-aware routes registry.
 *
 * @return array<int, array<string, string>>
 */
function bi_command_palette_routes() {
    $user   = wp_get_current_user();
    $roles  = (array) $user->roles;
    $routes = [];

    $dashboards = [
        'parent'  => [ __( 'Parent dashboard', 'beyondinfinity' ), '/parent-dashboard' ],
        'student' => [ __( 'Student dashboard', 'beyondinfinity' ), '/student-dashboard' ],
        'tutor'   => [ __( 'Tutor dashboard', 'beyondinfinity' ), '/tutor-dashboard' ],
    ];
    foreach ( $dashboards as $role => $def ) {
        if ( in_array( $role, $roles, true ) || ( 'parent' === $role && in_array( 'parent_guardian', $roles, true ) ) ) {
            $routes[] = [
                'label'    => $def[0],
                'url'      => home_url( $def[1] ),
                'section'  => __( 'Dashboards', 'beyondinfinity' ),
                'keywords' => 'dashboard home overview',
            ];
        }
    }
    if ( current_user_can( 'manage_options' ) ) {
        $routes[] = [
            'label'    => __( 'Admin dashboard', 'beyondinfinity' ),
            'url'      => home_url( '/admin-dashboard' ),
            'section'  => __( 'Dashboards', 'beyondinfinity' ),
            'keywords' => 'dashboard admin overview',
        ];
        $routes[] = [
            'label'    => __( 'WordPress admin', 'beyondinfinity' ),
            'url'      => admin_url(),
            'section'  => __( 'Dashboards', 'beyondinfinity' ),
            'keywords' => 'wp-admin settings manage',
        ];
    }

    $pages = [
        [ __( 'Home', 'beyondinfinity' ), '/', 'home start front' ],
        [ __( 'Find a Tutor', 'beyondinfinity' ), '/find-a-tutor', 'search book marketplace tutor' ],
        [ __( 'Become a Tutor', 'beyondinfinity' ), '/become-a-tutor', 'apply teach application' ],
        [ __( 'Pricing', 'beyondinfinity' ), '/pricing', 'cost rates plans fees' ],
        [ __( 'About', 'beyondinfinity' ), '/about', 'company team story' ],
        [ __( 'Contact', 'beyondinfinity' ), '/contact', 'support help email message' ],
    ];
    foreach ( $pages as $p ) {
        $routes[] = [
            'label'    => $p[0],
            'url'      => home_url( $p[1] ),
            'section'  => __( 'Pages', 'beyondinfinity' ),
            'keywords' => $p[2],
        ];
    }

    $routes[] = [
        'label'    => __( 'Toggle dark mode', 'beyondinfinity' ),
        'action'   => 'toggle-scheme',
        'section'  => __( 'Actions', 'beyondinfinity' ),
        'keywords' => 'dark light theme scheme appearance',
    ];
    $routes[] = [
        'label'    => __( 'Log out', 'beyondinfinity' ),
        'url'      => wp_logout_url( home_url( '/' ) ),
        'section'  => __( 'Actions', 'beyondinfinity' ),
        'keywords' => 'sign out exit logout',
    ];

    return apply_filters( 'bi_command_palette_routes', $routes );
}
