<?php
/**
 * Template Name: About
 *
 * Narrative sourced from NGT Design UI PDFs (Our Story, Mission & Values).
 */
defined( 'ABSPATH' ) || exit;

$brand = function_exists( 'bi_brand_content' ) ? bi_brand_content() : [];

get_template_part( 'template-parts/head' );
get_template_part( 'template-parts/nav' );
?>

<section class="about-hero section" aria-labelledby="about-hero-title">
  <div class="container about-hero__inner">
    <div class="about-hero__copy">
      <span class="badge badge--lime"><?php echo esc_html( $brand['story']['eyebrow'] ?? __( 'Our Story', 'beyondinfinity' ) ); ?></span>
      <h1 id="about-hero-title" data-bi-slide-title><?php echo esc_html( $brand['story']['title'] ?? __( "Connecting South Africa's best tutors with students who need them", 'beyondinfinity' ) ); ?></h1>
      <p><?php echo esc_html( $brand['story']['lead'] ?? '' ); ?></p>
      <p><?php echo esc_html( $brand['positioning'] ?? '' ); ?></p>
    </div>
    <div class="about-hero__stats" role="list">
      <?php foreach ( (array) ( $brand['stats'] ?? [] ) as $stat ) : ?>
        <div class="about-stat" role="listitem">
          <strong><?php echo esc_html( $stat['value'] ); ?></strong>
          <span><?php echo esc_html( $stat['label'] ); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ( function_exists( 'bi_render_brand_story_sections' ) ) : ?>
  <?php bi_render_brand_story_sections( [ 'skip_story' => true ] ); ?>
<?php endif; ?>

<section class="about-team section" aria-labelledby="about-team-title">
  <div class="container">
    <h2 id="about-team-title" class="section__title" data-bi-slide-title><?php esc_html_e( 'The team behind NextGen', 'beyondinfinity' ); ?></h2>
    <p class="section__sub"><?php esc_html_e( 'Educators, engineers, and parents working together to improve tutoring access across South Africa.', 'beyondinfinity' ); ?></p>
    <ul class="team-grid" role="list">
      <?php
      $team = [
        [ 'Nomsa Dlamini', 'CEO & Co-founder', 'Former educator with 15 years in curriculum development across Gauteng schools.' ],
        [ 'Sipho Khumalo', 'CTO', 'Full-stack engineer and EdTech veteran. Built learning platforms serving 200k+ students.' ],
        [ 'Priya Naidoo', 'Head of Tutor Success', 'Manages the vetting pipeline and tutor community. Based in Durban.' ],
        [ 'Andile Mokoena', 'Head of Growth', 'Drives student acquisition and provincial expansion across all 9 provinces.' ],
      ];
      foreach ( $team as $member ) :
        ?>
        <li class="team-card">
          <div class="team-card__avatar" aria-hidden="true">
            <span><?php echo esc_html( mb_substr( $member[0], 0, 1 ) ); ?></span>
          </div>
          <h3><?php echo esc_html( $member[0] ); ?></h3>
          <span class="team-card__role"><?php echo esc_html( $member[1] ); ?></span>
          <p><?php echo esc_html( $member[2] ); ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="about-provinces section section--alt" aria-labelledby="about-provinces-title">
  <div class="container">
    <h2 id="about-provinces-title" class="section__title"><?php esc_html_e( 'Active in all 9 provinces', 'beyondinfinity' ); ?></h2>
    <?php
    $provinces = [
      'Gauteng' => [ '18200', '16400' ],
      'KwaZulu-Natal' => [ '9100', '8800' ],
      'Western Cape' => [ '7400', '6900' ],
      'Eastern Cape' => [ '4800', '4500' ],
      'Limpopo' => [ '3900', '3700' ],
      'Mpumalanga' => [ '2800', '2600' ],
      'North West' => [ '1900', '1800' ],
      'Free State' => [ '1600', '1500' ],
      'Northern Cape' => [ '300', '300' ],
    ];
    ?>
    <ul class="province-list" role="list">
      <?php foreach ( $provinces as $name => $counts ) : ?>
        <li class="province-row" role="listitem">
          <span class="province-row__name"><?php echo esc_html( $name ); ?></span>
          <div class="province-row__bar-wrap" aria-hidden="true">
            <div class="province-row__bar" style="width:<?php echo esc_attr( (string) min( 100, round( (int) $counts[0] / 200 ) ) ); ?>%"></div>
          </div>
          <span class="province-row__count"><?php echo esc_html( number_format( (int) $counts[0] ) ); ?> tutors</span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section">
  <div class="container" style="text-align:center;padding:48px 0">
    <a class="btn btn--lime btn--lg" href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
  </div>
</section>

<?php
get_template_part( 'template-parts/footer' );
