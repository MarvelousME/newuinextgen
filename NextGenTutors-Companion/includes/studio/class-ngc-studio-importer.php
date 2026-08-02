<?php
/**
 * Import Hub / Integrate / Orchestrator / Template workflows into Automation Studio.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts external workflow definitions into Studio graph rows (idempotent by workflow_key).
 */
final class NGC_Studio_Importer {

	public const OPTION_SYNCED = 'ngc_studio_sources_synced';
	public const SYNC_VERSION  = '2026-07-27.2';

	/**
	 * Auto-sync missing source workflows once (or when version bumps).
	 */
	public static function maybe_auto_sync() {
		if ( self::SYNC_VERSION === (string) get_option( self::OPTION_SYNCED, '' ) ) {
			return;
		}
		self::import_all( false );
		update_option( self::OPTION_SYNCED, self::SYNC_VERSION, false );
	}

	/**
	 * @param bool $force Overwrite existing imported rows.
	 * @return array<string, mixed>
	 */
	public static function import_all( $force = false ) {
		NGC_Studio::maybe_upgrade_tables();
		$report = [
			'ok'      => true,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'sources' => [ 'templates' => 0, 'hub' => 0, 'integrate' => 0, 'orchestrator' => 0 ],
			'errors'  => [],
		];
		foreach ( self::from_templates() as $item ) {
			self::upsert( $item, $force, 'templates', $report );
		}
		foreach ( self::from_hub() as $item ) {
			self::upsert( $item, $force, 'hub', $report );
		}
		foreach ( self::from_integrate() as $item ) {
			self::upsert( $item, $force, 'integrate', $report );
		}
		foreach ( self::from_orchestrator() as $item ) {
			self::upsert( $item, $force, 'orchestrator', $report );
		}
		update_option( self::OPTION_SYNCED, self::SYNC_VERSION, false );
		return $report;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function sources() {
		$hub   = self::load_hub_workflows();
		$specs = class_exists( 'NGC_Workflow_Spec_Registry' ) ? NGC_Workflow_Spec_Registry::all() : [];
		return [
			'templates'    => array_keys( NGC_Studio_Templates::all() ),
			'hub'          => array_map(
				static function ( $w ) {
					return [
						'key'     => (string) ( $w['key'] ?? '' ),
						'name'    => (string) ( $w['name'] ?? '' ),
						'enabled' => ! empty( $w['enabled'] ),
						'event'   => (string) ( $w['trigger']['event'] ?? '' ),
					];
				},
				$hub
			),
			'integrate'    => array_map(
				static function ( $s ) {
					return [
						'id'     => (string) ( $s['id'] ?? '' ),
						'name'   => (string) ( $s['name'] ?? '' ),
						'events' => (array) ( $s['events'] ?? [] ),
					];
				},
				array_values( $specs )
			),
			'orchestrator' => array_keys( self::orchestrator_map() ),
			'studio_count' => count( NGC_Studio_Repository::list_workflows() ),
			'synced'       => (string) get_option( self::OPTION_SYNCED, '' ),
		];
	}

	/** @return array<int, array<string, mixed>> */
	private static function from_templates() {
		$out = [];
		foreach ( NGC_Studio_Templates::all() as $key => $tpl ) {
			$out[] = [
				'workflow_key' => 'tpl_' . sanitize_key( (string) $key ),
				'name'         => (string) ( $tpl['name'] ?? $key ),
				'description'  => (string) ( $tpl['description'] ?? '' ),
				'graph'        => $tpl['graph'] ?? [],
				'template_key' => sanitize_key( (string) $key ),
				'settings'     => [ 'source' => 'templates', 'source_key' => (string) $key, 'editable' => true ],
				'status'       => 'draft',
			];
		}
		return $out;
	}

	/**
	 * Load Hub workflow definitions (live class or pack JSON on disk).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function load_hub_workflows() {
		if ( class_exists( 'NGT_Hub_Workflows' ) ) {
			$workflows = NGT_Hub_Workflows::get_workflows();
			if ( is_array( $workflows ) && $workflows ) {
				return $workflows;
			}
		}
		if ( class_exists( 'NGT_Hub' ) && method_exists( 'NGT_Hub', 'get_workflows' ) ) {
			$workflows = NGT_Hub::get_workflows();
			if ( is_array( $workflows ) && $workflows ) {
				return $workflows;
			}
		}
		$candidates = [
			dirname( NGC_PLUGIN_DIR ) . '/nextgen-automation-hub/config/workflows.json',
			WP_PLUGIN_DIR . '/nextgen-automation-hub/config/workflows.json',
			WP_CONTENT_DIR . '/plugins/nextgen-automation-hub/config/workflows.json',
		];
		foreach ( $candidates as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$raw = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $raw ) && ! empty( $raw['workflows'] ) && is_array( $raw['workflows'] ) ) {
				return $raw['workflows'];
			}
		}
		return [];
	}

	/** @return array<int, array<string, mixed>> */
	private static function from_hub() {
		$workflows = self::load_hub_workflows();
		$out       = [];
		foreach ( $workflows as $wf ) {
			if ( ! is_array( $wf ) ) {
				continue;
			}
			$key   = sanitize_key( (string) ( $wf['key'] ?? '' ) );
			$event = sanitize_text_field( (string) ( $wf['trigger']['event'] ?? 'CUSTOM_EVENT' ) );
			$steps = [];
			foreach ( (array) ( $wf['actions'] ?? [] ) as $action ) {
				$steps[] = self::map_hub_action( sanitize_key( (string) ( $action['type'] ?? 'API' ) ) );
			}
			$steps[] = 'END';
			$out[]   = [
				'workflow_key' => 'hub_' . $key,
				'name'         => (string) ( $wf['name'] ?? $key ),
				'description'  => sprintf( __( 'Imported from Automation Hub. Trigger: %s', 'nextgencompanion' ), $event ),
				'graph'        => NGC_Studio_Templates::build_linear_graph( $event, $steps ),
				'settings'     => [
					'source'      => 'hub',
					'source_key'  => $key,
					'hub_event'   => $event,
					'hub_enabled' => ! empty( $wf['enabled'] ),
					'editable'    => true,
					'raw'         => $wf,
				],
				'status'       => ! empty( $wf['enabled'] ) ? 'published' : 'draft',
			];
		}
		return $out;
	}

	/** @return array<int, array<string, mixed>> */
	private static function from_integrate() {
		if ( ! class_exists( 'NGC_Workflow_Spec_Registry' ) ) {
			return [];
		}
		$out = [];
		foreach ( NGC_Workflow_Spec_Registry::all() as $spec ) {
			if ( ! is_array( $spec ) ) {
				continue;
			}
			$id      = sanitize_key( (string) ( $spec['id'] ?? '' ) );
			$events  = array_values( array_filter( array_map( 'strval', (array) ( $spec['events'] ?? [] ) ) ) );
			$trigger = $events[0] ?? 'CUSTOM_EVENT';
			$steps   = self::map_integrate_steps( (array) ( $spec['steps'] ?? [] ) );
			$out[]   = [
				'workflow_key' => 'integrate_' . $id,
				'name'         => (string) ( $spec['name'] ?? $id ),
				'description'  => (string) ( $spec['description'] ?? $spec['business_goal'] ?? '' ),
				'graph'        => NGC_Studio_Templates::build_linear_graph( $trigger, $steps ),
				'settings'     => [
					'source'     => 'integrate',
					'source_key' => $id,
					'events'     => $events,
					'editable'   => true,
				],
				'status'       => 'draft',
			];
		}
		return $out;
	}

	/**
	 * Map integrate/catalog step names onto Studio node types.
	 *
	 * @param array<int, mixed> $steps Spec steps.
	 * @return array<int, string>
	 */
	private static function map_integrate_steps( array $steps ) {
		$out = [];
		foreach ( $steps as $step ) {
			$name = is_string( $step ) ? $step : (string) ( is_array( $step ) ? ( $step['name'] ?? '' ) : '' );
			$name = strtolower( $name );
			if ( '' === $name ) {
				continue;
			}
			if ( false !== strpos( $name, 'email' ) || false !== strpos( $name, 'mail' ) || false !== strpos( $name, 'notify' ) ) {
				$out[] = false !== strpos( $name, 'notify' ) ? 'NOTIFICATION' : 'EMAIL';
			} elseif ( false !== strpos( $name, 'crm' ) || false !== strpos( $name, 'fluent' ) || false !== strpos( $name, 'contact' ) || false !== strpos( $name, 'parent' ) || false !== strpos( $name, 'student' ) || false !== strpos( $name, 'tutor' ) ) {
				$out[] = 'CRM';
			} elseif ( false !== strpos( $name, 'lms' ) || false !== strpos( $name, 'masterstudy' ) || false !== strpos( $name, 'enroll' ) ) {
				$out[] = 'LMS';
			} elseif ( false !== strpos( $name, 'book' ) || false !== strpos( $name, 'amelia' ) || false !== strpos( $name, 'lesson' ) ) {
				$out[] = 'BOOKING';
			} elseif ( false !== strpos( $name, 'pay' ) || false !== strpos( $name, 'refund' ) || false !== strpos( $name, 'invoice' ) ) {
				$out[] = 'PAYMENT';
			} elseif ( false !== strpos( $name, 'approv' ) || false !== strpos( $name, 'review' ) ) {
				$out[] = 'APPROVAL';
			} elseif ( false !== strpos( $name, 'role' ) || false !== strpos( $name, 'user' ) ) {
				$out[] = 'ROLE';
			} elseif ( false !== strpos( $name, 'webhook' ) ) {
				$out[] = 'WEBHOOK';
			} else {
				$out[] = 'API';
			}
		}
		$out = array_values( array_unique( $out ) );
		if ( ! $out ) {
			$out = [ 'CRM', 'EMAIL', 'NOTIFICATION', 'AUDIT' ];
		}
		$out[] = 'AUDIT';
		$out[] = 'END';
		return $out;
	}

	/** @return array<int, array<string, mixed>> */
	private static function from_orchestrator() {
		$out = [];
		foreach ( self::orchestrator_map() as $wf_key => $meta ) {
			$out[] = [
				'workflow_key' => 'orch_' . sanitize_key( $wf_key ),
				'name'         => (string) $meta['name'],
				'description'  => (string) $meta['description'],
				'graph'        => NGC_Studio_Templates::build_linear_graph( (string) $meta['trigger'], (array) $meta['steps'] ),
				'settings'     => [
					'source'     => 'orchestrator',
					'source_key' => $wf_key,
					'form'       => $meta['form'] ?? '',
					'editable'   => true,
				],
				'status'       => 'published',
			];
		}
		return $out;
	}

	/** @return array<string, array<string, mixed>> */
	private static function orchestrator_map() {
		return [
			'WF-TUTOR-REGISTERED'   => [
				'name' => 'Tutor Registered', 'description' => 'Orchestrator: become_tutor form.',
				'trigger' => 'TUTOR_REGISTERED', 'form' => 'become_tutor',
				'steps' => [ 'CRM', 'EMAIL', 'APPROVAL', 'AUDIT', 'END' ],
			],
			'WF-TUTOR-APPROVED'     => [
				'name' => 'Tutor Approved', 'description' => 'Orchestrator: admin approve.',
				'trigger' => 'TUTOR_APPROVED', 'form' => 'Admin approve / REST',
				'steps' => [ 'ROLE', 'CRM', 'LMS', 'BOOKING', 'EMAIL', 'NOTIFICATION', 'AUDIT', 'END' ],
			],
			'WF-TUTOR-REJECTED'     => [
				'name' => 'Tutor Rejected', 'description' => 'Orchestrator: admin reject.',
				'trigger' => 'TUTOR_REJECTED', 'form' => 'Admin reject / REST',
				'steps' => [ 'EMAIL', 'CRM', 'AUDIT', 'END' ],
			],
			'WF-TUTOR-RESUBMITTED'  => [
				'name' => 'Tutor Resubmitted', 'description' => 'Orchestrator: REST resubmit.',
				'trigger' => 'TUTOR_RESUBMITTED', 'form' => 'REST resubmit',
				'steps' => [ 'EMAIL', 'APPROVAL', 'AUDIT', 'END' ],
			],
			'WF-PARENT-REGISTERED'  => [
				'name' => 'Parent Registered', 'description' => 'Orchestrator: parent_register.',
				'trigger' => 'PARENT_REGISTERED', 'form' => 'parent_register',
				'steps' => [ 'CRM', 'EMAIL', 'ROLE', 'AUDIT', 'END' ],
			],
			'WF-STUDENT-REGISTERED' => [
				'name' => 'Student Registered', 'description' => 'Orchestrator: student_register.',
				'trigger' => 'STUDENT_REGISTERED', 'form' => 'student_register',
				'steps' => [ 'CRM', 'LMS', 'EMAIL', 'AUDIT', 'END' ],
			],
			'WF-CHILD-REGISTERED'   => [
				'name' => 'Child Registered', 'description' => 'Orchestrator: child registration.',
				'trigger' => 'CHILD_REGISTERED', 'form' => 'parent_register child_name',
				'steps' => [ 'CRM', 'EMAIL', 'ROLE', 'AUDIT', 'END' ],
			],
		];
	}

	/** @param string $type Hub action type. @return string */
	private static function map_hub_action( $type ) {
		$map = [
			'log_event' => 'AUDIT', 'create_rtm_message' => 'NOTIFICATION',
			'wp_mail_admin' => 'EMAIL', 'wp_mail' => 'EMAIL',
			'http_request' => 'API', 'webhook' => 'WEBHOOK',
		];
		return $map[ $type ] ?? 'API';
	}

	/**
	 * @param array<string, mixed> $item   Payload.
	 * @param bool                 $force  Force update.
	 * @param string               $source Source.
	 * @param array<string, mixed> $report Report.
	 */
	private static function upsert( array $item, $force, $source, array &$report ) {
		$key = sanitize_key( (string) ( $item['workflow_key'] ?? '' ) );
		if ( '' === $key ) {
			$report['errors'][] = 'Missing workflow_key';
			return;
		}
		$existing = NGC_Studio_Repository::get_workflow_by_key( $key );
		if ( $existing ) {
			$src = (string) ( $existing['settings']['source'] ?? '' );
			if ( ! $force && $src === $source ) {
				++$report['skipped'];
				return;
			}
			$result = NGC_Studio_Repository::update_workflow(
				(int) $existing['id'],
				[
					'name'        => $item['name'] ?? $existing['name'],
					'description' => $item['description'] ?? $existing['description'],
					'graph'       => $item['graph'] ?? $existing['graph'],
					'settings'    => $item['settings'] ?? $existing['settings'],
					'status'      => $item['status'] ?? $existing['status'],
				]
			);
			if ( ! empty( $result['ok'] ) ) {
				++$report['updated'];
				++$report['sources'][ $source ];
			} else {
				$report['errors'][] = $result['message'] ?? ( 'Update failed: ' . $key );
			}
			return;
		}
		$result = NGC_Studio_Repository::create_workflow( $item );
		if ( empty( $result['ok'] ) ) {
			$report['errors'][] = $result['message'] ?? ( 'Create failed: ' . $key );
			return;
		}
		++$report['created'];
		++$report['sources'][ $source ];
		$id = (int) ( $result['id'] ?? 0 );
		// Persist intended status; publish/apply is available from Studio CRUD UI.
		if ( $id && ! empty( $item['status'] ) && 'draft' !== $item['status'] ) {
			NGC_Studio_Repository::update_workflow( $id, [ 'status' => sanitize_key( (string) $item['status'] ) ] );
		}
	}
}
