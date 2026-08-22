# 13 — Rollback Plan

## Instant

1. `whatsapp.enabled=false`  
2. Channel policies force Email only  
3. `docker compose --profile whatsapp stop`  

Core platform unaffected.

## Provider rollback

Switch `whatsapp.provider=wa_automate` or `none`.

## Hard remove

Remove profile services; retain message tables for audit; theme `wa.me` unchanged.

## Verify

Booking smoke + email transactional + RAD gate + no fatal on missing OpenWA.
