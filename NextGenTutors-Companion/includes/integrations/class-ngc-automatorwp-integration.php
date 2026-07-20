<?php
/**
 * AutomatorWP integration — NextGen Companion workflow triggers.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers NextGen Companion as an AutomatorWP integration.
 */
class NGC_AutomatorWP_Integration {

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'automatorwp_init', [ __CLASS__, 'register_integration' ] );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow_dispatched' ], 10, 2 );
	}

	/**
	 * @return void
	 */
	public static function register_integration() {
		if ( ! function_exists( 'automatorwp_register_integration' ) ) {
			return;
		}

		automatorwp_register_integration(
			'nextgencompanion',
			[
				'label' => __( 'NextGen Companion', 'nextgencompanion' ),
				'icon'  => 'dashicons-welcome-learn-more',
			]
		);

		automatorwp_register_trigger(
			'nextgencompanion_workflow_event',
			[
				'integration'   => 'nextgencompanion',
				'label'         => __( 'NextGen workflow event fires', 'nextgencompanion' ),
				'select_option' => __( 'A <strong>NextGen workflow event</strong> fires', 'nextgencompanion' ),
				'edit_label'    => __( 'NextGen workflow event:', 'nextgencompanion' ),
				'log_label'     => __( 'NextGen workflow event', 'nextgencompanion' ),
				'action'        => 'ngc_workflow_dispatched',
				'function'      => [ __CLASS__, 'listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
				'options'       => [
					'event' => [
						'from'    => 'event',
						'default' => 'ngt.find_tutor.submitted',
						'fields'  => [
							'event' => [
								'name'    => __( 'Event slug:', 'nextgencompanion' ),
								'type'    => 'text',
								'default' => 'ngt.find_tutor.submitted',
							],
						],
					],
				],
				'tags'          => [
					'event' => [
						'label'     => __( 'Event', 'nextgencompanion' ),
						'type'      => 'text',
						'preview'   => 'ngt.find_tutor.submitted',
						'conditional' => false,
					],
					'payload' => [
						'label'     => __( 'Payload JSON', 'nextgencompanion' ),
						'type'      => 'text',
						'preview'   => '{}',
						'conditional' => false,
					],
				],
			]
		);

		if ( function_exists( 'automatorwp_register_action' ) ) {
			automatorwp_register_action(
				'nextgencompanion_dispatch_workflow',
				[
					'integration' => 'nextgencompanion',
					'label'       => __( 'Dispatch NextGen workflow event', 'nextgencompanion' ),
					'select_option' => __( 'Dispatch a <strong>NextGen workflow event</strong>', 'nextgencompanion' ),
					'edit_label'  => __( 'Dispatch event:', 'nextgencompanion' ),
					'log_label'   => __( 'Dispatch NextGen workflow', 'nextgencompanion' ),
					'function'    => [ __CLASS__, 'action_dispatch' ],
					'options'     => [
						'event' => [
							'from'   => 'event',
							'fields' => [
								'event' => [
									'name' => __( 'Companion event (without ngt. prefix):', 'nextgencompanion' ),
									'type' => 'text',
								],
							],
						],
					],
				]
			);
		}
	}

	/**
	 * @param string               $full Full event key.
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_workflow_dispatched( $full, $vars ) {
		if ( ! function_exists( 'automatorwp_trigger_event' ) ) {
			return;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id && ! empty( $vars['user_id'] ) ) {
			$user_id = (int) $vars['user_id'];
		}
		automatorwp_trigger_event(
			[
				'trigger' => 'nextgencompanion_workflow_event',
				'user_id' => $user_id,
				'event'   => (string) $full,
				'payload' => wp_json_encode( $vars ),
			]
		);
	}

	/**
	 * AutomatorWP trigger listener (required signature).
	 *
	 * @param string               $full Event.
	 * @param array<string, mixed> $vars Vars.
	 */
	public static function listener( $full, $vars ) {
		self::on_workflow_dispatched( $full, is_array( $vars ) ? $vars : [] );
	}

	/**
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $action  Action config.
	 * @param array<string, mixed> $args    Args.
	 */
	public static function action_dispatch( $user_id, $action, $args ) {
		$event = sanitize_text_field( (string) automatorwp_get_action_meta( $action['id'], 'event', true ) );
		if ( ! $event || ! class_exists( 'NGC_Workflows' ) ) {
			return;
		}
		NGC_Workflows::dispatch( $event, is_array( $args ) ? $args : [] );
	}
}
