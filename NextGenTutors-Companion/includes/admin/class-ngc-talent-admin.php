<?php
/**
 * Talent Intelligence admin screens.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin under Tutors → Talent Intelligence.
 */
class NGC_Talent_Admin {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 67 );
		add_action( 'admin_post_ngc_talent_save', [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_post_ngc_talent_evaluate_demo', [ __CLASS__, 'handle_evaluate_demo' ] );
	}

	/**
	 * @return bool
	 */
	public static function can_view() {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'ngc_manage_matches' )
			|| current_user_can( 'ngc_manage_platform' )
			|| current_user_can( 'ngc_admin_operations' );
	}

	/**
	 * Settings mutation — same operators as view (decision-support config).
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return self::can_view();
	}

	/**
	 * Menu.
	 */
	public static function register_menu() {
		if ( ! self::can_view() ) {
			return;
		}
		$parent = function_exists( 'ngt_admin_parent' ) ? ngt_admin_parent() : 'ngt-admin';
		$cap    = current_user_can( 'manage_options' ) ? 'manage_options' : ( current_user_can( 'ngc_manage_matches' ) ? 'ngc_manage_matches' : 'ngc_manage_platform' );
		add_submenu_page(
			$parent,
			__( 'Talent Intelligence', 'nextgencompanion' ),
			__( 'Talent Intelligence', 'nextgencompanion' ),
			$cap,
			'ngc-talent-intelligence',
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * Save settings.
	 */
	public static function handle_save() {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_talent_save' );
		NGC_Talent_Settings::update(
			[
				'enabled'               => ! empty( $_POST['enabled'] ),
				'evaluate_applications' => ! empty( $_POST['evaluate_applications'] ),
				'rank_find_tutor'       => ! empty( $_POST['rank_find_tutor'] ),
				'nlp_sidecar_enabled'   => ! empty( $_POST['nlp_sidecar_enabled'] ),
				'agent_tools_enabled'   => ! empty( $_POST['agent_tools_enabled'] ),
				'mode'                  => sanitize_text_field( wp_unslash( (string) ( $_POST['mode'] ?? 'DISABLED' ) ) ),
				'nlp_sidecar_url'       => esc_url_raw( wp_unslash( (string) ( $_POST['nlp_sidecar_url'] ?? '' ) ) ),
				'auto_approve_forbidden'=> true,
			]
		);
		NGC_Talent_Repository::save_requirement_profile(
			'default',
			[
				'title'         => 'Default tutor requirement',
				'subjects'      => self::csv_list( (string) ( $_POST['req_subjects'] ?? '' ) ),
				'grades'        => self::csv_list( (string) ( $_POST['req_grades'] ?? '' ) ),
				'deliveryModes' => self::csv_list( (string) ( $_POST['req_modes'] ?? '' ) ),
			]
		);
		NGC_Talent_Service::reset_provider();
		wp_safe_redirect( add_query_arg( [ 'page' => 'ngc-talent-intelligence', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Run a manual evaluation from admin form.
	 */
	public static function handle_evaluate_demo() {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_talent_evaluate_demo' );
		$candidate = [
			'subjects'      => self::csv_list( (string) ( $_POST['cand_subjects'] ?? '' ) ),
			'grades'        => self::csv_list( (string) ( $_POST['cand_grades'] ?? '' ) ),
			'bio'           => sanitize_textarea_field( wp_unslash( (string) ( $_POST['cand_bio'] ?? '' ) ) ),
			'province'      => sanitize_text_field( wp_unslash( (string) ( $_POST['cand_province'] ?? '' ) ) ),
			'location'      => sanitize_text_field( wp_unslash( (string) ( $_POST['cand_province'] ?? '' ) ) ),
			'deliveryModes' => self::csv_list( (string) ( $_POST['cand_modes'] ?? '' ) ),
		];
		$result = NGC_Talent_Service::evaluate_safe(
			$candidate,
			NGC_Talent_Service::default_requirements(),
			[
				'persist'         => true,
				'candidate_type'  => 'manual',
				'candidate_id'    => 'admin-' . get_current_user_id() . '-' . time(),
				'idempotency_key' => '',
			]
		);
		set_transient( 'ngc_talent_last_eval_' . get_current_user_id(), $result, 300 );
		wp_safe_redirect( add_query_arg( [ 'page' => 'ngc-talent-intelligence', 'evaluated' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * @param string $raw CSV.
	 * @return string[]
	 */
	private static function csv_list( $raw ) {
		$raw = sanitize_text_field( wp_unslash( $raw ) );
		return array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
	}

	/**
	 * Render.
	 */
	public static function render_page() {
		if ( ! self::can_view() ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		$cfg    = NGC_Talent_Settings::get();
		$health = NGC_Talent_Service::health();
		$items  = NGC_Talent_Repository::query( [ 'limit' => 20 ] );
		$req    = NGC_Talent_Service::default_requirements();
		$last   = get_transient( 'ngc_talent_last_eval_' . get_current_user_id() );
		$active = NGC_Talent_Settings::is_active();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Talent Intelligence', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Decision support for tutor suitability. Scores never approve or reject tutors. Safeguarding stays separate.', 'nextgencompanion' ); ?></p>

			<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'nextgencompanion' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['evaluated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Evaluation completed — review the explanation below. No tutor status was changed.', 'nextgencompanion' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! $active ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Talent Intelligence is currently inactive (safe default). Enable and set mode to BRIDGE_NATIVE to score applications.', 'nextgencompanion' ); ?></p></div>
			<?php endif; ?>

			<div class="card" style="max-width:880px;padding:1em 1.25em;margin:1em 0;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></h2>
				<p><?php echo esc_html( (string) ( $health['message'] ?? '' ) ); ?></p>
				<ul style="list-style:disc;margin-left:1.25em;">
					<li><?php esc_html_e( 'Mode:', 'nextgencompanion' ); ?> <code><?php echo esc_html( (string) ( $health['mode'] ?? '' ) ); ?></code></li>
					<li><?php esc_html_e( 'Auto-approve / auto-reject:', 'nextgencompanion' ); ?> <strong><?php esc_html_e( 'FORBIDDEN', 'nextgencompanion' ); ?></strong></li>
					<li><?php esc_html_e( 'Model:', 'nextgencompanion' ); ?> <code><?php echo esc_html( NGC_Talent_Settings::MODEL_VERSION ); ?></code></li>
				</ul>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngc_talent_save" />
				<?php wp_nonce_field( 'ngc_talent_save' ); ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th scope="row"><?php esc_html_e( 'Enable', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $cfg['enabled'] ) ); ?> /> <?php esc_html_e( 'Master switch', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th scope="row"><label for="ngc-talent-mode"><?php esc_html_e( 'Mode', 'nextgencompanion' ); ?></label></th>
						<td><select id="ngc-talent-mode" name="mode">
							<?php foreach ( [ 'DISABLED', 'BRIDGE_NATIVE', 'HYBRID', 'DEGRADED', 'MAINTENANCE' ] as $m ) : ?>
								<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $cfg['mode'], $m ); ?>><?php echo esc_html( $m ); ?></option>
							<?php endforeach; ?>
						</select></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Evaluate applications', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="evaluate_applications" value="1" <?php checked( ! empty( $cfg['evaluate_applications'] ) ); ?> /> <?php esc_html_e( 'Queue suitability scores on application submit', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Rank Find-a-Tutor pool', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="rank_find_tutor" value="1" <?php checked( ! empty( $cfg['rank_find_tutor'] ) ); ?> /> <?php esc_html_e( 'Re-order after hard filters only (never expands the pool)', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'NLP sidecar', 'nextgencompanion' ); ?></th>
						<td>
							<label><input type="checkbox" name="nlp_sidecar_enabled" value="1" <?php checked( ! empty( $cfg['nlp_sidecar_enabled'] ) ); ?> /> <?php esc_html_e( 'Blend optional text similarity', 'nextgencompanion' ); ?></label><br />
							<input type="url" class="regular-text" name="nlp_sidecar_url" value="<?php echo esc_attr( (string) $cfg['nlp_sidecar_url'] ); ?>" placeholder="http://talent-nlp:8090" aria-label="<?php esc_attr_e( 'NLP sidecar URL', 'nextgencompanion' ); ?>" />
						</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Agent tools', 'nextgencompanion' ); ?></th>
						<td><label><input type="checkbox" name="agent_tools_enabled" value="1" <?php checked( ! empty( $cfg['agent_tools_enabled'] ) ); ?> /> <?php esc_html_e( 'Expose permissioned talent.evaluate / talent.explain tools (no approve)', 'nextgencompanion' ); ?></label></td></tr>
					<tr><th scope="row"><label for="ngc-req-subjects"><?php esc_html_e( 'Default requirement subjects', 'nextgencompanion' ); ?></label></th>
						<td><input id="ngc-req-subjects" type="text" class="regular-text" name="req_subjects" value="<?php echo esc_attr( implode( ', ', (array) ( $req['subjects'] ?? [] ) ) ); ?>" /></td></tr>
					<tr><th scope="row"><label for="ngc-req-grades"><?php esc_html_e( 'Default grades', 'nextgencompanion' ); ?></label></th>
						<td><input id="ngc-req-grades" type="text" class="regular-text" name="req_grades" value="<?php echo esc_attr( implode( ', ', (array) ( $req['grades'] ?? [] ) ) ); ?>" /></td></tr>
					<tr><th scope="row"><label for="ngc-req-modes"><?php esc_html_e( 'Delivery modes', 'nextgencompanion' ); ?></label></th>
						<td><input id="ngc-req-modes" type="text" class="regular-text" name="req_modes" value="<?php echo esc_attr( implode( ', ', (array) ( $req['deliveryModes'] ?? [] ) ) ); ?>" /></td></tr>
				</tbody></table>
				<?php submit_button( __( 'Save Talent settings', 'nextgencompanion' ) ); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Manual evaluation', 'nextgencompanion' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Produces an explainable suitability score for review. Does not change application or tutor status.', 'nextgencompanion' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngc_talent_evaluate_demo" />
				<?php wp_nonce_field( 'ngc_talent_evaluate_demo' ); ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th scope="row"><label for="cand_subjects"><?php esc_html_e( 'Subjects', 'nextgencompanion' ); ?></label></th><td><input id="cand_subjects" name="cand_subjects" class="regular-text" placeholder="Mathematics, Physics" /></td></tr>
					<tr><th scope="row"><label for="cand_grades"><?php esc_html_e( 'Grades', 'nextgencompanion' ); ?></label></th><td><input id="cand_grades" name="cand_grades" class="regular-text" placeholder="10, 11, 12" /></td></tr>
					<tr><th scope="row"><label for="cand_province"><?php esc_html_e( 'Province', 'nextgencompanion' ); ?></label></th><td><input id="cand_province" name="cand_province" class="regular-text" /></td></tr>
					<tr><th scope="row"><label for="cand_modes"><?php esc_html_e( 'Delivery', 'nextgencompanion' ); ?></label></th><td><input id="cand_modes" name="cand_modes" class="regular-text" placeholder="online" /></td></tr>
					<tr><th scope="row"><label for="cand_bio"><?php esc_html_e( 'Bio', 'nextgencompanion' ); ?></label></th><td><textarea id="cand_bio" name="cand_bio" rows="4" class="large-text"></textarea></td></tr>
				</tbody></table>
				<?php submit_button( __( 'Evaluate (decision support only)', 'nextgencompanion' ), 'secondary' ); ?>
			</form>

			<?php if ( is_array( $last ) ) : ?>
				<?php self::render_explanation( $last ); ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Recent evaluations', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'ID', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Candidate', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Score', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Recommendation', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Model', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'When', 'nextgencompanion' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No evaluations yet.', 'nextgencompanion' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $items as $row ) : ?>
						<tr>
							<td><?php echo (int) $row['id']; ?></td>
							<td><?php echo esc_html( (string) $row['candidate_id'] ); ?></td>
							<td><?php echo esc_html( null === $row['score'] ? '—' : (string) $row['score'] ); ?></td>
							<td><?php echo esc_html( (string) $row['recommendation'] ); ?></td>
							<td><?php echo esc_html( (string) $row['model_version'] ); ?></td>
							<td><?php echo esc_html( (string) $row['created_at'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Structured explanation (AI UX: transparent, scannable, non-authoritative).
	 *
	 * @param array<string,mixed> $eval Evaluation.
	 */
	private static function render_explanation( array $eval ) {
		$score = $eval['score'] ?? null;
		$rec   = (string) ( $eval['recommendation'] ?? '' );
		?>
		<div class="card" style="max-width:880px;padding:1em 1.25em;margin:1.5em 0;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Explanation', 'nextgencompanion' ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'Suitability:', 'nextgencompanion' ); ?></strong>
				<?php echo null === $score ? '—' : esc_html( (string) $score ); ?>%
				— <code><?php echo esc_html( $rec ); ?></code>
			</p>
			<p class="description"><?php esc_html_e( 'This is decision support only. Qualification claims are not verified credentials.', 'nextgencompanion' ); ?></p>

			<?php if ( ! empty( $eval['components'] ) && is_array( $eval['components'] ) ) : ?>
				<h3><?php esc_html_e( 'Components', 'nextgencompanion' ); ?></h3>
				<table class="widefat striped" style="max-width:100%;">
					<thead><tr><th><?php esc_html_e( 'Factor', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Score', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Weight', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $eval['components'] as $c ) : ?>
						<?php if ( ! is_array( $c ) ) { continue; } ?>
						<tr>
							<td><?php echo esc_html( (string) ( $c['key'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( isset( $c['score'] ) ? (string) $c['score'] : '—' ); ?></td>
							<td><?php echo esc_html( isset( $c['weight'] ) ? (string) $c['weight'] : '—' ); ?></td>
							<td><?php echo esc_html( (string) ( $c['status'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $eval['matchedCriteria'] ) ) : ?>
				<h3><?php esc_html_e( 'Matched', 'nextgencompanion' ); ?></h3>
				<ul style="list-style:disc;margin-left:1.25em;">
					<?php foreach ( (array) $eval['matchedCriteria'] as $m ) : ?>
						<li><?php echo esc_html( is_array( $m ) ? wp_json_encode( $m ) : (string) $m ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $eval['gaps'] ) ) : ?>
				<h3><?php esc_html_e( 'Gaps', 'nextgencompanion' ); ?></h3>
				<ul style="list-style:disc;margin-left:1.25em;">
					<?php foreach ( (array) $eval['gaps'] as $g ) : ?>
						<li><?php echo esc_html( is_array( $g ) ? wp_json_encode( $g ) : (string) $g ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $eval['warnings'] ) ) : ?>
				<p><strong><?php esc_html_e( 'Warnings:', 'nextgencompanion' ); ?></strong> <?php echo esc_html( implode( '; ', array_map( 'strval', (array) $eval['warnings'] ) ) ); ?></p>
			<?php endif; ?>

			<details>
				<summary><?php esc_html_e( 'Raw JSON (audit)', 'nextgencompanion' ); ?></summary>
				<pre style="max-height:240px;overflow:auto;background:#f6f7f7;padding:1em;"><?php echo esc_html( wp_json_encode( $eval, JSON_PRETTY_PRINT ) ); ?></pre>
			</details>
		</div>
		<?php
	}
}
