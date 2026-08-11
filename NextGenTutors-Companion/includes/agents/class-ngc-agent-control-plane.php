<?php
/**
 * Governed autonomous agent control plane.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry, autonomy levels, kill switches, task enqueue, seed agents.
 */
final class NGC_Agent_Control_Plane {

	public const OPTION_GLOBAL_PAUSE = 'ngc_agent_global_pause';
	public const OPTION_AGENT_PAUSE  = 'ngc_agent_paused_ids';
	public const OPTION_REGISTRY     = 'ngc_agent_ops_registry';

	/** Autonomy levels per master directive §16. */
	public const LEVEL_OBSERVE          = 0;
	public const LEVEL_RECOMMEND        = 1;
	public const LEVEL_REVERSIBLE       = 2;
	public const LEVEL_OPERATIONAL      = 3;
	public const LEVEL_HUMAN_APPROVAL   = 4;
	public const LEVEL_PROHIBITED       = 5;

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_install' ], 5 );
		add_action( 'ngc_agent_run_task', [ __CLASS__, 'run_task' ], 10, 1 );
		add_filter( 'ngc_ai_skills', [ __CLASS__, 'register_ops_skills' ] );
	}

	/**
	 * Ensure tables + default registry.
	 */
	public static function maybe_install() {
		$ver = get_option( 'ngc_agent_ops_db_version', '' );
		if ( version_compare( (string) $ver, '1.0.0', '<' ) ) {
			self::install();
			update_option( 'ngc_agent_ops_db_version', '1.0.0', false );
		}
		if ( ! get_option( self::OPTION_REGISTRY ) ) {
			self::seed_registry();
		}
	}

	/**
	 * Create agent ops tables.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$tasks   = $wpdb->prefix . 'ngc_agent_tasks';
		$runs    = $wpdb->prefix . 'ngc_agent_runs';
		$approvals = $wpdb->prefix . 'ngc_agent_approvals';

		dbDelta(
			"CREATE TABLE {$tasks} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				agent_id varchar(64) NOT NULL DEFAULT '',
				action_id varchar(128) NOT NULL DEFAULT '',
				status varchar(32) NOT NULL DEFAULT 'queued',
				autonomy_level tinyint(3) NOT NULL DEFAULT 0,
				payload longtext NULL,
				policy_decision varchar(32) NOT NULL DEFAULT '',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY agent_id (agent_id),
				KEY status (status)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$runs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				task_id bigint(20) unsigned NOT NULL DEFAULT 0,
				agent_id varchar(64) NOT NULL DEFAULT '',
				outcome varchar(32) NOT NULL DEFAULT '',
				detail longtext NULL,
				duration_ms int(11) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY task_id (task_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$approvals} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				task_id bigint(20) unsigned NOT NULL DEFAULT 0,
				action_id varchar(128) NOT NULL DEFAULT '',
				status varchar(32) NOT NULL DEFAULT 'pending',
				requested_by bigint(20) unsigned NOT NULL DEFAULT 0,
				decided_by bigint(20) unsigned NOT NULL DEFAULT 0,
				note longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				decided_at datetime NULL,
				PRIMARY KEY (id),
				KEY status (status)
			) {$charset};"
		);
	}

	/**
	 * Seed required agent definitions (Level defaults; tools are declarative).
	 */
	public static function seed_registry() {
		$agents = [
			'system-audit'           => [ 'name' => 'System Audit Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend', 'agent.task.create' ] ],
			'security-ops'           => [ 'name' => 'Security Operations Agent', 'autonomy' => 3, 'tools' => [ 'agent.observe', 'agent.rate_limit.source', 'agent.mfa.challenge', 'agent.case.create' ] ],
			'fraud-detection'        => [ 'name' => 'Fraud Detection Agent', 'autonomy' => 2, 'tools' => [ 'agent.observe', 'agent.case.create', 'agent.recommend' ] ],
			'financial-reconciliation'=> [ 'name' => 'Financial Reconciliation Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend', 'finance.refund.execute', 'finance.payout.release' ] ],
			'tutor-verification'     => [ 'name' => 'Tutor Verification Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend', 'tutor.approve', 'tutor.reject' ] ],
			'tutor-matching'         => [ 'name' => 'Tutor Matching Agent', 'autonomy' => 2, 'tools' => [ 'agent.observe', 'agent.recommend' ] ],
			'scheduling'             => [ 'name' => 'Scheduling Agent', 'autonomy' => 2, 'tools' => [ 'agent.observe', 'agent.recommend' ] ],
			'customer-support'       => [ 'name' => 'Customer Support Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend' ] ],
			'notification'           => [ 'name' => 'Notification Agent', 'autonomy' => 2, 'tools' => [ 'agent.observe', 'agent.recommend' ] ],
			'content-marketing'      => [ 'name' => 'Content and Marketing Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend' ] ],
			'compliance'             => [ 'name' => 'Compliance Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend', 'data.delete' ] ],
			'observability'          => [ 'name' => 'Observability Agent', 'autonomy' => 2, 'tools' => [ 'agent.observe', 'agent.recommend', 'agent.task.create' ] ],
			'quality-assurance'      => [ 'name' => 'Quality Assurance Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend' ] ],
			'remediation'            => [ 'name' => 'Remediation Agent', 'autonomy' => 2, 'tools' => [ 'agent.recommend', 'agent.task.create' ] ],
			'release-governance'     => [ 'name' => 'Release Governance Agent', 'autonomy' => 1, 'tools' => [ 'agent.observe', 'agent.recommend', 'deploy.production' ] ],
			'safeguarding'           => [ 'name' => 'Safeguarding Signal Agent', 'autonomy' => 3, 'tools' => [ 'agent.observe', 'safeguarding.escalate', 'agent.case.create' ] ],
		];

		foreach ( $agents as $id => &$meta ) {
			$meta['id']     = $id;
			$meta['status'] = 'active';
			$meta['paused'] = false;
		}
		unset( $meta );

		update_option( self::OPTION_REGISTRY, $agents, false );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function registry() {
		$reg = get_option( self::OPTION_REGISTRY, [] );
		return is_array( $reg ) ? $reg : [];
	}

	public static function is_globally_paused() {
		return (bool) get_option( self::OPTION_GLOBAL_PAUSE, false );
	}

	public static function set_global_pause( $paused ) {
		update_option( self::OPTION_GLOBAL_PAUSE, (bool) $paused, false );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'agent_global_pause', 'agent_ops', 0, [ 'paused' => (bool) $paused ], get_current_user_id() );
		}
	}

	public static function is_agent_paused( $agent_id ) {
		$paused = get_option( self::OPTION_AGENT_PAUSE, [] );
		if ( ! is_array( $paused ) ) {
			return false;
		}
		return in_array( sanitize_key( $agent_id ), $paused, true );
	}

	public static function set_agent_pause( $agent_id, $paused ) {
		$agent_id = sanitize_key( $agent_id );
		$list     = get_option( self::OPTION_AGENT_PAUSE, [] );
		if ( ! is_array( $list ) ) {
			$list = [];
		}
		if ( $paused ) {
			$list[] = $agent_id;
			$list   = array_values( array_unique( $list ) );
		} else {
			$list = array_values( array_diff( $list, [ $agent_id ] ) );
		}
		update_option( self::OPTION_AGENT_PAUSE, $list, false );
	}

	/**
	 * Request an agent action through the policy gate.
	 *
	 * @param string               $agent_id Agent slug.
	 * @param string               $action_id Policy action.
	 * @param array<string, mixed> $payload  Payload.
	 * @return int|WP_Error Task ID.
	 */
	public static function request_action( $agent_id, $action_id, $payload = [] ) {
		$agent_id  = NGC_Agent_Policy_Engine::sanitize_id( $agent_id );
		$action_id = NGC_Agent_Policy_Engine::sanitize_id( $action_id );
		$registry  = self::registry();
		if ( ! isset( $registry[ $agent_id ] ) ) {
			return new WP_Error( 'ngc_unknown_agent', __( 'Unknown agent.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}

		$agent    = $registry[ $agent_id ];
		$autonomy = (int) ( $agent['autonomy'] ?? 0 );
		$tools    = (array) ( $agent['tools'] ?? [] );
		if ( $tools && ! in_array( $action_id, $tools, true ) ) {
			return new WP_Error( 'ngc_tool_denied', __( 'Tool not allowed for this agent.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$decision = NGC_Agent_Policy_Engine::evaluate(
			$action_id,
			[
				'agent_id'       => $agent_id,
				'autonomy_level' => $autonomy,
				'environment'    => self::environment(),
				'actor_user_id'  => get_current_user_id(),
			]
		);

		// RAD: observe capability-shaped actions via policy bridge (does not replace agent policy catalogue).
		if ( class_exists( 'NGC_Policy_Bridge' ) && class_exists( 'NGC_Capability_Registry' ) && NGC_Capability_Registry::has( $action_id ) ) {
			$bridged = NGC_Policy_Bridge::decide(
				$action_id,
				[
					'actor_type'     => 'agent',
					'actor'          => $agent_id,
					'agent_id'       => $agent_id,
					'autonomy_level' => $autonomy,
					'environment'    => self::environment(),
					'actor_user_id'  => get_current_user_id(),
					'policy_action'  => $action_id,
				]
			);
			do_action( 'ngc_rad_capability_policy_observed', $action_id, $bridged, $decision );
			if ( NGC_Policy_Bridge::DENY === ( $bridged['decision'] ?? '' ) && NGC_Agent_Policy_Engine::DENY !== ( $decision['decision'] ?? '' ) ) {
				// Capability registry is stricter: fail closed.
				return new WP_Error( 'ngc_policy_deny', $bridged['reason'] ?? __( 'Denied by capability policy', 'nextgencompanion' ), [ 'status' => 403 ] );
			}
		}

		if ( NGC_Agent_Policy_Engine::DENY === $decision['decision'] ) {
			return new WP_Error( 'ngc_policy_deny', $decision['reason'], [ 'status' => 403 ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'ngc_agent_tasks';
		$wpdb->insert(
			$table,
			[
				'agent_id'         => $agent_id,
				'action_id'        => $action_id,
				'status'           => ! empty( $decision['requires_approval'] ) ? 'awaiting_approval' : 'queued',
				'autonomy_level'   => $autonomy,
				'payload'          => wp_json_encode( $payload ),
				'policy_decision'  => $decision['decision'],
				'created_at'       => current_time( 'mysql', true ),
				'updated_at'       => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
		);
		$task_id = (int) $wpdb->insert_id;

		if ( ! empty( $decision['requires_approval'] ) ) {
			$wpdb->insert(
				$wpdb->prefix . 'ngc_agent_approvals',
				[
					'task_id'      => $task_id,
					'action_id'    => $action_id,
					'status'       => 'pending',
					'requested_by' => get_current_user_id(),
				],
				[ '%d', '%s', '%s', '%d' ]
			);
			return $task_id;
		}

		self::execute_task( $task_id );
		return $task_id;
	}

	/**
	 * Human approval for pending task.
	 *
	 * @param int    $task_id Task.
	 * @param bool   $approve Approve or reject.
	 * @param string $note    Note.
	 * @return true|WP_Error
	 */
	public static function decide_approval( $task_id, $approve, $note = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'ngc_forbidden', __( 'Insufficient permissions.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}
		global $wpdb;
		$approvals = $wpdb->prefix . 'ngc_agent_approvals';
		$tasks     = $wpdb->prefix . 'ngc_agent_tasks';
		$row       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$approvals} WHERE task_id = %d AND status = 'pending' LIMIT 1", $task_id ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ngc_no_approval', __( 'No pending approval.', 'nextgencompanion' ) );
		}

		$wpdb->update(
			$approvals,
			[
				'status'     => $approve ? 'approved' : 'rejected',
				'decided_by' => get_current_user_id(),
				'note'       => sanitize_textarea_field( $note ),
				'decided_at' => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $row['id'] ],
			[ '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);

		if ( $approve ) {
			$wpdb->update( $tasks, [ 'status' => 'queued', 'updated_at' => current_time( 'mysql', true ) ], [ 'id' => $task_id ], [ '%s', '%s' ], [ '%d' ] );
			self::execute_task( $task_id );
		} else {
			$wpdb->update( $tasks, [ 'status' => 'rejected', 'updated_at' => current_time( 'mysql', true ) ], [ 'id' => $task_id ], [ '%s', '%s' ], [ '%d' ] );
		}
		return true;
	}

	/**
	 * Execute a queued low-risk task (no shell, no finance mutations).
	 *
	 * @param int $task_id Task ID.
	 */
	public static function execute_task( $task_id ) {
		global $wpdb;
		$tasks = $wpdb->prefix . 'ngc_agent_tasks';
		$task  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tasks} WHERE id = %d LIMIT 1", $task_id ), ARRAY_A );
		if ( ! $task || 'queued' !== $task['status'] ) {
			return;
		}

		$start = microtime( true );
		$wpdb->update( $tasks, [ 'status' => 'running', 'updated_at' => current_time( 'mysql', true ) ], [ 'id' => $task_id ], [ '%s', '%s' ], [ '%d' ] );

		$payload = json_decode( (string) $task['payload'], true );
		if ( ! is_array( $payload ) ) {
			$payload = [];
		}

		$outcome = 'success';
		$detail  = [ 'message' => 'Task recorded — no privileged side effect.' ];

		switch ( $task['action_id'] ) {
			case 'agent.case.create':
				if ( ! empty( $payload['fraud'] ) && class_exists( 'NGC_Fraud_Engine' ) ) {
					$case_id = NGC_Fraud_Engine::create_case( $payload );
					$detail  = [ 'fraud_case_id' => $case_id ];
				} elseif ( ! empty( $payload['safeguarding'] ) && class_exists( 'NGC_Safeguarding' ) ) {
					$case_id = NGC_Safeguarding::create_case( $payload );
					$detail  = [ 'safeguarding_case_id' => $case_id ];
				}
				break;
			case 'safeguarding.escalate':
				if ( class_exists( 'NGC_Safeguarding' ) ) {
					$detail = [ 'safeguarding_case_id' => NGC_Safeguarding::create_case( array_merge( $payload, [ 'priority' => 'high' ] ) ) ];
				}
				break;
			case 'agent.rate_limit.source':
				$ip = sanitize_text_field( (string) ( $payload['ip'] ?? '' ) );
				if ( $ip && class_exists( 'NGC_Rate_Limiter' ) ) {
					// Burn the window so subsequent checks fail for this fingerprint action.
					$action = 'agent_block_' . md5( $ip );
					for ( $i = 0; $i < 5; $i++ ) {
						NGC_Rate_Limiter::check( $action, 1, HOUR_IN_SECONDS );
					}
					$detail = [ 'rate_limited_action' => $action ];
				}
				break;
			case 'agent.observe':
			case 'agent.recommend':
			case 'agent.task.create':
				$detail = [ 'recorded' => true, 'payload_keys' => array_keys( $payload ) ];
				break;
			default:
				// High-impact actions must not auto-mutate here even if somehow queued.
				$outcome = 'blocked';
				$detail  = [ 'message' => 'Action requires human-mediated execution path.' ];
				break;
		}

		$duration = (int) round( ( microtime( true ) - $start ) * 1000 );
		$wpdb->insert(
			$wpdb->prefix . 'ngc_agent_runs',
			[
				'task_id'     => $task_id,
				'agent_id'    => $task['agent_id'],
				'outcome'     => $outcome,
				'detail'      => wp_json_encode( $detail ),
				'duration_ms' => $duration,
			],
			[ '%d', '%s', '%s', '%s', '%d' ]
		);
		$wpdb->update(
			$tasks,
			[ 'status' => 'success' === $outcome ? 'completed' : 'failed', 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => $task_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Cron / hook runner.
	 *
	 * @param int $task_id Task.
	 */
	public static function run_task( $task_id ) {
		self::execute_task( (int) $task_id );
	}

	/**
	 * @param array<string, array<string, mixed>> $skills Skills.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_ops_skills( $skills ) {
		$skills['agent.ops.status'] = [
			'label'    => 'Read agent control plane status',
			'cap'      => 'bia_ai_use',
			'mutating' => false,
		];
		return $skills;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function status_summary() {
		global $wpdb;
		$tasks = $wpdb->prefix . 'ngc_agent_tasks';
		return [
			'global_paused'      => self::is_globally_paused(),
			'paused_agents'      => get_option( self::OPTION_AGENT_PAUSE, [] ),
			'registry_count'     => count( self::registry() ),
			'queued'             => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tasks} WHERE status = 'queued'" ),
			'awaiting_approval'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tasks} WHERE status = 'awaiting_approval'" ),
			'completed_24h'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tasks} WHERE status = 'completed' AND created_at >= (NOW() - INTERVAL 1 DAY)" ),
		];
	}

	/**
	 * @return string
	 */
	public static function environment() {
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			return (string) WP_ENVIRONMENT_TYPE;
		}
		if ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
			return 'local';
		}
		return 'production';
	}
}
