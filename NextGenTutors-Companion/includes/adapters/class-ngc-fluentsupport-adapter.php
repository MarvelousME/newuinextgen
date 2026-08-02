<?php
/**
 * Fluent Support adapter — mailbox seed, portal page, ticket create.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent Support helpdesk integration.
 */
class NGC_FluentSupport_Adapter extends NGC_Adapter_Base {

	public const OPTION_MAILBOX_ID = 'ngc_fluent_support_mailbox_id';
	public const OPTION_SEEDED     = 'ngc_fluent_support_seeded';

	/**
	 * @return string
	 */
	public function slug() {
		return 'fluent_support';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return function_exists( 'FluentSupportApi' )
			&& class_exists( '\FluentSupport\App\Models\MailBox' )
			&& $this->tables_exist();
	}

	/**
	 * @return bool
	 */
	private function tables_exist() {
		global $wpdb;
		$table = $wpdb->prefix . 'fs_mail_boxes';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (string) $found === $table;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		$checks = [
			'active'     => $this->is_available(),
			'mailbox_id' => (int) get_option( self::OPTION_MAILBOX_ID, 0 ),
			'portal'     => shortcode_exists( 'fluent_support_portal' ),
		];
		if ( ! $checks['active'] ) {
			$checks['ok']     = false;
			$checks['status'] = 'PARTIAL — Fluent Support inactive';
			return $checks;
		}

		$this->bootstrap_assets();
		$checks['mailbox_id'] = (int) get_option( self::OPTION_MAILBOX_ID, 0 );
		$checks['ok']         = $checks['mailbox_id'] > 0 && $checks['portal'];
		$checks['status']     = $checks['ok'] ? 'VERIFIED' : 'PARTIAL — mailbox/portal incomplete';
		return $checks;
	}

	/**
	 * Seed NextGenTutors business mailbox + portal page binding.
	 *
	 * @param bool $force Overwrite mailbox contact fields.
	 * @return array<string, mixed>
	 */
	public function bootstrap_assets( $force = false ) {
		if ( ! $this->is_available() ) {
			return $this->handle_error( 'fs_unavailable', __( 'Fluent Support is not active.', 'nextgencompanion' ) );
		}

		$company = class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::get() : [];
		if ( empty( $company ) && class_exists( 'NGC_Business_Profile' ) ) {
			$profile = NGC_Business_Profile::load();
			$company = NGC_Business_Profile::to_company_option( $profile['business'] ?? [] );
		}

		$name  = (string) ( $company['company_name'] ?? 'NextGenTutors' );
		$email = sanitize_email( (string) ( $company['email'] ?? 'support@nextgentutors.co.za' ) );
		if ( ! is_email( $email ) ) {
			$email = 'support@nextgentutors.co.za';
		}

		$mailbox_id = $this->ensure_mailbox( $name, $email, $force );
		$portal     = $this->ensure_support_portal_page( $mailbox_id );

		update_option( self::OPTION_SEEDED, gmdate( 'c' ), false );

		return $this->success(
			[
				'mailbox_id' => $mailbox_id,
				'portal'     => $portal,
				'email'      => $email,
				'name'       => $name,
			]
		);
	}

	/**
	 * @param string $name  Inbox name.
	 * @param string $email Support email.
	 * @param bool   $force Force update.
	 * @return int
	 */
	public function ensure_mailbox( $name, $email, $force = false ) {
		$existing_id = (int) get_option( self::OPTION_MAILBOX_ID, 0 );
		$mailbox     = null;

		if ( $existing_id > 0 ) {
			$mailbox = \FluentSupport\App\Models\MailBox::find( $existing_id );
		}
		if ( ! $mailbox ) {
			$mailbox = \FluentSupport\App\Models\MailBox::query()->where( 'is_default', 'yes' )->first();
		}
		if ( ! $mailbox ) {
			$mailbox = \FluentSupport\App\Models\MailBox::query()->orderBy( 'id', 'asc' )->first();
		}

		$settings = [
			'admin_email_address' => $email,
		];

		if ( ! $mailbox ) {
			$mailbox = \FluentSupport\App\Models\MailBox::create(
				[
					'name'       => $name . ' Support',
					'slug'       => 'nextgentutors-support',
					'box_type'   => 'web',
					'email'      => $email,
					'settings'   => $settings,
					'is_default' => 'yes',
					'created_by' => get_current_user_id() ?: 1,
				]
			);
		} elseif ( $force || empty( $mailbox->email ) ) {
			$mailbox->name     = $name . ' Support';
			$mailbox->email    = $email;
			$mailbox->settings = array_merge( (array) $mailbox->settings, $settings );
			$mailbox->save();
		}

		$id = (int) ( $mailbox->id ?? 0 );
		if ( $id > 0 ) {
			update_option( self::OPTION_MAILBOX_ID, $id, false );
		}
		return $id;
	}

