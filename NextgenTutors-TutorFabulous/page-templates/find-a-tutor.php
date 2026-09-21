<?php
/**
 * Template Name: Find a Tutor
 */
defined( 'ABSPATH' ) || exit;
get_template_part( 'template-parts/head', null, [ 'title' => 'Find a Tutor — NextGen Tutors', 'desc' => 'Browse SACE-registered tutors by subject, format and budget across South Africa.', 'page' => 'tutors' ] );
?>
<section class="pagehead">
  <div class="pagehead__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap pagehead__inner">
    <div class="pagehead__crumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg> <span>Find a Tutor</span></div>
    <span class="eyebrow pagehead__eyebrow">500+ Verified Educators</span>
    <h1 class="pagehead__title">Find Your <span class="accent">Perfect</span> Tutor</h1>
    <p class="pagehead__sub">Filter by subject, teaching format and budget. Every tutor is SACE-registered, ID-verified and background-checked.</p>
  </div>
</section>
<section class="section">
  <div class="wrap directory">
    <aside class="filters" id="filters">
      <div class="filters__h"><i data-lucide="sliders-horizontal"></i> Refine Results</div>
      <div class="filter-group"><div class="filter-group__label">Subject</div>
        <div class="filter-chips" id="filter-subjects">
          <?php foreach ( (array) get_terms(['taxonomy'=>'subject','hide_empty'=>false]) as $t ) : if(is_wp_error($t)) continue; ?>
          <button class="fchip" data-subject="<?php echo esc_attr($t->slug); ?>"><?php echo esc_html($t->name); ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="filter-group"><div class="filter-group__label">Format</div>
        <div class="filter-chips" id="filter-format">
          <button class="fchip is-active" data-format="all">All</button>
          <button class="fchip" data-format="online">Online</button>
          <button class="fchip" data-format="personal">In-Person</button>
        </div>
      </div>
      <div class="filter-group"><div class="filter-group__label">Max Rate / Hour</div>
        <input type="range" class="filter-range" id="filter-price" min="300" max="500" step="10" value="500" />
        <div class="filter-range-val" id="price-val">R500</div>
      </div>
      <div class="filter-group"><div class="filter-group__label">Province</div>
        <select id="filter-province" style="width:100%;padding:10px 14px;border:2px solid var(--slate-200);border-radius:var(--r-md);font-size:13px;font-weight:700">
          <option value="">All Provinces</option>
          <?php foreach ( (array) get_terms(['taxonomy'=>'province','hide_empty'=>false]) as $pv ) : if(is_wp_error($pv)) continue; ?>
          <option value="<?php echo esc_attr($pv->slug); ?>"><?php echo esc_html($pv->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn--ghost btn--block" id="clear-filters" style="background:var(--slate-100);color:var(--navy);border-color:var(--slate-200);margin-top:8px">Clear Filters</button>
    </aside>
    <div>
      <div class="dir-bar">
        <div class="dir-count"><b id="result-count">0</b> tutors match your search</div>
        <div class="filter-chips">
          <button class="fchip is-active" data-sort="rating">Top Rated</button>
          <button class="fchip" data-sort="price-low">Price ↑</button>
          <button class="fchip" data-sort="price-high">Price ↓</button>
        </div>
      </div>
      <div class="dir-grid" id="dir-grid">
        <?php
        $q = new WP_Query(['post_type'=>'tutors','post_status'=>'publish','posts_per_page'=>12,'meta_key'=>'tutor_average_rating','orderby'=>'meta_value_num','order'=>'DESC']);
        while($q->have_posts()){$q->the_post(); get_template_part('template-parts/tutor-card',null,['post_id'=>get_the_ID()]);}
        wp_reset_postdata();
        ?>
      </div>
      <div style="text-align:center;margin-top:32px"><button class="btn btn--ghost" id="load-more" data-page="2" style="display:none">Load More Tutors</button></div>
    </div>
  </div>
</section>
<section class="section section--tight" style="padding-top:0">
  <div class="wrap">
    <div class="cta-band" data-reveal-scale>
      <div class="cta-band__glow"></div>
      <div class="cta-band__c">
        <span class="eyebrow" style="color:var(--lime)">Not sure who to pick?</span>
        <h2 class="h-serif" style="color:#fff;font-size:clamp(26px,3.4vw,40px);margin:12px 0 10px">Let Our Matchmaker Help</h2>
        <p class="lead" style="color:rgba(255,255,255,0.7);max-width:540px">Tell us your subject, grade and goals — we'll hand-match you to the ideal vetted tutor, free of charge.</p>
      </div>
      <a class="btn btn--lime btn--shine" href="<?php echo esc_url(ngt_get_page_url('contact')); ?>" style="position:relative;z-index:1">Get Matched Free</a>
    </div>
  </div>
</section>
<?php get_template_part('template-parts/footer-close'); ?>
