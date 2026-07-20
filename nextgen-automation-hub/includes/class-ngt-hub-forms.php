<?php
/**
 * Public intake forms with validation and file uploads.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Forms {

	public static function register_hooks(): void {
		add_shortcode( 'ngt_find_tutor_form', [ __CLASS__, 'find_tutor_form' ] );
		add_shortcode( 'ngt_become_tutor_form', [ __CLASS__, 'become_tutor_form' ] );
		add_action( 'admin_post_nopriv_ngt_find_tutor', [ __CLASS__, 'handle_find_tutor' ] );
		add_action( 'admin_post_ngt_find_tutor', [ __CLASS__, 'handle_find_tutor' ] );
		add_action( 'admin_post_nopriv_ngt_become_tutor', [ __CLASS__, 'handle_become_tutor' ] );
		add_action( 'admin_post_ngt_become_tutor', [ __CLASS__, 'handle_become_tutor' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	public static function enqueue_assets(): void {
		global $post;
		if ( ! $post ) {
			return;
		}
		if ( has_shortcode( $post->post_content, 'ngt_find_tutor_form' ) || has_shortcode( $post->post_content, 'ngt_become_tutor_form' ) ) {
			wp_enqueue_style( 'ngt-hub', NGT_HUB_URL . 'assets/css/ngt-hub.css', [], NGT_Hub::VERSION );
		}
	}

	public static function find_tutor_form(): string {
		ob_start();
		?>
		<form class="ngt-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ngt_find_tutor" />
			<?php wp_nonce_field( 'ngt_find_tutor', 'ngt_nonce' ); ?>
			<?php echo NGT_Hub_Security::honeypot_field(); ?>
			<label><?php esc_html_e( 'Parent / Guardian name', 'nextgen-automation-hub' ); ?><input type="text" name="name" required /></label>
			<label><?php esc_html_e( 'Email', 'nextgen-automation-hub' ); ?><input type="email" name="email" required /></label>
			<label><?php esc_html_e( 'Phone', 'nextgen-automation-hub' ); ?><input type="tel" name="phone" /></label>
			<label><?php esc_html_e( 'Grade', 'nextgen-automation-hub' ); ?><input type="text" name="grade" required /></label>
			<label><?php esc_html_e( 'Subject', 'nextgen-automation-hub' ); ?><input type="text" name="subject" required /></label>
			<label><?php esc_html_e( 'Area / Province', 'nextgen-automation-hub' ); ?><input type="text" name="area" required /></label>
			<label><?php esc_html_e( 'Notes', 'nextgen-automation-hub' ); ?><textarea name="notes"></textarea></label>
			<button type="submit"><?php esc_html_e( 'Find a Tutor', 'nextgen-automation-hub' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	public static function become_tutor_form(): string {
		ob_start();
		?>
		<form class="ngt-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="ngt_become_tutor" />
			<?php wp_nonce_field( 'ngt_become_tutor', 'ngt_nonce' ); ?>
			<?php echo NGT_Hub_Security::honeypot_field(); ?>
			<label><?php esc_html_e( 'Full name', 'nextgen-automation-hub' ); ?><input type="text" name="name" required /></label>
			<label><?php esc_html_e( 'Email', 'nextgen-automation-hub' ); ?><input type="email" name="email" required /></label>
			<label><?php esc_html_e( 'Phone', 'nextgen-automation-hub' ); ?><input type="tel" name="phone" /></label>
			<label><?php esc_html_e( 'Subjects (comma-separated)', 'nextgen-automation-hub' ); ?><input type="text" name="subjects" required /></label>
			<label><?php esc_html_e( 'Area / Province', 'nextgen-automation-hub' ); ?><input type="text" name="area" required /></label>
			<label><?php esc_html_e( 'Bio', 'nextgen-automation-hub' ); ?><textarea name="bio" required></textarea></label>
			<label><?php esc_html_e( 'ID Document (PDF/JPG)', 'nextgen-automation-hub' ); ?><input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" /></label>
			<label><?php esc_html_e( 'Qualification Certificate', 'nextgen-automation-hub' ); ?><input type="file" name="qualification" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" /></label>
			<button type="submit"><?php esc_html_e( 'Submit Application', 'nextgen-automation-hub' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	public static function handle_find_tutor(): void {
		self::verify_nonce( 'ngt_find_tutor' );

		$data = self::collect_fields( [ 'name', 'email', 'phone', 'grade', 'subject', 'area', 'notes' ] );
		$data['ngt_hp_field'] = sanitize_text_field( wp_unslash( (string) ( $_POST['ngt_hp_field'] ?? '' ) ) );

		$valid = apply_filters( 'ngt_hub_validate_form', true, [ 'form' => 'find_tutor', 'data' => $data ] );
		if ( is_wp_error( $valid ) ) {
			wp_die( esc_html( $valid->get_error_message() ) );
		}

		$parent_id = get_current_user_id();
		$data['parent_user_id'] = $parent_id;

		$match_id = NGT_Hub_Matching::create_from_intake( $data );

		NGT_Hub::fire_event(
			'ngt.find_tutor.submitted',
			'find_a_tutor',
			$parent_id,
			is_int( $match_id ) ? $match_id : 0,
			array_merge( $data, [ 'match_id' => is_int( $match_id ) ? $match_id : 0 ] )
		);

		wp_safe_redirect( add_query_arg( 'ngt_success', '1', wp_get_referer() ?: home_url( '/find-a-tutor/' ) ) );
		exit;
	}

	public static function handle_become_tutor(): void {
		self::verify_nonce( 'ngt_become_tutor' );

		$data = self::collect_fields( [ 'name', 'email', 'phone', 'subjects', 'area', 'bio' ] );
		$data['ngt_hp_field'] = sanitize_text_field( wp_unslash( (string) ( $_POST['ngt_hp_field'] ?? '' ) ) );

		$valid = apply_filters( 'ngt_hub_validate_form', true, [ 'form' => 'become_tutor', 'data' => $data ] );
		if ( is_wp_error( $valid ) ) {
			wp_die( esc_html( $valid->get_error_message() ) );
		}

		$user_id = email_exists( $data['email'] );
		if ( ! $user_id ) {
			$user_id = wp_insert_user(
				[
					'user_login'   => $data['email'],
					'user_email'   => $data['email'],
					'user_pass'    => wp_generate_password( 16 ),
					'display_name' => $data['name'],
					'role'         => 'ngt_tutor',
				]
			);
		}
		if ( is_wp_error( $user_id ) ) {
			wp_die( esc_html( $user_id->get_error_message() ) );
		}

		update_user_meta( (int) $user_id, 'ngt_subjects', $data['subjects'] );
		update_user_meta( (int) $user_id, 'ngt_area', $data['area'] );
		update_user_meta( (int) $user_id, 'ngt_tutor_approved', 0 );

		self::store_uploads( (int) $user_id );

		wp_insert_post(
			[
				'post_type'   => 'ngt_tutor_profile',
				'post_title'  => $data['name'],
				'post_status' => 'publish',
				'meta_input'  => [
					'ngt_user_id' => (int) $user_id,
					'ngt_email'   => $data['email'],
					'ngt_phone'   => $data['phone'] ?? '',
				],
			]
		);

		NGT_Hub::fire_event(
			'ngt.tutor_application.submitted',
			'become_a_tutor',
			(int) $user_id,
			0,
			$data
		);

		wp_safe_redirect( add_query_arg( 'ngt_success', '1', wp_get_referer() ?: home_url( '/become-a-tutor/' ) ) );
		exit;
	}

	private static function store_uploads( int $user_id ): void {
		global $wpdb;
		$fields = [
			'id_document'   => 'id',
			'qualification' => 'qualification',
		];
		foreach ( $fields as $field => $doc_type ) {
			if ( empty( $_FILES[ $field ]['tmp_name'] ) ) {
				continue;
			}
			$attach_id = NGT_Hub_Security::handle_upload( $_FILES[ $field ] );
			if ( is_wp_error( $attach_id ) ) {
				continue;
			}
			$wpdb->insert(
				NGT_Hub_Database::table( 'tutor_documents' ),
				[
					'user_id'       => $user_id,
					'attachment_id' => $attach_id,
					'doc_type'      => $doc_type,
					'status'        => 'pending',
				],
				[ '%d', '%d', '%s', '%s' ]
			);
		}
	}

	/**
	 * @param array<int, string> $fields Field names.
	 * @return array<string, string>
	 */
	private static function collect_fields( array $fields ): array {
		$data = [];
		foreach ( $fields as $field ) {
			$raw = wp_unslash( $_POST[ $field ] ?? '' );
			$data[ $field ] = 'notes' === $field || 'bio' === $field
				? sanitize_textarea_field( (string) $raw )
				: sanitize_text_field( (string) $raw );
		}
		return $data;
	}

	private static function verify_nonce( string $action ): void {
		if ( ! isset( $_POST['ngt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['ngt_nonce'] ) ), $action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'nextgen-automation-hub' ) );
		}
	}
}