	/**
	 * Ensure /support (or Support title) hosts Fluent Support portal shortcode.
	 *
	 * @param int $mailbox_id Mailbox ID.
	 * @return array<string, mixed>
	 */
	public function ensure_support_portal_page( $mailbox_id = 0 ) {
		$page = get_page_by_path( 'support' );
		if ( ! $page ) {
			$q = new WP_Query(
				[
					'post_type'      => 'page',
					'title'          => 'Support',
					'posts_per_page' => 1,
					'post_status'    => [ 'publish', 'draft' ],
				]
			);
			$page = ! empty( $q->posts[0] ) ? $q->posts[0] : null;
		}

		$shortcode = '[fluent_support_portal show_logout="yes"';
		if ( $mailbox_id > 0 ) {
			$shortcode .= ' business_box_id="' . (int) $mailbox_id . '"';
		}
		$shortcode .= ']';

		$content_block = "<!-- wp:shortcode -->\n{$shortcode}\n<!-- /wp:shortcode -->";

		if ( ! $page ) {
			$page_id = wp_insert_post(
				[
					'post_title'   => 'Support',
					'post_name'    => 'support',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => $content_block,
				],
				true
			);
			if ( is_wp_error( $page_id ) ) {
				return [ 'ok' => false, 'error' => $page_id->get_error_message() ];
			}
			$page = get_post( $page_id );
		} else {
			$content = (string) $page->post_content;
			if ( false === strpos( $content, 'fluent_support_portal' ) && false === strpos( $content, 'fluent_support' ) ) {
				$new = trim( $content . "\n\n" . $content_block );
				wp_update_post(
					[
						'ID'           => $page->ID,
						'post_content' => $new,
					]
				);
			}
		}

		$page_id = (int) ( $page->ID ?? 0 );
		if ( $page_id > 0 ) {
			// Fluent Support global setting keys used across versions.
			update_option( '_fs_portal_page_id', $page_id, false );
			$settings = get_option( 'fluent_support_settings', [] );
			if ( ! is_array( $settings ) ) {
				$settings = [];
			}
			$settings['portalPageId']   = $page_id;
			$settings['portal_page_id'] = $page_id;
			update_option( 'fluent_support_settings', $settings, false );
		}

		return [
			'ok'      => $page_id > 0,
			'page_id' => $page_id,
			'url'     => $page_id ? get_permalink( $page_id ) : '',
		];
	}

	/**
	 * Create a support ticket via Fluent Support API.
	 *
	 * @param array<string, mixed> $data Ticket payload.
	 * @return array<string, mixed>
	 */
	public function create_ticket( array $data ) {
		if ( ! $this->is_available() ) {
			return $this->handle_error( 'fs_unavailable', __( 'Fluent Support is not active.', 'nextgencompanion' ) );
		}

		$email    = sanitize_email( (string) ( $data['email'] ?? '' ) );
		$name     = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
		$subject  = sanitize_text_field( (string) ( $data['subject'] ?? '' ) );
		$message  = wp_kses_post( (string) ( $data['message'] ?? '' ) );
		$category = sanitize_text_field( (string) ( $data['category'] ?? '' ) );

		if ( ! is_email( $email ) || '' === $subject || '' === $message ) {
			return $this->handle_error( 'fs_invalid', __( 'Name, email, subject and message are required.', 'nextgencompanion' ) );
		}

		$mailbox_id = (int) ( $data['mailbox_id'] ?? get_option( self::OPTION_MAILBOX_ID, 0 ) );
		if ( $mailbox_id <= 0 ) {
			$boot       = $this->bootstrap_assets( true );
			$mailbox_id = (int) ( $boot['mailbox_id'] ?? 0 );
		}
		if ( $mailbox_id <= 0 ) {
			return $this->handle_error( 'fs_no_mailbox', __( 'Support mailbox is not configured.', 'nextgencompanion' ) );
		}

		$parts      = preg_split( '/\s+/', trim( $name ?: 'Website Guest' ), 2 );
		$first_name = $parts[0] ?? 'Guest';
		$last_name  = $parts[1] ?? '';

		$content = $message;
		if ( $category ) {
			$content = '<p><strong>Category:</strong> ' . esc_html( $category ) . '</p>' . $content;
		}

		try {
			$customer = \FluentSupport\App\Models\Customer::where( 'email', $email )->first();
			if ( ! $customer ) {
				$customers_api = FluentSupportApi( 'customers' );
				$created       = $customers_api->createCustomerWithOrWithoutWpUser(
					[
						'email'      => $email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
					],
					false
				);
				if ( $created ) {
					$customer = $created;
				} else {
					$customer = \FluentSupport\App\Models\Customer::create(
						[
							'email'      => $email,
							'first_name' => $first_name,
							'last_name'  => $last_name,
						]
					);
				}
			}

			if ( ! $customer || empty( $customer->id ) ) {
				return $this->handle_error( 'fs_customer_failed', __( 'Could not create support customer.', 'nextgencompanion' ) );
			}

			$ticket_data = [
				'customer_id'     => (int) $customer->id,
				'mailbox_id'      => $mailbox_id,
				'title'           => $subject,
				'content'         => $content,
				'client_priority' => 'normal',
				'source'          => 'web',
			];

			$ticket = FluentSupportApi( 'tickets' )->createTicket( $ticket_data );
			if ( ! $ticket ) {
				return $this->handle_error( 'fs_create_failed', __( 'Ticket API returned empty response.', 'nextgencompanion' ) );
			}
			if ( is_wp_error( $ticket ) ) {
				return $this->handle_error( 'fs_create_failed', $ticket->get_error_message() );
			}

			$id = is_object( $ticket ) ? (int) ( $ticket->id ?? 0 ) : (int) ( $ticket['id'] ?? 0 );
			if ( $id <= 0 ) {
				return $this->handle_error( 'fs_create_failed', __( 'Ticket was not persisted.', 'nextgencompanion' ) );
			}

			return $this->success(
				[
					'ticket_id'  => $id,
					'mailbox_id' => $mailbox_id,
					'customer_id'=> (int) $customer->id,
				]
			);
		} catch ( Throwable $e ) {
			return $this->handle_error( 'fs_create_failed', $e->getMessage() );
		}
	}

	/**
	 * @param string               $action  Action.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( 'ticket' === $action ) {
			return $this->create_ticket( $payload );
		}
		return $this->handle_error( 'fs_unsupported_action', __( 'Unsupported Fluent Support action.', 'nextgencompanion' ) );
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		return null;
	}
}
