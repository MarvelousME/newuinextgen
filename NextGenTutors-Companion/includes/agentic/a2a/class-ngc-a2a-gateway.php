<?php
/**
 * A2A gateway boundary — WordPress never embeds untrusted agent runtimes.
 *
 * External A2A agents are treated as untrusted. Official SDK runs in a separate
 * Agent Gateway service; this class persists tasks and signed hand-offs only.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Durable A2A task records + approved agent card pins.
 */
final class NGC_A2a_Gateway {

	const OPTION_AGENTS = 'ngc_a2a_agents';
	const OPTION_TASKS  = 'ngc_a2a_tasks';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function agents() {
		$rows = get_option( self::OPTION_AGENTS, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}

	/**
	 * Pin an approved Agent Card identity (no arbitrary URLs executed).
	 *
	 * @param array<string, mixed> $card Card.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function pin_agent( array $card ) {
		$id = sanitize_key( (string) ( $card['id'] ?? '' ) );
		if ( '' === $id ) {
			return new WP_Error( 'ngc_a2a_id', __( 'Agent id is required.', 'nextgencompanion' ) );
		}
		$url = esc_url_raw( (string) ( $card['url'] ?? '' ) );
		if ( $url && is_wp_error( NGC_Mcp_Ssrf::assert_safe_url( $url, false ) ) ) {
			// Reuse SSRF guard for agent endpoints.
			$check = NGC_Mcp_Ssrf::assert_safe_url( $url, defined( 'WP_DEBUG' ) && WP_DEBUG );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
		}
		$row = [
			'id'           => $id,
			'name'         => sanitize_text_field( (string) ( $card['name'] ?? $id ) ),
			'url'          => $url,
			'version'      => sanitize_text_field( (string) ( $card['version'] ?? '' ) ),
			'skills'       => array_values( array_map( 'sanitize_text_field', (array) ( $card['skills'] ?? [] ) ) ),
			'approved'     => empty( $card['approved'] ) ? 0 : 1,
			'kill_switch'  => empty( $card['kill_switch'] ) ? 0 : 1,
			'pinned_at'    => gmdate( 'c' ),
			'pinned_by'    => get_current_user_id(),
		];
		$all   = self::agents();
		$found = false;
		foreach ( $all as $i => $existing ) {
			if ( ( $existing['id'] ?? '' ) === $id ) {
				$all[ $i ] = array_merge( $existing, $row );
				$found     = true;
				break;
			}
		}
		if ( ! $found ) {
			$all[] = $row;
		}
		update_option( self::OPTION_AGENTS, $all, false );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'a2a_agent_pinned', 'a2a', 0, [ 'id' => $id, 'approved' => $row['approved'] ] );
		}
		return $row;
	}

	/**
	 * Create a durable task (does not auto-execute against external agents).
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_task( array $input ) {
		$agent_id = sanitize_key( (string) ( $input['agent_id'] ?? '' ) );
		$agent    = null;
		foreach ( self::agents() as $candidate ) {
			if ( ( $candidate['id'] ?? '' ) === $agent_id ) {
				$agent = $candidate;
				break;
			}
		}
		if ( ! $agent || empty( $agent['approved'] ) || ! empty( $agent['kill_switch'] ) ) {
			return new WP_Error( 'ngc_a2a_agent', __( 'Agent is missing, unapproved, or killed.', 'nextgencompanion' ) );
		}
		$task_id = 'a2a_' . wp_generate_password( 16, false, false );
		$task    = [
			'id'             => $task_id,
			'agent_id'       => $agent_id,
			'status'         => 'submitted',
			'correlation_id' => sanitize_text_field( (string) ( $input['correlation_id'] ?? wp_generate_uuid4() ) ),
			'idempotency'    => sanitize_text_field( (string) ( $input['idempotency_key'] ?? $task_id ) ),
			'message'        => wp_kses_post( (string) ( $input['message'] ?? '' ) ),
			'artifacts'      => [],
			'created_at'     => gmdate( 'c' ),
			'updated_at'     => gmdate( 'c' ),
			'created_by'     => get_current_user_id(),
			'gateway_note'   => __( 'Task persisted. Execution requires the separate Agent Gateway service with official a2a-js/python SDK.', 'nextgencompanion' ),
		];
		$tasks = get_option( self::OPTION_TASKS, [] );
		if ( ! is_array( $tasks ) ) {
			$tasks = [];
		}
		foreach ( $tasks as $existing ) {
			if ( ( $existing['idempotency'] ?? '' ) === $task['idempotency'] ) {
				return $existing;
			}
		}
		$tasks[] = $task;
		// Cap in-option storage; production should migrate to DB table.
		update_option( self::OPTION_TASKS, array_slice( $tasks, -200 ), false );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'a2a_task_created', 'a2a', 0, [ 'task_id' => $task_id, 'agent_id' => $agent_id ] );
		}
		return $task;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function tasks() {
		$rows = get_option( self::OPTION_TASKS, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}
}
