<?php
/**
 * Orchestration Cockpit — realtime monitoring UI (servers, VPS, APIs, schedules, alerts).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screen: drag/resize workspace, CRUD grids, BeyondInfinity chrome.
 */
final class NGC_Orchestration_Cockpit_Admin {

	public const PAGE           = 'ngc-orchestration-cockpit';
	public const ENTITIES_OPT   = 'ngc_orchestration_cockpit_entities';
	public const LAYOUT_META    = 'ngc_cockpit_layout_v1';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'admin_post_ngc_cockpit_save_config', [ __CLASS__, 'handle_save_config' ] );
		add_action( 'admin_post_ngc_cockpit_emergency', [ __CLASS__, 'handle_emergency' ] );
		add_action( 'wp_ajax_ngc_cockpit_snapshot', [ __CLASS__, 'ajax_snapshot' ] );
		add_action( 'wp_ajax_ngc_cockpit_entity', [ __CLASS__, 'ajax_entity' ] );
		add_action( 'wp_ajax_ngc_cockpit_layout', [ __CLASS__, 'ajax_layout' ] );
	}

	/**
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE !== $page && false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}
		$ver = NGC_VERSION;
		wp_enqueue_style( 'ngc-orchestration-cockpit', NGC_PLUGIN_URL . 'assets/css/ngc-orchestration-cockpit.css', [ 'dashicons' ], $ver );
		wp_enqueue_script( 'ngc-orchestration-cockpit', NGC_PLUGIN_URL . 'assets/js/ngc-orchestration-cockpit.js', [], $ver, true );
		$snap = class_exists( 'NGC_Observability_Service' ) ? NGC_Observability_Service::cockpit_snapshot() : [];
		wp_localize_script(
			'ngc-orchestration-cockpit',
			'NGC_COCKPIT',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restRoot'  => esc_url_raw( rest_url( 'ngc/v1/admin' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'ajaxNonce' => wp_create_nonce( 'ngc_cockpit' ),
				'snapshot'  => $snap,
				'entities'  => self::get_entities(),
				'layout'    => self::get_layout(),
				'pollMs'    => 20000,
				'toastMs'   => 5000,
				'i18n'      => [
					'refresh'      => __( 'Refreshing…', 'nextgencompanion' ),
					'confirmStop'  => __( 'Engage emergency stop? All agent actions will pause.', 'nextgencompanion' ),
					'confirmResume'=> __( 'Resume agents from emergency stop?', 'nextgencompanion' ),
					'confirmDelete'=> __( 'Delete this row permanently?', 'nextgencompanion' ),
					'added'        => __( 'Row added.', 'nextgencompanion' ),
					'updated'      => __( 'Row updated.', 'nextgencompanion' ),
					'deleted'      => __( 'Row deleted.', 'nextgencompanion' ),
					'savedLayout'  => __( 'Layout saved.', 'nextgencompanion' ),
					'error'        => __( 'Operation failed.', 'nextgencompanion' ),
					'edit'         => __( 'Edit', 'nextgencompanion' ),
					'update'       => __( 'Update', 'nextgencompanion' ),
					'delete'       => __( 'Delete', 'nextgencompanion' ),
					'add'          => __( 'Add', 'nextgencompanion' ),
					'cancel'       => __( 'Cancel', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_entities() {
		$raw = get_option( self::ENTITIES_OPT, [] );
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}
		$types = [ 'connectivity', 'apis', 'schedules', 'processes', 'agents' ];
		$out   = [ '_hidden' => [] ];
		foreach ( $types as $type ) {
			$rows = isset( $raw[ $type ] ) && is_array( $raw[ $type ] ) ? array_values( $raw[ $type ] ) : [];
			$out[ $type ] = $rows;
			$out['_hidden'][ $type ] = isset( $raw['_hidden'][ $type ] ) && is_array( $raw['_hidden'][ $type ] )
				? array_values( array_map( 'strval', $raw['_hidden'][ $type ] ) )
				: [];
		}
		return $out;
	}

	/**
	 * @param array<string, array<int, array<string, mixed>>> $entities Entities.
	 */
	public static function save_entities( array $entities ) {
		update_option( self::ENTITIES_OPT, $entities, false );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_layout() {
		$user = get_current_user_id();
		$lay  = $user ? get_user_meta( $user, self::LAYOUT_META, true ) : [];
		return is_array( $lay ) ? $lay : [];
	}

	/**
	 * Render page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		$snap   = class_exists( 'NGC_Observability_Service' ) ? NGC_Observability_Service::cockpit_snapshot() : [];
		$config = (array) ( $snap['config'] ?? [] );
		$status = (array) ( $snap['status'] ?? [ 'label' => 'UNKNOWN', 'level' => 'warning' ] );
		$paused = ! empty( $snap['emergency_stop'] );
		$flash  = isset( $_GET['saved'] ) ? 'config' : ( isset( $_GET['emergency'] ) ? 'emergency' : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap ngc-cockpit-wrap" data-testid="ngc-orchestration-cockpit" data-flash="<?php echo esc_attr( $flash ); ?>">
			<span class="ngbi-agent-badge ngc-cockpit-bi-badge" aria-label="<?php esc_attr_e( 'NEXTGEN-BEYOND-INFINITY UI', 'nextgencompanion' ); ?>">NEXTGEN-BEYOND-INFINITY UI</span>

			<header class="ngc-cockpit-topbar">
				<div class="ngc-cockpit-topbar__title-block">
					<h1 class="ngc-cockpit-title"><?php esc_html_e( 'Orchestration Cockpit', 'nextgencompanion' ); ?></h1>
					<p class="ngc-cockpit-subtitle"><?php esc_html_e( 'Realtime monitoring · connectivity · schedules · background processes · alerts', 'nextgencompanion' ); ?></p>
				</div>
				<div class="ngc-cockpit-topbar__actions">
					<div class="ngc-cockpit-status" data-level="<?php echo esc_attr( (string) ( $status['level'] ?? 'info' ) ); ?>">
						<span class="ngc-cockpit-status__label"><?php esc_html_e( 'System status', 'nextgencompanion' ); ?></span>
						<strong id="ngc-cockpit-global-status"><?php echo esc_html( (string) ( $status['label'] ?? 'READY' ) ); ?></strong>
					</div>
					<button type="button" class="button" id="ngc-cockpit-refresh"><?php esc_html_e( 'Refresh', 'nextgencompanion' ); ?></button>
					<button type="button" class="button" id="ngc-cockpit-reset-layout"><?php esc_html_e( 'Reset layout', 'nextgencompanion' ); ?></button>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngc-cockpit-emergency-form">
						<?php wp_nonce_field( 'ngc_cockpit_emergency' ); ?>
						<input type="hidden" name="action" value="ngc_cockpit_emergency" />
						<input type="hidden" name="paused" value="<?php echo $paused ? '0' : '1'; ?>" />
						<button type="submit" class="button <?php echo $paused ? 'button-primary' : 'button-link-delete'; ?>" id="ngc-cockpit-emergency">
							<?php echo $paused ? esc_html__( 'Resume agents', 'nextgencompanion' ) : esc_html__( 'Emergency stop', 'nextgencompanion' ); ?>
						</button>
					</form>
				</div>
			</header>

			<div class="ngc-cockpit-workspace" id="ngc-cockpit-workspace" aria-label="<?php esc_attr_e( 'Draggable monitoring workspace', 'nextgencompanion' ); ?>">
				<?php
				$widgets = [
					[ 'id' => 'kpi', 'title' => __( 'Live KPIs', 'nextgencompanion' ), 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 2 ],
					[ 'id' => 'mem', 'title' => __( 'Host memory', 'nextgencompanion' ), 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 4 ],
					[ 'id' => 'cpu', 'title' => __( 'Load average', 'nextgencompanion' ), 'x' => 6, 'y' => 2, 'w' => 6, 'h' => 4 ],
					[ 'id' => 'connectivity', 'title' => __( 'Connectivity', 'nextgencompanion' ), 'x' => 0, 'y' => 6, 'w' => 6, 'h' => 6 ],
					[ 'id' => 'apis', 'title' => __( 'API matrix', 'nextgencompanion' ), 'x' => 6, 'y' => 6, 'w' => 6, 'h' => 6 ],
					[ 'id' => 'schedules', 'title' => __( 'Schedules & cron', 'nextgencompanion' ), 'x' => 0, 'y' => 12, 'w' => 7, 'h' => 6 ],
					[ 'id' => 'processes', 'title' => __( 'Background processes', 'nextgencompanion' ), 'x' => 7, 'y' => 12, 'w' => 5, 'h' => 6 ],
					[ 'id' => 'agents', 'title' => __( 'Agent swarms', 'nextgencompanion' ), 'x' => 0, 'y' => 18, 'w' => 8, 'h' => 6 ],
					[ 'id' => 'alerts', 'title' => __( 'Live alerts', 'nextgencompanion' ), 'x' => 8, 'y' => 18, 'w' => 4, 'h' => 6 ],
					[ 'id' => 'config', 'title' => __( 'Infrastructure config', 'nextgencompanion' ), 'x' => 0, 'y' => 24, 'w' => 5, 'h' => 7 ],
					[ 'id' => 'architecture', 'title' => __( 'Architecture', 'nextgencompanion' ), 'x' => 5, 'y' => 24, 'w' => 7, 'h' => 7 ],
				];
				foreach ( $widgets as $w ) :
					?>
				<article class="ngc-cockpit-widget" data-widget="<?php echo esc_attr( $w['id'] ); ?>" data-x="<?php echo (int) $w['x']; ?>" data-y="<?php echo (int) $w['y']; ?>" data-w="<?php echo (int) $w['w']; ?>" data-h="<?php echo (int) $w['h']; ?>" data-default-x="<?php echo (int) $w['x']; ?>" data-default-y="<?php echo (int) $w['y']; ?>" data-default-w="<?php echo (int) $w['w']; ?>" data-default-h="<?php echo (int) $w['h']; ?>">
					<header class="ngc-cockpit-widget__head">
						<span class="ngc-cockpit-widget__drag" title="<?php esc_attr_e( 'Drag', 'nextgencompanion' ); ?>" aria-hidden="true">⋮⋮</span>
						<h2><?php echo esc_html( $w['title'] ); ?></h2>
					</header>
					<div class="ngc-cockpit-widget__body" data-widget-body="<?php echo esc_attr( $w['id'] ); ?>">
						<?php if ( 'config' === $w['id'] ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngc-cockpit-config" id="ngc-cockpit-config-form">
							<?php wp_nonce_field( 'ngc_cockpit_save_config' ); ?>
							<input type="hidden" name="action" value="ngc_cockpit_save_config" />
							<label><?php esc_html_e( 'Project', 'nextgencompanion' ); ?>
								<input type="text" name="project_name" value="<?php echo esc_attr( (string) ( $config['project_name'] ?? '' ) ); ?>" />
							</label>
							<label><?php esc_html_e( 'Domain', 'nextgencompanion' ); ?>
								<input type="text" name="domain" value="<?php echo esc_attr( (string) ( $config['domain'] ?? '' ) ); ?>" />
							</label>
							<label><?php esc_html_e( 'DirectAdmin user', 'nextgencompanion' ); ?>
								<input type="text" name="da_user" value="<?php echo esc_attr( (string) ( $config['da_user'] ?? '' ) ); ?>" autocomplete="off" />
							</label>
							<label><?php esc_html_e( 'DA host / IP', 'nextgencompanion' ); ?>
								<input type="text" name="da_host" value="<?php echo esc_attr( (string) ( $config['da_host'] ?? '' ) ); ?>" autocomplete="off" />
							</label>
							<label><?php esc_html_e( 'Coolify / VPS ID', 'nextgencompanion' ); ?>
								<input type="text" name="vps_id" value="<?php echo esc_attr( (string) ( $config['vps_id'] ?? '' ) ); ?>" />
							</label>
							<label><?php esc_html_e( 'Agent Gateway URL', 'nextgencompanion' ); ?>
								<input type="url" name="gateway_url" value="<?php echo esc_attr( (string) ( $config['gateway_url'] ?? '' ) ); ?>" />
							</label>
							<?php submit_button( __( 'Save configuration', 'nextgencompanion' ), 'primary', 'submit', false ); ?>
						</form>
						<?php elseif ( 'mem' === $w['id'] ) : ?>
							<canvas id="ngc-cockpit-mem-chart" height="140"></canvas>
						<?php elseif ( 'cpu' === $w['id'] ) : ?>
							<canvas id="ngc-cockpit-cpu-chart" height="140"></canvas>
						<?php else : ?>
							<div class="ngc-cockpit-widget__mount" id="ngc-cockpit-mount-<?php echo esc_attr( $w['id'] ); ?>"></div>
						<?php endif; ?>
					</div>
					<span class="ngc-cockpit-widget__resize" title="<?php esc_attr_e( 'Resize', 'nextgencompanion' ); ?>" aria-hidden="true"></span>
				</article>
				<?php endforeach; ?>
			</div>

			<dialog id="ngc-cockpit-modal" class="ngc-cockpit-modal">
				<form method="dialog" id="ngc-cockpit-entity-form">
					<header>
						<h3 id="ngc-cockpit-modal-title"><?php esc_html_e( 'Edit row', 'nextgencompanion' ); ?></h3>
						<button type="button" class="button-link" id="ngc-cockpit-modal-close">&times;</button>
					</header>
					<input type="hidden" name="entity_type" id="ngc-cockpit-entity-type" />
					<input type="hidden" name="entity_id" id="ngc-cockpit-entity-id" />
					<label><?php esc_html_e( 'Label', 'nextgencompanion' ); ?>
						<input type="text" name="label" id="ngc-cockpit-field-label" required />
					</label>
					<label><?php esc_html_e( 'Detail / path', 'nextgencompanion' ); ?>
						<input type="text" name="detail" id="ngc-cockpit-field-detail" />
					</label>
					<label><?php esc_html_e( 'Status', 'nextgencompanion' ); ?>
						<select name="status" id="ngc-cockpit-field-status">
							<?php foreach ( [ 'up', 'down', 'warn', 'ok', 'ready', 'idle', 'configured', 'active', 'paused' ] as $st ) : ?>
								<option value="<?php echo esc_attr( $st ); ?>"><?php echo esc_html( $st ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<footer>
						<button type="button" class="button" id="ngc-cockpit-modal-cancel"><?php esc_html_e( 'Cancel', 'nextgencompanion' ); ?></button>
						<button type="submit" class="button button-primary" id="ngc-cockpit-modal-save"><?php esc_html_e( 'Update', 'nextgencompanion' ); ?></button>
					</footer>
				</form>
			</dialog>

			<div id="ngc-cockpit-toasts" class="ngc-cockpit-toasts" aria-live="polite" aria-relevant="additions"></div>
		</div>
		<?php
	}

	/**
	 * Save config.
	 */
	public static function handle_save_config() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_cockpit_save_config' );
		if ( class_exists( 'NGC_Observability_Service' ) ) {
			NGC_Observability_Service::save_cockpit_config( wp_unslash( $_POST ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&saved=1' ) );
		exit;
	}

	/**
	 * Emergency stop / resume.
	 */
	public static function handle_emergency() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_cockpit_emergency' );
		$paused = ! empty( $_POST['paused'] );
		if ( class_exists( 'NGC_Agent_Control_Plane' ) ) {
			NGC_Agent_Control_Plane::set_global_pause( $paused );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&emergency=' . ( $paused ? '1' : '0' ) ) );
		exit;
	}

	/**
	 * AJAX snapshot.
	 */
	public static function ajax_snapshot() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		check_ajax_referer( 'ngc_cockpit', 'nonce' );
		$snap = class_exists( 'NGC_Observability_Service' ) ? NGC_Observability_Service::cockpit_snapshot() : [];
		wp_send_json_success(
			[
				'snapshot' => $snap,
				'entities' => self::get_entities(),
			]
		);
	}

	/**
	 * AJAX entity CRUD.
	 */
	public static function ajax_entity() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'nextgencompanion' ) ], 403 );
		}
		check_ajax_referer( 'ngc_cockpit', 'nonce' );
		$op   = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
		$type = sanitize_key( wp_unslash( $_POST['entity_type'] ?? '' ) );
		$allowed = [ 'connectivity', 'apis', 'schedules', 'processes', 'agents' ];
		if ( ! in_array( $type, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid entity type.', 'nextgencompanion' ) ] );
		}
		$store = self::get_entities();
		$rows  = $store[ $type ];

		if ( 'create' === $op ) {
			$row = [
				'id'     => 'custom_' . wp_generate_password( 8, false, false ),
				'label'  => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
				'detail' => sanitize_text_field( wp_unslash( $_POST['detail'] ?? '' ) ),
				'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'ok' ) ),
				'source' => 'custom',
			];
			if ( '' === $row['label'] ) {
				wp_send_json_error( [ 'message' => __( 'Label is required.', 'nextgencompanion' ) ] );
			}
			$rows[]         = $row;
			$store[ $type ] = $rows;
			self::save_entities( $store );
			wp_send_json_success( [ 'entities' => $store, 'row' => $row, 'message' => __( 'Row added.', 'nextgencompanion' ) ] );
		}

		if ( 'update' === $op ) {
			$id = sanitize_key( wp_unslash( $_POST['entity_id'] ?? '' ) );
			$found = false;
			foreach ( $rows as &$row ) {
				if ( (string) ( $row['id'] ?? '' ) !== $id ) {
					continue;
				}
				$row['label']  = sanitize_text_field( wp_unslash( $_POST['label'] ?? $row['label'] ) );
				$row['detail'] = sanitize_text_field( wp_unslash( $_POST['detail'] ?? ( $row['detail'] ?? '' ) ) );
				$row['status'] = sanitize_key( wp_unslash( $_POST['status'] ?? ( $row['status'] ?? 'ok' ) ) );
				$found         = true;
				break;
			}
			unset( $row );
			if ( ! $found ) {
				// Overlay update for live snapshot rows — store as custom override with same id.
				$rows[] = [
					'id'     => $id,
					'label'  => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
					'detail' => sanitize_text_field( wp_unslash( $_POST['detail'] ?? '' ) ),
					'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'ok' ) ),
					'source' => 'override',
				];
			}
			$store[ $type ] = array_values( $rows );
			self::save_entities( $store );
			wp_send_json_success( [ 'entities' => $store, 'message' => __( 'Row updated.', 'nextgencompanion' ) ] );
		}

		if ( 'delete' === $op ) {
			$id = sanitize_key( wp_unslash( $_POST['entity_id'] ?? '' ) );
			$store[ $type ] = array_values(
				array_filter(
					$rows,
					static function ( $row ) use ( $id ) {
						return (string) ( $row['id'] ?? '' ) !== $id;
					}
				)
			);
			// Also track deleted live ids so they stay hidden.
			$hidden = isset( $store['_hidden'][ $type ] ) && is_array( $store['_hidden'][ $type ] ) ? $store['_hidden'][ $type ] : [];
			if ( ! isset( $store['_hidden'] ) || ! is_array( $store['_hidden'] ) ) {
				$store['_hidden'] = [];
			}
			$hidden[]                 = $id;
			$store['_hidden'][ $type ] = array_values( array_unique( array_map( 'strval', $hidden ) ) );
			self::save_entities( $store );
			wp_send_json_success( [ 'entities' => $store, 'message' => __( 'Row deleted.', 'nextgencompanion' ) ] );
		}

		wp_send_json_error( [ 'message' => __( 'Unknown operation.', 'nextgencompanion' ) ] );
	}

	/**
	 * Persist widget layout.
	 */
	public static function ajax_layout() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		check_ajax_referer( 'ngc_cockpit', 'nonce' );
		$raw = wp_unslash( $_POST['layout'] ?? '' );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		if ( ! is_array( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid layout.', 'nextgencompanion' ) ] );
		}
		$clean = [];
		foreach ( $data as $id => $box ) {
			if ( ! is_array( $box ) ) {
				continue;
			}
			$clean[ sanitize_key( (string) $id ) ] = [
				'x' => max( 0, (int) ( $box['x'] ?? 0 ) ),
				'y' => max( 0, (int) ( $box['y'] ?? 0 ) ),
				'w' => max( 2, min( 12, (int) ( $box['w'] ?? 4 ) ) ),
				'h' => max( 2, min( 16, (int) ( $box['h'] ?? 4 ) ) ),
			];
		}
		update_user_meta( get_current_user_id(), self::LAYOUT_META, $clean );
		wp_send_json_success( [ 'layout' => $clean, 'message' => __( 'Layout saved.', 'nextgencompanion' ) ] );
	}
}
