<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

bi_hero(
	__( 'Safe Tutoring Guidelines', 'beyondinfinity' ),
	__( 'Industry-leading safety measures for every NextGen tutoring session — so families do not have to worry.', 'beyondinfinity' ),
	[ 'variant' => 'trust' ]
);
?>

<section class="ngt-section" id="sec-safety-toc">
	<div class="ngt-container bi-narrow">
		<?php
		bi_page_toc(
			[
				[ 'id' => 'sec-safety-parents', 'label' => __( 'Parents', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-students', 'label' => __( 'Students', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-tutors', 'label' => __( 'Tutors', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-online', 'label' => __( 'Online', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-inperson', 'label' => __( 'In-person', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-reporting', 'label' => __( 'Reporting', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-emergency', 'label' => __( 'Emergency', 'beyondinfinity' ) ],
				[ 'id' => 'sec-safety-faq', 'label' => __( 'Common questions', 'beyondinfinity' ) ],
			]
		);
		?>
		<div class="ngt-card bi-intro-card">
			<p><?php esc_html_e( 'We understand that inviting someone into your child\'s learning journey requires trust. NextGen Tutors has built safety systems to give South African families peace of mind.', 'beyondinfinity' ); ?></p>
			<p><?php esc_html_e( 'From ID-verified tutors to monitored sessions and full parent oversight, we have thought of everything so you do not have to.', 'beyondinfinity' ); ?></p>
		</div>
		<?php
		bi_info_card(
			__( 'Platform Security', 'beyondinfinity' ),
			[
				__( 'Encrypted sessions — end-to-end encrypted video and audio', 'beyondinfinity' ),
				__( 'Secure payments — PCI-compliant processing via trusted SA providers', 'beyondinfinity' ),
				__( 'POPIA compliant — full protection of personal information', 'beyondinfinity' ),
				__( 'Optional recording — session recording for parent review, with consent', 'beyondinfinity' ),
			]
		);
		?>
	</div>
</section>

<section class="ngt-section ngt-section--alt" id="sec-safety-parents">
	<div class="ngt-container">
		<div class="ngt-section__header">
			<h2><?php esc_html_e( 'Parent Oversight', 'beyondinfinity' ); ?></h2>
			<p><?php esc_html_e( 'Parents should always be able to see, observe and control how tutoring happens.', 'beyondinfinity' ); ?></p>
		</div>
		<?php
		bi_value_cards(
			[
				[ 'icon' => 'chart', 'title' => __( 'Dashboard Access', 'beyondinfinity' ), 'text' => __( 'View session history, tutor feedback, progress reports, attendance and recordings.', 'beyondinfinity' ) ],
				[ 'icon' => 'users', 'title' => __( 'Real-Time Features', 'beyondinfinity' ), 'text' => __( 'Join sessions as a silent observer, get start/end notifications and direct tutor messaging.', 'beyondinfinity' ) ],
				[ 'icon' => 'layout', 'title' => __( 'Control Options', 'beyondinfinity' ), 'text' => __( 'Approve or decline tutors, set scheduling parameters and pause or cancel anytime.', 'beyondinfinity' ) ],
			]
		);
		?>
	</div>
</section>

<section class="ngt-section" id="sec-safety-students">
	<div class="ngt-container bi-narrow">
		<div class="ngt-card bi-intro-card">
			<h2><?php esc_html_e( 'For students', 'beyondinfinity' ); ?></h2>
			<?php
			bi_bullets(
				[
					__( 'Platform messaging should be used for scheduling and academic communication.', 'beyondinfinity' ),
					__( 'No tutor should request unnecessary personal information from a learner.', 'beyondinfinity' ),
					__( 'Online sessions should occur in an appropriate shared space where possible.', 'beyondinfinity' ),
				]
			);
			?>
			<p><a href="#sec-safety-reporting"><?php esc_html_e( 'If something feels wrong, use Reporting below.', 'beyondinfinity' ); ?></a></p>
		</div>
	</div>
</section>

<section class="ngt-section ngt-section--alt" id="sec-safety-tutors">
	<div class="ngt-container bi-narrow">
		<div class="ngt-card bi-intro-card">
			<h2><?php esc_html_e( 'For tutors', 'beyondinfinity' ); ?></h2>
			<p><?php esc_html_e( 'Tutors undergo manual review before matching. We expect professional conduct, appropriate communication, and immediate reporting of any safeguarding concern.', 'beyondinfinity' ); ?></p>
			<?php bi_safety_notice( 'tutor' ); ?>
			<p><a href="<?php echo esc_url( home_url( '/tutor-vetting/' ) ); ?>"><?php esc_html_e( 'How We Vet Tutors', 'beyondinfinity' ); ?></a></p>
		</div>
	</div>
</section>

<section class="ngt-section" id="sec-safety-online">
	<div class="ngt-container">
		<div class="bi-grid-2">
			<div class="ngt-card bi-intro-card" id="sec-safety-online-card">
				<h2><?php esc_html_e( 'Online sessions', 'beyondinfinity' ); ?></h2>
				<?php
				bi_bullets(
					[
						__( 'Online sessions should occur in an appropriate shared space where possible.', 'beyondinfinity' ),
						__( 'Session recording is optional and disabled by default. Parents can enable it in their dashboard. All recordings are encrypted and stored securely.', 'beyondinfinity' ),
						__( 'Join sessions as a silent observer, get start/end notifications and direct tutor messaging.', 'beyondinfinity' ),
					]
				);
				?>
			</div>
			<div class="ngt-card bi-intro-card" id="sec-safety-inperson">
				<h2><?php esc_html_e( 'In-person sessions', 'beyondinfinity' ); ?></h2>
				<?php
				bi_bullets(
					[
						__( 'In-person sessions require a guardian present for minors.', 'beyondinfinity' ),
						__( 'Guardians are responsible for supervising tutoring arrangements, especially in-person sessions in the home.', 'beyondinfinity' ),
					]
				);
				?>
				<p>
					<a href="<?php echo esc_url( home_url( '/child-safety/' ) ); ?>"><?php esc_html_e( 'Read Child Safety Policy', 'beyondinfinity' ); ?></a>
					·
					<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms of Service', 'beyondinfinity' ); ?></a>
				</p>
			</div>
		</div>
	</div>
</section>

<section class="ngt-section ngt-section--alt" id="sec-safety-session-verify">
	<div class="ngt-container">
		<div class="ngt-section__header"><h2><?php esc_html_e( 'Session Safety', 'beyondinfinity' ); ?></h2></div>
		<?php
		bi_value_cards(
			[
				[ 'icon' => 'shield', 'title' => __( 'AI Moderation', 'beyondinfinity' ), 'text' => __( 'Automated systems flag concerning interactions in real time for human review.', 'beyondinfinity' ) ],
				[ 'icon' => 'clipboard', 'title' => __( 'Quality Audits', 'beyondinfinity' ), 'text' => __( 'Our team conducts random reviews of session recordings to uphold standards.', 'beyondinfinity' ) ],
				[ 'icon' => 'phone', 'title' => __( '24/7 Response Team', 'beyondinfinity' ), 'text' => __( 'A dedicated safety team responds to every report within two hours, day or night.', 'beyondinfinity' ) ],
			]
		);
		?>
	</div>
</section>

<section class="ngt-section" id="sec-safety-badges">
	<div class="ngt-container bi-narrow">
		<div class="ngt-section__header">
			<h2><?php esc_html_e( 'Verification Badges', 'beyondinfinity' ); ?></h2>
			<p><a href="<?php echo esc_url( home_url( '/tutor-vetting/' ) ); ?>"><?php esc_html_e( 'Full badge list', 'beyondinfinity' ); ?></a></p>
		</div>
		<?php
		bi_badge_table(
			[
				[ 'badge' => __( 'ID Verified', 'beyondinfinity' ), 'desc' => __( 'Full identity confirmed with South African authorities.', 'beyondinfinity' ) ],
				[ 'badge' => __( 'Background Cleared', 'beyondinfinity' ), 'desc' => __( 'Clean criminal-record check via an accredited agency.', 'beyondinfinity' ) ],
				[ 'badge' => __( 'Reference Checked', 'beyondinfinity' ), 'desc' => __( 'Professional and personal references verified.', 'beyondinfinity' ) ],
				[ 'badge' => __( 'Training Complete', 'beyondinfinity' ), 'desc' => __( 'NextGen safety and ethics training passed.', 'beyondinfinity' ) ],
				[ 'badge' => __( 'Curriculum Trained', 'beyondinfinity' ), 'desc' => __( 'South African educational-system specialist.', 'beyondinfinity' ) ],
			]
		);
		?>
	</div>
</section>

<section class="ngt-section ngt-section--alt" id="sec-safety-faq">
	<div class="ngt-container bi-narrow">
		<div class="ngt-section__header"><h2><?php esc_html_e( 'Common Safety Questions', 'beyondinfinity' ); ?></h2></div>
		<?php
		bi_faq_list(
			[
				[
					'q' => __( 'How do you verify tutor identities?', 'beyondinfinity' ),
					'a' => __( 'We require South African ID documents, verified through official channels. Every tutor passes a comprehensive criminal background check via accredited SA screening agencies.', 'beyondinfinity' ),
				],
				[
					'q' => __( 'Can I monitor my child\'s sessions?', 'beyondinfinity' ),
					'a' => __( 'Yes. Parents can join any session as a silent observer, view session recordings, and receive detailed session notes after each meeting.', 'beyondinfinity' ),
				],
				[
					'q' => __( 'What happens if I have a safety concern?', 'beyondinfinity' ),
					'a' => __( 'Our dedicated safety team responds to all reports within two hours. Use the in-app report button, email safety@nextgentutors.co.za, or call our 24/7 hotline.', 'beyondinfinity' ),
				],
				[
					'q' => __( 'Are sessions recorded?', 'beyondinfinity' ),
					'a' => __( 'Session recording is optional and disabled by default. Parents can enable it in their dashboard. All recordings are encrypted and stored securely.', 'beyondinfinity' ),
				],
				[
					'q' => __( 'How do you protect my child\'s data?', 'beyondinfinity' ),
					'a' => __( 'We are fully POPIA compliant. Your data is encrypted, access-controlled and never shared with third parties without consent.', 'beyondinfinity' ),
				],
			]
		);
		?>
	</div>
</section>

<section class="ngt-section" id="sec-safety-reporting">
	<div class="ngt-container bi-narrow">
		<div class="ngt-card bi-intro-card">
			<h2><?php esc_html_e( 'Reporting', 'beyondinfinity' ); ?></h2>
			<p><?php esc_html_e( 'Our dedicated safety team responds to all reports within two hours. Use the in-app report button, email safety@nextgentutors.co.za, or call our 24/7 hotline.', 'beyondinfinity' ); ?></p>
			<p>
				<?php
				printf(
					esc_html__( 'Policy contacts: %1$s or %2$s.', 'beyondinfinity' ),
					'<a href="mailto:' . esc_attr( bi_get_support_email() ) . '">' . esc_html( bi_get_support_email() ) . '</a>',
					'<a href="' . esc_url( bi_phone_tel_href() ) . '">' . esc_html( bi_get_phone() ) . '</a>'
				);
				?>
			</p>
			<div class="bi-hero__actions">
				<a class="ngt-btn ngt-btn--outline" href="mailto:safety@nextgentutors.co.za"><?php esc_html_e( 'Email', 'beyondinfinity' ); ?></a>
				<a class="ngt-btn ngt-btn--outline" href="tel:08006398436"><?php esc_html_e( 'Call', 'beyondinfinity' ); ?></a>
				<a class="ngt-btn ngt-btn--outline" href="<?php echo esc_url( bi_whatsapp_url( __( 'Safety concern — please assist.', 'beyondinfinity' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'beyondinfinity' ); ?></a>
			</div>
		</div>
	</div>
</section>

<section class="ngt-section ngt-section--alt" id="sec-safety-emergency">
	<div class="ngt-container bi-narrow">
		<div class="ngt-card bi-emergency-card">
			<h2><?php esc_html_e( 'Emergency Safety Contacts', 'beyondinfinity' ); ?></h2>
			<p><?php esc_html_e( 'If you ever have a concern, reach our safety team instantly — we respond within two hours, guaranteed.', 'beyondinfinity' ); ?></p>
			<ul class="bi-bullets bi-bullets--light">
				<?php
				foreach (
					[
						[ 'icon' => 'phone', 'label' => __( 'Safety Hotline: 0800 639 8436', 'beyondinfinity' ), 'href' => 'tel:08006398436' ],
						[ 'icon' => 'mail', 'label' => __( 'Email: safety@nextgentutors.co.za', 'beyondinfinity' ), 'href' => 'mailto:safety@nextgentutors.co.za' ],
						[ 'icon' => 'message', 'label' => __( 'WhatsApp support', 'beyondinfinity' ), 'href' => bi_whatsapp_url( __( 'Safety concern — please assist.', 'beyondinfinity' ) ) ],
					] as $row
				) :
					?>
					<li>
						<span class="bi-bullets__mark bi-bullets__mark--light" aria-hidden="true"><?php echo bi_ui_icon( $row['icon'], 18 ); // phpcs:ignore ?></span>
						<span><a href="<?php echo esc_url( $row['href'] ); ?>"><?php echo esc_html( $row['label'] ); ?></a></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<p class="bi-center bi-safety-policy-cta">
			<a href="<?php echo esc_url( home_url( '/child-safety/' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Read Child Safety Policy', 'beyondinfinity' ); ?></a>
		</p>
		<?php
		bi_related_nav(
			[
				[ 'url' => home_url( '/child-safety/' ), 'label' => __( 'Child Safety Policy', 'beyondinfinity' ) ],
				[ 'url' => home_url( '/tutor-vetting/' ), 'label' => __( 'How We Vet Tutors', 'beyondinfinity' ) ],
				[ 'url' => home_url( '/support/' ), 'label' => __( 'Support', 'beyondinfinity' ) ],
			]
		);
		?>
	</div>
</section>
