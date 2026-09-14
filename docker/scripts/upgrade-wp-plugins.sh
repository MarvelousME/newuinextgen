#!/bin/bash
# Upgrade WordPress core to 7.1, install missing registry plugins, update all.
set -eu
WP_PATH=/var/www/html
cd "$WP_PATH"

ensure_wpcli() {
  if command -v wp >/dev/null 2>&1; then
    return 0
  fi
  curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x /usr/local/bin/wp
}
ensure_wpcli
WP="wp --allow-root --path=${WP_PATH}"

echo "=== BEFORE ==="
$WP core version || true
$WP plugin list --fields=name,status,version,update || true

TARGET_CORE="${1:-7.1}"
CUR=$($WP core version 2>/dev/null || echo "0")
echo "CURRENT_CORE=${CUR} TARGET=${TARGET_CORE}"

# Core update (works even when image files are older than target).
if [ "$CUR" != "$TARGET_CORE" ] && [ "${CUR#${TARGET_CORE}}" = "$CUR" ]; then
  echo "Updating core to ${TARGET_CORE}..."
  $WP core update --version="$TARGET_CORE" || $WP core update --version="${TARGET_CORE}.0" || $WP core update || true
  $WP core update-db || true
fi
echo "CORE_NOW=$($WP core version)"

install_activate() {
  slug="$1"
  echo "--- ensure plugin: ${slug}"
  if $WP plugin is-installed "$slug" 2>/dev/null; then
    $WP plugin activate "$slug" 2>/dev/null || true
    return 0
  fi
  if $WP plugin install "$slug" --activate; then
    echo "INSTALLED=${slug}"
    return 0
  fi
  echo "FAIL_INSTALL=${slug}"
  return 1
}

# NGCPM required + Companion-critical (WordPress.org)
WPORG_REQUIRED=(
  elementor
  woocommerce
  fluent-crm
  fluent-smtp
  fluent-support
  masterstudy-lms-learning-management-system
  gamipress
  automatorwp
  user-role-editor
)

# Best-effort w.org installs for packages marked manual in registry
WPORG_TRY=(
  woocommerce-payfast-gateway
  ameliabooking
)

for s in "${WPORG_REQUIRED[@]}"; do
  install_activate "$s" || true
done
for s in "${WPORG_TRY[@]}"; do
  install_activate "$s" || true
done

# Activate mounted NextGen fleet plugins
FLEET=(
  "NextGenTutors-Companion/nextgencompanion"
  "NextGenTutors-AI-Integration/nextgentutors-ai-integration"
  "NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager"
  "NextGenTutors-Html-Importer/revamp-html-importer"
  "NextGenTutors-Mission-Control/nextgentutors-mission-control"
  "NextGenTutors-BeyondMeasure/nextgentutors-beyond-measure"
  "nextgen-automation-hub/nextgen-automation-hub"
  "nextgen-command-center/nextgen-command-center"
  "nextgen-completion-suite/nextgen-completion-suite"
)
for p in "${FLEET[@]}"; do
  echo "--- activate fleet: ${p}"
  $WP plugin activate "$p" 2>/dev/null || echo "SKIP_FLEET=${p}"
done

echo "=== PLUGIN UPDATES ==="
$WP plugin update --all || true
$WP theme update --all || true
$WP core update-db || true

# Hello Elementor parent if missing
if ! $WP theme is-installed hello-elementor 2>/dev/null; then
  $WP theme install hello-elementor || true
fi
$WP theme activate nextgentutors-beyondinfinity 2>/dev/null || true

echo "=== AFTER ==="
$WP core version
$WP plugin list --fields=name,status,version,update
echo "=== MISSING REQUIRED CHECK ==="
for s in "${WPORG_REQUIRED[@]}"; do
  if $WP plugin is-installed "$s" 2>/dev/null; then
    st=$($WP plugin get "$s" --field=status 2>/dev/null || echo unknown)
    echo "OK ${s}=${st}"
  else
    echo "MISSING ${s}"
  fi
done
