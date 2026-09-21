<?php
/**
 * Generate PRODUCTION validation CSVs from source (no WordPress bootstrap).
 *
 * Usage: php scripts/production-inventory.php [output-dir]
 *
 * @package NextGenTutors
 */

$root = dirname( __DIR__ );
$out  = isset( $argv[1] ) ? $argv[1] : $root . DIRECTORY_SEPARATOR . 'PRODUCTION' . DIRECTORY_SEPARATOR . '04-VALIDATION';
if ( ! is_dir( $out ) ) {
	mkdir( $out, 0755, true );
}

function ngt_walk_php( $dir, array &$files ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$ext = strtolower( $file->getExtension() );
		if ( ! in_array( $ext, [ 'php', 'css', 'js', 'json', 'md' ], true ) ) {
			continue;
		}
		$path = $file->getPathname();
		if ( preg_match( '#[\\\\/](node_modules|\.git|BACKUPS|UNUSED|NOT-USED|tests|vendor|build-src|_extracted|offline-packages)[\\\\/]#', $path ) ) {
			continue;
		}
		$files[] = $path;
	}
}

function ngt_header_field( $path, array $keys ) {
	$raw = @file_get_contents( $path );
	if ( false === $raw ) {
		return [];
	}
	$head = substr( $raw, 0, 4096 );
	$out  = [];
	foreach ( $keys as $key ) {
		if ( preg_match( '/^[ \t\*]*' . preg_quote( $key, '/' ) . ':[ \t]*(.+)$/mi', $head, $m ) ) {
			$out[ $key ] = trim( $m[1] );
		}
	}
	return $out;
}

function ngt_rel( $root, $path ) {
	$root = rtrim( str_replace( '\\', '/', $root ), '/' ) . '/';
	$path = str_replace( '\\', '/', $path );
	if ( 0 === strpos( $path, $root ) ) {
		return substr( $path, strlen( $root ) );
	}
	return $path;
}

function ngt_csv( $path, array $rows, array $headers ) {
	$fh = fopen( $path, 'wb' );
	fputcsv( $fh, $headers, ',', '"', '\\' );
	foreach ( $rows as $row ) {
		$line = [];
		foreach ( $headers as $h ) {
			$line[] = isset( $row[ $h ] ) ? $row[ $h ] : '';
		}
		fputcsv( $fh, $line, ',', '"', '\\' );
	}
	fclose( $fh );
}

$packages = [
	[ 'id' => 'NextGenTutors-BeyondInfinity', 'kind' => 'theme', 'src' => $root, 'entry' => 'style.css', 'required' => 'yes', 'notes' => 'Materialized from monorepo theme files; not the whole repo' ],
	[ 'id' => 'Hello-Elementor', 'kind' => 'theme-parent', 'src' => $root . '/docker/hello-elementor', 'entry' => 'style.css', 'required' => 'yes', 'notes' => 'GPL parent; Template: hello-elementor' ],
	[ 'id' => 'NextGenTutors-Companion', 'kind' => 'plugin', 'src' => $root . '/NextGenTutors-Companion', 'entry' => 'nextgencompanion.php', 'required' => 'yes', 'notes' => 'Domain, CPT, ngc_* tables, ngc/v1' ],
	[ 'id' => 'NextGenTutors-Plugin-Manager', 'kind' => 'plugin', 'src' => $root . '/NextGenTutors-Plugin-Manager', 'entry' => 'NextGenTutors-Plugin-Manager.php', 'required' => 'yes', 'notes' => 'Slim; no offline-packages' ],
	[ 'id' => 'NextGenTutors-Mission-Control', 'kind' => 'plugin', 'src' => $root . '/NextGenTutors-Mission-Control', 'entry' => 'nextgentutors-mission-control.php', 'required' => 'yes', 'notes' => 'Ops / seed / verify' ],
	[ 'id' => 'nextgen-3d-scroll-manager', 'kind' => 'plugin', 'src' => $root . '/nextgen-3d-scroll-manager', 'entry' => 'nextgen-3d-scroll-manager.php', 'required' => 'yes', 'notes' => 'Motion engine; ngt3d/v1' ],
	[ 'id' => 'nextgen-3d-filmstrip', 'kind' => 'plugin', 'src' => $root . '/nextgen-3d-filmstrip', 'entry' => 'nextgen-3d-filmstrip.php', 'required' => 'yes', 'notes' => 'Filmstrip shortcode' ],
	[ 'id' => 'nextgen-subjects-widget', 'kind' => 'plugin', 'src' => $root . '/nextgen-subjects-widget', 'entry' => 'nextgen-subjects-widget.php', 'required' => 'yes', 'notes' => 'Subjects grid' ],
	[ 'id' => 'NextGenTutors-Html-Importer', 'kind' => 'plugin', 'src' => $root . '/NextGenTutors-Html-Importer', 'entry' => 'revamp-html-importer.php', 'required' => 'no', 'notes' => 'One-time migration; deactivate after use' ],
	[ 'id' => 'NextGenTutors-AI-Integration', 'kind' => 'plugin', 'src' => $root . '/NextGenTutors-AI-Integration', 'entry' => 'nextgentutors-ai-integration.php', 'required' => 'no', 'notes' => 'Optional agents-api bridge' ],
	[ 'id' => 'NextGenTutors-BeyondMeasure', 'kind' => 'plugin', 'src' => $root . '/NextGenTutors-BeyondMeasure', 'entry' => 'nextgentutors-beyond-measure.php', 'required' => 'no', 'notes' => 'Optional control-plane SPA' ],
	[ 'id' => 'nextgen-automation-hub', 'kind' => 'plugin', 'src' => $root . '/nextgen-automation-hub', 'entry' => 'nextgen-automation-hub.php', 'required' => 'no', 'notes' => 'Overlaps Companion; leave off when Companion is domain owner' ],
];

