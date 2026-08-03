#!/usr/bin/env bash
# Verify WooCommerce SKUs / tutor payout meta (IMPORTANT/Verification-of-woo-products.sh).
set -euo pipefail

WP_ROOT="${WP_ROOT:-.}"
SKU_PREFIX="${SKU_PREFIX:-NGT}"
SAMPLE_SKU="${SAMPLE_SKU:-NGT-ONLINE-T1-R-7-1HR}"
PAYOUT_META="${PAYOUT_META:-_ngc_tutor_payout}"
LEGACY_META="${LEGACY_META:-_ngt_tutor_payout}"
EXPECTED_PAYOUT="${EXPECTED_PAYOUT:-}"

cd "$WP_ROOT"

echo "== SKUs matching ${SKU_PREFIX} =="
wp product list --field=sku 2>/dev/null | grep -E "$SKU_PREFIX" || {
  echo "No products with SKU prefix $SKU_PREFIX" >&2
  exit 1
}

PID="$(wp product list --sku="$SAMPLE_SKU" --field=id 2>/dev/null | head -n1 || true)"
if [[ -z "$PID" ]]; then
  echo "Sample SKU not found: $SAMPLE_SKU (ok if catalogue differs)"
  exit 0
fi

echo "== Meta for $SAMPLE_SKU (id=$PID) =="
VAL="$(wp product meta get "$PID" "$PAYOUT_META" 2>/dev/null || true)"
if [[ -z "$VAL" ]]; then
  VAL="$(wp product meta get "$PID" "$LEGACY_META" 2>/dev/null || true)"
fi
echo "payout_meta=$VAL"
if [[ -n "$EXPECTED_PAYOUT" && "$VAL" != "$EXPECTED_PAYOUT" ]]; then
  echo "Expected payout $EXPECTED_PAYOUT, got $VAL" >&2
  exit 2
fi
