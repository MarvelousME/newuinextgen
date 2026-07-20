<?php
/**
 * Amelia adapter — employee creation on tutor approval.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Amelia employee/provider integration via REST API, command bus, or DB fallback.
 */
class NGC_Amelia_Adapter extends NGC_Adapter_Base {

	/**
	 * @return string
	 */
	public function slug() {
		return 'amelia';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Amelia_Bootstrap' )
			? NGC_Amelia_Bootstrap::is_active()
			: ( defined( 'AMELIA_VERSION' ) || class_exists( '\AmeliaBooking\Plugin' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		$checks = [
			'active'  => $this->is_available(),
			'api_key' => false,
			'ok'      => false,
		];

		if ( ! $checks['active'] ) {
			$checks['status'] = 'PARTIAL — Amelia plugin inactive';
			return $checks;
		}

		$api_key = $this->api_key();
		$checks['api_key'] = ! empty( $api_key );
		$checks['direct']  = class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::uses_direct_mode();

		$service_id = (int) get_option( 'ngc_amelia_default_service_id', 0 );
		if ( ! $service_id && class_exists( 'NGC_Amelia_Bootstrap' ) ) {
			$service_id = NGC_Amelia_Bootstrap::ensure_default_service();
		}

		if ( $checks['direct'] && $service_id > 0 ) {
			$checks['ok']         = true;
			$checks['service_id'] = $service_id;
			$checks['status']     = 'VERIFIED — direct mode (Amelia Lite / Docker)';
			return $checks;
		}

		if ( empty( $api_key ) ) {
			$checks['status'] = 'PARTIAL — Amelia API key not configured (NextGen → Workflows → Amelia)';
			return $checks;
		}

		if ( $service_id <= 0 ) {
			$checks['status'] = 'PARTIAL — no Amelia tutoring service seeded';
			return $checks;
		}

		$checks['ok']          = true;
		$checks['service_id']  = $service_id;
		$checks['status']      = $checks['direct']
			? 'VERIFIED — direct mode (Amelia Lite / Docker)'
			: 'VERIFIED — API key configured';

		return $checks;
	}

	/**
	 * @param string               $action  Action.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( 'create_employee' !== $action ) {
			return $this->handle_error( 'amelia_invalid_action', __( 'Unsupported Amelia action.', 'nextgencompanion' ) );
		}

		if ( ! $this->is_available() ) {
			return $this->handle_error( 'amelia_unavailable', __( 'Amelia is not active.', 'nextgencompanion' ) );
		}

		$user_id = (int) ( $payload['user_id'] ?? 0 );
		$names   = $this->resolve_names( $payload, $user_id );
		$email   = sanitize_email( $payload['email'] ?? '' );

		if ( ! $email && $user_id ) {
			$user = get_userdata( $user_id );
			$email = $user ? sanitize_email( $user->user_email ) : '';
		}

		if ( ! $email ) {
			return $this->handle_error( 'amelia_missing_email', __( 'Email required for Amelia employee.', 'nextgencompanion' ) );
		}

		$existing_id = (int) get_user_meta( $user_id, 'ngc_amelia_employee_id', true );
		if ( $existing_id && class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::provider_exists( $existing_id ) ) {
			$result = $this->success(
				[
					'id'    => $existing_id,
					'event' => 'AMELIA_EMPLOYEE_EXISTS',
				]
			);
			$this->audit_result( 'AMELIA_EMPLOYEE_EXISTS', $result, $user_id );
			return $result;
		}
		if ( $existing_id ) {
			delete_user_meta( $user_id, 'ngc_amelia_employee_id' );
		}

		$service_id = $this->resolve_service_id();
		$body       = $this->build_provider_body( $names, $email, $payload, $user_id, $service_id );

		$response = null;
		$api_key  = $this->api_key();
		$direct_mode = class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::uses_direct_mode();
		$use_api  = $api_key && ! $direct_mode;

		if ( $use_api ) {
			$response = $this->api_post( '/users/providers', $body );
		}

		$employee_id = 0;
		if ( ! empty( $response['ok'] ) ) {
			$employee_id = $this->parse_employee_id( $response );
		}

		if ( ! $employee_id && class_exists( 'NGC_Amelia_Bootstrap' ) && ! $direct_mode && NGC_Amelia_Bootstrap::allows_elevated_sync() ) {
			$employee_id = $this->create_via_command_bus( $body );
		}

		if ( ! $employee_id && class_exists( 'NGC_Amelia_Bootstrap' ) && $direct_mode && NGC_Amelia_Bootstrap::allows_elevated_sync() ) {
			$employee_id = $this->create_provider_db( $body, $user_id, $service_id );
		}

		if ( ! $employee_id ) {
			$message = $response['message'] ?? __( 'Amelia employee creation failed.', 'nextgencompanion' );
			$result  = $this->handle_error( 'amelia_create_failed', $message, $this->redact_sensitive( $response ?? [] ) );
			$this->audit_result( 'AMELIA_SYNC_FAILED', $result, $user_id );
			return $result;
		}

		if ( $user_id ) {
			update_user_meta( $user_id, 'ngc_amelia_employee_id', $employee_id );
		}

		$result = $this->success(
			[
				'id'    => $employee_id,
				'event' => 'AMELIA_EMPLOYEE_CREATED',
			]
		);
		$this->audit_result( 'AMELIA_EMPLOYEE_CREATED', $result, $user_id );
		return $result;
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		$user_id = (int) ( $payload['user_id'] ?? 0 );
		$id      = (int) get_user_meta( $user_id, 'ngc_amelia_employee_id', true );
		if ( ! $id ) {
			return null;
		}
		return [ 'id' => $id ];
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @param int                  $user_id User ID.
	 * @return array{first:string,last:string}
	 */
	private function resolve_names( $payload, $user_id ) {
		$first = sanitize_text_field( (string) ( $payload['first_name'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $payload['last_name'] ?? '' ) );

		if ( ( ! $first || ! $last ) && $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$first = $first ?: sanitize_text_field( $user->first_name ?: $user->display_name );
				$last  = $last ?: sanitize_text_field( $user->last_name );
			}
		}

		if ( ! $first ) {
			$first = 'Tutor';
		}
		if ( ! $last ) {
			$last = 'User';
		}

		return [ 'first' => $first, 'last' => $last ];
	}

	/**
	 * @return int
	 */
	private function resolve_service_id() {
		$service_id = (int) get_option( 'ngc_amelia_default_service_id', 0 );
		if ( $service_id > 0 ) {
			return $service_id;
		}

		if ( class_exists( 'NGC_Amelia_Bootstrap' ) ) {
			return NGC_Amelia_Bootstrap::ensure_default_service();
		}

		$services = $this->api_get( '/services' );
		if ( ! empty( $services['data']['services'][0]['id'] ) ) {
			$service_id = (int) $services['data']['services'][0]['id'];
			update_option( 'ngc_amelia_default_service_id', $service_id, false );
			return $service_id;
		}

		return 0;
	}

	/**
	 * @param array{first:string,last:string} $names      Names.
	 * @param string                          $email      Email.
	 * @param array<string, mixed>            $payload    Payload.
	 * @param int                             $user_id    WP user ID.
	 * @param int                             $service_id Service ID.
	 * @return array<string, mixed>
	 */
	private function build_provider_body( $names, $email, $payload, $user_id, $service_id ) {
		$service_list = [];
		if ( $service_id > 0 ) {
			$service_list[] = [
				'id'          => $service_id,
				'price'       => 0,
				'minCapacity' => 1,
				'maxCapacity' => 1,
			];
		}

		return [
			'type'        => 'provider',
			'firstName'   => $names['first'],
			'lastName'    => $names['last'],
			'email'       => $email,
			'phone'       => sanitize_text_field( (string) ( $payload['phone'] ?? '' ) ),
			'note'        => sanitize_textarea_field( (string) ( $payload['subjects'] ?? '' ) ),
			'description' => sanitize_textarea_field( (string) ( $payload['bio'] ?? '' ) ),
			'status'      => 'visible',
			'externalId'  => $user_id,
			'serviceList' => $service_list,
			'weekDayList' => class_exists( 'NGC_Amelia_Bootstrap' )
				? NGC_Amelia_Bootstrap::default_week_days()
				: $this->default_week_days(),
			'periodList'  => [],
			'show'        => 1,
		];
	}

	/**
	 * @param array<string, mixed> $body Provider payload.
	 * @return int
	 */
	private function create_via_command_bus( $body ) {
		if ( ! defined( 'AMELIA_PATH' ) || ! class_exists( '\AmeliaBooking\Application\Commands\User\Provider\AddProviderCommand' ) ) {
			return 0;
		}

		if ( ! class_exists( 'NGC_Amelia_Bootstrap' ) || ! NGC_Amelia_Bootstrap::allows_elevated_sync() ) {
			return 0;
		}

		$admin_id = NGC_Amelia_Bootstrap::resolve_admin_user_id();
		if ( ! $admin_id ) {
			return 0;
		}

		$previous = get_current_user_id();
		wp_set_current_user( $admin_id );

		try {
			/** @var \AmeliaBooking\Infrastructure\Common\Container $container */
			$container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

			$command = new \AmeliaBooking\Application\Commands\User\Provider\AddProviderCommand( [] );
			foreach ( $body as $field => $value ) {
				$command->setField( $field, $value );
			}

			$command->setPermissionService( $container->getPermissionsService() );
			$command->setUserApplicationService( $container->getUserApplicationService() );

			/** @var \AmeliaBooking\Application\Commands\CommandResult $result */
			$result = $container->getCommandBus()->handle( $command );
			if ( ! $result || \AmeliaBooking\Application\Commands\CommandResult::RESULT_SUCCESS !== $result->getResult() ) {
				return 0;
			}

			$data = $result->getData();
			return $this->parse_employee_id( [ 'ok' => true, 'data' => $data ] );
		} catch ( \Throwable $e ) {
			return 0;
		} finally {
			wp_set_current_user( $previous );
		}
	}

	/**
	 * @param array<string, mixed> $body       Provider payload.
	 * @param int                  $user_id    WP user ID.
	 * @param int                  $service_id Service ID.
	 * @return int
	 */
	private function create_provider_db( $body, $user_id, $service_id ) {
		global $wpdb;

		if ( ! class_exists( 'NGC_Amelia_Bootstrap' )
			|| ! NGC_Amelia_Bootstrap::allows_elevated_sync()
			|| ! NGC_Amelia_Bootstrap::uses_direct_mode()
			|| ! NGC_Amelia_Bootstrap::table_exists( 'amelia_users' ) ) {
			return 0;
		}

		$users_table = NGC_Amelia_Bootstrap::table_name( 'amelia_users' );
		$email       = sanitize_email( (string) ( $body['email'] ?? '' ) );

		if ( $user_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$by_external = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$users_table} WHERE externalId = %d AND type = 'provider' LIMIT 1",
					$user_id
				)
			);
			if ( $by_external > 0 ) {
				return $by_external;
			}
		}

