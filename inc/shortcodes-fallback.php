<?php
/**
 * ngc_* shortcode fallbacks — registered only when nextgencompanion is inactive.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode tags the theme requires (must match companion registry).
 *
 * @return string[]
 */
function bi_required_ngc_shortcodes() {
    return [
        'ngc_find_tutor_form',
        'ngc_become_tutor_form',
        'ngc_contact_support_form',
        'ngc_parent_register_child_form',
        'ngc_student_register_form',
        'ngc_login_form',
        'ngc_forgot_password_form',
        'ngc_parent_dashboard',
        'ngc_student_dashboard',
        'ngc_tutor_dashboard',
        'ngc_admin_dashboard',
    ];
}

add_action( 'init', 'bi_register_ngc_shortcode_fallbacks', 99 );

/**
 * Register theme-owned fallbacks when companion did not register shortcodes.
 */
function bi_register_ngc_shortcode_fallbacks() {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return;
    }

    $map = [
        'ngc_find_tutor_form'            => 'bi_ngc_form_find_tutor',
        'ngc_become_tutor_form'          => 'bi_ngc_form_become_tutor',
        'ngc_contact_support_form'       => 'bi_ngc_form_contact_support',
        'ngc_parent_register_child_form' => 'bi_ngc_form_parent_register',
        'ngc_student_register_form'      => 'bi_ngc_form_student_register',
        'ngc_login_form'                 => 'bi_ngc_form_login',
        'ngc_forgot_password_form'       => 'bi_ngc_form_forgot_password',
        'ngc_parent_dashboard'           => 'bi_ngc_dashboard_shortcode',
        'ngc_student_dashboard'          => 'bi_ngc_dashboard_shortcode',
        'ngc_tutor_dashboard'            => 'bi_ngc_dashboard_shortcode',
        'ngc_admin_dashboard'            => 'bi_ngc_dashboard_shortcode',
    ];

    foreach ( $map as $tag => $callback ) {
        if ( ! shortcode_exists( $tag ) && is_callable( $callback ) ) {
            add_shortcode( $tag, $callback );
        }
    }

    if ( ! has_action( 'admin_post_nopriv_ngc_form_submit', 'bi_ngc_handle_form_submit' ) ) {
        add_action( 'admin_post_nopriv_ngc_form_submit', 'bi_ngc_handle_form_submit' );
        add_action( 'admin_post_ngc_form_submit', 'bi_ngc_handle_form_submit' );
    }
}

/**
 * @param string               $form_id Form key.
 * @param string               $title   Heading.
 * @param array<int, array<string, mixed>> $fields  Fields.
 * @param array<string, string>            $values  Pre-filled field values keyed by name.
 * @return string
 */
function bi_ngc_field_validate_rules( $field ) {
	$rules = [];
	if ( ! empty( $field['validate'] ) ) {
		return (string) $field['validate'];
	}
	if ( ! empty( $field['required'] ) ) {
		$rules[] = 'required';
	}
	$type = $field['type'] ?? 'text';
	if ( 'email' === $type ) {
		$rules[] = 'email';
	}
	if ( 'tel' === $type ) {
		$rules[] = 'sa-phone';
	}
	if ( 'textarea' === $type && ! empty( $field['required'] ) ) {
		$rules[] = 'min-length:10';
	} elseif ( ! empty( $field['required'] ) && 'select' !== $type ) {
		$rules[] = 'min-length:2';
	}
	$rules[] = 'no-script';
	return implode( '|', array_unique( array_filter( $rules ) ) );
}

