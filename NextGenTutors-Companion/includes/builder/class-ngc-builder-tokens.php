<?php
/**
 * Global design tokens overlay for Visual Builder.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merges option overlay onto ui-library tokens.json baseline.
 */
class NGC_Builder_Tokens {

	const OPTION_KEY = 'ngc_builder_tokens';

	/**
	 * Baseline tokens from UI library JSON when present.
	 *
	 * @return array<string, mixed>
	 */
	public static function baseline() {
		$candidates = [
			trailingslashit( dirname( rtrim( NGC_PLUGIN_DIR, '/\\' ) ) ) . 'ui-library/tokens/tokens.json',
			trailingslashit( NGC_PLUGIN_DIR ) . 'ui-library/tokens/tokens.json',
		];
		if ( defined( 'BI_DIR' ) ) {
			$candidates[] = trailingslashit( dirname( rtrim( BI_DIR, '/\\' ) ) ) . 'ui-library/tokens/tokens.json';
		}

		foreach ( array_unique( $candidates ) as $path ) {
			$real = wp_normalize_path( $path );
			if ( is_readable( $real ) ) {
				$data = json_decode( (string) file_get_contents( $real ), true );
				if ( is_array( $data ) ) {
					return $data;
				}
			}
		}

		return [
			'colors'   => [
				'primary'   => '#0F1A2F',
				'secondary' => '#059669',
				'accent'    => '#28c7f7',
				'surface'   => '#F8FAFC',
				'text'      => '#1E293B',
			],
			'spacing'  => [
				'1'  => '0.25rem',
				'2'  => '0.5rem',
				'3'  => '0.75rem',
				'4'  => '1rem',
				'5'  => '1.5rem',
				'6'  => '2rem',
				'8'  => '2.5rem',
				'12' => '3rem',
			],
			'radii'    => [ 'sm' => '8px', 'md' => '14px', 'lg' => '20px' ],
			'motion'   => [ 'fast' => '180ms', 'normal' => '320ms', 'easing' => 'cubic-bezier(0.22, 1, 0.36, 1)' ],
			'typography' => [
				'fontFamily'    => 'Space Grotesk, system-ui, sans-serif',
				'fontSize'      => [ 'sm' => '0.875rem', 'md' => '1rem', 'lg' => '1.25rem', 'xl' => '2rem' ],
				'lineHeight'    => [ 'tight' => '1.2', 'normal' => '1.5', 'loose' => '1.75' ],
				'letterSpacing' => [ 'normal' => '0', 'wide' => '0.04em' ],
				'fontWeight'    => [ 'regular' => '400', 'medium' => '500', 'bold' => '700' ],
			],
			'shadows'  => [
				'sm' => '0 1px 2px rgba(7,23,47,0.08)',
				'md' => '0 8px 24px rgba(7,23,47,0.12)',
				'lg' => '0 16px 40px rgba(7,23,47,0.18)',
			],
			'effects'  => [
				'glass' => [ 'blur' => '12px', 'opacity' => 0.72 ],
				'neuro' => [ 'distance' => '6px', 'blur' => '12px' ],
			],
		];
	}

	/**
	 * Overlay option.
	 *
	 * @return array<string, mixed>
	 */
	public static function overlay() {
		$opt = get_option( self::OPTION_KEY, [] );
		return is_array( $opt ) ? $opt : [];
	}

	/**
	 * Merged tokens.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		return self::deep_merge( self::baseline(), self::overlay() );
	}

	/**
	 * @param array<string, mixed> $overlay Overlay.
	 * @return array<string, mixed>
	 */
	public static function save_overlay( array $overlay ) {
		$clean = self::sanitize( $overlay );
		update_option( self::OPTION_KEY, $clean, false );
		return self::all();
	}

	/**
	 * Emit CSS custom properties.
	 *
	 * @param array<string, mixed>|null $tokens Tokens.
	 * @return string
	 */
	public static function to_css( $tokens = null ) {
		$tokens = is_array( $tokens ) ? $tokens : self::all();
		$lines  = [ ':root{' ];
		self::flatten_css( $tokens, 'ngt', $lines );
		$lines[] = '}';
		return implode( '', $lines );
	}

	/**
	 * Resolve token:path references.
	 *
	 * @param string               $ref    token:colors.primary or raw.
	 * @param array<string, mixed> $tokens Token map.
	 * @return string
	 */
	public static function resolve( $ref, array $tokens = [] ) {
		if ( ! is_string( $ref ) || 0 !== strpos( $ref, 'token:' ) ) {
			return is_scalar( $ref ) ? (string) $ref : '';
		}
		$tokens = $tokens ?: self::all();
		$path   = substr( $ref, 6 );
		$cur    = $tokens;
		foreach ( explode( '.', $path ) as $part ) {
			if ( ! is_array( $cur ) || ! array_key_exists( $part, $cur ) ) {
				return $ref;
			}
			$cur = $cur[ $part ];
		}
		return is_scalar( $cur ) ? (string) $cur : $ref;
	}

	/**
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>
	 */
	private static function sanitize( array $data ) {
		$out = [];
		foreach ( $data as $k => $v ) {
			$key = sanitize_key( (string) $k );
			if ( is_array( $v ) ) {
				$out[ $key ] = self::sanitize( $v );
			} elseif ( is_string( $v ) ) {
				$out[ $key ] = sanitize_text_field( $v );
			} elseif ( is_numeric( $v ) ) {
				$out[ $key ] = 0 + $v;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $base Base.
	 * @param array<string, mixed> $over Overlay.
	 * @return array<string, mixed>
	 */
	private static function deep_merge( array $base, array $over ) {
		foreach ( $over as $k => $v ) {
			if ( is_array( $v ) && isset( $base[ $k ] ) && is_array( $base[ $k ] ) ) {
				$base[ $k ] = self::deep_merge( $base[ $k ], $v );
			} else {
				$base[ $k ] = $v;
			}
		}
		return $base;
	}

	/**
	 * @param mixed                $node  Node.
	 * @param string               $prefix Prefix.
	 * @param array<int, string>   $lines Lines.
	 */
	private static function flatten_css( $node, $prefix, array &$lines ) {
		if ( ! is_array( $node ) ) {
			if ( is_scalar( $node ) ) {
				$lines[] = '--' . $prefix . ':' . $node . ';';
			}
			return;
		}
		foreach ( $node as $k => $v ) {
			self::flatten_css( $v, $prefix . '-' . sanitize_key( (string) $k ), $lines );
		}
	}
}
