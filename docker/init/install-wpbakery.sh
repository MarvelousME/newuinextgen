#!/bin/sh
# Optional WPBakery (js_composer) install — requires licensed zip on host.
set -eu

WP_PATH="/var/www/html"
PLUGINS_DIR="${WP_PATH}/wp-content/plugins"

log() { printf '[newuinextgen-wpbakery] %s\n' "$1"; }

ZIP_PATH="${WPBAKERY_ZIP_PATH:-}"

if [ -z "$ZIP_PATH" ] || [ ! -f "$ZIP_PATH" ]; then
	log "SKIP: Set WPBAKERY_ZIP_PATH to js_composer.zip to enable WPBakery in Docker."
	exit 0
fi

if wp plugin is-installed js_composer --path="$WP_PATH" --allow-root 2>/dev/null; then
	log "WPBakery already installed — activating..."
	wp plugin activate js_composer --path="$WP_PATH" --allow-root 2>/dev/null || true
	exit 0
fi

log "Installing WPBakery from ${ZIP_PATH}..."
wp plugin install "$ZIP_PATH" --activate --path="$WP_PATH" --allow-root
log "WPBakery installed."