		if ( $email ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$by_email = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$users_table} WHERE email = %s AND type = 'provider' LIMIT 1",
					$email
				)
			);
			if ( $by_email > 0 ) {
				return $by_email;
			}
		}

		$row = [
			'type'        => 'provider',
			'status'      => sanitize_key( (string) ( $body['status'] ?? 'visible' ) ),
			'firstName'   => sanitize_text_field( (string) ( $body['firstName'] ?? 'Tutor' ) ),
			'lastName'    => sanitize_text_field( (string) ( $body['lastName'] ?? 'User' ) ),
			'email'       => $email,
			'phone'       => sanitize_text_field( (string) ( $body['phone'] ?? '' ) ),
			'note'        => sanitize_textarea_field( (string) ( $body['note'] ?? '' ) ),
			'description' => sanitize_textarea_field( (string) ( $body['description'] ?? '' ) ),
			'show'        => 1,
		];
		if ( $user_id > 0 ) {
			$row['externalId'] = $user_id;
		}

		$inserted = $wpdb->insert( $users_table, $row );

		if ( ! $inserted ) {
			return 0;
		}

		$employee_id = (int) $wpdb->insert_id;
		$this->seed_provider_schedule_db( $employee_id, $body, $service_id );

		return $employee_id;
	}

	/**
	 * @param int                  $employee_id Employee ID.
	 * @param array<string, mixed> $body        Provider payload.
	 * @param int                  $service_id  Service ID.
	 */
	private function seed_provider_schedule_db( $employee_id, $body, $service_id ) {
		global $wpdb;

		$employee_id = (int) $employee_id;
		if ( $employee_id <= 0 ) {
			return;
		}

		$weekdays = isset( $body['weekDayList'] ) && is_array( $body['weekDayList'] )
			? $body['weekDayList']
			: ( class_exists( 'NGC_Amelia_Bootstrap' ) ? NGC_Amelia_Bootstrap::default_week_days() : $this->default_week_days() );

		$week_table = NGC_Amelia_Bootstrap::table_name( 'amelia_providers_to_weekdays' );
		if ( NGC_Amelia_Bootstrap::table_exists( 'amelia_providers_to_weekdays' ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing_days = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$week_table} WHERE userId = %d", $employee_id )
			);
			if ( 0 === $existing_days ) {
				foreach ( $weekdays as $day ) {
					$wpdb->insert(
						$week_table,
						[
							'userId'    => $employee_id,
							'dayIndex'  => (int) ( $day['dayIndex'] ?? 0 ),
							'startTime' => sanitize_text_field( (string) ( $day['startTime'] ?? '09:00:00' ) ),
							'endTime'   => sanitize_text_field( (string) ( $day['endTime'] ?? '17:00:00' ) ),
						],
						[ '%d', '%d', '%s', '%s' ]
					);
				}
			}
		}

		if ( $service_id > 0 && NGC_Amelia_Bootstrap::table_exists( 'amelia_providers_to_services' ) ) {
			$ps_table       = NGC_Amelia_Bootstrap::table_name( 'amelia_providers_to_services' );
			$services_table = NGC_Amelia_Bootstrap::table_name( 'amelia_services' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing_link = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$ps_table} WHERE userId = %d AND serviceId = %d LIMIT 1",
					$employee_id,
					$service_id
				)
			);
			if ( $existing_link > 0 ) {
				return;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$service = $wpdb->get_row(
				$wpdb->prepare( "SELECT price, minCapacity, maxCapacity FROM {$services_table} WHERE id = %d", $service_id ),
				ARRAY_A
			);

			$wpdb->insert(
				$ps_table,
				[
					'userId'      => $employee_id,
					'serviceId'   => $service_id,
					'price'       => (float) ( $service['price'] ?? 0 ),
					'minCapacity' => (int) ( $service['minCapacity'] ?? 1 ),
					'maxCapacity' => (int) ( $service['maxCapacity'] ?? 1 ),
				],
				[ '%d', '%d', '%f', '%d', '%d' ]
			);
		}
	}

	/**
	 * @param array<string, mixed>|null $response API response envelope.
	 * @return int
	 */
	private function parse_employee_id( $response ) {
		if ( empty( $response['ok'] ) || empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return 0;
		}
		$data = $response['data'];
		return (int) ( $data['user']['id'] ?? $data['id'] ?? 0 );
	}

	/**
	 * Strip sensitive fields before audit logging.
	 *
	 * @param array<string, mixed> $data Payload.
	 * @return array<string, mixed>
	 */
	private function redact_sensitive( $data ) {
		unset( $data['Amelia'], $data['apiKey'], $data['token'] );
		return $data;
	}

	/**
	 * @return string
	 */
	private function api_key() {
		return (string) get_option( 'ngc_amelia_api_key', '' );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function default_week_days() {
		$days = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$days[] = [
				'dayIndex'  => $i,
				'startTime' => '09:00:00',
				'endTime'   => '17:00:00',
			];
		}
		return $days;
	}

	/**
	 * @param string               $path API path.
	 * @param array<string, mixed> $body Body.
	 * @return array<string, mixed>
	 */
	private function api_post( $path, $body ) {
		$url = admin_url( 'admin-ajax.php?action=wpamelia_api&call=/api/v1' . $path );
		$res = wp_remote_post(
			$url,
			[
				'timeout' => 30,
				'headers' => [
					'Content-Type' => 'application/json',
					'Amelia'       => $this->api_key(),
				],
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $res ) ) {
			return [ 'ok' => false, 'message' => $res->get_error_message() ];
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code < 200 || $code >= 300 ) {
			return [
				'ok'      => false,
				'message' => $data['message'] ?? __( 'Amelia API error.', 'nextgencompanion' ),
				'data'    => $data,
			];
		}

		return [ 'ok' => true, 'data' => $data['data'] ?? $data ];
	}

	/**
	 * @param string $path Path.
	 * @return array<string, mixed>
	 */
	private function api_get( $path ) {
		$url = admin_url( 'admin-ajax.php?action=wpamelia_api&call=/api/v1' . $path );
		$res = wp_remote_get(
			$url,
			[
				'timeout' => 20,
				'headers' => [ 'Amelia' => $this->api_key() ],
			]
		);
		if ( is_wp_error( $res ) ) {
			return [ 'ok' => false ];
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		return [ 'ok' => true, 'data' => $data['data'] ?? $data ];
	}
}
