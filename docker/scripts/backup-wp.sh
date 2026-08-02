#!/usr/bin/env bash
# Backup WordPress MySQL + uploads (Linux/Coolify).
set -euo pipefail
COMPOSE_FILE="${COMPOSE_FILE:-docker/docker-compose.yml}"
OUT_ROOT="${OUT_ROOT:-docker/backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
DEST="${OUT_ROOT}/ngt-${STAMP}"
mkdir -p "$DEST"

echo "Backing up DB → ${DEST}/db.sql.gz"
docker compose -f "$COMPOSE_FILE" exec -T db sh -c 'mysqldump -uwordpress -pwordpress wordpress' | gzip > "${DEST}/db.sql.gz"

echo "Backing up uploads → ${DEST}/uploads.tgz"
docker compose -f "$COMPOSE_FILE" exec -T wordpress sh -c 'cd /var/www/html/wp-content && tar czf - uploads' > "${DEST}/uploads.tgz"

(
  cd "$DEST"
  sha256sum db.sql.gz uploads.tgz > MANIFEST.sha256
)

echo "Backup complete: ${DEST}"
