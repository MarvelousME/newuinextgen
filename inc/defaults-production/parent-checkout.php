<?php
/**
 * Parent checkout — PayFast redirect for lesson credits.
 *
 * @package BeyondInfinity
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	$login = add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), home_url( '/login/' ) );
	echo '<section class="ngt-section"><div class="ngt-container bi-narrow"><div class="ngt-card" style="padding:32px;text-align:center">';
	echo '<p>' . esc_html__( 'Please sign in to pay for a lesson.', 'beyondinfinity' ) . '</p>';
	echo '<a class="ngt-btn ngt-btn--primary" href="' . esc_url( $login ) . '">' . esc_html__( 'Sign in', 'beyondinfinity' ) . '</a>';
	echo '</div></div></section>';
	return;
}

if ( shortcode_exists( 'ngc_parent_checkout' ) ) {
	echo do_shortcode( '[ngc_parent_checkout]' );
} else {
	echo '<p class="ngc-checkout-notice">' . esc_html__( 'Online checkout is unavailable.', 'beyondinfinity' ) . '</p>';
}
