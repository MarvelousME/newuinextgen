<?php
/**
 * Shortcode integrations for NGT UI library.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Shortcodes' ) ) {
	/**
	 * Registers [ngt_ui] and dedicated aliases.
	 */
	class NGT_UI_Shortcodes {

		/**
		 * Hook registrations.
		 */
		public static function init(): void {
			add_shortcode( 'ngt_ui', array( __CLASS__, 'render_generic' ) );
			add_shortcode( 'ngt_magic_card', array( __CLASS__, 'render_magic_card' ) );
			add_shortcode( 'ngt_border_beam', array( __CLASS__, 'render_border_beam' ) );
			add_shortcode( 'ngt_marquee', array( __CLASS__, 'render_marquee' ) );
			add_shortcode( 'ngt_income_calculator', array( __CLASS__, 'render_income_calculator' ) );

			// Auto aliases: [ngt_aurora_text], [ngt_shimmer_button], …
			foreach ( NGT_UI_Registry::all() as $component ) {
				$slug = $component->get_name();
				if ( in_array( $slug, array( 'magic-card', 'border-beam', 'marquee', 'income-calculator' ), true ) ) {
					continue;
				}
				$tag = 'ngt_' . str_replace( '-', '_', $slug );
				add_shortcode( $tag, array( __CLASS__, 'render_alias' ) );
			}
		}

		/**
		 * Alias shortcode callback — tag name maps to component slug.
		 *
		 * @param array<string, mixed>|string $atts    Attributes.
		 * @param string|null                 $content Inner content.
		 * @param string                      $tag     Shortcode tag.
		 */
		public static function render_alias( $atts = array(), $content = null, string $tag = '' ): string {
			$atts = is_array( $atts ) ? $atts : array();
			$name = sanitize_key( str_replace( '_', '-', preg_replace( '/^ngt_/', '', $tag ) ?: '' ) );
			if ( '' === $name ) {
				return '';
			}
			if ( $content ) {
				$atts['content'] = do_shortcode( $content );
			}
			return NGT_UI_Renderer::render( $name, $atts, array( 'content' => $atts['content'] ?? '' ) );
		}

		/**
		 * @param array<string, mixed>|string $atts Attributes.
		 * @param string|null                 $content Inner content.
		 */
		public static function render_generic( $atts = array(), $content = null ): string {
			$atts = is_array( $atts ) ? $atts : array();
			$name = sanitize_key( (string) ( $atts['component'] ?? '' ) );
			if ( '' === $name ) {
				return '';
			}
			unset( $atts['component'] );
			return NGT_UI_Renderer::render(
				$name,
				$atts,
				array( 'content' => $content ? do_shortcode( $content ) : '' )
			);
		}

		/**
		 * @param array<string, mixed>|string $atts Attributes.
		 * @param string|null                 $content Content.
		 */
		public static function render_magic_card( $atts = array(), $content = null ): string {
			$atts = shortcode_atts(
				array(
					'title'            => '',
					'content'          => '',
					'mode'             => 'gradient',
					'gradient_size'    => 200,
					'gradient_from'    => '#059669',
					'gradient_to'      => '#FF9F0A',
					'gradient_color'   => '#262626',
					'gradient_opacity' => 0.8,
					'class'            => '',
				),
				is_array( $atts ) ? $atts : array(),
				'ngt_magic_card'
			);
			if ( $content ) {
				$atts['content'] = do_shortcode( $content );
			}
			return NGT_UI_Renderer::render( 'magic-card', $atts );
		}

		/**
		 * @param array<string, mixed>|string $atts Attributes.
		 * @param string|null                 $content Content.
		 */
		public static function render_border_beam( $atts = array(), $content = null ): string {
			$atts = shortcode_atts(
				array(
					'content'      => '',
					'size'         => 50,
					'duration'     => 6,
					'delay'        => 0,
					'color_from'   => '#ffaa40',
					'color_to'     => '#9c40ff',
					'reverse'      => false,
					'border_width' => 1,
					'class'        => '',
				),
				is_array( $atts ) ? $atts : array(),
				'ngt_border_beam'
			);
			$atts['reverse'] = filter_var( $atts['reverse'], FILTER_VALIDATE_BOOLEAN );
			if ( $content ) {
				$atts['content'] = do_shortcode( $content );
			}
			return NGT_UI_Renderer::render( 'border-beam', $atts );
		}

		/**
		 * @param array<string, mixed>|string $atts Attributes.
		 * @param string|null                 $content Content.
		 */
		public static function render_marquee( $atts = array(), $content = null ): string {
			$atts = shortcode_atts(
				array(
					'items'          => '',
					'content'        => '',
					'reverse'        => false,
					'pause_on_hover' => true,
					'vertical'       => false,
					'repeat'         => 4,
					'duration'       => 40,
					'gap'            => '1rem',
					'class'          => '',
				),
				is_array( $atts ) ? $atts : array(),
				'ngt_marquee'
			);
			$atts['reverse']        = filter_var( $atts['reverse'], FILTER_VALIDATE_BOOLEAN );
			$atts['pause_on_hover'] = filter_var( $atts['pause_on_hover'], FILTER_VALIDATE_BOOLEAN );
			$atts['vertical']       = filter_var( $atts['vertical'], FILTER_VALIDATE_BOOLEAN );
			if ( $content ) {
				$atts['content'] = do_shortcode( $content );
			}
			return NGT_UI_Renderer::render( 'marquee', $atts );
		}

		/**
		 * [ngt_income_calculator] — tutor earnings estimator (Become a Tutor page).
		 *
		 * @param array<string, mixed>|string $atts Attributes.
		 */
		public static function render_income_calculator( $atts = array() ): string {
			$atts = shortcode_atts(
				array(
					'title'           => '',
					'hours_per_week'  => 10,
					'hourly_rate'     => 350,
					'platform_fee'    => 15,
					'weeks_per_month' => 4.33,
					'currency'        => 'ZAR',
					'currency_symbol' => 'R',
					'class'           => '',
				),
				is_array( $atts ) ? $atts : array(),
				'ngt_income_calculator'
			);
			return NGT_UI_Renderer::render( 'income-calculator', $atts );
		}
	}
}
