<?php
/**
 * Regenerate inc/defaults/*.php as thin production/prototype routers.
 *
 * Usage: php scripts/regenerate-default-wrappers.php
 */

$root     = dirname( __DIR__ );
$defaults = $root . '/inc/defaults';

$map = [
	'home.php'              => [ 'home', 'index-body.php' ],
	'find-a-tutor.php'      => [ 'find-a-tutor', 'find-a-tutor-body.php' ],
	'become-a-tutor.php'    => [ 'become-a-tutor', 'become-a-tutor-body.php' ],
	'about.php'             => [ 'about', 'about-body.php' ],
	'contact.php'           => [ 'contact', 'contact-body.php' ],
	'support.php'           => [ 'support', 'support-body.php' ],
	'blog.php'              => [ 'blog', 'blog-body.php' ],
	'guarantee.php'         => [ 'guarantee', 'guarantee-body.php' ],
	'pricing.php'           => [ 'pricing', 'pricing-body.php' ],
	'tutor-vetting.php'     => [ 'tutor-vetting', 'tutor-vetting-body.php' ],
	'safety-guide.php'      => [ 'safety-guide', 'safety-guide-body.php' ],
	'terms.php'             => [ 'terms', 'terms-body.php' ],
	'privacy-policy.php'    => [ 'privacy-policy', 'privacy-body.php' ],
	'student-dashboard.php' => [ 'student-dashboard', 'dashboard-body.php' ],
	'parent-dashboard.php'  => [ 'parent-dashboard', 'dashboard-body.php' ],
	'tutor-dashboard.php'   => [ 'tutor-dashboard', 'tutor-dashboard-body.php' ],
	'admin-dashboard.php'   => [ 'admin-dashboard', 'admin-dashboard-body.php' ],
	'onboarding.php'        => [ 'onboarding', 'onboarding-body.php' ],
	'wordpress-setup.php'   => [ 'wordpress-setup', 'setup-body.php' ],
	'register.php'          => [ 'register', '' ],
	'login.php'             => [ 'login', '' ],
	'thank-you.php'         => [ 'thank-you', '' ],
	'child-safety.php'      => [ 'child-safety', '' ],
];

foreach ( $map as $file => $meta ) {
	list( $slug, $prototype ) = $meta;
	$php  = "<?php\n/**\n * Page default router — production (live data) or prototype preview.\n *\n * @package BeyondInfinity\n */\n";
	$php .= "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\n";
	if ( $prototype ) {
		$php .= "bi_render_page_default( '{$slug}', '{$prototype}' );\n";
	} else {
		$php .= "bi_render_page_default( '{$slug}' );\n";
	}
	file_put_contents( $defaults . '/' . $file, $php );
}

echo 'OK: regenerated ' . count( $map ) . " default wrappers.\n";
