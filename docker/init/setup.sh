#!/bin/sh
# Idempotent WordPress bootstrap for newuinextgen workspace.
set -eu

WP_PATH="/var/www/html"
MARKER_OPTION="newuinextgen_docker_setup_version"

MARKER_VERSION="1"

log() { printf '[newuinextgen-setup] %s\n' "$1"; }

wait_for_file() {
  file="$1"
  max="${2:-60}"
  i=0
  while [ ! -f "$file" ]; do
    i=$((i + 1))
    if [ "$i" -ge "$max" ]; then
      log "Timeout waiting for $file"
      exit 1
    fi
    sleep 2
  done
}

wait_for_db() {
  i=0
  while ! wp db check --path="$WP_PATH" --allow-root --skip-ssl >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 45 ]; then
      log "Database not reachable"
      exit 1
    fi
    sleep 2
  done
}

log "Waiting for WordPress core files..."
wait_for_file "${WP_PATH}/wp-config.php"
wait_for_db

if wp option get "$MARKER_OPTION" --path="$WP_PATH" --allow-root 2>/dev/null | grep -q "^${MARKER_VERSION}$"; then
  log "Setup already completed (marker v${MARKER_VERSION}). Skipping."
  exit 0
fi

if ! wp core is-installed --path="$WP_PATH" --allow-root 2>/dev/null; then
  log "Installing WordPress..."
  wp core install \
    --path="$WP_PATH" \
    --url="${WP_URL:-http://localhost:8890}" \
    --title="${WP_TITLE:-NextGen Tutors}" \
    --admin_user="${WP_ADMIN_USER:-admin}" \
    --admin_password="${WP_ADMIN_PASSWORD:-NextGenAdmin!2026}" \
    --admin_email="${WP_ADMIN_EMAIL:-admin@nextgentutors.local}" \
    --skip-email \
    --allow-root
else
  log "WordPress already installed."
fi

log "Installing Hello Elementor parent theme..."
if wp theme is-installed hello-elementor --path="$WP_PATH" --allow-root 2>/dev/null; then
  log "Hello Elementor already installed."
elif [ -f "${WP_PATH}/wp-content/themes/hello-elementor/style.css" ]; then
  log "Hello Elementor present via bind mount."
else
  wp theme install hello-elementor --path="$WP_PATH" --allow-root 2>/dev/null \
    || log "WARN: Could not download hello-elementor — run docker/scripts/install-themes.ps1 on the host."
fi

log "Activating NextGenTutors-BeyondInfinity child theme..."
wp theme activate nextgentutors-beyondinfinity --path="$WP_PATH" --allow-root

log "Installing Elementor (optional page builder)..."
if wp plugin is-installed elementor --path="$WP_PATH" --allow-root 2>/dev/null; then
  wp plugin activate elementor --path="$WP_PATH" --allow-root 2>/dev/null || true
else
  wp plugin install elementor --activate --path="$WP_PATH" --allow-root 2>/dev/null \
    || log "WARN: Could not install elementor from WordPress.org"
fi

log "Configuring permalinks and timezone..."
wp rewrite structure '/%postname%/' --hard --path="$WP_PATH" --allow-root
wp option update timezone_string 'Africa/Johannesburg' --path="$WP_PATH" --allow-root
wp option update blogdescription 'Accessible tutoring across South Africa' --path="$WP_PATH" --allow-root

log "Applying theme defaults..."
if [ -f /setup/apply-defaults.sh ]; then
  /bin/sh /setup/apply-defaults.sh
else
  wp theme mod set visual_preset beyond-infinity --path="$WP_PATH" --allow-root 2>/dev/null || true
  wp theme mod set color_scheme default --path="$WP_PATH" --allow-root 2>/dev/null || true
  wp eval 'if ( function_exists( "bi_sync_launch_pages" ) ) { bi_sync_launch_pages(); }' --path="$WP_PATH" --allow-root 2>/dev/null || true
fi

if [ -f /setup/install-plugins.sh ] && [ -f "${WP_PATH}/wp-content/plugins/NextGenTutors-Companion/nextgencompanion.php" ]; then
  log "Installing NextGen fleet plugins..."
  /bin/sh /setup/install-plugins.sh
  if [ -f /setup/install-registry-zips.sh ]; then
    log "Installing registry zips from ngcpm-packages..."
    /bin/sh /setup/install-registry-zips.sh || log "WARN: Registry zip install had issues — run docker/scripts/install-registry-zips.ps1"
  fi
elif [ -f /setup/install-companion.sh ] && [ -f "${WP_PATH}/wp-content/plugins/NextGenTutors-Companion/nextgencompanion.php" ]; then
  log "Installing NextGen Companion plugin..."
  /bin/sh /setup/install-companion.sh
else
  log "WARN: NextGenTutors-Companion not mounted — set COMPANION_PLUGIN_PATH in .env or use repo copy, then run docker/scripts/install-plugins.ps1"
fi

if [ -f /setup/install-wpbakery.sh ]; then
  log "Optional WPBakery install..."
  /bin/sh /setup/install-wpbakery.sh || log "INFO: WPBakery skipped (set WPBAKERY_ZIP_PATH for licensed zip)"
fi

wp option update "$MARKER_OPTION" "$MARKER_VERSION" --path="$WP_PATH" --allow-root
log "Setup complete."
log "Site: ${WP_URL:-http://localhost:8890}"
log "Admin: ${WP_ADMIN_USER:-admin} / ${WP_ADMIN_PASSWORD:-NextGenAdmin!2026}"
log "phpMyAdmin: http://localhost:${PMA_PORT:-8082}"
