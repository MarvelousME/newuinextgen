<?php
/**
 * FluentCRM adapter — lists, tags, contact upsert.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentCRM integration via official Contact API.
 */
class NGC_Fluentcrm_Adapter extends NGC_Adapter_Base {

	/** @var string[] */
	const LISTS = [
		'Tutor',
		'Parent',
		'Student',
		'Tutor Leads',
		'Active Customers',
		'Marketing Opt-In',
	];

	/** Stable FluentCRM list slug for recruitment leads. */
	const TUTOR_LEADS_LIST_SLUG = 'tutor-leads';

	/** @var string[] */
	const TAGS = [
		'Tutor Applicant',
		'Tutor Approved',
		'Tutor Rejected',
		'Tutor Resubmitted',
		'Parent Registered',
		'Parent Enquiry',
		'Prospective Parent',
		'Parent Matched',
		'Parent Paid',
		'Student Registered',
		'Child Learner',
		'LMS Student',
		'Active Learner',
		'POPIA Consented',
		'POPIA Withdrawn',
		'Do Not Market',
		'Marketing Opt-In',
		'Engaged Customer',
		'Loyal Customer',
		'Needs Support',
		'Satisfied',
		'Advocate',
		'Verified Tutor',
		'Ready for Bookings',
		'Pending Review',
		// Tutor recruitment pipeline (stable titles → sanitize_title slugs).
		'Tutor Lead',
		'Tutor Lead — New',
		'Tutor Lead — Qualified',
		'Tutor Lead — Contact Approved',
		'Tutor Lead — Contacted',
		'Tutor Lead — Replied',
		'Tutor Lead — Interested',
		'Tutor Lead — Nurture',
		'Tutor Lead — Application Started',
		'Tutor Lead — Applied',
		'Tutor Lead — Not Interested',
		'Tutor Lead — Do Not Contact',
		'Tutor Lead — Human Review',
	];

	/**
	 * POPIA / tutoring custom fields (from Create-FluentCRM-Custom-Fields.xlsx).
	 *
	 * @return array<int, array{label:string,slug:string,type:string}>
	 */
	public static function custom_field_defs() {
		return [
			[ 'label' => 'POPIA Consent Given', 'slug' => 'popia_consent_given', 'type' => 'checkbox' ],
			[ 'label' => 'Consent Timestamp', 'slug' => 'popia_consent_date', 'type' => 'date' ],
			[ 'label' => 'Consent IP Address', 'slug' => 'popia_consent_ip', 'type' => 'text' ],
			[ 'label' => 'Consent Version', 'slug' => 'popia_consent_version', 'type' => 'text' ],
			[ 'label' => 'Data Processing Purpose', 'slug' => 'popia_processing_purpose', 'type' => 'select-multi' ],
			// Used by FluentCRM-Automation-JSON-Export parent nurture / rating conditions.
			[ 'label' => 'Sessions Count', 'slug' => 'sessions_count', 'type' => 'number' ],
			[ 'label' => 'Last Session Date', 'slug' => 'last_session_date', 'type' => 'date' ],
			[ 'label' => 'Latest Rating', 'slug' => 'latest_rating', 'type' => 'number' ],
			[ 'label' => 'Verification Status', 'slug' => 'verification_status', 'type' => 'text' ],
		];
	}

	/**
	 * @return string
	 */
	public function slug() {
		return 'fluentcrm';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return function_exists( 'FluentCrmApi' )
			&& class_exists( '\FluentCrm\App\Models\Subscriber' )
			&& $this->tables_exist();
	}

	/**
	 * FluentCRM was activated but may not have run its DB migrations yet
	 * (e.g. silent activation). Querying its models then throws a fatal
	 * QueryException, so verify the core table first.
	 *
	 * @return bool
	 */
	private function tables_exist() {
		static $exists = null;
		if ( null !== $exists ) {
			return $exists;
		}

		$required = [ 'fc_lists', 'fc_tags', 'fc_subscribers' ];
		$resolved = [];
		foreach ( $required as $suffix ) {
			$table = $this->resolve_table_name( $suffix );
			if ( ! $table ) {
				$exists = false;
				return false;
			}
			$resolved[] = $table;
		}

		// All core tables must belong to one discovered prefix/schema set.
		$prefixes = [];
		foreach ( $resolved as $index => $table ) {
			$prefixes[] = substr( $table, 0, -strlen( $required[ $index ] ) );
		}
		$exists = 1 === count( array_unique( $prefixes ) );
		return $exists;
	}

