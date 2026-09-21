#!/bin/bash
set -euo pipefail
cd /var/www/html
if command -v wp >/dev/null 2>&1; then
  WP='wp --allow-root --path=/var/www/html'
else
  # Install WP-CLI if missing
  curl -sS -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x /usr/local/bin/wp
  WP='wp --allow-root --path=/var/www/html'
fi

echo "=== CORE ==="
$WP core version || true
echo "=== ACTIVE PLUGINS ==="
$WP plugin list --status=active --fields=name,version,update 2>/dev/null || true
echo "=== ALL PLUGINS ==="
$WP plugin list --fields=name,status,version,update 2>/dev/null || true
echo "=== PLUGIN DIRS ==="
ls -1 wp-content/plugins 2>/dev/null || true
