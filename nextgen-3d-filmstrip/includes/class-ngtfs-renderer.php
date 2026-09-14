<?php
/**
 * Filmstrip HTML renderer — portrait / footer card composition.
 *
 * @package NextGen_3D_Filmstrip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Server-rendered filmstrip markup (works without JS as a horizontal list).
 */
final class NGTFS_Renderer {

	/**
	 * @param array<int, array<string, mixed>> $cards Cards.
	 * @param array<string, mixed>             $settings Settings.
	 */
	public static function render( array $cards, array $settings = [] ): string {
		if ( ! $cards ) {
			return '';
		}

		NGTFS_Assets::enqueue();

		$settings = wp_parse_args(
			$settings,
			[
				'title'     => __( 'Meet our stars', 'nextgen-3d-filmstrip' ),
				'subtitle'  => __( 'Drag, swipe, or use the arrows to explore.', 'nextgen-3d-filmstrip' ),
				'source'    => 'tutors',
				'autoplay'  => true,
				'loop'      => true,
				'class'     => '',
			]
		);

		$id = 'ngtfs-' . wp_unique_id();

		ob_start();
		?>
		<section
			class="ngtfs <?php echo esc_attr( (string) $settings['class'] ); ?>"
			id="<?php echo esc_attr( $id ); ?>"
			data-ngtfs
			data-source="<?php echo esc_attr( (string) $settings['source'] ); ?>"
			data-autoplay="<?php echo ! empty( $settings['autoplay'] ) ? '1' : '0'; ?>"
			data-loop="<?php echo ! empty( $settings['loop'] ) ? '1' : '0'; ?>"
			aria-roledescription="<?php esc_attr_e( 'carousel', 'nextgen-3d-filmstrip' ); ?>"
			aria-label="<?php echo esc_attr( (string) $settings['title'] ); ?>"
		>
			<?php if ( $settings['title'] || $settings['subtitle'] ) : ?>
				<header class="ngtfs__header">
					<?php if ( $settings['title'] ) : ?>
						<h2 class="ngtfs__title"><?php echo esc_html( (string) $settings['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $settings['subtitle'] ) : ?>
						<p class="ngtfs__subtitle"><?php echo esc_html( (string) $settings['subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<div class="ngtfs__viewport" data-ngtfs-viewport>
				<div class="ngtfs__stage" data-ngtfs-stage style="perspective: 1400px;">
					<?php foreach ( $cards as $i => $card ) : ?>
						<?php echo self::card_markup( $card, (int) $i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="ngtfs__controls">
				<button type="button" class="ngtfs__nav" data-ngtfs-prev aria-label="<?php esc_attr_e( 'Previous card', 'nextgen-3d-filmstrip' ); ?>">‹</button>
				<div class="ngtfs__dots" data-ngtfs-dots role="tablist" aria-label="<?php esc_attr_e( 'Choose card', 'nextgen-3d-filmstrip' ); ?>">
					<?php foreach ( $cards as $i => $card ) : ?>
						<button
							type="button"
							class="ngtfs__dot<?php echo 0 === (int) $i ? ' is-active' : ''; ?>"
							data-index="<?php echo esc_attr( (string) $i ); ?>"
							role="tab"
							aria-selected="<?php echo 0 === (int) $i ? 'true' : 'false'; ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'Card %d', 'nextgen-3d-filmstrip' ), $i + 1 ) ); ?>"
						></button>
					<?php endforeach; ?>
				</div>
				<button type="button" class="ngtfs__nav" data-ngtfs-next aria-label="<?php esc_attr_e( 'Next card', 'nextgen-3d-filmstrip' ); ?>">›</button>
			</div>

			<p class="screen-reader-text" aria-live="polite" aria-atomic="true" data-ngtfs-live></p>

			<!-- No-JS fallback: plain list remains visible via CSS when [data-ngtfs-enhanced] is absent -->
			<ul class="ngtfs__fallback" data-ngtfs-fallback>
				<?php foreach ( $cards as $card ) : ?>
					<li>
						<a href="<?php echo esc_url( (string) ( $card['url'] ?? '#' ) ); ?>">
							<?php echo esc_html( (string) ( $card['title'] ?? '' ) ); ?>
							<?php if ( ! empty( $card['subtitle'] ) ) : ?>
								— <?php echo esc_html( (string) $card['subtitle'] ); ?>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $card Card.
	 */
	private static function card_markup( array $card, int $index ): string {
		$type   = sanitize_key( (string) ( $card['type'] ?? 'tutor' ) );
		$title  = (string) ( $card['title'] ?? '' );
		$url    = (string) ( $card['url'] ?? '#' );
		$image  = (string) ( $card['image'] ?? '' );
		$accent = (string) ( $card['accent'] ?? '#059669' );
		$icon   = (string) ( $card['icon'] ?? 'book' );

		ob_start();
		?>
		<article
			class="ngtfs-card ngtfs-card--<?php echo esc_attr( $type ); ?>"
			data-ngtfs-card
			data-index="<?php echo esc_attr( (string) $index ); ?>"
			style="--ngtfs-accent: <?php echo esc_attr( $accent ); ?>;"
			tabindex="-1"
		>
			<a class="ngtfs-card__hit" href="<?php echo esc_url( $url ); ?>" tabindex="-1">
				<div class="ngtfs-card__portrait">
					<?php if ( $image ) : ?>
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" referrerpolicy="no-referrer" />
					<?php else : ?>
						<div class="ngtfs-card__glyph" aria-hidden="true"><?php echo self::icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $card['badge'] ) ) : ?>
						<span class="ngtfs-card__badge"><?php echo esc_html( (string) $card['badge'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $card['meta'] ) ) : ?>
						<span class="ngtfs-card__meta"><?php echo esc_html( (string) $card['meta'] ); ?></span>
					<?php endif; ?>
				</div>
				<footer class="ngtfs-card__footer">
					<div class="ngtfs-card__footer-top">
						<h3 class="ngtfs-card__name"><?php echo esc_html( $title ); ?></h3>
						<?php if ( ! empty( $card['rating'] ) ) : ?>
							<span class="ngtfs-card__rating">★ <?php echo esc_html( (string) $card['rating'] ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $card['subtitle'] ) ) : ?>
						<p class="ngtfs-card__sub"><?php echo esc_html( (string) $card['subtitle'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $card['body'] ) ) : ?>
						<p class="ngtfs-card__body"><?php echo esc_html( (string) $card['body'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $card['tags'] ) ) : ?>
						<div class="ngtfs-card__tags">
							<?php foreach ( (array) $card['tags'] as $tag ) : ?>
								<span><?php echo esc_html( (string) $tag ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<span class="ngtfs-card__cta"><?php echo 'subject' === $type ? esc_html__( 'Browse tutors', 'nextgen-3d-filmstrip' ) : esc_html__( 'View profile', 'nextgen-3d-filmstrip' ); ?> →</span>
				</footer>
			</a>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	private static function icon_svg( string $icon ): string {
		$paths = [
			'calculator' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8M8 10h2M14 10h2M8 14h2M14 14h2M8 18h2M14 18h2"/>',
			'book'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>',
			'atom'       => '<circle cx="12" cy="12" r="1"/><path d="M20.2 7.8c1.9 3.3-1.8 8.5-6.2 11s-10 1.6-11.8-1.7 1.8-8.5 6.2-11 10-1.6 11.8 1.7Z"/>',
			'flask'      => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 18l-5-9V3"/>',
			'leaf'       => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 4.4 20 5 20 5s.6 4.5-1.1 10.2A7 7 0 0 1 11 20Z"/>',
			'globe'      => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/>',
			'landmark'   => '<path d="m3 10 9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18"/>',
			'chart'      => '<path d="M3 3v18h18"/><path d="m7 16 4-5 4 3 5-7"/>',
			'code'       => '<path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/>',
			'message'    => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>',
		];
		$path = $paths[ $icon ] ?? $paths['book'];
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
	}
}
