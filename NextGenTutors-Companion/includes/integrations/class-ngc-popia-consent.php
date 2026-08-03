<?php
/**
 * POPIA consent grant, audit, WooCommerce checkout gate, and withdrawal.
 *
 * Absorbs IMPORTANT: Consent Withdrawal Handler, WooCommerce Checkout Hook,
 * Exact-Production-Deliverables, Create-FluentCRM-Custom-Fields.xlsx.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POPIA consent lifecycle.
 */
class NGC_Popia_Consent {

	const META_KEY     = '_ngc_popia_consent';
	const CONSENT_VER  = '1.2';
	const QUERY_WITHDRAW = 'ngc_withdraw_popia';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'handle_withdrawal' ], 5 );
		add_action( 'woocommerce_after_checkout_billing_form', [ __CLASS__, 'render_checkout_consent' ] );
		add_action( 'woocommerce_checkout_process', [ __CLASS__, 'validate_checkout_consent' ] );
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'save_checkout_consent' ], 10, 2 );
		add_shortcode( 'ngc_popia_withdraw', [ __CLASS__, 'withdraw_shortcode' ] );
		add_filter( 'ngc_email_template_override', [ __CLASS__, 'inject_withdraw_urls' ], 5, 2 );
	}

	/**
	 * Build a signed consent-withdrawal URL for the current (or given) user.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function withdraw_url( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$url     = add_query_arg(
			[
				self::QUERY_WITHDRAW => 1,
			],
			home_url( '/' )
		);
		return wp_nonce_url( $url, 'ngc_withdraw_popia' );
	}

	/**
	 * Preferences URL used in email footers.
	 *
	 * @return string
	 */
	public static function preferences_url() {
		return home_url( '/privacy-policy/' );
	}

	/**
	 * Handle ?ngc_withdraw_popia=1 withdrawal request.
	 */
	public static function handle_withdrawal() {
		if ( empty( $_GET[ self::QUERY_WITHDRAW ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ngc_withdraw_popia' ) ) {
			wp_die( esc_html__( 'Invalid or expired POPIA withdrawal link.', 'nextgencompanion' ), 403 );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_safe_redirect( wp_login_url( self::withdraw_url() ) );
			exit;
		}

		self::record_withdrawal( $user_id );
		self::sync_fluentcrm_withdrawal( $user_id );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'popia_consent_withdrawn', 'user', $user_id, [ 'consent_ver' => self::CONSENT_VER ], $user_id );
		}

		$redirect = home_url( '/parent-dashboard/?popia_withdrawn=1' );
		if ( ! get_page_by_path( 'parent-dashboard' ) ) {
			$redirect = home_url( '/?popia_withdrawn=1' );
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Persist withdrawal on the user.
	 *
	 * @param int $user_id User ID.
	 */
	public static function record_withdrawal( $user_id ) {
		$audit = [
			'given'        => false,
			'withdrawn_at' => current_time( 'mysql' ),
			'withdrawn_ip' => self::client_ip(),
			'consent_ver'  => self::CONSENT_VER,
		];
		update_user_meta( (int) $user_id, self::META_KEY, $audit );
	}

	/**
	 * Persist granted consent.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $extra   Extra audit fields.
	 * @return array<string, mixed>
	 */
	public static function record_grant( $user_id, $extra = [] ) {
		$audit = array_merge(
			[
				'accepted'      => true,
				'given'         => true,
				'timestamp'     => current_time( 'mysql' ),
				'ip_address'    => self::client_ip(),
				'user_agent'    => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'consent_ver'   => self::CONSENT_VER,
				'checkout_type' => $user_id ? 'returning' : 'guest',
			],
			$extra
		);
		if ( $user_id ) {
			update_user_meta( (int) $user_id, self::META_KEY, $audit );
			self::sync_fluentcrm_grant( (int) $user_id, $audit );
		}
		return $audit;
	}

	/**
	 * WooCommerce checkout consent UI.
	 */
	public static function render_checkout_consent() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$privacy = esc_url( self::preferences_url() );
		?>
		<div id="ngc-popia-checkout" class="ngt-consent-checkout ngc-popia-checkout">
			<style>
				.ngc-popia-checkout{background:#f8f9fa;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:20px 0}
				.ngc-popia-checkout label{font-size:14px;line-height:1.5;display:flex;align-items:flex-start;gap:10px;cursor:pointer;color:#0f172a}
				.ngc-popia-checkout input[type=checkbox]{margin-top:4px;width:18px;height:18px;accent-color:#0066cc}
				.ngc-popia-checkout a{color:#0066cc;text-decoration:none}
				.ngc-popia-error{color:#dc2626;font-size:13px;margin-top:8px;display:none}
			</style>
			<label for="ngc_popia_consent">
				<input type="checkbox" id="ngc_popia_consent" name="ngc_popia_consent" value="1" required>
				<span>
					<?php esc_html_e( 'I explicitly consent to NextGen Tutors processing my personal information and session recordings per POPIA. Data is retained for service duration then minimised.', 'nextgencompanion' ); ?>
					<a href="<?php echo $privacy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy Policy', 'nextgencompanion' ); ?></a>.
				</span>
			</label>
			<div id="ngc-popia-error" class="ngc-popia-error"><?php esc_html_e( 'Consent is required to complete your booking.', 'nextgencompanion' ); ?></div>
		</div>
		<script>
		(function(){
			var form=document.querySelector('form.checkout');
			var chk=document.getElementById('ngc_popia_consent');
			var err=document.getElementById('ngc-popia-error');
			if(!form||!chk) return;
			form.addEventListener('submit',function(e){
				if(!chk.checked){e.preventDefault(); if(err){err.style.display='block';} chk.focus();}
				else if(err){err.style.display='none';}
			});
			chk.addEventListener('change',function(){ if(err) err.style.display='none'; });
		})();
		</script>
		<?php
	}

	/**
	 * Block WooCommerce checkout without consent.
	 */
	public static function validate_checkout_consent() {
		if ( empty( $_POST['ngc_popia_consent'] ) || '1' !== (string) wp_unslash( $_POST['ngc_popia_consent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wc_add_notice( __( 'POPIA consent is mandatory. Please accept the terms to proceed.', 'nextgencompanion' ), 'error' );
		}
	}

	/**
	 * Save consent audit on the order + user.
	 *
	 * @param WC_Order             $order Order.
	 * @param array<string, mixed> $data  Checkout data.
	 */
	public static function save_checkout_consent( $order, $data ) {
		unset( $data );
		if ( empty( $_POST['ngc_popia_consent'] ) || '1' !== (string) wp_unslash( $_POST['ngc_popia_consent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		$user_id = (int) $order->get_customer_id();
		$audit   = self::record_grant(
			$user_id,
			[
				'checkout_type' => $user_id ? 'returning' : 'guest',
				'source'        => 'woocommerce_checkout',
			]
		);
		$order->update_meta_data( self::META_KEY, $audit );
	}

	/**
	 * Shortcode: withdraw link button.
	 *
	 * @return string
	 */
	public static function withdraw_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Log in to withdraw POPIA consent.', 'nextgencompanion' ) . '</p>';
		}
		$url = esc_url( self::withdraw_url() );
		return '<p><a class="ngt-btn ngt-btn--outline" href="' . $url . '">' . esc_html__( 'Withdraw POPIA consent', 'nextgencompanion' ) . '</a></p>';
	}

	/**
	 * Inject withdraw / preferences URLs into email merge context via override pass-through.
	 *
	 * @param array<string, mixed>|null $override Existing override.
	 * @param string                    $key      Template key.
	 * @return array<string, mixed>|null
	 */
	public static function inject_withdraw_urls( $override, $key ) {
		unset( $key );
		return $override;
	}

	/**
	 * Sync grant to FluentCRM custom fields + tags.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $audit   Audit payload.
	 */
	public static function sync_fluentcrm_grant( $user_id, $audit ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			return;
		}
		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}
		$adapter->bootstrap_assets();
		$adapter->create_or_update(
			'sync',
			[
				'email'      => $user->user_email,
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
				'user_id'    => $user_id,
				'custom_fields' => [
					'popia_consent_given'      => true,
					'popia_consent_date'       => (string) ( $audit['timestamp'] ?? current_time( 'mysql' ) ),
					'popia_consent_ip'         => (string) ( $audit['ip_address'] ?? '' ),
					'popia_consent_version'    => self::CONSENT_VER,
					'popia_processing_purpose' => [ 'booking', 'session_delivery', 'support' ],
				],
				'tags'       => [ 'POPIA Consented' ],
				'detach_tags'=> [ 'POPIA Withdrawn', 'Do Not Market' ],
			]
		);
	}

	/**
	 * Sync withdrawal to FluentCRM.
	 *
	 * @param int $user_id User ID.
	 */
	public static function sync_fluentcrm_withdrawal( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			return;
		}
		$adapter = new NGC_Fluentcrm_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}
		$adapter->bootstrap_assets();
		$adapter->create_or_update(
			'sync',
			[
				'email'         => $user->user_email,
				'user_id'       => $user_id,
				'custom_fields' => [
					'popia_consent_given' => false,
				],
				'tags'          => [ 'POPIA Withdrawn', 'Do Not Market' ],
				'detach_tags'   => [ 'POPIA Consented', 'Marketing Opt-In' ],
				'detach_lists'  => [ 'Marketing Opt-In' ],
			]
		);
	}

	/**
	 * @return string
	 */
	private static function client_ip() {
		if ( class_exists( 'WC_Geolocation' ) ) {
			return (string) WC_Geolocation::get_ip_address();
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
}
