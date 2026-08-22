<?php
/**
 * Default — About (Our Story / Mission / Values from NGT Design UI PDFs).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand = function_exists( 'bi_brand_content' ) ? bi_brand_content() : [];

bi_hero(
	__( "Connecting South Africa's Best Tutors with Students Who Need Them", 'beyondinfinity' ),
	$brand['positioning'] ?? __( 'NextGen Tutors was built to fix a broken system — where quality tutoring was expensive, hard to find, and unevenly distributed across provinces. We changed that.', 'beyondinfinity' )
);

if ( function_exists( 'bi_render_brand_story_sections' ) ) {
	bi_render_brand_story_sections();
} else {
	?>
	<section class="ngt-section">
		<div class="ngt-container">
			<h2><?php esc_html_e( 'Our story', 'beyondinfinity' ); ?></h2>
			<p><?php esc_html_e( 'NextGen Tutors connects verified tutors with learners across South Africa.', 'beyondinfinity' ); ?></p>
		</div>
	</section>
	<?php
}

bi_parallax_cta(
	__( 'Every struggling learner deserves the right approach.', 'beyondinfinity' ),
	__( 'Find a Tutor', 'beyondinfinity' ),
	home_url( '/find-a-tutor' )
);
