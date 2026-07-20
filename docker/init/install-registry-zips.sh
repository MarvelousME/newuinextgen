#!/bin/sh
# Install registry plugins from wp-content/ngcpm-packages and configure NextGen stack.
set -eu

WP_PATH="/var/www/html"
SCRIPT="${WP_PATH}/wp-content/plugins/NextGenTutors-Plugin-Manager/scripts/install-registry-zips.php"

log() { printf '[newuinextgen-registry] %s\n' "$1"; }

if [ ! -f "$SCRIPT" ]; then
	log "WARN: install-registry-zips.php not found — skip"
	exit 0
fi

log "Activating Plugin Manager..."
wp plugin activate NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager \
	--path="$WP_PATH" --allow-root 2>/dev/null || true

log "Installing registry zips + configuring integrations..."
wp eval-file "$SCRIPT" --path="$WP_PATH" --allow-root

log "Registry install complete."
