<?php
require '/var/www/html/wp-load.php';
$ids = [ 85, 87, 89, 91, 93, 95, 97, 99, 101, 102, 103, 106, 107, 109, 111, 3, 113, 115 ];
foreach ( $ids as $id ) {
	$p = get_post( $id );
	if ( ! $p ) {
		echo "MISSING $id\n";
		continue;
	}
	$mode = get_post_meta( $id, '_elementor_edit_mode', true );
	$tpl  = get_page_template_slug( $id );
	$data = get_post_meta( $id, '_elementor_data', true );
	$len  = is_string( $data ) ? strlen( $data ) : 0;
	$opts = get_post_meta( $id, 'bi_options', true );
	$force = is_array( $opts ) ? ( $opts['force_theme_default'] ?? '?' ) : '?';
	$edit = admin_url( 'post.php?post=' . $id . '&action=elementor' );
	echo sprintf(
		"%d\t%s\tmode=%s\ttpl=%s\tdata=%d\tforce=%s\n",
		$id,
		$p->post_name,
		$mode,
		$tpl ?: 'default',
		$len,
		(string) $force
	);
}
if ( class_exists( '\Elementor\Plugin' ) ) {
	$plugin = \Elementor\Plugin::instance();
	if ( isset( $plugin->files_manager ) && method_exists( $plugin->files_manager, 'clear_cache' ) ) {
		$plugin->files_manager->clear_cache();
		echo "CACHE_CLEARED\n";
	}
}
echo 'ELEMENTOR_ACTIVE=' . ( is_plugin_active( 'elementor/elementor.php' ) ? '1' : '0' ) . "\n";
