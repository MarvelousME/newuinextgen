<?php
/**
 * Base adapter utilities.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared adapter helpers.
 */
abstract class NGC_Adapter_Base implements NGC_Integration_Adapter {

	/**
	 * @param string               $code    Code.
	 * @param string               $message Message.
	 * @param array<string, mixed> $data    Data.
	 * @return array<string, mixed>
	 */
	public function handle_error( $code, $message, $data = [] ) {
		return [
			'ok'      => false,
			'partial' => true,
			'code'    => $code,
			'message' => $message,
			'data'    => $data,
		];
	}

	/**
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>
	 */
	protected function success( $data = [] ) {
		return array_merge(
			[
				'ok'      => true,
				'partial' => false,
			],
			$data
		);
	}

	/**
	 * @param string               $event   Event.
	 * @param array<string, mixed> $result  Result.
	 * @param int                  $user_id User ID.
	 */
	public function audit_result( $event, $result, $user_id = 0 ) {
		NGC_Audit::log(
			sanitize_key( $event ),
			'integration',
			(int) ( $result['id'] ?? 0 ),
			$result,
			$user_id
		);
	}

	/**
	 * Split full name.
	 *
	 * @param string $name Name.
	 * @return array{0: string, 1: string}
	 */
	protected function split_name( $name ) {
		$parts = preg_split( '/\s+/', trim( (string) $name ), 2 );
		return [
			$parts[0] ?? '',
			$parts[1] ?? '',
		];
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>
	 */
	public function map_payload( $workflow, $context ) {
		list( $first, $last ) = $this->split_name( $context['name'] ?? $context['full_name'] ?? $context['parent_name'] ?? '' );
		return [
			'workflow'        => $workflow,
			'email'           => sanitize_email( $context['email'] ?? '' ),
			'first_name'      => sanitize_text_field( $context['first_name'] ?? $first ),
			'last_name'       => sanitize_text_field( $context['last_name'] ?? $last ),
			'phone'           => sanitize_text_field( $context['phone'] ?? '' ),
			'role'            => sanitize_text_field( $context['role'] ?? '' ),
			'subjects'        => sanitize_textarea_field( $context['subjects'] ?? $context['subject'] ?? '' ),
			'grade'           => sanitize_text_field( $context['grade'] ?? '' ),
			'location'        => sanitize_text_field( $context['province'] ?? $context['area'] ?? $context['location'] ?? '' ),
			'bio'             => sanitize_textarea_field( $context['bio'] ?? $context['experience'] ?? '' ),
			'user_id'         => (int) ( $context['user_id'] ?? 0 ),
			'application_id'  => (int) ( $context['application_id'] ?? 0 ),
			'student_name'    => sanitize_text_field( $context['student_name'] ?? $context['child_name'] ?? '' ),
			'parent_name'     => sanitize_text_field( $context['parent_name'] ?? '' ),
			'parent_email'    => sanitize_email( $context['parent_email'] ?? '' ),
			'rejection_reason'=> sanitize_textarea_field( $context['rejection_reason'] ?? $context['review_notes'] ?? '' ),
			'workflow_status' => sanitize_text_field( $context['workflow_status'] ?? '' ),
			'tutor_status'    => sanitize_text_field( $context['tutor_status'] ?? '' ),
		];
	}
}
