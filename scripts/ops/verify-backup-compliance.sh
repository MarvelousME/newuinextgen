#!/usr/bin/env bash
# Lightweight POPIA backup compliance checks (IMPORTANT Verification&Compliance*.sh).
# Does not assume S3/DirectAdmin — set vars for your host.
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups/db}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
CRON_PATTERN="${CRON_PATTERN:-backup-db-encrypted|backup_db}"

echo "== Cron (optional) =="
if command -v crontab >/dev/null 2>&1; then
  crontab -l 2>/dev/null | grep -E "$CRON_PATTERN" || echo "No matching backup cron (configure on host)."
else
  echo "crontab not available"
fi

echo "== Backup directory =="
if [[ -d "$BACKUP_DIR" ]]; then
  COUNT="$(find "$BACKUP_DIR" -type f \( -name '*.enc' -o -name '*.sql.gz' \) | wc -l | tr -d ' ')"
  echo "files=$COUNT dir=$BACKUP_DIR retention_days=$RETENTION_DAYS"
  # Soft ceiling: ~retention + a few extras
  MAX=$((RETENTION_DAYS + 5))
  if [[ "$COUNT" -gt "$MAX" ]]; then
    echo "WARN: file count $COUNT exceeds soft ceiling $MAX — check rotation." >&2
  fi
else
  echo "BACKUP_DIR missing: $BACKUP_DIR"
fi

if [[ -n "${S3_BACKUP_URI:-}" ]] && command -v aws >/dev/null 2>&1; then
  echo "== S3 listing =="
  aws s3 ls "$S3_BACKUP_URI" ${AWS_PROFILE:+--profile "$AWS_PROFILE"}
fi

cat <<'EOF'
POPIA backup checklist:
  [ ] Encrypted at rest (AES-256 / S3 SSE)
  [ ] Retention aligns with recording policy (~30 days)
  [ ] Serialized WP data via `wp db export`
  [ ] Integrity check before deleting plaintext
  [ ] Success/failure notified to ops email
EOF
