<?php
/**
 * Supervised agent registry — rules, commands, declarative skills.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic add/remove agents bound to BYOK models.
 */
final class NGC_AI_Agents {

	private const OPTION = 'ngc_ai_agents';

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function available_skills() {
		return apply_filters(
			'ngc_ai_skills',
			[
				'site.search'      => [ 'label' => 'Search site content', 'cap' => 'bia_ai_use', 'mutating' => false ],
				'leads.count'      => [ 'label' => 'Count leads by status', 'cap' => 'bia_ai_use', 'mutating' => false ],
				'earnings.summary' => [ 'label' => 'Read tutor earnings summary', 'cap' => 'bia_ai_use', 'mutating' => false ],
				'db.read'          => [ 'label' => 'Read database rows', 'cap' => 'bia_db_ops', 'mutating' => false ],
				'db.write'         => [ 'label' => 'Write database rows (approval required)', 'cap' => 'bia_db_ops', 'mutating' => true ],
				'terminal.run'     => [ 'label' => 'Run allowlisted CLI (approval required)', 'cap' => 'bia_terminal', 'mutating' => true ],
			]
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function list() {
		$agents = get_option( self::OPTION, [] );
		if ( ! is_array( $agents ) ) {
			return [];
		}
		return array_values( $agents );
	}

	/**
	 * @param string $id Agent id.
	 * @return array<string,mixed>|null
	 */
	public static function get( $id ) {
		$agents = get_option( self::OPTION, [] );
		return isset( $agents[ $id ] ) && is_array( $agents[ $id ] ) ? $agents[ $id ] : null;
	}

	/**
	 * @param array<string,mixed> $data Agent payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function save( $data ) {
		$gate = BIA_Policy::can( 'ai.agent.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		$id   = sanitize_key( (string) ( $data['id'] ?? '' ) );
		$name = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
		if ( '' === $name ) {
			return new WP_Error( 'ngc_agent', __( 'Agent name is required.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		if ( '' === $id ) {
			$id = sanitize_key( $name ) . '-' . wp_generate_password( 4, false, false );
		}

		$valid_skills = array_keys( self::available_skills() );
		$skills       = array_values( array_intersect( (array) ( $data['skills'] ?? [] ), $valid_skills ) );

		$commands = [];
		foreach ( (array) ( $data['commands'] ?? [] ) as $cmd ) {
			if ( ! is_array( $cmd ) ) {
				continue;
			}
			$cname   = sanitize_text_field( (string) ( $cmd['name'] ?? '' ) );
			$cprompt = sanitize_textarea_field( (string) ( $cmd['prompt'] ?? '' ) );
			if ( '' !== $cname ) {
				$commands[] = [ 'name' => $cname, 'prompt' => $cprompt ];
			}
		}

		$agents = get_option( self::OPTION, [] );
		if ( ! is_array( $agents ) ) {
			$agents = [];
		}
		$agents[ $id ] = [
			'id'       => $id,
			'name'     => $name,
			'model_id' => sanitize_key( (string) ( $data['model_id'] ?? '' ) ),
			'rules'    => sanitize_textarea_field( (string) ( $data['rules'] ?? '' ) ),
			'role'     => sanitize_key( (string) ( $data['role'] ?? 'worker' ) ),
			'commands' => $commands,
			'skills'   => $skills,
			'created'  => $agents[ $id ]['created'] ?? current_time( 'mysql' ),
		];
		update_option( self::OPTION, $agents, false );
		BIA_Policy::audit( 'ai.agent.manage', 'success', [ 'op' => 'save', 'id' => $id ] );
		return [ 'id' => $id ];
	}

	/**
	 * @param string $id Agent id.
	 * @return bool|WP_Error
	 */
	public static function delete( $id ) {
		$gate = BIA_Policy::can( 'ai.agent.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$agents = get_option( self::OPTION, [] );
		unset( $agents[ $id ] );
		update_option( self::OPTION, $agents, false );
		BIA_Policy::audit( 'ai.agent.manage', 'success', [ 'op' => 'delete', 'id' => $id ] );
		return true;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function export() {
		return self::list();
	}

	/**
	 * @param array<int,array<string,mixed>> $rows Agent rows.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function import( $rows ) {
		$gate = BIA_Policy::can( 'ai.agent.manage' );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$count = 0;
		foreach ( $rows as $row ) {
			if ( is_array( $row ) && ! empty( $row['name'] ) ) {
				$res = self::save( $row );
				if ( ! is_wp_error( $res ) ) {
					++$count;
				}
			}
		}
		return [ 'imported' => $count ];
	}
}
