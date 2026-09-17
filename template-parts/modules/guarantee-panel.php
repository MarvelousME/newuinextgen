<?php
/**
 * Compact first-lesson guarantee panel (reuse on find-a-tutor, become, pricing).
 *
 * @package TutorFabulous
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args      = isset( $args ) && is_array( $args ) ? $args : [];
$sla       = function_exists( 'bi_policy_sla_labels' ) ? bi_policy_sla_labels() : [ 'claim_window' => '24h' ];
$claim     = (string) ( $sla['claim_window'] ?? '24h' );
$g_label   = function_exists( 'bi_guarantee_label' ) ? bi_guarantee_label() : 'NextGen100';
$title     = (string) ( $args['title'] ?? __( 'First lesson is risk-free', 'beyondinfinity' ) );
$copy      = (string) ( $args['copy'] ?? sprintf(
	/* translators: 1: claim window, 2: guarantee label */
	__( 'Love the first lesson or we rematch — or refund the first hour. Tell us within %1$s. %2$s.', 'beyondinfinity' ),
	$claim,
	$g_label
) );
$cta_url   = (string) ( $args['cta_url'] ?? home_url( '/guarantee/' ) );
$cta_label = (string) ( $args['cta_label'] ?? __( 'Read the guarantee', 'beyondinfinity' ) );
?>
<section class="tf-module tf-guarantee ngt-section" aria-labelledby="tf-guarantee-title">
	<div class="tf-module__inner ngt-container">
		<div class="tf-guarantee__panel tf-card">
			<p class="tf-guarantee__eyebrow"><?php esc_html_e( 'NextGen guarantee', 'beyondinfinity' ); ?></p>
			<h2 id="tf-guarantee-title" class="tf-guarantee__title"><?php echo esc_html( $title ); ?></h2>
			<p class="tf-guarantee__copy"><?php echo esc_html( $copy ); ?></p>
			<div class="tf-guarantee__actions">
				<a class="ngt-btn ngt-btn--primary tf-btn" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
			</div>
		</div>
	</div>
</section>
