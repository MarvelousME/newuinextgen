<?php
/**
 * BeyondInfinity theme admin: Health page and plugin handshake.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme admin pages.
 */
function bi_beyondinfinity_admin_menu() {
	add_theme_page(
		__( 'BeyondInfinity Health', 'beyondinfinity' ),
		__( 'BeyondInfinity Health', 'beyondinfinity' ),
		'manage_options',
		'bi-health',
		'bi_beyondinfinity_health_page'
	);
}
add_action( 'admin_menu', 'bi_beyondinfinity_admin_menu' );

/**
 * Collect integration health checks (theme ⇄ plugin contract).
 *
 * @return array<string, mixed>
 */
function bi_beyondinfinity_health_checks() {
	if ( function_exists( 'bi_ensure_live_tutor_cpt' ) ) {
		bi_ensure_live_tutor_cpt();
	}

	$companion = bi_companion_active();
	$cpt_count = bi_count_published_tutors();

	$sc_health   = function_exists( 'bi_ngc_shortcode_health' ) ? bi_ngc_shortcode_health() : [ 'ok' => false, 'missing' => [] ];

	$checks = [
		__( 'Companion plugin detected', 'beyondinfinity' ) => $companion,
		__( 'Plugin version', 'beyondinfinity' )          => bi_ngc_version(),
		__( 'Tutor marketplace CPT', 'beyondinfinity' )     => post_type_exists( 'tutors' ),
		__( 'Published tutor CPT posts', 'beyondinfinity' ) => $cpt_count > 0 ? (string) $cpt_count : false,
		__( 'Live CPT tutor helper', 'beyondinfinity' )     => function_exists( 'bi_get_live_tutors' ) && ! empty( bi_get_live_tutors( 1 ) ),
		__( 'Match tutor widget', 'beyondinfinity' )            => class_exists( 'NGC_Smart_Matching', false ) && file_exists( NGC_PLUGIN_DIR . 'assets/js/ngc-match-widget.js' ),
		__( 'Match tutor shortcode', 'beyondinfinity' )       => shortcode_exists( 'ngc_match_tutor' ),
		__( 'Form validation assets', 'beyondinfinity' )    => ( defined( 'NGC_PLUGIN_DIR' ) && file_exists( NGC_PLUGIN_DIR . 'assets/js/ngc-validation.js' ) ) || file_exists( BI_DIR . '/assets/css/ngc-validation.css' ),
		__( 'Exception log dashboard', 'beyondinfinity' )     => class_exists( 'NGC_Exception_Log', false ),
		__( 'Find-a-tutor shortcode', 'beyondinfinity' )      => shortcode_exists( 'ngc_find_tutor_form' ),
		__( 'Role dashboard shortcode', 'beyondinfinity' )    => shortcode_exists( 'ngc_student_dashboard' ),
		__( 'Tutor carousel shortcode', 'beyondinfinity' )  => shortcode_exists( 'ngc_tutor_carousel' ) || shortcode_exists( 'bi_tutors_carousel' ),
		__( 'All ngc_* shortcodes', 'beyondinfinity' )        => ! empty( $sc_health['ok'] ),
		__( 'Primary menu assigned', 'beyondinfinity' )       => has_nav_menu( 'primary' ),
		__( 'NGT design assets', 'beyondinfinity' )           => file_exists( BI_DIR . '/assets/ngt/css/floating.css' ),
	];

	if ( $companion && class_exists( 'NGC_Health_Scanner', false ) ) {
		$scan = NGC_Health_Scanner::full_scan();
		$checks[ __( 'Plugin full health scan', 'beyondinfinity' ) ] = ! empty( $scan['ok'] );
	}

	return apply_filters( 'bi_beyondinfinity_health_checks', $checks );
}

/**
 * Health / integration handshake screen.
 */
function bi_beyondinfinity_health_page() {
	$checks   = bi_beyondinfinity_health_checks();
	$verified = 0;
	$total    = count( $checks );

	foreach ( $checks as $val ) {
		if ( true === $val || ( is_string( $val ) && '' !== $val ) ) {
			++$verified;
		}
	}

	$plugin_health_url = admin_url( 'admin.php?page=ngc-health' );
	$errors_url        = admin_url( 'admin.php?page=ngc-errors' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BeyondInfinity — Theme ⇄ Plugin Health', 'beyondinfinity' ); ?></h1>
		<p><?php esc_html_e( 'Verifies the integration contract between the BeyondInfinity theme and the NextGen Companion plugin.', 'beyondinfinity' ); ?></p>

		<p>
			<strong><?php echo esc_html( sprintf( '%d / %d checks passing', $verified, $total ) ); ?></strong>
			<?php if ( bi_companion_active() ) : ?>
				— <a href="<?php echo esc_url( $plugin_health_url ); ?>"><?php esc_html_e( 'Open plugin System Health', 'beyondinfinity' ); ?></a>
				<?php if ( class_exists( 'NGC_Exception_Log', false ) ) : ?>
					| <a href="<?php echo esc_url( $errors_url ); ?>"><?php esc_html_e( 'View error log', 'beyondinfinity' ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</p>

		<table class="widefat striped" style="max-width:760px">
			<tbody>
			<?php foreach ( $checks as $label => $val ) : ?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td>
						<?php
						if ( true === $val ) {
							echo '<span style="color:#15803d;font-weight:700">✓ ' . esc_html__( 'VERIFIED', 'beyondinfinity' ) . '</span>';
						} elseif ( false === $val || null === $val || '' === $val ) {
							echo '<span style="color:#b91c1c;font-weight:700">✗ ' . esc_html__( 'NOT READY', 'beyondinfinity' ) . '</span>';
						} else {
							echo '<code>' . esc_html( (string) $val ) . '</code>';
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! bi_companion_active() ) : ?>
			<p style="margin-top:16px">
				<strong><?php esc_html_e( 'Next step:', 'beyondinfinity' ); ?></strong>
				<?php esc_html_e( 'Install and activate the NextGen Companion plugin to enable forms, dashboards, smart matching, and the live tutor marketplace.', 'beyondinfinity' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Admin notice when companion plugin is missing.
 */
function bi_beyondinfinity_plugin_notice() {
	if ( bi_companion_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && in_array( $screen->id, [ 'themes', 'dashboard' ], true ) ) {
		echo '<div class="notice notice-info is-dismissible"><p><strong>BeyondInfinity:</strong> ';
		esc_html_e( 'For full functionality (forms, dashboards, live tutor marketplace), install the NextGen Companion plugin. The theme works without it using static fallbacks.', 'beyondinfinity' );
		echo ' <a href="' . esc_url( admin_url( 'themes.php?page=bi-health' ) ) . '">' . esc_html__( 'View health', 'beyondinfinity' ) . '</a>';
		echo '</p></div>';
	}
}
add_action( 'admin_notices', 'bi_beyondinfinity_plugin_notice' );
