<?php
/**
 * SEO meta descriptions and JSON-LD schema.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bi_page_meta_descriptions() {
    return [
        'home'              => 'NextGen Tutors connects South African learners with qualified tutors for online, in-person, and hybrid support from Grade 1 to tertiary.',
        'find-a-tutor'      => 'Request a tutor for your child. NextGen Tutors matches learners across all 9 provinces with patient, qualified tutors.',
        'become-a-tutor'    => 'Join NextGen Tutors as a tutor. Flexible online, in-person, and hybrid sessions with platform-managed payments.',
        'about'             => 'NextGen Tutors is building accessible one-on-one academic support for every learner in South Africa.',
        'contact'           => 'Contact NextGen Tutors for tutor matching, tutor applications, and platform support in Johannesburg and nationwide online.',
        'pricing'           => sprintf(
            'Transparent tutoring rates from %s per hour. Online, in-person, and tertiary packages with platform-managed payments.',
            bi_format_rate( 'rate_online', 320 )
        ),
        'tutor-vetting'     => 'Learn how NextGen Tutors vets tutors through profile review, qualification checks, and manual approval.',
        'safety-guide'      => 'Safe tutoring guidelines for parents and tutors using NextGen Tutors across South Africa.',
        'register'          => 'Register as a parent, guardian, or student with NextGen Tutors.',
        'login'             => 'Login to your NextGen Tutors dashboard.',
        'privacy-policy'    => 'NextGen Tutors privacy policy and POPIA-aligned data practices.',
        'terms'             => 'Terms of service for parents, students, and tutors using NextGen Tutors.',
        'child-safety'      => 'Child safety policy for tutoring sessions facilitated through NextGen Tutors.',
        'thank-you'         => 'Thank you for contacting NextGen Tutors. Here is what happens next.',
        'guarantee'         => sprintf(
            '%s first-lesson guarantee — risk-free tutoring with rematch or refund.',
            bi_guarantee_label()
        ),
        'blog'              => 'Study tips, exam prep and education insights for South African families.',
        'support'           => 'Help centre for parents, students and tutors using NextGen Tutors.',
        'onboarding'        => 'Admin onboarding management for tutors and staff.',
        'wordpress-setup'   => 'Theme setup guide — required plugins, launch page sync, companion activation and database tables.',
        'parent-dashboard'  => 'Parent dashboard — manage children, tutors, lessons and billing on NextGen Tutors.',
        'student-dashboard' => 'Student dashboard — track sessions, progress, achievements and study goals.',
        'tutor-dashboard'   => 'Tutor dashboard — earnings, sessions, ratings and account standing.',
        'admin-dashboard'   => 'Admin mission control — platform health, registrations and demo readiness.',
    ];
}

add_action( 'wp_head', 'bi_output_meta_description', 1 );
function bi_output_meta_description() {
    if ( bi_seo_plugin_handles_meta() ) {
        return;
    }
    if ( ! is_page() && ! is_front_page() ) {
        return;
    }

    $slug = is_front_page() ? 'home' : get_post_field( 'post_name', get_queried_object_id() );
    $map  = bi_page_meta_descriptions();
    $desc = $map[ $slug ] ?? get_bloginfo( 'description' );

    if ( $desc ) {
        echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
    }
}

/**
 * Defer meta description when a major SEO plugin is active.
 */
function bi_seo_plugin_handles_meta() {
    return defined( 'WPSEO_VERSION' )
        || defined( 'RANK_MATH_VERSION' )
        || defined( 'AIOSEO_VERSION' )
        || class_exists( 'The_SEO_Framework\Load', false );
}

add_action( 'wp_head', 'bi_output_schema', 20 );
function bi_output_schema() {
    if ( is_front_page() || is_page( 'contact' ) ) {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'EducationalOrganization',
            'name'        => get_bloginfo( 'name' ),
            'url'         => home_url( '/' ),
            'email'       => bi_get_support_email(),
            'telephone'   => bi_get_phone(),
            'areaServed'  => [
                [ '@type' => 'Country', 'name' => 'South Africa' ],
                [ '@type' => 'City', 'name' => 'Johannesburg' ],
            ],
            'description' => bi_page_meta_descriptions()['home'],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }

    if ( is_page( 'pricing' ) ) {
        $online = bi_format_rate( 'rate_online', 320 );
        bi_output_faq_schema( [
            [ 'How much does online tutoring cost?', sprintf( 'Online tutoring is %s per hour for 1–3 month commitments.', $online ) ],
            [ 'How do parents pay?', 'Parents pay NextGen Tutors directly. The platform pays tutors. One invoice. No cash handling.' ],
            [ 'What packages are available?', 'Families can choose 4, 8, or 12+ lessons per month depending on support frequency.' ],
        ] );
    }

    if ( is_page( 'tutor-vetting' ) ) {
        bi_output_faq_schema( [
            [ 'How are tutors vetted?', 'Every tutor goes through profile submission, subject review, qualification checks, location review, manual approval, and ongoing monitoring.' ],
            [ 'Can parents trust matched tutors?', 'We aim to match learners with tutors who are patient, prepared, and committed to helping students grow in confidence.' ],
        ] );
    }
}

function bi_output_faq_schema( $items ) {
    $entities = [];
    foreach ( $items as $item ) {
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $item[0],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $item[1],
            ],
        ];
    }
    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
}
