<?php
/**
 * Company / site-wide settings provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Company contact, branding, trust copy.
 */
class NGC_UI_Company_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'company';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * @param array<string, mixed> $args Unused.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		$opts = get_option( 'ngc_company_profile', [] );
		if ( ! is_array( $opts ) ) {
			$opts = [];
		}

		return [
			[
				'name'          => $opts['company_name'] ?? get_bloginfo( 'name' ),
				'legal_name'    => $opts['legal_name'] ?? '',
				'tagline'       => $opts['tagline'] ?? get_bloginfo( 'description' ),
				'phone'         => $opts['phone'] ?? '',
				'whatsapp'      => $opts['whatsapp'] ?? '',
				'email'         => $opts['email'] ?? get_option( 'admin_email' ),
				'admin_email'   => $opts['admin_email'] ?? get_option( 'admin_email' ),
				'notification_email' => $opts['notification_email'] ?? '',
				'address'       => $opts['address'] ?? '',
				'website'       => $opts['website'] ?? home_url( '/' ),
				'powered_by'    => $opts['powered_by'] ?? 'GET ONLINE NOW',
				'currency'      => $opts['currency'] ?? 'ZAR',
				'timezone'      => $opts['timezone'] ?? 'Africa/Johannesburg',
				'response_time' => $opts['response_time'] ?? '',
				'logo_id'       => (int) ( $opts['logo_id'] ?? 0 ),
			],
		];
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		switch ( $component ) {
			case 'contact-hero':
			case 'footer-contact':
				return [
					'phone'    => $row['phone'] ?? '',
					'email'    => $row['email'] ?? '',
					'whatsapp' => $row['whatsapp'] ?? '',
				];
			default:
				return $row;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'tables'   => [],
			'options'  => [ 'ngc_company_profile', 'blogname', 'admin_email' ],
		];
	}
}
