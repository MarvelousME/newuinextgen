<?php
declare(strict_types=1);

namespace NGTBM\Domain\Subsystem;

/**
 * Optional provider interface for typed registration.
 */
interface SubsystemProviderInterface {

	/**
	 * @return array<string,mixed>
	 */
	public function manifest(): array;

	/**
	 * @return list<string>
	 */
	public function capabilities(): array;

	/**
	 * @return array{status:string,message?:string,metrics?:array<string,mixed>}
	 */
	public function health(): array;

	/**
	 * @return list<array<string,mixed>>
	 */
	public function admin_resources(): array;
}
