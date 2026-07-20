#!/bin/sh
# Install + activate NextGenTutors-Companion (bind-mounted plugin).
set -eu

WP_PATH="/var/www/html"
PLUGIN_FILE="${WP_PATH}/wp-content/plugins/NextGenTutors-Companion/nextgencompanion.php"
PLUGIN_SLUG="NextGenTutors-Companion/nextgencompanion"

log() { printf '[newuinextgen-companion] %s\n' "$1"; }

if [ ! -f "$PLUGIN_FILE" ]; then
  log "ERROR: Companion plugin not found at $PLUGIN_FILE"
  log "Set COMPANION_PLUGIN_PATH in docker/.env to your NextGenTutors-Companion folder."
  exit 1
fi

log "Activating NextGenTutors-Companion..."
wp plugin activate "$PLUGIN_SLUG" --path="$WP_PATH" --allow-root

log "Seeding demo tutor roster (when NGC_ALLOW_DEMO_SEED)..."
wp eval 'if ( class_exists( "NGC_Tutor_Seeder" ) ) { $r = NGC_Tutor_Seeder::ensure_seeded( true ); echo "seeded:" . json_encode( $r ) . "\n"; }' \
  --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Repairing page form shortcodes..."
wp eval 'if ( class_exists( "NGC_Page_Forms_Registry" ) ) { $r = NGC_Page_Forms_Registry::ensure_production_forms( true ); echo json_encode( $r ) . "\n"; }' \
  --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Syncing launch pages + menus..."
wp eval 'if ( function_exists( "bi_sync_launch_pages" ) ) { $r = bi_sync_launch_pages(); echo is_wp_error( $r ) ? $r->get_error_message() : json_encode( $r ); echo "\n"; }' \
  --path="$WP_PATH" --allow-root 2>/dev/null || true

log "Verifying companion..."
wp ngc verify --path="$WP_PATH" --allow-root 2>/dev/null || log "WARN: wp ngc verify reported issues (integrations may need extra plugins)"

log "Companion ready."
