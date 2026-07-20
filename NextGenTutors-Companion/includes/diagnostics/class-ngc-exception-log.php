<?php
/**
 * Central error and exception logging with admin dashboard.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Captures PHP errors, exceptions, and shutdown fatals when explicitly enabled.
 */
class NGC_Exception_Log {

	const OPTION = 'ngc_exception_log';
	const LIMIT  = 200;

	/** @var bool|null */
	private static $handlers_registered = null;

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'handle_admin_actions' ] );
		if ( self::should_capture_frontend() ) {
			self::register_handlers();
		}
	}

	/**
	 * @return bool
	 */
	public static function should_capture_frontend() {
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}
		return (bool) apply_filters( 'ngc_enable_frontend_exception_capture', false );
	}

	/**
	 * Register PHP handlers without replacing WordPress defaults unless enabled.
	 */
	public static function register_handlers() {
		if ( self::$handlers_registered ) {
			return;
		}
		self::$handlers_registered = true;
		set_error_handler( [ __CLASS__, 'handle_error' ] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_exception_handler( [ __CLASS__, 'handle_exception' ] );
		register_shutdown_function( [ __CLASS__, 'handle_shutdown' ] );
	}

	/**
	 * @param int    $errno   Error number.
	 * @param string $errstr  Message.
	 * @param string $errfile File.
	 * @param int    $errline Line.
	 * @return bool
	 */
	public static function handle_error( $errno, $errstr, $errfile, $errline ) {
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}
		self::log(
			'error',
			$errstr,
			[
				'errno' => $errno,
				'file'  => $errfile,
				'line'  => $errline,
			]
		);
		return false;
	}

	/**
	 * @param Throwable $e Exception.
	 */
	public static function handle_exception( $e ) {
		self::log(
			'exception',
			$e->getMessage(),
			[
				'class' => get_class( $e ),
				'file'  => $e->getFile(),
				'line'  => $e->getLine(),
				'trace' => $e->getTraceAsString(),
			]
		);
	}

	/**
	 * Capture fatal errors on shutdown.
	 */
	public static function handle_shutdown() {
		$error = error_get_last();
		if ( ! $error || ! in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
			return;
		}
		self::log(
			'fatal',
			$error['message'],
			[
				'file' => $error['file'],
				'line' => $error['line'],
				'type' => $error['type'],
			]
		);
	}

	/**
	 * Scrub PII and secrets from log text.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function scrub_pii( $text ) {
		$text = (string) $text;
		$patterns = [
			'/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/' => '[email_redacted]',
			'/(?:\+?\d[\d\s().-]{7,}\d)/'                       => '[phone_redacted]',
			'/(?:nonce|_wpnonce|access_token|api_key|password|secret)=[^\s&]+/i' => '$1=[redacted]',
			'/(?:Cookie|Set-Cookie):\s*[^\n]+/i'                => 'Cookie: [redacted]',
		];
		foreach ( $patterns as $pattern => $replacement ) {
			$text = preg_replace( $pattern, $replacement, $text );
		}
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = (string) $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$scrubbed_uri = preg_replace( '/[?&][^=]+=[^&]+/', '', $uri );
			$text = str_replace( $uri, $scrubbed_uri ?: '/', $text );
		}
		return $text;
	}

	/**
	 * @param string               $type    error|exception|fatal|manual.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function log( $type, $message, $context = [] ) {
		$message = self::scrub_pii( wp_strip_all_tags( (string) $message ) );
		$url     = isset( $_SERVER['REQUEST_URI'] ) ? self::scrub_pii( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( is_array( $context ) ) {
			array_walk_recursive(
				$context,
				static function ( &$value ) {
					if ( is_string( $value ) ) {
						$value = self::scrub_pii( $value );
					}
				}
			);
		}

		$entry = [
			'id'      => wp_generate_uuid4(),
			'type'    => sanitize_key( $type ),
			'message' => $message,
			'context' => $context,
			'url'     => sanitize_text_field( $url ),
			'user_id' => get_current_user_id(),
			'time'    => gmdate( 'c' ),
		];

		$log = get_option( self::OPTION, [] );
		if ( ! is_array( $log ) ) {
			$log = [];
		}
		array_unshift( $log, $entry );
		$limit = (int) apply_filters( 'ngc_exception_log_max_entries', self::LIMIT );
		$log   = array_slice( $log, 0, max( 1, $limit ) );
		update_option( self::OPTION, $log, false );

		do_action( 'ngc_exception_logged', $type, $message, $context );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'platform_error', 'system', 0, $entry );
		}
	}

	/**
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( $limit = 50 ) {
		$log = get_option( self::OPTION, [] );
		if ( ! is_array( $log ) ) {
			return [];
		}
		return array_slice( $log, 0, max( 1, (int) $limit ) );
	}

	/**
	 * Clear stored log.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}

	/**
	 * Export log as JSON download.
	 */
	public static function export_json() {
		$data = self::recent( (int) apply_filters( 'ngc_exception_log_max_entries', self::LIMIT ) );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ngc-exception-log-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Handle export/clear admin actions.
	 */
	public static function handle_admin_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['ngc_export_errors'] ) && check_admin_referer( 'ngc_export_errors' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::export_json();
		}
	}

	/**
	 * Admin dashboard UI.
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		if ( isset( $_POST['ngc_clear_errors'] ) && check_admin_referer( 'ngc_clear_errors' ) ) {
			self::clear();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Error log cleared.', 'nextgencompanion' ) . '</p></div>';
		}

		$rows   = self::recent( 100 );
		$counts = [ 'error' => 0, 'exception' => 0, 'fatal' => 0, 'manual' => 0 ];
		foreach ( $rows as $row ) {
			$t = $row['type'] ?? 'error';
			if ( isset( $counts[ $t ] ) ) {
				++$counts[ $t ];
			}
		}
		$capture = self::should_capture_frontend() ? __( 'Enabled (filter)', 'nextgencompanion' ) : __( 'Disabled (default)', 'nextgencompanion' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Errors & Exceptions', 'nextgencompanion' ); ?></h1>
			<p><?php esc_html_e( 'Central log for PHP errors, uncaught exceptions, and fatal shutdown events. Frontend capture is opt-in via ngc_enable_frontend_exception_capture.', 'nextgencompanion' ); ?></p>
			<p><strong><?php esc_html_e( 'Frontend capture:', 'nextgencompanion' ); ?></strong> <?php echo esc_html( $capture ); ?></p>

			<div style="display:flex;gap:16px;margin:16px 0;flex-wrap:wrap">
				<div class="card" style="padding:12px 18px;min-width:120px"><strong><?php echo (int) $counts['fatal']; ?></strong><br><?php esc_html_e( 'Fatals', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px 18px;min-width:120px"><strong><?php echo (int) $counts['exception']; ?></strong><br><?php esc_html_e( 'Exceptions', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px 18px;min-width:120px"><strong><?php echo (int) $counts['error']; ?></strong><br><?php esc_html_e( 'Errors', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px 18px;min-width:120px"><strong><?php echo count( $rows ); ?></strong><br><?php esc_html_e( 'Total (recent)', 'nextgencompanion' ); ?></div>
			</div>

			<form method="post" style="margin-bottom:16px;display:inline-block">
				<?php wp_nonce_field( 'ngc_clear_errors' ); ?>
				<button type="submit" name="ngc_clear_errors" class="button" onclick="return confirm('<?php echo esc_js( __( 'Clear all logged errors?', 'nextgencompanion' ) ); ?>');"><?php esc_html_e( 'Clear log', 'nextgencompanion' ); ?></button>
			</form>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-errors&ngc_export_errors=1' ), 'ngc_export_errors' ) ); ?>"><?php esc_html_e( 'Export log (JSON)', 'nextgencompanion' ); ?></a>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-health' ) ); ?>"><?php esc_html_e( 'System Health', 'nextgencompanion' ); ?></a>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time (UTC)', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Type', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Message', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Location', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'URL', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No errors logged — platform is clean.', 'nextgencompanion' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) :
						$ctx = is_array( $row['context'] ?? null ) ? $row['context'] : [];
						$loc = '';
						if ( ! empty( $ctx['file'] ) ) {
							$loc = basename( (string) $ctx['file'] ) . ':' . (int) ( $ctx['line'] ?? 0 );
						}
						$type  = (string) ( $row['type'] ?? 'error' );
						$color = 'fatal' === $type ? '#b91c1c' : ( 'exception' === $type ? '#c2410c' : '#64748b' );
						?>
						<tr>
							<td><code><?php echo esc_html( (string) ( $row['time'] ?? '' ) ); ?></code></td>
							<td><span style="color:<?php echo esc_attr( $color ); ?>;font-weight:700"><?php echo esc_html( strtoupper( $type ) ); ?></span></td>
							<td><?php echo esc_html( (string) ( $row['message'] ?? '' ) ); ?></td>
							<td><code><?php echo esc_html( $loc ); ?></code></td>
							<td><code style="word-break:break-all"><?php echo esc_html( (string) ( $row['url'] ?? '' ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
