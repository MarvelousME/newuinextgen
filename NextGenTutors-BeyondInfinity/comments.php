<?php
/**
 * Comments template.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="ngt-comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="ngt-comments-title">
			<?php
			$ngt_count = get_comments_number();
			printf(
				esc_html( _n( '%s Comment', '%s Comments', $ngt_count, 'nextgen-tutors' ) ),
				esc_html( number_format_i18n( $ngt_count ) )
			);
			?>
		</h2>

		<ol class="ngt-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 44,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( 'Previous', 'nextgen-tutors' ),
				'next_text' => esc_html__( 'Next', 'nextgen-tutors' ),
			)
		);
		?>
	<?php endif; ?>

	<?php
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) {
		echo '<p class="ngt-no-comments">' . esc_html__( 'Comments are closed.', 'nextgen-tutors' ) . '</p>';
	}

	comment_form();
	?>
</div>
