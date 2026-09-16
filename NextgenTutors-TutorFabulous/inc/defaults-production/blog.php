<?php
/** Default — Blog hub (pages-to-review/blog.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Study Tips, Exam Prep & Subject Insights', 'beyondinfinity' ),
    __( 'Expert guides for CAPS, IEB and Cambridge — free for South African students and parents.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate">
      <p class="bi-eyebrow"><?php esc_html_e( 'Latest & featured', 'beyondinfinity' ); ?></p>
      <h2><?php esc_html_e( 'From the Blog', 'beyondinfinity' ); ?></h2>
    </div>
    <div class="bi-blog-feature ngt-animate">
      <?php $posts = bi_get_blog_posts(); $featured = $posts[0] ?? null; ?>
      <?php if ( $featured ) : ?>
        <article class="bi-blog-hero-card ngt-card">
          <?php if ( ! empty( $featured['image'] ) ) : ?>
            <img src="<?php echo esc_url( $featured['image'] ); ?>" alt="" class="bi-blog-hero-card__img" loading="lazy" />
          <?php endif; ?>
          <div class="bi-blog-hero-card__body">
            <span class="bi-blog-cat"><?php echo esc_html( $featured['category'] ); ?></span>
            <h3><?php echo esc_html( $featured['title'] ); ?></h3>
            <p class="bi-blog-meta"><?php echo esc_html( $featured['meta'] ); ?></p>
          </div>
        </article>
        <div class="bi-blog-side">
          <?php foreach ( array_slice( $posts, 1, 3 ) as $post ) : ?>
            <article class="bi-blog-mini ngt-card">
              <?php if ( ! empty( $post['image'] ) ) : ?>
                <img src="<?php echo esc_url( $post['image'] ); ?>" alt="" loading="lazy" />
              <?php endif; ?>
              <div>
                <span class="bi-blog-cat"><?php echo esc_html( $post['category'] ); ?></span>
                <h4><?php echo esc_html( $post['title'] ); ?></h4>
                <p class="bi-blog-meta"><?php echo esc_html( $post['meta'] ); ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate bi-center">
      <h2><?php esc_html_e( 'Browse by Topic', 'beyondinfinity' ); ?></h2>
    </div>
    <div class="bi-subj-grid">
      <?php foreach ( bi_get_blog_categories() as $i => $cat ) : ?>
        <div class="bi-subj-card ngt-animate ngt-animate--delay-<?php echo ( $i % 3 ) + 1; ?>">
          <span style="font-size:1.75rem"><?php echo esc_html( $cat['icon'] ); ?></span>
          <strong><?php echo esc_html( $cat['title'] ); ?></strong>
          <p style="margin:8px 0 0;font-size:.875rem;color:var(--ngt-text-2)"><?php echo esc_html( $cat['desc'] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="ngt-section bi-center">
  <div class="ngt-container ngt-animate">
    <p><?php esc_html_e( 'Full blog publishing connects when your WordPress posts or companion content hub is active.', 'beyondinfinity' ); ?></p>
    <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Get a Tutor Today', 'beyondinfinity' ); ?></a>
  </div>
</section>
