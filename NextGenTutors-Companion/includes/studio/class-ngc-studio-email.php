<?php
/**
 * Visual email designer — templates, merge fields, test send.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Studio email engine with runtime sync to workflow templates.
 */
class NGC_Studio_Email {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'ngc_email_template_override', [ __CLASS__, 'template_override' ], 10, 2 );
	}

	/**
	 * @return array<int, string>
	 */
	public static function merge_field_catalog() {
		return apply_filters(
			'ngc_studio_merge_fields',
			[
				'{{first_name}}', '{{last_name}}', '{{email}}', '{{phone}}', '{{role}}', '{{status}}',
				'{{booking}}', '{{invoice}}', '{{payment}}', '{{lesson}}', '{{tutor}}', '{{student}}',
				'{{parent}}', '{{dashboard_url}}', '{{site_name}}', '{{login_url}}',
			]
		);
	}

	/**
	 * Save and sync to live email templates immediately.
	 *
	 * @param int                  $id   Email ID.
	 * @param array<string, mixed> $data Payload.
	 * @return array{ok:bool,email?:array<string,mixed>}
	 */
	public static function save_and_apply( $id, $data ) {
		$result = NGC_Studio_Repository::update_email( $id, $data );
		if ( empty( $result['ok'] ) ) {
			return $result;
		}
		$email = $result['email'];
		if ( $email && 'published' === ( $email['status'] ?? '' ) ) {
			self::sync_to_runtime( $email );
		}
		return $result;
	}

	/**
	 * @param int $id Email ID.
	 * @return array{ok:bool,email?:array<string,mixed>}
	 */
	public static function publish( $id ) {
		return self::save_and_apply( $id, [ 'status' => 'published' ] );
	}

	/**
	 * Push studio email into NGC_Workflow_Email_Templates option.
	 *
	 * @param array<string, mixed> $email Email row.
	 */
	public static function sync_to_runtime( $email ) {
		if ( ! class_exists( 'NGC_Workflow_Email_Templates' ) ) {
			return;
		}
		$key      = sanitize_key( (string) ( $email['email_key'] ?? '' ) );
		$stored   = get_option( NGC_Workflow_Email_Templates::OPTION_KEY, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		$stored[ $key ] = [
			'subject'   => (string) ( $email['subject'] ?? '' ),
			'html'      => (string) ( $email['body_html'] ?? '' ),
			'text'      => (string) ( $email['body_text'] ?? '' ),
			'trigger'   => (string) ( $email['settings']['trigger'] ?? 'STUDIO' ),
			'recipient' => (string) ( $email['settings']['recipient'] ?? 'user' ),
		];
		update_option( NGC_Workflow_Email_Templates::OPTION_KEY, $stored, false );
	}

	/**
	 * @param int                  $id      Email ID.
	 * @param array<string, mixed> $context Test context.
	 * @return array{ok:bool,message?:string}
	 */
	public static function test_send( $id, $context = [] ) {
		$email = NGC_Studio_Repository::get_email( $id );
		if ( ! $email ) {
			return [ 'ok' => false, 'message' => __( 'Email not found.', 'nextgencompanion' ) ];
		}
		$to = (string) ( $context['to'] ?? get_option( 'admin_email' ) );
		if ( ! class_exists( 'NGC_Email_Adapter' ) ) {
			return [ 'ok' => false, 'message' => __( 'Email adapter missing.', 'nextgencompanion' ) ];
		}
		self::sync_to_runtime( $email );
		$adapter = new NGC_Email_Adapter();
		return $adapter->create_or_update(
			'send_template',
			[
				'template_key' => (string) $email['email_key'],
				'to'           => $to,
				'context'      => array_merge(
					[
						'first_name' => 'Test',
						'last_name'  => 'User',
						'email'      => $to,
						'site_name'  => get_bloginfo( 'name' ),
					],
					$context
				),
			]
		);
	}

	/**
	 * @param array<string, string>|null $template Existing.
	 * @param string                     $key      Template key.
	 * @return array<string, string>|null
	 */
	public static function template_override( $template, $key ) {
		$row = NGC_Studio_Repository::get_email_by_key( $key );
		if ( ! $row || 'published' !== ( $row['status'] ?? '' ) ) {
			return $template;
		}
		return [
			'subject' => (string) ( $row['subject'] ?? '' ),
			'html'    => (string) ( $row['body_html'] ?? '' ),
			'text'    => (string) ( $row['body_text'] ?? '' ),
		];
	}

	/**
	 * Seed default emails.
	 */
	public static function seed_defaults() {
		if ( NGC_Studio_Repository::list_emails() ) {
			return;
		}
		NGC_Studio_Repository::create_email(
			[
				'email_key'  => 'studio_welcome_parent',
				'name'       => 'Parent Welcome',
				'status'     => 'published',
				'subject'    => 'Welcome to {{site_name}}',
				'body_html'  => '<p>Hi {{first_name}},</p><p>Welcome to {{site_name}}. <a href="{{dashboard_url}}">Open your dashboard</a>.</p>',
				'body_text'  => 'Hi {{first_name}}, welcome to {{site_name}}.',
				'settings'   => [ 'recipient' => 'parent', 'trigger' => 'PARENT_REGISTERED' ],
			]
		);
	}
}
