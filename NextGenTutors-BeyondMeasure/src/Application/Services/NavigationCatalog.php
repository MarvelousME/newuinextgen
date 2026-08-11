<?php
declare(strict_types=1);

namespace NGTBM\Application\Services;

/**
 * Primary navigation IA for the Admin UI Runtime.
 */
final class NavigationCatalog {

	/**
	 * @return list<array<string,mixed>>
	 */
	public static function tree(): array {
		return [
			[
				'id'    => 'command-center',
				'label' => 'Command Center',
				'path'  => '/',
				'icon'  => 'gauge',
			],
			[
				'id'       => 'ecosystem',
				'label'    => 'Ecosystem',
				'icon'     => 'network',
				'children' => [
					[ 'id' => 'subsystems', 'label' => 'Subsystems', 'path' => '/ecosystem/subsystems' ],
					[ 'id' => 'capabilities', 'label' => 'Capabilities', 'path' => '/ecosystem/capabilities' ],
					[ 'id' => 'connections', 'label' => 'Connections', 'path' => '/ecosystem/connections' ],
					[ 'id' => 'dependency-map', 'label' => 'Dependency Map', 'path' => '/ecosystem/dependency-map' ],
					[ 'id' => 'compliance', 'label' => 'Compliance', 'path' => '/ecosystem/compliance' ],
				],
			],
			[
				'id'       => 'tutors',
				'label'    => 'Tutors',
				'icon'     => 'users',
				'children' => [
					[ 'id' => 'directory', 'label' => 'Directory', 'path' => '/tutors/directory', 'placeholder' => true ],
					[ 'id' => 'applications', 'label' => 'Applications', 'path' => '/tutors/applications', 'placeholder' => true ],
					[ 'id' => 'talent', 'label' => 'Talent Intelligence', 'path' => '/tutors/talent' ],
					[ 'id' => 'matching', 'label' => 'Matching', 'path' => '/tutors/matching', 'placeholder' => true ],
					[ 'id' => 'safeguarding', 'label' => 'Safeguarding', 'path' => '/tutors/safeguarding', 'placeholder' => true ],
				],
			],
			[ 'id' => 'students', 'label' => 'Students & Parents', 'path' => '/students', 'placeholder' => true ],
			[ 'id' => 'education', 'label' => 'Education', 'path' => '/education', 'placeholder' => true ],
			[ 'id' => 'bookings', 'label' => 'Bookings', 'path' => '/bookings', 'placeholder' => true ],
			[ 'id' => 'commerce', 'label' => 'Commerce', 'path' => '/commerce', 'placeholder' => true ],
			[ 'id' => 'crm', 'label' => 'CRM', 'path' => '/crm', 'placeholder' => true ],
			[ 'id' => 'communications', 'label' => 'Communications', 'path' => '/communications', 'placeholder' => true ],
			[
				'id'       => 'automation',
				'label'    => 'Automation',
				'children' => [
					[ 'id' => 'workflows', 'label' => 'Workflows', 'path' => '/automation/workflows', 'placeholder' => true ],
					[ 'id' => 'events', 'label' => 'Events', 'path' => '/automation/events', 'placeholder' => true ],
					[ 'id' => 'queues', 'label' => 'Queues', 'path' => '/automation/queues' ],
					[ 'id' => 'schedules', 'label' => 'Schedules', 'path' => '/automation/schedules', 'placeholder' => true ],
				],
			],
			[
				'id'       => 'ai',
				'label'    => 'AI & Agents',
				'children' => [
					[ 'id' => 'agents', 'label' => 'Agents', 'path' => '/ai/agents', 'placeholder' => true ],
					[ 'id' => 'memory', 'label' => 'Memory', 'path' => '/ai/memory', 'placeholder' => true ],
					[ 'id' => 'mcp', 'label' => 'MCP', 'path' => '/ai/mcp', 'placeholder' => true ],
					[ 'id' => 'a2a', 'label' => 'A2A', 'path' => '/ai/a2a', 'placeholder' => true ],
					[ 'id' => 'models', 'label' => 'Models', 'path' => '/ai/models', 'placeholder' => true ],
				],
			],
			[
				'id'       => 'security',
				'label'    => 'Security',
				'children' => [
					[ 'id' => 'roles', 'label' => 'Roles', 'path' => '/security/roles' ],
					[ 'id' => 'caps', 'label' => 'Capabilities', 'path' => '/security/capabilities' ],
					[ 'id' => 'policies', 'label' => 'Policies', 'path' => '/security/policies', 'placeholder' => true ],
					[ 'id' => 'access-matrix', 'label' => 'Access Matrix', 'path' => '/security/access-matrix' ],
				],
			],
			[
				'id'       => 'operations',
				'label'    => 'Operations',
				'children' => [
					[ 'id' => 'health', 'label' => 'Health', 'path' => '/operations/health' ],
					[ 'id' => 'monitoring', 'label' => 'Monitoring', 'path' => '/operations/monitoring', 'placeholder' => true ],
					[ 'id' => 'logs', 'label' => 'Logs', 'path' => '/operations/logs', 'placeholder' => true ],
					[ 'id' => 'metrics', 'label' => 'Metrics', 'path' => '/operations/metrics', 'placeholder' => true ],
					[ 'id' => 'traces', 'label' => 'Traces', 'path' => '/operations/traces', 'placeholder' => true ],
					[ 'id' => 'ops-queues', 'label' => 'Queues', 'path' => '/operations/queues' ],
					[ 'id' => 'dlq', 'label' => 'DLQ', 'path' => '/operations/dlq' ],
				],
			],
			[
				'id'       => 'governance',
				'label'    => 'Governance',
				'children' => [
					[ 'id' => 'audit', 'label' => 'Audit', 'path' => '/governance/audit' ],
					[ 'id' => 'gov-compliance', 'label' => 'Compliance', 'path' => '/governance/compliance', 'placeholder' => true ],
					[ 'id' => 'data', 'label' => 'Data', 'path' => '/governance/data', 'placeholder' => true ],
					[ 'id' => 'architecture', 'label' => 'Architecture', 'path' => '/governance/architecture' ],
				],
			],
			[ 'id' => 'settings', 'label' => 'Settings', 'path' => '/settings' ],
		];
	}
}
