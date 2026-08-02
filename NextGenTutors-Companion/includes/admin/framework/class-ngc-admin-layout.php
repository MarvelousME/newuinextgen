<?php
/**
 * Consistent admin page chrome (title region helpers).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layout helpers for enterprise admin screens.
 */
final class NGC_Admin_Layout {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'admin_body_class', [ __CLASS__, 'body_class' ] );
	}

	/**
	 * @param string $classes Classes.
	 * @return string
	 */
	public static function body_class( $classes ) {
		if ( NGC_Admin_Shell::is_ngt_screen() ) {
			$classes .= ' ngt-admin-screen';
			$prefs    = class_exists( 'NGC_Admin_Prefs' ) ? NGC_Admin_Prefs::get() : [];
			if ( ! empty( $prefs['density'] ) ) {
				$classes .= ' ngt-density-' . sanitize_html_class( (string) $prefs['density'] );
			}
		}
		return $classes;
	}

	/**
	 * Render a standard page header block.
	 *
	 * @param string             $title   Title.
	 * @param string             $summary Summary.
	 * @param array<int, string> $actions HTML action buttons.
	 */
	public static function header( $title, $summary = '', array $actions = [] ) {
		echo '<header class="ngt-admin-page-header">';
		echo '<div class="ngt-admin-page-header__main">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		if ( $summary ) {
			echo '<p class="ngt-admin-page-header__summary">' . esc_html( $summary ) . '</p>';
		}
		echo '</div>';
		if ( $actions ) {
			echo '<div class="ngt-admin-page-header__actions">';
			foreach ( $actions as $html ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller-provided trusted HTML buttons.
				echo $html;
			}
			echo '</div>';
		}
		echo '</header>';
	}

	/**
	 * Full page chrome wrapper.
	 *
	 * @param array<string, mixed> $args Args: title, summary, actions, tabs, toolbar, filters, content(callable|string), help, footer.
	 */
	public static function render_page( array $args ) {
		$title   = (string) ( $args['title'] ?? '' );
		$summary = (string) ( $args['summary'] ?? '' );
		$actions = (array) ( $args['actions'] ?? [] );
		$tabs    = (array) ( $args['tabs'] ?? [] );
		$help    = (string) ( $args['help'] ?? '' );
		$footer  = (string) ( $args['footer'] ?? '' );

		echo '<div class="wrap ngt-admin-page" data-testid="ngt-admin-page">';
		self::header( $title, $summary, $actions );

		if ( $tabs ) {
			echo '<nav class="ngt-admin-tabs" aria-label="' . esc_attr__( 'Context tabs', 'nextgencompanion' ) . '">';
			foreach ( $tabs as $tab ) {
				$url    = (string) ( $tab['url'] ?? '#' );
				$label  = (string) ( $tab['label'] ?? '' );
				$active = ! empty( $tab['active'] ) ? ' is-active' : '';
				echo '<a class="ngt-admin-tabs__item' . esc_attr( $active ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
			echo '</nav>';
		}

		if ( ! empty( $args['toolbar'] ) ) {
			echo '<div class="ngt-admin-toolbar">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $args['toolbar'];
			echo '</div>';
		}

		if ( ! empty( $args['filters'] ) ) {
			echo '<div class="ngt-admin-filters">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $args['filters'];
			echo '</div>';
		}

		echo '<div class="ngt-admin-page__content">';
		if ( isset( $args['content'] ) && is_callable( $args['content'] ) ) {
			call_user_func( $args['content'] );
		} elseif ( isset( $args['content'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $args['content'];
		}
		echo '</div>';

		if ( $help ) {
			echo '<aside class="ngt-admin-help"><p>' . esc_html( $help ) . '</p></aside>';
		}
		if ( $footer ) {
			echo '<footer class="ngt-admin-page-footer">' . esc_html( $footer ) . '</footer>';
		}
		echo '</div>';
	}

	/**
	 * Coming-soon placeholder for nested Education screens etc.
	 */
	public static function render_placeholder() {
		$page  = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$screen = NGC_Admin_Registry::get_screen( $page );
		$title  = $screen['title'] ?? __( 'Coming soon', 'nextgencompanion' );
		self::render_page(
			[
				'title'   => (string) $title,
				'summary' => __( 'This module is registered in the enterprise navigation hierarchy and will be delivered in a later phase.', 'nextgencompanion' ),
				'content' => '<div class="ngt-admin-card" data-ngt-motion><p>' . esc_html__( 'Placeholder — no data operations yet.', 'nextgencompanion' ) . '</p></div>',
			]
		);
	}
}
