<?php
/**
 * Single tutor profile (tutors CPT).
 *
 * @package BeyondInfinity
 */

get_header();

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        $tutor = bi_format_tutor_post( get_post() );
        bi_render_tutor_profile( $tutor );
    }
} else {
    $demo = bi_get_demo_tutors( 1 );
    if ( ! empty( $demo[0] ) ) {
        $demo[0]['permalink'] = home_url( '/find-a-tutor' );
        bi_render_tutor_profile( $demo[0] );
    }
}

get_footer();
