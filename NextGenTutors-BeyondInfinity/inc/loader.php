<?php
/**
 * Premium page transition loader — rotating branded loading experiences.
 *
 * Behaviour:
 *  - Overlay markup prints at the very top of <body> (wp_body_open) so it
 *    paints before content; content layout beneath is untouched (zero CLS).
 *  - A tiny inline head script hides the overlay instantly for repeat views
 *    in the same session and for reduced-motion users (static fade only).
 *  - assets/js/bi-loader.js picks a random variant (avoiding immediate
 *    repeats), waits for window.load + fonts, then fades out and removes the
 *    overlay, setting body.bi-loaded to gate hero entrance animations.
 *  - Failsafe: overlay force-fades after 2.5s even if load events stall.
 *
 * Spec: documentation/ux-redesign/04-motion-spec.md §5
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'bi_loader_enqueue_assets', 8 );
add_action( 'wp_body_open', 'bi_loader_render_overlay', 1 );

/**
 * Whether the loader should render for this request.
 *
 * @return bool
 */
function bi_loader_enabled() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return false;
	}
	/**
	 * Filter to disable the page loader (e.g. for performance testing).
	 *
	 * @param bool $enabled Default true.
	 */
	return (bool) apply_filters( 'bi_page_loader_enabled', true );
}

/**
 * Enqueue loader assets early so the overlay is styled on first paint.
 */
function bi_loader_enqueue_assets() {
	if ( ! bi_loader_enabled() ) {
		return;
	}

	wp_enqueue_style( 'bi-loader', BI_URI . '/assets/css/bi-loader.css', [], BI_VERSION );
	wp_enqueue_script( 'bi-loader', BI_URI . '/assets/js/bi-loader.js', [], BI_VERSION, true );

	// Skip the full overlay for same-session repeat views and reduced motion —
	// decided before first paint to guarantee the overlay never flashes.
	$inline = 'try{if(sessionStorage.getItem("biLoaderSeen")==="1"||window.matchMedia("(prefers-reduced-motion: reduce)").matches){document.documentElement.classList.add("bi-loader-skip");}}catch(e){}';
	wp_add_inline_script( 'bi-loader', $inline, 'before' );
	// The "before" position still runs in footer; mirror it in head for first paint.
	add_action( 'wp_head', 'bi_loader_print_head_script', 0 );
}

/**
 * Head-position gate script (runs before any paint).
 */
function bi_loader_print_head_script() {
	echo '<script>try{if(sessionStorage.getItem("biLoaderSeen")==="1"||window.matchMedia("(prefers-reduced-motion: reduce)").matches){document.documentElement.classList.add("bi-loader-skip");}}catch(e){}</script>' . "\n";
}

/**
 * Overlay markup. Variants ship in the DOM (tiny — pure CSS shapes / one canvas);
 * bi-loader.js activates exactly one via data-bi-loader-variant.
 */
function bi_loader_render_overlay() {
	if ( ! bi_loader_enabled() ) {
		return;
	}
	?>
	<div id="bi-loader" class="bi-loader" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Loading', 'beyondinfinity' ); ?>">
		<div class="bi-loader__stage">
			<div class="bi-loader__variant bi-loader__variant--constellation" aria-hidden="true">
				<canvas class="bi-loader__canvas" width="280" height="180"></canvas>
			</div>
			<div class="bi-loader__variant bi-loader__variant--orb" aria-hidden="true">
				<div class="bi-loader__orb"><span class="bi-loader__orb-ring"></span></div>
			</div>
			<div class="bi-loader__variant bi-loader__variant--wave" aria-hidden="true">
				<div class="bi-loader__wave"></div>
			</div>
			<div class="bi-loader__variant bi-loader__variant--nodes" aria-hidden="true">
				<div class="bi-loader__nodes">
					<span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
				</div>
			</div>
			<div class="bi-loader__variant bi-loader__variant--pulse" aria-hidden="true"></div>
			<div class="bi-loader__variant bi-loader__variant--aurora" aria-hidden="true">
				<div class="bi-loader__aurora"></div>
			</div>
			<div class="bi-loader__variant bi-loader__variant--ring" aria-hidden="true">
				<div class="bi-loader__ring">
					<span class="bi-loader__ring-outer"></span>
					<span class="bi-loader__ring-inner"></span>
				</div>
			</div>
			<div class="bi-loader__variant bi-loader__variant--spark" aria-hidden="true">
				<div class="bi-loader__spark"><span></span><span></span><span></span></div>
			</div>
			<p class="bi-loader__brand">NextGen<span>Tutors</span></p>
		</div>
	</div>
	<div id="bi-loader-bar" class="bi-loader-bar" aria-hidden="true"></div>
	<?php
}
