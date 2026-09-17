<?php
/**
 * Role CTAs — parent Find a Tutor + tutor Become a Tutor.
 *
 * @package TutorFabulous
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = isset( $args ) && is_array( $args ) ? $args : [];
$primary_label = (string) ( $args['primary_label'] ?? __( 'Find a Tutor', 'beyondinfinity' ) );
$primary_url   = (string) ( $args['primary_url'] ?? home_url( '/find-a-tutor/' ) );
$secondary_label = (string) ( $args['secondary_label'] ?? __( 'Become a Tutor', 'beyondinfinity' ) );
$secondary_url   = (string) ( $args['secondary_url'] ?? home_url( '/become-a-tutor/' ) );
?>
<div class="tf-module tf-role-cta">
	<a class="ngt-btn ngt-btn--primary tf-btn" href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?></a>
	<a class="ngt-btn ngt-btn--outline tf-btn tf-btn--outline" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?></a>
</div>
