<?php
/**
 * Outreach sequence + reply classification (governed; human gates for sensitive topics).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor recruitment outreach / nurture engine.
 */
final class NGC_Outreach_Engine {

	const OPTION_CAMPAIGNS = 'ngc_outreach_campaigns';
	const OPTION_ENROLLMENTS = 'ngc_outreach_enrollments';
	const OPTION_REPLIES = 'ngc_outreach_replies';

	const STOP_STATUSES = [ 'unsubscribed', 'do_not_contact', 'hard_bounce', 'complaint', 'not_interested', 'negative' ];

	/**
	 * Default sequence steps (templates are text only; send requires approval).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_sequence() {
		return [
			[ 'step' => 1, 'key' => 'intro', 'subject' => 'Tutoring opportunity with Next Gen Tutors', 'requires_human' => true ],
			[ 'step' => 2, 'key' => 'value', 'subject' => 'How Next Gen Tutors supports tutors', 'requires_human' => true ],
			[ 'step' => 3, 'key' => 'experience', 'subject' => 'Platform support for your subject', 'requires_human' => true ],
			[ 'step' => 4, 'key' => 'reminder', 'subject' => 'Friendly follow-up', 'requires_human' => true ],
			[ 'step' => 5, 'key' => 'close', 'subject' => 'Closing our outreach', 'requires_human' => true ],
		];
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_campaign( array $input ) {
		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return new WP_Error( 'ngc_outreach_name', __( 'Campaign name required.', 'nextgencompanion' ) );
		}
		$id = 'camp_' . wp_generate_password( 10, false, false );
		$row = [
			'id'         => $id,
			'name'       => $name,
			'sequence'   => self::default_sequence(),
			'status'     => 'draft',
			'created_at' => gmdate( 'c' ),
			'created_by' => get_current_user_id(),
		];
		$all   = self::campaigns();
		$all[] = $row;
		update_option( self::OPTION_CAMPAIGNS, $all, false );
		return $row;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function campaigns() {
		$rows = get_option( self::OPTION_CAMPAIGNS, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}

	/**
	 * Enroll lead into campaign after human approval.
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function enroll( array $input ) {
		if ( empty( $input['human_approved'] ) ) {
			return new WP_Error( 'ngc_outreach_approval', __( 'Human approval required before enrollment.', 'nextgencompanion' ) );
		}
		$lead_id = sanitize_key( (string) ( $input['lead_id'] ?? '' ) );
		$camp_id = sanitize_key( (string) ( $input['campaign_id'] ?? '' ) );
		$email   = sanitize_email( (string) ( $input['email'] ?? '' ) );
		if ( ! $lead_id || ! $camp_id || ! is_email( $email ) ) {
			return new WP_Error( 'ngc_outreach_args', __( 'lead_id, campaign_id, and valid email required.', 'nextgencompanion' ) );
		}
		foreach ( self::enrollments() as $e ) {
			if ( ( $e['lead_id'] ?? '' ) === $lead_id && ( $e['campaign_id'] ?? '' ) === $camp_id && ! in_array( ( $e['status'] ?? '' ), self::STOP_STATUSES, true ) ) {
				return $e; // Idempotent.
			}
		}
		$row = [
			'id'          => 'enr_' . wp_generate_password( 10, false, false ),
			'lead_id'     => $lead_id,
			'campaign_id' => $camp_id,
			'email'       => $email,
			'step'        => 0,
			'status'      => 'enrolled',
			'created_at'  => gmdate( 'c' ),
		];
		$all   = self::enrollments();
		$all[] = $row;
		update_option( self::OPTION_ENROLLMENTS, $all, false );
		return $row;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function enrollments() {
		$rows = get_option( self::OPTION_ENROLLMENTS, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}

	/**
	 * Advance one step — records intended send; does not auto-email without transport.
	 *
	 * @param string $enrollment_id ID.
	 * @param bool   $human_approved Approval.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function advance( $enrollment_id, $human_approved = false ) {
		$all = self::enrollments();
		foreach ( $all as $i => $e ) {
			if ( ( $e['id'] ?? '' ) !== $enrollment_id ) {
				continue;
			}
			if ( in_array( ( $e['status'] ?? '' ), self::STOP_STATUSES, true ) ) {
				return new WP_Error( 'ngc_outreach_stopped', __( 'Enrollment is stopped.', 'nextgencompanion' ) );
			}
			if ( ! $human_approved ) {
				return new WP_Error( 'ngc_outreach_approval', __( 'Human approval required to send the next step.', 'nextgencompanion' ) );
			}
			$step = (int) ( $e['step'] ?? 0 ) + 1;
			if ( $step > 5 ) {
				$all[ $i ]['status'] = 'completed';
				update_option( self::OPTION_ENROLLMENTS, $all, false );
				return $all[ $i ];
			}
			$all[ $i ]['step']       = $step;
			$all[ $i ]['status']     = 'step_' . $step;
			$all[ $i ]['last_sent']  = gmdate( 'c' );
			$all[ $i ]['updated_at'] = gmdate( 'c' );
			update_option( self::OPTION_ENROLLMENTS, $all, false );
			return $all[ $i ];
		}
		return new WP_Error( 'ngc_outreach_missing', __( 'Enrollment not found.', 'nextgencompanion' ) );
	}

	/**
	 * Classify reply text (deterministic rules; AI optional later).
	 *
	 * @param string $text Reply body.
	 * @return array<string, mixed>
	 */
	public static function classify_reply( $text ) {
		$t = strtolower( (string) $text );
		$map = [
			'unsubscribe'   => [ 'unsubscribe', 'remove me', 'stop emailing' ],
			'not_interested'=> [ 'not interested', 'no thanks', 'please stop' ],
			'interested'    => [ 'interested', 'tell me more', 'sounds good', 'i am keen' ],
			'application_started' => [ 'applied', 'application', 'i signed up' ],
			'wrong_person'  => [ 'wrong person', 'not me', 'wrong email' ],
			'complaint'     => [ 'complaint', 'report spam', 'attorney', 'lawyer' ],
			'delivery_failure' => [ 'mailer-daemon', 'undeliverable', 'delivery failed' ],
		];
		foreach ( $map as $label => $needles ) {
			foreach ( $needles as $n ) {
				if ( false !== strpos( $t, $n ) ) {
					$needs_human = in_array( $label, [ 'complaint', 'delivery_failure' ], true ) || preg_match( '/\b(contract|pay|salary|safeguarding|id document)\b/', $t );
					return [
						'label'       => $label,
						'confidence'  => 0.85,
						'needs_human' => (bool) $needs_human,
						'stop'        => in_array( $label, [ 'unsubscribe', 'not_interested', 'complaint', 'delivery_failure' ], true ),
					];
				}
			}
		}
		return [ 'label' => 'ambiguous', 'confidence' => 0.4, 'needs_human' => true, 'stop' => false ];
	}

