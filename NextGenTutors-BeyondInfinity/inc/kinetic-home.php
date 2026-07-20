<?php
/**
 * Kinetic homepage helpers — icons and section data.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Inline SVG icon (Lucide-style, 24×24).
 *
 * @param string $name Icon key.
 * @return string Safe SVG markup.
 */
function bi_kinetic_icon( $name ) {
    $icons = [
        'bolt'       => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        'book'       => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'target'     => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'chart'      => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>',
        'shield'     => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'compass'    => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"/></svg>',
        'users'      => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'layout'     => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
        'box'        => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
        'type'       => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v16"/><path d="M4 7V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2"/><path d="M9 20h6"/></svg>',
        'sparkles'   => '<svg class="ngi-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>',
        'calendar'   => '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
        'check'      => '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
        'user'       => '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'star'       => '<svg class="ngi-icon-svg ngi-icon-svg--star" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'play'       => '<svg class="ngi-icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>',
        'pause'      => '<svg class="ngi-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>',
        'plus'       => '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
        'minus'      => '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/></svg>',
        'close'      => '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    ];

    return $icons[ $name ] ?? bi_ui_icon( $name, 24 );
}

/**
 * Accessible star rating markup.
 *
 * @param int $stars Star count (1–5).
 */
function bi_kinetic_stars( $stars ) {
    $stars = max( 1, min( 5, (int) $stars ) );
    printf(
        '<div class="ngi-stars" role="img" aria-label="%s">%s</div>',
        esc_attr( sprintf( /* translators: %d: star count */ __( '%d out of 5 stars', 'beyondinfinity' ), $stars ) ),
        str_repeat( '<span class="ngi-star" aria-hidden="true">★</span>', $stars )
    );
}

/**
 * Initials avatar for testimonials / tutors.
 *
 * @param string $name Display name.
 */
function bi_kinetic_initials( $name ) {
    $parts = preg_split( '/\s+/', trim( $name ) );
    $init  = '';
    foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
        $init .= strtoupper( mb_substr( $part, 0, 1 ) );
    }
    return $init ?: '?';
}

/**
 * Subject tabs for kinetic explorer (from BI tracks + extras).
 *
 * @return array<int, array{slug:string,title:string,body:string,bullets:array<int,string>}>
 */
function bi_kinetic_subject_tabs() {
    return [
        [
            'slug'    => 'mathematics',
            'title'   => __( 'Mathematics', 'beyondinfinity' ),
            'body'    => __( 'CAPS & IEB Pure Maths from Grade 1–12, Matric exam prep, homework rescue and weekly progress reports for parents.', 'beyondinfinity' ),
            'bullets' => [ __( 'Grade 1–12', 'beyondinfinity' ), __( 'Exam technique', 'beyondinfinity' ), __( 'Weekly progress', 'beyondinfinity' ), __( 'Homework rescue', 'beyondinfinity' ) ],
        ],
        [
            'slug'    => 'physical-science',
            'title'   => __( 'Physical Science', 'beyondinfinity' ),
            'body'    => __( 'Physics and chemistry with practical understanding, problem-solving drills and Matric confidence building.', 'beyondinfinity' ),
            'bullets' => [ __( 'Physics', 'beyondinfinity' ), __( 'Chemistry', 'beyondinfinity' ), __( 'Problem solving', 'beyondinfinity' ), __( 'Matric prep', 'beyondinfinity' ) ],
        ],
        [
            'slug'    => 'english',
            'title'   => __( 'English HL', 'beyondinfinity' ),
            'body'    => __( 'Essays, literature, comprehension and grammar coaching aligned to IEB and CAPS outcomes.', 'beyondinfinity' ),
            'bullets' => [ __( 'Comprehension', 'beyondinfinity' ), __( 'Essay writing', 'beyondinfinity' ), __( 'Grammar', 'beyondinfinity' ), __( 'Literature', 'beyondinfinity' ) ],
        ],
        [
            'slug'    => 'programming',
            'title'   => __( 'Programming', 'beyondinfinity' ),
            'body'    => __( 'Python, IT/CAT projects and logic foundations for school, college and portfolio building.', 'beyondinfinity' ),
            'bullets' => [ __( 'Python basics', 'beyondinfinity' ), __( 'Web projects', 'beyondinfinity' ), __( 'Logic', 'beyondinfinity' ), __( 'Portfolio support', 'beyondinfinity' ) ],
        ],
    ];
}

/**
 * Whether kinetic homepage assets should load.
 */
function bi_is_kinetic_home() {
	return function_exists( 'bi_use_kinetic_home' )
		? bi_use_kinetic_home() && ! bi_is_builder_edit_mode()
		: is_front_page() && ! bi_is_builder_edit_mode();
}
