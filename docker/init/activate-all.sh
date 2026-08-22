#!/bin/sh
set -eu
WP=/var/www/html
URL=http://127.0.0.1:8081

wp core is-installed --path="$WP" --allow-root || wp core install \
  --path="$WP" \
  --url="$URL" \
  --title="NextGen Tutors" \
  --admin_user=admin \
  --admin_password='NextGenAdmin!2026' \
  --admin_email=admin@nextgentutors.local \
  --skip-email --allow-root

wp option update siteurl "$URL" --path="$WP" --allow-root
wp option update home "$URL" --path="$WP" --allow-root

if [ ! -f "$WP/wp-content/themes/hello-elementor/style.css" ]; then
  wp theme install hello-elementor --path="$WP" --allow-root || true
fi

wp theme activate nextgentutors-beyondinfinity --path="$WP" --allow-root

for plugin in \
  NextGenTutors-Companion/nextgencompanion \
  NextGenTutors-AI-Integration/nextgentutors-ai-integration \
  NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager \
  NextGenTutors-Html-Importer/revamp-html-importer \
  NextGenTutors-Mission-Control/nextgentutors-mission-control \
  NextGenTutors-BeyondMeasure/nextgentutors-beyond-measure \
  nextgen-automation-hub/nextgen-automation-hub \
  nextgen-command-center/nextgen-command-center \
  nextgen-completion-suite/nextgen-completion-suite
do
  echo "Activating $plugin ..."
  wp plugin activate "$plugin" --path="$WP" --allow-root || echo "WARN: could not activate $plugin"
done

wp rewrite structure '/%postname%/' --hard --path="$WP" --allow-root || true
wp option update timezone_string 'Africa/Johannesburg' --path="$WP" --allow-root || true

echo "=== Active theme ==="
wp theme list --status=active --path="$WP" --allow-root
echo "=== Active plugins ==="
wp plugin list --status=active --path="$WP" --allow-root
echo "=== siteurl/home ==="
wp option get siteurl --path="$WP" --allow-root
wp option get home --path="$WP" --allow-root