	/**
	 * Ingest reply and apply stop conditions.
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function ingest_reply( array $input ) {
		$enrollment_id = sanitize_key( (string) ( $input['enrollment_id'] ?? '' ) );
		$body          = sanitize_textarea_field( (string) ( $input['body'] ?? '' ) );
		if ( '' === $enrollment_id || '' === $body ) {
			return new WP_Error( 'ngc_reply_args', __( 'enrollment_id and body required.', 'nextgencompanion' ) );
		}
		$class = self::classify_reply( $body );
		$reply = [
			'id'            => 'rep_' . wp_generate_password( 8, false, false ),
			'enrollment_id' => $enrollment_id,
			'body_redacted' => wp_html_excerpt( $body, 500, '…' ),
			'classification'=> $class,
			'created_at'    => gmdate( 'c' ),
		];
		$replies   = get_option( self::OPTION_REPLIES, [] );
		$replies   = is_array( $replies ) ? $replies : [];
		$replies[] = $reply;
		update_option( self::OPTION_REPLIES, array_slice( $replies, -200 ), false );

		$all = self::enrollments();
		foreach ( $all as $i => $e ) {
			if ( ( $e['id'] ?? '' ) !== $enrollment_id ) {
				continue;
			}
			$all[ $i ]['last_reply_class'] = $class['label'];
			if ( ! empty( $class['stop'] ) ) {
				$all[ $i ]['status'] = 'unsubscribe' === $class['label'] ? 'unsubscribed' : ( 'complaint' === $class['label'] ? 'complaint' : 'not_interested' );
			} elseif ( 'interested' === $class['label'] ) {
				$all[ $i ]['status'] = 'interested';
			} elseif ( ! empty( $class['needs_human'] ) ) {
				$all[ $i ]['status'] = 'human_review';
			}
			$all[ $i ]['updated_at'] = gmdate( 'c' );
			update_option( self::OPTION_ENROLLMENTS, $all, false );
			$reply['enrollment'] = $all[ $i ];
			return $reply;
		}
		return new WP_Error( 'ngc_reply_enrollment', __( 'Enrollment not found.', 'nextgencompanion' ) );
	}

	/**
	 * Handoff interested lead to tutor applicant path (metadata only).
	 *
	 * @param string $enrollment_id ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function recruitment_handoff( $enrollment_id ) {
		$all = self::enrollments();
		foreach ( $all as $i => $e ) {
			if ( ( $e['id'] ?? '' ) !== $enrollment_id ) {
				continue;
			}
			if ( 'interested' !== ( $e['status'] ?? '' ) && 'application_started' !== ( $e['status'] ?? '' ) ) {
				return new WP_Error( 'ngc_handoff_state', __( 'Handoff requires interested or application_started status.', 'nextgencompanion' ) );
			}
			$all[ $i ]['status']          = 'handed_off';
			$all[ $i ]['handoff_at']      = gmdate( 'c' );
			$all[ $i ]['applicant_flag']  = 1;
			update_option( self::OPTION_ENROLLMENTS, $all, false );
			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log( 'recruitment_handoff', 'outreach', 0, [ 'enrollment_id' => $enrollment_id, 'lead_id' => $e['lead_id'] ?? '' ] );
			}
			return $all[ $i ];
		}
		return new WP_Error( 'ngc_handoff_missing', __( 'Enrollment not found.', 'nextgencompanion' ) );
	}
}
