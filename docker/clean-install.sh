#!/usr/bin/env bash
# Clean-room install from PRODUCTION ZIPs (no monorepo mounts).
# Uses host-network compose when Docker bridge networking is unavailable.
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
PROD_PKGS="$HERE/../PRODUCTION/01-INSTALLABLE-PACKAGES"
cd "$HERE"

if ! ls "$PROD_PKGS"/NextGenTutors-Companion-v*.zip >/dev/null 2>&1; then
  echo "Missing PRODUCTION packages. Run: python3 scripts/build-production-release.py" >&2
  exit 1
fi

COMPOSE=( -p ngt-clean-install -f docker-compose.clean-install.host.yml )

echo "Starting clean-install stack (http://127.0.0.1:8999)..."
docker compose "${COMPOSE[@]}" down -v --remove-orphans || true
docker compose "${COMPOSE[@]}" up -d

echo "Waiting for WordPress (45s)..."
sleep 45

echo "Installing ZIPs via WP-CLI..."
docker compose "${COMPOSE[@]}" --profile setup run --rm wpcli
CODE=$?

REPORT="$HERE/../PRODUCTION/04-VALIDATION/INSTALLATION-TEST.md"
STAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
{
  echo ""
  echo ""
  echo "## Latest clean-install run"
  echo "- UTC: $STAMP"
  echo "- WP-CLI exit: $CODE"
  echo "- URL: http://127.0.0.1:8999"
  echo "- Compose: docker-compose.clean-install.host.yml (host network)"
} >> "$REPORT"

if [ "$CODE" -ne 0 ]; then
  echo "Clean-install WP-CLI failed - do not label PRODUCTION PASS." >&2
  exit "$CODE"
fi

echo ""
echo "Clean install WP-CLI succeeded."
echo "  WordPress: http://127.0.0.1:8999"
echo "  Admin:     http://127.0.0.1:8999/wp-admin"
echo "Visit Home, Find a Tutor, Login with JS on/off before signing RELEASE-ACCEPTANCE.md."
