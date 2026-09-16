<?php
/**
 * Template Name: Blog
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/head');
get_template_part('template-parts/nav');

$paged    = max(1, get_query_var('paged'));
$per_page = 9;

$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

// Category filter from ?cat= param
if (!empty($_GET['cat']) && is_numeric($_GET['cat'])) {
    $args['cat'] = (int) $_GET['cat'];
}
// Search filter from ?s= param
if (!empty($_GET['s'])) {
    $args['s'] = sanitize_text_field($_GET['s']);
}

$blog_query = new WP_Query($args);
$categories = get_categories(['hide_empty' => true, 'number' => 20]);
?>

<!-- HERO -->
<section class="blog-hero section" aria-labelledby="blog-hero-title">
  <div class="container blog-hero__inner">
    <span class="badge badge--lime">Resources &amp; Tips</span>
    <h1 id="blog-hero-title">The NextGen Tutors Blog</h1>
    <p>Study tips, subject guides, tutor advice, and education news — curated for South African students and parents.</p>
    <form class="blog-search" action="<?php echo esc_url(get_permalink()); ?>" method="get" role="search" aria-label="Search blog posts">
      <input type="search" name="s" class="blog-search__input" placeholder="Search articles..." value="<?php echo esc_attr(get_query_var('s', $_GET['s'] ?? '')); ?>" aria-label="Search">
      <button type="submit" class="btn btn--lime" aria-label="Submit search"><i data-lucide="search" aria-hidden="true"></i></button>
    </form>
  </div>
</section>

<!-- CATEGORY FILTER -->
<?php if (!empty($categories)) : ?>
<nav class="blog-cats" aria-label="Filter by category">
  <div class="container blog-cats__inner">
    <a href="<?php echo esc_url(get_permalink()); ?>" class="cat-chip<?php echo empty($_GET['cat']) ? ' cat-chip--active' : ''; ?>">All</a>
    <?php foreach ($categories as $cat) : ?>
      <a href="<?php echo esc_url(add_query_arg('cat', $cat->term_id, get_permalink())); ?>"
         class="cat-chip<?php echo (isset($_GET['cat']) && (int)$_GET['cat'] === $cat->term_id) ? ' cat-chip--active' : ''; ?>"
         aria-current="<?php echo (isset($_GET['cat']) && (int)$_GET['cat'] === $cat->term_id) ? 'page' : 'false'; ?>">
        <?php echo esc_html($cat->name); ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<!-- POSTS GRID -->
<section class="blog-grid-section section" aria-labelledby="blog-posts-title">
  <div class="container">
    <?php if (!empty($_GET['s'])) : ?>
      <p class="blog-search-result" role="status">
        Results for <strong><?php echo esc_html($_GET['s']); ?></strong>
        — <?php echo (int) $blog_query->found_posts; ?> article<?php echo $blog_query->found_posts !== 1 ? 's' : ''; ?> found
      </p>
    <?php endif; ?>

    <h2 id="blog-posts-title" class="sr-only">Blog posts</h2>

    <?php if ($blog_query->have_posts()) : ?>
      <ul class="blog-grid" role="list">
        <?php while ($blog_query->have_posts()) : $blog_query->the_post(); ?>
          <li class="blog-card" role="listitem">
            <?php if (has_post_thumbnail()) : ?>
              <a href="<?php the_permalink(); ?>" class="blog-card__thumb" aria-hidden="true" tabindex="-1">
                <?php the_post_thumbnail('medium_large', ['alt' => '']); ?>
              </a>
            <?php else : ?>
              <div class="blog-card__thumb blog-card__thumb--placeholder" aria-hidden="true">
                <i data-lucide="book-open"></i>
              </div>
            <?php endif; ?>
            <div class="blog-card__body">
              <?php
              $cat = get_the_category();
              if (!empty($cat)) :
              ?>
                <a href="<?php echo esc_url(get_category_link($cat[0]->term_id)); ?>" class="blog-card__cat"><?php echo esc_html($cat[0]->name); ?></a>
              <?php endif; ?>
              <h3 class="blog-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h3>
              <p class="blog-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '…'); ?></p>
              <footer class="blog-card__meta">
                <span class="blog-card__author">
                  <?php echo get_avatar(get_the_author_meta('ID'), 24, '', '', ['class' => 'blog-card__avatar']); ?>
                  <?php the_author(); ?>
                </span>
                <time class="blog-card__date" datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date('j M Y'); ?></time>
              </footer>
            </div>
          </li>
        <?php endwhile; ?>
      </ul>

      <!-- PAGINATION -->
      <?php if ($blog_query->max_num_pages > 1) : ?>
        <nav class="blog-pagination" aria-label="Blog pagination">
          <?php
          echo paginate_links([
            'total'     => $blog_query->max_num_pages,
            'current'   => $paged,
            'prev_text' => '<i data-lucide="chevron-left" aria-hidden="true"></i><span class="sr-only">Previous</span>',
            'next_text' => '<span class="sr-only">Next</span><i data-lucide="chevron-right" aria-hidden="true"></i>',
            'type'      => 'list',
          ]);
          ?>
        </nav>
      <?php endif; ?>

    <?php else : ?>
      <div class="blog-empty" role="status">
        <i data-lucide="file-search" aria-hidden="true"></i>
        <h3>No articles found</h3>
        <?php if (!empty($_GET['s'])) : ?>
          <p>Try a different search term or <a href="<?php echo esc_url(get_permalink()); ?>">browse all articles</a>.</p>
        <?php else : ?>
          <p>Check back soon — new content is published weekly.</p>
        <?php endif; ?>
      </div>
    <?php endif;
    wp_reset_postdata(); ?>
  </div>
</section>

<!-- NEWSLETTER -->
<section class="blog-newsletter section section--alt" aria-labelledby="blog-nl-title">
  <div class="container blog-newsletter__inner">
    <div>
      <h2 id="blog-nl-title">Get Study Tips in Your Inbox</h2>
      <p>Weekly articles on studying smarter, subject guides, and tutor spotlights.</p>
    </div>
    <?php if (shortcode_exists('mc4wp_form')) : ?>
      <?php echo do_shortcode('[mc4wp_form id="1"]'); ?>
    <?php else : ?>
      <form class="nl-form" action="#" method="post" aria-label="Newsletter signup">
        <input type="email" name="EMAIL" placeholder="Your email address" required class="nl-form__input" aria-label="Email address">
        <button type="submit" class="btn btn--lime">Subscribe</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<script>
if (window.lucide) lucide.createIcons();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
