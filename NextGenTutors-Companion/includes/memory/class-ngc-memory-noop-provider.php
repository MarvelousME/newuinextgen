<?php
/**
 * No-op memory provider — DISABLED / DEGRADED safe path.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Always succeeds with empty results; never throws into booking/payment paths.
 */
final class NGC_Memory_Noop_Provider implements NGC_Memory_Provider_Interface {

	/**
	 * @return string
	 */
	public function slug() {
		return 'noop';
	}

	/**
	 * @return array{ok:bool,mode:string,message:string,details?:array}
	 */
	public function health() {
		$mode = (string) ( NGC_Memory_Settings::get()['mode'] ?? NGC_Memory_Settings::MODE_DISABLED );
		return [
			'ok'      => true,
			'mode'    => $mode,
			'message' => 'Memory provider inactive (noop).',
			'details' => [ 'provider' => 'noop' ],
		];
	}

	/**
	 * @param array<string,mixed> $context Unused.
	 * @return array<string,mixed>
	 */
	public function write( array $context ) {
		return [ 'ok' => true, 'written' => false, 'provider' => 'noop' ];
	}

	/**
	 * @param array<string,mixed> $context Unused.
	 * @return array<string,mixed>
	 */
	public function search( array $context ) {
		return [ 'ok' => true, 'items' => [], 'provider' => 'noop' ];
	}

	/**
	 * @param array<string,mixed> $context Unused.
	 * @return array<string,mixed>
	 */
	public function retrieve( array $context ) {
		return [
			'ok'           => true,
			'items'        => [],
			'context_text' => '',
			'provider'     => 'noop',
		];
	}

	/**
	 * @param array<string,mixed> $context Unused.
	 * @return array<string,mixed>
	 */
	public function forget( array $context ) {
		return [ 'ok' => true, 'forgotten' => 0, 'provider' => 'noop' ];
	}

	/**
	 * @param array<string,mixed> $context Unused.
	 * @return array<string,mixed>
	 */
	public function correct( array $context ) {
		return [ 'ok' => true, 'corrected' => false, 'provider' => 'noop' ];
	}

	/**
	 * @param array<string,mixed> $context Unused.
	 * @return array<string,mixed>
	 */
	public function list_memories( array $context ) {
		return [ 'ok' => true, 'items' => [], 'provider' => 'noop' ];
	}
}
