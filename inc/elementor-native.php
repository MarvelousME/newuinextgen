<?php
/**
 * Elementor-native authoring layer.
 *
 * Makes public pages Elementor-editable by:
 * - Preferring widget-bearing Elementor documents over theme PHP fallback
 * - Registering NextGen shortcode / theme-body widgets with style controls
 * - Seeding Elementor documents from the pages registry (shortcodes + theme body)
 * - Preventing NGC visual-builder from replacing Elementor output
 *
 * Commerce shells (Woo cart/checkout/thank-you) remain theme-owned.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Soften kinetic front-page reclaim when Elementor owns the document.
 * Replaces the earlier filter at the same priority without editing locked files.
 */
add_action(
	'init',
	static function () {
		if ( ! function_exists( 'bi_prefer_kinetic_front_page_template' ) ) {
			return;
		}
		remove_filter( 'template_include', 'bi_prefer_kinetic_front_page_template', 9999 );
		add_filter( 'template_include', 'bi_elementor_native_front_page_template', 9999 );
	},
	20
);

/**
 * @param string $template Template path.
 * @return string
 */
function bi_elementor_native_front_page_template( $template ) {
	if ( ! is_front_page() ) {
		return $template;
	}
	if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
		return $template;
	}
	if ( function_exists( 'bi_is_elementor_canvas_template' ) && bi_is_elementor_canvas_template() ) {
		return $template;
	}

	$post_id = function_exists( 'bi_get_current_page_id' ) ? bi_get_current_page_id() : (int) get_option( 'page_on_front' );
	if ( $post_id && function_exists( 'bi_is_elementor_built' ) && bi_is_elementor_built( $post_id ) ) {
		return $template;
	}

	if ( ! function_exists( 'bi_use_kinetic_home' ) || ! bi_use_kinetic_home() ) {
		if ( ! $post_id || ( function_exists( 'bi_page_has_editor_content' ) && bi_page_has_editor_content( $post_id ) ) ) {
			return $template;
		}
		if ( function_exists( 'bi_is_wpbakery_built' ) && bi_is_wpbakery_built( $post_id ) ) {
			return $template;
		}
	}

	$front = trailingslashit( get_stylesheet_directory() ) . 'front-page.php';
	return file_exists( $front ) ? $front : $template;
}

/**
 * Do not let NGC visual builder replace Elementor page content.
 */
add_filter(
	'ngc_builder_replace_content',
	static function ( $enabled ) {
		$post_id = get_queried_object_id();
		if ( $post_id && function_exists( 'bi_is_elementor_built' ) && bi_is_elementor_built( $post_id ) ) {
			return false;
		}
		return $enabled;
	}
);

/**
 * Shortcode: render a full theme page default body (fallback bridge into Elementor).
 *
 * @param array<string, string> $atts Attributes.
 * @return string
 */
function bi_shortcode_theme_page_body( $atts ) {
	$atts = shortcode_atts(
		[
			'slug' => '',
		],
		$atts,
		'bi_theme_page_body'
	);
	$slug = sanitize_title( (string) $atts['slug'] );
	if ( '' === $slug ) {
		$slug = function_exists( 'bi_page_slug' ) ? (string) bi_page_slug() : '';
	}
	if ( '' === $slug || ! function_exists( 'bi_render_page_default' ) ) {
		return '';
	}

	ob_start();
	bi_render_page_default( $slug );
	return (string) ob_get_clean();
}
add_shortcode( 'bi_theme_page_body', 'bi_shortcode_theme_page_body' );

/**
 * Shortcode: render one kinetic/home production section by id.
 *
 * @param array<string, string> $atts Attributes.
 * @return string
 */
function bi_shortcode_home_section( $atts ) {
	$atts = shortcode_atts(
		[
			'id' => '',
		],
		$atts,
		'bi_home_section'
	);
	$id = sanitize_key( (string) $atts['id'] );
	if ( '' === $id ) {
		return '';
	}

	$file = trailingslashit( get_stylesheet_directory() ) . 'template-parts/sections/' . $id . '.php';
	if ( ! file_exists( $file ) ) {
		$alt = trailingslashit( get_stylesheet_directory() ) . 'template-parts/home/' . $id . '.php';
		$file = file_exists( $alt ) ? $alt : '';
	}
	if ( ! $file ) {
		return '';
	}

	ob_start();
	include $file;
	return (string) ob_get_clean();
}
add_shortcode( 'bi_home_section', 'bi_shortcode_home_section' );

/**
 * Register Elementor widgets for shortcodes + theme body.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
 */
function bi_elementor_native_register_widgets( $widgets_manager ) {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}

	require_once __DIR__ . '/elementor-native-widgets.php';
	$widgets_manager->register( new BI_Elementor_Shortcode_Widget() );
	$widgets_manager->register( new BI_Elementor_Theme_Body_Widget() );
}
add_action( 'elementor/widgets/register', 'bi_elementor_native_register_widgets', 20 );

