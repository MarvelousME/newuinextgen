#!/bin/sh
# Apply theme + site defaults (idempotent).
set -eu

WP_PATH="/var/www/html"

log() { printf '[newuinextgen-defaults] %s\n' "$1"; }

log "Activating theme..."
wp theme activate nextgentutors-beyondinfinity --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Applying Customizer defaults..."
wp theme mod set visual_preset beyond-infinity --path="$WP_PATH" --allow-root
wp theme mod set color_scheme default --path="$WP_PATH" --allow-root
wp theme mod set theme_switcher_enabled 0 --path="$WP_PATH" --allow-root
wp theme mod set theme_switcher_visibility admins --path="$WP_PATH" --allow-root
wp theme mod set body_style wide --path="$WP_PATH" --allow-root
wp theme mod set home_layout kinetic --path="$WP_PATH" --allow-root

log "Site options..."
wp option update timezone_string 'Africa/Johannesburg' --path="$WP_PATH" --allow-root
wp option update blogdescription 'Accessible tutoring across South Africa' --path="$WP_PATH" --allow-root
wp rewrite structure '/%postname%/' --hard --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Syncing launch pages + prototype content..."
wp eval 'if ( function_exists( "bi_sync_all_prototype_pages" ) ) { $r = bi_sync_all_prototype_pages(); echo is_wp_error( $r ) ? $r->get_error_message() : json_encode( $r ); echo "\n"; } elseif ( function_exists( "bi_sync_launch_pages" ) ) { $r = bi_sync_launch_pages(); echo is_wp_error( $r ) ? $r->get_error_message() : json_encode( $r ); echo "\n"; }' \
  --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Binding templates and building menus..."
wp eval '
$pages = function_exists("bi_load_page_map") ? bi_load_page_map() : [];
if ( is_wp_error($pages) ) { echo $pages->get_error_message(); exit(1); }
$bound = 0; $missing = 0;
foreach ( (array) $pages as $entry ) {
  $slug = $entry["slug"] ?? "";
  $page = function_exists("bi_find_page_by_slug") ? bi_find_page_by_slug($slug) : get_page_by_path($slug);
  if ( ! $page ) { $missing++; continue; }
  $tpl = ! empty($entry["is_front"]) ? "default" : ($entry["template"] ?? "");
  if ( function_exists("bi_bind_page_template") ) { bi_bind_page_template((int)$page->ID, $tpl); }
  $bound++;
}
$nav = function_exists("bi_sync_launch_nav") ? bi_sync_launch_nav(true) : [];
echo json_encode(["templates_bound"=>$bound,"pages_missing"=>$missing,"menus"=>$nav]) . "\n";
' --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Regenerating dynamic CSS..."
wp eval 'if ( function_exists( "bi_customizer_save_css" ) ) { bi_customizer_save_css(); echo "css ok\n"; }' \
  --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Defaults applied."
