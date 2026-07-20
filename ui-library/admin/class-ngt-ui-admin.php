<?php
/**
 * Admin — NextGen → UI Library (real registry / preview / diagnostics).
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Admin' ) ) {
	/**
	 * Administrator UI for the shared component library.
	 */
	class NGT_UI_Admin {

		public static function init(): void {
			add_action( 'admin_menu', array( __CLASS__, 'register_menus' ), 30 );
			add_action( 'admin_post_ngt_ui_preview', array( __CLASS__, 'handle_preview' ) );
		}

		/**
		 * Attach under Companion "NextGen" menu when present; else top-level.
		 */
		public static function register_menus(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			global $menu;
			$has_ngc = false;
			if ( is_array( $menu ) ) {
				foreach ( $menu as $item ) {
					if ( isset( $item[2] ) && 'ngc-operations' === $item[2] ) {
						$has_ngc = true;
						break;
					}
				}
			}

			$parent = $has_ngc ? 'ngc-operations' : 'ngt-ui-library';
			if ( ! $has_ngc ) {
				add_menu_page(
					__( 'NGT UI Library', 'ngt-ui' ),
					__( 'UI Library', 'ngt-ui' ),
					'manage_options',
					'ngt-ui-library',
					array( __CLASS__, 'render_registry' ),
					'dashicons-art',
					59
				);
			} else {
				add_submenu_page(
					$parent,
					__( 'UI Library', 'ngt-ui' ),
					__( 'UI Library', 'ngt-ui' ),
					'manage_options',
					'ngt-ui-library',
					array( __CLASS__, 'render_registry' )
				);
			}

			add_submenu_page(
				$parent,
				__( 'UI Preview', 'ngt-ui' ),
				__( 'UI Preview', 'ngt-ui' ),
				'manage_options',
				'ngt-ui-preview',
				array( __CLASS__, 'render_preview' )
			);

			add_submenu_page(
				$parent,
				__( 'UI Diagnostics', 'ngt-ui' ),
				__( 'UI Diagnostics', 'ngt-ui' ),
				'manage_options',
				'ngt-ui-diagnostics',
				array( __CLASS__, 'render_diagnostics' )
			);
		}

		/**
		 * Component registry table — live from NGT_UI_Registry.
		 */
		public static function render_registry(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Forbidden', 'ngt-ui' ) );
			}

			$components = class_exists( 'NGT_UI_Registry' ) ? NGT_UI_Registry::all() : array();
			$dir        = defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : '';
			$url        = defined( 'NGT_UI_LIBRARY_URL' ) ? NGT_UI_LIBRARY_URL : '';

			echo '<div class="wrap"><h1>' . esc_html__( 'NGT UI Library — Component Registry', 'ngt-ui' ) . '</h1>';
			echo '<p>' . esc_html( sprintf( /* translators: %d count */ __( '%d registered components. All editors call NGT_UI_Renderer.', 'ngt-ui' ), count( $components ) ) ) . '</p>';
			echo '<p><code>' . esc_html( $dir ) . '</code><br><code>' . esc_html( $url ) . '</code></p>';

			echo '<table class="widefat striped"><thead><tr>';
			echo '<th>' . esc_html__( 'Slug', 'ngt-ui' ) . '</th>';
			echo '<th>' . esc_html__( 'Label', 'ngt-ui' ) . '</th>';
			echo '<th>' . esc_html__( 'Category', 'ngt-ui' ) . '</th>';
			echo '<th>' . esc_html__( 'Shortcode', 'ngt-ui' ) . '</th>';
			echo '<th>' . esc_html__( 'Editors', 'ngt-ui' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $components as $slug => $component ) {
				$alias = 'ngt_' . str_replace( '-', '_', $slug );
				$editors = array();
				foreach ( array( 'shortcode', 'php', 'gutenberg', 'elementor', 'wpbakery' ) as $ed ) {
					if ( $component->supports_editor( $ed ) ) {
						$editors[] = $ed;
					}
				}
				echo '<tr>';
				echo '<td><code>' . esc_html( $slug ) . '</code></td>';
				echo '<td>' . esc_html( $component->get_label() ) . '</td>';
				echo '<td>' . esc_html( $component->get_category() ) . '</td>';
				echo '<td><code>[ngt_ui component="' . esc_attr( $slug ) . '"]</code><br><code>[' . esc_html( $alias ) . ']</code></td>';
				echo '<td>' . esc_html( implode( ', ', $editors ) ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table></div>';
		}

		/**
		 * Live preview using canonical renderer.
		 */
		public static function render_preview(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Forbidden', 'ngt-ui' ) );
			}

			$components = class_exists( 'NGT_UI_Registry' ) ? NGT_UI_Registry::all() : array();
			$selected   = isset( $_GET['component'] ) ? sanitize_key( wp_unslash( (string) $_GET['component'] ) ) : 'magic-card'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $components[ $selected ] ) && $components ) {
				$selected = (string) array_key_first( $components );
			}

			$text    = isset( $_GET['text'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['text'] ) ) : 'NextGen Tutors'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$items   = isset( $_GET['items'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['items'] ) ) : 'Math|Science|English'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$preview = '';
			if ( class_exists( 'NGT_UI_Renderer' ) && $selected ) {
				NGT_UI_Assets::register();
				NGT_UI_Assets::enqueue_for( $selected );
				$preview = NGT_UI_Renderer::render(
					$selected,
					array(
						'text'    => $text,
						'title'   => $text,
						'label'   => $text,
						'items'   => $items,
						'content' => '',
					),
					array( 'editor' => 'admin-preview' )
				);
			}

			echo '<div class="wrap"><h1>' . esc_html__( 'Component Preview', 'ngt-ui' ) . '</h1>';
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="ngt-ui-preview" />';
			echo '<p><label>' . esc_html__( 'Component', 'ngt-ui' ) . ' <select name="component">';
			foreach ( $components as $slug => $component ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $slug ),
					selected( $selected, $slug, false ),
					esc_html( $component->get_label() . ' (' . $slug . ')' )
				);
			}
			echo '</select></label></p>';
			echo '<p><label>' . esc_html__( 'Text', 'ngt-ui' ) . ' <input type="text" name="text" value="' . esc_attr( $text ) . '" class="regular-text" /></label></p>';
			echo '<p><label>' . esc_html__( 'Items', 'ngt-ui' ) . ' <input type="text" name="items" value="' . esc_attr( $items ) . '" class="regular-text" /></label></p>';
			submit_button( __( 'Preview', 'ngt-ui' ), 'primary', '', false );
			echo '</form>';

			echo '<h2>' . esc_html__( 'Output', 'ngt-ui' ) . '</h2>';
			echo '<div class="ngt-ui-admin-preview" style="max-width:960px;padding:1rem;background:#fff;border:1px solid #ccd0d4;">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes.
			echo $preview;
			echo '</div>';

			$php = "echo ngt_render_ui_component( '" . $selected . "', [ 'text' => '" . esc_js( $text ) . "' ] );";
			$sc  = '[ngt_ui component="' . esc_attr( $selected ) . '" text="' . esc_attr( $text ) . '" items="' . esc_attr( $items ) . '"]';
			echo '<h2>' . esc_html__( 'Shortcode', 'ngt-ui' ) . '</h2><pre>' . esc_html( $sc ) . '</pre>';
			echo '<h2>' . esc_html__( 'PHP', 'ngt-ui' ) . '</h2><pre>' . esc_html( $php ) . '</pre>';
			echo '</div>';
		}

		/**
		 * Diagnostics against paths and registry.
		 */
		public static function render_diagnostics(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Forbidden', 'ngt-ui' ) );
			}

			$rows = array(
				'booted'           => did_action( 'ngt_ui_library_booted' ) > 0,
				'NGT_UI_LIBRARY_DIR'=> defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : null,
				'NGT_UI_LIBRARY_URL'=> defined( 'NGT_UI_LIBRARY_URL' ) ? NGT_UI_LIBRARY_URL : null,
				'registry_count'   => class_exists( 'NGT_UI_Registry' ) ? count( NGT_UI_Registry::all() ) : 0,
				'shortcode_ngt_ui' => shortcode_exists( 'ngt_ui' ),
				'block_registered' => function_exists( 'WP_Block_Type_Registry' ) ? WP_Block_Type_Registry::get_instance()->is_registered( 'ngt-ui/component' ) : false,
				'elementor_active' => did_action( 'elementor/loaded' ) > 0,
				'wpbakery_active'  => defined( 'WPB_VC_VERSION' ) || function_exists( 'vc_map' ),
				'php_version'      => PHP_VERSION,
				'wp_version'       => get_bloginfo( 'version' ),
			);

			echo '<div class="wrap"><h1>' . esc_html__( 'UI Library Diagnostics', 'ngt-ui' ) . '</h1>';
			echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:1rem;overflow:auto;">';
			echo esc_html( wp_json_encode( $rows, JSON_PRETTY_PRINT ) );
			echo '</pre></div>';
		}

		/**
		 * Reserved for POST preview actions.
		 */
		public static function handle_preview(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Forbidden', 'ngt-ui' ) );
			}
			check_admin_referer( 'ngt_ui_preview' );
			wp_safe_redirect( admin_url( 'admin.php?page=ngt-ui-preview' ) );
			exit;
		}
	}
}
