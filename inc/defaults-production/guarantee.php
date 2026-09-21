<?php
/** Default — 1st Lesson Guarantee (pages-to-review/guarantee.html) */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$marketing_kpis = bi_real_marketing_kpis();
$policy_sla     = bi_policy_sla_labels();
$claim_window   = (string) $policy_sla['claim_window'];
$rematch_window = (string) $policy_sla['rematch_window'];
$g_label        = function_exists( 'bi_guarantee_label' ) ? bi_guarantee_label() : bi_get_guarantee_code();
$satisfaction   = $marketing_kpis['satisfaction'];
$sat_empty      = bi_kpi_is_empty( $satisfaction );

bi_hero(
	__( 'Not Completely Satisfied? You Don’t Pay.', 'beyondinfinity' ),
	__( 'Love the lesson — or your first hour is on us. Every first lesson with NextGen Tutors is risk-free.', 'beyondinfinity' ),
	[
		'variant'              => 'trust',
		'class'                => 'bi-hero--guarantee',
		'eyebrow'              => sprintf(
			/* translators: %s: guarantee label e.g. NextGen100 */
			__( '%s first-lesson guarantee', 'beyondinfinity' ),
			$g_label
		),
		'chips'                => [
			[
				'label' => sprintf(
					/* translators: %s: claim window e.g. 24h */
					__( 'Claim within %s', 'beyondinfinity' ),
					$claim_window
				),
			],
			[
				'label' => sprintf(
					/* translators: %s: rematch window e.g. 48h */
					__( 'Rematch within %s', 'beyondinfinity' ),
					$rematch_window
				),
			],
			[
				'label' => __( '5-step vetting', 'beyondinfinity' ),
				'url'   => home_url( '/tutor-vetting/' ),
			],
		],
		'cta_label'            => __( 'Book a first lesson', 'beyondinfinity' ),
		'cta_url'              => home_url( '/find-a-tutor/' ),
		'cta_secondary_label'  => __( 'How to claim', 'beyondinfinity' ),
		'cta_secondary_url'    => '#claim',
	]
);
?>

<section class="ngt-section" id="sec-stats">
	<div class="ngt-container">
		<div class="bi-stat-grid" style="margin-bottom:0">
			<div class="bi-stat-card<?php echo $sat_empty ? ' bi-stat-card--empty' : ''; ?>">
				<?php if ( $sat_empty ) : ?>
					<p class="bi-stat-card__empty"><?php esc_html_e( 'We publish this when payment and match analytics are live.', 'beyondinfinity' ); ?></p>
				<?php else : ?>
					<div class="bi-stat-card__num"><?php echo esc_html( (string) $satisfaction ); ?></div>
				<?php endif; ?>
				<div class="bi-stat-card__label"><?php esc_html_e( 'Successful match/payment outcome', 'beyondinfinity' ); ?></div>
			</div>
			<div class="bi-stat-card">
				<div class="bi-stat-card__num"><?php echo esc_html( $claim_window ); ?></div>
				<div class="bi-stat-card__label"><?php esc_html_e( 'Claim window', 'beyondinfinity' ); ?></div>
			</div>
			<div class="bi-stat-card">
				<div class="bi-stat-card__num"><?php echo esc_html( $rematch_window ); ?></div>
				<div class="bi-stat-card__label"><?php esc_html_e( 'New tutor matched', 'beyondinfinity' ); ?></div>
			</div>
		</div>
	</div>
</section>

<?php
bi_steps(
	[
		[ 'title' => __( 'Book Your First Session', 'beyondinfinity' ), 'text' => __( 'Choose a verified tutor, pick a slot, and pay securely through the platform.', 'beyondinfinity' ) ],
		[ 'title' => __( 'Attend the Full Hour', 'beyondinfinity' ), 'text' => __( 'Experience how the tutor explains, engages and adapts to your learner.', 'beyondinfinity' ) ],
		[ 'title' => __( 'Assess the Fit', 'beyondinfinity' ), 'text' => __( 'Does your child connect? Was the explanation clear? Do you want to continue?', 'beyondinfinity' ) ],
		[
			'title' => sprintf(
				/* translators: %s: claim window */
				__( 'Decide within %s', 'beyondinfinity' ),
				$claim_window
			),
			'text'  => __( 'Love it — great. Not quite right? Contact us for a better match or a full refund.', 'beyondinfinity' ),
		],
	],
	__( 'How The Guarantee Works', 'beyondinfinity' ),
	__( 'Simple, honest process', 'beyondinfinity' )
);
?>

