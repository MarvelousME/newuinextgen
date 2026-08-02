<?php
/**
 * Admin UI — Page/Forms registry verification.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry admin screen.
 */
class NGC_Page_Forms_Registry_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 25 );
		add_action( 'admin_post_ngc_registry_repair', [ __CLASS__, 'handle_repair' ] );
		add_action( 'admin_post_ngc_registry_verify', [ __CLASS__, 'handle_verify' ] );
	}

	/**
	 * Register submenu.
	 */
	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'Page & Form Registry', 'nextgencompanion' ),
			__( 'Page Registry', 'nextgencompanion' ),
			'manage_options',
			'ngc-page-registry',
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Render verification screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		$report = NGC_Page_Forms_Registry::last_report();
		if ( empty( $report['items'] ) ) {
			$report = NGC_Page_Forms_Registry::verify();
		}
		$summary = $report['summary'] ?? [];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Page & Form Registry', 'nextgencompanion' ); ?></h1>
			<p><?php esc_html_e( 'Verifies launch pages, required shortcodes, and safe auto-repair for missing mappings.', 'nextgencompanion' ); ?></p>

			<p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
					<?php wp_nonce_field( 'ngc_registry_verify' ); ?>
					<input type="hidden" name="action" value="ngc_registry_verify" />
					<?php submit_button( __( 'Run verification', 'nextgencompanion' ), 'secondary', 'submit', false ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:8px">
					<?php wp_nonce_field( 'ngc_registry_repair' ); ?>
					<input type="hidden" name="action" value="ngc_registry_repair" />
					<?php submit_button( __( 'Repair all (safe)', 'nextgencompanion' ), 'primary', 'submit', false ); ?>
				</form>
			</p>

			<?php if ( ! empty( $report['verified_at'] ) ) : ?>
				<p><em><?php printf( esc_html__( 'Last verified: %s', 'nextgencompanion' ), esc_html( $report['verified_at'] ) ); ?></em></p>
			<?php endif; ?>

			<ul>
				<li><?php printf( esc_html__( 'Pass: %d', 'nextgencompanion' ), (int) ( $summary['pass'] ?? 0 ) ); ?></li>
				<li><?php printf( esc_html__( 'Warning: %d', 'nextgencompanion' ), (int) ( $summary['warning'] ?? 0 ) ); ?></li>
				<li><?php printf( esc_html__( 'Fail: %d', 'nextgencompanion' ), (int) ( $summary['fail'] ?? 0 ) ); ?></li>
			</ul>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Shortcodes', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( (array) ( $report['items'] ?? [] ) as $slug => $item ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( (string) ( $item['title'] ?? $slug ) ); ?></strong><br>
							<code><?php echo esc_html( (string) $slug ); ?></code>
						</td>
						<td><?php echo wp_kses_post( self::badge( (string) ( $item['status'] ?? 'FAIL' ) ) ); ?></td>
						<td>
							<?php if ( ! empty( $item['shortcodes'] ) ) : ?>
								<ul style="margin:0">
									<?php foreach ( (array) $item['shortcodes'] as $sc ) : ?>
										<li><code><?php echo esc_html( (string) ( $sc['tag'] ?? '' ) ); ?></code>
											<?php echo wp_kses_post( self::badge( (string) ( $sc['status'] ?? 'FAIL' ), true ) ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<?php echo esc_html( (string) ( $item['message'] ?? '' ) ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! str_starts_with( (string) $slug, '_' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'ngc_registry_repair' ); ?>
									<input type="hidden" name="action" value="ngc_registry_repair" />
									<input type="hidden" name="slug" value="<?php echo esc_attr( (string) $slug ); ?>" />
									<?php submit_button( __( 'Repair', 'nextgencompanion' ), 'small', 'submit', false ); ?>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param string $status Status.
	 * @param bool   $small  Small badge.
	 * @return string
	 */
	private static function badge( $status, $small = false ) {
		$colors = [
			'PASS'    => '#15803d',
			'WARNING' => '#b45309',
			'FAIL'    => '#b91c1c',
		];
		$color = $colors[ $status ] ?? '#64748b';
		$size  = $small ? '11px' : '12px';
		return sprintf(
			'<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:%s;color:#fff;font-size:%s;font-weight:600">%s</span>',
			esc_attr( $color ),
			esc_attr( $size ),
			esc_html( $status )
		);
	}

	/**
	 * Handle verify POST.
	 */
	public static function handle_verify() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ngc_registry_verify' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'nextgencompanion' ) );
		}
		NGC_Page_Forms_Registry::verify();
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-page-registry&verified=1' ) );
		exit;
	}

	/**
	 * Handle repair POST.
	 */
	public static function handle_repair() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ngc_registry_repair' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'nextgencompanion' ) );
		}
		$slug  = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
		$force = ! empty( $_POST['force'] );
		NGC_Page_Forms_Registry::repair( $slug, $force );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-page-registry&repaired=1' ) );
		exit;
	}
}
