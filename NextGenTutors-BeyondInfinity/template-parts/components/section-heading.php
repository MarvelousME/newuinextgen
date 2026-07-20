<?php
/**
 * Component: section heading (eyebrow + title + sub).
 *
 * @param array $args { eyebrow, title, sub, align }
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ngt_a   = wp_parse_args(
	$args,
	array(
		'eyebrow' => '',
		'title'   => '',
		'sub'     => '',
		'align'   => 'center',
	)
);
$ngt_cls = 'ngt-heading ngt-heading-' . ( 'left' === $ngt_a['align'] ? 'left' : 'center' );
?>
<div class="<?php echo esc_attr( $ngt_cls ); ?>">
	<?php if ( $ngt_a['eyebrow'] ) : ?>
		<span class="ngt-eyebrow"><?php echo esc_html( $ngt_a['eyebrow'] ); ?></span>
	<?php endif; ?>
	<?php if ( $ngt_a['title'] ) : ?>
		<h2 class="ngt-heading-title"><?php echo esc_html( $ngt_a['title'] ); ?></h2>
	<?php endif; ?>
	<?php if ( $ngt_a['sub'] ) : ?>
		<p class="ngt-heading-sub"><?php echo esc_html( $ngt_a['sub'] ); ?></p>
	<?php endif; ?>
</div>
