<?php
/**
 * Delegates duplicate domain work to Companion when present.
 *
 * Prevents dual payout crons, REST namespace collisions on ngt/v1, and duplicate health crons.
 * TD-RAD-004: quiet-domain skips matching/finance CPT + matching REST when NGC_Plugin is active.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Companion delegation + structured logging for Automation Hub.
 *
 * TD-RAD-004: When Companion is active, Hub runs quiet-domain / delegate-only —
 * matching + finance CPT/REST stay off; Hub remains installable for RTM/workflows.
 */
final class NGT_Hub_Companion_Delegate {

	private const LOG_SOURCE = 'automation_hub';

	/** Finance CPTs Companion owns when present. */
	private const FINANCE_POST_TYPES = [
		'ngt_payout',
	];

	/** @var bool|null */
	private static $companion_active = null;

	/** @var bool */
	private static $synced = false;

	/**
	 * Whether NextGen Companion owns authoritative domain logic.
	 */
	public static function companion_active(): bool {
		if ( null === self::$companion_active ) {
			self::$companion_active = defined( 'NGC_VERSION' )
				|| class_exists( 'NGC_Plugin', false )
				|| class_exists( 'NGC_Payout_Scheduler', false );
		}
		return (bool) self::$companion_active;
	}

	/**
	 * Reset cached detection (tests / late Companion load).
	 */
	public static function reset_detection_cache(): void {
		self::$companion_active = null;
		self::$synced           = false;
	}

	/**
	 * Quiet domain = Companion is authority; Hub must not register matching/finance.
	 */
	public static function is_quiet_domain(): bool {
		return self::companion_active();
	}

	/**
	 * Alias: Hub installable but domain registration deferred.
	 */
	public static function is_delegate_only(): bool {
		return self::is_quiet_domain();
	}

	/**
	 * Whether Hub may register matching hooks / match REST.
	 */
	public static function should_register_matching(): bool {
		return ! self::is_quiet_domain();
	}

	/**
	 * Whether Hub may register finance CPT / payout hooks.
	 */
	public static function should_register_finance(): bool {
		return ! self::is_quiet_domain();
	}

	/**
	 * Finance CPT slugs skipped under quiet domain.
	 *
	 * @return array<int, string>
	 */
	public static function finance_post_types(): array {
		return self::FINANCE_POST_TYPES;
	}

	/**
	 * REST namespace — avoid ngt/v1 collision when Companion mirrors ngc/v1 there.
	 */
	public static function rest_namespace(): string {
		return self::companion_active() ? 'ngt-hub/v1' : 'ngt/v1';
	}

	/**
	 * @param string $path Route path beginning with /.
	 */
	public static function rest_url( string $path ): string {
		$path = '/' . ltrim( $path, '/' );
		return rest_url( self::rest_namespace() . $path );
	}

	/**
	 * Unschedule Hub crons that Companion owns; log outcome once per request boot.
	 */
	public static function sync_delegation(): void {
		if ( self::$synced ) {
			return;
		}
		self::$synced = true;

		if ( ! self::companion_active() ) {
			self::log( 'info', 'Hub running in standalone mode (Companion not detected).' );
			return;
		}

		$actions = [
			'quiet_domain'          => true,
			'matching_registration' => 'skipped',
			'finance_registration'  => 'skipped',
			'payout_cron_cleared'   => false,
			'health_cron_cleared'   => false,
		];

		if ( class_exists( 'NGT_Hub_Payouts', false ) ) {
			NGT_Hub_Payouts::unschedule_cron();
			$actions['payout_cron_cleared'] = true;
		}
		if ( class_exists( 'NGT_Hub_Workflows', false ) ) {
			NGT_Hub_Workflows::unschedule_health_cron();
			$actions['health_cron_cleared'] = true;
		}

		self::log(
			'info',
			'Delegate-only mode: quiet domain (matching/finance deferred to Companion).',
			[
				'rest_namespace' => self::rest_namespace(),
				'actions'        => $actions,
			]
		);

		/**
		 * Fires after Hub→Companion delegation sync.
		 *
		 * @param array<string, mixed> $context Delegation context.
		 */
		do_action( 'ngt_hub_companion_delegated', [
			'rest_namespace'           => self::rest_namespace(),
			'quiet_domain'             => true,
			'should_register_matching' => self::should_register_matching(),
			'should_register_finance'  => self::should_register_finance(),
			'actions'                  => $actions,
		] );
	}

	/**
	 * Hub may still register non-domain REST under ngt-hub/v1 when Companion is active.
	 * Matching/finance routes are gated separately via should_register_matching().
	 */
	public static function should_register_rest(): bool {
		return true;
	}

	/**
	 * @param string               $level   debug|info|warning|error.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function log( string $level, string $message, array $context = [] ): void {
		try {
			if ( class_exists( 'NGC_System_Log', false ) ) {
				$method = in_array( $level, [ 'warning', 'error', 'critical' ], true ) ? $level : 'info';
				if ( 'critical' === $method ) {
					NGC_System_Log::error( self::LOG_SOURCE, 'delegation', $message, $context );
					return;
				}
				if ( 'warning' === $method ) {
					NGC_System_Log::warning( self::LOG_SOURCE, 'delegation', $message, $context );
					return;
				}
				if ( 'error' === $method ) {
					NGC_System_Log::error( self::LOG_SOURCE, 'delegation', $message, $context );
					return;
				}
				NGC_System_Log::info( self::LOG_SOURCE, 'delegation', $message, $context );
				return;
			}
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Fall through to error_log.
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log(
			sprintf(
				'[NGT Hub][%s] %s %s',
				strtoupper( $level ),
				$message,
				$context ? wp_json_encode( $context ) : ''
			)
		);
	}
}
