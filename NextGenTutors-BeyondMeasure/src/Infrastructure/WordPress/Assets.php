<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\WordPress;

/**
 * Enqueue SPA assets on Beyond Measure page only.
 */
final class Assets {

	public static function enqueue( string $hook ): void {
		if ( ! self::is_bm_screen( $hook ) ) {
			return;
		}

		$handle = 'ngtbm-admin';
		$js     = NGTBM_PLUGIN_DIR . 'build/index.js';
		$css    = NGTBM_PLUGIN_DIR . 'build/index.css';
		$tokens = NGTBM_PLUGIN_URL . 'admin/design-system/tokens/tokens.css';

		wp_enqueue_style( 'ngtbm-tokens', $tokens, [], NGTBM_VERSION );

		if ( is_readable( $css ) ) {
			wp_enqueue_style( $handle, NGTBM_PLUGIN_URL . 'build/index.css', [ 'ngtbm-tokens', 'wp-components' ], NGTBM_VERSION );
		}

		$deps = [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ];
		if ( is_readable( $js ) ) {
			wp_enqueue_script( $handle, NGTBM_PLUGIN_URL . 'build/index.js', $deps, NGTBM_VERSION, true );
		} else {
			// Fallback runtime when build assets are missing.
			wp_enqueue_script( $handle, NGTBM_PLUGIN_URL . 'build/fallback.js', $deps, NGTBM_VERSION, true );
		}

		wp_localize_script(
			$handle,
			'ngtbmBoot',
			[
				'restRoot'   => esc_url_raw( rest_url( NGTBM_REST_NAMESPACE ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'version'    => NGTBM_VERSION,
				'pluginUrl'  => NGTBM_PLUGIN_URL,
				'userId'     => get_current_user_id(),
				'caps'       => self::current_caps(),
				'adminUrl'   => admin_url(),
				'pageSlug'   => AdminMenu::PAGE_SLUG,
			]
		);

		add_action( 'admin_head', [ self::class, 'hide_notices' ] );
	}

	/**
	 * @return array<string,bool>
	 */
	private static function current_caps(): array {
		$keys = [
			'ngt_cp_access',
			'ngt_talent_read',
			'ngt_talent_evaluate',
			'ngt_talent_configure',
			'ngt_subsystem_read',
			'ngt_subsystem_configure',
			'ngt_subsystem_enable',
			'ngt_subsystem_disable',
			'ngt_audit_read',
			'ngt_health_read',
			'ngt_dlq_replay',
			'ngt_access_matrix_read',
			'ngt_access_matrix_manage',
		];
		$out = [];
		foreach ( $keys as $k ) {
			$out[ $k ] = current_user_can( $k ) || current_user_can( 'manage_options' );
		}
		return $out;
	}

	private static function is_bm_screen( string $hook ): bool {
		return false !== strpos( $hook, AdminMenu::PAGE_SLUG );
	}

	public static function hide_notices(): void {
		echo '<style>.ngtbm-wrap ~ .notice,.ngtbm-wrap .notice,.update-nag{display:none!important}.ngtbm-wrap{margin:0 0 0 -20px;max-width:none}#wpcontent{padding-left:0!important}.ngtbm-wrap #ngtbm-root{min-height:calc(100vh - 32px)}</style>';
	}
}
