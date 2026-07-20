# PayFast payout export

Monthly batch payouts create **pending** `ngc_payouts` records. Export a CSV for PayFast mass payment / EFT, then confirm after transfer.

## Workflow

1. **Cron or CLI batch** — creates pending payout records (earnings stay pending):
   ```bash
   wp ngc run_payout_batch
   ```
2. **Export CSV** — upload to PayFast or your bank batch tool:
   ```bash
   wp ngc export_payouts
   wp ngc export_payouts --status=preview   # preview from pending earnings only
   ```
3. **Confirm after transfer** — settles earnings:
   ```bash
   wp ngc confirm_payout 42
   ```

Or use **wp-admin → NextGen → Tutor Payouts** for export download and confirm links.

## CSV columns

| Column | Description |
|--------|-------------|
| `recipient_email` | Tutor WordPress email |
| `recipient_name` | Display name |
| `amount` | Decimal amount (ZAR) |
| `currency` | `ZAR` |
| `reference` | `NGC-PAYOUT-{id}` or `NGC-EARNINGS-{tutor_id}` for preview |
| `payout_id` | Internal payout ID (0 for preview rows) |

Files are written to `wp-content/uploads/ngc-exports/` when using CLI without `--output`.

## Immediate settlement (legacy)

Admin **Mark paid now** or filter `ngc_payout_auto_confirm` (default `true` for single-tutor admin action) creates and confirms in one step.

Batch cron uses `auto_confirm = false` so finance can export before settlement.

## Docker

```bash
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar ngc run_payout_batch --allow-root
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar ngc export_payouts --allow-root
```
