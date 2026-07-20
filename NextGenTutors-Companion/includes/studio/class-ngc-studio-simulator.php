<?php
/**
 * Workflow simulation and dry-run engine.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simulates workflow execution without side effects.
 */
class NGC_Studio_Simulator {

	/**
	 * @param array<string, mixed> $workflow Workflow.
	 * @param array<string, mixed> $context  Test context.
	 * @return array<string, mixed>
	 */
	public static function dry_run( $workflow, $context = [] ) {
		$context['__simulation'] = true;
		return NGC_Studio_Engine::execute( $workflow, $context, 'SIMULATION', true );
	}

	/**
	 * Replay a prior execution using stored context.
	 *
	 * @param int  $execution_id Execution ID.
	 * @param bool $simulate     Dry-run.
	 * @return array<string, mixed>
	 */
	public static function replay( $execution_id, $simulate = true ) {
		global $wpdb;
		$table = NGC_Database::table( 'studio_executions' );
		if ( ! $table ) {
			return [ 'ok' => false, 'message' => __( 'Executions table missing.', 'nextgencompanion' ) ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $execution_id ), ARRAY_A );
		if ( ! $row ) {
			return [ 'ok' => false, 'message' => __( 'Execution not found.', 'nextgencompanion' ) ];
		}
		$wf = NGC_Studio_Repository::get_workflow( (int) $row['workflow_id'] );
		if ( ! $wf ) {
			return [ 'ok' => false, 'message' => __( 'Workflow not found.', 'nextgencompanion' ) ];
		}
		$context = json_decode( (string) ( $row['context_json'] ?? '' ), true ) ?: [];
		$context['__replay_from'] = (int) $execution_id;
		if ( $simulate ) {
			return self::dry_run( $wf, $context );
		}
		return NGC_Studio_Engine::execute( $wf, $context, (string) ( $row['trigger_event'] ?? 'REPLAY' ), false );
	}
}
