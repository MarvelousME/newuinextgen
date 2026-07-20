<?php
/**
 * Layout mode manager, resolver, and debug tooling.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bi_layout_defaults() {
	return [
		'default_site_layout'          => 'boxed',
		'dashboard_layout_mode'        => 'full',
		'tutor_profile_layout_mode'    => 'boxed',
		'calendar_layout_mode'         => 'full',
		'force_full_width_dashboards'  => 1,
		'force_boxed_marketing_pages'  => 0,
		'disable_overflow_clipping'    => 0,
		'enable_visual_qa_debug'       => 0,
	];
}

function bi_layout_get_settings() {
	$defaults = bi_layout_defaults();
	$saved    = get_option( 'bi_layout_settings', [] );
	if ( ! is_array( $saved ) ) {
		$saved = [];
	}
	return wp_parse_args( $saved, $defaults );
}

function bi_layout_builder_mode( $post_id = 0 ) {
	$post_id = $post_id ?: bi_get_current_page_id();
	if ( bi_is_elementor_canvas_template( $post_id ) ) {
		return 'elementor-canvas';
	}
	if ( bi_is_elementor_built( $post_id ) ) {
		return 'elementor';
	}
	if ( bi_is_wpbakery_built( $post_id ) ) {
		return 'wpbakery';
	}
	return 'theme';
}

function bi_layout_is_dashboard_slug( $slug ) {
	$slugs = [ 'parent-dashboard', 'student-dashboard', 'tutor-dashboard', 'admin-dashboard' ];
	return in_array( (string) $slug, $slugs, true );
}

function bi_layout_resolve( $post_id = 0 ) {
	$post_id    = $post_id ?: bi_get_current_page_id();
	$settings   = bi_layout_get_settings();
	$slug       = $post_id ? (string) get_post_field( 'post_name', $post_id ) : '';
	$template   = $post_id ? (string) get_page_template_slug( $post_id ) : '';
	$builder    = bi_layout_builder_mode( $post_id );
	$is_dash    = bi_is_dashboard_page() || bi_layout_is_dashboard_slug( $slug );
	$is_calendar = is_singular( 'tutors' ) || is_page( 'booking' );
	$is_form     = is_page( [ 'find-a-tutor', 'become-a-tutor', 'contact', 'support', 'register', 'login' ] );
	$mode        = (string) $settings['default_site_layout'];

	$override_mode = $post_id ? get_post_meta( $post_id, '_bi_layout_mode', true ) : '';
	$sidebar_mode  = $post_id ? get_post_meta( $post_id, '_bi_sidebar_mode', true ) : '';

	if ( in_array( $override_mode, [ 'boxed', 'full', 'wide' ], true ) ) {
		$mode = $override_mode;
	}

	if ( $is_dash ) {
		$mode = (string) $settings['dashboard_layout_mode'];
	}

	if ( $is_dash && ! empty( $settings['force_full_width_dashboards'] ) ) {
		$mode = 'full';
	}

	if ( $is_calendar ) {
		$mode = (string) $settings['calendar_layout_mode'];
	}

	if ( is_singular( 'tutors' ) ) {
		$mode = (string) $settings['tutor_profile_layout_mode'];
	}

	if ( ! empty( $settings['force_boxed_marketing_pages'] ) && ! $is_dash ) {
		$mode = 'boxed';
	}

	if ( 'templates/full-width.php' === $template ) {
		$mode = 'full';
	}

	$sidebar = 'none';
	if ( in_array( $sidebar_mode, [ 'left', 'right', 'none' ], true ) ) {
		$sidebar = $sidebar_mode;
	}

	$container = 'boxed';
	if ( 'full' === $mode ) {
		$container = 'full';
	} elseif ( 'wide' === $mode ) {
		$container = 'wide';
	}

	$classes = [
		'ng-page',
		$slug ? 'ng-page-' . sanitize_html_class( $slug ) : 'ng-page-archive',
		'ng-layout-' . sanitize_html_class( $mode ),
		'none' === $sidebar ? 'ng-no-sidebar' : 'ng-has-sidebar',
		'left' === $sidebar ? 'ng-sidebar-left' : '',
		'right' === $sidebar ? 'ng-sidebar-right' : '',
		'elementor' === $builder ? 'ng-builder-elementor' : '',
		'wpbakery' === $builder ? 'ng-builder-wpbakery' : '',
		'elementor-canvas' === $builder ? 'ng-builder-elementor-canvas' : '',
		$is_dash ? 'ng-dashboard-page' : '',
		$is_calendar ? 'ng-calendar-page' : '',
		$is_form ? 'ng-form-page' : '',
	];

	$classes = array_values( array_filter( $classes ) );

	return [
		'post_id'      => $post_id,
		'slug'         => $slug,
		'template'     => $template ?: 'default',
		'builder'      => $builder,
		'mode'         => $mode,
		'container'    => $container,
		'sidebar'      => $sidebar,
		'is_dashboard' => $is_dash,
		'is_calendar'  => $is_calendar,
		'is_form'      => $is_form,
		'classes'      => $classes,
	];
}

function bi_layout_body_classes( $classes ) {
	$ctx = bi_layout_resolve();
	$merged = array_merge( $classes, $ctx['classes'] );
	if ( bi_layout_debug_enabled() ) {
		$merged[] = 'ng-layout-debug';
	}
	return $merged;
}
add_filter( 'body_class', 'bi_layout_body_classes', 40 );

function bi_layout_wrapper_open( $context = 'page' ) {
	$ctx = bi_layout_resolve();
	if ( 'dashboard' === $context || $ctx['is_dashboard'] ) {
		return '<div class="ng-dashboard ng-layout ng-layout--full"><div class="ng-dashboard__inner">';
	}

	$layout_class    = 'ng-layout ng-layout--' . esc_attr( $ctx['mode'] );
	$container_class = 'ng-container ng-container--' . esc_attr( $ctx['container'] );
	return '<div class="' . $layout_class . '"><div class="' . $container_class . '">';
}

function bi_layout_wrapper_close() {
	return '</div></div>';
}

function bi_layout_debug_enabled() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	$settings = bi_layout_get_settings();
	return ! empty( $settings['enable_visual_qa_debug'] );
}

function bi_layout_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'bi-layout-system',
		BI_URI . '/assets/css/layout-system.css',
		[ 'bi-style', 'bi-page-builders' ],
		BI_VERSION
	);

	$settings = bi_layout_get_settings();
	if ( ! empty( $settings['disable_overflow_clipping'] ) ) {
		wp_add_inline_style( 'bi-layout-system', 'body{overflow-x:visible !important;} .ng-layout{overflow:visible !important;}' );
	}

	if ( bi_layout_debug_enabled() ) {
		$ctx = bi_layout_resolve();
		wp_enqueue_script( 'bi-layout-debug', BI_URI . '/assets/js/layout-debug.js', [], BI_VERSION, true );
		wp_localize_script(
			'bi-layout-debug',
			'biLayoutDebug',
			[
				'enabled'    => true,
				'layoutMode' => $ctx['mode'],
				'template'   => $ctx['template'],
				'builder'    => $ctx['builder'],
			]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bi_layout_enqueue_assets', 30 );

function bi_layout_admin_bar( $admin_bar ) {
	if ( ! bi_layout_debug_enabled() ) {
		return;
	}
	$ctx = bi_layout_resolve();
	$admin_bar->add_node(
		[
			'id'    => 'bi-layout-debug',
			'title' => 'Layout: ' . strtoupper( $ctx['mode'] ) . ' | Builder: ' . strtoupper( $ctx['builder'] ),
			'href'  => admin_url( 'themes.php?page=bi-layout-settings' ),
		]
	);
}
add_action( 'admin_bar_menu', 'bi_layout_admin_bar', 100 );

function bi_layout_register_menu() {
	add_theme_page(
		__( 'Layout Settings', 'beyondinfinity' ),
		__( 'Layout Settings', 'beyondinfinity' ),
		'manage_options',
		'bi-layout-settings',
		'bi_layout_settings_screen'
	);
}
add_action( 'admin_menu', 'bi_layout_register_menu' );

function bi_layout_settings_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = bi_layout_get_settings();
	if ( isset( $_POST['bi_layout_save'] ) && check_admin_referer( 'bi_layout_save' ) ) {
		$settings['default_site_layout']         = in_array( $_POST['default_site_layout'] ?? '', [ 'boxed', 'full', 'wide' ], true ) ? sanitize_text_field( wp_unslash( $_POST['default_site_layout'] ) ) : 'boxed';
		$settings['dashboard_layout_mode']       = in_array( $_POST['dashboard_layout_mode'] ?? '', [ 'boxed', 'full', 'wide' ], true ) ? sanitize_text_field( wp_unslash( $_POST['dashboard_layout_mode'] ) ) : 'full';
		$settings['tutor_profile_layout_mode']   = in_array( $_POST['tutor_profile_layout_mode'] ?? '', [ 'boxed', 'full', 'wide' ], true ) ? sanitize_text_field( wp_unslash( $_POST['tutor_profile_layout_mode'] ) ) : 'boxed';
		$settings['calendar_layout_mode']        = in_array( $_POST['calendar_layout_mode'] ?? '', [ 'boxed', 'full', 'wide' ], true ) ? sanitize_text_field( wp_unslash( $_POST['calendar_layout_mode'] ) ) : 'full';
		$settings['force_full_width_dashboards'] = ! empty( $_POST['force_full_width_dashboards'] ) ? 1 : 0;
		$settings['force_boxed_marketing_pages'] = ! empty( $_POST['force_boxed_marketing_pages'] ) ? 1 : 0;
		$settings['disable_overflow_clipping']   = ! empty( $_POST['disable_overflow_clipping'] ) ? 1 : 0;
		$settings['enable_visual_qa_debug']      = ! empty( $_POST['enable_visual_qa_debug'] ) ? 1 : 0;

		update_option( 'bi_layout_settings', $settings, false );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Layout settings saved.', 'beyondinfinity' ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BeyondInfinity Layout Settings', 'beyondinfinity' ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( 'bi_layout_save' ); ?>
			<table class="form-table">
				<tr><th scope="row"><?php esc_html_e( 'Default site layout', 'beyondinfinity' ); ?></th><td><?php bi_layout_select( 'default_site_layout', $settings['default_site_layout'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Dashboard layout mode', 'beyondinfinity' ); ?></th><td><?php bi_layout_select( 'dashboard_layout_mode', $settings['dashboard_layout_mode'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Tutor profile layout mode', 'beyondinfinity' ); ?></th><td><?php bi_layout_select( 'tutor_profile_layout_mode', $settings['tutor_profile_layout_mode'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Calendar layout mode', 'beyondinfinity' ); ?></th><td><?php bi_layout_select( 'calendar_layout_mode', $settings['calendar_layout_mode'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Force full-width dashboards', 'beyondinfinity' ); ?></th><td><label><input type="checkbox" name="force_full_width_dashboards" value="1" <?php checked( ! empty( $settings['force_full_width_dashboards'] ) ); ?> /> <?php esc_html_e( 'Enable', 'beyondinfinity' ); ?></label></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Force boxed marketing pages', 'beyondinfinity' ); ?></th><td><label><input type="checkbox" name="force_boxed_marketing_pages" value="1" <?php checked( ! empty( $settings['force_boxed_marketing_pages'] ) ); ?> /> <?php esc_html_e( 'Enable', 'beyondinfinity' ); ?></label></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Disable theme overflow clipping', 'beyondinfinity' ); ?></th><td><label><input type="checkbox" name="disable_overflow_clipping" value="1" <?php checked( ! empty( $settings['disable_overflow_clipping'] ) ); ?> /> <?php esc_html_e( 'Enable', 'beyondinfinity' ); ?></label></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Enable visual QA debug outline', 'beyondinfinity' ); ?></th><td><label><input type="checkbox" name="enable_visual_qa_debug" value="1" <?php checked( ! empty( $settings['enable_visual_qa_debug'] ) ); ?> /> <?php esc_html_e( 'Admin-only debug overlays and checks', 'beyondinfinity' ); ?></label></td></tr>
			</table>
			<p><button class="button button-primary" type="submit" name="bi_layout_save" value="1"><?php esc_html_e( 'Save Layout Settings', 'beyondinfinity' ); ?></button></p>
		</form>
	</div>
	<?php
}

function bi_layout_select( $name, $selected ) {
	$options = [
		'boxed' => __( 'Boxed', 'beyondinfinity' ),
		'full'  => __( 'Full Width', 'beyondinfinity' ),
		'wide'  => __( 'Wide Content', 'beyondinfinity' ),
	];
	echo '<select name="' . esc_attr( $name ) . '">';
	foreach ( $options as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

function bi_layout_meta_box() {
	add_meta_box(
		'bi-layout-meta',
		__( 'Layout Override', 'beyondinfinity' ),
		'bi_layout_meta_box_render',
		'page',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'bi_layout_meta_box' );

function bi_layout_meta_box_render( $post ) {
	wp_nonce_field( 'bi_layout_meta', 'bi_layout_meta_nonce' );
	$mode    = (string) get_post_meta( $post->ID, '_bi_layout_mode', true );
	$sidebar = (string) get_post_meta( $post->ID, '_bi_sidebar_mode', true );
	?>
	<p><label for="bi-layout-mode"><strong><?php esc_html_e( 'Per-page layout mode', 'beyondinfinity' ); ?></strong></label></p>
	<select id="bi-layout-mode" name="bi_layout_mode" style="width:100%">
		<option value=""><?php esc_html_e( 'Use defaults', 'beyondinfinity' ); ?></option>
		<option value="boxed" <?php selected( $mode, 'boxed' ); ?>><?php esc_html_e( 'Boxed', 'beyondinfinity' ); ?></option>
		<option value="full" <?php selected( $mode, 'full' ); ?>><?php esc_html_e( 'Full Width', 'beyondinfinity' ); ?></option>
		<option value="wide" <?php selected( $mode, 'wide' ); ?>><?php esc_html_e( 'Wide Content', 'beyondinfinity' ); ?></option>
	</select>
	<p><label for="bi-sidebar-mode"><strong><?php esc_html_e( 'Sidebar mode', 'beyondinfinity' ); ?></strong></label></p>
	<select id="bi-sidebar-mode" name="bi_sidebar_mode" style="width:100%">
		<option value=""><?php esc_html_e( 'Use defaults', 'beyondinfinity' ); ?></option>
		<option value="none" <?php selected( $sidebar, 'none' ); ?>><?php esc_html_e( 'No Sidebar', 'beyondinfinity' ); ?></option>
		<option value="left" <?php selected( $sidebar, 'left' ); ?>><?php esc_html_e( 'Left Sidebar', 'beyondinfinity' ); ?></option>
		<option value="right" <?php selected( $sidebar, 'right' ); ?>><?php esc_html_e( 'Right Sidebar', 'beyondinfinity' ); ?></option>
	</select>
	<?php
}

function bi_layout_save_meta( $post_id ) {
	if ( ! isset( $_POST['bi_layout_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bi_layout_meta_nonce'] ) ), 'bi_layout_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	$mode = sanitize_text_field( wp_unslash( $_POST['bi_layout_mode'] ?? '' ) );
	if ( in_array( $mode, [ 'boxed', 'full', 'wide' ], true ) ) {
		update_post_meta( $post_id, '_bi_layout_mode', $mode );
	} else {
		delete_post_meta( $post_id, '_bi_layout_mode' );
	}

	$sidebar = sanitize_text_field( wp_unslash( $_POST['bi_sidebar_mode'] ?? '' ) );
	if ( in_array( $sidebar, [ 'none', 'left', 'right' ], true ) ) {
		update_post_meta( $post_id, '_bi_sidebar_mode', $sidebar );
	} else {
		delete_post_meta( $post_id, '_bi_sidebar_mode' );
	}
}
add_action( 'save_post_page', 'bi_layout_save_meta' );

