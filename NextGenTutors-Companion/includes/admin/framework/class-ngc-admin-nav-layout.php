<?php
/**
 * Persistent navigation layouts (user / role / global).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores DnD sidebar personalization.
 */
final class NGC_Admin_Nav_Layout {

	public const USER_META  = 'ngt_admin_nav_layout';
	public const GLOBAL_OPT = 'ngt_admin_nav_layout_global';
	public const ROLE_OPT   = 'ngt_admin_nav_layout_roles';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return [
			'order'         => [],
			'hidden'        => [],
			'favorites'     => [],
			'collapsed'     => [],
			'renames'       => [],
			'custom_groups' => [],
			'version'       => 1,
		];
	}

	/**
	 * Merge global ← role ← user.
	 *
	 * @param int $user_id User.
	 * @return array<string, mixed>
	 */
	public static function get( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$layout  = self::defaults();
		$global  = get_option( self::GLOBAL_OPT, [] );
		if ( is_array( $global ) ) {
			$layout = self::merge( $layout, $global );
		}
		$user = wp_get_current_user();
		$roles = get_option( self::ROLE_OPT, [] );
		if ( is_array( $roles ) && $user && ! empty( $user->roles ) ) {
			foreach ( (array) $user->roles as $role ) {
				if ( ! empty( $roles[ $role ] ) && is_array( $roles[ $role ] ) ) {
					$layout = self::merge( $layout, $roles[ $role ] );
				}
			}
		}
		if ( $user_id ) {
			$meta = get_user_meta( $user_id, self::USER_META, true );
			if ( is_array( $meta ) ) {
				$layout = self::merge( $layout, $meta );
			}
		}
		return $layout;
	}

	/**
	 * @param array<string, mixed> $base Base.
	 * @param array<string, mixed> $over Overlay.
	 * @return array<string, mixed>
	 */
	public static function merge( array $base, array $over ) {
		foreach ( [ 'order', 'hidden', 'favorites', 'collapsed' ] as $list_key ) {
			if ( isset( $over[ $list_key ] ) && is_array( $over[ $list_key ] ) ) {
				$base[ $list_key ] = array_values( array_map( 'sanitize_text_field', $over[ $list_key ] ) );
			}
		}
		if ( isset( $over['renames'] ) && is_array( $over['renames'] ) ) {
			$clean = [];
			foreach ( $over['renames'] as $k => $v ) {
				$clean[ sanitize_text_field( (string) $k ) ] = sanitize_text_field( (string) $v );
			}
			$base['renames'] = $clean;
		}
		if ( isset( $over['custom_groups'] ) && is_array( $over['custom_groups'] ) ) {
			$base['custom_groups'] = $over['custom_groups'];
		}
		return $base;
	}

	/**
	 * @param array<string, mixed> $layout Layout.
	 * @param string               $scope  user|global.
	 * @return array<string, mixed>
	 */
	public static function save( array $layout, $scope = 'user' ) {
		$clean = self::merge( self::defaults(), $layout );
		if ( 'global' === $scope && current_user_can( 'manage_options' ) ) {
			update_option( self::GLOBAL_OPT, $clean, false );
		} else {
			update_user_meta( get_current_user_id(), self::USER_META, $clean );
		}
		return self::get();
	}

	/**
	 * Reset user layout.
	 *
	 * @return array<string, mixed>
	 */
	public static function reset() {
		delete_user_meta( get_current_user_id(), self::USER_META );
		return self::get();
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function export() {
		return self::get();
	}

	/**
	 * @param array<string, mixed> $payload Import.
	 * @return array<string, mixed>
	 */
	public static function import( array $payload ) {
		return self::save( $payload, 'user' );
	}
}
