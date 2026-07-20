<?php
/**
 * Studio verification center.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Health checks for the automation studio.
 */
class NGC_Studio_Verification {

	/**
	 * @return array<string, mixed>
	 */
	public static function run() {
		$checks = [
			'tables'           => self::check_tables(),
			'runtime'          => self::check_runtime(),
			'adapters'         => self::check_adapters(),
			'templates'        => count( NGC_Studio_Templates::all() ),
			'published_count'  => count( NGC_Studio_Repository::list_workflows( 'published' ) ),
			'draft_count'      => count( NGC_Studio_Repository::list_workflows( 'draft' ) ),
			'active_triggers'  => count( NGC_Studio_Repository::list_active_triggers() ),
			'forms'            => count( NGC_Studio_Repository::list_forms() ),
			'emails'           => count( NGC_Studio_Repository::list_emails() ),
			'notifications'    => count( NGC_Studio_Repository::list_notifications() ),
			'dashboards'       => count( NGC_Studio_Repository::list_dashboards() ),
			'form_fields'      => count( NGC_Studio_Forms::field_catalog() ),
			'notify_channels'  => count( NGC_Studio_Notifications::channel_catalog() ),
			'dashboard_widgets'=> count( NGC_Studio_Dashboards::widget_catalog() ),
			'live_stream'      => NGC_Studio_Stream::status(),
			'rest_api'         => rest_url( NGC_Rest::NAMESPACE . '/studio/workflows' ),
		];
		$checks['ok'] = $checks['tables'] && $checks['runtime']['active'] >= 0;
		return $checks;
	}

	/**
	 * @return bool
	 */
	private static function check_tables() {
		global $wpdb;
		$required = [ 'studio_workflows', 'studio_executions', 'studio_triggers', 'studio_forms', 'studio_emails', 'studio_notifications', 'studio_dashboards' ];
		foreach ( $required as $key ) {
			$table = NGC_Database::table( $key );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return array{active:int,triggers:int}
	 */
	private static function check_runtime() {
		$status = NGC_Studio_Runtime::status();
		return [
			'active'   => (int) ( $status['active_workflows'] ?? 0 ),
			'triggers' => (int) ( $status['active_triggers'] ?? 0 ),
		];
	}

	/**
	 * @return array<string, bool>
	 */
	private static function check_adapters() {
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return [];
		}
		$out = [];
		foreach ( NGC_Workflow_Orchestrator::adapters() as $key => $adapter ) {
			$out[ $key ] = is_object( $adapter );
		}
		return $out;
	}
}