/**
 * Generate an Elementor-compatible random id.
 *
 * @return string
 */
function bi_elementor_generate_id() {
	if ( class_exists( '\Elementor\Utils' ) && method_exists( '\Elementor\Utils', 'generate_random_string' ) ) {
		return \Elementor\Utils::generate_random_string();
	}
	return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
}

/**
 * Build a container wrapping shortcode widgets.
 *
 * @param array<int, string> $shortcodes Shortcode tags or full shortcodes.
 * @return array<int, array<string, mixed>>
 */
function bi_elementor_document_from_shortcodes( array $shortcodes ) {
	$widgets = [];
	foreach ( $shortcodes as $tag ) {
		$tag = trim( (string) $tag );
		if ( '' === $tag ) {
			continue;
		}
		$shortcode = ( '[' === $tag[0] ) ? $tag : '[' . $tag . ']';
		$widgets[] = [
			'id'         => bi_elementor_generate_id(),
			'elType'     => 'widget',
			'widgetType' => 'bi_ngc_shortcode',
			'settings'   => [
				'shortcode' => $shortcode,
			],
			'elements'   => [],
		];
	}

	if ( ! $widgets ) {
		return [];
	}

	return [
		[
			'id'       => bi_elementor_generate_id(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => [
				'flex_direction' => 'column',
				'padding'        => [
					'unit'     => 'px',
					'top'      => '24',
					'right'    => '16',
					'bottom'   => '24',
					'left'     => '16',
					'isLinked' => false,
				],
			],
			'elements' => $widgets,
		],
	];
}

/**
 * Persist Elementor document meta on a page.
 *
 * @param int                         $page_id  Page ID.
 * @param array<int, array<string, mixed>> $document Elementor data tree.
 * @return bool
 */
function bi_elementor_save_document( $page_id, array $document ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 || ! $document ) {
		return false;
	}

	$json = wp_json_encode( $document );
	if ( ! $json ) {
		return false;
	}

	update_post_meta( $page_id, '_elementor_data', wp_slash( $json ) );
	update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
	}
	delete_post_meta( $page_id, '_elementor_css' );

	// Clear stale force-theme flags.
	$meta = get_post_meta( $page_id, 'bi_options', true );
	if ( is_array( $meta ) && ! empty( $meta['force_theme_default'] ) ) {
		unset( $meta['force_theme_default'] );
		update_post_meta( $page_id, 'bi_options', $meta );
	}

	return true;
}

/**
 * Commerce / utility pages that stay theme-owned.
 *
 * @return string[]
 */
function bi_elementor_native_locked_slugs() {
	return apply_filters(
		'bi_elementor_native_locked_slugs',
		[ 'parent-checkout', 'thank-you', 'checkout', 'checkout-2', 'cart' ]
	);
}

/**
 * Seed Elementor documents for registry pages that are not yet Elementor-built.
 *
 * @param array<string, mixed> $args Optional args: force, slugs[].
 * @return array<string, mixed>
 */
function bi_elementor_enable_all_pages( array $args = [] ) {
	$force = ! empty( $args['force'] );
	$only  = isset( $args['slugs'] ) && is_array( $args['slugs'] ) ? array_map( 'sanitize_title', $args['slugs'] ) : [];
	$registry = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	$locked   = bi_elementor_native_locked_slugs();
	$results  = [
		'ok'      => true,
		'seeded'  => [],
		'skipped' => [],
		'errors'  => [],
	];

	if ( ! function_exists( 'bi_elementor_active' ) || ! bi_elementor_active() ) {
		$results['ok'] = false;
		$results['errors'][] = 'elementor_inactive';
		return $results;
	}

	foreach ( $registry as $slug => $def ) {
		if ( $only && ! in_array( $slug, $only, true ) ) {
			continue;
		}
		if ( in_array( $slug, $locked, true ) ) {
			$results['skipped'][ $slug ] = 'commerce_locked';
			continue;
		}

		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page && 'home' === $slug ) {
			$front = (int) get_option( 'page_on_front' );
			$page  = $front ? get_post( $front ) : null;
		}
		if ( ! $page ) {
			$results['errors'][ $slug ] = 'page_missing';
			continue;
		}

		$page_id = (int) $page->ID;
		if ( ! $force && function_exists( 'bi_is_elementor_built' ) && bi_is_elementor_built( $page_id ) ) {
			// Still clear force flag if present.
			$meta = get_post_meta( $page_id, 'bi_options', true );
			if ( is_array( $meta ) && ! empty( $meta['force_theme_default'] ) ) {
				unset( $meta['force_theme_default'] );
				update_post_meta( $page_id, 'bi_options', $meta );
			}
			$results['skipped'][ $slug ] = 'already_elementor';
			continue;
		}

		$shortcodes = [];
		if ( ! empty( $def['shortcodes'] ) && is_array( $def['shortcodes'] ) ) {
			foreach ( $def['shortcodes'] as $tag ) {
				$shortcodes[] = '[' . $tag . ']';
			}
		}

			if ( 'home' === $slug ) {
			// Full kinetic/production home body as one editable Elementor widget.
			// Designers can split into native Elementor widgets section-by-section.
			$shortcodes   = [ '[bi_theme_page_body slug="home"]' ];
			if ( function_exists( 'bi_update_theme_option' ) ) {
				bi_update_theme_option( 'home_layout', 'elementor' );
			} else {
				$opts = get_option( 'bi_options', [] );
				if ( ! is_array( $opts ) ) {
					$opts = [];
				}
				$opts['home_layout'] = 'elementor';
				update_option( 'bi_options', $opts, false );
			}
		} elseif ( ! $shortcodes ) {
			$shortcodes[] = '[bi_theme_page_body slug="' . esc_attr( $slug ) . '"]';
		}

		$document = bi_elementor_document_from_shortcodes( $shortcodes );
		if ( ! bi_elementor_save_document( $page_id, $document ) ) {
			$results['errors'][ $slug ] = 'save_failed';
			$results['ok'] = false;
			continue;
		}

		$results['seeded'][ $slug ] = [
			'page_id'    => $page_id,
			'shortcodes' => $shortcodes,
		];
	}

	/**
	 * Fires after Elementor-native enablement.
	 *
	 * @param array<string, mixed> $results Results.
	 */
	do_action( 'bi_elementor_enable_all_pages', $results );

	update_option( 'bi_elementor_native_seed_report', $results, false );
	return $results;
}

