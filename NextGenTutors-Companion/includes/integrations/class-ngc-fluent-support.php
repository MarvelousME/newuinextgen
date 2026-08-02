<?php
/**
 * Fluent Support integration — seed, localize dock, bridge portal.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end + bootstrap hooks for Fluent Support.
 */
class NGC_Fluent_Support {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_bootstrap' ], 25 );
		add_action( 'ngc_business_profile_applied', [ __CLASS__, 'on_business_profile' ], 20, 2 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'localize_dock' ], 40 );
		add_filter( 'script_loader_tag', [ __CLASS__, 'mark_floating_defer' ], 10, 2 );
	}

	/**
	 * Seed mailbox when Fluent Support is active (idempotent).
	 */
	public static function maybe_bootstrap() {
		if ( ! class_exists( 'NGC_FluentSupport_Adapter' ) ) {
			return;
		}
		$adapter = new NGC_FluentSupport_Adapter();
		if ( ! $adapter->is_available() ) {
			return;
		}
		if ( get_option( NGC_FluentSupport_Adapter::OPTION_SEEDED ) && (int) get_option( NGC_FluentSupport_Adapter::OPTION_MAILBOX_ID, 0 ) > 0 ) {
			return;
		}
		try {
			$adapter->bootstrap_assets( true );
		} catch ( Throwable $e ) {
			error_log( 'NGC Fluent Support bootstrap skipped: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Re-seed mailbox when business profile is applied.
	 *
	 * @param array<string, mixed> $company Company option.
	 * @param array<string, mixed> $b       Business block.
	 */
	public static function on_business_profile( $company, $b ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'NGC_FluentSupport_Adapter' ) ) {
			return;
		}
		$adapter = new NGC_FluentSupport_Adapter();
		if ( $adapter->is_available() ) {
			$adapter->bootstrap_assets( true );
		}
	}

	/**
	 * Expose support + RTM config to floating dock scripts.
	 */
	public static function localize_dock() {
		if ( ! wp_script_is( 'bi-ngt-wp-bridge', 'enqueued' ) && ! wp_script_is( 'bi-ngt-floating', 'enqueued' ) ) {
			return;
		}

		$adapter    = class_exists( 'NGC_FluentSupport_Adapter' ) ? new NGC_FluentSupport_Adapter() : null;
		$mailbox_id = (int) get_option( NGC_FluentSupport_Adapter::OPTION_MAILBOX_ID, 0 );
		$support_page = get_page_by_path( 'support' );
		$support_url  = $support_page ? get_permalink( $support_page ) : home_url( '/support/' );

		$rtm_ns = class_exists( 'NGT_Hub_Companion_Delegate', false )
			? NGT_Hub_Companion_Delegate::rest_namespace() . '/rtm'
			: ( class_exists( 'NGT_Hub_RTM', false ) ? 'ngt/v1/rtm' : '' );

		$company = class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::get() : [];
		$wa      = preg_replace( '/\D+/', '', (string) ( $company['whatsapp'] ?? '' ) );

		$data = [
			'support' => [
				'active'     => $adapter && $adapter->is_available(),
				'mailboxId'  => $mailbox_id,
				'portalUrl'  => $support_url,
				'ticketUrl'  => rest_url( 'ngc/v1/support/tickets' ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'email'      => (string) ( $company['email'] ?? get_option( 'admin_email' ) ),
				'shortcode'  => 'fluent_support_portal',
			],
			'rtm'     => [
				'active'   => (bool) $rtm_ns && class_exists( 'NGT_Hub_RTM', false ),
				'loggedIn' => is_user_logged_in(),
				'loginUrl' => wp_login_url( $support_url ),
				'rest'     => $rtm_ns ? untrailingslashit( rest_url( $rtm_ns ) ) : '',
				'rooms'    => $rtm_ns ? rest_url( $rtm_ns . '/rooms' ) : '',
				'messages' => $rtm_ns ? untrailingslashit( rest_url( $rtm_ns . '/messages' ) ) : '',
				'sse'      => $rtm_ns ? rest_url( $rtm_ns . '/stream' ) : '',
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'userId'   => get_current_user_id(),
				'userName' => wp_get_current_user()->display_name ?? '',
			],
			'waNumber'=> $wa ?: null,
		];

		$handle = wp_script_is( 'bi-ngt-wp-bridge', 'enqueued' ) ? 'bi-ngt-wp-bridge' : 'bi-ngt-floating';
		if ( wp_script_is( $handle, 'enqueued' ) || wp_script_is( $handle, 'registered' ) ) {
			wp_add_inline_script(
				$handle,
				'window.NGT_WP=Object.assign({},window.NGT_WP||{},' . wp_json_encode( $data ) . ');',
				'before'
			);
		}
	}

	/**
	 * @param string $tag    Script tag.
	 * @param string $handle Handle.
	 * @return string
	 */
	public static function mark_floating_defer( $tag, $handle ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return $tag;
	}
}