function bi_ngc_render_form( $form_id, $title, $fields, $values = [] ) {
    ob_start();
    ?>
    <form class="ngc-form ngt-form bi-ngc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
      <input type="hidden" name="action" value="ngc_form_submit" />
      <input type="hidden" name="ngc_form_id" value="<?php echo esc_attr( $form_id ); ?>" />
      <?php wp_nonce_field( 'ngc_form_' . $form_id, 'ngc_form_nonce' ); ?>
      <?php if ( $title ) : ?>
        <h3 class="ngc-form__title"><?php echo esc_html( $title ); ?></h3>
      <?php endif; ?>
      <?php foreach ( $fields as $field ) :
          $fname = $field['name'] ?? '';
          $fval  = isset( $values[ $fname ] ) ? (string) $values[ $fname ] : '';
          $rules = bi_ngc_field_validate_rules( $field );
          if ( 'hidden' === ( $field['type'] ?? '' ) ) :
              ?>
        <input type="hidden" name="<?php echo esc_attr( $fname ); ?>" value="<?php echo esc_attr( $fval ); ?>" <?php echo ! empty( $field['id'] ) ? 'id="' . esc_attr( $field['id'] ) . '"' : ''; ?> />
              <?php
              continue;
          endif;
          if ( 'notice' === ( $field['type'] ?? '' ) ) :
              ?>
        <p class="ngc-form-notice bi-ngc-form__notice" data-bi-intake-notice><?php echo esc_html( (string) ( $field['label'] ?? '' ) ); ?></p>
              <?php
              continue;
          endif;
          ?>
        <div class="ngc-field-group ngt-form-group">
          <label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
          <?php if ( 'textarea' === ( $field['type'] ?? 'text' ) ) : ?>
            <textarea id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $fname ); ?>" rows="<?php echo esc_attr( (string) ( $field['rows'] ?? 4 ) ); ?>" class="ngc-wysiwyg" <?php echo $rules ? 'data-validate="' . esc_attr( $rules ) . '"' : ''; ?> <?php echo ! empty( $field['required'] ) ? 'required aria-required="true"' : ''; ?>><?php echo esc_textarea( $fval ); ?></textarea>
          <?php elseif ( 'select' === ( $field['type'] ?? '' ) ) : ?>
            <select id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $fname ); ?>" <?php echo $rules ? 'data-validate="' . esc_attr( $rules ) . '"' : ''; ?> <?php echo ! empty( $field['required'] ) ? 'required aria-required="true"' : ''; ?>>
              <?php foreach ( (array) ( $field['options'] ?? [] ) as $value => $label ) : ?>
                <option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $fval, (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
              <?php endforeach; ?>
            </select>
          <?php elseif ( 'checkbox' === ( $field['type'] ?? '' ) ) : ?>
            <label class="bi-ngc-form__check" for="<?php echo esc_attr( $field['id'] ); ?>" style="display:flex;gap:10px;align-items:flex-start;font-weight:400">
              <input type="checkbox" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $fname ); ?>" value="1" <?php checked( $fval, '1' ); ?> <?php echo $rules ? 'data-validate="' . esc_attr( $rules ) . '"' : ''; ?> <?php echo ! empty( $field['required'] ) ? 'required aria-required="true"' : ''; ?> />
              <span><?php echo esc_html( (string) ( $field['check_label'] ?? $field['label'] ) ); ?></span>
            </label>
          <?php else : ?>
            <input type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $fname ); ?>" value="<?php echo esc_attr( $fval ); ?>" <?php echo $rules ? 'data-validate="' . esc_attr( $rules ) . '"' : ''; ?> <?php echo ! empty( $field['required'] ) ? 'required aria-required="true"' : ''; ?> />
          <?php endif; ?>
          <span class="ngc-field-error" aria-live="polite"></span>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Submit', 'beyondinfinity' ); ?></button>
    </form>
    <?php
    return (string) ob_get_clean();
}