/**
 * Admin Tools submenu: enable Elementor on all pages.
 */
function bi_elementor_native_admin_menu() {
	add_management_page(
		__( 'Elementor Native Pages', 'beyondinfinity' ),
		__( 'Elementor Native Pages', 'beyondinfinity' ),
		'manage_options',
		'bi-elementor-native',
		'bi_elementor_native_admin_page'
	);
}
add_action( 'admin_menu', 'bi_elementor_native_admin_menu' );

/**
 * Admin page renderer.
 */
function bi_elementor_native_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$report = null;
	if ( isset( $_POST['bi_elementor_native_seed'] ) && check_admin_referer( 'bi_elementor_native_seed' ) ) {
		$force  = ! empty( $_POST['bi_elementor_native_force'] );
		$report = bi_elementor_enable_all_pages( [ 'force' => $force ] );
	}

	$last = get_option( 'bi_elementor_native_seed_report', [] );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Elementor-native pages', 'beyondinfinity' ); ?></h1>
		<p><?php esc_html_e( 'Seed every public/auth/dashboard page with an Elementor document so designers can edit layout and styling in Elementor Pro. Domain logic stays in Companion shortcodes / shared PHP renderers.', 'beyondinfinity' ); ?></p>
		<p><strong><?php esc_html_e( 'Locked (theme PHP):', 'beyondinfinity' ); ?></strong> <?php echo esc_html( implode( ', ', bi_elementor_native_locked_slugs() ) ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'bi_elementor_native_seed' ); ?>
			<p>
				<label>
					<input type="checkbox" name="bi_elementor_native_force" value="1" />
					<?php esc_html_e( 'Force re-seed (overwrites existing Elementor documents)', 'beyondinfinity' ); ?>
				</label>
			</p>
			<?php submit_button( __( 'Enable Elementor on all pages', 'beyondinfinity' ), 'primary', 'bi_elementor_native_seed' ); ?>
		</form>
		<?php if ( $report ) : ?>
			<h2><?php esc_html_e( 'Result', 'beyondinfinity' ); ?></h2>
			<pre style="max-width:960px;overflow:auto;background:#fff;padding:12px;border:1px solid #ccd0d4;"><?php echo esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT ) ); ?></pre>
		<?php elseif ( $last ) : ?>
			<h2><?php esc_html_e( 'Last seed report', 'beyondinfinity' ); ?></h2>
			<pre style="max-width:960px;overflow:auto;background:#fff;padding:12px;border:1px solid #ccd0d4;"><?php echo esc_html( wp_json_encode( $last, JSON_PRETTY_PRINT ) ); ?></pre>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * REST endpoint for agents / ops to trigger seeding.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bi/v1',
			'/elementor-native/enable',
			[
				'methods'             => 'POST',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function ( WP_REST_Request $request ) {
					$force = (bool) $request->get_param( 'force' );
					$slugs = $request->get_param( 'slugs' );
					$args  = [ 'force' => $force ];
					if ( is_array( $slugs ) ) {
						$args['slugs'] = $slugs;
					}
					return rest_ensure_response( bi_elementor_enable_all_pages( $args ) );
				},
			]
		);
	}
);
