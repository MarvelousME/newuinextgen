<?php
/**
 * Production runtime for integrate/ workflow pack (WF-01..WF-05).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots integrate modules and bridges legacy JSON specs to ngc_* stack.
 */
class NGC_Integrate_Runtime {

	/**
	 * Hook registration.
	 */
	public static function init() {
		NGC_Workflow_Spec_Registry::all();
		if ( class_exists( 'NGC_Workflow_Integrate_Executor' ) ) {
			NGC_Workflow_Integrate_Executor::init();
		}
		if ( ! NGC_Workflow_Spec_Registry::get_store()['specs'] ) {
			NGC_Workflow_Spec_Registry::import_from_integrate_dir( true );
		}
		NGC_Workflow_Spec_Registry::import_from_catalog( false );
		NGC_Session_Reminders::init();
		NGC_Referrals::init();
		NGC_Payout_Scheduler::init();

		add_action( 'init', [ __CLASS__, 'capture_referral_cookie' ], 1 );
		add_action( 'ngc_review_submitted', [ __CLASS__, 'on_review_submitted' ], 10, 1 );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_payment_queue_reminders' ], 15, 2 );
		add_filter( 'ngc_workflow_vars', [ __CLASS__, 'enrich_workflow_vars' ], 10, 2 );

		do_action( 'ngc_integrate_runtime_ready' );
	}

	/**
	 * Persist ?ref= for referral attribution (30 days) — POPIA gated.
	 */
	public static function capture_referral_cookie() {
		if ( is_admin() || ! isset( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$ref = absint( wp_unslash( $_GET['ref'] ) );
		if ( $ref <= 0 ) {
			return;
		}
		if ( class_exists( 'NGC_Platform_Tracking' ) && NGC_Platform_Tracking::marketing_capture_allowed() ) {
			NGC_Platform_Tracking::set_referral_cookie( $ref );
			return;
		}
		if ( class_exists( 'NGC_Platform_Tracking' ) ) {
			NGC_Platform_Tracking::store_pending_referral( $ref );
		}
	}

	/**
	 * WF-04 — review submitted bridge.
	 *
	 * @param array<string, mixed> $review Review payload.
	 */
	public static function on_review_submitted( $review ) {
		NGC_Workflows::dispatch( 'review.submitted', $review );
	}

	/**
	 * WF-02 — queue reminders after successful payment.
	 *
	 * @param string               $full Event.
	 * @param array<string, mixed> $vars Context.
	 */
	public static function on_payment_queue_reminders( $full, $vars ) {
		if ( 'ngt.payment.received' !== $full && 'woocommerce.order.completed' !== $full ) {
			return;
		}
		NGC_Session_Reminders::queue_for_booking_context( $vars );
	}

	/**
	 * @param array<string, mixed> $vars     Vars.
	 * @param string               $workflow Workflow slug.
	 * @return array<string, mixed>
	 */
	public static function enrich_workflow_vars( $vars, $workflow ) {
		$company = class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::get() : [];
		$vars['site_name']     = (string) ( $company['company_name'] ?? get_bloginfo( 'name' ) );
		$vars['support_email'] = (string) ( $company['email'] ?? get_option( 'admin_email' ) );
		if ( ! empty( $company['phone'] ) ) {
			$vars['support_phone'] = (string) $company['phone'];
		}
		return $vars;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function status() {
		$specs = NGC_Workflow_Spec_Registry::verify();
		return [
			'ok'                => $specs['ok'],
			'specs_loaded'      => $specs['specs'],
			'events_catalogued' => $specs['events'],
			'stored_specs'      => $specs['stored'] ?? 0,
			'modules'           => [
				'reminders'   => class_exists( 'NGC_Session_Reminders' ),
				'referrals'   => class_exists( 'NGC_Referrals' ),
				'payout_cron'          => (bool) wp_next_scheduled( NGC_Payout_Scheduler::CRON_HOOK ),
				'payout_biweekly_cron' => (bool) wp_next_scheduled( NGC_Payout_Scheduler::CRON_HOOK_BIWEEKLY ),
				'reminder_cron' => (bool) wp_next_scheduled( NGC_Session_Reminders::CRON_HOOK ),
			],
			'csv_catalog'       => file_exists( NGC_WooCommerce_Catalog::csv_path() ),
		];
	}
}
