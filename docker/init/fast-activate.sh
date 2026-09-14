#!/bin/sh
# Fast plugin/theme activation without loading the full plugin stack.
set -eu
WP_PATH="/var/www/html"
WP="wp --allow-root --path=${WP_PATH} --skip-plugins --skip-themes"

log() { printf '[fast-activate] %s\n' "$1"; }

log "Core: $($WP core version)"
log "Activating theme..."
$WP theme activate nextgentutors-beyondinfinity || log "Theme already active or missing"

log "Activating plugins..."
$WP plugin activate \
  elementor \
  woocommerce \
  fluent-crm \
  fluent-smtp \
  fluent-support \
  masterstudy-lms-learning-management-system \
  gamipress \
  automatorwp \
  user-role-editor \
  woocommerce-payfast-gateway \
  NextGenTutors-Companion/nextgencompanion \
  NextGenTutors-AI-Integration/nextgentutors-ai-integration \
  NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager \
  NextGenTutors-Html-Importer/revamp-html-importer \
  NextGenTutors-Mission-Control/nextgentutors-mission-control \
  NextGenTutors-BeyondMeasure/nextgentutors-beyond-measure \
  nextgen-automation-hub/nextgen-automation-hub \
  nextgen-subjects-widget/nextgen-subjects-widget \
  nextgen-3d-filmstrip/nextgen-3d-filmstrip \
  nextgen-3d-scroll-manager/nextgen-3d-scroll-manager \
  nextgen-command-center/nextgen-command-center \
  nextgen-completion-suite/nextgen-completion-suite \
  || true

log "Active plugins:"
$WP plugin list --fields=name,status,version --status=active
log "Inactive plugins:"
$WP plugin list --fields=name,status --status=inactive
log "Done."
