#!/usr/bin/env bash
# Parameterized WP DB backup with optional AES-256 encrypt (POPIA at-rest).
# Absorbs IMPORTANT/Create-Backup-Script.sh — no hardcoded host paths.
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups/db}"
WP_ROOT="${WP_ROOT:-.}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
NOTIFY_EMAIL="${NOTIFY_EMAIL:-}"
PREFIX="${BACKUP_PREFIX:-ngc_wp}"
DATE="$(date +%Y-%m-%d_%H%M%S)"

mkdir -p "$BACKUP_DIR"
cd "$WP_ROOT"

if ! command -v wp >/dev/null 2>&1; then
  echo "WP-CLI not found. Set WP_ROOT and ensure wp is on PATH." >&2
  exit 1
fi

SQL="$BACKUP_DIR/${PREFIX}_${DATE}.sql"
wp db export "$SQL" --add-drop-table --quiet
gzip -9 "$SQL"
GZ="${SQL}.gz"
FINAL="$GZ"

if [[ -n "${NGT_BACKUP_KEY:-${NGC_BACKUP_KEY:-}}" ]] && command -v openssl >/dev/null 2>&1; then
  ENC="${GZ}.enc"
  # Prefer NGC_BACKUP_KEY; accept legacy NGT_BACKUP_KEY.
  export NGC_BACKUP_KEY="${NGC_BACKUP_KEY:-$NGT_BACKUP_KEY}"
  openssl enc -aes-256-cbc -salt -pbkdf2 -in "$GZ" -out "$ENC" -pass env:NGC_BACKUP_KEY
  rm -f "$GZ"
  FINAL="$ENC"
  if ! openssl enc -d -aes-256-cbc -pbkdf2 -in "$FINAL" -pass env:NGC_BACKUP_KEY 2>/dev/null | gzip -t 2>/dev/null; then
    msg="Backup integrity check failed for $FINAL"
    echo "$msg" >&2
    [[ -n "$NOTIFY_EMAIL" ]] && echo "$msg" | mail -s "NGC Backup ERROR" "$NOTIFY_EMAIL" || true
    exit 2
  fi
fi

find "$BACKUP_DIR" -type f \( -name '*.enc' -o -name '*.sql.gz' \) -mtime +"$RETENTION_DAYS" -delete

echo "DB backup complete: $FINAL"
[[ -n "$NOTIFY_EMAIL" ]] && echo "DB Backup Complete: $FINAL" | mail -s "NGC Backup Success" "$NOTIFY_EMAIL" || true
