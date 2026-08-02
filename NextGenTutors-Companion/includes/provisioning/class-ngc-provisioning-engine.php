<?php
/**
 * Provisioning engine — lock, run, resume, evidence, rollback.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ngc-provisioning-contracts.php';
require_once __DIR__ . '/class-ngc-provisioning-step-base.php';

/**
 * Orchestrates NGC_Provisioning_Step implementations.
 */
class NGC_Provisioning_Engine {

	public const STATE_OPTION = 'ngc_provisioning_state';
	public const LOCK_OPTION  = 'ngc_provisioning_lock';
	public const LOCK_TTL     = 900;

	/**
	 * Boot hooks (admin bar evidence path only).
	 */
	public static function init() {
		// Reserved for future REST/admin hooks; admin class registers UI.
	}

	/**
	 * @return NGC_Provisioning_Step[]
	 */
	public static function steps() {
		static $steps = null;
		if ( null !== $steps ) {
			return $steps;
		}
		require_once __DIR__ . '/class-ngc-provisioning-steps.php';

		$list = [
			new NGC_Provision_Step_Env_Preflight(),
			new NGC_Provision_Step_Backup_Awareness(),
			new NGC_Provision_Step_Wp_Baseline(),
			new NGC_Provision_Step_Theme(),
			new NGC_Provision_Step_First_Party_Plugins(),
			new NGC_Provision_Step_Third_Party_Detect(),
			new NGC_Provision_Step_Third_Party_Install(),
			new NGC_Provision_Step_Migrations(),
			new NGC_Provision_Step_Roles(),
			new NGC_Provision_Step_Business_Profile(),
			new NGC_Provision_Step_Ui_Library(),
			new NGC_Provision_Step_Pages(),
			new NGC_Provision_Step_Menus(),
			new NGC_Provision_Step_Forms(),
			new NGC_Provision_Step_Crm(),
			new NGC_Provision_Step_Email(),
			new NGC_Provision_Step_Domain(),
			new NGC_Provision_Step_Lms(),
			new NGC_Provision_Step_Booking(),
			new NGC_Provision_Step_Commerce(),
			new NGC_Provision_Step_Products(),
			new NGC_Provision_Step_Finance(),
			new NGC_Provision_Step_Workflows(),
			new NGC_Provision_Step_Gamification(),
			new NGC_Provision_Step_Analytics(),
			new NGC_Provision_Step_Ai(),
			new NGC_Provision_Step_Mission_Control(),
			new NGC_Provision_Step_Demo(),
			new NGC_Provision_Step_Verify(),
			new NGC_Provision_Step_Hardening(),
			new NGC_Provision_Step_Packaging(),
			new NGC_Provision_Step_Deployment_Docs(),
		];
		usort(
			$list,
			static function ( NGC_Provisioning_Step $a, NGC_Provisioning_Step $b ) {
				return $a->order() <=> $b->order();
			}
		);
		/**
		 * Filter provisioning steps.
		 *
		 * @param NGC_Provisioning_Step[] $list Steps.
		 */
		$steps = apply_filters( 'ngc_provisioning_steps', $list );
		return $steps;
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function state() {
		$s = get_option( self::STATE_OPTION, [] );
		return is_array( $s ) ? $s : [];
	}

	/**
	 * @param array<string,mixed> $patch Patch.
	 */
	public static function save_state( array $patch ) {
		$s = array_merge( self::state(), $patch, [ 'updated_at' => gmdate( 'c' ) ] );
		update_option( self::STATE_OPTION, $s, false );
	}

	/**
	 * @return bool
	 */
	public static function acquire_lock() {
		$lock = get_option( self::LOCK_OPTION, null );
		if ( is_array( $lock ) && ! empty( $lock['until'] ) && time() < (int) $lock['until'] ) {
			return false;
		}
		update_option(
			self::LOCK_OPTION,
			[
				'until'   => time() + self::LOCK_TTL,
				'owner'   => get_current_user_id(),
				'started' => gmdate( 'c' ),
			],
			false
		);
		return true;
	}

	/**
	 * Release lock.
	 */
	public static function release_lock() {
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * Catalogue of steps for UI/CLI.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function catalogue() {
		$rows = [];
		$state = self::state();
		$results = is_array( $state['results'] ?? null ) ? $state['results'] : [];
		foreach ( self::steps() as $step ) {
			$id = $step->id();
			$rows[] = [
				'order'        => $step->order(),
				'id'           => $id,
				'label'        => $step->label(),
				'version'      => $step->version(),
				'critical'     => $step->is_critical(),
				'dependencies' => $step->dependencies(),
				'last'         => $results[ $id ] ?? null,
			];
		}
		return $rows;
	}

	/**
	 * Run all or from a step id.
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @param string|null           $from_id Resume from step id (inclusive).
	 * @param string|null           $only_id Run single step.
	 * @return array<string,mixed>
	 */
	public static function run( NGC_Provision_Context $context, $from_id = null, $only_id = null ) {
		if ( ! self::acquire_lock() ) {
			return [
				'ok'      => false,
				'status'  => 'LOCKED',
				'message' => 'Another provisioning run is in progress. Wait or clear the lock.',
				'lock'    => get_option( self::LOCK_OPTION ),
			];
		}

		$started = gmdate( 'c' );
		$results = [];
		$stop    = false;
		$status  = 'COMPLETED';

		try {
			self::save_state(
				[
					'status'         => 'RUNNING',
					'correlation_id' => $context->correlation_id,
					'started_at'     => $started,
					'context'        => $context->to_array(),
				]
			);

			$started_run = $from_id ? false : true;
			foreach ( self::steps() as $step ) {
				$id = $step->id();
				if ( $only_id && $id !== $only_id ) {
					continue;
				}
				if ( $from_id && ! $started_run ) {
					if ( $id === $from_id ) {
						$started_run = true;
					} else {
						continue;
					}
				}

				$entry = self::execute_step( $step, $context );
				$results[ $id ] = $entry;
				self::write_checkpoint( $step, $entry, $context );
				self::save_state( [ 'results' => array_merge( self::state()['results'] ?? [], [ $id => $entry ] ) ] );

				if ( empty( $entry['ok'] ) && $step->is_critical() ) {
					$stop   = true;
					$status = 'FAILED';
					break;
				}
				if ( empty( $entry['ok'] ) ) {
					$status = 'COMPLETED_WITH_WARNINGS';
				}
			}

			$report = [
				'ok'             => 'FAILED' !== $status,
				'status'         => $status,
				'correlation_id' => $context->correlation_id,
				'started_at'     => $started,
				'completed_at'   => gmdate( 'c' ),
				'stopped_early'  => $stop,
				'results'        => $results,
				'context'        => $context->to_array(),
			];
			$path = self::export_evidence( $report );
			$report['evidence_path'] = $path;
			self::save_state(
				[
					'status'         => $status,
					'completed_at'   => $report['completed_at'],
					'evidence_path'  => $path,
					'last_run'       => $report,
				]
			);
			return $report;
		} finally {
			self::release_lock();
		}
	}

	/**
	 * @param NGC_Provisioning_Step $step Step.
	 * @param NGC_Provision_Context $context Context.
	 * @return array<string,mixed>
	 */
	public static function execute_step( NGC_Provisioning_Step $step, NGC_Provision_Context $context ) {
		$started = gmdate( 'c' );
		$pre     = $step->preflight( $context );
		if ( ! $pre->ok ) {
			return [
				'ok'             => false,
				'status'         => 'PREFLIGHT_FAILED',
				'step'           => $step->id(),
				'version'        => $step->version(),
				'started_at'     => $started,
				'completed_at'   => gmdate( 'c' ),
				'correlation_id' => $context->correlation_id,
				'preflight'      => $pre->to_array(),
				'plan'           => null,
				'apply'          => null,
				'verify'         => null,
			];
		}

		$plan = $step->plan( $context );
		if ( $context->dry_run ) {
			return [
				'ok'             => true,
				'status'         => 'DRY_RUN',
				'step'           => $step->id(),
				'version'        => $step->version(),
				'started_at'     => $started,
				'completed_at'   => gmdate( 'c' ),
				'correlation_id' => $context->correlation_id,
				'preflight'      => $pre->to_array(),
				'plan'           => $plan->to_array(),
				'apply'          => null,
				'verify'         => null,
			];
		}

		$apply  = $step->apply( $context );
		$verify = $step->verify( $context );
		$ok     = $apply->ok && $verify->ok;

		return [
			'ok'             => $ok,
			'status'         => $ok ? $apply->status : ( $apply->ok ? 'VERIFY_FAILED' : $apply->status ),
			'step'           => $step->id(),
			'version'        => $step->version(),
			'started_at'     => $started,
			'completed_at'   => gmdate( 'c' ),
			'correlation_id' => $context->correlation_id,
			'actor_id'       => $context->actor_id,
			'environment'    => $context->environment,
			'preflight'      => $pre->to_array(),
			'plan'           => $plan->to_array(),
			'apply'          => $apply->to_array(),
			'verify'         => $verify->to_array(),
		];
	}

	/**
	 * Rollback a single step.
	 *
	 * @param string                $step_id Step id.
	 * @param NGC_Provision_Context $context Context.
	 * @return array<string,mixed>
	 */
	public static function rollback_step( $step_id, NGC_Provision_Context $context ) {
		foreach ( self::steps() as $step ) {
			if ( $step->id() === $step_id ) {
				$result = $step->rollback( $context );
				return [
					'step'   => $step_id,
					'result' => $result->to_array(),
				];
			}
		}
		return [ 'step' => $step_id, 'result' => [ 'ok' => false, 'status' => 'NOT_FOUND' ] ];
	}

	/**
	 * @param NGC_Provisioning_Step $step Step.
	 * @param array<string,mixed>   $entry Entry.
	 * @param NGC_Provision_Context $context Context.
	 */
	private static function write_checkpoint( NGC_Provisioning_Step $step, array $entry, NGC_Provision_Context $context ) {
		$dir = WP_CONTENT_DIR . '/uploads/ngt-provisioning/checkpoints';
		wp_mkdir_p( $dir );
		$file = $dir . '/step-' . str_pad( (string) $step->order(), 2, '0', STR_PAD_LEFT ) . '-' . $step->id() . '.md';
		$lines = [
			'# Step ' . $step->order() . ' — ' . $step->label(),
			'',
			'## Status',
			(string) ( $entry['status'] ?? '' ),
			'',
			'## Correlation ID',
			$context->correlation_id,
			'',
			'## Evidence',
			'```json',
			wp_json_encode( $entry, JSON_PRETTY_PRINT ),
			'```',
			'',
		];
		file_put_contents( $file, implode( "\n", $lines ) ); // phpcs:ignore
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return string
	 */
	public static function export_evidence( array $report ) {
		$dirs = [
			WP_CONTENT_DIR . '/uploads/ngt-provisioning',
			WP_CONTENT_DIR . '/ngt-provisioning',
		];
		$path = '';
		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
				continue;
			}
			$path = $dir . '/run-' . gmdate( 'Ymd-His' ) . '.json';
			$ok   = (bool) file_put_contents( $path, wp_json_encode( $report, JSON_PRETTY_PRINT ) ); // phpcs:ignore
			if ( $ok ) {
				file_put_contents( $dir . '/latest.json', wp_json_encode( $report, JSON_PRETTY_PRINT ) ); // phpcs:ignore
				break;
			}
			$path = '';
		}
		update_option( 'ngc_provisioning_last_report', $report, false );
		return $path ? $path : 'option:ngc_provisioning_last_report';
	}

	/**
	 * @param string $file Plugin file.
	 * @return bool
	 */
	public static function is_plugin_active( $file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $file );
	}