/** @return string */
function bi_ngc_form_find_tutor() {
    $values = [];
    $notes  = [];
    $lead   = [];

    if ( function_exists( 'bi_get_search_query_arg' ) ) {
        $subject = bi_get_search_query_arg( 'subject' );
        if ( $subject && function_exists( 'bi_subject_label_from_slug' ) ) {
            $values['subject'] = bi_subject_label_from_slug( $subject );
        }
        $location = bi_get_search_query_arg( 'location' );
        if ( $location ) {
            $notes[] = sprintf(
                /* translators: %s: city or suburb */
                __( 'Preferred area: %s', 'beyondinfinity' ),
                $location
            );
        }
    }

    // "Book Session" carries the chosen tutor (and slot) into the match request.
    $tutor = function_exists( 'bi_get_requested_tutor_context' ) ? bi_get_requested_tutor_context() : [];
    if ( ! empty( $tutor['id'] ) ) {
        $slot_label = function_exists( 'bi_format_tutor_slot_label' ) ? bi_format_tutor_slot_label( $tutor['slot'] ) : '';

        $lead[] = [
            'type'  => 'notice',
            'label' => $slot_label
                ? sprintf(
                    /* translators: 1: tutor name, 2: slot summary */
                    __( 'Booking request for %1$s — %2$s. Confirm your details and we will secure this session.', 'beyondinfinity' ),
                    $tutor['name'],
                    $slot_label
                )
                : sprintf(
                    /* translators: %s: tutor name */
                    __( 'Booking request for %s. Confirm your details and we will secure this session.', 'beyondinfinity' ),
                    $tutor['name']
                ),
        ];
        $lead[] = [ 'type' => 'hidden', 'id' => 'bi-ngc-preferred-tutor-id', 'name' => 'preferred_tutor_id' ];
        $lead[] = [ 'type' => 'hidden', 'id' => 'bi-ngc-preferred-tutor', 'name' => 'preferred_tutor' ];

        $values['preferred_tutor_id'] = (string) $tutor['id'];
        $values['preferred_tutor']    = (string) $tutor['name'];

        if ( ! empty( $tutor['slot']['subject'] ) && empty( $values['subject'] ) ) {
            $values['subject'] = (string) $tutor['slot']['subject'];
        }
        if ( $slot_label ) {
            $lead[] = [ 'type' => 'hidden', 'id' => 'bi-ngc-preferred-slot', 'name' => 'preferred_slot' ];
            $values['preferred_slot'] = $slot_label;
            $notes[] = sprintf(
                /* translators: 1: tutor name, 2: slot summary */
                __( 'Requested tutor: %1$s · Requested time: %2$s', 'beyondinfinity' ),
                $tutor['name'],
                $slot_label
            );
        } else {
            $notes[] = sprintf(
                /* translators: %s: tutor name */
                __( 'Requested tutor: %s', 'beyondinfinity' ),
                $tutor['name']
            );
        }
    }

    if ( $notes ) {
        $values['notes'] = implode( "\n", $notes );
    }

    // Schema aligned with IMPORTANT/find-tutor-form.json (parent intake + POPIA).
    $grade_opts = [
        ''          => __( 'Select…', 'beyondinfinity' ),
        'Primary (R-7)' => __( 'Primary (R-7)', 'beyondinfinity' ),
        'High School (8-12)' => __( 'High School (8-12)', 'beyondinfinity' ),
        'Tertiary'  => __( 'Tertiary', 'beyondinfinity' ),
    ];
    $subject_opts = [
        ''                   => __( 'Select…', 'beyondinfinity' ),
        'Mathematics'        => __( 'Mathematics', 'beyondinfinity' ),
        'Physical Science'   => __( 'Physical Science', 'beyondinfinity' ),
        'Accounting'         => __( 'Accounting', 'beyondinfinity' ),
        'English'            => __( 'English', 'beyondinfinity' ),
        'Life Sciences'      => __( 'Life Sciences', 'beyondinfinity' ),
        'Tertiary Support'   => __( 'Tertiary Support', 'beyondinfinity' ),
    ];
    $province_opts = [
        ''               => __( 'Select…', 'beyondinfinity' ),
        'Gauteng'        => __( 'Gauteng', 'beyondinfinity' ),
        'Western Cape'   => __( 'Western Cape', 'beyondinfinity' ),
        'KZN'            => __( 'KZN', 'beyondinfinity' ),
        'Eastern Cape'   => __( 'Eastern Cape', 'beyondinfinity' ),
        'Free State'     => __( 'Free State', 'beyondinfinity' ),
        'Limpopo'        => __( 'Limpopo', 'beyondinfinity' ),
        'Mpumalanga'     => __( 'Mpumalanga', 'beyondinfinity' ),
        'North West'     => __( 'North West', 'beyondinfinity' ),
        'Northern Cape'  => __( 'Northern Cape', 'beyondinfinity' ),
    ];

    return bi_ngc_render_form( 'find_tutor', '', array_merge( $lead, [
        [ 'id' => 'bi-ngc-parent-name', 'name' => 'parent_name', 'label' => __( 'Parent / guardian name', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-phone', 'name' => 'phone', 'type' => 'tel', 'label' => __( 'WhatsApp / phone', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-grade', 'name' => 'grade', 'type' => 'select', 'label' => __( 'Learner grade', 'beyondinfinity' ), 'options' => $grade_opts, 'required' => true ],
        [ 'id' => 'bi-ngc-subject', 'name' => 'subject', 'type' => 'select', 'label' => __( 'Subject needed', 'beyondinfinity' ), 'options' => $subject_opts, 'required' => true ],
        [ 'id' => 'bi-ngc-province', 'name' => 'province', 'type' => 'select', 'label' => __( 'Province', 'beyondinfinity' ), 'options' => $province_opts, 'required' => true ],
        [ 'id' => 'bi-ngc-notes', 'name' => 'notes', 'type' => 'textarea', 'label' => __( 'Additional details', 'beyondinfinity' ) ],
        [
            'id'           => 'bi-ngc-popia',
            'name'         => 'popia_consent',
            'type'         => 'checkbox',
            'label'        => __( 'POPIA consent', 'beyondinfinity' ),
            'check_label'  => __( 'I consent to data processing per POPIA.', 'beyondinfinity' ),
            'required'     => true,
        ],
    ] ), $values );
}

/** @return string */
function bi_ngc_form_become_tutor() {
    return bi_ngc_render_form( 'become_tutor', '', [
        [ 'id' => 'bi-ngc-t-name', 'name' => 'full_name', 'label' => __( 'Full name', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-t-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-t-phone', 'name' => 'phone', 'type' => 'tel', 'label' => __( 'Phone', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-t-subjects', 'name' => 'subjects', 'label' => __( 'Subjects you teach', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-t-exp', 'name' => 'experience', 'type' => 'textarea', 'label' => __( 'Teaching experience', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-t-province', 'name' => 'province', 'label' => __( 'Province', 'beyondinfinity' ), 'required' => true ],
    ] );
}

/** @return string */
function bi_ngc_form_contact_support() {
    return bi_ngc_render_form( 'contact_support', '', [
        [ 'id' => 'bi-ngc-s-name', 'name' => 'name', 'label' => __( 'Your name', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-s-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-s-topic', 'name' => 'topic', 'type' => 'select', 'label' => __( 'Topic', 'beyondinfinity' ), 'options' => [ 'general' => __( 'General', 'beyondinfinity' ), 'billing' => __( 'Billing', 'beyondinfinity' ), 'safety' => __( 'Safety', 'beyondinfinity' ) ], 'required' => true ],
        [ 'id' => 'bi-ngc-s-message', 'name' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'beyondinfinity' ), 'required' => true ],
    ] );
}

/** @return string */
function bi_ngc_form_parent_register() {
    return bi_ngc_render_form( 'parent_register', __( 'Register a learner (parent / guardian)', 'beyondinfinity' ), [
        [ 'id' => 'bi-ngc-p-name', 'name' => 'parent_name', 'label' => __( 'Your name', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-p-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-p-child', 'name' => 'child_name', 'label' => __( 'Learner name', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-p-grade', 'name' => 'grade', 'label' => __( 'Grade', 'beyondinfinity' ), 'required' => true ],
    ] );
}

/** @return string */
function bi_ngc_form_student_register() {
    return bi_ngc_render_form( 'student_register', __( 'Student self-registration', 'beyondinfinity' ), [
        [ 'id' => 'bi-ngc-st-name', 'name' => 'full_name', 'label' => __( 'Full name', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-st-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'beyondinfinity' ), 'required' => true ],
        [ 'id' => 'bi-ngc-st-grade', 'name' => 'grade', 'label' => __( 'Grade / year', 'beyondinfinity' ), 'required' => true ],
    ] );
}

/** @return string */
function bi_ngc_form_login() {
    if ( is_user_logged_in() ) {
        return '<p class="ngc-form-notice">' . esc_html__( 'You are already signed in.', 'beyondinfinity' ) . '</p>';
    }

    $role = sanitize_key( $_GET['role'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! in_array( $role, [ 'parent', 'student', 'tutor' ], true ) ) {
        $role = 'student';
    }

    $redirect = function_exists( 'bi_role_dashboard_url' )
        ? bi_role_dashboard_url( $role )
        : home_url( '/student-dashboard' );

    if ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $redirect = bi_validate_internal_redirect( wp_unslash( $_GET['redirect_to'] ), $redirect ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    ob_start();
    wp_login_form( [
        'echo'           => true,
        'redirect'       => $redirect,
        'form_id'        => 'ngc-loginform',
        'label_username' => __( 'Email or username', 'beyondinfinity' ),
        'label_password' => __( 'Password', 'beyondinfinity' ),
        'label_remember' => __( 'Remember me', 'beyondinfinity' ),
        'label_log_in'   => __( 'Sign in', 'beyondinfinity' ),
    ] );
    return '<div class="ngc-form ngc-form--login" data-bi-login-role="' . esc_attr( $role ) . '">' . ob_get_clean() . '</div>';
}

/** @return string */
function bi_ngc_form_forgot_password() {
    if ( is_user_logged_in() ) {
        return '';
    }
    return '<p class="ngc-form-forgot"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html__( 'Forgot your password?', 'beyondinfinity' ) . '</a></p>';
}

/**
 * Dashboard shortcodes (attribute type=parent|student|tutor|admin).
 *
 * @param array<string, string>|string $atts Attributes.
 * @param string                       $content Content.
 * @param string                       $tag Shortcode tag.
 * @return string
 */
function bi_ngc_dashboard_shortcode( $atts, $content = '', $tag = '' ) {
    $type_map = [
        'ngc_parent_dashboard'  => 'parent',
        'ngc_student_dashboard' => 'student',
        'ngc_tutor_dashboard'   => 'tutor',
        'ngc_admin_dashboard'   => 'admin',
    ];
    $type = $type_map[ $tag ] ?? 'student';

    if ( ! is_user_logged_in() ) {
        $login = add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), home_url( '/login' ) );
        return '<p class="ngc-dashboard-notice"><a href="' . esc_url( $login ) . '">' . esc_html__( 'Sign in to view your dashboard.', 'beyondinfinity' ) . '</a></p>';
    }

    bi_enqueue_dashboard_rest_for_type( $type );

    ob_start();
    ?>
    <div class="bi-dashboard-rest ngt-card ngt-animate" data-dashboard="<?php echo esc_attr( $type ); ?>" role="region" aria-live="polite" aria-busy="true">
      <p class="bi-dashboard-rest__loading"><?php esc_html_e( 'Loading your dashboard…', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Form POST handler (theme fallback when companion inactive).
 */
function bi_ngc_handle_form_submit() {
    $form_id = isset( $_POST['ngc_form_id'] ) ? sanitize_key( wp_unslash( $_POST['ngc_form_id'] ) ) : '';
    if ( ! $form_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ngc_form_nonce'] ?? '' ) ), 'ngc_form_' . $form_id ) ) {
        wp_die( esc_html__( 'Invalid form submission.', 'beyondinfinity' ), 403 );
    }

    $payload = [];
    foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( in_array( $key, [ 'action', 'ngc_form_id', 'ngc_form_nonce', '_wp_http_referer' ], true ) ) {
            continue;
        }
        $payload[ sanitize_key( $key ) ] = is_array( $value )
            ? array_map( 'sanitize_text_field', wp_unslash( $value ) )
            : sanitize_textarea_field( wp_unslash( $value ) );
    }

    $queue = get_option( 'ngc_form_queue', [] );
    if ( ! is_array( $queue ) ) {
        $queue = [];
    }
    $queue[] = [
        'form'    => $form_id,
        'data'    => $payload,
        'source'  => 'theme-fallback',
        'created' => gmdate( 'c' ),
    ];
    update_option( 'ngc_form_queue', array_slice( $queue, -100 ), false );

    add_action(
        'shutdown',
        static function () use ( $form_id, $payload ) {
            if ( function_exists( 'do_action' ) ) {
                do_action( 'ngc_form_submitted', $form_id, $payload );
            }

            wp_mail(
                get_option( 'admin_email' ),
                sprintf( '[NextGen] %s submission', $form_id ),
                wp_json_encode( $payload, JSON_PRETTY_PRINT )
            );

            if ( function_exists( 'bi_openwa_notify_form_submission' ) ) {
                bi_openwa_notify_form_submission( $form_id, $payload );
            }

            do_action( 'bi_form_submitted', $form_id, $payload );
        },
        1
    );

    $redirect_map = [
        'find_tutor'       => home_url( '/thank-you/?type=parent' ),
        'become_tutor'     => home_url( '/thank-you/?type=tutor' ),
        'contact_support'  => home_url( '/thank-you/?type=contact' ),
        'parent_register'  => home_url( '/thank-you/?type=parent' ),
        'student_register' => home_url( '/thank-you/?type=general' ),
    ];
    $redirect = $redirect_map[ $form_id ] ?? home_url( '/thank-you/' );
    $redirect = apply_filters( 'ngc_form_redirect_url', $redirect, $form_id );

    wp_safe_redirect( add_query_arg( 'ngc_submitted', $form_id, $redirect ) );
    exit;
}

/**
 * @return array{ok: bool, missing: string[]}
 */
if ( ! function_exists( 'bi_ngc_shortcode_health' ) ) {
	function bi_ngc_shortcode_health() {
		$missing = [];
		foreach ( bi_required_ngc_shortcodes() as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				$missing[] = $tag;
			}
		}
		return [
			'ok'      => empty( $missing ),
			'missing' => $missing,
		];
	}
}
