<?php
/**
 * Workflow email templates and delivery.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email adapter with merge fields and audit.
 */
class NGC_Email_Adapter extends NGC_Adapter_Base {

	/**
	 * @return string
	 */
	public function slug() {
		return 'email';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return function_exists( 'wp_mail' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		$templates = NGC_Workflow_Email_Templates::all();
		$missing   = [];
		foreach ( array_keys( NGC_Workflow_Email_Templates::defaults() ) as $key ) {
			if ( empty( $templates[ $key ]['subject'] ) ) {
				$missing[] = $key;
			}
		}
		return [
			'active'   => $this->is_available(),
			'templates'=> count( $templates ),
			'missing'  => $missing,
			'ok'       => $this->is_available() && empty( $missing ),
			'status'   => empty( $missing ) ? 'VERIFIED' : 'PARTIAL — missing templates',
		];
	}

	/**
	 * @param string               $action  send_template.
	 * @param array<string, mixed> $payload Must include template_key, to, context.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( 'send_template' !== $action ) {
			return $this->handle_error( 'email_invalid_action', __( 'Unsupported email action.', 'nextgencompanion' ) );
		}

		$key     = sanitize_key( $payload['template_key'] ?? '' );
		$to      = sanitize_email( $payload['to'] ?? '' );
		$context = (array) ( $payload['context'] ?? [] );

		if ( ! $key || ! $to ) {
			return $this->handle_error( 'email_missing_fields', __( 'Template key and recipient required.', 'nextgencompanion' ) );
		}

		$rendered = NGC_Workflow_Email_Templates::render( $key, $context );
		if ( is_wp_error( $rendered ) ) {
			$result = $this->handle_error( 'email_template_missing', $rendered->get_error_message() );
			$this->audit_result( 'EMAIL_FAILED', $result, (int) ( $context['user_id'] ?? 0 ) );
			return $result;
		}

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$sent    = wp_mail( $to, $rendered['subject'], $rendered['html'], $headers );

		if ( ! $sent ) {
			$result = $this->handle_error( 'email_send_failed', sprintf( __( 'Failed to send %s to %s.', 'nextgencompanion' ), $key, $to ) );
			$this->audit_result( 'EMAIL_FAILED', array_merge( $result, [ 'template' => $key, 'to' => $to ] ), (int) ( $context['user_id'] ?? 0 ) );
			return $result;
		}

		$result = $this->success(
			[
				'event'    => 'EMAIL_SENT',
				'template' => $key,
				'to'       => $to,
			]
		);
		$this->audit_result( 'EMAIL_SENT', $result, (int) ( $context['user_id'] ?? 0 ) );
		return $result;
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		return null;
	}

	/**
	 * Send to admin.
	 *
	 * @param string               $template_key Template.
	 * @param array<string, mixed> $context      Context.
	 * @return array<string, mixed>
	 */
	public function send_admin( $template_key, $context ) {
		return $this->create_or_update(
			'send_template',
			[
				'template_key' => $template_key,
				'to'           => get_option( 'admin_email' ),
				'context'      => $context,
			]
		);
	}
}