	/**
	 * @param string $group first-party|third-party|all.
	 * @return array<int,array<string,mixed>>
	 */
	public static function plugin_matrix( $group = 'all' ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$first = [
			'NextGenTutors-Companion/nextgencompanion.php' => 'NextGenTutors Companion',
			'NextGenTutors-Mission-Control/nextgentutors-mission-control.php' => 'Mission Control',
			'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php' => 'Plugin Manager',
			'NextGenTutors-AI-Integration/nextgentutors-ai-integration.php' => 'AI Integration',
			'NextGenTutors-Html-Importer/revamp-html-importer.php' => 'HTML Importer',
		];
		$third = [
			'woocommerce/woocommerce.php' => 'WooCommerce',
			'fluent-crm/fluent-crm.php' => 'FluentCRM',
			'fluent-support/fluent-support.php' => 'Fluent Support',
			'fluent-smtp/fluent-smtp.php' => 'FluentSMTP',
			'elementor/elementor.php' => 'Elementor',
			'automatorwp/automatorwp.php' => 'AutomatorWP',
			'gamipress/gamipress.php' => 'GamiPress',
			'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php' => 'MasterStudy LMS',
			'user-role-editor/user-role-editor.php' => 'User Role Editor',
		];
		$want = 'first-party' === $group ? $first : ( 'third-party' === $group ? $third : $first + $third );
		$all = get_plugins();
		$active = (array) get_option( 'active_plugins', [] );
		$rows = [];
		foreach ( $want as $file => $label ) {
			$rows[] = [
				'plugin'    => $label,
				'file'      => $file,
				'installed' => isset( $all[ $file ] ),
				'active'    => in_array( $file, $active, true ),
				'version'   => $all[ $file ]['Version'] ?? '',
			];
		}
		return $rows;
	}
}