$inventory     = [];
$dep_matrix    = [];
$rest_rows     = [];
$shortcode_rows = [];
$secret_hits   = [];
$versions      = [];

$secret_re = '/(api[_-]?key|secret_key|private_key|BEGIN (RSA |OPENSSH )?PRIVATE KEY|sk_live_|AKIA[0-9A-Z]{16}|password\s*=\s*[\'\"][^\'\"]{8,})/i';

foreach ( $packages as $pkg ) {
	$entry = $pkg['src'] . '/' . $pkg['entry'];
	$meta  = is_file( $entry ) ? ngt_header_field( $entry, [ 'Plugin Name', 'Theme Name', 'Version', 'Requires at least', 'Requires PHP', 'Template' ] ) : [];
	$ver   = $meta['Version'] ?? '';
	$versions[ $pkg['id'] ] = $ver;
	$name  = $meta['Plugin Name'] ?? $meta['Theme Name'] ?? $pkg['id'];

	$scan_root = $pkg['src'];
	$php_files = [];
	if ( 'theme' === $pkg['kind'] ) {
		foreach ( [ 'inc', 'templates', 'template-parts', 'page-templates', 'prototypes', 'assets' ] as $d ) {
			ngt_walk_php( $root . '/' . $d, $php_files );
		}
		foreach ( glob( $root . '/*.php' ) ?: [] as $f ) {
			$php_files[] = $f;
		}
		$php_files[] = $root . '/style.css';
	} else {
		ngt_walk_php( $scan_root, $php_files );
	}

	$count = 0;
	foreach ( $php_files as $file ) {
		$count++;
		$rel = ngt_rel( $root, $file );
		$inventory[] = [
			'package' => $pkg['id'],
			'kind'    => $pkg['kind'],
			'path'    => $rel,
			'bytes'   => is_file( $file ) ? filesize( $file ) : 0,
			'ext'     => strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ),
		];
		if ( ! is_file( $file ) || 'php' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
			continue;
		}
		$src = file_get_contents( $file );
		if ( preg_match_all( '/register_rest_route\s*\(\s*([^,]+)\s*,\s*([\'\"][^\'\"]+[\'\"])/', $src, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $hit ) {
				$ns = trim( $hit[1] );
				$ns = preg_replace( '/.*NAMESPACE.*/', 'ngc/v1', $ns );
				$ns = trim( $ns, " \t\"'" );
				$rest_rows[] = [
					'package'  => $pkg['id'],
					'file'     => $rel,
					'namespace'=> $ns,
					'route'    => trim( $hit[2], "'\"" ),
				];
			}
		}
		if ( preg_match_all( '/add_shortcode\s*\(\s*[\'\"]([^\'\"]+)[\'\"]/', $src, $sm ) ) {
			foreach ( $sm[1] as $tag ) {
				$shortcode_rows[] = [
					'package' => $pkg['id'],
					'tag'     => $tag,
					'file'    => $rel,
					'owner'   => ( 'theme' === $pkg['kind'] ) ? 'theme-fallback-or-ui' : 'plugin',
				];
			}
		}
		if ( preg_match( $secret_re, $src ) && ! preg_match( '#/(tests|docs|documentation)/#', $rel ) ) {
			$secret_hits[] = $rel;
		}
	}

	$dep_matrix[] = [
		'package'        => $pkg['id'],
		'wordpress_name' => $name,
		'version'        => $ver,
		'kind'           => $pkg['kind'],
		'required'       => $pkg['required'],
		'requires_wp'    => $meta['Requires at least'] ?? '',
		'requires_php'   => $meta['Requires PHP'] ?? '',
		'parent_theme'   => $meta['Template'] ?? '',
		'depends_on'     => ( 'theme' === $pkg['kind'] ) ? 'Hello-Elementor (soft Companion)' : ( ( 'NextGenTutors-Companion' === $pkg['id'] ) ? 'WordPress 6.0+' : 'Companion recommended' ),
		'file_count'     => $count,
		'notes'          => $pkg['notes'],
	];
}

