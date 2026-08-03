<?php
/**
 * Operational HTML email/consent layouts from IMPORTANT design pack.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load and normalize SA-branded HTML layouts for live transactional use.
 */
class NGC_Operational_Layouts {

	/**
	 * Layout file map (key => filename under assets/email-layouts/operational/).
	 *
	 * @return array<string, string>
	 */
	public static function map() {
		return [
			'booking_confirmed'      => 'booking-confirmation.html',
			'tutor_approved'         => 'tutor-approval-welcome.html',
			'session_rating_request' => 'session-rating-request.html',
			'popia_shell'            => 'popia-compliant-email.html',
			'popia_consent_form'     => 'popia-consent-form.html',
		];
	}

	/**
	 * Absolute directory for operational layouts.
	 *
	 * @return string
	 */
	public static function dir() {
		return trailingslashit( NGC_PLUGIN_DIR ) . 'assets/email-layouts/operational/';
	}

	/**
	 * Load raw HTML for a layout key.
	 *
	 * @param string $key Layout key.
	 * @return string
	 */
	public static function load( $key ) {
		$key  = sanitize_key( $key );
		$map  = self::map();
		$file = $map[ $key ] ?? '';
		if ( ! $file ) {
			return '';
		}
		$path = self::dir() . $file;
		if ( ! is_readable( $path ) ) {
			return '';
		}
		$html = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return self::normalize_tokens( $html );
	}

	/**
	 * Map FluentCRM-style tokens to Companion merge fields.
	 *
	 * @param string $html Raw HTML.
	 * @return string
	 */
	public static function normalize_tokens( $html ) {
		$replacements = [
			'{{contact.first_name}}'                 => '{{first_name}}',
			'{{contact.child_name}}'                  => '{{student_name}}',
			'{{contact.custom.booking_date}}'         => '{{booking_date}}',
			'{{contact.custom.booking_time}}'         => '{{booking_time}}',
			'{{contact.custom.tutor_name}}'           => '{{tutor_name}}',
			'{{contact.custom.zoom_link}}'            => '{{join_url}}',
			'{{contact.custom.dashboard_link}}'       => '{{dashboard_url}}',
			'{{contact.custom.tutor_dashboard_link}}' => '{{dashboard_url}}',
			'{{contact.custom.payout_rate}}'          => '{{payout_rate}}',
			'{{contact.custom.kb_link}}'              => '{{kb_url}}',
			'{{contact.custom.rating_link}}'          => '{{rating_url}}',
			'{{contact.custom.popia_consent_date}}'   => '{{popia_consent_date}}',
			'{{contact.manage_preferences_url}}'      => '{{preferences_url}}',
			'{{contact.unsubscribe_url}}'             => '{{unsubscribe_url}}',
			'+27 XX XXX XXXX'                         => '{{support_phone}}',
		];
		return str_replace( array_keys( $replacements ), array_values( $replacements ), $html );
	}

	/**
	 * Subject lines for operational templates.
	 *
	 * @param string $key Template key.
	 * @return string
	 */
	public static function subject( $key ) {
		$site = get_bloginfo( 'name' );
		$subjects = [
			'booking_confirmed'      => sprintf( __( '[%s] Booking confirmed — your session details', 'nextgencompanion' ), $site ),
			'tutor_approved'         => sprintf( __( '[%s] You’re approved — your tutor profile is live', 'nextgencompanion' ), $site ),
			'session_rating_request' => sprintf( __( '[%s] Rate your tutoring session', 'nextgencompanion' ), $site ),
			'popia_shell'            => sprintf( __( '[%s] {{subject}}', 'nextgencompanion' ), $site ),
		];
		return $subjects[ $key ] ?? sprintf( __( '[%s] NextGen Tutors', 'nextgencompanion' ), $site );
	}

	/**
	 * Plain-text fallback from HTML.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function to_text( $html ) {
		$text = wp_strip_all_tags( str_replace( [ '<br>', '<br/>', '<br />', '</p>', '</tr>' ], "\n", $html ) );
		$text = preg_replace( "/[ \t]+/", ' ', $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", (string) $text );
		return trim( (string) $text );
	}

	/**
	 * Render POPIA consent form markup for registration/checkout.
	 *
	 * @return string
	 */
	public static function consent_form_html() {
		$html = self::load( 'popia_consent_form' );
		if ( ! $html ) {
			return '';
		}
		$privacy = esc_url( home_url( '/privacy-policy/' ) );
		return str_replace( 'href="/privacy-policy/"', 'href="' . $privacy . '"', $html );
	}
}
