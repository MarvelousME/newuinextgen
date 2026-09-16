<?php
/**
 * Custom roles for dashboard routing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_switch_theme', 'bi_register_roles' );
function bi_register_roles() {
    if ( ! get_role( 'parent' ) ) {
        add_role( 'parent', __( 'Parent', 'beyondinfinity' ), [ 'read' => true ] );
    }
    if ( ! get_role( 'parent_guardian' ) ) {
        add_role( 'parent_guardian', __( 'Parent/Guardian', 'beyondinfinity' ), [ 'read' => true ] );
    }
    if ( ! get_role( 'tutor' ) ) {
        add_role( 'tutor', __( 'Tutor', 'beyondinfinity' ), [ 'read' => true ] );
    }
    if ( ! get_role( 'student' ) ) {
        add_role( 'student', __( 'Student', 'beyondinfinity' ), [ 'read' => true ] );
    }
}
