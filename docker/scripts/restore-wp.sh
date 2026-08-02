#!/usr/bin/env bash
# Restore from backup-wp.sh output.
set -euo pipefail
COMPOSE_FILE="${COMPOSE_FILE:-docker/docker-compose.yml}"
BACKUP_DIR="${1:?Usage: restore-wp.sh <backup-dir>}"

gunzip -c "${BACKUP_DIR}/db.sql.gz" | docker compose -f "$COMPOSE_FILE" exec -T db sh -c 'mysql -uwordpress -pwordpress wordpress'
if [[ -f "${BACKUP_DIR}/uploads.tgz" ]]; then
  docker compose -f "$COMPOSE_FILE" exec -T wordpress sh -c 'cd /var/www/html/wp-content && tar xzf -' < "${BACKUP_DIR}/uploads.tgz"
fi
echo "Restore complete. Run: wp ngc audit verify && wp ngc queue work"
