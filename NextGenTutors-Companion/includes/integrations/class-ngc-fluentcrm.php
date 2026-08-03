<?php
/**
 * FluentCRM legacy shim + enquiry / session / rating field sync.
 *
 * Absorbs IMPORTANT find-tutor-form CRM tags and FluentCRM-Automation custom fields.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inbound Amelia-style CRM hooks only; upsert handled by NGC_Fluentcrm_Adapter.
 */
class NGC_Fluentcrm {

	/**
	 * Hook registration.
	 */
	public static function init() {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'missing_notice' ] );
			return;
		}
		// Orchestrator handles CRM for registration workflows.
		add_action( 'ngc_fluentcrm_bootstrap', [ __CLASS__, 'bootstrap' ] );
		add_action( 'ngc_form_submitted', [ __CLASS__, 'on_form_submitted' ], 20, 2 );
		add_action( 'ngc_lesson_completed', [ __CLASS__, 'on_lesson_completed' ], 30, 1 );
		add_action( 'ngc_review_submitted', [ __CLASS__, 'on_review_submitted' ], 20, 1 );
		add_action( 'ngc_tutor_approved', [ __CLASS__, 'on_tutor_approved' ], 30, 1 );
	}

	/**
	 * Bootstrap lists/tags on demand.
	 */
	public static function bootstrap() {
		$adapter = new NGC_Fluentcrm_Adapter();
		$adapter->bootstrap_assets();
	}

	/**
	 * Sync Find a Tutor intake → Active Customers + Parent Enquiry tags + POPIA fields.
	 *
	 * @param string               $form_id Form slug.
	 * @param array<string, mixed> $payload Fields.
	 */
	public static function on_form_submitted( $form_id, $payload ) {
		if ( 'find_tutor' !== $form_id ) {
			return;
		}
		$email = sanitize_email( (string) ( $payload['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			return;
		}

		$name  = sanitize_text_field( (string) ( $payload['parent_name'] ?? $payload['full_name'] ?? '' ) );
		$parts = preg_split( '/\s+/', $name, 2 );
		$first = $parts[0] ?? '';
		$last  = $parts[1] ?? '';

		$user_id = email_exists( $email );
		if ( ! $user_id ) {
			$user = get_user_by( 'email', $email );
			$user_id = $user ? (int) $user->ID : 0;
		}

		$custom = [];
		if ( ! empty( $payload['popia_consent'] ) ) {
			$custom = [
				'popia_consent_given'      => true,
				'popia_consent_date'       => current_time( 'mysql' ),
				'popia_consent_version'    => class_exists( 'NGC_Popia_Consent' ) ? NGC_Popia_Consent::CONSENT_VER : '1.2',
				'popia_processing_purpose' => [ 'matching', 'support', 'marketing_opt_in' ],
			];
			// Persist WP audit without a second FluentCRM round-trip (we sync below).
			if ( $user_id && class_exists( 'NGC_Popia_Consent' ) ) {
				update_user_meta(
					(int) $user_id,
					NGC_Popia_Consent::META_KEY,
					[
						'accepted'    => true,
						'given'       => true,
						'timestamp'   => current_time( 'mysql' ),
						'consent_ver' => NGC_Popia_Consent::CONSENT_VER,
						'source'      => 'find_tutor_form',
					]
				);
			}
		}

		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}
		$adapter->create_or_update(
			'sync',
			[
				'email'         => $email,
				'first_name'    => $first,
				'last_name'     => $last,
				'phone'         => sanitize_text_field( (string) ( $payload['phone'] ?? '' ) ),
				'user_id'       => (int) $user_id,
				'role'          => 'parent',
				'workflow'      => 'find_tutor',
				'lists'         => [ 'Active Customers', 'Marketing Opt-In' ],
				'tags'          => [ 'Parent Enquiry', 'Prospective Parent', 'POPIA Consented' ],
				'custom_fields' => $custom,
			]
		);
	}

	/**
	 * Bump sessions_count + last_session_date after a lesson completes.
	 *
	 * @param int|array<string,mixed> $booking_id Booking ID or workflow vars.
	 */
	public static function on_lesson_completed( $booking_id ) {
		$context = [];
		if ( is_array( $booking_id ) ) {
			$context    = $booking_id;
			$booking_id = (int) ( $context['booking_id'] ?? 0 );
		} else {
			$booking_id = (int) $booking_id;
		}

		$booking = ( $booking_id && class_exists( 'NGC_Bookings' ) ) ? NGC_Bookings::get( $booking_id ) : null;
		$parent_id = (int) ( $booking->parent_user_id ?? $context['parent_user_id'] ?? 0 );
		if ( ! $parent_id ) {
			return;
		}
		$user = get_userdata( $parent_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}

		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}

		$existing = $adapter->get_existing( [ 'email' => $user->user_email ] );
		$count    = 1;
		if ( $existing && ! empty( $existing['id'] ) && function_exists( 'FluentCrmApi' ) ) {
			try {
				$contact = FluentCrmApi( 'contacts' )->getContact( $user->user_email );
				if ( $contact && isset( $contact->custom_values['sessions_count'] ) ) {
					$count = max( 1, (int) $contact->custom_values['sessions_count'] + 1 );
				}
			} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				$count = 1;
			}
		}

		$adapter->create_or_update(
			'sync',
			[
				'email'         => $user->user_email,
				'user_id'       => $parent_id,
				'tags'          => [ 'Engaged Customer' ],
				'custom_fields' => [
					'sessions_count'    => $count,
					'last_session_date' => current_time( 'Y-m-d' ),
				],
			]
		);
	}

	/**
	 * Sync latest_rating + quality tags from review submission.
	 *
	 * @param array<string, mixed> $payload Review context.
	 */
	public static function on_review_submitted( $payload ) {
		$parent_id = (int) ( $payload['parent_user_id'] ?? 0 );
		$rating    = (float) ( $payload['rating'] ?? 0 );
		if ( ! $parent_id || $rating < 1 ) {
			return;
		}
		$user = get_userdata( $parent_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}

		$tags = [];
		$detach = [];
		if ( $rating < 4 ) {
			$tags[] = 'Needs Support';
		} elseif ( (int) $rating === 5 ) {
			$tags[]   = 'Satisfied';
			$tags[]   = 'Advocate';
			$detach[] = 'Needs Support';
		} else {
			$tags[]   = 'Satisfied';
			$detach[] = 'Needs Support';
		}

		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}
		$adapter->create_or_update(
			'sync',
			[
				'email'         => $user->user_email,
				'user_id'       => $parent_id,
				'tags'          => $tags,
				'detach_tags'   => $detach,
				'custom_fields' => [
					'latest_rating' => $rating,
				],
			]
		);
	}

	/**
	 * Mark verification_status approved when a tutor is approved.
	 *
	 * @param int|array<string,mixed> $user_id Tutor user ID or context.
	 */
	public static function on_tutor_approved( $user_id ) {
		if ( is_array( $user_id ) ) {
			$user_id = (int) ( $user_id['user_id'] ?? $user_id['tutor_user_id'] ?? 0 );
		} else {
			$user_id = (int) $user_id;
		}
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}
		$adapter->create_or_update(
			'sync',
			[
				'email'         => $user->user_email,
				'user_id'       => $user_id,
				'tags'          => [ 'Verified Tutor', 'Ready for Bookings' ],
				'detach_tags'   => [ 'Pending Review' ],
				'custom_fields' => [
					'verification_status' => 'approved',
				],
			]
		);
	}

	/**
	 * Admin notice when FluentCRM is not installed.
	 */
	public static function missing_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-info is-dismissible"><p>';
		esc_html_e( 'NextGen Companion: FluentCRM is not active. CRM tagging is disabled; WordPress/email workflows continue.', 'nextgencompanion' );
		echo '</p></div>';
	}
}
