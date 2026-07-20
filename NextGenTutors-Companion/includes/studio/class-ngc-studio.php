<?php
/**
 * Automation Studio bootstrap.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main entry for the visual orchestration platform.
 */
class NGC_Studio {

	/**
	 * Hook registration.
	 */
	public static function init() {
		self::maybe_upgrade_tables();
		NGC_Studio_Runtime::init();
		add_action( 'admin_init', [ __CLASS__, 'maybe_upgrade_tables' ] );
		add_action( 'init', [ __CLASS__, 'maybe_seed_templates' ], 20 );
	}

	/**
	 * Ensure studio tables exist (dbDelta safe).
	 */
	public static function maybe_upgrade_tables() {
		global $wpdb;
		$required = [ 'studio_workflows', 'studio_dashboards' ];
		$missing  = false;
		foreach ( $required as $key ) {
			$table = NGC_Database::table( $key );
			if ( ! $table ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
				$missing = true;
				break;
			}
		}
		if ( $missing ) {
			NGC_Database::create_tables();
			NGC_Studio_Runtime::reload_all();
		}
	}

	/**
	 * Seed templates on first run.
	 */
	public static function maybe_seed_templates() {
		if ( ! get_option( 'ngc_studio_seeded' ) ) {
			NGC_Studio_Templates::seed_if_empty();
			update_option( 'ngc_studio_seeded', 1, false );
		}
		if ( ! NGC_Studio_Repository::list_forms() ) {
			NGC_Studio_Forms::seed_defaults();
		}
		if ( ! NGC_Studio_Repository::list_emails() ) {
			NGC_Studio_Email::seed_defaults();
		}
		if ( ! NGC_Studio_Repository::list_notifications() ) {
			NGC_Studio_Notifications::seed_defaults();
		}
		if ( ! NGC_Studio_Repository::list_dashboards() ) {
			NGC_Studio_Dashboards::seed_defaults();
		}
	}

	/**
	 * Save + compile + hot-apply workflow (realtime apply engine).
	 *
	 * @param int                  $id   Workflow ID.
	 * @param array<string, mixed> $data Payload.
	 * @return array{ok:bool,workflow?:array<string,mixed>,apply?:array<string,mixed>,errors?:array<int,string>}
	 */
	public static function save_and_apply( $id, $data ) {
		$update = NGC_Studio_Repository::update_workflow( $id, $data );
		if ( empty( $update['ok'] ) ) {
			return $update;
		}
		$wf = $update['workflow'];
		if ( empty( $wf ) ) {
			return [ 'ok' => false, 'message' => __( 'Workflow missing after save.', 'nextgencompanion' ) ];
		}

		$compile = NGC_Studio_Compiler::compile( (array) ( $wf['graph'] ?? [] ) );
		if ( empty( $compile['ok'] ) ) {
			return [ 'ok' => false, 'errors' => (array) ( $compile['errors'] ?? [] ) ];
		}

		NGC_Studio_Repository::update_workflow( $id, [ 'compiled' => $compile['compiled'] ] );
		$wf['compiled'] = $compile['compiled'];

		$apply = NGC_Studio_Runtime::apply_workflow( $wf );

		return [
			'ok'       => ! empty( $apply['ok'] ),
			'workflow' => NGC_Studio_Repository::get_workflow( $id ),
			'apply'    => $apply,
			'runtime'  => NGC_Studio_Runtime::status(),
		];
	}

	/**
	 * Publish workflow — activates triggers immediately.
	 *
	 * @param int $id Workflow ID.
	 * @return array<string, mixed>
	 */
	public static function publish( $id ) {
		NGC_Studio_Repository::update_workflow(
			$id,
			[
				'status' => 'published',
			]
		);
		$wf = NGC_Studio_Repository::get_workflow( $id );
		if ( ! $wf ) {
			return [ 'ok' => false ];
		}
		global $wpdb;
		$table = NGC_Database::table( 'studio_workflows' );
		if ( $table ) {
			$wpdb->update(
				$table,
				[
					'published_at' => current_time( 'mysql', true ),
					'version'      => (int) ( $wf['version'] ?? 1 ) + 1,
				],
				[ 'id' => (int) $id ]
			);
		}
		$wf = NGC_Studio_Repository::get_workflow( $id );
		return self::save_and_apply( $id, [ 'status' => 'published', 'graph' => $wf['graph'] ?? [] ] );
	}
}
