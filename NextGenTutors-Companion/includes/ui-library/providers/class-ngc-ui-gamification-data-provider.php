<?php
/**
 * GamiPress achievements provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Badges, leaderboards, achievements.
 */
class NGC_UI_Gamification_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'gamification';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Gamification' ) || function_exists( 'gamipress_get_user_achievements' );
	}

	/**
	 * @param array<string, mixed> $args { user_id }.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		$user_id = (int) ( $args['user_id'] ?? get_current_user_id() );
		if ( ! $user_id ) {
			return [];
		}

		if ( class_exists( 'NGC_Gamification' ) && method_exists( 'NGC_Gamification', 'get_user_badges' ) ) {
			return (array) NGC_Gamification::get_user_badges( $user_id, $args );
		}

		if ( function_exists( 'gamipress_get_user_achievements' ) ) {
			$achievements = gamipress_get_user_achievements(
				[
					'user_id' => $user_id,
					'limit'   => (int) ( $args['limit'] ?? 12 ),
				]
			);
			return is_array( $achievements ) ? $achievements : [];
		}

		return [];
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		if ( 'achievement-badge' === $component ) {
			return [
				'title' => $row['title'] ?? '',
				'icon'  => $row['icon'] ?? '',
			];
		}
		return $row;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'class'    => 'NGC_Gamification',
			'plugin'   => 'GamiPress',
		];
	}
}
