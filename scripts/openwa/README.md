# OpenWA Easy API — BeyondInfinity setup

This theme integrates with [@open-wa/wa-automate](https://github.com/open-wa/wa-automate-nodejs) **Easy API** for:

- Outbound WhatsApp when theme fallback forms are submitted
- Inbound message webhooks (logged + optional auto-reply)
- Admin status on **Appearance → Sync Launch Pages**

## Prerequisites

- Node.js 18+ and `npx`
- A WhatsApp account for the business number
- WordPress site reachable from the machine running wa-automate (for webhooks)

## 1. Start Easy API

Replace `YOUR_KEY` and your site URL:

```powershell
npx @open-wa/wa-automate `
  --port 8080 `
  --api-key "YOUR_KEY" `
  --webhook "https://YOUR-SITE/wp-json/bi/v1/openwa/webhook?token=YOUR_WEBHOOK_SECRET"
```

On first run, scan the QR code in the terminal or use link-code login.

**Tunnel (local dev):** add `--tunnel` so WordPress can reach your machine.

**PM2 (production):**

```powershell
npx @open-wa/wa-automate --pm2 --session-id nextgen --port 8080 --api-key "YOUR_KEY" --webhook "https://..."
```

Interactive API docs: `http://127.0.0.1:8080/api-docs/`

## 2. Configure WordPress

1. **Appearance → Customize → Integrations → OpenWA**
2. Enable **OpenWA integration**
3. Set **Easy API base URL** (e.g. `http://127.0.0.1:8080`)
4. Set **Easy API key** (same as `--api-key`)
5. Copy **Webhook secret** from Customizer into the `--webhook` URL `token=` query param (or use header `X-BI-Webhook-Secret`)
6. Optionally enable **WhatsApp admin on form submit** and **Auto-reply**

Webhook URL is also shown on **Sync Launch Pages** admin screen.

## 3. Verify

- **Sync Launch Pages** → OpenWA section shows connection state
- REST (logged-in admin): `GET /wp-json/bi/v1/openwa/status`
- Submit a theme form (find tutor, contact) — admin WhatsApp should receive a summary

## REST endpoints (theme)

| Method | Route | Auth |
|--------|-------|------|
| POST | `/wp-json/bi/v1/openwa/webhook` | Webhook secret |
| GET | `/wp-json/bi/v1/openwa/status` | `manage_options` |
| POST | `/wp-json/bi/v1/openwa/send` | `manage_options` |

Send body example:

```json
{ "to": "27813340625", "message": "Test from REST" }
```

## Hooks

- `bi_openwa_inbound_message` — after webhook parsed
- `bi_openwa_after_send` — after outbound attempt
- `bi_form_submitted` — after theme form queue + email
- `bi_openwa_form_notify_recipient` — filter admin notify phone

## Notes

- Frontend FAB still uses `wa.me` links; OpenWA handles server-side automation.
- Easy API must run on a host that can reach WhatsApp Web; WordPress only calls HTTP.
- **NOT PRODUCTION-VERIFIED** — test on staging before live traffic.
