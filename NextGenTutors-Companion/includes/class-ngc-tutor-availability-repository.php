<?php
/**
 * Tutor availability repository.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists and resolves tutor availability context.
 */
class NGC_Tutor_Availability_Repository {

	/**
	 * Resolve tutor context from post/user identifiers.
	 *
	 * @param int $tutor_id Tutor post ID or user ID.
	 * @return array<string, mixed>
	 */
	public static function resolve_tutor_context( $tutor_id ) {
		$tutor_id = (int) $tutor_id;
		$context  = [
			'tutor_id'           => $tutor_id,
			'post_id'            => 0,
			'user_id'            => 0,
			'amelia_employee_id' => 0,
			'timezone'           => 'Africa/Johannesburg',
			'delivery_mode'      => 'hybrid',
			'subjects'           => [],
			'working_hours'      => self::default_working_hours(),
		];

		$post = get_post( $tutor_id );
		if ( $post && 'tutors' === $post->post_type ) {
			$context['post_id']       = (int) $post->ID;
			$context['delivery_mode'] = self::normalize_delivery_mode( (string) get_post_meta( $post->ID, 'tutor_mode', true ) );
			$context['subjects']      = wp_get_post_terms( $post->ID, 'subject', [ 'fields' => 'names' ] ) ?: [];
			$mapped_user              = (int) get_post_meta( $post->ID, 'tutor_user_id', true );
			$context['user_id']       = $mapped_user > 0 ? $mapped_user : (int) $post->post_author;
		} else {
			$user = get_user_by( 'id', $tutor_id );
			if ( $user ) {
				$context['user_id'] = (int) $user->ID;
			}
		}

		if ( $context['user_id'] ) {
			$context['timezone']           = self::sanitize_timezone( (string) get_user_meta( $context['user_id'], 'ngc_timezone', true ) );
			$context['amelia_employee_id'] = (int) get_user_meta( $context['user_id'], 'ngc_amelia_employee_id', true );
			$user_hours                    = get_user_meta( $context['user_id'], 'ngc_tutor_working_hours', true );
			$user_mode                     = (string) get_user_meta( $context['user_id'], 'ngc_delivery_mode', true );
			if ( $user_mode ) {
				$context['delivery_mode'] = self::normalize_delivery_mode( $user_mode );
			}
			if ( is_array( $user_hours ) ) {
				$context['working_hours'] = self::normalize_working_hours( $user_hours );
			}
		}

		return $context;
	}

	/**
	 * @param array<string, mixed> $context Tutor context.
	 * @return bool
	 */
	public static function is_publicly_bookable( $context ) {
		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( ! $user_id ) {
			return false;
		}
		$verified = (bool) get_user_meta( $user_id, 'ngc_tutor_verified', true ) || (bool) get_user_meta( $user_id, 'ngt_tutor_verified', true );
		$suspend  = (bool) get_user_meta( $user_id, 'ngc_tutor_suspended', true ) || (bool) get_user_meta( $user_id, 'ngt_tutor_suspended', true );
		if ( ! $verified || $suspend ) {
			return false;
		}

		$has_profile = ! empty( $context['subjects'] ) || ! empty( $context['post_id'] );
		if ( ! empty( $context['post_id'] ) ) {
			$post = get_post( (int) $context['post_id'] );
			if ( ! $post || 'publish' !== $post->post_status ) {
				return false;
			}
		}
		return $has_profile;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function default_working_hours() {
		return [
			'monday'    => [ 'start' => '09:00', 'end' => '17:00' ],
			'tuesday'   => [ 'start' => '09:00', 'end' => '17:00' ],
			'wednesday' => [ 'start' => '09:00', 'end' => '17:00' ],
			'thursday'  => [ 'start' => '09:00', 'end' => '17:00' ],
			'friday'    => [ 'start' => '09:00', 'end' => '17:00' ],
		];
	}

	/**
	 * @param string $mode Raw mode.
	 * @return string
	 */
	public static function normalize_delivery_mode( $mode ) {
		$mode = strtolower( sanitize_text_field( $mode ) );
		if ( in_array( $mode, [ 'online', 'in-person', 'hybrid', 'personal', 'both' ], true ) ) {
			if ( in_array( $mode, [ 'both', 'personal' ], true ) ) {
				return 'hybrid';
			}
			return $mode;
		}
		return 'hybrid';
	}

	/**
	 * @param string $timezone Raw timezone.
	 * @return string
	 */
	public static function sanitize_timezone( $timezone ) {
		$timezone = sanitize_text_field( $timezone );
		return in_array( $timezone, timezone_identifiers_list(), true ) ? $timezone : 'Africa/Johannesburg';
	}

	/**
	 * @param array<string, mixed> $hours Hours.
	 * @return array<string, array<string, string>>
	 */
	public static function normalize_working_hours( $hours ) {
		$out = self::default_working_hours();
		foreach ( $hours as $day => $window ) {
			$day = sanitize_key( (string) $day );
			if ( ! isset( $out[ $day ] ) || ! is_array( $window ) ) {
				continue;
			}
			$start = sanitize_text_field( (string) ( $window['start'] ?? $out[ $day ]['start'] ) );
			$end   = sanitize_text_field( (string) ( $window['end'] ?? $out[ $day ]['end'] ) );
			if ( preg_match( '/^\d{2}:\d{2}$/', $start ) && preg_match( '/^\d{2}:\d{2}$/', $end ) ) {
				$out[ $day ] = [ 'start' => $start, 'end' => $end ];
			}
		}
		return $out;
	}
}

