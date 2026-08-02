<?php
/**
 * Admin design tokens + Theme Designer persistence.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads CSS custom properties for the unified admin experience.
 */
final class NGC_Admin_Theme {

	public const USER_META   = 'ngt_admin_theme';
	public const GLOBAL_OPT  = 'ngt_admin_theme_global';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_head', [ __CLASS__, 'inject_vars' ], 5 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_tokens' ], 5 );
	}

	/**
	 * Default token map.
	 *
	 * @return array<string, string|int|float>
	 */
	public static function defaults() {
		return [
			'primary'        => '#1d4ed8',
			'secondary'      => '#0f172a',
			'accent'         => '#0891b2',
			'success'        => '#15803d',
			'warning'        => '#b45309',
			'error'          => '#b91c1c',
			'background'     => '#f1f5f9',
			'foreground'     => '#0f172a',
			'sidebar'        => '#0f172a',
			'sidebar_text'   => '#e2e8f0',
			'hover'          => '#1e293b',
			'active'         => '#2563eb',
			'card'           => '#ffffff',
			'button'         => '#1d4ed8',
			'table'          => '#ffffff',
			'form'           => '#ffffff',
			'font_family'    => 'Segoe UI, system-ui, sans-serif',
			'font_size'      => '14',
			'font_weight'    => '400',
			'letter_spacing' => '0',
			'line_height'    => '1.45',
			'sidebar_width'  => '280',
			'card_radius'    => '12',
			'border_radius'  => '8',
			'shadow'         => '0 8px 24px rgba(15,23,42,.08)',
			'spacing'        => '16',
			'density'        => 'comfortable',
			'motion'         => '1',
			'motion_ms'      => '280',
			'glass'          => '0',
			'icon_size'      => '18',
		];
	}

	/**
	 * Sanitize theme payload.
	 *
	 * @param array<string, mixed> $raw Raw.
	 * @return array<string, string>
	 */
	public static function sanitize( array $raw ) {
		$out = [];
		foreach ( self::defaults() as $key => $default ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$val = $raw[ $key ];
			if ( in_array( $key, [ 'font_family', 'shadow', 'density' ], true ) ) {
				$out[ $key ] = sanitize_text_field( (string) $val );
				continue;
			}
			if ( is_string( $val ) && 0 === strpos( $val, '#' ) ) {
				$hex = function_exists( 'sanitize_hex_color' ) ? sanitize_hex_color( $val ) : ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $val ) ? $val : '' );
				if ( $hex ) {
					$out[ $key ] = $hex;
				}
				continue;
			}
			$out[ $key ] = sanitize_text_field( (string) $val );
		}
		return $out;
	}

	/**
	 * Resolved theme for current user (user overlay on global on defaults).
	 *
	 * @param int $user_id User ID.
	 * @return array<string, string|int|float>
	 */
	public static function get( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$theme   = self::defaults();
		$global  = get_option( self::GLOBAL_OPT, [] );
		if ( is_array( $global ) ) {
			$theme = array_merge( $theme, self::sanitize( $global ) );
		}
		if ( $user_id ) {
			$user = get_user_meta( $user_id, self::USER_META, true );
			if ( is_array( $user ) ) {
				$theme = array_merge( $theme, self::sanitize( $user ) );
			}
		}
		return $theme;
	}

	/**
	 * @param array<string, mixed> $theme Theme.
	 * @param string               $scope user|global.
	 * @return array<string, string|int|float>
	 */
	public static function save( array $theme, $scope = 'user' ) {
		$clean = self::sanitize( $theme );
		if ( 'global' === $scope && current_user_can( 'manage_options' ) ) {
			update_option( self::GLOBAL_OPT, $clean, false );
		} else {
			update_user_meta( get_current_user_id(), self::USER_META, $clean );
		}
		return self::get();
	}

	/**
	 * Enqueue token stylesheet on NGT screens.
	 */
	public static function enqueue_tokens() {
		if ( ! class_exists( 'NGC_Admin_Shell' ) || ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		wp_enqueue_style(
			'ngt-admin-tokens',
			NGC_PLUGIN_URL . 'assets/css/admin-tokens.css',
			[],
			NGC_VERSION
		);
	}

	/**
	 * Inject resolved CSS variables.
	 */
	public static function inject_vars() {
		if ( ! class_exists( 'NGC_Admin_Shell' ) || ! NGC_Admin_Shell::is_ngt_screen() ) {
			return;
		}
		$t = self::get();
		$css = ':root{';
		$map = [
			'primary' => '--ngt-admin-primary',
			'secondary' => '--ngt-admin-secondary',
			'accent' => '--ngt-admin-accent',
			'success' => '--ngt-admin-success',
			'warning' => '--ngt-admin-warning',
			'error' => '--ngt-admin-error',
			'background' => '--ngt-admin-bg',
			'foreground' => '--ngt-admin-fg',
			'sidebar' => '--ngt-admin-sidebar',
			'sidebar_text' => '--ngt-admin-sidebar-text',
			'hover' => '--ngt-admin-hover',
			'active' => '--ngt-admin-active',
			'card' => '--ngt-admin-card',
			'button' => '--ngt-admin-button',
			'table' => '--ngt-admin-table',
			'form' => '--ngt-admin-form',
			'font_family' => '--ngt-admin-font',
			'shadow' => '--ngt-admin-shadow',
			'density' => '--ngt-admin-density',
		];
		foreach ( $map as $key => $var ) {
			$css .= $var . ':' . esc_attr( (string) ( $t[ $key ] ?? '' ) ) . ';';
		}
		$css .= '--ngt-admin-font-size:' . esc_attr( (string) $t['font_size'] ) . 'px;';
		$css .= '--ngt-admin-font-weight:' . esc_attr( (string) $t['font_weight'] ) . ';';
		$css .= '--ngt-admin-letter-spacing:' . esc_attr( (string) $t['letter_spacing'] ) . 'px;';
		$css .= '--ngt-admin-line-height:' . esc_attr( (string) $t['line_height'] ) . ';';
		$css .= '--ngt-admin-sidebar-width:' . esc_attr( (string) $t['sidebar_width'] ) . 'px;';
		$css .= '--ngt-admin-card-radius:' . esc_attr( (string) $t['card_radius'] ) . 'px;';
		$css .= '--ngt-admin-radius:' . esc_attr( (string) $t['border_radius'] ) . 'px;';
		$css .= '--ngt-admin-spacing:' . esc_attr( (string) $t['spacing'] ) . 'px;';
		$css .= '--ngt-admin-motion:' . esc_attr( (string) $t['motion'] ) . ';';
		$css .= '--ngt-admin-motion-ms:' . esc_attr( (string) $t['motion_ms'] ) . 'ms;';
		$css .= '--ngt-admin-icon-size:' . esc_attr( (string) $t['icon_size'] ) . 'px;';
		$css .= '}';
		echo '<style id="ngt-admin-theme-vars">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Theme Designer admin screen.
	 */
	public static function render_designer() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$theme = self::get();
		NGC_Admin_Layout::render_page(
			[
				'title'   => __( 'Theme Designer', 'nextgencompanion' ),
				'summary' => __( 'Configure administration typography, colors, layout, and motion. All plugins inherit these tokens.', 'nextgencompanion' ),
				'content' => static function () use ( $theme ) {
					echo '<div class="ngt-theme-designer" data-testid="ngt-theme-designer" id="ngt-theme-designer">';
					echo '<div class="ngt-theme-designer__controls">';
					$colors = [ 'primary', 'secondary', 'accent', 'success', 'warning', 'error', 'background', 'foreground', 'sidebar', 'sidebar_text', 'hover', 'active', 'card', 'button' ];
					foreach ( $colors as $key ) {
						printf(
							'<label class="ngt-theme-field">%1$s <input type="color" data-theme-key="%2$s" value="%3$s" /></label>',
							esc_html( ucwords( str_replace( '_', ' ', $key ) ) ),
							esc_attr( $key ),
							esc_attr( (string) ( $theme[ $key ] ?? '#000000' ) )
						);
					}
					printf(
						'<label class="ngt-theme-field">%1$s <input type="number" min="0" max="1" step="0.1" data-theme-key="motion" value="%2$s" /></label>',
						esc_html__( 'Motion intensity', 'nextgencompanion' ),
						esc_attr( (string) $theme['motion'] )
					);
					printf(
						'<label class="ngt-theme-field">%1$s <input type="number" min="0" max="24" data-theme-key="border_radius" value="%2$s" /></label>',
						esc_html__( 'Border radius', 'nextgencompanion' ),
						esc_attr( (string) $theme['border_radius'] )
					);
					printf(
						'<label class="ngt-theme-field">%1$s <select data-theme-key="density"><option value="comfortable"%2$s>comfortable</option><option value="compact"%3$s>compact</option></select></label>',
						esc_html__( 'Density', 'nextgencompanion' ),
						selected( $theme['density'], 'comfortable', false ),
						selected( $theme['density'], 'compact', false )
					);
					echo '<p><button type="button" class="button button-primary" id="ngt-theme-save" data-testid="ngt-theme-save">' . esc_html__( 'Save theme', 'nextgencompanion' ) . '</button> ';
					echo '<button type="button" class="button" id="ngt-theme-reset">' . esc_html__( 'Reset defaults', 'nextgencompanion' ) . '</button></p>';
					echo '</div>';
					echo '<div class="ngt-theme-designer__preview ngt-admin-card" data-ngt-motion>';
					echo '<h3>' . esc_html__( 'Live preview', 'nextgencompanion' ) . '</h3>';
					echo '<p>' . esc_html__( 'Cards, buttons, and tables inherit tokens automatically.', 'nextgencompanion' ) . '</p>';
					echo '<button type="button" class="button button-primary ngt-admin-btn">' . esc_html__( 'Primary action', 'nextgencompanion' ) . '</button> ';
					echo '<span class="ngt-admin-badge">' . esc_html__( 'Badge', 'nextgencompanion' ) . '</span>';
					echo '</div></div>';
				},
			]
		);
	}
}
