<?php
/**
 * Delegates duplicate domain work to Companion when present.
 *
 * Prevents dual payout crons, REST namespace collisions on ngt/v1, and duplicate health crons.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Companion delegation + structured logging for Automation Hub.
 */
final class NGT_Hub_Companion_Delegate {

	private const LOG_SOURCE = 'automation_hub';

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
				|| class_exists( 'NGC_Payout_Scheduler', false )
				|| class_exists( 'NGC_Bookings', false )
				|| class_exists( 'NGC_Workflow_Authority', false );
		}
		return (bool) self::$companion_active;
	}

	/**
	 * When Companion (or workflow authority) is present, Hub must not own finance/matching writes.
	 */
	public static function domain_writes_blocked(): bool {
		if ( ! self::companion_active() ) {
			return false;
		}
		if ( class_exists( 'NGC_Platform', false ) && method_exists( 'NGC_Platform', 'authority_enabled' ) ) {
			return (bool) NGC_Platform::authority_enabled();
		}
		// Companion present without kill-switch API → still block dual finance/matching.
		return true;
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

		$actions = [];

		if ( class_exists( 'NGT_Hub_Payouts', false ) ) {
			NGT_Hub_Payouts::unschedule_cron();
			$actions[] = 'payout_cron_cleared';
		}
		if ( class_exists( 'NGT_Hub_Workflows', false ) ) {
			NGT_Hub_Workflows::unschedule_health_cron();
			$actions[] = 'health_cron_cleared';
		}

		self::log(
			'info',
			'Delegated domain crons and REST namespace to Companion.',
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
			'rest_namespace' => self::rest_namespace(),
			'actions'        => $actions,
		] );
	}

	/**
	 * Skip Hub REST registration on ngt/v1 when Companion is active (routes move to ngt-hub/v1).
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
