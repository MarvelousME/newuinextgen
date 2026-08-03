<?php
/**
 * Tutor lead store + ethical discovery policy.
 *
 * Discovery: permitted APIs / first-party / consented imports only.
 * NEVER scrape LinkedIn, Google/Bing SERP, Maps people-harvest, or browser-login automation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor leads.
 */
final class NGC_Tutor_Leads {

	const OPTION = 'ngc_tutor_leads';
	const OPTION_SOURCES = 'ngc_tutor_lead_sources';

	/**
	 * Approved source types (policy matrix).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function source_policy() {
		return [
			'first_party_referral'   => [ 'allowed' => true, 'method' => 'api', 'notes' => 'Inbound referrals and applications.' ],
			'job_board_api'          => [ 'allowed' => true, 'method' => 'api', 'notes' => 'Requires approved partner API.' ],
			'public_directory_api'   => [ 'allowed' => true, 'method' => 'api', 'notes' => 'Only when ToS permits recruitment use.' ],
			'google_job_posting'     => [ 'allowed' => true, 'method' => 'structured_data', 'notes' => 'Attract applicants via JobPosting; not scrape.' ],
			'linkedin_official_api'  => [ 'allowed' => true, 'method' => 'api', 'notes' => 'Only with granted LinkedIn products/scopes.' ],
			'consented_import'       => [ 'allowed' => true, 'method' => 'import', 'notes' => 'Documented lawful basis required.' ],
			'manual_entry'           => [ 'allowed' => true, 'method' => 'manual', 'notes' => 'Operator-entered with consent record.' ],
			// Explicitly blocked.
			'linkedin_scrape'        => [ 'allowed' => false, 'method' => 'scrape', 'notes' => 'PROHIBITED.' ],
			'google_serp_scrape'     => [ 'allowed' => false, 'method' => 'scrape', 'notes' => 'PROHIBITED.' ],
			'bing_search_api'        => [ 'allowed' => false, 'method' => 'retired', 'notes' => 'Bing Search APIs retired 2025-08-11 — do not use.' ],
			'bing_serp_scrape'       => [ 'allowed' => false, 'method' => 'scrape', 'notes' => 'PROHIBITED.' ],
			'maps_people_harvest'    => [ 'allowed' => false, 'method' => 'scrape', 'notes' => 'Maps/Places is place discovery, not people search.' ],
			'browser_login_harvest'  => [ 'allowed' => false, 'method' => 'automation', 'notes' => 'PROHIBITED simulated logins.' ],
			'social_profile_scrape'  => [ 'allowed' => false, 'method' => 'scrape', 'notes' => 'PROHIBITED.' ],
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		$rows = get_option( self::OPTION, [] );
		return is_array( $rows ) ? array_values( $rows ) : [];
	}

	/**
	 * Create lead from approved source + job-relevant criteria only.
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create( array $input ) {
		$source = sanitize_key( (string) ( $input['source'] ?? '' ) );
		$policy = self::source_policy();
		if ( ! isset( $policy[ $source ] ) || empty( $policy[ $source ]['allowed'] ) ) {
			return new WP_Error(
				'ngc_lead_source_forbidden',
				sprintf(
					/* translators: %s: source slug */
					__( 'Lead source "%s" is not permitted. Use official APIs or consented imports only — no scraping or browser-login harvest.', 'nextgencompanion' ),
					$source ?: '(empty)'
				)
			);
		}

		$criteria = NGC_Lead_Criteria::sanitize( (array) ( $input['discovery_query'] ?? $input['criteria'] ?? [] ) );
		if ( is_wp_error( $criteria ) ) {
			return $criteria;
		}

		// Strip any protected fields from payload body.
		foreach ( NGC_Lead_Criteria::forbidden_keys() as $fk ) {
			unset( $input[ $fk ] );
		}

		$id = 'lead_' . wp_generate_password( 12, false, false );
		$row = [
			'id'                 => $id,
			'source'             => $source,
			'source_record_id'   => sanitize_text_field( (string) ( $input['source_record_id'] ?? '' ) ),
			'source_url'         => esc_url_raw( (string) ( $input['source_url'] ?? '' ) ),
			'discovery_query'    => $criteria,
			'subject'            => sanitize_text_field( (string) ( $input['subject'] ?? ( $criteria['subject'] ?? '' ) ) ),
			'speciality'         => sanitize_text_field( (string) ( $input['speciality'] ?? '' ) ),
			'service_area'       => sanitize_text_field( (string) ( $input['service_area'] ?? '' ) ),
			'organization'       => sanitize_text_field( (string) ( $input['organization'] ?? '' ) ),
			'display_name'       => sanitize_text_field( (string) ( $input['display_name'] ?? '' ) ),
			'public_email'       => sanitize_email( (string) ( $input['public_email'] ?? '' ) ),
			'public_phone'       => sanitize_text_field( (string) ( $input['public_phone'] ?? '' ) ),
			'lawful_basis'       => sanitize_key( (string) ( $input['lawful_basis'] ?? 'legitimate_interest_assessed' ) ),
			'consent_status'     => sanitize_key( (string) ( $input['consent_status'] ?? 'unknown' ) ),
			'contactability'     => sanitize_key( (string) ( $input['contactability'] ?? 'pending_review' ) ),
			'quality_score'      => null,
			'quality_explanation'=> '',
			'duplicate_fingerprint' => self::fingerprint( $input ),
			'crm_sync_status'    => 'pending',
			'outreach_status'    => 'none',
			'suppression_status' => 'none',
			'retention_expiry'   => sanitize_text_field( (string) ( $input['retention_expiry'] ?? gmdate( 'Y-m-d', strtotime( '+18 months' ) ) ) ),
			'created_at'         => gmdate( 'c' ),
			'updated_at'         => gmdate( 'c' ),
			'is_demo'            => ! empty( $input['is_demo'] ) ? 1 : 0,
		];

		// Deduplicate.
		foreach ( self::all() as $existing ) {
			if ( ( $existing['duplicate_fingerprint'] ?? '' ) === $row['duplicate_fingerprint'] && $row['duplicate_fingerprint'] ) {
				return new WP_Error( 'ngc_lead_duplicate', __( 'Duplicate lead fingerprint.', 'nextgencompanion' ), [ 'existing_id' => $existing['id'] ] );
			}
		}

		$all   = self::all();
		$all[] = $row;
		update_option( self::OPTION, $all, false );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'tutor_lead_created', 'lead', 0, [ 'id' => $id, 'source' => $source ] );
		}

		return $row;
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return string
	 */
	private static function fingerprint( array $input ) {
		$email = strtolower( trim( (string) ( $input['public_email'] ?? '' ) ) );
		$name  = strtolower( trim( (string) ( $input['display_name'] ?? '' ) ) );
		$org   = strtolower( trim( (string) ( $input['organization'] ?? '' ) ) );
		$src   = strtolower( trim( (string) ( $input['source_record_id'] ?? '' ) ) );
		$base  = $email ?: ( $src ?: ( $name . '|' . $org ) );
		return $base ? hash( 'sha256', $base ) : '';
	}

	/**
	 * Score lead with job-relevant explanation only.
	 *
	 * @param string               $lead_id Lead id.
	 * @param int                  $score   0–100.
	 * @param string               $explanation Explanation.
	 * @param array<string,mixed>  $factors Factors.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function score( $lead_id, $score, $explanation, array $factors = [] ) {
		$clean = NGC_Lead_Criteria::assert_explanation_clean( $explanation );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		foreach ( array_keys( $factors ) as $fk ) {
			if ( in_array( strtolower( (string) $fk ), NGC_Lead_Criteria::forbidden_keys(), true ) ) {
				return new WP_Error( 'ngc_lead_score_protected', __( 'Scoring factors must not include protected traits.', 'nextgencompanion' ) );
			}
		}
		$all = self::all();
		$found = false;
		foreach ( $all as &$row ) {
			if ( ( $row['id'] ?? '' ) === $lead_id ) {
				$row['quality_score']       = max( 0, min( 100, (int) $score ) );
				$row['quality_explanation'] = sanitize_textarea_field( (string) $explanation );
				$row['quality_factors']     = $factors;
				$row['updated_at']          = gmdate( 'c' );
				$found = true;
				$out   = $row;
				break;
			}
		}
		unset( $row );
		if ( ! $found ) {
			return new WP_Error( 'ngc_lead_missing', __( 'Lead not found.', 'nextgencompanion' ) );
		}
		update_option( self::OPTION, $all, false );
		return $out;
	}

	/**
	 * Tool-gateway entry: create from discovery payload.
	 *
	 * @param array<string, mixed> $args    Args.
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_from_discovery( array $args, array $context = [] ) {
		unset( $context );
		return self::create( $args );
	}

	/**
	 * Tool-gateway entry: create (if needed) and sync to FluentCRM.
	 *
	 * @param array<string, mixed> $args    Args.
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function upsert_and_sync( array $args, array $context = [] ) {
		unset( $context );
		$lead_id = sanitize_key( (string) ( $args['lead_id'] ?? '' ) );
		if ( '' === $lead_id ) {
			$created = self::create( $args );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
			$lead_id = (string) $created['id'];
		}
		$sync = self::sync_fluentcrm( $lead_id );
		if ( is_wp_error( $sync ) ) {
			return $sync;
		}
		return [ 'ok' => true, 'lead_id' => $lead_id, 'sync' => $sync ];
	}

	/**
	 * Sync to FluentCRM tutor-leads list when adapter available.
	 *
	 * @param string $lead_id Lead id.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function sync_fluentcrm( $lead_id ) {
		$all = self::all();
		$lead = null;
		$idx  = null;
		foreach ( $all as $i => $row ) {
			if ( ( $row['id'] ?? '' ) === $lead_id ) {
				$lead = $row;
				$idx  = $i;
				break;
			}
		}
		if ( ! $lead ) {
			return new WP_Error( 'ngc_lead_missing', __( 'Lead not found.', 'nextgencompanion' ) );
		}
		if ( 'suppressed' === ( $lead['suppression_status'] ?? '' ) || 'do_not_contact' === ( $lead['contactability'] ?? '' ) ) {
			return new WP_Error( 'ngc_lead_suppressed', __( 'Lead is suppressed — CRM sync blocked.', 'nextgencompanion' ) );
		}
		if ( ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			return new WP_Error( 'ngc_fluentcrm_missing', __( 'FluentCRM adapter not loaded.', 'nextgencompanion' ) );
		}
		$result = NGC_Fluentcrm_Adapter::upsert_tutor_lead( $lead );
		if ( is_wp_error( $result ) ) {
			$all[ $idx ]['crm_sync_status'] = 'failed';
			$all[ $idx ]['crm_sync_error']  = $result->get_error_code();
			update_option( self::OPTION, $all, false );
			return $result;
		}
		$all[ $idx ]['crm_sync_status'] = 'synced';
		$all[ $idx ]['crm_contact_id']  = $result['contact_id'] ?? null;
		$all[ $idx ]['updated_at']      = gmdate( 'c' );
		update_option( self::OPTION, $all, false );
		return [ 'ok' => true, 'lead_id' => $lead_id, 'crm' => $result ];
	}
}
