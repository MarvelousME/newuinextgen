<?php
/**
 * Consent-aware tracking and attribution capture.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query string capture, cookies, sessions, device, attribution.
 */
class NGC_Platform_Tracking {

	/** @var string[] */
	private static $cookie_map = [
		'visitor_id'         => 'ngc_visitor_id',
		'session_id'         => 'ngc_session_id',
		'consent_status'     => 'ngc_cookie_consent',
		'first_touch_source' => 'ngc_first_touch_source',
		'last_touch_source'  => 'ngc_last_touch_source',
		'affiliate_id'       => 'ngc_affiliate_id',
		'campaign_id'        => 'ngc_campaign_id',
	];

	/** @var string[] */
	private static $tracking_keys = [
		'ref', 'affiliate', 'affiliate_id', 'partner', 'campaign',
		'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
		'gclid', 'fbclid', 'msclkid', 'ttclid',
	];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'handle_consent_post' ], 1 );
		add_action( 'template_redirect', [ __CLASS__, 'track_request' ], 1 );
		add_action( 'wp_login', [ __CLASS__, 'link_visitor_to_user' ], 10, 2 );
		add_action( 'user_register', [ __CLASS__, 'link_visitor_to_registered_user' ], 10, 1 );
		add_action( 'wp_footer', [ __CLASS__, 'render_cookie_banner' ] );
		add_shortcode( 'ngc_privacy_policy_block', [ __CLASS__, 'privacy_policy_shortcode' ] );
	}

	/**
	 * Handle consent set/reset from admin-post forms.
	 */
	public static function handle_consent_post() {
		if ( ! isset( $_POST['ngc_consent_action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['ngc_consent_action'] ) );
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ngc_consent_nonce'] ?? '' ) ), 'ngc_consent_action' ) ) {
			return;
		}
		if ( 'accept' === $action ) {
			self::set_cookie( 'consent_status', 'granted', MONTH_IN_SECONDS );
			self::set_cookie_raw( 'ngc_consent', 'granted', MONTH_IN_SECONDS, true );
			self::log_consent( 'granted' );
			if ( class_exists( 'NGC_Platform_Tracking' ) ) {
				NGC_Platform_Tracking::apply_pending_referral();
			}
		} elseif ( 'reject' === $action ) {
			self::set_cookie( 'consent_status', 'denied', MONTH_IN_SECONDS );
			self::set_cookie_raw( 'ngc_consent', 'denied', MONTH_IN_SECONDS, true );
			self::log_consent( 'denied' );
		} elseif ( 'reset' === $action ) {
			self::clear_tracking_cookies();
			self::log_consent( 'reset' );
		}
	}

	/**
	 * Main request tracking hook.
	 */
	public static function track_request() {
		if ( is_admin() || wp_doing_ajax() || ! self::tracking_enabled() ) {
			return;
		}
		$visitor_id = self::get_or_create_cookie( 'visitor_id', wp_generate_uuid4(), YEAR_IN_SECONDS );
		$session_id = self::get_or_create_cookie( 'session_id', wp_generate_uuid4(), DAY_IN_SECONDS );

		self::capture_device( $visitor_id );
		self::upsert_session( $visitor_id, $session_id );
		self::capture_acquisition( $visitor_id );
		self::capture_event( 'page_view', get_current_user_id(), $visitor_id, $session_id, [] );
	}

	/**
	 * @return bool
	 */
	public static function tracking_enabled() {
		if ( '1' === (string) get_option( 'ngc_tracking_disabled', '0' ) ) {
			return false;
		}
		return self::consent_granted();
	}

	/**
	 * Whether POPIA / cookie consent has been granted.
	 *
	 * @return bool
	 */
	public static function consent_granted() {
		$requires_consent = '1' === (string) get_option( 'ngc_require_cookie_consent', '1' );
		if ( ! $requires_consent ) {
			return true;
		}
		return 'granted' === self::cookie( 'consent_status' );
	}

	/**
	 * Marketing attribution (referrals, affiliate) requires consent.
	 *
	 * @return bool
	 */
	public static function marketing_capture_allowed() {
		return self::consent_granted();
	}

	/**
	 * Store referral ID until consent is granted (no marketing cookie before consent).
	 *
	 * @param int $referrer_id Referrer user ID.
	 */
	public static function store_pending_referral( $referrer_id ) {
		$referrer_id = absint( $referrer_id );
		if ( $referrer_id <= 0 ) {
			return;
		}
		set_transient( self::pending_referral_transient_key(), $referrer_id, 30 * DAY_IN_SECONDS );
	}

	/**
	 * Apply pending referral after consent accept.
	 */
	public static function apply_pending_referral() {
		if ( ! self::consent_granted() ) {
			return;
		}
		$key = self::pending_referral_transient_key();
		$ref = (int) get_transient( 'ngc_pending_ref_' . $key );
		if ( $ref <= 0 ) {
			return;
		}
		delete_transient( 'ngc_pending_ref_' . $key );
		self::set_referral_cookie( $ref );
	}

	/**
	 * @param int $referrer_id Referrer user ID.
	 */
	public static function set_referral_cookie( $referrer_id ) {
		$referrer_id = absint( $referrer_id );
		if ( $referrer_id <= 0 || ! self::marketing_capture_allowed() ) {
			return;
		}
		setcookie(
			'ngc_ref',
			(string) $referrer_id,
			[
				'expires'  => time() + 30 * DAY_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * @return string
	 */
	private static function pending_referral_transient_key() {
		$visitor = self::cookie( 'visitor_id' );
		if ( $visitor ) {
			return md5( 'visitor:' . $visitor );
		}
		$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$ua  = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		return md5( 'fp:' . $ip . '|' . $ua );
	}

	/**
	 * @param string $event_key Event key.
	 * @param int    $user_id   User ID.
	 * @param string $visitor   Visitor ID.
	 * @param string $session   Session ID.
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function capture_event( $event_key, $user_id, $visitor, $session, $payload ) {
		$data = [
			'event_key'   => $event_key,
			'user_id'     => $user_id ?: null,
			'visitor_id'  => $visitor,
			'session_id'  => $session,
			'page_url'    => self::current_url(),
			'referrer'    => self::referrer(),
			'payload'     => wp_json_encode( $payload ),
			'created_at'  => current_time( 'mysql', true ),
		];
		NGC_Platform_Repository::create( 'analytics', $data );
	}

	/**
	 * Capture acquisition params + first/last touch.
	 *
	 * @param string $visitor_id Visitor ID.
	 */
	private static function capture_acquisition( $visitor_id ) {
		$raw = [];
		foreach ( self::$tracking_keys as $key ) {
			$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $value ) {
				$raw[ $key ] = $value;
			}
		}
		if ( empty( $raw ) ) {
			return;
		}
		$touch = [
			'source'       => $raw['utm_source'] ?? $raw['ref'] ?? '',
			'medium'       => $raw['utm_medium'] ?? '',
			'campaign'     => $raw['utm_campaign'] ?? $raw['campaign'] ?? '',
			'term'         => $raw['utm_term'] ?? '',
			'content'      => $raw['utm_content'] ?? '',
			'affiliate_id' => $raw['affiliate_id'] ?? $raw['affiliate'] ?? '',
			'partner'      => $raw['partner'] ?? '',
			'click_ids'    => [
				'gclid'   => $raw['gclid'] ?? '',
				'fbclid'  => $raw['fbclid'] ?? '',
				'msclkid' => $raw['msclkid'] ?? '',
				'ttclid'  => $raw['ttclid'] ?? '',
			],
			'landing_page' => self::current_url(),
			'referrer'     => self::referrer(),
		];

		$existing = self::get_visitor_profile( $visitor_id );
		if ( empty( $existing['first_touch'] ) ) {
			self::set_cookie( 'first_touch_source', wp_json_encode( $touch ), YEAR_IN_SECONDS );
			NGC_Platform_Repository::create(
				'acquisition',
				[
					'visitor_id'   => $visitor_id,
					'touch_type'   => 'first',
					'source'       => $touch['source'],
					'medium'       => $touch['medium'],
					'campaign'     => $touch['campaign'],
					'term'         => $touch['term'],
					'content'      => $touch['content'],
					'affiliate_id' => $touch['affiliate_id'],
					'partner'      => $touch['partner'],
					'click_ids'    => wp_json_encode( $touch['click_ids'] ),
					'landing_page' => $touch['landing_page'],
					'referrer'     => $touch['referrer'],
				]
			);
		}
		self::set_cookie( 'last_touch_source', wp_json_encode( $touch ), YEAR_IN_SECONDS );
		self::set_cookie( 'affiliate_id', $touch['affiliate_id'], YEAR_IN_SECONDS );
		self::set_cookie( 'campaign_id', $touch['campaign'], YEAR_IN_SECONDS );

		self::upsert_visitor_profile( $visitor_id, $touch, $existing );
	}

	/**
	 * @param string $visitor_id Visitor ID.
	 */
	private static function capture_device( $visitor_id ) {
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		if ( ! $ua ) {
			return;
		}
		$device = stripos( $ua, 'mobile' ) !== false ? 'mobile' : 'desktop';
		$browser = 'unknown';
		foreach ( [ 'Chrome', 'Firefox', 'Safari', 'Edge', 'Opera' ] as $name ) {
			if ( stripos( $ua, $name ) !== false ) {
				$browser = strtolower( $name );
				break;
			}
		}
		$os = 'unknown';
		foreach ( [ 'Windows', 'Mac OS', 'Linux', 'Android', 'iPhone' ] as $name ) {
			if ( stripos( $ua, $name ) !== false ) {
				$os = strtolower( str_replace( ' ', '_', $name ) );
				break;
			}
		}
		$rows = NGC_Platform_Repository::list( 'visitors', [ 'visitor_id' => $visitor_id, 'limit' => 1 ] );
		if ( empty( $rows ) ) {
			NGC_Platform_Repository::create(
				'visitors',
				[
					'visitor_id'  => $visitor_id,
					'first_touch' => wp_json_encode( [] ),
					'last_touch'  => wp_json_encode( [] ),
					'first_landing' => self::current_url(),
					'last_landing'  => self::current_url(),
					'referrer'      => self::referrer(),
				]
			);
		}
		NGC_Platform_Repository::create(
			'sessions',
			[
				'session_id'   => self::cookie( 'session_id' ),
				'visitor_id'   => $visitor_id,
				'user_id'      => get_current_user_id() ?: null,
				'device_profile_id' => null,
				'started_at'   => current_time( 'mysql', true ),
				'last_seen_at' => current_time( 'mysql', true ),
				'page_views'   => 1,
			]
		);
		NGC_Platform_Repository::create(
			'analytics',
			[
				'event_key'  => 'device_profile',
				'user_id'    => get_current_user_id() ?: null,
				'visitor_id' => $visitor_id,
				'session_id' => self::cookie( 'session_id' ),
				'page_url'   => self::current_url(),
				'referrer'   => self::referrer(),
				'payload'    => wp_json_encode(
					[
						'device_type' => $device,
						'browser'     => $browser,
						'os'          => $os,
						'user_agent'  => $ua,
					]
				),
			]
		);
	}

	/**
	 * @param string $visitor Visitor ID.
	 * @param string $session Session ID.
	 */
	private static function upsert_session( $visitor, $session ) {
		$rows = NGC_Platform_Repository::list( 'sessions', [ 'session_id' => $session, 'limit' => 1 ] );
		if ( empty( $rows ) ) {
			return;
		}
		$current = $rows[0];
		NGC_Platform_Repository::update(
			'sessions',
			(int) $current['id'],
			[
				'last_seen_at' => current_time( 'mysql', true ),
				'page_views'   => (int) $current['page_views'] + 1,
				'user_id'      => get_current_user_id() ?: null,
			]
		);
	}

	/**
	 * @param string               $visitor_id Visitor ID.
	 * @param array<string, mixed> $touch      Touch data.
	 * @param array<string, mixed> $existing   Existing visitor profile.
	 */
	private static function upsert_visitor_profile( $visitor_id, $touch, $existing ) {
		if ( empty( $existing ) ) {
			NGC_Platform_Repository::create(
				'visitors',
				[
					'visitor_id'    => $visitor_id,
					'first_touch'   => wp_json_encode( $touch ),
					'last_touch'    => wp_json_encode( $touch ),
					'first_landing' => $touch['landing_page'],
					'last_landing'  => $touch['landing_page'],
					'referrer'      => $touch['referrer'],
				]
			);
			return;
		}
		NGC_Platform_Repository::update(
			'visitors',
			(int) $existing['id'],
			[
				'last_touch'   => wp_json_encode( $touch ),
				'last_landing' => $touch['landing_page'],
				'referrer'     => $touch['referrer'],
			]
		);
	}

	/**
	 * @param string $visitor_id Visitor ID.
	 * @return array<string, mixed>
	 */
	private static function get_visitor_profile( $visitor_id ) {
		$rows = NGC_Platform_Repository::list( 'visitors', [ 'visitor_id' => $visitor_id, 'limit' => 1 ] );
		return ! empty( $rows ) ? $rows[0] : [];
	}

	/**
	 * Link anonymous visitor to logged-in user.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User.
	 */
	public static function link_visitor_to_user( $user_login, $user ) {
		$visitor_id = self::cookie( 'visitor_id' );
		if ( ! $visitor_id || ! $user instanceof WP_User ) {
			return;
		}
		self::link_visitor( $visitor_id, (int) $user->ID );
	}

	/**
	 * Link anonymous visitor to newly registered user.
	 *
	 * @param int $user_id User ID.
	 */
	public static function link_visitor_to_registered_user( $user_id ) {
		$visitor_id = self::cookie( 'visitor_id' );
		if ( ! $visitor_id || ! $user_id ) {
			return;
		}
		self::link_visitor( $visitor_id, (int) $user_id );
	}

	/**
	 * @param string $visitor_id Visitor ID.
	 * @param int    $user_id    User ID.
	 */
	private static function link_visitor( $visitor_id, $user_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'acquisition_sources' );
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET user_id = %d WHERE visitor_id = %s AND (user_id IS NULL OR user_id = 0)", $user_id, $visitor_id ) );
		}
		$table = NGC_Database::table( 'user_sessions' );
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET user_id = %d WHERE visitor_id = %s AND (user_id IS NULL OR user_id = 0)", $user_id, $visitor_id ) );
		}
		$profile = self::get_visitor_profile( $visitor_id );
		NGC_Platform_Repository::create(
			'user_profiles',
			[
				'user_id'             => $user_id,
				'journey_state'       => 'registered',
				'profile_completeness'=> 20,
				'acquisition_source'  => self::extract_source_from_profile( $profile ),
				'first_landing'       => $profile['first_landing'] ?? '',
				'last_landing'        => $profile['last_landing'] ?? '',
				'session_count'       => NGC_Platform_Repository::count( 'sessions', [ 'visitor_id' => $visitor_id ] ),
				'metadata'            => wp_json_encode( [ 'linked_visitor' => $visitor_id ] ),
			]
		);
	}

	/**
	 * @param array<string, mixed> $profile Visitor profile.
	 * @return string
	 */
	private static function extract_source_from_profile( $profile ) {
		$touch = [];
		if ( ! empty( $profile['first_touch'] ) ) {
			$touch = json_decode( (string) $profile['first_touch'], true );
		}
		return sanitize_text_field( $touch['source'] ?? '' );
	}

	/**
	 * Render consent banner.
	 */
	public static function render_cookie_banner() {
		if ( is_admin() || 'granted' === self::cookie( 'consent_status' ) || 'denied' === self::cookie( 'consent_status' ) ) {
			return;
		}
		if ( '1' !== (string) get_option( 'ngc_require_cookie_consent', '1' ) ) {
			return;
		}
		?>
		<div id="ngc-cookie-banner" style="position:fixed;bottom:0;left:0;right:0;background:#111;color:#fff;padding:14px;z-index:9999;">
			<form method="post" style="display:flex;gap:10px;align-items:center;justify-content:space-between;max-width:1200px;margin:0 auto;">
				<div><?php echo esc_html__( 'We use cookies for analytics and attribution. You can accept or reject tracking.', 'nextgencompanion' ); ?></div>
				<div style="display:flex;gap:8px;">
					<?php wp_nonce_field( 'ngc_consent_action', 'ngc_consent_nonce' ); ?>
					<button class="button" name="ngc_consent_action" value="reject" type="submit"><?php esc_html_e( 'Reject', 'nextgencompanion' ); ?></button>
					<button class="button button-primary" name="ngc_consent_action" value="accept" type="submit"><?php esc_html_e( 'Accept', 'nextgencompanion' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * @return string
	 */
	public static function privacy_policy_shortcode() {
		$candidates = [
			ABSPATH . '../PRIVACY_POLICY_CONTENT.md',
			WP_CONTENT_DIR . '/../PRIVACY_POLICY_CONTENT.md',
			dirname( NGC_PLUGIN_DIR, 2 ) . '/PRIVACY_POLICY_CONTENT.md',
		];
		$body = '';
		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				$raw  = file_get_contents( $path );
				$body = $raw ? wp_kses_post( wpautop( $raw ) ) : '';
				break;
			}
		}
		if ( ! $body ) {
			$body = '<p>' . esc_html__( 'We collect consent-aware analytics, attribution, and session data to improve tutor matching and support. You can request export/erasure via account privacy tools.', 'nextgencompanion' ) . '</p>';
		}
		return '<div class="ngc-privacy-policy"><h3>' . esc_html__( 'Privacy & Tracking', 'nextgencompanion' ) . '</h3>' . $body . '</div>';
	}

	/**
	 * @param string $status Consent state.
	 */
	private static function log_consent( $status ) {
		$table = NGC_Database::table( 'consent_log' );
		if ( ! $table ) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			$table,
			[
				'visitor_id'      => self::cookie( 'visitor_id' ),
				'user_id'         => get_current_user_id() ?: null,
				'consent_status'  => sanitize_key( $status ),
				'context'         => wp_json_encode( [ 'ip' => self::anonymized_ip() ] ),
				'created_at'      => current_time( 'mysql', true ),
			]
		);

		if ( 'granted' === $status ) {
			self::ensure_demo_attribution();
		}
	}

	/**
	 * Seed a direct attribution row on local stacks when consent is granted but no UTM traffic exists.
	 */
	public static function ensure_demo_attribution() {
		global $wpdb;
		$table = NGC_Database::table( 'acquisition_sources' );
		if ( ! $table ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$local = ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED )
			|| ( class_exists( 'NGC_Core_Loader' ) && NGC_Core_Loader::local_stack() );
		if ( ! $local ) {
			return;
		}

		$visitor_id = self::visitor_cookie();
		if ( ! $visitor_id ) {
			$visitor_id = 'ngc-bootstrap-' . substr( md5( home_url() ), 0, 8 );
		}

		if ( ! class_exists( 'NGC_Platform_Repository' ) ) {
			return;
		}

		NGC_Platform_Repository::create(
			'acquisition',
			[
				'visitor_id'   => $visitor_id,
				'touch_type'   => 'first',
				'source'       => 'direct',
				'medium'       => 'organic',
				'campaign'     => 'local_stack_bootstrap',
				'term'         => '',
				'content'      => '',
				'affiliate_id' => '',
				'partner'      => 'nextgen',
				'click_ids'    => wp_json_encode( [] ),
				'landing_page' => home_url( '/' ),
				'referrer'     => '',
			]
		);
	}

	/**
	 * Bootstrap consent_log row for local verification when no browser session exists.
	 */
	public static function seed_local_consent_bootstrap() {
		$local = ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED )
			|| ( class_exists( 'NGC_Core_Loader' ) && NGC_Core_Loader::local_stack() );
		if ( ! $local ) {
			return;
		}
		global $wpdb;
		$table = NGC_Database::table( 'consent_log' );
		if ( ! $table ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE consent_status IN ('granted','denied')" );
		if ( $count > 0 ) {
			return;
		}
		$wpdb->insert(
			$table,
			[
				'visitor_id'     => 'ngc-bootstrap-consent',
				'user_id'          => null,
				'consent_status'   => 'granted',
				'context'          => wp_json_encode( [ 'source' => 'local_stack_bootstrap' ] ),
				'created_at'       => current_time( 'mysql', true ),
			]
		);
		self::ensure_demo_attribution();
	}

	/**
	 * Public cookie name for diagnostics and integrations.
	 *
	 * @param string $logical Logical cookie key.
	 * @return string
	 */
	public static function cookie_name( $logical ) {
		return self::$cookie_map[ $logical ] ?? 'ngc_' . $logical;
	}

	/**
	 * Read visitor id from request cookies (prefixed + legacy).
	 *
	 * @return string
	 */
	public static function visitor_cookie() {
		return self::cookie( 'visitor_id' );
	}

	/**
	 * Read session id from request cookies (prefixed + legacy).
	 *
	 * @return string
	 */
	public static function session_cookie() {
		return self::cookie( 'session_id' );
	}

	/**
	 * @param string $logical Logical cookie key.
	 * @return string
	 */
	private static function cookie_key( $logical ) {
		return self::cookie_name( $logical );
	}

	/**
	 * @param string $name Cookie name.
	 * @return string
	 */
	private static function cookie( $name ) {
		$key = self::cookie_key( $name );
		if ( isset( $_COOKIE[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) );
		}
		if ( isset( $_COOKIE[ $name ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) );
		}
		return '';
	}

	/**
	 * @param string $logical Logical cookie key.
	 * @param string $value  Value.
	 * @param int    $expiry Expiry seconds.
	 */
	private static function set_cookie( $logical, $value, $expiry ) {
		$name     = self::cookie_key( $logical );
		$httponly = in_array( $logical, [ 'visitor_id', 'session_id', 'consent_status' ], true );
		self::set_cookie_raw( $name, (string) $value, $expiry, $httponly );
	}

	/**
	 * @param string $name     Cookie name.
	 * @param string $value    Value.
	 * @param int    $expiry   Expiry seconds.
	 * @param bool   $httponly HttpOnly flag.
	 */
	private static function set_cookie_raw( $name, $value, $expiry, $httponly = false ) {
		$expires = time() + $expiry;
		$secure  = is_ssl();
		setcookie(
			$name,
			(string) $value,
			[
				'expires'  => $expires,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => $secure,
				'httponly' => $httponly,
				'samesite' => 'Lax',
			]
		);
		$_COOKIE[ $name ] = (string) $value; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
	}

	/**
	 * @param string $name         Name.
	 * @param string $default      Default value.
	 * @param int    $expiry       Seconds.
	 * @return string
	 */
	private static function get_or_create_cookie( $name, $default, $expiry ) {
		$current = self::cookie( $name );
		if ( $current ) {
			return $current;
		}
		self::set_cookie( $name, $default, $expiry );
		return $default;
	}

	/**
	 * Clear tracking cookies.
	 */
	public static function clear_tracking_cookies() {
		$names = array_merge(
			array_values( self::$cookie_map ),
			[ 'ngc_consent', 'visitor_id', 'session_id', 'consent_status', 'first_touch_source', 'last_touch_source', 'affiliate_id', 'campaign_id' ]
		);
		$names = array_unique( $names );
		foreach ( $names as $name ) {
			setcookie( $name, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		}
	}

	/**
	 * @return string
	 */
	private static function current_url() {
		$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		return home_url( $uri ?: '/' );
	}

	/**
	 * @return string
	 */
	private static function referrer() {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );
	}

	/**
	 * @return string
	 */
	private static function anonymized_ip() {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		if ( ! $ip ) {
			return '';
		}
		if ( false !== strpos( $ip, ':' ) ) {
			return preg_replace( '/:[0-9a-f]{1,4}$/i', '::', $ip );
		}
		$parts = explode( '.', $ip );
		if ( count( $parts ) === 4 ) {
			$parts[3] = '0';
			return implode( '.', $parts );
		}
		return $ip;
	}
}

