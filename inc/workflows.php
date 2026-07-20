<?php
/**
 * Workflow pack runner — theme fallback when companion workflows plugin is inactive.
 *
 * Loads content/nextgen-workflow-pack.json and executes enabled workflows on hooks.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return array<string, mixed>|null
 */
function bi_workflow_pack() {
    static $pack = null;
    if ( null !== $pack ) {
        return $pack;
    }
    $path = BI_DIR . '/content/nextgen-workflow-pack.json';
    if ( ! file_exists( $path ) ) {
        $pack = null;
        return null;
    }
    $json = json_decode( (string) file_get_contents( $path ), true );
    $pack = is_array( $json ) ? $json : null;
    return $pack;
}

/**
 * @return array<int, array<string, mixed>>
 */
function bi_workflow_definitions() {
    $pack = bi_workflow_pack();
    if ( ! $pack || empty( $pack['workflows'] ) || ! is_array( $pack['workflows'] ) ) {
        return [];
    }
    return $pack['workflows'];
}

/**
 * Replace {{tokens}} in workflow templates.
 *
 * @param string               $template Template string.
 * @param array<string, mixed> $vars     Variables.
 * @return string
 */
function bi_workflow_render( $template, $vars ) {
    return preg_replace_callback(
        '/\{\{(\w+)\}\}/',
        static function ( $m ) use ( $vars ) {
            $key = $m[1];
            if ( ! isset( $vars[ $key ] ) ) {
                return '';
            }
            $val = $vars[ $key ];
            if ( is_array( $val ) ) {
                $val = implode( ', ', $val );
            }
            return (string) $val;
        },
        (string) $template
    );
}

/**
 * Append RTM-style staff message to option queue (companion can consume later).
 *
 * @param string $room    Room slug.
 * @param string $message Message body.
 */
function bi_workflow_queue_rtm( $room, $message ) {
    $queue = get_option( 'bi_rtm_queue', [] );
    if ( ! is_array( $queue ) ) {
        $queue = [];
    }
    $queue[] = [
        'room'    => sanitize_key( $room ),
        'message' => sanitize_textarea_field( $message ),
        'created' => gmdate( 'c' ),
    ];
    update_option( 'bi_rtm_queue', array_slice( $queue, -200 ), false );

    /**
     * Fires when an RTM message is queued by the workflow pack.
     *
     * @param string $room    Room slug.
     * @param string $message Message body.
     */
    do_action( 'bi_workflow_rtm_queued', $room, $message );
}

/**
 * Log workflow event.
 *
 * @param string               $source  Source slug.
 * @param array<string, mixed> $context Context.
 */
function bi_workflow_log( $source, $context = [] ) {
    $log = get_option( 'bi_workflow_log', [] );
    if ( ! is_array( $log ) ) {
        $log = [];
    }
    $log[] = [
        'source'  => sanitize_key( $source ),
        'context' => $context,
        'created' => gmdate( 'c' ),
    ];
    update_option( 'bi_workflow_log', array_slice( $log, -200 ), false );
}

/**
 * Map workflow pack role slugs to theme-registered roles.
 *
 * @return array<string, string>
 */
function bi_workflow_role_aliases() {
    return apply_filters(
        'bi_workflow_role_aliases',
        [
            'ngt_tutor'          => 'tutor',
            'ngt_parent'         => 'parent',
            'ngt_student'        => 'student',
            'ngc_tutor'          => 'tutor',
            'ngc_parent'         => 'parent',
            'ngc_parent_guardian'=> 'parent_guardian',
            'ngc_student'        => 'student',
        ]
    );
}

/**
 * Resolve a role slug, falling back to theme aliases when pack roles are absent.
 *
 * @param string $role Role slug from workflow pack.
 * @return string
 */
function bi_workflow_resolve_role( $role ) {
    $role = sanitize_key( $role );
    if ( ! $role ) {
        return '';
    }
    if ( get_role( $role ) ) {
        return $role;
    }
    $aliases = bi_workflow_role_aliases();
    if ( isset( $aliases[ $role ] ) && get_role( $aliases[ $role ] ) ) {
        return $aliases[ $role ];
    }
    return $role;
}

/**
 * Assign a role to a user from a workflow action.
 *
 * @param int    $user_id User ID.
 * @param string $role    Role slug.
 * @return true|WP_Error
 */
