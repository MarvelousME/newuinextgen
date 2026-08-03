<?php
/**
 * Declarative theme options schema.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', 'bi_options_create', 2 );
function bi_options_create() {
    $schema = [
        'bi_panel_brand' => [
            'title' => __( 'Brand & Colors', 'beyondinfinity' ),
            'desc'  => __( 'Color scheme and visual identity.', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_colors' => [
            'title' => __( 'Color scheme', 'beyondinfinity' ),
            'desc'  => __( 'Switch palette tokens used across the theme.', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_brand',
        ],
        'color_scheme' => [
            'title'   => __( 'Scheme', 'beyondinfinity' ),
            'desc'    => __( 'Applies to CSS custom properties (--ngt-*).', 'beyondinfinity' ),
            'std'     => 'default',
            'options' => bi_get_list_color_schemes(),
            'type'    => 'select',
            'section' => 'bi_section_colors',
        ],
        'visual_preset' => [
            'title'   => __( 'Visual style (skin)', 'beyondinfinity' ),
            'desc'    => __( 'Swaps token layers only — component CSS is never overwritten.', 'beyondinfinity' ),
            'std'     => 'beyond-infinity',
            'options' => bi_get_list_visual_presets(),
            'type'    => 'select',
            'section' => 'bi_section_colors',
        ],
        'theme_switcher_enabled' => [
            'title'   => __( 'Show style switcher widget', 'beyondinfinity' ),
            'desc'    => __( 'Floating preview switcher for admins (or all visitors).', 'beyondinfinity' ),
            'std'     => 0,
            'type'    => 'checkbox',
            'section' => 'bi_section_colors',
        ],
        'theme_switcher_visibility' => [
            'title'   => __( 'Switcher visibility', 'beyondinfinity' ),
            'std'     => 'admins',
            'options' => bi_get_list_theme_switcher_visibility(),
            'type'    => 'select',
            'section' => 'bi_section_colors',
        ],
        'bi_panel_contact' => [
            'title' => __( 'Contact Details', 'beyondinfinity' ),
            'desc'  => __( 'Phone, email, WhatsApp and service area.', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_contact' => [
            'title' => __( 'Contact', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_contact',
        ],
        'bi_phone' => [
            'title'   => __( 'Phone', 'beyondinfinity' ),
            'std'     => '081 334 0625',
            'type'    => 'text',
            'section' => 'bi_section_contact',
        ],
        'bi_email' => [
            'title'   => __( 'Admin email', 'beyondinfinity' ),
            'std'     => 'admin@nextgentutors.co.za',
            'type'    => 'text',
            'section' => 'bi_section_contact',
        ],
        'bi_support_email' => [
            'title'   => __( 'Support email', 'beyondinfinity' ),
            'std'     => 'support@nextgentutors.co.za',
            'type'    => 'text',
            'section' => 'bi_section_contact',
        ],
        'bi_whatsapp' => [
            'title'   => __( 'WhatsApp number (digits only)', 'beyondinfinity' ),
            'std'     => '27813340625',
            'type'    => 'text',
            'section' => 'bi_section_contact',
        ],
        'bi_service_area' => [
            'title'   => __( 'Service area text', 'beyondinfinity' ),
            'std'     => 'Johannesburg launch, online support nationwide',
            'type'    => 'text',
            'section' => 'bi_section_contact',
        ],
        'bi_whatsapp_message' => [
            'title'   => __( 'Default WhatsApp message', 'beyondinfinity' ),
            'std'     => 'Hi NextGen Tutors, I need help finding a tutor.',
            'type'    => 'text',
            'section' => 'bi_section_contact',
        ],
        'bi_show_whatsapp' => [
            'title'   => __( 'Show WhatsApp FAB', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_contact',
        ],
        'bi_response_hours' => [
            'title'   => __( 'Expected response time (hours)', 'beyondinfinity' ),
            'desc'    => __( 'Shown on thank-you page.', 'beyondinfinity' ),
            'std'     => 24,
            'type'    => 'number',
            'section' => 'bi_section_contact',
        ],
        'bi_panel_layout' => [
            'title' => __( 'Layout', 'beyondinfinity' ),
            'desc'  => __( 'Header, footer and page width.', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_layout' => [
            'title' => __( 'Global layout', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_layout',
        ],
        'header_style' => [
            'title'    => __( 'Header style', 'beyondinfinity' ),
            'std'      => 'transparent',
            'options'  => bi_get_list_header_styles(),
            'type'     => 'select',
            'section'  => 'bi_section_layout',
            'override' => true,
        ],
        'footer_style' => [
            'title'    => __( 'Footer style', 'beyondinfinity' ),
            'std'      => 'default',
            'options'  => bi_get_list_footer_styles(),
            'type'     => 'select',
            'section'  => 'bi_section_layout',
            'override' => true,
        ],
        'body_style' => [
            'title'    => __( 'Body width', 'beyondinfinity' ),
            'std'      => 'wide',
            'options'  => bi_get_list_body_styles(),
            'type'     => 'select',
            'section'  => 'bi_section_layout',
            'override' => true,
        ],
        'show_page_title' => [
            'title'    => __( 'Show page title', 'beyondinfinity' ),
            'std'      => 1,
            'type'     => 'checkbox',
            'section'  => 'bi_section_layout',
            'override' => true,
        ],
        'force_theme_default' => [
            'title'    => __( 'Force theme default content', 'beyondinfinity' ),
            'desc'     => __( 'Use bundled inc/defaults even when a page builder is active.', 'beyondinfinity' ),
            'std'      => 0,
            'type'     => 'checkbox',
            'section'  => 'bi_section_layout',
            'override' => true,
        ],
        'hide_whatsapp_fab' => [
            'title'    => __( 'Hide WhatsApp FAB on this page', 'beyondinfinity' ),
            'std'      => 0,
            'type'     => 'checkbox',
            'section'  => 'bi_section_layout',
            'override' => true,
        ],
        'bi_panel_home' => [
            'title' => __( 'Homepage', 'beyondinfinity' ),
            'desc'  => __( 'Kinetic homepage sections and layout.', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_home' => [
            'title' => __( 'Homepage', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_home',
        ],
        'home_layout' => [
            'title'   => __( 'Home layout', 'beyondinfinity' ),
            'std'     => 'kinetic',
            'options' => bi_get_list_home_layouts(),
            'type'    => 'select',
            'section' => 'bi_section_home',
        ],
        'prototype_blend' => [
            'title'   => __( 'Blend prototype page bodies', 'beyondinfinity' ),
            'desc'    => __( 'Render marketing pages from prototypes/ with kinetic styling + live shortcodes.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_trust' => [
            'title'   => __( 'Trust ecosystem section', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_subjects' => [
            'title'   => __( 'Subject explorer', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_journey' => [
            'title'   => __( 'Learner journey steps', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_narrative' => [
            'title'   => __( 'Tutoring story (3D scroll imagery)', 'beyondinfinity' ),
            'desc'    => __( 'Informal online-tutoring narrative with Unsplash imagery and 3D scroll motion.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_highlights' => [
            'title'   => __( 'Platform highlights', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_proof' => [
            'title'   => __( 'Before / after proof', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_video' => [
            'title'   => __( 'Video story split', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_pathways' => [
            'title'   => __( 'Learning pathways', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_image_hover' => [
            'title'   => __( 'Image hover discovery cards', 'beyondinfinity' ),
            'desc'    => __( 'Interactive parent/tutor/online cards from home preview.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_hero_video_url' => [
            'title'   => __( 'Hero background video URL (MP4)', 'beyondinfinity' ),
            'desc'    => __( 'Optional override. Leave empty to use assets/media/videos/hero-loop.mp4 when present.', 'beyondinfinity' ),
            'std'     => '',
            'type'    => 'text',
            'section' => 'bi_section_home',
        ],
        'home_hero_right_panel' => [
            'title'   => __( 'Hero right column', 'beyondinfinity' ),
            'std'     => 'search',
            'options' => [
                'progress' => __( 'Parent dashboard preview', 'beyondinfinity' ),
                'search'   => __( 'Advanced tutor search', 'beyondinfinity' ),
            ],
            'type'    => 'select',
            'section' => 'bi_section_home',
        ],
        'home_image_hover_mode' => [
            'title'   => __( 'Default image hover mode', 'beyondinfinity' ),
            'std'     => 'shine',
            'options' => [
                'zoom'  => __( 'Zoom overlay', 'beyondinfinity' ),
                'slide' => __( 'Slide caption', 'beyondinfinity' ),
                'flip'  => __( 'Flip card', 'beyondinfinity' ),
                'blur'  => __( 'Blur focus', 'beyondinfinity' ),
                'shine' => __( 'Shine sweep', 'beyondinfinity' ),
            ],
            'type'    => 'select',
            'section' => 'bi_section_home',
        ],
        'home_section_tutors' => [
            'title'   => __( 'Featured tutors', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_pricing' => [
            'title'   => __( 'Pricing strip', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_reviews' => [
            'title'   => __( 'Testimonials', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'home_section_faq' => [
            'title'   => __( 'FAQ', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'bi_panel_tutoring' => [
            'title' => __( 'Tutoring & Rates', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_tutoring' => [
            'title' => __( 'Rates', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_tutoring',
        ],
        'rate_online' => [
            'title'   => __( 'Online rate (ZAR/hr)', 'beyondinfinity' ),
            'std'     => 320,
            'type'    => 'number',
            'section' => 'bi_section_tutoring',
        ],
        'rate_inperson' => [
            'title'   => __( 'In-person rate (ZAR/hr)', 'beyondinfinity' ),
            'std'     => 350,
            'type'    => 'number',
            'section' => 'bi_section_tutoring',
        ],
        'rate_tertiary' => [
            'title'   => __( 'Tertiary rate (ZAR/hr)', 'beyondinfinity' ),
            'std'     => 500,
            'type'    => 'number',
            'section' => 'bi_section_tutoring',
        ],
        'guarantee_code' => [
            'title'   => __( 'Guarantee reference code', 'beyondinfinity' ),
            'std'     => 'NEXTGEN100',
            'type'    => 'text',
            'section' => 'bi_section_tutoring',
        ],
        'bi_panel_integrations' => [
            'title' => __( 'Integrations', 'beyondinfinity' ),
            'desc'  => __( 'OpenWA Easy API for outbound WhatsApp and inbound webhooks.', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_openwa' => [
            'title' => __( 'OpenWA (WhatsApp API)', 'beyondinfinity' ),
            'desc'  => __( 'Connect a local @open-wa/wa-automate Easy API instance. See scripts/openwa/README.md in the theme.', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_integrations',
        ],
        'openwa_enabled' => [
            'title'   => __( 'Enable OpenWA integration', 'beyondinfinity' ),
            'std'     => 0,
            'type'    => 'checkbox',
            'section' => 'bi_section_openwa',
        ],
        'openwa_api_url' => [
            'title'   => __( 'Easy API base URL', 'beyondinfinity' ),
            'desc'    => __( 'Example: http://127.0.0.1:8080 — no trailing slash.', 'beyondinfinity' ),
            'std'     => 'http://127.0.0.1:8080',
            'type'    => 'text',
            'section' => 'bi_section_openwa',
        ],
        'openwa_api_key' => [
            'title'   => __( 'Easy API key', 'beyondinfinity' ),
            'desc'    => __( 'Same value as --api-key when starting wa-automate.', 'beyondinfinity' ),
            'std'     => '',
            'type'    => 'text',
            'section' => 'bi_section_openwa',
        ],
        'openwa_session_id' => [
            'title'   => __( 'Session ID (optional)', 'beyondinfinity' ),
            'desc'    => __( 'Required when Easy API uses session ID in the path.', 'beyondinfinity' ),
            'std'     => '',
            'type'    => 'text',
            'section' => 'bi_section_openwa',
        ],
        'openwa_webhook_secret' => [
            'title'   => __( 'Webhook secret', 'beyondinfinity' ),
            'desc'    => __( 'Sent as X-BI-Webhook-Secret on inbound webhooks. Auto-generated if empty on first save.', 'beyondinfinity' ),
            'std'     => '',
            'type'    => 'text',
            'section' => 'bi_section_openwa',
        ],
        'openwa_notify_forms' => [
            'title'   => __( 'WhatsApp admin on form submit', 'beyondinfinity' ),
            'desc'    => __( 'Send a WhatsApp message to the site WhatsApp number when theme forms are submitted.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_openwa',
        ],
        'openwa_auto_reply' => [
            'title'   => __( 'Auto-reply to inbound messages', 'beyondinfinity' ),
            'std'     => 0,
            'type'    => 'checkbox',
            'section' => 'bi_section_openwa',
        ],
        'openwa_auto_reply_text' => [
            'title'   => __( 'Auto-reply message', 'beyondinfinity' ),
            'std'     => 'Thanks for messaging NextGen Tutors. We will reply during business hours. For urgent help visit nextgentutors.co.za/contact.',
            'type'    => 'text',
            'section' => 'bi_section_openwa',
        ],
        'bi_panel_motion' => [
            'title' => __( 'Motion & Media', 'beyondinfinity' ),
            'desc'  => __( 'Framer-style animations and swappable page images.', 'beyondinfinity' ),
            'type'  => 'panel',
        ],
        'bi_section_motion' => [
            'title' => __( 'Motion effects', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_motion',
        ],
        'bi_motion_enabled' => [
            'title'   => __( 'Enable Framer-style motion', 'beyondinfinity' ),
            'desc'    => __( 'Scroll reveals, parallax heroes, hover lift on cards. Respects prefers-reduced-motion.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_motion',
        ],
        'bi_section_3d' => [
            'title' => __( '3D effects', 'beyondinfinity' ),
            'desc'  => __( 'Pointer tilt, stacked cards, and spinning tutor carousel. Respects prefers-reduced-motion.', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_motion',
        ],
        'bi_3d_enabled' => [
            'title'   => __( 'Enable 3D hover & stacks', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_3d',
        ],
        'bi_3d_carousel_spin' => [
            'title'   => __( 'Carousel glow on hover', 'beyondinfinity' ),
            'desc'    => __( 'Pulse the ambient glow behind the tutor carousel on hover. Card rotation is handled by the carousel itself.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_3d',
        ],
        'bi_3d_tilt_max' => [
            'title'   => __( 'Hover tilt intensity (degrees)', 'beyondinfinity' ),
            'std'     => 10,
            'type'    => 'text',
            'section' => 'bi_section_3d',
        ],
        'home_tutors_carousel_3d' => [
            'title'   => __( 'Homepage tutors: 3D carousel', 'beyondinfinity' ),
            'desc'    => __( 'Replace the static tutor grid with the spinning 3D carousel.', 'beyondinfinity' ),
            'std'     => 1,
            'type'    => 'checkbox',
            'section' => 'bi_section_home',
        ],
        'bi_section_images' => [
            'title' => __( 'Theme images', 'beyondinfinity' ),
            'desc'  => __( 'Paste a Media Library URL or replace files in assets/images/ (see README).', 'beyondinfinity' ),
            'type'  => 'section',
            'panel' => 'bi_panel_motion',
        ],
    ];

    foreach ( function_exists( 'bi_theme_image_registry' ) ? bi_theme_image_registry() : [] as $key => $meta ) {
        $schema[ 'bi_img_' . $key ] = [
            'title'   => $meta['title'],
            'std'     => '',
            'type'    => 'text',
            'section' => 'bi_section_images',
        ];
    }

    bi_storage_set( 'options', apply_filters( 'bi_filter_options_schema', $schema ) );
}

/**
 * @return array<string, array<string, mixed>>
 */
function bi_get_options_schema() {
    if ( ! bi_storage_isset( 'options' ) ) {
        bi_options_create();
    }
    return bi_storage_get( 'options', [] );
}

/**
 * @return array<string, array<string, mixed>>
 */
function bi_get_override_options_schema() {
    $out = [];
    foreach ( bi_get_options_schema() as $id => $opt ) {
        if ( ! empty( $opt['override'] ) && ! in_array( $opt['type'], [ 'panel', 'section', 'panel_end', 'section_end' ], true ) ) {
            $out[ $id ] = $opt;
        }
    }
    return apply_filters( 'bi_filter_override_options_schema', $out );
}
