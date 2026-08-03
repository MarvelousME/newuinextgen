<?php
/**
 * ngc_* shortcode registration (11 tags).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes.
 */
class NGC_Shortcodes {

	/**
	 * @return string[]
	 */
	public static function required_tags() {
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
			'nextgen_tutor_calendar',
		];
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ], 20 );
		add_action( 'admin_post_nopriv_ngc_form_submit', [ __CLASS__, 'handle_form_submit' ] );
		add_action( 'admin_post_ngc_form_submit', [ __CLASS__, 'handle_form_submit' ] );
	}

	/**
	 * Register shortcodes — delegate to theme renderers when available.
	 */
	public static function register() {
		$map = [
			'ngc_find_tutor_form'            => 'find_tutor_form',
			'ngc_become_tutor_form'          => 'become_tutor_form',
			'ngc_contact_support_form'       => 'contact_support_form',
			'ngc_parent_register_child_form' => 'parent_register_form',
			'ngc_student_register_form'      => 'student_register_form',
			'ngc_login_form'                 => 'login_form',
			'ngc_forgot_password_form'       => 'forgot_password_form',
			'ngc_parent_dashboard'           => 'dashboard',
			'ngc_student_dashboard'          => 'dashboard',
			'ngc_tutor_dashboard'            => 'dashboard',
			'ngc_admin_dashboard'            => 'dashboard',
			'nextgen_tutor_calendar'         => 'tutor_calendar',
			'ngc_popia_consent'              => 'popia_consent',
		];

		foreach ( $map as $tag => $method ) {
			add_shortcode( $tag, [ __CLASS__, $method ] );
		}
	}

	/**
	 * POPIA consent block for registration / booking forms.
	 *
	 * @return string
	 */
	public static function popia_consent() {
		if ( class_exists( 'NGC_Operational_Layouts' ) ) {
			$html = NGC_Operational_Layouts::consent_form_html();
			if ( $html ) {
				return $html;
			}
		}
		return '<div class="ngt-popia-consent"><p>' . esc_html__( 'By continuing you consent to POPIA-compliant processing of your personal information for tutoring services.', 'nextgencompanion' ) . '</p></div>';
	}

	/**
	 * @return string
	 */
	public static function find_tutor_form() {
		if ( function_exists( 'bi_ngc_form_find_tutor' ) ) {
			return bi_ngc_form_find_tutor();
		}
		// Fallback mirrors IMPORTANT/find-tutor-form.json when theme helper is absent.
		return self::render_form(
			'find_tutor',
			'',
			[
				[ 'id' => 'ngc-parent-name', 'name' => 'parent_name', 'label' => __( 'Parent / guardian name', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-phone', 'name' => 'phone', 'type' => 'tel', 'label' => __( 'WhatsApp / phone', 'nextgencompanion' ), 'required' => true ],
				[
					'id'       => 'ngc-grade',
					'name'     => 'grade',
					'type'     => 'select',
					'label'    => __( 'Learner grade', 'nextgencompanion' ),
					'required' => true,
					'options'  => [
						''                   => __( 'Select…', 'nextgencompanion' ),
						'Primary (R-7)'      => __( 'Primary (R-7)', 'nextgencompanion' ),
						'High School (8-12)' => __( 'High School (8-12)', 'nextgencompanion' ),
						'Tertiary'           => __( 'Tertiary', 'nextgencompanion' ),
					],
				],
				[
					'id'       => 'ngc-subject',
					'name'     => 'subject',
					'type'     => 'select',
					'label'    => __( 'Subject needed', 'nextgencompanion' ),
					'required' => true,
					'options'  => [
						''                 => __( 'Select…', 'nextgencompanion' ),
						'Mathematics'      => __( 'Mathematics', 'nextgencompanion' ),
						'Physical Science' => __( 'Physical Science', 'nextgencompanion' ),
						'Accounting'       => __( 'Accounting', 'nextgencompanion' ),
						'English'          => __( 'English', 'nextgencompanion' ),
						'Life Sciences'    => __( 'Life Sciences', 'nextgencompanion' ),
						'Tertiary Support' => __( 'Tertiary Support', 'nextgencompanion' ),
					],
				],
				[
					'id'       => 'ngc-province',
					'name'     => 'province',
					'type'     => 'select',
					'label'    => __( 'Province', 'nextgencompanion' ),
					'required' => true,
					'options'  => [
						''              => __( 'Select…', 'nextgencompanion' ),
						'Gauteng'       => __( 'Gauteng', 'nextgencompanion' ),
						'Western Cape'  => __( 'Western Cape', 'nextgencompanion' ),
						'KZN'           => __( 'KZN', 'nextgencompanion' ),
						'Eastern Cape'  => __( 'Eastern Cape', 'nextgencompanion' ),
						'Free State'    => __( 'Free State', 'nextgencompanion' ),
						'Limpopo'       => __( 'Limpopo', 'nextgencompanion' ),
						'Mpumalanga'    => __( 'Mpumalanga', 'nextgencompanion' ),
						'North West'    => __( 'North West', 'nextgencompanion' ),
						'Northern Cape' => __( 'Northern Cape', 'nextgencompanion' ),
					],
				],
				[ 'id' => 'ngc-notes', 'name' => 'notes', 'type' => 'textarea', 'label' => __( 'Additional details', 'nextgencompanion' ) ],
				[
					'id'          => 'ngc-popia',
					'name'        => 'popia_consent',
					'type'        => 'checkbox',
					'label'       => __( 'POPIA consent', 'nextgencompanion' ),
					'check_label' => __( 'I consent to data processing per POPIA.', 'nextgencompanion' ),
					'required'    => true,
				],
			]
		);
	}

	/** @return string */
	public static function become_tutor_form() {
		if ( function_exists( 'bi_ngc_form_become_tutor' ) ) {
			return bi_ngc_form_become_tutor();
		}
		return self::render_form(
			'become_tutor',
			'',
			[
				[ 'id' => 'ngc-t-name', 'name' => 'full_name', 'label' => __( 'Full name', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-t-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-t-phone', 'name' => 'phone', 'type' => 'tel', 'label' => __( 'Phone', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-t-subjects', 'name' => 'subjects', 'label' => __( 'Subjects you teach', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-t-exp', 'name' => 'experience', 'type' => 'textarea', 'label' => __( 'Teaching experience', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-t-province', 'name' => 'province', 'label' => __( 'Province', 'nextgencompanion' ), 'required' => true ],
			]
		);
	}

	/** @return string */
	public static function contact_support_form() {
		if ( shortcode_exists( 'fluent_support_portal' ) ) {
			$mailbox_id = (int) get_option( 'ngc_fluent_support_mailbox_id', 0 );
			$sc         = '[fluent_support_portal show_logout="yes"';
			if ( $mailbox_id > 0 ) {
				$sc .= ' business_box_id="' . $mailbox_id . '"';
			}
			$sc .= ']';
			return '<div class="ngc-fluent-support-portal" data-testid="ngc-fluent-support-portal">' . do_shortcode( $sc ) . '</div>';
		}
		if ( function_exists( 'bi_ngc_form_contact_support' ) ) {
			return bi_ngc_form_contact_support();
		}
		return self::render_form(
			'contact_support',
			'',
			[
				[ 'id' => 'ngc-s-name', 'name' => 'name', 'label' => __( 'Your name', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-s-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-s-message', 'name' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'nextgencompanion' ), 'required' => true ],
			]
		);
	}

	/** @return string */
	public static function parent_register_form() {
		if ( function_exists( 'bi_ngc_form_parent_register' ) ) {
			return bi_ngc_form_parent_register();
		}
		return self::render_form(
			'parent_register',
			__( 'Register a learner (parent / guardian)', 'nextgencompanion' ),
			[
				[ 'id' => 'ngc-p-name', 'name' => 'parent_name', 'label' => __( 'Your name', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-p-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-p-child', 'name' => 'child_name', 'label' => __( 'Learner name', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-p-grade', 'name' => 'grade', 'label' => __( 'Grade', 'nextgencompanion' ), 'required' => true ],
			]
		);
	}

	/** @return string */
	public static function student_register_form() {
		if ( function_exists( 'bi_ngc_form_student_register' ) ) {
			return bi_ngc_form_student_register();
		}
		return self::render_form(
			'student_register',
			__( 'Student self-registration', 'nextgencompanion' ),
			[
				[ 'id' => 'ngc-st-name', 'name' => 'full_name', 'label' => __( 'Full name', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-st-email', 'name' => 'email', 'type' => 'email', 'label' => __( 'Email', 'nextgencompanion' ), 'required' => true ],
				[ 'id' => 'ngc-st-grade', 'name' => 'grade', 'label' => __( 'Grade / year', 'nextgencompanion' ), 'required' => true ],
			]
		);
	}

	/** @return string */
	public static function login_form() {
		if ( function_exists( 'bi_ngc_form_login' ) ) {
			return bi_ngc_form_login();
		}
		if ( is_user_logged_in() ) {
			return '<p class="ngc-form-notice">' . esc_html__( 'You are already signed in.', 'nextgencompanion' ) . '</p>';
		}

		$role = sanitize_key( $_GET['role'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $role, [ 'parent', 'student', 'tutor' ], true ) ) {
			$role = 'student';
		}

		$redirect = home_url( '/student-dashboard' );
		if ( function_exists( 'bi_role_dashboard_url' ) ) {
			$redirect = bi_role_dashboard_url( $role );
		} elseif ( 'parent' === $role ) {
			$redirect = home_url( '/parent-dashboard' );
		} elseif ( 'tutor' === $role ) {
			$redirect = home_url( '/tutor-dashboard' );
		}

		if ( ! empty( $_GET['redirect_to'] ) && function_exists( 'bi_validate_internal_redirect' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect = bi_validate_internal_redirect( wp_unslash( $_GET['redirect_to'] ), $redirect ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$candidate = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$validated = wp_validate_redirect( $candidate, $redirect );
			$redirect  = $validated ? $validated : $redirect;
		}

		ob_start();
		wp_login_form( [
			'echo'           => true,
			'redirect'       => $redirect,
			'form_id'        => 'ngc-loginform',
			'label_username' => __( 'Email or username', 'nextgencompanion' ),
			'label_password' => __( 'Password', 'nextgencompanion' ),
			'label_remember' => __( 'Remember me', 'nextgencompanion' ),
			'label_log_in'   => __( 'Sign in', 'nextgencompanion' ),
		] );
		return self::render_shell( 'ngc-form-shell ngc-form ngc-form--login ng-form', ob_get_clean() );
	}

	/** @return string */
	public static function forgot_password_form() {
		if ( function_exists( 'bi_ngc_form_forgot_password' ) ) {
			return bi_ngc_form_forgot_password();
		}
		if ( is_user_logged_in() ) {
			return '';
		}
		return '<p class="ngc-form-forgot"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html__( 'Forgot your password?', 'nextgencompanion' ) . '</a></p>';
	}

	/**
	 * Dashboard shortcodes.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @param string                       $content Content.
	 * @param string                       $tag Tag.
	 * @return string
	 */
	public static function dashboard( $atts, $content = '', $tag = '' ) {
		if ( function_exists( 'bi_ngc_dashboard_shortcode' ) ) {
			return bi_ngc_dashboard_shortcode( $atts, $content, $tag );
		}

		$type_map = [
			'ngc_parent_dashboard'  => 'parent',
			'ngc_student_dashboard' => 'student',
			'ngc_tutor_dashboard'   => 'tutor',
			'ngc_admin_dashboard'   => 'admin',
		];
		$type = $type_map[ $tag ] ?? 'student';

		if ( ! is_user_logged_in() ) {
			$login = add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), home_url( '/login' ) );
			return '<p class="ngc-dashboard-notice"><a href="' . esc_url( $login ) . '">' . esc_html__( 'Sign in to view your dashboard.', 'nextgencompanion' ) . '</a></p>';
		}

		if ( function_exists( 'bi_enqueue_dashboard_rest_for_type' ) ) {
			bi_enqueue_dashboard_rest_for_type( $type );
		} else {
			wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true );
			wp_enqueue_script(
				'ngc-dashboard-rest',
				plugins_url( 'assets/js/dashboard-rest.js', NGC_PLUGIN_FILE ),
				[ 'chart-js' ],
				NGC_VERSION,
				true
			);
			$config = function_exists( 'bi_dashboard_rest_config' )
				? bi_dashboard_rest_config( $type )
				: [
					'restRoot'  => esc_url_raw( rest_url() ),
					'namespace' => 'ngc/v1',
					'path'      => '/dashboard/' . $type,
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'type'      => $type,
					'i18n'      => [
						'loading'  => __( 'Loading your dashboard…', 'nextgencompanion' ),
						'error'    => __( 'Could not load dashboard data.', 'nextgencompanion' ),
						'empty'    => __( 'No data yet.', 'nextgencompanion' ),
						'sessions' => __( 'Recent sessions', 'nextgencompanion' ),
					],
				];
			wp_localize_script( 'ngc-dashboard-rest', 'biDashboard', $config );
		}

		$dashboard_html = '<div class="bi-dashboard-rest ngt-card" data-dashboard="' . esc_attr( $type ) . '" role="region" aria-live="polite" aria-busy="true"><p class="bi-dashboard-rest__loading">' . esc_html__( 'Loading your dashboard…', 'nextgencompanion' ) . '</p></div>';

		/**
		 * Filters dashboard shell content for separately deployed integrations.
		 *
		 * Integrations may append read-only UI, but Companion remains the
		 * authoritative owner of dashboard and domain data.
		 *
		 * @param string $dashboard_html Dashboard markup.
		 * @param string $type           Dashboard persona.
		 * @param int    $user_id        Current WordPress user ID.
		 */
		$dashboard_html = (string) apply_filters( 'ngc_dashboard_html', $dashboard_html, $type, get_current_user_id() );
		return self::render_shell( 'ngc-dashboard-shell ng-dashboard', $dashboard_html );
	}

	/**
	 * Public tutor calendar shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attrs.
	 * @return string
	 */
	public static function tutor_calendar( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'tutor_id'      => '0',
				'view'          => 'week',
				'show_filters'  => 'yes',
				'subject'       => '',
				'delivery_mode' => '',
				'timezone'      => 'Africa/Johannesburg',
			],
			(array) $atts,
			'nextgen_tutor_calendar'
		);

		$tutor_id = (int) $atts['tutor_id'];
		if ( $tutor_id <= 0 ) {
			return '<div class="ngc-calendar-empty">' . esc_html__( 'Tutor calendar unavailable.', 'nextgencompanion' ) . '</div>';
		}
		if ( ! class_exists( 'NGC_Tutor_Calendar_Service' ) || ! method_exists( 'NGC_Tutor_Calendar_Service', 'get_calendar' ) ) {
			return '<div class="ngc-calendar-empty">' . esc_html__( 'Calendar service is temporarily unavailable.', 'nextgencompanion' ) . '</div>';
		}

		$result = NGC_Tutor_Calendar_Service::get_calendar(
			$tutor_id,
			[
				'from'          => gmdate( 'Y-m-d' ),
				'to'            => gmdate( 'Y-m-d', strtotime( '+14 days' ) ),
				'subject'       => sanitize_text_field( $atts['subject'] ),
				'delivery_mode' => sanitize_text_field( $atts['delivery_mode'] ),
				'timezone'      => sanitize_text_field( $atts['timezone'] ),
			]
		);

		if ( empty( $result['success'] ) ) {
			return '<div class="ngc-calendar-empty">' . esc_html__( 'No public availability currently.', 'nextgencompanion' ) . '</div>';
		}

		$calendar = $result['data'];
		$slots    = is_array( $calendar['slots'] ?? null ) ? $calendar['slots'] : [];
		ob_start();
		?>
		<div class="ngc-tutor-calendar" data-tutor-id="<?php echo esc_attr( (string) $tutor_id ); ?>" data-view="<?php echo esc_attr( $atts['view'] ); ?>">
			<div class="ngc-tutor-calendar__head">
				<strong><?php esc_html_e( 'Tutor calendar', 'nextgencompanion' ); ?></strong>
				<span><?php echo esc_html( sprintf( 'Timezone: %s', (string) ( $calendar['timezone'] ?? 'Africa/Johannesburg' ) ) ); ?></span>
			</div>
			<?php if ( 'yes' === strtolower( (string) $atts['show_filters'] ) ) : ?>
				<div class="ngc-tutor-calendar__filters">
					<span class="ngc-legend ngc-legend--available"><?php esc_html_e( 'Available', 'nextgencompanion' ); ?></span>
					<span class="ngc-legend ngc-legend--booked"><?php esc_html_e( 'Booked', 'nextgencompanion' ); ?></span>
					<span class="ngc-legend ngc-legend--pending"><?php esc_html_e( 'Pending confirmation', 'nextgencompanion' ); ?></span>
					<span class="ngc-legend ngc-legend--blocked"><?php esc_html_e( 'Unavailable', 'nextgencompanion' ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( empty( $slots ) ) : ?>
				<p class="ngc-calendar-empty"><?php esc_html_e( 'No availability found in this range.', 'nextgencompanion' ); ?></p>
			<?php else : ?>
				<div class="ngc-tutor-calendar__slots" role="list">
					<?php foreach ( $slots as $slot ) : ?>
						<?php
						$status = sanitize_key( (string) ( $slot['status'] ?? 'unavailable' ) );
						$label  = sanitize_text_field( (string) ( $slot['public_label'] ?? 'Unavailable' ) );
						$is_available = 'available' === $status;
						$booking_url  = add_query_arg(
							[
								'ngc_tutor_id'      => (int) $tutor_id,
								'ngc_slot_date'     => sanitize_text_field( (string) ( $slot['date'] ?? '' ) ),
								'ngc_slot_start'    => sanitize_text_field( (string) ( $slot['start_time'] ?? '' ) ),
								'ngc_slot_end'      => sanitize_text_field( (string) ( $slot['end_time'] ?? '' ) ),
								'ngc_subject'       => sanitize_text_field( (string) ( $slot['subject'] ?? '' ) ),
								'ngc_delivery_mode' => sanitize_text_field( (string) ( $slot['delivery_mode'] ?? '' ) ),
							],
							home_url( '/find-a-tutor' )
						);
						?>
						<div class="ngc-slot ngc-slot--<?php echo esc_attr( $status ); ?>" role="listitem">
							<div class="ngc-slot__time">
								<?php echo esc_html( (string) $slot['date'] ); ?> · <?php echo esc_html( (string) $slot['start_time'] ); ?>-<?php echo esc_html( (string) $slot['end_time'] ); ?>
							</div>
							<div class="ngc-slot__meta"><?php echo esc_html( ucfirst( (string) ( $slot['delivery_mode'] ?? 'hybrid' ) ) ); ?> · <?php echo esc_html( $label ); ?></div>
							<?php if ( $is_available ) : ?>
								<a class="ngc-slot__cta" href="<?php echo esc_url( $booking_url ); ?>" data-ngc-slot="1" data-bi-booking-drawer="1" data-tutor-id="<?php echo esc_attr( (string) $tutor_id ); ?>" data-date="<?php echo esc_attr( (string) $slot['date'] ); ?>" data-start="<?php echo esc_attr( (string) $slot['start_time'] ); ?>" data-end="<?php echo esc_attr( (string) ( $slot['end_time'] ?? '' ) ); ?>" data-subject="<?php echo esc_attr( (string) ( $slot['subject'] ?? '' ) ); ?>" data-delivery="<?php echo esc_attr( (string) ( $slot['delivery_mode'] ?? '' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ngc_calendar_slot' ) ); ?>">
									<?php esc_html_e( 'Book this time', 'nextgencompanion' ); ?>
								</a>
							<?php else : ?>
								<span class="ngc-slot__label"><?php echo esc_html( $label ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<script>
		document.addEventListener('click', function(ev){
			var link = ev.target.closest('[data-ngc-slot="1"]');
			if(!link){ return; }
			var payload = new URLSearchParams();
			payload.set('action', 'ngc_calendar_slot_selected');
			payload.set('nonce', link.getAttribute('data-nonce') || '');
			payload.set('tutor_id', link.getAttribute('data-tutor-id') || '');
			payload.set('date', link.getAttribute('data-date') || '');
			payload.set('start_time', link.getAttribute('data-start') || '');
			payload.set('subject', link.getAttribute('data-subject') || '');
			payload.set('delivery_mode', link.getAttribute('data-delivery') || '');
			fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:payload.toString()});
		});
		</script>
		<?php
		return self::render_shell( 'ngc-calendar-shell ng-calendar', (string) ob_get_clean() );
	}

	/**
	 * @param string                             $form_id Form slug.
	 * @param string                             $title   Title.
	 * @param array<int, array<string, mixed>>   $fields  Fields.
	 * @return string
	 */
	private static function render_form( $form_id, $title, $fields ) {
		ob_start();
		?>
		<form class="ngc-form ngt-form ng-form bi-ngc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ngc_form_submit" />
			<input type="hidden" name="ngc_form_id" value="<?php echo esc_attr( $form_id ); ?>" />
			<?php wp_nonce_field( 'ngc_form_' . $form_id, 'ngc_form_nonce' ); ?>
			<?php if ( $title ) : ?>
				<h3 class="ngc-form__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<?php foreach ( $fields as $field ) : ?>
				<div class="ngc-field-group ngt-form-group">
					<?php if ( 'checkbox' !== ( $field['type'] ?? '' ) ) : ?>
						<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
					<?php endif; ?>
					<?php if ( 'textarea' === ( $field['type'] ?? 'text' ) ) : ?>
						<textarea id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" rows="4" class="ngc-wysiwyg" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>></textarea>
					<?php elseif ( 'select' === ( $field['type'] ?? '' ) ) : ?>
						<select id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
							<?php foreach ( (array) ( $field['options'] ?? [] ) as $value => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $value ); ?>"><?php echo esc_html( (string) $label ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php elseif ( 'checkbox' === ( $field['type'] ?? '' ) ) : ?>
						<label for="<?php echo esc_attr( $field['id'] ); ?>" style="display:flex;gap:10px;align-items:flex-start;font-weight:400">
							<input type="checkbox" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="1" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?> />
							<span><?php echo esc_html( (string) ( $field['check_label'] ?? $field['label'] ) ); ?></span>
						</label>
					<?php else : ?>
						<input type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?> />
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<button type="submit" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Submit', 'nextgencompanion' ); ?></button>
		</form>
		<?php
		return self::render_shell( 'ngc-form-shell ng-form', (string) ob_get_clean() );
	}

	/**
	 * Lightweight markup shell for shortcode output.
	 *
	 * @param string $classes CSS classes.
	 * @param string $html    Inner HTML.
	 * @return string
	 */
	private static function render_shell( $classes, $html ) {
		return '<div class="' . esc_attr( $classes ) . '">' . $html . '</div>';
	}

	/**
	 * Form POST handler.
	 */
	public static function handle_form_submit() {
		$form_id = isset( $_POST['ngc_form_id'] ) ? sanitize_key( wp_unslash( $_POST['ngc_form_id'] ) ) : '';
		if ( ! $form_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ngc_form_nonce'] ?? '' ) ), 'ngc_form_' . $form_id ) ) {
			wp_die( esc_html__( 'Invalid form submission.', 'nextgencompanion' ), 403 );
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

		if ( 'find_tutor' === $form_id && empty( $payload['popia_consent'] ) ) {
			wp_die( esc_html__( 'POPIA consent is required to submit a tutor enquiry.', 'nextgencompanion' ), 400 );
		}

		$queue = get_option( 'ngc_form_queue', [] );
		if ( ! is_array( $queue ) ) {
			$queue = [];
		}
		$queue[] = [
			'form'    => $form_id,
			'data'    => $payload,
			'source'  => 'companion',
			'created' => gmdate( 'c' ),
		];
		update_option( 'ngc_form_queue', array_slice( $queue, -100 ), false );

		self::defer_form_processing( $form_id, $payload );

		$redirect = self::form_redirect_url( $form_id );
		wp_safe_redirect( add_query_arg( 'ngc_submitted', $form_id, $redirect ) );
		exit;
	}

	/**
	 * Process workflows after the HTTP redirect (keeps POST responses fast).
	 *
	 * @param string               $form_id Form slug.
	 * @param array<string, mixed> $payload Sanitized fields.
	 */
	private static function defer_form_processing( $form_id, $payload ) {
		add_action(
			'shutdown',
			static function () use ( $form_id, $payload ) {
				do_action( 'ngc_form_submitted', $form_id, $payload );

				wp_mail(
					get_option( 'admin_email' ),
					sprintf( '[NextGen] %s submission', $form_id ),
					wp_json_encode( $payload, JSON_PRETTY_PRINT )
				);
				do_action( 'bi_form_submitted', $form_id, $payload );
			},
			1
		);
	}

	/**
	 * Safe redirect target after form POST.
	 *
	 * @param string $form_id Form slug.
	 * @return string
	 */
	private static function form_redirect_url( $form_id ) {
		$map = [
			'find_tutor'       => home_url( '/thank-you/?type=parent' ),
			'become_tutor'     => home_url( '/thank-you/?type=tutor' ),
			'contact_support'  => home_url( '/thank-you/?type=contact' ),
			'parent_register'  => home_url( '/thank-you/?type=parent' ),
			'student_register' => home_url( '/thank-you/?type=general' ),
		];

		$redirect = wp_get_referer();
		// Prefer the thank-you page so the timeline can set expectations.
		if ( isset( $map[ $form_id ] ) ) {
			$redirect = $map[ $form_id ];
		} elseif ( ! $redirect || ! wp_validate_redirect( $redirect, false ) ) {
			$redirect = home_url( '/thank-you/' );
		}

		return (string) apply_filters( 'ngc_form_redirect_url', $redirect, $form_id );
	}

	/**
	 * @return array{ok: bool, missing: string[]}
	 */
	public static function health() {
		$missing = [];
		foreach ( self::required_tags() as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				$missing[] = $tag;
			}
		}
		return [ 'ok' => empty( $missing ), 'missing' => $missing ];
	}
}

/**
 * Shortcode health for theme admin.
 *
 * @return array{ok: bool, missing: string[]}
 */
function bi_ngc_shortcode_health() {
	return NGC_Shortcodes::health();
}
