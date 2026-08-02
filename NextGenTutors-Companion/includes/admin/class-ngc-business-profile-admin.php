<?php
/**
 * Company / business profile admin screen (SSOT display + re-apply).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for ngc_company_profile.
 */
final class NGC_Business_Profile_Admin {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 65 );
		add_action( 'admin_post_ngc_apply_business_profile', [ __CLASS__, 'handle_apply' ] );
	}

	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'Business Profile', 'nextgencompanion' ),
			__( 'Business Profile', 'nextgencompanion' ),
			'manage_options',
			'ngc-business-profile',
			[ __CLASS__, 'render' ]
		);
	}

	public static function handle_apply() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_apply_business_profile' );
		$result = NGC_Business_Profile::apply( true );
		$ok     = ! empty( $result['ok'] );
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'ngc-business-profile',
					'applied' => $ok ? '1' : '0',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		$status  = NGC_Business_Profile::status();
		$company = NGC_Business_Profile::get();
		$blog    = get_bloginfo( 'name' );
		$admin   = get_option( 'admin_email' );
		$woo     = class_exists( 'WooCommerce' );

		$fields = [
			'company_name'       => __( 'Trading name', 'nextgencompanion' ),
			'legal_name'         => __( 'Legal name', 'nextgencompanion' ),
			'platform_name'      => __( 'Platform name', 'nextgencompanion' ),
			'powered_by'         => __( 'Powered by', 'nextgencompanion' ),
			'tagline'            => __( 'Tagline', 'nextgencompanion' ),
			'phone'              => __( 'Phone', 'nextgencompanion' ),
			'whatsapp'           => __( 'WhatsApp (E.164 digits)', 'nextgencompanion' ),
			'email'              => __( 'Support email', 'nextgencompanion' ),
			'admin_email'        => __( 'Admin email', 'nextgencompanion' ),
			'notification_email' => __( 'Notification email', 'nextgencompanion' ),
			'website'            => __( 'Website', 'nextgencompanion' ),
			'address'            => __( 'Address', 'nextgencompanion' ),
			'country'            => __( 'Country', 'nextgencompanion' ),
			'timezone'           => __( 'Timezone', 'nextgencompanion' ),
			'currency'           => __( 'Currency', 'nextgencompanion' ),
		];
		?>
		<div class="wrap" data-testid="ngc-business-profile">
			<h1><?php esc_html_e( 'Business Profile', 'nextgencompanion' ); ?></h1>
			<p><?php esc_html_e( 'Source of truth from config/nextgentutors-business-profile.json — applied into Companion, WordPress, theme, and WooCommerce.', 'nextgencompanion' ); ?></p>

			<?php if ( isset( $_GET['applied'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-<?php echo '1' === (string) $_GET['applied'] ? 'success' : 'error'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
					<p data-testid="ngc-business-profile-flash">
						<?php
						echo '1' === (string) $_GET['applied'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							? esc_html__( 'Business profile applied to core plugins and theme.', 'nextgencompanion' )
							: esc_html__( 'Apply failed — check conflicts or JSON path.', 'nextgencompanion' );
						?>
					</p>
				</div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width:920px;margin:1rem 0;">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'JSON source', 'nextgencompanion' ); ?></th>
						<td data-testid="ngc-business-profile-source"><?php echo esc_html( (string) ( $status['source'] ?? '' ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Applied', 'nextgencompanion' ); ?></th>
						<td data-testid="ngc-business-profile-applied"><?php echo ! empty( $status['applied'] ) ? 'YES' : 'NO'; ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'WP site title', 'nextgencompanion' ); ?></th>
						<td data-testid="ngc-business-profile-blogname"><?php echo esc_html( $blog ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'WP admin email', 'nextgencompanion' ); ?></th>
						<td data-testid="ngc-business-profile-admin-email"><?php echo esc_html( (string) $admin ); ?></td>
					</tr>
					<?php if ( $woo ) : ?>
						<tr>
							<th><?php esc_html_e( 'WooCommerce from email', 'nextgencompanion' ); ?></th>
							<td data-testid="ngc-business-profile-woo-email"><?php echo esc_html( (string) get_option( 'woocommerce_email_from_address', '' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'WooCommerce store phone', 'nextgencompanion' ); ?></th>
							<td data-testid="ngc-business-profile-woo-phone"><?php echo esc_html( (string) get_option( 'woocommerce_store_phone', '' ) ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Saved company option', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped" style="max-width:920px;" data-testid="ngc-business-profile-fields">
				<tbody>
					<?php foreach ( $fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td data-field="<?php echo esc_attr( $key ); ?>">
								<?php
								$val = $company[ $key ] ?? '';
								echo esc_html( is_scalar( $val ) ? (string) $val : wp_json_encode( $val ) );
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.25rem;">
				<input type="hidden" name="action" value="ngc_apply_business_profile" />
				<?php wp_nonce_field( 'ngc_apply_business_profile' ); ?>
				<button type="submit" class="button button-primary" data-testid="ngc-business-profile-apply">
					<?php esc_html_e( 'Re-apply business profile', 'nextgencompanion' ); ?>
				</button>
			</form>
		</div>
		<?php
	}
}
