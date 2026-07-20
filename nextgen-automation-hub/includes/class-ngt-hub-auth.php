<?php
/**
 * Front-end registration and login flows.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Auth {

	public static function register_hooks(): void {
		add_shortcode( 'ngt_register', [ __CLASS__, 'register_form' ] );
		add_shortcode( 'ngt_login', [ __CLASS__, 'login_form' ] );
		add_action( 'admin_post_nopriv_ngt_register', [ __CLASS__, 'handle_register' ] );
		add_action( 'admin_post_ngt_register', [ __CLASS__, 'handle_register' ] );
		add_action( 'admin_post_nopriv_ngt_login', [ __CLASS__, 'handle_login' ] );
		add_action( 'admin_post_ngt_login', [ __CLASS__, 'handle_login' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	public static function enqueue_assets(): void {
		global $post;
		if ( ! $post ) {
			return;
		}
		if ( has_shortcode( $post->post_content, 'ngt_register' ) || has_shortcode( $post->post_content, 'ngt_login' ) ) {
			wp_enqueue_style( 'ngt-hub', NGT_HUB_URL . 'assets/css/ngt-hub.css', [], NGT_Hub::VERSION );
		}
	}

	public static function register_form(): string {
		if ( is_user_logged_in() ) {
			return '<div class="ngt-wrap"><p>' . esc_html__( 'You are already logged in.', 'nextgen-automation-hub' ) . '</p></div>';
		}

		$error = isset( $_GET['ngt_error'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ngt_error'] ) ) : '';

		ob_start();
		?>
		<div class="ngt-wrap">
			<?php if ( $error ) : ?>
				<div class="ngt-notice ngt-notice--error"><?php echo esc_html( $error ); ?></div>
			<?php endif; ?>
			<form class="ngt-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngt_register" />
				<?php wp_nonce_field( 'ngt_register', 'ngt_nonce' ); ?>
				<?php echo NGT_Hub_Security::honeypot_field(); ?>
				<label><?php esc_html_e( 'Full name', 'nextgen-automation-hub' ); ?><input type="text" name="name" required /></label>
				<label><?php esc_html_e( 'Email', 'nextgen-automation-hub' ); ?><input type="email" name="email" required /></label>
				<label><?php esc_html_e( 'Password', 'nextgen-automation-hub' ); ?><input type="password" name="password" required minlength="8" /></label>
				<label><?php esc_html_e( 'I am a…', 'nextgen-automation-hub' ); ?>
					<select name="role" required>
						<option value="ngt_parent"><?php esc_html_e( 'Parent / Guardian', 'nextgen-automation-hub' ); ?></option>
						<option value="ngt_student"><?php esc_html_e( 'Student', 'nextgen-automation-hub' ); ?></option>
						<option value="ngt_tutor"><?php esc_html_e( 'Tutor', 'nextgen-automation-hub' ); ?></option>
					</select>
				</label>
				<button type="submit"><?php esc_html_e( 'Create Account', 'nextgen-automation-hub' ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function login_form(): string {
		if ( is_user_logged_in() ) {
			return '<div class="ngt-wrap"><p>' . esc_html__( 'You are already logged in.', 'nextgen-automation-hub' ) . '</p></div>';
		}

		$error = isset( $_GET['ngt_error'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ngt_error'] ) ) : '';

		ob_start();
		?>
		<div class="ngt-wrap">
			<?php if ( $error ) : ?>
				<div class="ngt-notice ngt-notice--error"><?php echo esc_html( $error ); ?></div>
			<?php endif; ?>
			<form class="ngt-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngt_login" />
				<?php wp_nonce_field( 'ngt_login', 'ngt_nonce' ); ?>
				<?php echo NGT_Hub_Security::honeypot_field(); ?>
				<label><?php esc_html_e( 'Email or username', 'nextgen-automation-hub' ); ?><input type="text" name="login" required /></label>
				<label><?php esc_html_e( 'Password', 'nextgen-automation-hub' ); ?><input type="password" name="password" required /></label>
				<label><input type="checkbox" name="remember" value="1" /> <?php esc_html_e( 'Remember me', 'nextgen-automation-hub' ); ?></label>
				<button type="submit"><?php esc_html_e( 'Log In', 'nextgen-automation-hub' ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function handle_register(): void {
		if ( ! isset( $_POST['ngt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['ngt_nonce'] ) ), 'ngt_register' ) ) {
			self::redirect_error( home_url( '/register/' ), __( 'Security check failed.', 'nextgen-automation-hub' ) );
		}

		$data = [
			'form'     => 'register',
			'name'     => sanitize_text_field( wp_unslash( (string) ( $_POST['name'] ?? '' ) ) ),
			'email'    => sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) ),
			'password' => (string) ( $_POST['password'] ?? '' ),
			'role'     => sanitize_key( (string) ( $_POST['role'] ?? 'ngt_parent' ) ),
			'ngt_hp_field' => sanitize_text_field( wp_unslash( (string) ( $_POST['ngt_hp_field'] ?? '' ) ) ),
		];

		$valid = apply_filters( 'ngt_hub_validate_form', true, [ 'form' => 'register', 'data' => $data ] );
		if ( is_wp_error( $valid ) ) {
			self::redirect_error( home_url( '/register/' ), $valid->get_error_message() );
		}

		if ( email_exists( $data['email'] ) ) {
			self::redirect_error( home_url( '/register/' ), __( 'Email already registered.', 'nextgen-automation-hub' ) );
		}

		$allowed_roles = [ 'ngt_parent', 'ngt_student', 'ngt_tutor' ];
		if ( ! in_array( $data['role'], $allowed_roles, true ) ) {
			$data['role'] = 'ngt_parent';
		}

		$user_id = wp_insert_user(
			[
				'user_login'   => $data['email'],
				'user_email'   => $data['email'],
				'user_pass'    => $data['password'],
				'display_name' => $data['name'],
				'role'         => $data['role'],
			]
		);

		if ( is_wp_error( $user_id ) ) {
			self::redirect_error( home_url( '/register/' ), $user_id->get_error_message() );
		}

		NGT_Hub::fire_event( 'wp.user_registered', 'auth', (int) $user_id, 0, [
			'user_id' => $user_id,
			'role'    => $data['role'],
			'email'   => $data['email'],
			'name'    => $data['name'],
		] );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		wp_safe_redirect( self::dashboard_url_for_role( $data['role'] ) );
		exit;
	}

	public static function handle_login(): void {
		if ( ! isset( $_POST['ngt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['ngt_nonce'] ) ), 'ngt_login' ) ) {
			self::redirect_error( home_url( '/login/' ), __( 'Security check failed.', 'nextgen-automation-hub' ) );
		}

		$data = [
			'ngt_hp_field' => sanitize_text_field( wp_unslash( (string) ( $_POST['ngt_hp_field'] ?? '' ) ) ),
		];
		$valid = apply_filters( 'ngt_hub_validate_form', true, [ 'form' => 'login', 'data' => $data ] );
		if ( is_wp_error( $valid ) ) {
			self::redirect_error( home_url( '/login/' ), $valid->get_error_message() );
		}

		$creds = [
			'user_login'    => sanitize_text_field( wp_unslash( (string) ( $_POST['login'] ?? '' ) ) ),
			'user_password' => (string) ( $_POST['password'] ?? '' ),
			'remember'      => ! empty( $_POST['remember'] ),
		];

		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) {
			self::redirect_error( home_url( '/login/' ), __( 'Invalid credentials.', 'nextgen-automation-hub' ) );
		}

		$role = $user->roles[0] ?? 'ngt_parent';
		wp_safe_redirect( self::dashboard_url_for_role( $role ) );
		exit;
	}

	private static function dashboard_url_for_role( string $role ): string {
		$map = [
			'ngt_student' => 'student-dashboard',
			'ngt_parent'  => 'parent-dashboard',
			'ngt_tutor'   => 'tutor-dashboard',
			'ngt_support' => 'support-center',
			'administrator' => 'admin-control-plane',
		];
		$slug = $map[ $role ] ?? 'parent-dashboard';
		$page = get_page_by_path( $slug );
		return $page ? get_permalink( $page ) : home_url( '/' );
	}

	private static function redirect_error( string $url, string $message ): void {
		wp_safe_redirect( add_query_arg( 'ngt_error', rawurlencode( $message ), $url ) );
		exit;
	}
}
