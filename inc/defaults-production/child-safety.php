<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = bi_get_support_email();
$phone = bi_get_phone();

bi_hero(
	__( 'Child Safety Policy', 'beyondinfinity' ),
	__( 'How NextGen Tutors protects learners under 18.', 'beyondinfinity' ),
	[ 'variant' => 'legal' ]
);
?>

<section class="ngt-section">
	<div class="ngt-container bi-legal">
		<?php
		bi_page_toc(
			[
				[ 'id' => 'commitment', 'label' => __( 'Commitment', 'beyondinfinity' ) ],
				[ 'id' => 'guardian', 'label' => __( 'Guardian', 'beyondinfinity' ) ],
				[ 'id' => 'tutors', 'label' => __( 'Tutors', 'beyondinfinity' ) ],
				[ 'id' => 'sessions', 'label' => __( 'Sessions', 'beyondinfinity' ) ],
				[ 'id' => 'reporting', 'label' => __( 'Reporting', 'beyondinfinity' ) ],
			]
		);
		?>

		<h2 id="commitment"><?php esc_html_e( 'Our Commitment', 'beyondinfinity' ); ?></h2>
		<p><?php esc_html_e( 'NextGen Tutors is committed to providing a safe environment for children and young people using our tutoring services.', 'beyondinfinity' ); ?></p>

		<h2 id="guardian"><?php esc_html_e( 'Parent & Guardian Responsibility', 'beyondinfinity' ); ?></h2>
		<p><?php esc_html_e( 'Learners under 18 must be registered by a parent or legal guardian. Guardians are responsible for supervising tutoring arrangements, especially in-person sessions in the home.', 'beyondinfinity' ); ?></p>

		<h2 id="tutors"><?php esc_html_e( 'Tutor Standards', 'beyondinfinity' ); ?></h2>
		<p><?php esc_html_e( 'Tutors undergo manual review before matching. We expect professional conduct, appropriate communication, and immediate reporting of any safeguarding concern.', 'beyondinfinity' ); ?></p>

		<h2 id="sessions"><?php esc_html_e( 'Session Safety', 'beyondinfinity' ); ?></h2>
		<?php
		bi_bullets(
			[
				__( 'In-person sessions require a guardian present for minors.', 'beyondinfinity' ),
				__( 'Online sessions should occur in an appropriate shared space where possible.', 'beyondinfinity' ),
				__( 'Platform messaging should be used for scheduling and academic communication.', 'beyondinfinity' ),
				__( 'No tutor should request unnecessary personal information from a learner.', 'beyondinfinity' ),
			]
		);
		?>

		<div class="bi-legal-report" id="reporting">
			<h2><?php esc_html_e( 'Reporting Concerns', 'beyondinfinity' ); ?></h2>
			<p>
				<?php
				printf(
					esc_html__( 'Report any safeguarding concern immediately to %1$s or call %2$s.', 'beyondinfinity' ),
					'<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>',
					'<a href="' . esc_url( bi_phone_tel_href( $phone ) ) . '">' . esc_html( $phone ) . '</a>'
				);
				?>
			</p>
			<p><a href="<?php echo esc_url( home_url( '/safety-guide/' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'View Safety Guide', 'beyondinfinity' ); ?></a></p>
		</div>

		<?php
		bi_related_nav(
			[
				[ 'url' => home_url( '/safety-guide/' ), 'label' => __( 'Safe Tutoring Guidelines', 'beyondinfinity' ) ],
				[ 'url' => home_url( '/tutor-vetting/' ), 'label' => __( 'How We Vet Tutors', 'beyondinfinity' ) ],
				[ 'url' => home_url( '/privacy-policy/' ), 'label' => __( 'Privacy Policy', 'beyondinfinity' ) ],
				[ 'url' => home_url( '/terms/' ), 'label' => __( 'Terms of Service', 'beyondinfinity' ) ],
				[ 'url' => home_url( '/register/?role=parent' ), 'label' => __( 'Register as a parent', 'beyondinfinity' ) ],
			]
		);
		?>
	</div>
</section>
