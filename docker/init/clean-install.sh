#!/bin/sh
# Install PRODUCTION ZIPs into a vanilla WordPress (no source mounts).
set -eu

WP_PATH="/var/www/html"
PKG="/packages"
log() { printf '[clean-install] %s\n' "$1"; }

wait_for_file() {
  file="$1"
  i=0
  while [ ! -f "$file" ]; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
      log "Timeout waiting for $file"
      exit 1
    fi
    sleep 2
  done
}

wait_for_db() {
  i=0
  # Avoid `wp db check` / mariadb-check --skip-ssl hangs against MySQL 8.
  # WORDPRESS_DB_HOST may be "db" or "127.0.0.1:3307".
  while ! php -r '
$raw = getenv("WORDPRESS_DB_HOST") ?: "db";
$host = $raw; $port = 3306;
if (strpos($raw, ":") !== false) { list($host, $port) = explode(":", $raw, 2); $port = (int)$port; }
$user = getenv("WORDPRESS_DB_USER") ?: "wordpress";
$pass = getenv("WORDPRESS_DB_PASSWORD") ?: "wordpress";
$name = getenv("WORDPRESS_DB_NAME") ?: "wordpress";
mysqli_report(MYSQLI_REPORT_OFF);
$m = @new mysqli($host, $user, $pass, $name, $port);
if ($m->connect_errno) { exit(1); }
$m->close();
'; do
    i=$((i + 1))
    if [ "$i" -ge 45 ]; then
      log "Database not reachable"
      exit 1
    fi
    sleep 2
  done
}

pick_zip() {
  pattern="$1"
  found="$(ls -1 ${PKG}/${pattern} 2>/dev/null | head -n 1 || true)"
  if [ -z "$found" ]; then
    log "MISSING package matching ${pattern}"
    exit 1
  fi
  printf '%s' "$found"
}

log "Waiting for WordPress core..."
wait_for_file "${WP_PATH}/wp-config.php"
wait_for_db

mkdir -p "${WP_PATH}/wp-content/upgrade" "${WP_PATH}/wp-content/themes" "${WP_PATH}/wp-content/plugins" "${WP_PATH}/wp-content/uploads" "${WP_PATH}/wp-content/upgrade-temp-backup"
chown -R www-data:www-data "${WP_PATH}/wp-content" || true
chmod -R a+rwX "${WP_PATH}/wp-content" || true

if ! wp core is-installed --path="$WP_PATH" --allow-root 2>/dev/null; then
  log "Installing WordPress..."
  wp core install \
    --path="$WP_PATH" \
    --url="${WP_URL:-http://localhost:8891}" \
    --title="${WP_TITLE:-NextGen Tutors Clean Install}" \
    --admin_user="${WP_ADMIN_USER:-admin}" \
    --admin_password="${WP_ADMIN_PASSWORD:-NextGenAdmin!2026}" \
    --admin_email="${WP_ADMIN_EMAIL:-admin@nextgentutors.local}" \
    --skip-email \
    --allow-root
fi

HELLO="$(pick_zip 'Hello-Elementor-v*.zip')"
log "Installing parent theme $HELLO"
wp theme install "$HELLO" --force --path="$WP_PATH" --allow-root
wp theme activate hello-elementor --path="$WP_PATH" --allow-root

install_plugin() {
  zip="$1"
  log "Installing plugin $zip"
  wp plugin install "$zip" --force --activate --path="$WP_PATH" --allow-root
}

install_plugin "$(pick_zip 'NextGenTutors-Companion-v*.zip')"
install_plugin "$(pick_zip 'NextGenTutors-Plugin-Manager-v*.zip')"
install_plugin "$(pick_zip 'NextGenTutors-Mission-Control-v*.zip')"
install_plugin "$(pick_zip 'nextgen-3d-scroll-manager-v*.zip')"
install_plugin "$(pick_zip 'nextgen-3d-filmstrip-v*.zip')"
install_plugin "$(pick_zip 'nextgen-subjects-widget-v*.zip')"

# Html Importer is optional in the required visual stack but ships in 01 — install, leave inactive after.
IMP="$(ls -1 ${PKG}/NextGenTutors-Html-Importer-v*.zip 2>/dev/null | head -n 1 || true)"
if [ -n "$IMP" ]; then
  log "Installing Html Importer (inactive after install)"
  wp plugin install "$IMP" --force --path="$WP_PATH" --allow-root
fi

THEME="$(pick_zip 'NextGenTutors-BeyondInfinity-v*.zip')"
log "Installing child theme $THEME (branded NextgenTutors-TutorFabulous)"
wp theme install "$THEME" --force --path="$WP_PATH" --allow-root
wp theme activate nextgentutors-tutorfabulous --path="$WP_PATH" --allow-root 2>/dev/null \
  || wp theme activate nextgentutors-beyondinfinity --path="$WP_PATH" --allow-root 2>/dev/null \
  || wp theme activate NextGenTutors-BeyondInfinity --path="$WP_PATH" --allow-root

if [ -f "${PKG}/drop-ins/ngt-ui-library.zip" ]; then
  log "Extracting UI library drop-in"
  php -r '$z=new ZipArchive(); if($z->open("/packages/drop-ins/ngt-ui-library.zip")===true){$z->extractTo("/var/www/html/wp-content/");$z->close(); echo "extracted\n";} else { fwrite(STDERR,"ui-library unzip failed\n"); exit(1);} '
fi

wp rewrite structure '/%postname%/' --hard --path="$WP_PATH" --allow-root || true
wp rewrite flush --path="$WP_PATH" --allow-root || true

log "Smoke checks..."
wp eval '
$ok = true;
$lines = [];
$lines[] = "NGC_Plugin=" . (class_exists("NGC_Plugin") ? "yes" : "NO");
$lines[] = "tutors_cpt=" . (post_type_exists("tutors") ? "yes" : "NO");
$lines[] = "theme=" . wp_get_theme()->get_stylesheet();
$lines[] = "parent=" . wp_get_theme()->get_template();
$lines[] = "ngc_find_tutor=" . (shortcode_exists("ngc_find_tutor_form") ? "yes" : "NO");
$log = WP_CONTENT_DIR . "/debug.log";
$fatals = 0;
if (file_exists($log)) {
  $fatals = preg_match_all("/PHP (Fatal|Parse) error/i", file_get_contents($log));
}
$lines[] = "php_fatals_in_debug_log=" . (int) $fatals;
echo implode("\n", $lines), "\n";
if (!class_exists("NGC_Plugin") || !post_type_exists("tutors") || !shortcode_exists("ngc_find_tutor_form")) {
  exit(1);
}
' --path="$WP_PATH" --allow-root

log "Clean install completed."
