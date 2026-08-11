<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\WordPress;

/**
 * Admin menu — SPA mount under NGT shell when present.
 */
final class AdminMenu {

	public const PAGE_SLUG = 'ngtbm-beyond-measure';

	public static function register(): void {
		$parent = function_exists( 'ngt_admin_parent' ) ? ngt_admin_parent() : '';
		$cap    = 'ngt_cp_access';
		if ( ! current_user_can( $cap ) && current_user_can( 'manage_options' ) ) {
			$cap = 'manage_options';
		}

		$title = __( 'Beyond Measure', 'nextgentutors-beyond-measure' );

		if ( is_string( $parent ) && $parent !== '' ) {
			add_submenu_page(
				$parent,
				$title,
				$title,
				$cap,
				self::PAGE_SLUG,
				[ self::class, 'render' ]
			);
			return;
		}

		add_menu_page(
			$title,
			$title,
			$cap,
			self::PAGE_SLUG,
			[ self::class, 'render' ],
			'dashicons-chart-area',
			3
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'ngt_cp_access' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Beyond Measure.', 'nextgentutors-beyond-measure' ) );
		}
		echo '<div class="wrap ngtbm-wrap"><div id="ngtbm-root" data-ngtbm-boot="1"></div></div>';
	}
}
