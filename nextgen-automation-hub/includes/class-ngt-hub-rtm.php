<?php
/**
 * Real-time messaging hub with SSE streaming.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_RTM {

	public static function register_hooks(): void {
		add_shortcode( 'ngt_rtm', [ __CLASS__, 'shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
	}

	public static function enqueue_assets(): void {
		$force = (bool) apply_filters( 'ngt_enqueue_rtm_assets', false );
		global $post;
		$has_shortcode = $post && has_shortcode( (string) $post->post_content, 'ngt_rtm' );
		if ( ! $force && ! $has_shortcode ) {
			return;
		}
		wp_enqueue_style( 'ngt-hub', NGT_HUB_URL . 'assets/css/ngt-hub.css', [], NGT_Hub::VERSION );
		if ( $has_shortcode ) {
			wp_enqueue_script( 'ngt-rtm', NGT_HUB_URL . 'assets/js/ngt-rtm.js', [], NGT_Hub::VERSION, true );
			wp_localize_script(
				'ngt-rtm',
				'NGTRTM',
				[
					'rest'  => class_exists( 'NGT_Hub_Companion_Delegate', false )
						? NGT_Hub_Companion_Delegate::rest_url( '/rtm/rooms' )
						: rest_url( 'ngt/v1/rtm/rooms' ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'sse'   => class_exists( 'NGT_Hub_Companion_Delegate', false )
						? NGT_Hub_Companion_Delegate::rest_url( '/rtm/stream' )
						: rest_url( 'ngt/v1/rtm/stream' ),
				]
			);
		}
	}

	public static function shortcode(): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="ngt-wrap"><p>' . esc_html__( 'Please log in to access the communication hub.', 'nextgen-automation-hub' ) . '</p></div>';
		}

		$rooms = self::get_rooms();
		$default = ! empty( $rooms[0]['id'] ) ? (int) $rooms[0]['id'] : 0;

		ob_start();
		?>
		<div class="ngt-wrap ngt-rtm" data-default-room="<?php echo esc_attr( (string) $default ); ?>">
			<div class="ngt-rooms">
				<h3><?php esc_html_e( 'Rooms', 'nextgen-automation-hub' ); ?></h3>
				<?php foreach ( $rooms as $room ) : ?>
					<button type="button" class="ngt-room ngt-button" data-room="<?php echo esc_attr( (string) $room['id'] ); ?>">
						<?php echo esc_html( $room['title'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="ngt-chat">
				<div class="ngt-chat-head">
					<strong id="ngt-room-title"><?php esc_html_e( 'Communication Hub', 'nextgen-automation-hub' ); ?></strong>
					<button type="button" id="ngt-video" class="ngt-button"><?php esc_html_e( 'Video', 'nextgen-automation-hub' ); ?></button>
				</div>
				<div id="ngt-messages" class="ngt-messages" aria-live="polite"></div>
				<form id="ngt-message-form">
					<input type="text" id="ngt-message" placeholder="<?php esc_attr_e( 'Type a message…', 'nextgen-automation-hub' ); ?>" autocomplete="off" />
					<button type="submit" class="ngt-button"><?php esc_html_e( 'Send', 'nextgen-automation-hub' ); ?></button>
				</form>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function register_rest(): void {
		$rtm_ns = class_exists( 'NGT_Hub_Companion_Delegate', false )
			? NGT_Hub_Companion_Delegate::rest_namespace() . '/rtm'
			: 'ngt/v1/rtm';

		register_rest_route(
			$rtm_ns,
			'/rooms',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_rooms' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			$rtm_ns,
			'/messages/(?P<room_id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_messages' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			$rtm_ns,
			'/messages',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'rest_post_message' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			$rtm_ns,
			'/stream',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_sse_stream' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function rest_rooms(): WP_REST_Response {
		return rest_ensure_response( self::get_rooms() );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_messages( WP_REST_Request $request ) {
		$room_id = (int) $request['room_id'];
		return rest_ensure_response( self::fetch_messages( $room_id, 50 ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_post_message( WP_REST_Request $request ) {
		$room_id = (int) $request->get_param( 'room_id' );
		$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
		if ( ! $room_id || ! $message ) {
			return new WP_Error( 'ngt_invalid', __( 'Room and message required.', 'nextgen-automation-hub' ), [ 'status' => 400 ] );
		}

		$id = self::insert_message( $room_id, get_current_user_id(), $message, 'user' );
		return rest_ensure_response( [ 'id' => $id ] );
	}

	/**
	 * SSE stream endpoint.
	 */
	public static function rest_sse_stream( WP_REST_Request $request ) {
		$room_id  = (int) $request->get_param( 'room_id' );
		$since_id = (int) $request->get_param( 'since_id' );

		if ( headers_sent() ) {
			return new WP_Error( 'ngt_headers_sent', 'Headers already sent' );
		}

		nocache_headers();
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		$iterations = 0;
		while ( $iterations < 30 && ! connection_aborted() ) {
			$messages = self::fetch_messages_since( $room_id, $since_id );
			if ( $messages ) {
				$since_id = (int) end( $messages )['id'];
				echo 'event: messages' . "\n";
				echo 'data: ' . wp_json_encode( $messages ) . "\n\n";
			} else {
				echo ": heartbeat\n\n";
			}
			if ( function_exists( 'ob_flush' ) ) {
				@ob_flush();
			}
			flush();
			sleep( 2 );
			++$iterations;
		}
		exit;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_rooms(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			'SELECT id, slug, title FROM ' . NGT_Hub_Database::table( 'rtm_rooms' ) . ' ORDER BY id ASC',
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	public static function insert_message( int $room_id, int $user_id, string $message, string $type = 'user' ): int {
		global $wpdb;
		$wpdb->insert(
			NGT_Hub_Database::table( 'rtm_messages' ),
			[
				'room_id'      => $room_id,
				'user_id'      => $user_id,
				'message'      => $message,
				'message_type' => $type,
			],
			[ '%d', '%d', '%s', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}

	public static function post_system_message( string $room_slug, string $message ): void {
		global $wpdb;
		$room_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . NGT_Hub_Database::table( 'rtm_rooms' ) . ' WHERE slug = %s LIMIT 1',
				$room_slug
			)
		);
		if ( $room_id ) {
			self::insert_message( $room_id, 0, $message, 'system' );
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function fetch_messages( int $room_id, int $limit = 50 ): array {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'rtm_messages' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.*, u.display_name FROM {$table} m LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id WHERE m.room_id = %d ORDER BY m.id DESC LIMIT %d",
				$room_id,
				$limit
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return [];
		}
		foreach ( $rows as &$row ) {
			$row['display_name'] = $row['display_name'] ?: __( 'System', 'nextgen-automation-hub' );
		}
		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function fetch_messages_since( int $room_id, int $since_id ): array {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'rtm_messages' );
		if ( $room_id <= 0 ) {
			return [];
		}
		$sql = "SELECT m.*, u.display_name FROM {$table} m LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id WHERE m.room_id = %d";
		$args = [ $room_id ];
		if ( $since_id > 0 ) {
			$sql .= ' AND m.id > %d';
			$args[] = $since_id;
		}
		$sql .= ' ORDER BY m.id ASC LIMIT 50';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}
}
