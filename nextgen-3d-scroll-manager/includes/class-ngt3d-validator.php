<?php
/**
 * Input validation and sanitization for all NGT 3D Scroll data.
 *
 * SECURITY: This class is the single sanitization gateway.
 * Never store rule data that has not passed through sanitize_rule().
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Validator {

	/**
	 * Validate and sanitize a selector string.
	 *
	 * Status codes:
	 *   VALID_SELECTOR      – passes all checks
	 *   INVALID_SELECTOR    – fails syntax or security check
	 *
	 * @param string $selector Raw selector input.
	 * @return array{ status: string, selector: string, error: string }
	 */
	public static function validate_selector( string $selector ): array {
		$selector = trim( $selector );

		if ( '' === $selector ) {
			return [ 'status' => 'INVALID_SELECTOR', 'selector' => '', 'error' => __( 'Selector cannot be empty.', 'ngt-3d-scroll' ) ];
		}

		// Block obvious injection payloads.
		$blocked = [
			'javascript:',
			'<script',
			'</script',
			'onerror',
			'onload',
			'onclick',
			'eval(',
			'expression(',
			'behavior:',
		];

		$lower = strtolower( $selector );
		foreach ( $blocked as $token ) {
			if ( str_contains( $lower, $token ) ) {
				return [
					'status'   => 'INVALID_SELECTOR',
					'selector' => '',
					'error'    => __( 'Selector contains disallowed content.', 'ngt-3d-scroll' ),
				];
			}
		}

		// Permit only printable ASCII minus quotes/backticks to prevent attribute injection.
		if ( ! preg_match( '/^[a-zA-Z0-9\s#.\[\]=*:>~+,\-_()\^$|%"\'\/]+$/', $selector ) ) {
			return [
				'status'   => 'INVALID_SELECTOR',
				'selector' => '',
				'error'    => __( 'Selector contains disallowed characters.', 'ngt-3d-scroll' ),
			];
		}

		// Length limit.
		if ( mb_strlen( $selector ) > 500 ) {
			return [
				'status'   => 'INVALID_SELECTOR',
				'selector' => '',
				'error'    => __( 'Selector exceeds maximum length.', 'ngt-3d-scroll' ),
			];
		}

		return [ 'status' => 'VALID_SELECTOR', 'selector' => $selector, 'error' => '' ];
	}

	/**
	 * Parse and validate a comma-separated list of animation names.
	 * Returns only names that are in the registry.
	 *
	 * @param string|string[] $input Raw animation names.
	 * @return string[]  Validated animation names.
	 */
	public static function validate_animation_names( $input ): array {
		if ( is_string( $input ) ) {
			$parts = explode( ',', $input );
		} elseif ( is_array( $input ) ) {
			$parts = $input;
		} else {
			return [];
		}

		$clean = array_map( fn( $n ) => sanitize_key( trim( $n ) ), $parts );
		$clean = array_filter( $clean );
		$clean = array_values( $clean );

		return NGT3D_Animation_Registry::filter_valid( $clean );
	}

	/**
	 * Validate and sanitize a JSON animation_options blob.
	 *
	 * @param string|array $raw Raw JSON string or already-decoded array.
	 * @return array  Sanitized options array (empty on failure).
	 */
	public static function validate_animation_options( $raw ): array {
		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
		} else {
			$decoded = $raw;
		}

		if ( ! is_array( $decoded ) ) {
			return [];
		}

		return self::sanitize_options_array( $decoded );
	}

	/**
	 * Recursively sanitize an options array.
	 * Only scalar values permitted; no closures, objects, or executable code.
	 *
	 * @param array $arr Raw array.
	 * @return array
	 */
	private static function sanitize_options_array( array $arr ): array {
		$safe = [];

		$numeric_keys = [
			'scrub', 'duration', 'delay', 'stagger', 'perspective',
			'x', 'y', 'z', 'rotateX', 'rotateY', 'rotateZ',
			'scaleFrom', 'scaleTo', 'opacityFrom', 'opacityTo',
			'rate', 'depth', 'max', 'intensity', 'speed',
			'activeOffset', 'shadowScale', 'sort_order',
		];

		$bool_keys = [ 'pin', 'pinSpacing', 'anticipatePin', 'once', 'markers', 'single' ];

		$string_keys = [ 'start', 'end', 'ease', 'displacement' ];

		$device_keys = [ 'desktop', 'tablet', 'mobile' ];

		foreach ( $arr as $key => $val ) {
			$key = sanitize_key( $key );

			if ( in_array( $key, $numeric_keys, true ) ) {
				if ( is_bool( $val ) ) {
					$safe[ $key ] = $val;
				} else {
					$num = filter_var( $val, FILTER_VALIDATE_FLOAT );
					if ( false !== $num ) {
						$safe[ $key ] = $num;
					}
				}
			} elseif ( in_array( $key, $bool_keys, true ) ) {
				$safe[ $key ] = (bool) $val;
			} elseif ( in_array( $key, $string_keys, true ) ) {
				$safe[ $key ] = sanitize_text_field( (string) $val );
			} elseif ( in_array( $key, $device_keys, true ) && is_array( $val ) ) {
				$safe[ $key ] = self::sanitize_options_array( $val );
			}
			// Unknown keys silently dropped.
		}

		return $safe;
	}

	/**
	 * Validate and sanitize a complete rule array for DB storage.
	 *
	 * @param array $raw Raw input (e.g., from $_POST).
	 * @return array{ valid: bool, data: array, errors: array }
	 */
	public static function sanitize_rule( array $raw ): array {
		$errors = [];
		$data   = [];

		// page_id — must be 0 or a valid positive integer.
		$page_id        = absint( $raw['page_id'] ?? 0 );
		$data['page_id'] = $page_id;

		// page_slug — 'all', 'front-page', or sanitize_key slug.
		$slug = sanitize_text_field( $raw['page_slug'] ?? '' );
		if ( '' === $slug && $page_id > 0 ) {
			$page = get_post( $page_id );
			if ( $page instanceof WP_Post ) {
				$slug = $page->post_name;
			}
		}
		$data['page_slug'] = sanitize_key( $slug );

		// target_selector.
		$sel_result = self::validate_selector( $raw['target_selector'] ?? '' );
		if ( 'VALID_SELECTOR' !== $sel_result['status'] ) {
			$errors['target_selector'] = $sel_result['error'];
		}
		$data['target_selector'] = $sel_result['selector'];

		// target_type.
		$allowed_types        = [ 'id', 'class', 'selector', 'data-attr', 'element' ];
		$type                 = sanitize_key( $raw['target_type'] ?? 'selector' );
		$data['target_type']  = in_array( $type, $allowed_types, true ) ? $type : 'selector';

		// animation_names.
		$data['animation_names'] = implode( ',', self::validate_animation_names( $raw['animation_names'] ?? '' ) );

		// style_classes — space-separated CSS class names.
		$classes_raw          = sanitize_text_field( $raw['style_classes'] ?? '' );
		$classes_arr          = array_filter( array_map( 'sanitize_html_class', explode( ' ', $classes_raw ) ) );
		$data['style_classes'] = implode( ' ', $classes_arr );

		// animation_options.
		$opts = self::validate_animation_options( $raw['animation_options'] ?? [] );
		$data['animation_options'] = $opts ? wp_json_encode( $opts ) : null;

		// sort_order.
		$data['sort_order'] = max( 0, min( 9999, absint( $raw['sort_order'] ?? 10 ) ) );

		// enabled.
		$data['enabled'] = isset( $raw['enabled'] ) ? ( (bool) $raw['enabled'] ? 1 : 0 ) : 1;

		// device modes.
		$valid_modes = [ 'full', 'reduced', 'disabled' ];
		foreach ( [ 'desktop_mode', 'tablet_mode', 'mobile_mode' ] as $col ) {
			$mode        = sanitize_key( $raw[ $col ] ?? '' );
			$data[ $col ] = in_array( $mode, $valid_modes, true ) ? $mode : self::default_mode( $col );
		}

		// created_by / updated_by.
		$uid                   = get_current_user_id();
		$data['created_by']    = $uid;
		$data['updated_by']    = $uid;

		return [
			'valid'  => empty( $errors ),
			'data'   => $data,
			'errors' => $errors,
		];
	}

	/**
	 * Validate an entire import JSON payload.
	 *
	 * @param string $json Raw JSON string from file upload or textarea.
	 * @return array{ valid: bool, rules: array, errors: array }
	 */
	public static function validate_import_json( string $json ): array {
		$decoded = json_decode( $json, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return [ 'valid' => false, 'rules' => [], 'errors' => [ __( 'Invalid JSON: ', 'ngt-3d-scroll' ) . json_last_error_msg() ] ];
		}

		if ( ! isset( $decoded['3DScrollPages'] ) || ! is_array( $decoded['3DScrollPages'] ) ) {
			return [ 'valid' => false, 'rules' => [], 'errors' => [ __( 'JSON must contain a "3DScrollPages" array.', 'ngt-3d-scroll' ) ] ];
		}

		$valid_rules = [];
		$errors      = [];

		foreach ( $decoded['3DScrollPages'] as $idx => $item ) {
			if ( ! is_array( $item ) ) {
				$errors[] = sprintf( __( 'Row %d: not an object.', 'ngt-3d-scroll' ), $idx + 1 );
				continue;
			}

			// Map the simple canonical JSON model to internal fields.
			$mapped = [
				'page_slug'         => $item['Id'] ?? '',
				'target_selector'   => $item['target'] ?? '',
				'animation_names'   => $item['style'] ?? ( $item['animations'] ?? '' ),
				'animation_options' => $item['options'] ?? [],
				'enabled'           => $item['enabled'] ?? true,
				'sort_order'        => $item['sort'] ?? 10,
				'target_type'       => $item['targetType'] ?? 'selector',
				'desktop_mode'      => $item['desktop'] ?? 'full',
				'tablet_mode'       => $item['tablet'] ?? 'reduced',
				'mobile_mode'       => $item['mobile'] ?? 'reduced',
			];

			// Try to resolve page_id from slug.
			if ( '' !== $mapped['page_slug'] ) {
				if ( 'home' === $mapped['page_slug'] || 'front-page' === $mapped['page_slug'] ) {
					$mapped['page_id'] = (int) get_option( 'page_on_front', 0 );
				} else {
					$page = get_page_by_path( $mapped['page_slug'], OBJECT, 'page' );
					$mapped['page_id'] = $page ? (int) $page->ID : 0;
				}
			}

			$result = self::sanitize_rule( $mapped );

			if ( ! $result['valid'] ) {
				foreach ( $result['errors'] as $field => $msg ) {
					$errors[] = sprintf( __( 'Row %1$d / %2$s: %3$s', 'ngt-3d-scroll' ), $idx + 1, $field, $msg );
				}
				continue;
			}

			$valid_rules[] = $result['data'];
		}

		return [
			'valid'  => empty( $errors ) || count( $valid_rules ) > 0,
			'rules'  => $valid_rules,
			'errors' => $errors,
		];
	}

	/**
	 * @param string $col Column name.
	 * @return string
	 */
	private static function default_mode( string $col ): string {
		return match ( $col ) {
			'tablet_mode' => 'reduced',
			'mobile_mode' => 'reduced',
			default       => 'full',
		};
	}
}