function bi_workflow_apply_user_role( $user_id, $role ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return new WP_Error( 'bi_workflow_invalid_user', __( 'Invalid user ID for workflow role assignment.', 'beyondinfinity' ) );
    }

    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        return new WP_Error( 'bi_workflow_user_missing', __( 'User not found for workflow role assignment.', 'beyondinfinity' ) );
    }

    $role = bi_workflow_resolve_role( $role );
    if ( ! $role || ! get_role( $role ) ) {
        return new WP_Error( 'bi_workflow_role_missing', __( 'Workflow role is not registered.', 'beyondinfinity' ) );
    }

    $user->add_role( $role );

    if ( 'tutor' === $role ) {
        update_user_meta( $user_id, 'ngt_tutor_verified', 1 );
    }

    bi_workflow_log(
        'add_user_role',
        [
            'user_id' => $user_id,
            'role'    => $role,
        ]
    );

    /**
     * Fires after a workflow assigns a user role.
     *
     * @param int    $user_id User ID.
     * @param string $role    Resolved role slug.
     */
    do_action( 'bi_workflow_user_role_assigned', $user_id, $role );

    return true;
}

/**
 * Emit tutor-approved workflow event (admin or companion bridge).
 *
 * @param int $user_id User ID.
 * @return true|WP_Error
 */
function bi_workflow_emit_tutor_approved( $user_id ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 || ! get_user_by( 'id', $user_id ) ) {
        return new WP_Error( 'bi_workflow_invalid_user', __( 'Cannot approve tutor: user not found.', 'beyondinfinity' ) );
    }

    bi_workflow_dispatch( 'ngt.tutor.approved', [ 'user_id' => (string) $user_id ] );

    /**
     * Fires after tutor approval workflow dispatch.
     *
     * @param int $user_id User ID.
     */
    do_action( 'ngt_tutor_approved', $user_id );

    return true;
}

/**
 * Execute a single workflow action.
 *
 * @param array<string, mixed> $action Action definition.
 * @param array<string, mixed> $vars   Template variables.
 */
function bi_workflow_run_action( $action, $vars ) {
    $type = $action['type'] ?? '';
    switch ( $type ) {
        case 'log_event':
            bi_workflow_log( (string) ( $action['source'] ?? 'workflow' ), $vars );
            break;
        case 'create_rtm_message':
            bi_workflow_queue_rtm(
                (string) ( $action['room'] ?? 'staff' ),
                bi_workflow_render( (string) ( $action['message'] ?? '' ), $vars )
            );
            break;
        case 'wp_mail_admin':
            wp_mail(
                get_option( 'admin_email' ),
                bi_workflow_render( (string) ( $action['subject'] ?? '[NextGen Workflow]' ), $vars ),
                bi_workflow_render( (string) ( $action['message'] ?? '' ), $vars )
            );
            break;
        case 'add_user_role':
            $role    = bi_workflow_render( (string) ( $action['role'] ?? '' ), $vars );
            $user_id = (int) bi_workflow_render( (string) ( $action['user_id'] ?? '' ), $vars );
            bi_workflow_apply_user_role( $user_id, $role );
            break;
        default:
            /**
             * Run custom workflow action types.
             *
             * @param array<string, mixed> $action
             * @param array<string, mixed> $vars
             */
            do_action( 'bi_workflow_action_' . sanitize_key( $type ), $action, $vars );
    }
}

/**
 * Dispatch workflows for an event.
 *
 * @param string               $event Event name.
 * @param array<string, mixed> $vars  Template variables.
 */
function bi_workflow_dispatch( $event, $vars = [] ) {
    foreach ( bi_workflow_definitions() as $workflow ) {
        if ( empty( $workflow['enabled'] ) ) {
            continue;
        }
        $trigger = $workflow['trigger']['event'] ?? '';
        if ( $trigger !== $event ) {
            continue;
        }
        foreach ( (array) ( $workflow['actions'] ?? [] ) as $action ) {
            bi_workflow_run_action( $action, $vars );
        }
    }
}

/**
 * Map theme form payload keys to workflow template vars.
 *
 * @param array<string, mixed> $payload Form payload.
 * @return array<string, mixed>
 */
