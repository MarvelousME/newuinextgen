<?php
/**
 * Centralized NextGenTutors business profile (SSOT).
 *
 * Source: config/nextgentutors-business-profile.json (repo) or plugin copy.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load / apply / diff company + WordPress identity settings.
 */
final class NGC_Business_Profile {

	public const OPTION_KEY = 'ngc_company_profile';
	public const OPTION_APPLIED_HASH = 'ngc_business_profile_hash';

	/**
	 * Candidate paths for the JSON SSOT (first readable wins).
	 *
	 * @return string[]
	 */
	public static function candidate_paths() {
		$paths = [
			dirname( NGC_PLUGIN_DIR ) . '/../config/nextgentutors-business-profile.json',
			dirname( NGC_PLUGIN_DIR, 2 ) . '/config/nextgentutors-business-profile.json',
			ABSPATH . '../config/nextgentutors-business-profile.json',
			dirname( ABSPATH ) . '/config/nextgentutors-business-profile.json',
			NGC_PLUGIN_DIR . 'config/nextgentutors-business-profile.json',
		];
		// Docker mount: workspace sibling of plugins.
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$paths[] = dirname( WP_CONTENT_DIR ) . '/config/nextgentutors-business-profile.json';
			$paths[] = WP_CONTENT_DIR . '/../config/nextgentutors-business-profile.json';
			// Monorepo bind: /var/www/html is WP; repo may be mounted under plugins parent.
			$paths[] = WP_CONTENT_DIR . '/plugins/../config/nextgentutors-business-profile.json';
		}
		return array_values( array_unique( $paths ) );
	}

	/**
	 * @return string Absolute path or empty.
	 */
	public static function resolve_path() {
		foreach ( self::candidate_paths() as $path ) {
			$real = realpath( $path );
			if ( $real && is_readable( $real ) ) {
				return $real;
			}
		}
		return '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function load() {
		$path = self::resolve_path();
		if ( ! $path ) {
			return self::fallback_profile();
		}
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) || empty( $data['business'] ) || ! is_array( $data['business'] ) ) {
			return self::fallback_profile();
		}
		$data['_source_path'] = $path;
		return $data;
	}

	/**
	 * Hard-coded fallback matching master prompt (used if JSON missing).
	 *
	 * @return array<string, mixed>
	 */
	public static function fallback_profile() {
		return [
			'schema_version' => '1.0.0',
			'theme_package'  => 'NextGenTutors-BeyondInfinity',
			'business'       => [
				'legal_name'          => 'Next Gen Tutors',
				'trading_name'        => 'NextGenTutors',
				'platform_name'       => 'NextGenTutors',
				'powered_by'          => 'GET ONLINE NOW',
				'country'             => 'South Africa',
				'timezone'            => 'Africa/Johannesburg',
				'currency'            => 'ZAR',
				'primary_location'    => 'Johannesburg',
				'operating_regions'   => [ 'Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape' ],
				'phone'               => '0813340625',
				'whatsapp_e164'       => '27813340625',
				'primary_email'       => 'support@nextgentutors.co.za',
				'admin_email'         => 'admin@nextgentutors.co.za',
				'notification_email'  => 'marvin.saunders@gmail.com',
				'website'             => 'https://www.nextgentutors.co.za',
				'learning_modes'      => [ 'Online', 'In Person', 'Hybrid' ],
				'student_levels'      => [ 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'Tertiary' ],
				'tagline'             => 'Accessible tutoring marketplace across South Africa',
			],
			'_source_path'   => 'fallback',
		];
	}

	/**
	 * Diff current options vs profile without writing.
	 *
	 * @return array{changes:array<int,array<string,string>>,conflicts:array<int,array<string,string>>}
	 */
	public static function diff() {
		$profile = self::load();
		$b       = $profile['business'];
		$current = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $current ) ) {
			$current = [];
		}
		$desired = self::to_company_option( $b );
		$changes = [];
		$conflicts = [];
		foreach ( $desired as $key => $val ) {
			$have = $current[ $key ] ?? null;
			if ( (string) $have === (string) $val || ( is_array( $val ) && $have === $val ) ) {
				continue;
			}
			$row = [
				'field'    => $key,
				'current'  => is_scalar( $have ) || null === $have ? (string) $have : wp_json_encode( $have ),
				'desired'  => is_scalar( $val ) ? (string) $val : wp_json_encode( $val ),
			];
			if ( null !== $have && '' !== (string) $have && (string) $have !== (string) $val ) {
				$conflicts[] = $row;
			}
			$changes[] = $row;
		}
		return [ 'changes' => $changes, 'conflicts' => $conflicts, 'source' => $profile['_source_path'] ?? '' ];
	}

	/**
	 * @param array<string, mixed> $b Business block.
	 * @return array<string, mixed>
	 */
	public static function to_company_option( array $b ) {
		$phone = preg_replace( '/\s+/', '', (string) ( $b['phone'] ?? '' ) );
		return [
			'company_name'         => (string) ( $b['trading_name'] ?? 'NextGenTutors' ),
			'legal_name'           => (string) ( $b['legal_name'] ?? '' ),
			'platform_name'        => (string) ( $b['platform_name'] ?? '' ),
			'powered_by'           => (string) ( $b['powered_by'] ?? '' ),
			'tagline'              => (string) ( $b['tagline'] ?? '' ),
			'phone'                => $phone,
			'whatsapp'             => (string) ( $b['whatsapp_e164'] ?? '' ),
			'email'                => (string) ( $b['primary_email'] ?? '' ),
			'admin_email'          => (string) ( $b['admin_email'] ?? '' ),
			'notification_email'   => (string) ( $b['notification_email'] ?? '' ),
			'website'              => (string) ( $b['website'] ?? '' ),
			'address'              => (string) ( $b['primary_location'] ?? '' ) . ', ' . (string) ( $b['country'] ?? '' ),
			'country'              => (string) ( $b['country'] ?? 'South Africa' ),
			'timezone'             => (string) ( $b['timezone'] ?? 'Africa/Johannesburg' ),
			'currency'             => (string) ( $b['currency'] ?? 'ZAR' ),
			'operating_regions'    => array_values( (array) ( $b['operating_regions'] ?? [] ) ),
			'learning_modes'       => array_values( (array) ( $b['learning_modes'] ?? [] ) ),
			'student_levels'       => array_values( (array) ( $b['student_levels'] ?? [] ) ),
			'theme_package'        => 'NextGenTutors-BeyondInfinity',
		];
	}

	/**
	 * Apply profile to WordPress + Companion options (idempotent).
	 *
	 * @param bool $force Overwrite conflicting non-empty values.
	 * @return array<string, mixed>
	 */
	public static function apply( $force = false ) {
		$profile = self::load();
		$b       = $profile['business'];
		$diff    = self::diff();
		if ( ! $force && ! empty( $diff['conflicts'] ) ) {
			return [
				'ok'        => false,
				'blocked'   => true,
				'message'   => 'Existing values differ — re-run with --force-safe to overwrite.',
				'diff'      => $diff,
			];
		}

		$company = self::to_company_option( $b );
		update_option( self::OPTION_KEY, $company, false );

		// Core identity.
		update_option( 'blogname', (string) $b['platform_name'] );
		update_option( 'blogdescription', (string) ( $b['tagline'] ?? '' ) );
		update_option( 'timezone_string', (string) $b['timezone'] );
		update_option( 'WPLANG', 'en_ZA' );

		$admin_email = sanitize_email( (string) $b['admin_email'] );
		if ( is_email( $admin_email ) ) {
			update_option( 'admin_email', $admin_email );
			update_option( 'new_admin_email', $admin_email );
		}

		// WooCommerce store basics when present.
		if ( class_exists( 'WooCommerce' ) ) {
			update_option( 'woocommerce_default_country', 'ZA' );
			update_option( 'woocommerce_currency', 'ZAR' );
			update_option( 'woocommerce_store_phone', $company['phone'] );
			update_option( 'woocommerce_email_from_address', $company['email'] );
			update_option( 'woocommerce_email_from_name', $company['company_name'] );
		}

		self::apply_theme_bridge( $company, $b );

		/**
		 * Allow core plugins (AI, Hub, Plugin Manager) to sync branding after SSOT apply.
		 *
		 * @param array<string, mixed> $company Company option payload.
		 * @param array<string, mixed> $b       Raw business JSON block.
		 */
		do_action( 'ngc_business_profile_applied', $company, $b );

		$hash = md5( wp_json_encode( $company ) );
		update_option( self::OPTION_APPLIED_HASH, $hash, false );

		return [
			'ok'      => true,
			'source'  => $profile['_source_path'] ?? '',
			'company' => $company,
			'theme'   => 'NextGenTutors-BeyondInfinity',
		];
	}

	/**
	 * Push contact fields into BeyondInfinity theme mods (+ legacy ngt_* keys).
	 *
	 * @param array<string, mixed> $company Mapped company option.
	 * @param array<string, mixed> $b       Business JSON block.
	 */
	private static function apply_theme_bridge( array $company, array $b ) {
		$phone     = (string) ( $company['phone'] ?? '' );
		$email     = (string) ( $company['email'] ?? '' );
		$admin     = (string) ( $company['admin_email'] ?? '' );
		$whatsapp  = preg_replace( '/\D+/', '', (string) ( $company['whatsapp'] ?? '' ) );
		$area      = trim( (string) ( $b['primary_location'] ?? 'Johannesburg' ) . ' launch, online support nationwide' );

		$writes = [
			'bi_phone'         => $phone,
			'bi_email'         => $admin ?: $email,
			'bi_support_email' => $email,
			'bi_whatsapp'      => $whatsapp,
			'bi_service_area'  => $area,
		];

		if ( function_exists( 'bi_update_theme_option' ) ) {
			foreach ( $writes as $key => $val ) {
				bi_update_theme_option( $key, $val );
			}
		} else {
			foreach ( $writes as $key => $val ) {
				set_theme_mod( $key, $val );
			}
		}

		// Legacy page-template theme mods (parallel contact stack).
		$stylesheet = 'nextgentutors-beyondinfinity';
		$mods       = get_option( 'theme_mods_' . $stylesheet, [] );
		if ( ! is_array( $mods ) ) {
			$mods = [];
		}
		$mods['ngt_contact_email']    = $email;
		$mods['ngt_contact_phone']    = $phone;
		$mods['ngt_contact_whatsapp'] = $whatsapp;
		foreach ( $writes as $key => $val ) {
			$mods[ $key ] = $val;
		}
		update_option( 'theme_mods_' . $stylesheet, $mods );
	}

	/**
	 * Current applied company option (empty array if unset).
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$opt = get_option( self::OPTION_KEY, [] );
		return is_array( $opt ) ? $opt : [];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function status() {
		$profile = self::load();
		$opt     = get_option( self::OPTION_KEY, [] );
		$diff    = self::diff();
		return [
			'source'           => $profile['_source_path'] ?? '',
			'theme_package'    => $profile['theme_package'] ?? 'NextGenTutors-BeyondInfinity',
			'applied'          => is_array( $opt ) && ! empty( $opt['company_name'] ),
			'hash'             => (string) get_option( self::OPTION_APPLIED_HASH, '' ),
			'pending_changes'  => count( $diff['changes'] ),
			'conflicts'        => count( $diff['conflicts'] ),
			'phone'            => is_array( $opt ) ? ( $opt['phone'] ?? '' ) : '',
			'email'            => is_array( $opt ) ? ( $opt['email'] ?? '' ) : '',
		];
	}
}
