#!/bin/bash
set -eu
cd /var/www/html
grep -E '\$wp_version' wp-includes/version.php | head -1
php -r 'require "wp-includes/version.php"; echo "FS=".$wp_version."\n";'
if ! command -v wp >/dev/null 2>&1; then
  curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x /usr/local/bin/wp
fi
wp --allow-root --path=/var/www/html --skip-plugins --skip-themes core version || true
if [ -f /tmp/wordpress-7.1.zip ]; then
  echo "Updating from local zip..."
  wp --allow-root --path=/var/www/html --skip-plugins --skip-themes core update /tmp/wordpress-7.1.zip || true
  wp --allow-root --path=/var/www/html --skip-plugins --skip-themes core update-db || true
fi
echo "FINAL=$(wp --allow-root --path=/var/www/html --skip-plugins --skip-themes core version)"
