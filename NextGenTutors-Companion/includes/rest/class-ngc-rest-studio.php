<?php
/**
 * REST API for Automation Studio.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ngc/v1/studio/* endpoints.
 */
class NGC_Rest_Studio {

	/**
	 * Register routes.
	 */
	public static function register() {
		$ns = NGC_Rest::NAMESPACE;

		register_rest_route(
			$ns,
			'/studio/workflows',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'list_workflows' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'create_workflow' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/studio/workflows/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_workflow' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ __CLASS__, 'update_workflow' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ __CLASS__, 'delete_workflow' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/studio/workflows/(?P<id>\d+)/publish',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'publish_workflow' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/workflows/(?P<id>\d+)/simulate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'simulate_workflow' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/workflows/(?P<id>\d+)/execute',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'execute_workflow' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/triggers',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'list_triggers' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/node-types',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'list_node_types' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/templates',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'list_templates' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/templates/(?P<key>[a-z0-9_\-]+)/instantiate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'instantiate_template' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/import',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'import_sources' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'import_run' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/studio/executions',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'list_executions' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/executions/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get_execution' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/executions/(?P<id>\d+)/replay',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'replay_execution' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/verify',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'verify' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/runtime',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'runtime_status' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			$ns,
			'/studio/events/emit',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'emit_event' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		self::register_forms_routes( $ns );
		self::register_emails_routes( $ns );
		self::register_notifications_routes( $ns );
		self::register_dashboards_routes( $ns );
		self::register_live_routes( $ns );
	}

	/**
	 * @param string $ns Namespace.
	 */
	private static function register_forms_routes( $ns ) {
		register_rest_route( $ns, '/studio/forms', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'list_forms' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'create_form' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/forms/fields', [
			'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'form_fields' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/forms/(?P<id>\d+)', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'get_form' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::EDITABLE, 'callback' => [ __CLASS__, 'update_form' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::DELETABLE, 'callback' => [ __CLASS__, 'delete_form' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/forms/(?P<id>\d+)/publish', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'publish_form' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/forms/(?P<key>[a-z0-9_\-]+)/submit', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'submit_form' ], 'permission_callback' => [ __CLASS__, 'can_submit_form' ],
		] );
	}

	/**
	 * @param string $ns Namespace.
	 */
	private static function register_emails_routes( $ns ) {
		register_rest_route( $ns, '/studio/emails', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'list_emails' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'create_email' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/emails/merge-fields', [
			'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'merge_fields' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/emails/(?P<id>\d+)', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'get_email' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::EDITABLE, 'callback' => [ __CLASS__, 'update_email' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::DELETABLE, 'callback' => [ __CLASS__, 'delete_email' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/emails/(?P<id>\d+)/publish', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'publish_email' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/emails/(?P<id>\d+)/test', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'test_email' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
	}

	/**
	 * @param string $ns Namespace.
	 */
	private static function register_notifications_routes( $ns ) {
		register_rest_route( $ns, '/studio/notifications', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'list_notifications' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'create_notification' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/notifications/channels', [
			'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'notification_channels' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/notifications/(?P<id>\d+)', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'get_notification' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::EDITABLE, 'callback' => [ __CLASS__, 'update_notification' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::DELETABLE, 'callback' => [ __CLASS__, 'delete_notification' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/notifications/(?P<id>\d+)/publish', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'publish_notification' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/notifications/(?P<id>\d+)/test', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'test_notification' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
	}

	/**
	 * @param string $ns Namespace.
	 */
	private static function register_dashboards_routes( $ns ) {
		register_rest_route( $ns, '/studio/dashboards', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'list_dashboards' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'create_dashboard' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/dashboards/widgets', [
			'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'dashboard_widgets' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/dashboards/roles', [
			'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'dashboard_roles' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( $ns, '/studio/dashboards/(?P<id>\d+)', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'get_dashboard' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::EDITABLE, 'callback' => [ __CLASS__, 'update_dashboard' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
			[ 'methods' => WP_REST_Server::DELETABLE, 'callback' => [ __CLASS__, 'delete_dashboard' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ] ],
		] );
		register_rest_route( $ns, '/studio/dashboards/(?P<id>\d+)/publish', [
			'methods' => WP_REST_Server::CREATABLE, 'callback' => [ __CLASS__, 'publish_dashboard' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
	}

	/**
	 * @param string $ns Namespace.
	 */
	private static function register_live_routes( $ns ) {
		register_rest_route( $ns, '/studio/live', [
			'methods' => WP_REST_Server::READABLE, 'callback' => [ __CLASS__, 'live_stream' ], 'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_workflows( $request ) {
		$status = sanitize_key( (string) $request->get_param( 'status' ) );
		return new WP_REST_Response( NGC_Studio_Repository::list_workflows( $status ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_workflow( $request ) {
		$result = NGC_Studio_Repository::create_workflow( (array) $request->get_json_params() );
		if ( empty( $result['ok'] ) ) {
			return NGC_Rest::error_response( 'create_failed', $result['message'] ?? '', 400 );
		}
		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_workflow( $request ) {
		$wf = NGC_Studio_Repository::get_workflow( (int) $request['id'] );
		if ( ! $wf ) {
			return NGC_Rest::error_response( 'not_found', __( 'Workflow not found.', 'nextgencompanion' ), 404 );
		}
		return new WP_REST_Response( $wf, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_workflow( $request ) {
		$id     = (int) $request['id'];
		$result = NGC_Studio::save_and_apply( $id, (array) $request->get_json_params() );
		if ( empty( $result['ok'] ) ) {
			return NGC_Rest::error_response( 'save_failed', implode( '; ', (array) ( $result['errors'] ?? [ $result['message'] ?? '' ] ) ), 400 );
		}
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_workflow( $request ) {
		$result = NGC_Studio_Repository::delete_workflow( (int) $request['id'] );
		if ( empty( $result['ok'] ) ) {
			return NGC_Rest::error_response( 'delete_failed', $result['message'] ?? '', 400 );
		}
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function publish_workflow( $request ) {
		return new WP_REST_Response( NGC_Studio::publish( (int) $request['id'] ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function simulate_workflow( $request ) {
		$wf = NGC_Studio_Repository::get_workflow( (int) $request['id'] );
		if ( ! $wf ) {
			return NGC_Rest::error_response( 'not_found', __( 'Workflow not found.', 'nextgencompanion' ), 404 );
		}
		$context = (array) ( $request->get_json_params()['context'] ?? [] );
		return new WP_REST_Response( NGC_Studio_Simulator::dry_run( $wf, $context ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function execute_workflow( $request ) {
		$wf = NGC_Studio_Repository::get_workflow( (int) $request['id'] );
		if ( ! $wf ) {
			return NGC_Rest::error_response( 'not_found', __( 'Workflow not found.', 'nextgencompanion' ), 404 );
		}
		$params  = (array) $request->get_json_params();
		$trigger = sanitize_key( (string) ( $params['trigger'] ?? 'MANUAL' ) );
		$context = (array) ( $params['context'] ?? [] );
		return new WP_REST_Response( NGC_Studio_Engine::execute( $wf, $context, $trigger, false ), 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function list_triggers() {
		return new WP_REST_Response( NGC_Studio_Triggers::catalog(), 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function list_node_types() {
		return new WP_REST_Response( NGC_Studio_Triggers::node_types(), 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function list_templates() {
		return new WP_REST_Response( NGC_Studio_Templates::all(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function instantiate_template( $request ) {
		$result = NGC_Studio_Templates::instantiate( (string) $request['key'] );
		if ( empty( $result['ok'] ) ) {
			return NGC_Rest::error_response( 'instantiate_failed', $result['message'] ?? '', 400 );
		}
		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function import_sources() {
		return new WP_REST_Response( NGC_Studio_Importer::sources(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function import_run( $request ) {
		$body  = $request->get_json_params();
		$force = is_array( $body ) ? ! empty( $body['force'] ) : false;
		return new WP_REST_Response( NGC_Studio_Importer::import_all( $force ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_executions( $request ) {
		$limit = (int) $request->get_param( 'limit' ) ?: 50;
		return new WP_REST_Response( NGC_Studio_Repository::list_executions( $limit ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function replay_execution( $request ) {
		$params   = (array) $request->get_json_params();
		$simulate = ! isset( $params['live'] ) || empty( $params['live'] );
		return new WP_REST_Response( NGC_Studio_Simulator::replay( (int) $request['id'], $simulate ), 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function verify() {
		return new WP_REST_Response( NGC_Studio_Verification::run(), 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function runtime_status() {
		return new WP_REST_Response( NGC_Studio_Runtime::status(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function emit_event( $request ) {
		$params = (array) $request->get_json_params();
		$event  = sanitize_key( (string) ( $params['event'] ?? '' ) );
		$ctx    = (array) ( $params['context'] ?? [] );
		NGC_Studio_Event_Bus::emit( $event, $ctx );
		return new WP_REST_Response( [ 'ok' => true, 'event' => $event ], 200 );
	}

	/** @return WP_REST_Response */ public static function list_forms() { return new WP_REST_Response( NGC_Studio_Repository::list_forms(), 200 ); }
	/** @param WP_REST_Request $request */ public static function create_form( $request ) { return new WP_REST_Response( NGC_Studio_Repository::create_form( (array) $request->get_json_params() ), 201 ); }
	/** @param WP_REST_Request $request */ public static function get_form( $request ) { $f = NGC_Studio_Repository::get_form( (int) $request['id'] ); return $f ? new WP_REST_Response( $f, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 ); }
	/** @param WP_REST_Request $request */ public static function update_form( $request ) { return new WP_REST_Response( NGC_Studio_Forms::save_and_apply( (int) $request['id'], (array) $request->get_json_params() ), 200 ); }
	/** @param WP_REST_Request $request */ public static function delete_form( $request ) { return new WP_REST_Response( NGC_Studio_Repository::delete_form( (int) $request['id'] ), 200 ); }
	/** @param WP_REST_Request $request */ public static function publish_form( $request ) { return new WP_REST_Response( NGC_Studio_Forms::publish( (int) $request['id'] ), 200 ); }
	/** @return WP_REST_Response */ public static function form_fields() { return new WP_REST_Response( NGC_Studio_Forms::field_catalog(), 200 ); }

	/**
	 * Rate-limited public form submit.
	 *
	 * @return bool|WP_Error
	 */
	public static function can_submit_form() {
		return NGC_Rest::public_throttled( 'studio_form_submit', 10, 600 );
	}

	/** @param WP_REST_Request $request */ public static function submit_form( $request ) {
		$payload = (array) $request->get_json_params();
		return new WP_REST_Response( NGC_Studio_Forms::submit_rest( (string) $request['key'], $payload ), 200 );
	}

	/** @return WP_REST_Response */ public static function list_emails() { return new WP_REST_Response( NGC_Studio_Repository::list_emails(), 200 ); }
	/** @param WP_REST_Request $request */ public static function create_email( $request ) { return new WP_REST_Response( NGC_Studio_Repository::create_email( (array) $request->get_json_params() ), 201 ); }
	/** @param WP_REST_Request $request */ public static function get_email( $request ) { $e = NGC_Studio_Repository::get_email( (int) $request['id'] ); return $e ? new WP_REST_Response( $e, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 ); }
	/** @param WP_REST_Request $request */ public static function update_email( $request ) { return new WP_REST_Response( NGC_Studio_Email::save_and_apply( (int) $request['id'], (array) $request->get_json_params() ), 200 ); }
	/** @param WP_REST_Request $request */ public static function delete_email( $request ) { return new WP_REST_Response( NGC_Studio_Repository::delete_email( (int) $request['id'] ), 200 ); }
	/** @param WP_REST_Request $request */ public static function publish_email( $request ) { return new WP_REST_Response( NGC_Studio_Email::publish( (int) $request['id'] ), 200 ); }
	/** @return WP_REST_Response */ public static function merge_fields() { return new WP_REST_Response( NGC_Studio_Email::merge_field_catalog(), 200 ); }
	/** @param WP_REST_Request $request */ public static function test_email( $request ) { return new WP_REST_Response( NGC_Studio_Email::test_send( (int) $request['id'], (array) $request->get_json_params() ), 200 ); }

	/** @return WP_REST_Response */ public static function list_notifications() { return new WP_REST_Response( NGC_Studio_Repository::list_notifications(), 200 ); }
	/** @param WP_REST_Request $request */ public static function create_notification( $request ) { return new WP_REST_Response( NGC_Studio_Repository::create_notification( (array) $request->get_json_params() ), 201 ); }
	/** @param WP_REST_Request $request */ public static function get_notification( $request ) { $n = NGC_Studio_Repository::get_notification( (int) $request['id'] ); return $n ? new WP_REST_Response( $n, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 ); }
	/** @param WP_REST_Request $request */ public static function update_notification( $request ) { return new WP_REST_Response( NGC_Studio_Notifications::save_and_apply( (int) $request['id'], (array) $request->get_json_params() ), 200 ); }
	/** @param WP_REST_Request $request */ public static function delete_notification( $request ) { return new WP_REST_Response( NGC_Studio_Repository::delete_notification( (int) $request['id'] ), 200 ); }
	/** @param WP_REST_Request $request */ public static function publish_notification( $request ) { return new WP_REST_Response( NGC_Studio_Notifications::publish( (int) $request['id'] ), 200 ); }
	/** @return WP_REST_Response */ public static function notification_channels() { return new WP_REST_Response( NGC_Studio_Notifications::channel_catalog(), 200 ); }
	/** @param WP_REST_Request $request */ public static function test_notification( $request ) {
		$n = NGC_Studio_Repository::get_notification( (int) $request['id'] );
		if ( ! $n ) {
			return NGC_Rest::error_response( 'not_found', '', 404 );
		}
		return new WP_REST_Response( NGC_Studio_Notifications::dispatch( (string) $n['notification_key'], (array) $request->get_json_params(), true ), 200 );
	}

	/** @param WP_REST_Request $request */ public static function get_execution( $request ) {
		$ex = NGC_Studio_Repository::get_execution( (int) $request['id'] );
		return $ex ? new WP_REST_Response( $ex, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 );
	}

	/** @param WP_REST_Request $request */ public static function live_stream( $request ) {
		$since = (int) $request->get_param( 'since' );
		if ( $request->get_param( 'sse' ) || 'text/event-stream' === $request->get_header( 'accept' ) ) {
			NGC_Studio_Stream::render_sse( $since, (int) $request->get_param( 'timeout' ) ?: 25 );
		}
		return new WP_REST_Response( NGC_Studio_Stream::poll( $since ), 200 );
	}

	/** @return WP_REST_Response */ public static function list_dashboards() { return new WP_REST_Response( NGC_Studio_Repository::list_dashboards(), 200 ); }
	/** @param WP_REST_Request $request */ public static function create_dashboard( $request ) { return new WP_REST_Response( NGC_Studio_Repository::create_dashboard( (array) $request->get_json_params() ), 201 ); }
	/** @param WP_REST_Request $request */ public static function get_dashboard( $request ) { $d = NGC_Studio_Repository::get_dashboard( (int) $request['id'] ); return $d ? new WP_REST_Response( $d, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 ); }
	/** @param WP_REST_Request $request */ public static function update_dashboard( $request ) { return new WP_REST_Response( NGC_Studio_Dashboards::save_and_apply( (int) $request['id'], (array) $request->get_json_params() ), 200 ); }
	/** @param WP_REST_Request $request */ public static function delete_dashboard( $request ) { return new WP_REST_Response( NGC_Studio_Repository::delete_dashboard( (int) $request['id'] ), 200 ); }
	/** @param WP_REST_Request $request */ public static function publish_dashboard( $request ) { return new WP_REST_Response( NGC_Studio_Dashboards::publish( (int) $request['id'] ), 200 ); }
	/** @return WP_REST_Response */ public static function dashboard_widgets() { return new WP_REST_Response( NGC_Studio_Dashboards::widget_catalog(), 200 ); }
	/** @return WP_REST_Response */ public static function dashboard_roles() { return new WP_REST_Response( NGC_Studio_Dashboards::role_catalog(), 200 ); }
}