	/**
	 * Resolve a FluentCRM table from the live database rather than assuming a
	 * hard-coded WordPress prefix. Exact current-site and network prefixes are
	 * preferred; a suffix lookup supports migrated/staged installations.
	 *
	 * @param string $suffix FluentCRM table suffix.
	 * @return string Empty when no table exists.
	 */
	private function resolve_table_name( $suffix ) {
		global $wpdb;

		$suffix     = preg_replace( '/[^a-z0-9_]/i', '', (string) $suffix );
		$candidates = array_unique(
			array_filter(
				[
					$wpdb->prefix . $suffix,
					$wpdb->base_prefix . $suffix,
				]
			)
		);

		try {
			foreach ( $candidates as $candidate ) {
				$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $candidate ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $found === $candidate ) {
					return $found;
				}
			}

			$pattern = '%' . $wpdb->esc_like( '_' . $suffix );
			$matches = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pattern ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( 1 === count( $matches ) ) {
				return (string) $matches[0];
			}
		} catch ( Throwable $e ) {
			error_log( 'NGC FluentCRM table lookup failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		$checks = [
			'active' => $this->is_available(),
			'lists'  => [],
			'tags'   => [],
		];
		if ( ! $checks['active'] ) {
			$checks['ok'] = false;
			$checks['status'] = 'PARTIAL — plugin API unavailable';
			return $checks;
		}

		$this->bootstrap_assets();

		foreach ( self::LISTS as $list ) {
			$checks['lists'][ $list ] = (bool) $this->ensure_list( $list );
		}
		foreach ( self::TAGS as $tag ) {
			$checks['tags'][ $tag ] = (bool) $this->ensure_tag( $tag );
		}
		$checks['ok'] = ! in_array( false, $checks['lists'], true ) && ! in_array( false, $checks['tags'], true );
		$checks['status'] = $checks['ok'] ? 'VERIFIED' : 'PARTIAL — list/tag bootstrap incomplete';
		return $checks;
	}

	/**
	 * @param string               $action  Action.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( ! $this->is_available() ) {
			return $this->handle_error( 'crm_unavailable', __( 'FluentCRM is not active.', 'nextgencompanion' ) );
		}

		$email = $payload['email'] ?? '';
		if ( ! $email || ! is_email( $email ) ) {
			$result = $this->handle_error( 'crm_skipped_no_email', __( 'CRM sync skipped — no email.', 'nextgencompanion' ) );
			$this->audit_result( 'CRM_SKIPPED_NO_EMAIL', $result, (int) ( $payload['user_id'] ?? 0 ) );
			return $result;
		}

		$this->bootstrap_assets();

		$lists = (array) ( $payload['lists'] ?? [] );
		$tags  = (array) ( $payload['tags'] ?? [] );
		$detach_tags = (array) ( $payload['detach_tags'] ?? [] );
		$custom      = array_merge(
			[
				'ngc_role'            => $payload['role'] ?? '',
				'ngc_workflow_source' => $payload['workflow'] ?? '',
				'ngc_workflow_status' => $payload['workflow_status'] ?? '',
				'ngc_tutor_status'    => $payload['tutor_status'] ?? '',
			],
			(array) ( $payload['custom_fields'] ?? [] ),
			(array) ( $payload['custom_values'] ?? [] )
		);

		$data = [
			'email'         => $email,
			'first_name'    => $payload['first_name'] ?? '',
			'last_name'     => $payload['last_name'] ?? '',
			'phone'         => $payload['phone'] ?? '',
			'status'        => 'subscribed',
			'lists'         => $lists,
			'tags'          => $tags,
			'detach_tags'   => $detach_tags,
			'custom_values' => $custom,
		];
		if ( ! empty( $payload['detach_lists'] ) ) {
			$data['detach_lists'] = (array) $payload['detach_lists'];
		}

		try {
			$api      = FluentCrmApi( 'contacts' );
			$existing = $this->get_existing( $payload );
			$contact  = $api->createOrUpdate( $data, true );
			$event    = $existing ? 'CRM_CONTACT_UPDATED' : 'CRM_CONTACT_CREATED';
			$result   = $this->success(
				[
					'id'    => $contact ? (int) $contact->id : 0,
					'email' => $email,
					'event' => $event,
					'lists' => $lists,
					'tags'  => $tags,
				]
			);
			$this->audit_result( $event, $result, (int) ( $payload['user_id'] ?? 0 ) );
			if ( $contact && ! empty( $payload['user_id'] ) ) {
				update_user_meta( (int) $payload['user_id'], 'ngc_fluentcrm_contact_id', (int) $contact->id );
			}
			return $result;
		} catch ( Exception $e ) {
			$result = $this->handle_error( 'crm_sync_failed', $e->getMessage() );
			$this->audit_result( 'CRM_SYNC_FAILED', $result, (int) ( $payload['user_id'] ?? 0 ) );
			return $result;
		}
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		if ( ! $this->is_available() ) {
			return null;
		}
		$email = $payload['email'] ?? '';
		if ( ! $email ) {
			return null;
		}
		$api = FluentCrmApi( 'contacts' );
		$contact = null;
		if ( method_exists( $api, 'getContact' ) ) {
			$contact = $api->getContact( $email );
		} else {
			$contact = \FluentCrm\App\Models\Subscriber::where( 'email', $email )->first();
		}
		if ( ! $contact ) {
			return null;
		}
		return [
			'id'    => (int) $contact->id,
			'email' => $contact->email,
		];
	}

	/**
	 * Ensure lists and tags exist.
	 */
	public function bootstrap_assets() {
		if ( ! $this->is_available() ) {
			return;
		}
		foreach ( self::LISTS as $list ) {
			$this->ensure_list( $list );
		}
		foreach ( self::TAGS as $tag ) {
			$this->ensure_tag( $tag );
		}
		$this->ensure_custom_fields();
	}

