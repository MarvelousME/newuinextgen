<?php
/**
 * Bridge memory provider port — domain never depends on Tencent DTOs.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaceable memory provider contract.
 */
interface NGC_Memory_Provider_Interface {

	/**
	 * @return string Provider slug.
	 */
	public function slug();

	/**
	 * @return array{ok:bool,mode:string,message:string,details?:array}
	 */
	public function health();

	/**
	 * @param array<string,mixed> $context Isolation + messages.
	 * @return array<string,mixed>|WP_Error
	 */
	public function write( array $context );

	/**
	 * @param array<string,mixed> $context Query + isolation.
	 * @return array<string,mixed>|WP_Error
	 */
	public function search( array $context );

	/**
	 * Budgeted context for prompt enrichment.
	 *
	 * @param array<string,mixed> $context Query + budgets + isolation.
	 * @return array<string,mixed>|WP_Error
	 */
	public function retrieve( array $context );

	/**
	 * @param array<string,mixed> $context Delete criteria.
	 * @return array<string,mixed>|WP_Error
	 */
	public function forget( array $context );

	/**
	 * @param array<string,mixed> $context Correction payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function correct( array $context );

	/**
	 * @param array<string,mixed> $context List filters.
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_memories( array $context );
}