// Pages from pages-registry.php
$page_rows = [];
$reg_file  = $root . '/inc/pages-registry.php';
if ( is_file( $reg_file ) ) {
	$reg = file_get_contents( $reg_file );
	if ( preg_match_all( "/'([a-z0-9\-]+)'\s*=>\s*\[(.*?)\]/s", $reg, $pm, PREG_SET_ORDER ) ) {
		foreach ( $pm as $hit ) {
			$slug = $hit[1];
			if ( in_array( $slug, [ 'template', 'default', 'source', 'type', 'shortcodes', 'config_defaults', 'header_style' ], true ) ) {
				continue;
			}
			$block = $hit[2];
			$tpl = ( preg_match( "/'template'\s*=>\s*'([^']+)'/", $block, $t ) ) ? $t[1] : '';
			$type = ( preg_match( "/'type'\s*=>\s*'([^']+)'/", $block, $ty ) ) ? $ty[1] : '';
			$sc = [];
			if ( preg_match( "/'shortcodes'\s*=>\s*\[([^\]]+)\]/", $block, $s ) ) {
				if ( preg_match_all( "/'([^']+)'/", $s[1], $tags ) ) {
					$sc = $tags[1];
				}
			}
			$page_rows[] = [
				'slug'       => $slug,
				'template'   => $tpl,
				'type'       => $type,
				'shortcodes' => implode( '|', $sc ),
				'body'       => 'prototypes/' . $slug . '-body.php or defaults-production',
				'owner'      => 'theme presentation + Companion shortcodes',
			];
		}
	}
}

ngt_csv( $out . '/FILE-INVENTORY.csv', $inventory, [ 'package', 'kind', 'path', 'bytes', 'ext' ] );
ngt_csv( $out . '/DEPENDENCY-MATRIX.csv', $dep_matrix, [ 'package', 'wordpress_name', 'version', 'kind', 'required', 'requires_wp', 'requires_php', 'parent_theme', 'depends_on', 'file_count', 'notes' ] );
ngt_csv( $out . '/REST-ENDPOINTS.csv', $rest_rows, [ 'package', 'file', 'namespace', 'route' ] );
ngt_csv( $out . '/SHORTCODES.csv', $shortcode_rows, [ 'package', 'tag', 'file', 'owner' ] );
ngt_csv( $out . '/PAGE-MATRIX.csv', $page_rows, [ 'slug', 'template', 'type', 'shortcodes', 'body', 'owner' ] );

$manifest = [
	'generated_at' => gmdate( 'c' ),
	'versions'     => $versions,
	'secret_hits'  => $secret_hits,
	'counts'       => [
		'files'      => count( $inventory ),
		'rest'       => count( $rest_rows ),
		'shortcodes' => count( $shortcode_rows ),
		'pages'      => count( $page_rows ),
	],
];
file_put_contents( $out . '/inventory-meta.json', json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

fwrite( STDOUT, "Inventory written to {$out}\n" );
fwrite( STDOUT, 'files=' . count( $inventory ) . ' rest=' . count( $rest_rows ) . ' shortcodes=' . count( $shortcode_rows ) . "\n" );
if ( $secret_hits ) {
	fwrite( STDERR, "SECRET PATTERN HITS (review before packaging):\n" . implode( "\n", $secret_hits ) . "\n" );
}
