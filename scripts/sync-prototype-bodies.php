<?php
/**
 * Sync original-top-ui prototype bodies into REVAMP projects.
 *
 * Usage: php scripts/sync-prototype-bodies.php
 */

$root   = dirname( __DIR__ );
$source = 'C:/Users/marvi/Desktop/WP-THEME-NEXT-GEN/nextgen-tutors-theme-original-top-ui/prototypes';

if ( ! is_dir( $source ) ) {
	fwrite( STDERR, "Source not found: {$source}\n" );
	exit( 1 );
}

$targets = [
	$root . '/prototypes',
];

$files = glob( $source . '/*-body.php' );
sort( $files );

foreach ( $targets as $dir ) {
	if ( ! is_dir( $dir ) && ! mkdir( $dir, 0755, true ) ) {
		fwrite( STDERR, "Cannot create {$dir}\n" );
		exit( 1 );
	}
	foreach ( $files as $src ) {
		if ( ! copy( $src, $dir . '/' . basename( $src ) ) ) {
			fwrite( STDERR, "Copy failed: {$src}\n" );
			exit( 1 );
		}
	}
}

$default_map = [
	'home.php'              => [ 'index-body.php', 'home' ],
	'find-a-tutor.php'      => [ 'find-a-tutor-body.php', 'find-a-tutor' ],
	'become-a-tutor.php'    => [ 'become-a-tutor-body.php', 'become-a-tutor' ],
	'about.php'             => [ 'about-body.php', 'about' ],
	'contact.php'           => [ 'contact-body.php', 'contact' ],
	'support.php'           => [ 'support-body.php', 'support' ],
	'blog.php'              => [ 'blog-body.php', 'blog' ],
	'guarantee.php'         => [ 'guarantee-body.php', 'guarantee' ],
	'pricing.php'           => [ 'pricing-body.php', 'pricing' ],
	'tutor-vetting.php'     => [ 'tutor-vetting-body.php', 'tutor-vetting' ],
	'safety-guide.php'      => [ 'safety-guide-body.php', 'safety-guide' ],
	'terms.php'             => [ 'terms-body.php', 'terms' ],
	'privacy-policy.php'    => [ 'privacy-body.php', 'privacy-policy' ],
	'student-dashboard.php' => [ 'dashboard-body.php', 'student-dashboard' ],
	'parent-dashboard.php'  => [ 'dashboard-body.php', 'parent-dashboard' ],
	'tutor-dashboard.php'   => [ 'tutor-dashboard-body.php', 'tutor-dashboard' ],
	'admin-dashboard.php'   => [ 'admin-dashboard-body.php', 'admin-dashboard' ],
	'onboarding.php'        => [ 'onboarding-body.php', 'onboarding' ],
	'wordpress-setup.php'   => [ 'setup-body.php', 'wordpress-setup' ],
];

$defaults_dir = $root . '/inc/defaults';
$wrapper_script = $root . '/scripts/regenerate-default-wrappers.php';
if ( file_exists( $wrapper_script ) ) {
	include $wrapper_script;
} else {
	foreach ( $default_map as $default_file => $meta ) {
		list( $prototype_file, $slug ) = $meta;
		$php  = "<?php\n/**\n * Default — synced from original-top-ui prototype: {$prototype_file}\n *\n * @package BeyondInfinity\n */\n";
		$php .= "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\n";
		$php .= "bi_render_page_default( '{$slug}', '{$prototype_file}' );\n";
		file_put_contents( $defaults_dir . '/' . $default_file, $php );
	}
}

file_put_contents(
	$root . '/prototype-copy-verification-report.json',
	json_encode(
		[
			'source'    => $source,
			'copied'    => count( $files ),
			'targets'   => $targets,
			'defaults'  => array_keys( $default_map ),
		],
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	)
);

echo 'OK: ' . count( $files ) . ' prototypes x ' . count( $targets ) . " projects; default wrappers regenerated.\n";
