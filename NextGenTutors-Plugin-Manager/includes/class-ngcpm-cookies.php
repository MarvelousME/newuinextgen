<?php
/**
 * Cookie diagnostics and probe.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Separates cookie system checks from tracking/consent presence in admin.
 */
class NGCPM_Cookies {

	const PROBE_COOKIE_NAME = 'ngcpm_probe';
	const PROBE_TRANSIENT   = 'ngcpm_cookie_probe_';

	/** @var array<string, string> */
	const FAILURE_REASONS = [
		'browser_blocked_cookie'      => 'Browser did not store the probe cookie (blocked or third-party restrictions).',
		'server_did_not_set_cookie'   => 'Server could not set the probe cookie (headers sent or setcookie failed).',
		'cookie_not_returned_to_server' => 'Browser stored the cookie but it was not sent back on the next request.',
		'secure_cookie_on_http'       => 'Secure cookie flag is set but the site is not served over HTTPS.',
		'samesite_issue'              => 'SameSite cookie policy may be blocking cross-context delivery.',
		'path_mismatch'               => 'Cookie path does not match the admin request path.',
		'domain_mismatch'             => 'Cookie domain does not match the current host.',
		'consent_missing'             => 'Consent cookie not present (expected until visitor accepts on frontend).',
	];

	/**
	 * Run static cookie checks (no probe).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function run_checks() {
		$is_admin = is_admin();

		return [
			self::check_system_available(),
			self::check_session_cookie(),
			self::check_tracking_cookie( $is_admin ),
			self::check_consent_cookie( $is_admin ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_system_available() {
		$can_set = ! headers_sent();
		$path    = COOKIEPATH ? COOKIEPATH : '/';
		$domain  = COOKIE_DOMAIN;
		$secure  = is_ssl();
		$issues  = [];

		if ( $secure && ! is_ssl() ) {
			$issues[] = 'secure_cookie_on_http';
		}

		$status = $can_set ? 'PASS' : 'FAIL';
		$evidence = $can_set
			? sprintf(
				/* translators: 1: path 2: domain */
				__( 'setcookie available · path=%1$s · domain=%2$s', 'nextgentutors-plugin-manager' ),
				$path,
				$domain ?: '(default)'
			)
			: __( 'Headers already sent — cookies cannot be set on this response.', 'nextgentutors-plugin-manager' );

		return self::row(
			'COOKIE_SYSTEM_AVAILABLE',
			$status,
			$evidence,
			$can_set ? '' : __( 'Resolve output before headers in admin bootstrap.', 'nextgentutors-plugin-manager' ),
			$issues
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_session_cookie() {
		$found = false;
		foreach ( array_keys( $_COOKIE ) as $name ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( 0 === strpos( $name, 'wordpress_logged_in' ) || 0 === strpos( $name, 'wordpress_sec' ) ) {
				$found = true;
				break;
			}
		}

		return self::row(
			'SESSION_COOKIE_PRESENT',
			$found ? 'PASS' : 'WARNING',
			$found
				? __( 'WordPress session cookie present on this request.', 'nextgentutors-plugin-manager' )
				: __( 'No WordPress session cookie on this request.', 'nextgentutors-plugin-manager' ),
			$found ? '' : __( 'Log in again if admin actions fail with auth errors.', 'nextgentutors-plugin-manager' ),
			[]
		);
	}

	/**
	 * @param bool $is_admin In wp-admin context.
	 * @return array<string, mixed>
	 */
	private static function check_tracking_cookie( $is_admin ) {
		$tracking = apply_filters( 'ngcpm_tracking_cookie_names', [ 'ngc_visitor_id', 'ngc_session_id' ] );
		$present  = 0;
		foreach ( (array) $tracking as $name ) {
			if ( isset( $_COOKIE[ $name ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				++$present;
			}
		}
		$total = count( (array) $tracking );

		if ( $is_admin && 0 === $present ) {
			if ( apply_filters( 'ngcpm_frontend_cookies_configured', false ) ) {
				return self::row(
					'TRACKING_COOKIE_PRESENT',
					'PASS',
					__( 'Companion tracking configured — cookies are set on the public site after visitors accept consent.', 'nextgentutors-plugin-manager' ),
					__( 'Open the homepage in a private window, accept cookies, then re-run diagnostics.', 'nextgentutors-plugin-manager' ),
					[]
				);
			}
			return self::row(
				'TRACKING_COOKIE_PRESENT',
				'NOT_CONFIGURED',
				__( 'Tracking cookies absent in admin — this is normal until frontend consent flow runs.', 'nextgentutors-plugin-manager' ),
				__( 'Verify tracking on the public site after consent, not in wp-admin.', 'nextgentutors-plugin-manager' ),
				[]
			);
		}

		$status = $present > 0 ? 'PASS' : 'WARNING';
		return self::row(
			'TRACKING_COOKIE_PRESENT',
			$status,
			sprintf(
				/* translators: 1: present count 2: total expected */
				__( '%1$d/%2$d tracking cookies present (presence only).', 'nextgentutors-plugin-manager' ),
				$present,
				$total
			),
			$present > 0 ? '' : __( 'Enable NextGen Companion tracking on the frontend.', 'nextgentutors-plugin-manager' ),
			0 === $present ? [ 'consent_missing' ] : []
		);
	}

	/**
	 * @param bool $is_admin In wp-admin context.
	 * @return array<string, mixed>
	 */
	private static function check_consent_cookie( $is_admin ) {
		$consent_names = apply_filters( 'ngcpm_consent_cookie_names', [ 'ngc_cookie_consent', 'ngc_consent' ] );
		$present       = false;
		foreach ( (array) $consent_names as $name ) {
			if ( isset( $_COOKIE[ $name ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$present = true;
				break;
			}
		}

		if ( $is_admin && ! $present ) {
			if ( apply_filters( 'ngcpm_frontend_cookies_configured', false ) ) {
				return self::row(
					'CONSENT_COOKIE_PRESENT',
					'PASS',
					__( 'Companion consent banner active — consent cookie is set when visitors accept on the public site.', 'nextgentutors-plugin-manager' ),
					__( 'Test in a private browser window at the site front page, not in wp-admin.', 'nextgentutors-plugin-manager' ),
					[]
				);
			}
			return self::row(
				'CONSENT_COOKIE_PRESENT',
				'NOT_CONFIGURED',
				__( 'Consent cookie not set in admin — expected until a visitor accepts on the frontend.', 'nextgentutors-plugin-manager' ),
				__( 'Do not treat missing consent cookies as a health failure in wp-admin.', 'nextgentutors-plugin-manager' ),
				[]
			);
		}

		return self::row(
			'CONSENT_COOKIE_PRESENT',
			$present ? 'PASS' : 'WARNING',
			$present
				? __( 'Consent cookie present.', 'nextgentutors-plugin-manager' )
				: __( 'Consent cookie not present on this request.', 'nextgentutors-plugin-manager' ),
			$present ? '' : __( 'Configure cookie consent banner on the public site.', 'nextgentutors-plugin-manager' ),
			$present ? [] : [ 'consent_missing' ]
		);
	}

	/**
	 * Step 1: issue probe token and set server cookie.
	 *
	 * @return array<string, mixed>
	 */
	public static function probe_init() {
		if ( headers_sent() ) {
			return [
				'success' => false,
				'reason'  => 'server_did_not_set_cookie',
				'message' => self::reason_message( 'server_did_not_set_cookie' ),
			];
		}

		if ( is_ssl() === false && ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) ) {
			return [
				'success' => false,
				'reason'  => 'secure_cookie_on_http',
				'message' => self::reason_message( 'secure_cookie_on_http' ),
			];
		}

		$token = wp_generate_password( 32, false, false );
		$path  = COOKIEPATH ? COOKIEPATH : '/';
		$domain = COOKIE_DOMAIN;

		$set = setcookie(
			self::PROBE_COOKIE_NAME,
			$token,
			[
				'expires'  => time() + 300,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			]
		);

		if ( ! $set ) {
			return [
				'success' => false,
				'reason'  => 'server_did_not_set_cookie',
				'message' => self::reason_message( 'server_did_not_set_cookie' ),
			];
		}

		set_transient( self::PROBE_TRANSIENT . $token, 1, 300 );

		return [
			'success'     => true,
			'token'       => $token,
			'cookie_name' => self::PROBE_COOKIE_NAME,
			'path'        => $path,
			'domain'      => $domain,
			'secure'      => is_ssl(),
			'repair'      => __( 'If the browser step fails, check ad blockers, HTTPS, and cookie path/domain constants.', 'nextgentutors-plugin-manager' ),
		];
	}

	/**
	 * Step 2: verify server received probe cookie.
	 *
	 * @param string $token Expected token from init.
	 * @param bool   $browser_confirmed Frontend confirmed document.cookie contains token.
	 * @return array<string, mixed>
	 */
	public static function probe_verify( $token, $browser_confirmed ) {
		$token = sanitize_text_field( $token );
		if ( ! $token || ! get_transient( self::PROBE_TRANSIENT . $token ) ) {
			return [
				'success' => false,
				'reason'  => 'server_did_not_set_cookie',
				'message' => __( 'Probe session expired or invalid. Run the probe again.', 'nextgentutors-plugin-manager' ),
			];
		}

		if ( ! $browser_confirmed ) {
			delete_transient( self::PROBE_TRANSIENT . $token );
			return [
				'success' => false,
				'reason'  => 'browser_blocked_cookie',
				'message' => self::reason_message( 'browser_blocked_cookie' ),
				'repair'  => __( 'Disable strict browser privacy extensions for this site or allow cookies for the admin domain.', 'nextgentutors-plugin-manager' ),
			];
		}

		$received = isset( $_COOKIE[ self::PROBE_COOKIE_NAME ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			? sanitize_text_field( wp_unslash( $_COOKIE[ self::PROBE_COOKIE_NAME ] ) )
			: '';

		delete_transient( self::PROBE_TRANSIENT . $token );

		if ( $received !== $token ) {
			$reason = self::diagnose_mismatch( $token, $received );
			return [
				'success' => false,
				'reason'  => $reason,
				'message' => self::reason_message( $reason ),
				'repair'  => self::repair_for_reason( $reason ),
			];
		}

		NGCPM_Logger::log( 'cookie_probe', 'Cookie probe passed', [ 'reason' => 'ok' ] );

		return [
			'success' => true,
			'reason'  => 'ok',
			'message' => __( 'Cookie probe passed — browser and server exchange verified.', 'nextgentutors-plugin-manager' ),
			'checks'  => self::run_checks(),
		];
	}

	/**
	 * @param string $expected Expected token.
	 * @param string $received Received token.
	 * @return string
	 */
	private static function diagnose_mismatch( $expected, $received ) {
		if ( '' === $received ) {
			return 'cookie_not_returned_to_server';
		}
		if ( is_ssl() === false ) {
			return 'secure_cookie_on_http';
		}
		$path = COOKIEPATH ? COOKIEPATH : '/';
		if ( $path !== '/' && 0 !== strpos( admin_url(), home_url( $path ) ) ) {
			return 'path_mismatch';
		}
		if ( COOKIE_DOMAIN && false === strpos( wp_parse_url( home_url(), PHP_URL_HOST ), COOKIE_DOMAIN ) ) {
			return 'domain_mismatch';
		}
		return 'samesite_issue';
	}

	/**
	 * @param string $reason Reason key.
	 * @return string
	 */
	public static function reason_message( $reason ) {
		$messages = [
			'browser_blocked_cookie'        => __( 'Browser did not store the probe cookie (blocked or third-party restrictions).', 'nextgentutors-plugin-manager' ),
			'server_did_not_set_cookie'     => __( 'Server could not set the probe cookie (headers sent or setcookie failed).', 'nextgentutors-plugin-manager' ),
			'cookie_not_returned_to_server' => __( 'Browser stored the cookie but it was not sent back on the next request.', 'nextgentutors-plugin-manager' ),
			'secure_cookie_on_http'         => __( 'Secure cookie flag is set but the site is not served over HTTPS.', 'nextgentutors-plugin-manager' ),
			'samesite_issue'                => __( 'SameSite cookie policy may be blocking cross-context delivery.', 'nextgentutors-plugin-manager' ),
			'path_mismatch'                 => __( 'Cookie path does not match the admin request path.', 'nextgentutors-plugin-manager' ),
			'domain_mismatch'               => __( 'Cookie domain does not match the current host.', 'nextgentutors-plugin-manager' ),
			'consent_missing'               => __( 'Consent cookie not present (expected until visitor accepts on frontend).', 'nextgentutors-plugin-manager' ),
		];
		return $messages[ $reason ] ?? __( 'Cookie probe failed.', 'nextgentutors-plugin-manager' );
	}

	/**
	 * @param string $reason Reason key.
	 * @return string
	 */
	public static function repair_for_reason( $reason ) {
		$map = [
			'cookie_not_returned_to_server' => __( 'Ensure admin-ajax uses same-origin credentials and no cache strips Cookie headers.', 'nextgentutors-plugin-manager' ),
			'secure_cookie_on_http'         => __( 'Serve the site over HTTPS or adjust FORCE_SSL_ADMIN / cookie secure flags.', 'nextgentutors-plugin-manager' ),
			'path_mismatch'                 => __( 'Review COOKIEPATH in wp-config.php — it must cover /wp-admin/.', 'nextgentutors-plugin-manager' ),
			'domain_mismatch'               => __( 'Review COOKIE_DOMAIN — use empty string for single-host installs.', 'nextgentutors-plugin-manager' ),
			'samesite_issue'                => __( 'Avoid cross-site admin iframes; keep SameSite=Lax for admin probes.', 'nextgentutors-plugin-manager' ),
			'consent_missing'               => __( 'Consent is frontend-only; configure NextGen Companion cookie banner.', 'nextgentutors-plugin-manager' ),
		];
		return $map[ $reason ] ?? __( 'Re-run the probe after clearing browser cookies for this site.', 'nextgentutors-plugin-manager' );
	}

	/**
	 * @param string               $id           Check id.
	 * @param string               $status       Status.
	 * @param string               $evidence     Evidence.
	 * @param string               $recommendation Recommendation.
	 * @param array<int, string>   $reasons      Reason codes.
	 * @return array<string, mixed>
	 */
	private static function row( $id, $status, $evidence, $recommendation = '', $reasons = [] ) {
		return [
			'id'             => $id,
			'name'           => $id,
			'status'         => $status,
			'evidence'       => $evidence,
			'recommendation' => $recommendation,
			'reasons'        => $reasons,
		];
	}
}
