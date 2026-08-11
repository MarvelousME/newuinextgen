<?php
declare(strict_types=1);

namespace NGTBM\Domain\Resource;

/**
 * Metadata-driven resource schemas for the universal CRUD engine.
 */
final class ResourceCatalog {

	/**
	 * @return array<string,mixed>|null
	 */
	public static function get( string $id ): ?array {
		$all = self::all();
		return $all[ $id ] ?? null;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function all(): array {
		return [
			'talent-evaluation' => [
				'id'          => 'talent-evaluation',
				'permissions' => [
					'read'    => 'ngt_talent_read',
					'create'  => 'ngt_talent_create',
					'update'  => 'ngt_talent_update',
					'delete'  => 'ngt_talent_delete',
					'execute' => 'ngt_talent_evaluate',
				],
				'columns'     => [ 'tutor', 'score', 'recommendation', 'modelVersion', 'evaluatedAt' ],
				'filters'     => [ 'subject', 'scoreRange', 'recommendation', 'date' ],
			],
		];
	}
}
