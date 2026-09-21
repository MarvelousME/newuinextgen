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

?>
<section class="ngt-section bi-checkout-trust" aria-labelledby="bi-checkout-trust-title">
	<div class="ngt-container bi-narrow">
		<div class="bi-trust-inject ngt-animate" role="note">
			<h2 id="bi-checkout-trust-title"><?php esc_html_e( 'Your first lesson is protected', 'beyondinfinity' ); ?></h2>
			<p><?php esc_html_e( 'If the first lesson is not the right fit, NextGen100 gives you a rematch or a full refund under the guarantee terms.', 'beyondinfinity' ); ?></p>
			<div class="bi-trust-chip-row">
				<?php bi_trust_chip( __( 'Read the NextGen100 guarantee', 'beyondinfinity' ), home_url( '/guarantee/' ), [ 'icon' => 'check' ] ); ?>
			</div>
		</div>
	</div>
</section>
<?php

if ( shortcode_exists( 'ngc_parent_checkout' ) ) {
	echo do_shortcode( '[ngc_parent_checkout]' );
} else {
	echo '<p class="ngc-checkout-notice">' . esc_html__( 'Online checkout is unavailable.', 'beyondinfinity' ) . '</p>';
}
