<?php
/**
 * Template tags (split from inc/template-tags.php).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve real platform metrics for public stats blocks.
 *
 * @return array<string, mixed>
 */
function bi_real_platform_metrics() {
    $cache_key = 'bi_real_platform_metrics_v1';
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $metrics = [
        'total_students'       => count( get_users( [ 'role' => 'student', 'fields' => 'ID' ] ) ),
        'total_tutors'         => count( get_users( [ 'role' => 'tutor', 'fields' => 'ID' ] ) ),
        'average_tutor_rating' => 0,
        'provinces_served'     => count( bi_provinces() ),
        'source'               => 'fallback',
    ];

    if ( class_exists( 'NGC_Platform_Analytics' ) ) {
        $snapshot = NGC_Platform_Analytics::snapshot();
        if ( is_array( $snapshot ) ) {
            $metrics['total_students']       = (int) ( $snapshot['total_students'] ?? $metrics['total_students'] );
            $metrics['total_tutors']         = (int) ( $snapshot['total_tutors'] ?? $metrics['total_tutors'] );
            $metrics['average_tutor_rating'] = (float) ( $snapshot['average_tutor_rating'] ?? 0 );
            $metrics['source']               = 'real';
        }
    }

    set_transient( $cache_key, $metrics, MINUTE_IN_SECONDS );
    return $metrics;
}

/**
 * Build hero/about stat cards from real data.
 *
 * @return array<int, array<string, mixed>>
 */
function bi_real_stat_cards() {
    $metrics = bi_real_platform_metrics();
    return [
        [
            'count'  => (int) $metrics['total_students'],
            'suffix' => $metrics['total_students'] > 0 ? '+' : '',
            'label'  => __( 'Learners linked', 'beyondinfinity' ),
        ],
        [
            'count'  => (int) $metrics['total_tutors'],
            'suffix' => $metrics['total_tutors'] > 0 ? '+' : '',
            'label'  => __( 'Vetted tutors', 'beyondinfinity' ),
        ],
        [
            'count'  => (float) $metrics['average_tutor_rating'],
            'suffix' => $metrics['average_tutor_rating'] > 0 ? '/5' : '',
            'label'  => __( 'Average tutor rating', 'beyondinfinity' ),
        ],
        [
            'count'  => (int) $metrics['provinces_served'],
            'suffix' => '',
            'label'  => __( 'Provinces served', 'beyondinfinity' ),
        ],
    ];
}

/**
 * Parse numeric value from formatted rate text (e.g. "R320/hr").
 *
 * @param string $rate Rate text.
 * @return float
 */
function bi_rate_to_number( $rate ) {
    $clean = preg_replace( '/[^0-9\.]/', '', (string) $rate );
    return $clean ? (float) $clean : 0.0;
}

/**
 * Shared marketing/onboarding KPIs from real platform sources.
 *
 * @return array<string, mixed>
 */
function bi_real_marketing_kpis() {
    $metrics = bi_real_platform_metrics();
    $kpis    = [
        'average_rating'          => $metrics['average_tutor_rating'] > 0 ? number_format( (float) $metrics['average_tutor_rating'], 1 ) . '★' : __( 'EMPTY STATE', 'beyondinfinity' ),
        'satisfaction'            => __( 'EMPTY STATE', 'beyondinfinity' ),
        'first_booking_window'    => '48h',
        'acceptance_rate'         => __( 'EMPTY STATE', 'beyondinfinity' ),
        'credential_accuracy'     => __( 'EMPTY STATE', 'beyondinfinity' ),
        'onboarding_total'        => (string) ( (int) $metrics['total_tutors'] + (int) $metrics['total_students'] ),
        'onboarding_completion'   => __( 'EMPTY STATE', 'beyondinfinity' ),
        'onboarding_overdue'      => __( 'EMPTY STATE', 'beyondinfinity' ),
        'onboarding_certified'    => (string) (int) $metrics['total_tutors'],
        'top_monthly_earnings'    => (float) ( class_exists( 'NGC_Platform_Analytics' ) ? ( NGC_Platform_Analytics::snapshot()['tutor_payouts'] ?? 0 ) : 0 ),
    ];

    if ( class_exists( 'NGC_Platform_Repository' ) ) {
        $all_apps  = (int) NGC_Platform_Repository::count( 'audit', [ 'object_type' => 'tutor_application' ] );
        $approved  = (int) NGC_Platform_Repository::count( 'audit', [ 'action' => 'tutor_approved' ] );
        if ( $all_apps > 0 ) {
            $kpis['acceptance_rate'] = round( ( $approved / $all_apps ) * 100 ) . '%';
        }

        $conv_total = (int) NGC_Platform_Repository::count( 'conversions' );
        if ( $conv_total > 0 ) {
            $paid = (int) NGC_Platform_Repository::count( 'conversions', [ 'event_key' => 'payment_completed' ] );
            $kpis['satisfaction'] = round( ( $paid / $conv_total ) * 100 ) . '%';
        }

        $profiles = NGC_Platform_Repository::list( 'user_profiles', [ 'limit' => 500 ] );
        if ( ! empty( $profiles ) ) {
            $complete = 0;
            $overdue  = 0;
            foreach ( $profiles as $profile ) {
                $pct = (int) ( $profile['profile_completeness'] ?? 0 );
                if ( $pct >= 80 ) {
                    ++$complete;
                }
                if ( $pct > 0 && $pct < 50 ) {
                    ++$overdue;
                }
            }
            $kpis['onboarding_completion'] = round( ( $complete / count( $profiles ) ) * 100 ) . '%';
            $kpis['onboarding_overdue']    = (string) $overdue;
        }
    }

    return $kpis;
}

/**
 * Policy/SLA labels (configurable, non-analytic values).
 *
 * @return array<string, string>
 */
function bi_policy_sla_labels() {
    return [
        'claim_window'        => (string) bi_get_theme_option( 'bi_claim_window_label', '24h' ),
        'rematch_window'      => (string) bi_get_theme_option( 'bi_rematch_window_label', '48h' ),
        'first_booking_target'=> (string) bi_get_theme_option( 'bi_first_booking_label', '48h' ),
        'background_refresh'  => (string) bi_get_theme_option( 'bi_background_refresh_label', '24mo' ),
    ];
}