function bi_workflow_vars_from_payload( $payload ) {
    $map = [
        'name'       => $payload['parent_name'] ?? $payload['name'] ?? $payload['full_name'] ?? '',
        'child_name' => $payload['child_name'] ?? '',
        'email'      => $payload['email'] ?? '',
        'phone'      => $payload['phone'] ?? $payload['mobile'] ?? '',
        'grade'      => $payload['grade'] ?? '',
        'subject'    => $payload['subject'] ?? $payload['subjects'] ?? '',
        'area'       => $payload['area'] ?? $payload['province'] ?? '',
        'bio'        => $payload['bio'] ?? $payload['message'] ?? '',
        'subjects'   => $payload['subjects'] ?? $payload['subject'] ?? '',
        'summary'    => $payload['message'] ?? $payload['subject'] ?? '',
        'source'     => 'theme-form',
        'priority'   => 'normal',
    ];
    return apply_filters( 'bi_workflow_vars_from_payload', $map, $payload );
}

add_action( 'bi_form_submitted', 'bi_workflow_on_form_submitted', 10, 2 );
/**
 * @param string               $form_id Form slug.
 * @param array<string, mixed> $payload Fields.
 */
function bi_workflow_on_form_submitted( $form_id, $payload ) {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return;
    }
    $events = [
        'find_tutor'       => 'ngt.find_tutor.submitted',
        'become_tutor'     => 'ngt.tutor_application.submitted',
        'contact_support'  => 'ngt.support.escalated',
        'parent_register'  => 'ngt.parent_register.submitted',
        'student_register' => 'ngt.student_register.submitted',
    ];
    $event = $events[ $form_id ] ?? '';
    if ( ! $event ) {
        return;
    }
    $vars = bi_workflow_vars_from_payload( $payload );
    if ( 'ngt.support.escalated' === $event ) {
        $vars['summary'] = wp_json_encode( $payload, JSON_PRETTY_PRINT );
        $vars['priority'] = 'high';
    }
    bi_workflow_dispatch( $event, $vars );
}

add_action( 'user_register', 'bi_workflow_on_user_register', 20 );
/**
 * @param int $user_id User ID.
 */
function bi_workflow_on_user_register( $user_id ) {
    bi_workflow_dispatch( 'wp.user_registered', [ 'user_id' => (string) $user_id ] );
}

add_action( 'woocommerce_order_status_completed', 'bi_workflow_on_order_completed', 20 );

/**
 * When nextgencompanion is active it owns WooCommerce payment hooks and calls
 * bi_workflow_dispatch() for completed/failed/refunded events. Theme hooks
 * below remain as fallback when the companion plugin is inactive.
 */
add_action( 'woocommerce_order_status_failed', 'bi_workflow_on_order_failed', 20 );
add_action( 'woocommerce_order_status_refunded', 'bi_workflow_on_order_refunded', 20 );
add_action( 'woocommerce_order_partially_refunded', 'bi_workflow_on_order_refunded', 20 );

/**
 * @param int $order_id Order ID.
 */
function bi_workflow_on_order_completed( $order_id ) {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return;
    }
    $user_id = 0;
    if ( function_exists( 'wc_get_order' ) ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $user_id = (int) $order->get_user_id();
        }
    }
    bi_workflow_dispatch(
        'woocommerce.order.completed',
        [
            'order_id' => (string) $order_id,
            'user_id'  => (string) $user_id,
        ]
    );
}

/**
 * @param int $order_id Order ID.
 */
function bi_workflow_on_order_failed( $order_id ) {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return;
    }
    $user_id = 0;
    $amount  = '';
    if ( function_exists( 'wc_get_order' ) ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $user_id = (int) $order->get_user_id();
            $amount  = (string) $order->get_total();
        }
    }
    bi_workflow_dispatch(
        'ngt.payment.failed',
        [
            'order_id' => (string) $order_id,
            'user_id'  => (string) $user_id,
            'amount'   => $amount,
        ]
    );
}

/**
 * @param int $order_id Order ID.
 */
function bi_workflow_on_order_refunded( $order_id ) {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return;
    }
    $user_id = 0;
    $amount  = '';
    if ( function_exists( 'wc_get_order' ) ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $user_id = (int) $order->get_user_id();
            $amount  = (string) $order->get_total_refunded();
        }
    }
    bi_workflow_dispatch(
        'ngt.payment.refunded',
        [
            'order_id' => (string) $order_id,
            'user_id'  => (string) $user_id,
            'amount'   => $amount,
        ]
    );
}