	/**
	 * Ensure POPIA custom contact fields exist (xlsx field catalogue).
	 */
	public function ensure_custom_fields() {
		if ( ! $this->is_available() ) {
			return;
		}
		foreach ( self::custom_field_defs() as $field ) {
			$this->ensure_custom_field( $field['slug'], $field['label'], $field['type'] );
		}
	}

	/**
	 * @param string $slug  Field slug/key.
	 * @param string $label Field label.
	 * @param string $type  Field type.
	 * @return bool
	 */
	private function ensure_custom_field( $slug, $label, $type = 'text' ) {
		$slug  = sanitize_key( $slug );
		$label = sanitize_text_field( $label );
		if ( ! $slug || ! $label ) {
			return false;
		}

		try {
			if ( class_exists( '\FluentCrm\App\Models\CustomContactField' ) ) {
				$existing = \FluentCrm\App\Models\CustomContactField::where( 'slug', $slug )->first();
				if ( $existing ) {
					return true;
				}
				\FluentCrm\App\Models\CustomContactField::create(
					[
						'label'  => $label,
						'slug'   => $slug,
						'type'   => $type,
						'group'  => 'NextGen POPIA',
					]
				);
				return true;
			}
		} catch ( Throwable $e ) {
			error_log( 'NGC FluentCRM custom field bootstrap skipped: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		// Option-based registry fallback (FluentCRM stores field meta in options on some versions).
		$registry = get_option( 'ngc_fluentcrm_custom_fields', [] );
		if ( ! is_array( $registry ) ) {
			$registry = [];
		}
		$registry[ $slug ] = [
			'label' => $label,
			'type'  => $type,
		];
		update_option( 'ngc_fluentcrm_custom_fields', $registry, false );
		return true;
	}

	/**
	 * Idempotent upsert of a tutor recruitment lead into FluentCRM list `tutor-leads`.
	 *
	 * @param array<string, mixed> $lead Lead record from NGC_Tutor_Leads.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function upsert_tutor_lead( array $lead ) {
		$adapter = new self();
		if ( ! $adapter->is_available() ) {
			return new WP_Error( 'ngc_fluentcrm_unavailable', __( 'FluentCRM is not active or tables are missing.', 'nextgencompanion' ) );
		}

		$email = sanitize_email( (string) ( $lead['public_email'] ?? '' ) );
		if ( ! $email || ! is_email( $email ) ) {
			return new WP_Error( 'ngc_fluentcrm_no_email', __( 'Tutor lead has no usable public email for CRM sync.', 'nextgencompanion' ) );
		}

		if ( 'do_not_contact' === ( $lead['contactability'] ?? '' ) || 'suppressed' === ( $lead['suppression_status'] ?? '' ) ) {
			return new WP_Error( 'ngc_fluentcrm_suppressed', __( 'Suppressed leads are not synced to FluentCRM outreach lists.', 'nextgencompanion' ) );
		}

		$adapter->bootstrap_assets();

		$name  = trim( (string) ( $lead['display_name'] ?? '' ) );
		$parts = preg_split( '/\s+/', $name, 2 );
		$first = $parts[0] ?? '';
		$last  = $parts[1] ?? '';

		$stage_tag = 'Tutor Lead — New';
		$outreach  = sanitize_key( (string) ( $lead['outreach_status'] ?? 'none' ) );
		$stage_map = [
			'qualified'            => 'Tutor Lead — Qualified',
			'contact_approved'     => 'Tutor Lead — Contact Approved',
			'contacted'            => 'Tutor Lead — Contacted',
			'replied'              => 'Tutor Lead — Replied',
			'interested'           => 'Tutor Lead — Interested',
			'nurture'              => 'Tutor Lead — Nurture',
			'application_started'  => 'Tutor Lead — Application Started',
			'applied'              => 'Tutor Lead — Applied',
			'not_interested'       => 'Tutor Lead — Not Interested',
			'human_review'         => 'Tutor Lead — Human Review',
		];
		if ( isset( $stage_map[ $outreach ] ) ) {
			$stage_tag = $stage_map[ $outreach ];
		}

		$result = $adapter->create_or_update(
			'upsert_tutor_lead',
			[
				'email'      => $email,
				'first_name' => $first,
				'last_name'  => $last,
				'phone'      => (string) ( $lead['public_phone'] ?? '' ),
				'lists'      => [ 'Tutor Leads' ],
				'tags'       => [ 'Tutor Lead', $stage_tag ],
				'role'       => 'tutor_lead',
				'workflow'   => 'tutor_lead_sync',
				'workflow_status' => $outreach,
			]
		);

		if ( empty( $result['ok'] ) ) {
			$code = (string) ( $result['code'] ?? 'crm_sync_failed' );
			$msg  = (string) ( $result['message'] ?? __( 'FluentCRM tutor-lead sync failed.', 'nextgencompanion' ) );
			return new WP_Error( $code, $msg, $result );
		}

		return [
			'contact_id' => (int) ( $result['id'] ?? 0 ),
			'email'      => $email,
			'list'       => self::TUTOR_LEADS_LIST_SLUG,
			'tags'       => [ 'Tutor Lead', $stage_tag ],
			'raw'        => $result,
		];
	}

	/**
	 * @param string $title List title.
	 * @return int
	 */
	private function ensure_list( $title ) {
		$id = $this->ensure_crm_asset( 'list', $title );
		return $id > 0 ? $id : 0;
	}

	/**
	 * @param string $title Tag title.
	 * @return int
	 */
	private function ensure_tag( $title ) {
		$id = $this->ensure_crm_asset( 'tag', $title );
		return $id > 0 ? $id : 0;
	}

	/**
	 * Create or resolve a FluentCRM list/tag by title.
	 *
	 * @param string $type list|tag.
	 * @param string $title Title.
	 * @return int
	 */
	private function ensure_crm_asset( $type, $title ) {
		if ( ! $this->is_available() || '' === trim( $title ) ) {
			return 0;
		}

		$slug = sanitize_title( $title );

		try {
			$api = FluentCrmApi( 'lists' === $type ? 'lists' : 'tags' );
			if ( method_exists( $api, 'all' ) ) {
				foreach ( (array) $api->all() as $row ) {
					$row_title = is_object( $row ) ? ( $row->title ?? '' ) : ( $row['title'] ?? '' );
					if ( strcasecmp( (string) $row_title, $title ) === 0 ) {
						$row_id = is_object( $row ) ? ( $row->id ?? 0 ) : ( $row['id'] ?? 0 );
						return (int) $row_id;
					}
				}
			}
			if ( method_exists( $api, 'create' ) ) {
				$created = $api->create( [ 'title' => $title, 'slug' => $slug ] );
				if ( $created ) {
					$row_id = is_object( $created ) ? ( $created->id ?? 0 ) : ( $created['id'] ?? 0 );
					if ( $row_id ) {
						return (int) $row_id;
					}
				}
			}
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Model fallback below.
		}

		try {
			if ( 'list' === $type && class_exists( '\FluentCrm\App\Models\Lists' ) ) {
				$model = \FluentCrm\App\Models\Lists::where( 'title', $title )->orWhere( 'slug', $slug )->first();
				if ( $model ) {
					return (int) $model->id;
				}
				$model = \FluentCrm\App\Models\Lists::create(
					[
						'title' => $title,
						'slug'  => $slug,
					]
				);
				return $model ? (int) $model->id : 0;
			}

			if ( 'tag' === $type && class_exists( '\FluentCrm\App\Models\Tag' ) ) {
				$model = \FluentCrm\App\Models\Tag::where( 'title', $title )->orWhere( 'slug', $slug )->first();
				if ( $model ) {
					return (int) $model->id;
				}
				$model = \FluentCrm\App\Models\Tag::create(
					[
						'title' => $title,
						'slug'  => $slug,
					]
				);
				return $model ? (int) $model->id : 0;
			}
		} catch ( Throwable $e ) {
			// FluentCRM schema unavailable — skip instead of fataling the whole site.
			error_log( 'NGC FluentCRM asset bootstrap skipped: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return 0;
	}
}
