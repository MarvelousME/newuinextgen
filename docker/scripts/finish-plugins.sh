#!/bin/bash
# Finish plugin activate/update after core image upgrade. Skip slow wp-load chatter where possible.
set -eu
WP_PATH=/var/www/html
WP="wp --allow-root --path=${WP_PATH} --skip-plugins --skip-themes"
WP_FULL="wp --allow-root --path=${WP_PATH}"

echo "CORE=$($WP core version)"
$WP core update-db || true

activate() {
  slug="$1"
  if $WP plugin is-installed "$slug" 2>/dev/null; then
    $WP plugin activate "$slug" 2>/dev/null && echo "ACTIVE=$slug" || echo "ACTIVATE_FAIL=$slug"
  else
    echo "NOT_INSTALLED=$slug"
  fi
}

install_if_missing() {
  slug="$1"
  if $WP plugin is-installed "$slug" 2>/dev/null; then
    activate "$slug"
    return 0
  fi
  echo "INSTALLING=$slug"
  if $WP_FULL plugin install "$slug" --activate; then
    echo "INSTALLED=$slug"
  else
    echo "FAIL=$slug"
  fi
}

# Required stack
for s in elementor woocommerce fluent-crm fluent-smtp fluent-support masterstudy-lms-learning-management-system gamipress automatorwp user-role-editor woocommerce-payfast-gateway ameliabooking; do
  install_if_missing "$s" || true
done

# Fleet mounts
for p in \
  "NextGenTutors-Companion/nextgencompanion" \
  "NextGenTutors-AI-Integration/nextgentutors-ai-integration" \
  "NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager" \
  "NextGenTutors-Html-Importer/revamp-html-importer" \
  "NextGenTutors-Mission-Control/nextgentutors-mission-control" \
  "NextGenTutors-BeyondMeasure/nextgentutors-beyond-measure" \
  "nextgen-automation-hub/nextgen-automation-hub" \
  "nextgen-command-center/nextgen-command-center" \
  "nextgen-completion-suite/nextgen-completion-suite"
do
  $WP plugin activate "$p" 2>/dev/null && echo "FLEET_OK=$p" || echo "FLEET_SKIP=$p"
done

echo "=== UPDATES ==="
$WP_FULL plugin update --all || true
$WP theme update --all || true
$WP theme activate nextgentutors-beyondinfinity 2>/dev/null || true

echo "=== SUMMARY ==="
$WP core version
$WP plugin list --fields=name,status,version,update
