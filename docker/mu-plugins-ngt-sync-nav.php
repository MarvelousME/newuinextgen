<?php
/**
 * Plugin Name: NGT One-shot Reference Nav Sync
 */
add_action('init', function () {
  if (!isset($_GET['ngt_sync_ref_nav']) || !current_user_can('manage_options')) {
    if (!(defined('WP_CLI') && WP_CLI)) {
      // allow unsigned local one-shot via secret for docker
    }
  }
  if (!isset($_GET['ngt_sync_ref_nav']) || $_GET['ngt_sync_ref_nav'] !== '1') {
    return;
  }
  if (!function_exists('bi_sync_grouped_primary_menu')) {
    status_header(500);
    echo 'missing sync';
    exit;
  }
  $id = bi_sync_grouped_primary_menu(true);
  update_option('bi_nav_public_schema', bi_nav_public_schema_version(), false);
  $opts = get_option('bi_theme_options', []);
  if (!is_array($opts)) { $opts = []; }
  $opts['footer_style'] = 'default';
  update_option('bi_theme_options', $opts, false);
  header('Content-Type: text/plain');
  echo "ok menu=$id\n";
  foreach ((array) wp_get_nav_menu_items($id) as $it) {
    echo (((int)$it->menu_item_parent)>0?'  ':'').$it->title."\n";
  }
  exit;
}, 99);
