<?php
/**
 * Abstract base for provisioning steps.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helpers for concrete steps.
 */
abstract class NGC_Provisioning_Step_Base implements NGC_Provisioning_Step {

	/**
	 * {@inheritdoc}
	 */
	abstract public function id(): string;

	/**
	 * {@inheritdoc}
	 */
	abstract public function label(): string;

	/**
	 * {@inheritdoc}
	 */
	public function version(): string {
		return '1.0.0';
	}

	/**
	 * {@inheritdoc}
	 */
	abstract public function order(): int;

	/**
	 * {@inheritdoc}
	 */
	public function dependencies(): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_critical(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preflight( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		return new NGC_Provision_Check_Result( true );
	}

	/**
	 * {@inheritdoc}
	 */
	public function plan( NGC_Provision_Context $context ): NGC_Provision_Change_Set {
		$set = new NGC_Provision_Change_Set();
		$set->meta['note'] = 'No planned mutations (verify-only or detect-only step).';
		return $set;
	}

	/**
	 * {@inheritdoc}
	 */
	abstract public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result;

	/**
	 * {@inheritdoc}
	 */
	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		return new NGC_Provision_Check_Result( true );
	}

	/**
	 * {@inheritdoc}
	 */
	public function rollback( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		return new NGC_Provision_Step_Result(
			true,
			'UNSUPPORTED',
			'No compensating rollback for this step; use backups or demo reset where applicable.'
		);
	}

	/**
	 * @param string $message Message.
	 * @param array<string,mixed> $evidence Evidence.
	 * @return NGC_Provision_Step_Result
	 */
	protected function ok( $message = '', array $evidence = [] ) {
		return new NGC_Provision_Step_Result( true, 'COMPLETED', $message, [ 'evidence' => $evidence ] );
	}

	/**
	 * @param string $message Message.
	 * @param array<string,mixed> $evidence Evidence.
	 * @return NGC_Provision_Step_Result
	 */
	protected function skipped( $message, array $evidence = [] ) {
		return new NGC_Provision_Step_Result( true, 'SKIPPED', $message, [ 'evidence' => $evidence ] );
	}

	/**
	 * @param string $message Message.
	 * @param array<string,mixed> $evidence Evidence.
	 * @return NGC_Provision_Step_Result
	 */
	protected function failed( $message, array $evidence = [] ) {
		return new NGC_Provision_Step_Result( false, 'FAILED', $message, [ 'evidence' => $evidence ] );
	}

	/**
	 * @param string $message Message.
	 * @param array<string,mixed> $evidence Evidence.
	 * @return NGC_Provision_Step_Result
	 */
	protected function partial( $message, array $evidence = [] ) {
		return new NGC_Provision_Step_Result( true, 'COMPLETED_WITH_WARNINGS', $message, [ 'evidence' => $evidence ] );
	}
}
