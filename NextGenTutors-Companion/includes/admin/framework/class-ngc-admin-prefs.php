<?php
/**
 * User personalization preferences for the admin shell.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Density, motion, landing page, favorites, recent pages.
 */
final class NGC_Admin_Prefs {

	public const USER_META = 'ngt_admin_prefs';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return [
			'density'        => 'comfortable',
			'motion'         => 1,
			'landing'        => 'ngt-admin',
			'favorites'      => [],
			'recent'         => [],
			'sidebar_pinned' => true,
		];
	}

	/**
	 * @param int $user_id User.
	 * @return array<string, mixed>
	 */
	public static function get( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$prefs   = self::defaults();
		$meta    = get_user_meta( $user_id, self::USER_META, true );
		if ( is_array( $meta ) ) {
			$prefs = array_merge( $prefs, $meta );
		}
		return $prefs;
	}

	/**
	 * @param array<string, mixed> $prefs Prefs.
	 * @return array<string, mixed>
	 */
	public static function save( array $prefs ) {
		$clean = self::defaults();
		if ( isset( $prefs['density'] ) ) {
			$clean['density'] = sanitize_key( (string) $prefs['density'] );
		}
		if ( isset( $prefs['motion'] ) ) {
			$clean['motion'] = (float) $prefs['motion'];
		}
		if ( isset( $prefs['landing'] ) ) {
			$clean['landing'] = sanitize_key( (string) $prefs['landing'] );
		}
		if ( isset( $prefs['favorites'] ) && is_array( $prefs['favorites'] ) ) {
			$clean['favorites'] = array_values( array_map( 'sanitize_key', $prefs['favorites'] ) );
		}
		if ( isset( $prefs['recent'] ) && is_array( $prefs['recent'] ) ) {
			$clean['recent'] = array_slice( array_values( array_map( 'sanitize_key', $prefs['recent'] ) ), 0, 20 );
		}
		if ( isset( $prefs['sidebar_pinned'] ) ) {
			$clean['sidebar_pinned'] = ! empty( $prefs['sidebar_pinned'] );
		}
		update_user_meta( get_current_user_id(), self::USER_META, $clean );
		return $clean;
	}

	/**
	 * Track recent page visits.
	 */
	public static function track_recent() {
		if ( ! is_admin() || ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $page ) {
			return;
		}
		$prefs   = self::get();
		$recent  = array_values( array_diff( (array) $prefs['recent'], [ $page ] ) );
		array_unshift( $recent, $page );
		$prefs['recent'] = array_slice( $recent, 0, 20 );
		update_user_meta( get_current_user_id(), self::USER_META, $prefs );
	}

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'track_recent' ] );
	}
}
