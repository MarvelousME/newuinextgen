# 11 — Deployment Plan

## Compose

Add optional Docker profile `whatsapp` (not default):

- Service `openwa` from Calanca/OpenWA-Dev image/build
- Ports published **only on internal network** (or localhost bind for staging)
- Volumes for session auth data
- Env: API keys, DB, webhook target = WP `ngc/v1/.../webhook`
- Healthcheck → Bridge `whatsapp.health`

Do **not** copy upstream compose blindly; map into existing `docker/` networks/secrets.

## Config flags

```text
whatsapp.enabled
whatsapp.provider = openwa_dev | wa_automate | none
whatsapp.transactional.enabled
whatsapp.marketing.enabled
whatsapp.inbound.enabled
whatsapp.ai.enabled
whatsapp.bulk.enabled
openwa.api_base_url
openwa.api_key_secret_ref
openwa.webhook_secret_ref
openwa.dashboard_url (deep-link)
```

## Migration from theme wa-automate

1. Deploy OpenWA-Dev beside stack  
2. Parallel provider flag  
3. Cut webhook to Companion receiver  
4. Disable theme OpenWA when green  
5. Keep `wa.me` FAB  

## Coolify / prod

Same private networking; Traefik only if dashboard intentionally exposed to admins with auth.
