<?php
/**
 * Abstract data provider for NextGen UI Library components.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base provider — all dynamic UI values must flow through providers.
 */
abstract class NGC_UI_Data_Provider {

	/**
	 * Provider key slug.
	 *
	 * @return string
	 */
	abstract public function get_key();

	/**
	 * Whether the backing source is reachable.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Single record by ID/slug.
	 *
	 * @param string|int $id Identifier.
	 * @return array<string, mixed>|null
	 */
	public function get( $id ) {
		$items = $this->list( [ 'id' => $id, 'limit' => 1 ] );
		return $items[0] ?? null;
	}

	/**
	 * List records.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, array<string, mixed>>
	 */
	abstract public function list( $args = [] );

	/**
	 * Count records.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return int
	 */
	public function count( $args = [] ) {
		return count( $this->list( array_merge( $args, [ 'limit' => 1000 ] ) ) );
	}

	/**
	 * Search records.
	 *
	 * @param string               $query Search string.
	 * @param array<string, mixed> $args  Extra args.
	 * @return array<int, array<string, mixed>>
	 */
	public function search( $query, $args = [] ) {
		return $this->list( array_merge( $args, [ 'search' => $query ] ) );
	}

	/**
	 * Map raw row to component props.
	 *
	 * @param array<string, mixed> $row Raw data.
	 * @param string               $component Component slug.
	 * @return array<string, mixed>
	 */
	abstract public function map_to_component( $row, $component );

	/**
	 * Empty state payload when no data.
	 *
	 * @param string $component Component slug.
	 * @return array<string, mixed>
	 */
	public function fallback_empty_state( $component ) {
		return [
			'empty'   => true,
			'title'   => __( 'Nothing to show yet', 'nextgencompanion' ),
			'message' => __( 'Content will appear here once configured in the admin.', 'nextgencompanion' ),
			'source'  => $this->get_key(),
		];
	}

	/**
	 * Audit metadata for verification tooling.
	 *
	 * @return array<string, mixed>
	 */
	abstract public function verify_source();

	/**
	 * Demo mode gate.
	 *
	 * @return bool
	 */
	protected function demo_allowed() {
		return class_exists( 'NGC_Platform_Demo' ) && NGC_Platform_Demo::is_enabled();
	}
}