<section class="ngt-section ngt-section--alt" id="sec-qualify">
	<div class="ngt-container">
		<div class="ngt-section__header bi-center">
			<h2><?php esc_html_e( 'What Qualifies — and What Doesn’t', 'beyondinfinity' ); ?></h2>
		</div>
		<div class="bi-qual-grid">
			<div class="ngt-card bi-qual-card bi-qual-card--yes">
				<h3><?php esc_html_e( 'Qualifies for replacement or refund', 'beyondinfinity' ); ?></h3>
				<?php
				bi_bullets(
					[
						__( 'Teaching style mismatch — your child does not connect with how the tutor communicates', 'beyondinfinity' ),
						__( 'Difficulty level issues — too fast, too slow, or wrong grade pitch', 'beyondinfinity' ),
						__( 'Curriculum confusion — tutor not aligned with your school’s approach', 'beyondinfinity' ),
						__( 'Personality fit — the chemistry is not there', 'beyondinfinity' ),
						__( 'Technical problems that significantly impacted the session', 'beyondinfinity' ),
					]
				);
				?>
			</div>
			<div class="ngt-card bi-qual-card bi-qual-card--no">
				<h3><?php esc_html_e( 'Does not qualify', 'beyondinfinity' ); ?></h3>
				<?php
				bi_bullets(
					[
						__( 'Forgotten sessions or scheduling conflicts on your end', 'beyondinfinity' ),
						__( 'Deciding tutoring is not right for your child (unrelated to tutor quality)', 'beyondinfinity' ),
						sprintf(
							/* translators: %s: claim window */
							__( 'Claims made more than %s after the session', 'beyondinfinity' ),
							$claim_window
						),
						__( 'Cancellations less than 4 hours before the session', 'beyondinfinity' ),
					]
				);
				?>
			</div>
		</div>
	</div>
</section>

<section class="ngt-section" id="claim">
	<div class="ngt-container bi-narrow">
		<div class="ngt-card bi-claim-card">
			<h2><?php esc_html_e( 'How to Claim', 'beyondinfinity' ); ?></h2>
			<p>
				<?php
				printf(
					esc_html__( 'Email support within %1$s of your first lesson with your booking reference and a short note on what did not work. We will rematch you within %2$s or process a full refund.', 'beyondinfinity' ),
					esc_html( $claim_window ),
					esc_html( $rematch_window )
				);
				?>
			</p>
			<p class="bi-claim-card__ref"><strong><?php esc_html_e( 'Reference:', 'beyondinfinity' ); ?></strong> <?php echo esc_html( bi_get_guarantee_code() ); ?></p>
			<div class="bi-hero__actions bi-claim-card__actions">
				<a href="<?php echo esc_url( 'mailto:' . bi_get_support_email() ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Email Support', 'beyondinfinity' ); ?></a>
				<a href="<?php echo esc_url( bi_whatsapp_url( __( 'I need to claim the first-lesson guarantee.', 'beyondinfinity' ) ) ); ?>" class="ngt-btn ngt-btn--outline" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp Us', 'beyondinfinity' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/support/#ticket' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Open a support ticket', 'beyondinfinity' ); ?></a>
			</div>
		</div>
	</div>
</section>

<section class="ngt-section ngt-section--alt" id="sec-cta">
	<div class="ngt-container bi-center">
		<h2><?php esc_html_e( 'Ready to book a first lesson that is actually covered?', 'beyondinfinity' ); ?></h2>
		<?php
		if ( function_exists( 'bi_tf_module' ) ) {
			bi_tf_module(
				'role-cta',
				[
					'primary_label'   => __( 'Find a Tutor', 'beyondinfinity' ),
					'primary_url'     => home_url( '/find-a-tutor/' ),
					'secondary_label' => __( 'How we vet tutors', 'beyondinfinity' ),
					'secondary_url'   => home_url( '/tutor-vetting/' ),
				]
			);
		}
		?>
	</div>
</section>
