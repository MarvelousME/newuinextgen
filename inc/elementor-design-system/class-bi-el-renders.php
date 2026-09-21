<?php
/**
 * Presentation renders — wrap theme/Companion helpers, never domain logic.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget body renderer.
 */
class BI_EL_Renders {

	/**
	 * Dispatch.
	 *
	 * @param string               $id       Widget id.
	 * @param array<string, mixed> $settings Settings.
	 * @param array<string, mixed> $ctx      Context.
	 */
	public static function render( $id, $settings, $ctx = [] ) {
		$method = 'render_' . str_replace( '-', '_', $id );
		if ( 'render_3d_tilt_card' === $method ) {
			$method = 'render_tilt_card';
		}
		if ( 'render_3d_scene' === $method ) {
			$method = 'render_scene';
		}
		if ( method_exists( self::class, $method ) ) {
			self::$method( $settings, $ctx );
			return;
		}
		echo '<p class="bi-el__empty">' . esc_html__( 'Unknown NextGen widget.', 'beyondinfinity' ) . '</p>';
	}

	/**
	 * Heading helper.
	 *
	 * @param array  $settings Settings.
	 * @param string $fallback Fallback.
	 * @param string $tag      Tag.
	 */
	private static function title( $settings, $fallback, $tag = 'h2' ) {
		$text = trim( (string) ( $settings['heading'] ?? '' ) );
		if ( '' === $text ) {
			$text = $fallback;
		}
		if ( '' === $text ) {
			return;
		}
		$tag = in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'p' ], true ) ? $tag : 'h2';
		echo '<' . $tag . ' class="bi-el__title">' . esc_html( $text ) . '</' . $tag . '>';
	}

	/**
	 * Subheading.
	 *
	 * @param array $settings Settings.
	 */
	private static function subtitle( $settings ) {
		$text = trim( (string) ( $settings['subheading'] ?? '' ) );
		if ( '' === $text ) {
			return;
		}
		echo '<p class="bi-el__subtitle">' . esc_html( $text ) . '</p>';
	}

	/**
	 * NextGen Hero.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_hero( $settings, $ctx ) {
		$page_key = sanitize_key( $settings['page_key'] ?? 'home' );
		$cms      = BI_EL_Data::hero_cms( $page_key );
		$heading  = $settings['heading'] ?: ( $cms['title'] ?? $cms['heading'] ?? '' );
		$sub      = $settings['subheading'] ?: ( $cms['subtitle'] ?? $cms['text'] ?? '' );
		$cta      = trim( (string) ( $settings['cta_text'] ?? '' ) );
		$url      = esc_url( $settings['cta_url']['url'] ?? ( $settings['cta_link'] ?? '' ) );
		$tag      = sanitize_key( $settings['heading_tag'] ?? 'h1' );
		if ( ! in_array( $tag, [ 'h1', 'h2', 'h3' ], true ) ) {
			$tag = 'h1';
		}

		echo '<section class="bi-el__hero ngi-hero" aria-label="' . esc_attr__( 'Hero', 'beyondinfinity' ) . '">';
		if ( $heading ) {
			echo '<' . esc_attr( $tag ) . ' class="bi-el__title">' . esc_html( $heading ) . '</' . esc_attr( $tag ) . '>';
		}
		if ( $sub ) {
			echo '<p class="bi-el__subtitle">' . esc_html( $sub ) . '</p>';
		}
		if ( $cta && $url ) {
			echo '<p class="bi-el__actions"><a class="bi-el__btn" href="' . esc_url( $url ) . '">' . esc_html( $cta ) . '</a></p>';
		}
		if ( function_exists( 'bi_hero_search_form' ) && ! empty( $settings['show_search'] ) && 'yes' === $settings['show_search'] ) {
			bi_hero_search_form();
		}
		echo '</section>';
	}

	/**
	 * Find tutor — Companion form / marketplace.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_find_tutor( $settings, $ctx ) {
		self::title( $settings, __( 'Find a tutor', 'beyondinfinity' ), 'h2' );
		self::subtitle( $settings );
		$mode = sanitize_key( $settings['find_mode'] ?? 'form' );
		if ( 'marketplace' === $mode && shortcode_exists( 'ngc_tutor_marketplace' ) ) {
			echo do_shortcode( '[ngc_tutor_marketplace]' );
			return;
		}
		if ( 'match' === $mode && shortcode_exists( 'ngc_match_tutor' ) ) {
			echo do_shortcode( '[ngc_match_tutor]' );
			return;
		}
		if ( shortcode_exists( 'ngc_find_tutor_form' ) ) {
			echo do_shortcode( '[ngc_find_tutor_form]' );
			return;
		}
		if ( function_exists( 'bi_ngc_form_find_tutor' ) ) {
			bi_ngc_form_find_tutor();
			return;
		}
		echo BI_EL_Data::empty_state( __( 'Activate NextGen Companion to load the find-a-tutor form.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Tutor grid.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_tutor_grid( $settings, $ctx ) {
		self::title( $settings, __( 'Meet our tutors', 'beyondinfinity' ) );
		self::subtitle( $settings );
		$limit   = max( 1, min( 24, (int) ( $settings['limit'] ?? 6 ) ) );
		$subject = sanitize_text_field( (string) ( $settings['subject'] ?? '' ) );
		$tutors  = BI_EL_Data::tutors( [ 'limit' => $limit, 'subject' => $subject ] );
		if ( ! $tutors ) {
			echo BI_EL_Data::empty_state( __( 'No tutors available. Companion marketplace data is required.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		echo '<div class="bi-el__grid bi-el__grid--tutors" data-ngt-stagger-host="1">';
		foreach ( $tutors as $tutor ) {
			self::tutor_card_item( $tutor );
		}
		echo '</div>';
	}

	/**
	 * Single tutor card (first live tutor, or directory card).
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_tutor_card( $settings, $ctx ) {
		$tutors = BI_EL_Data::tutors( [ 'limit' => 1, 'subject' => sanitize_text_field( (string) ( $settings['subject'] ?? '' ) ) ] );
		if ( ! $tutors ) {
			echo BI_EL_Data::empty_state( __( 'No tutor card data. Companion must be active.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		self::tutor_card_item( $tutors[0] );
	}

	/**
	 * Card markup via theme helper.
	 *
	 * @param mixed $tutor Tutor row.
	 */
	private static function tutor_card_item( $tutor ) {
		if ( is_array( $tutor ) && function_exists( 'bi_render_tutor_carousel_card' ) ) {
			$mapped = $tutor;
			if ( isset( $tutor['name'] ) && ! isset( $tutor['rate'] ) ) {
				$mapped = [
					'name'     => $tutor['name'] ?? '',
					'avatar'   => $tutor['photo'] ?? ( $tutor['avatar'] ?? '' ),
					'subjects' => $tutor['subjects'] ?? [],
					'location' => $tutor['location'] ?? '',
					'bio'      => $tutor['bio'] ?? '',
					'rating'   => $tutor['rating'] ?? 0,
					'reviews'  => $tutor['reviews'] ?? 0,
					'rate'     => $tutor['price'] ?? ( $tutor['rate'] ?? 0 ),
					'vetted'   => ! empty( $tutor['vetted'] ),
					'id'       => $tutor['id'] ?? 0,
				];
			}
			bi_render_tutor_carousel_card( $mapped );
			return;
		}
		if ( function_exists( 'ng_ui_component' ) ) {
			echo apply_filters( 'ngc_ui_render_component', '', [ 'slug' => 'tutor-card', 'items' => [ $tutor ] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Subject grid.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_subject_grid( $settings, $ctx ) {
		self::title( $settings, __( 'Subjects', 'beyondinfinity' ) );
		self::subtitle( $settings );
		$items = BI_EL_Data::subjects( max( 1, min( 24, (int) ( $settings['limit'] ?? 8 ) ) ) );
		if ( ! $items ) {
			echo BI_EL_Data::empty_state( __( 'No subjects published.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		echo '<ul class="bi-el__grid bi-el__grid--subjects">';
		foreach ( $items as $row ) {
			self::subject_card_item( $row );
		}
		echo '</ul>';
	}

	/**
	 * Subject card.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_subject_card( $settings, $ctx ) {
		$items = BI_EL_Data::subjects( 1 );
		if ( ! $items ) {
			echo BI_EL_Data::empty_state( __( 'No subject data.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		echo '<ul class="bi-el__grid bi-el__grid--subjects">';
		self::subject_card_item( $items[0] );
		echo '</ul>';
	}

	/**
	 * @param mixed $row Subject.
	 */
	private static function subject_card_item( $row ) {
		if ( ! is_array( $row ) ) {
			return;
		}
		$slug  = sanitize_key( $row['slug'] ?? '' );
		$title = $row['name'] ?? ( $row['title'] ?? $slug );
		$url   = $slug ? add_query_arg( 'subject', $slug, home_url( '/find-a-tutor/' ) ) : home_url( '/find-a-tutor/' );
		echo '<li class="bi-el__card bi-el__subject">';
		echo '<a class="bi-el__subject-link" href="' . esc_url( $url ) . '">';
		echo '<span class="bi-el__title">' . esc_html( (string) $title ) . '</span>';
		echo '</a></li>';
	}

	/**
	 * Filmstrip — existing plugin shortcode.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_tutor_filmstrip( $settings, $ctx ) {
		$source = sanitize_key( $settings['film_source'] ?? 'tutors' );
		$limit  = max( 1, min( 16, (int) ( $settings['limit'] ?? 8 ) ) );
		if ( shortcode_exists( 'ngt_filmstrip' ) ) {
			echo do_shortcode(
				sprintf(
					'[ngt_filmstrip source="%s" limit="%d" title="%s" subtitle="%s"]',
					esc_attr( $source ),
					$limit,
					esc_attr( (string) ( $settings['heading'] ?? '' ) ),
					esc_attr( (string) ( $settings['subheading'] ?? '' ) )
				)
			);
			return;
		}
		self::render_tutor_grid( $settings, $ctx );
	}

	/**
	 * Testimonials from review provider.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_testimonials( $settings, $ctx ) {
		self::title( $settings, __( 'What families say', 'beyondinfinity' ) );
		self::subtitle( $settings );
		$items = BI_EL_Data::reviews( max( 1, min( 12, (int) ( $settings['limit'] ?? 4 ) ) ) );
		if ( ! $items ) {
			echo BI_EL_Data::empty_state( __( 'No published reviews yet.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		echo '<ul class="bi-el__grid bi-el__quotes">';
		foreach ( $items as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$quote = $row['content'] ?? ( $row['quote'] ?? ( $row['text'] ?? '' ) );
			$name  = $row['name'] ?? ( $row['author'] ?? '' );
			echo '<li class="bi-el__card"><blockquote><p>' . esc_html( (string) $quote ) . '</p>';
			if ( $name ) {
				echo '<footer>' . esc_html( (string) $name ) . '</footer>';
			}
			echo '</blockquote></li>';
		}
		echo '</ul>';
	}

	/**
	 * Pricing tiers.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_pricing( $settings, $ctx ) {
		self::title( $settings, __( 'Pricing', 'beyondinfinity' ) );
		self::subtitle( $settings );
		$items = BI_EL_Data::pricing( max( 1, min( 6, (int) ( $settings['limit'] ?? 3 ) ) ) );
		if ( ! $items ) {
			echo BI_EL_Data::empty_state( __( 'No pricing tiers configured in Companion.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		echo '<div class="bi-el__grid bi-el__grid--pricing">';
		foreach ( $items as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = $row['title'] ?? ( $row['name'] ?? '' );
			$price = $row['price'] ?? ( $row['amount'] ?? '' );
			echo '<article class="bi-el__card"><h3 class="bi-el__title">' . esc_html( (string) $title ) . '</h3>';
			if ( '' !== (string) $price ) {
				echo '<p class="bi-el__price">' . esc_html( (string) $price ) . '</p>';
			}
			if ( ! empty( $row['description'] ) ) {
				echo '<p>' . esc_html( (string) $row['description'] ) . '</p>';
			}
			echo '</article>';
		}
		echo '</div>';
	}

	/**
	 * Stats band.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_stats( $settings, $ctx ) {
		self::title( $settings, '' );
		$items = BI_EL_Data::stats();
		if ( ! $items ) {
			echo BI_EL_Data::empty_state( __( 'No live platform metrics yet.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		echo '<dl class="bi-el__stats">';
		foreach ( $items as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = $row['label'] ?? ( $row['title'] ?? '' );
			$value = $row['value'] ?? ( $row['stat'] ?? '' );
			echo '<div class="bi-el__stat"><dt>' . esc_html( (string) $label ) . '</dt><dd>' . esc_html( (string) $value ) . '</dd></div>';
		}
		echo '</dl>';
	}

	/**
	 * FAQ from Section CMS + theme list.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_faq( $settings, $ctx ) {
		self::title( $settings, __( 'Questions', 'beyondinfinity' ) );
		self::subtitle( $settings );
		$items = BI_EL_Data::faq();
		if ( ! $items ) {
			echo BI_EL_Data::empty_state( __( 'No FAQ items in Companion Section CMS.', 'beyondinfinity' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		if ( function_exists( 'bi_faq_list' ) ) {
			bi_faq_list( $items );
			return;
		}
		echo '<div class="bi-el__faq">';
		foreach ( $items as $item ) {
			$q = is_array( $item ) ? ( $item['q'] ?? '' ) : '';
			$a = is_array( $item ) ? ( $item['a'] ?? '' ) : '';
			echo '<details><summary>' . esc_html( (string) $q ) . '</summary><p>' . esc_html( (string) $a ) . '</p></details>';
		}
		echo '</div>';
	}

	/**
	 * Booking CTA — uses existing booking drawer / find-tutor URL.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_booking_cta( $settings, $ctx ) {
		$label = trim( (string) ( $settings['cta_text'] ?? '' ) );
		if ( '' === $label ) {
			$label = __( 'Book a session', 'beyondinfinity' );
		}
		$url = '';
		if ( ! empty( $settings['cta_url']['url'] ) ) {
			$url = $settings['cta_url']['url'];
		} elseif ( function_exists( 'home_url' ) ) {
			$url = home_url( '/find-a-tutor/' );
		}
		echo '<div class="bi-el__cta">';
		self::title( $settings, '' );
		self::subtitle( $settings );
		echo '<a class="bi-el__btn" data-bi-booking-drawer href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		echo '</div>';
	}

	/**
	 * Animated heading.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_animated_heading( $settings, $ctx ) {
		$tag = sanitize_key( $settings['heading_tag'] ?? 'h2' );
		if ( ! in_array( $tag, [ 'h1', 'h2', 'h3', 'h4' ], true ) ) {
			$tag = 'h2';
		}
		$text = (string) ( $settings['heading'] ?? '' );
		echo '<' . esc_attr( $tag ) . ' class="bi-el__title ngi-kinetic-text" data-motion-text-fallback="1">' . esc_html( $text ) . '</' . esc_attr( $tag ) . '>';
		self::subtitle( $settings );
	}

	/**
	 * GSAP text reveal — markup only; NGT3D text-perspective consumes it.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_gsap_text_reveal( $settings, $ctx ) {
		$text = (string) ( $settings['heading'] ?? '' );
		echo '<p class="bi-el__reveal" data-motion-layer="text" data-motion-text-fallback="1">' . esc_html( $text ) . '</p>';
	}

	/**
	 * 3D tilt card.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_tilt_card( $settings, $ctx ) {
		echo '<article class="bi-el__card bi-tilt-3d" data-bi-tilt data-ngt-tilt-host="1">';
		self::title( $settings, '', 'h3' );
		self::subtitle( $settings );
		echo '</article>';
	}

	/**
	 * 3D scene canvas with CSS fallback.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_scene( $settings, $ctx ) {
		self::webgl_shell( 'scene', $settings );
	}

	/**
	 * Scroll mask.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_scroll_mask( $settings, $ctx ) {
		$img = $settings['image']['url'] ?? '';
		echo '<figure class="bi-el__mask" data-ngt-3d-target="scroll-mask">';
		if ( $img ) {
			echo '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( (string) ( $settings['heading'] ?? '' ) ) . '" loading="lazy" />';
		}
		self::title( $settings, '', 'h2' );
		echo '</figure>';
	}

	/**
	 * Zoom reveal.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_zoom_reveal( $settings, $ctx ) {
		$img = $settings['image']['url'] ?? '';
		echo '<figure class="bi-el__zoom" data-ngt-3d-target="zoom">';
		if ( $img ) {
			echo '<img src="' . esc_url( $img ) . '" alt="" loading="lazy" />';
		}
		self::title( $settings, '', 'h2' );
		self::subtitle( $settings );
		echo '</figure>';
	}

	/**
	 * Horizontal scroll track.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_horizontal_scroll( $settings, $ctx ) {
		$items = $settings['track_items'] ?? [];
		echo '<div class="bi-el__hscroll" data-ngt-3d-target="horizontal-scroll">';
		self::title( $settings, '' );
		echo '<div class="bi-el__hscroll-track">';
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				echo '<article class="bi-el__card">';
				echo '<h3 class="bi-el__title">' . esc_html( (string) ( $item['title'] ?? '' ) ) . '</h3>';
				echo '<p>' . esc_html( (string) ( $item['text'] ?? '' ) ) . '</p>';
				echo '</article>';
			}
		}
		echo '</div></div>';
	}

	/**
	 * Sticky / pinned story.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_sticky_story( $settings, $ctx ) {
		$panels = $settings['story_panels'] ?? [];
		echo '<section class="bi-el__story" data-ngt-3d-target="pin-section">';
		self::title( $settings, '' );
		if ( is_array( $panels ) ) {
			foreach ( $panels as $panel ) {
				echo '<article class="bi-el__story-panel">';
				echo '<h3 class="bi-el__title">' . esc_html( (string) ( $panel['title'] ?? '' ) ) . '</h3>';
				echo '<p>' . esc_html( (string) ( $panel['text'] ?? '' ) ) . '</p>';
				echo '</article>';
			}
		}
		echo '</section>';
	}

	/**
	 * Particle background.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_particle_background( $settings, $ctx ) {
		self::webgl_shell( 'particles', $settings );
	}

	/**
	 * Orb background.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_orb_background( $settings, $ctx ) {
		self::webgl_shell( 'orb', $settings );
	}

	/**
	 * Interactive WebGL background.
	 *
	 * @param array $settings Settings.
	 * @param array $ctx Context.
	 */
	private static function render_webgl_background( $settings, $ctx ) {
		self::webgl_shell( 'interactive', $settings );
	}

	/**
	 * Shared WebGL host: lazy canvas + CSS fallback, no second Three.js copy.
	 *
	 * @param string $kind Kind.
	 * @param array  $settings Settings.
	 */
	private static function webgl_shell( $kind, $settings ) {
		$quality = sanitize_key( $settings['webgl_quality'] ?? 'medium' );
		if ( ! in_array( $quality, [ 'low', 'medium', 'high' ], true ) ) {
			$quality = 'medium';
		}
		$fallback = $settings['fallback_image']['url'] ?? '';
		echo '<div class="bi-el__webgl" data-bi-el-webgl="' . esc_attr( $kind ) . '" data-bi-el-quality="' . esc_attr( $quality ) . '" data-bi-el-pause="1">';
		if ( $fallback ) {
			echo '<div class="bi-el__webgl-fallback" style="background-image:url(' . esc_url( $fallback ) . ')"></div>';
		} else {
			echo '<div class="bi-el__webgl-fallback" aria-hidden="true"></div>';
		}
		echo '<canvas class="bi-el__webgl-canvas" hidden></canvas>';
		self::title( $settings, '' );
		self::subtitle( $settings );
		echo '<p class="bi-el__noscript"><noscript>' . esc_html__( 'Interactive 3D is unavailable without JavaScript. The page content above remains readable.', 'beyondinfinity' ) . '</noscript></p>';
		echo '</div>';
	}
}
