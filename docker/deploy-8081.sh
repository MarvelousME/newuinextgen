#!/usr/bin/env bash
# Deploy newuinextgen stack. Docker publishes WP on ${WP_PORT} (default 18080).
# Public URL remains http://localhost:8081 via host proxy (proxy-8081.ps1).
set -euo pipefail
cd /mnt/c/Users/marvi/Downloads/wetransfer_newuinextgen_2026-07-07_1929/newuinextgen/docker

# Read selected keys without sourcing (comments may contain shell metacharacters)
WP_PORT=$(grep -E '^WP_PORT=' .env 2>/dev/null | head -1 | cut -d= -f2- || true)
WP_URL=$(grep -E '^WP_URL=' .env 2>/dev/null | head -1 | cut -d= -f2- || true)
WP_PORT="${WP_PORT:-8081}"
WP_URL="${WP_URL:-http://127.0.0.1:8081}"

echo "=== Containers ==="
docker ps -a --format '{{.Names}} | {{.Ports}} | {{.Status}}' | head -40

echo "=== Bring up stack (WP host port ${WP_PORT}) ==="
docker-compose down --remove-orphans || true
docker-compose up -d --no-deps db
echo "Waiting for db healthy..."
for i in $(seq 1 40); do
  st=$(docker inspect -f '{{.State.Health.Status}}' newuinextgen_db_1 2>/dev/null || echo none)
  echo "db=$st"
  [ "$st" = healthy ] && break
  sleep 3
done

set +e
docker-compose up -d
COMPOSE_RC=$?
set -e
if [ "$COMPOSE_RC" -ne 0 ]; then
  echo "Full compose had errors — starting wordpress with --no-deps"
  docker-compose up -d --no-deps wordpress
fi

echo "Waiting for WordPress HTTP on ${WP_PORT}..."
for i in $(seq 1 40); do
  code=$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 3 "http://127.0.0.1:${WP_PORT}/" || echo 000)
  echo "HTTP=$code"
  case "$code" in
    200|301|302|303|307|308) break ;;
  esac
  sleep 3
done

echo "=== WP setup / activate theme + plugins (URL=${WP_URL}) ==="
docker-compose --profile setup run --rm --entrypoint sh wpcli -c "
set -eu
WP=/var/www/html
URL='${WP_URL}'
wp core is-installed --path=\"\$WP\" --allow-root || wp core install \
  --path=\"\$WP\" \
  --url=\"\$URL\" \
  --title='NextGen Tutors' \
  --admin_user='admin' \
  --admin_password='NextGenAdmin!2026' \
  --admin_email='admin@nextgentutors.local' \
  --skip-email --allow-root

wp option update siteurl \"\$URL\" --path=\"\$WP\" --allow-root
wp option update home \"\$URL\" --path=\"\$WP\" --allow-root

if [ ! -f \"\$WP/wp-content/themes/hello-elementor/style.css\" ]; then
  wp theme install hello-elementor --path=\"\$WP\" --allow-root || true
fi

wp theme activate nextgentutors-beyondinfinity --path=\"\$WP\" --allow-root

for plugin in \
  'NextGenTutors-Companion/nextgencompanion' \
  'NextGenTutors-AI-Integration/nextgentutors-ai-integration' \
  'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager' \
  'NextGenTutors-Html-Importer/revamp-html-importer' \
  'NextGenTutors-Mission-Control/nextgentutors-mission-control' \
  'NextGenTutors-BeyondMeasure/nextgentutors-beyond-measure' \
  'nextgen-automation-hub/nextgen-automation-hub' \
  'nextgen-command-center/nextgen-command-center' \
  'nextgen-completion-suite/nextgen-completion-suite'
do
  echo \"Activating \$plugin ...\"
  wp plugin activate \"\$plugin\" --path=\"\$WP\" --allow-root || echo \"WARN: could not activate \$plugin\"
done

wp rewrite structure '/%postname%/' --hard --path=\"\$WP\" --allow-root || true
wp option update timezone_string 'Africa/Johannesburg' --path=\"\$WP\" --allow-root || true

if [ -f /setup/apply-defaults.sh ]; then
  /bin/sh /setup/apply-defaults.sh || true
fi

echo '=== Active theme ==='
wp theme list --status=active --path=\"\$WP\" --allow-root
echo '=== Active plugins ==='
wp plugin list --status=active --path=\"\$WP\" --allow-root
"

echo "=== Probe docker port ${WP_PORT} ==="
curl -s -o /dev/null -w "http://127.0.0.1:${WP_PORT}/ -> %{http_code}\n" "http://127.0.0.1:${WP_PORT}/" || true
docker-compose ps
