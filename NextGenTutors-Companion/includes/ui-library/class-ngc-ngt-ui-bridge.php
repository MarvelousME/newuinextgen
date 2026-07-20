<?php
/**
 * Option-centric bridge between theme CMS UI (NGC_UI_Library) and Magic UI (NGT_UI).
 *
 * @package NextGenCompanion
 */

declare(strict_types=1);

if ( ! class_exists( 'NGC_NGT_UI_Bridge' ) ) {
	/**
	 * Routes ngc_ui_render_component to NGT_UI_Renderer when appropriate.
	 */
	class NGC_NGT_UI_Bridge {

		public const OPTION_MODE = 'ngc_ui_library_mode';

		public const MODE_BOTH       = 'both';
		public const MODE_THEME_ONLY = 'theme_only';
		public const MODE_MAGIC_ONLY = 'magic_only';

		/**
		 * Hook after Magic UI library boots.
		 */
		public static function init(): void {
			add_action( 'ngt_ui_library_booted', array( __CLASS__, 'register_hooks' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		}

		/**
		 * @return string both|theme_only|magic_only
		 */
		public static function get_mode(): string {
			$mode = (string) get_option( self::OPTION_MODE, self::MODE_BOTH );
			$modes = array( self::MODE_BOTH, self::MODE_THEME_ONLY, self::MODE_MAGIC_ONLY );
			return in_array( $mode, $modes, true ) ? $mode : self::MODE_BOTH;
		}

		/**
		 * Register render bridge and shortcode extensions.
		 */
		public static function register_hooks(): void {
			add_filter( 'ngc_ui_render_component', array( __CLASS__, 'maybe_render_magic' ), 8, 2 );
			add_filter( 'ngc_ui_render_component', array( __CLASS__, 'block_theme_when_magic_only' ), 9, 2 );
		}

		/**
		 * @param string               $html    Existing HTML.
		 * @param array<string, mixed> $context Context.
		 */
		public static function maybe_render_magic( $html, $context ): string {
			if ( '' !== $html ) {
				return $html;
			}
			$mode = self::get_mode();
			if ( self::MODE_THEME_ONLY === $mode ) {
				return $html;
			}
			if ( ! class_exists( 'NGT_UI_Registry' ) || ! class_exists( 'NGT_UI_Renderer' ) ) {
				return $html;
			}

			$slug = sanitize_key( (string) ( $context['magic_slug'] ?? $context['slug'] ?? '' ) );
			if ( '' === $slug ) {
				return $html;
			}

			// Theme CMS slugs take precedence in "both" mode unless explicitly routed via magic_slug.
			if ( self::MODE_BOTH === $mode && empty( $context['magic_slug'] ) ) {
				$theme_def = class_exists( 'NGC_UI_Component_Registry' )
					? NGC_UI_Component_Registry::get( $slug )
					: null;
				$magic_def = NGT_UI_Registry::get( $slug );
				if ( $theme_def && ! $magic_def ) {
					return $html;
				}
			}

			if ( ! NGT_UI_Registry::get( $slug ) ) {
				return $html;
			}

			$atts = is_array( $context['atts'] ?? null ) ? $context['atts'] : array();
			unset( $atts['slug'], $atts['component'] );
			return NGT_UI_Renderer::render( $slug, $atts, $context );
		}

		/**
		 * When magic-only, prevent theme fallback HTML for unknown slugs.
		 *
		 * @param string               $html    Existing HTML.
		 * @param array<string, mixed> $context Context.
		 */
		public static function block_theme_when_magic_only( $html, $context ): string {
			if ( self::MODE_MAGIC_ONLY !== self::get_mode() ) {
				return $html;
			}
			$slug = sanitize_key( (string) ( $context['slug'] ?? '' ) );
			if ( '' === $slug || '' !== $html ) {
				return $html;
			}
			if ( class_exists( 'NGT_UI_Registry' ) && NGT_UI_Registry::get( $slug ) ) {
				return $html;
			}
			return '<!-- ng-ui: blocked in magic_only mode -->';
		}

		/**
		 * Settings API registration.
		 */
		public static function register_settings(): void {
			register_setting(
				'ngc_ui_library',
				self::OPTION_MODE,
				array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ),
					'default'           => self::MODE_BOTH,
				)
			);
		}

		/**
		 * @param mixed $value Raw option.
		 */
		public static function sanitize_mode( $value ): string {
			$value = sanitize_key( (string) $value );
			$modes = array( self::MODE_BOTH, self::MODE_THEME_ONLY, self::MODE_MAGIC_ONLY );
			return in_array( $value, $modes, true ) ? $value : self::MODE_BOTH;
		}

		/**
		 * Render settings field for admin screens.
		 */
		public static function render_mode_field(): void {
			$mode = self::get_mode();
			?>
			<select name="<?php echo esc_attr( self::OPTION_MODE ); ?>" id="ngc_ui_library_mode">
				<option value="<?php echo esc_attr( self::MODE_BOTH ); ?>" <?php selected( $mode, self::MODE_BOTH ); ?>>
					<?php esc_html_e( 'Both — theme CMS + Magic UI (recommended)', 'nextgencompanion' ); ?>
				</option>
				<option value="<?php echo esc_attr( self::MODE_THEME_ONLY ); ?>" <?php selected( $mode, self::MODE_THEME_ONLY ); ?>>
					<?php esc_html_e( 'Theme CMS only (NGC_UI_Library)', 'nextgencompanion' ); ?>
				</option>
				<option value="<?php echo esc_attr( self::MODE_MAGIC_ONLY ); ?>" <?php selected( $mode, self::MODE_MAGIC_ONLY ); ?>>
					<?php esc_html_e( 'Magic UI only (NGT_UI)', 'nextgencompanion' ); ?>
				</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Controls how [ng_ui_component] resolves slugs. [ngt_ui] and [ngt_income_calculator] always use Magic UI.', 'nextgencompanion' ); ?>
			</p>
			<?php
		}
	}
}
