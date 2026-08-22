# 09 — Security & Privacy Model

## Unofficial transport disclosure

Admin UI must show: **Unofficial WhatsApp Web automation — not Meta Cloud API. Ban/session risks apply.**

## Auth

- OpenWA-Dev API key in Bridge secrets / encrypted options
- Webhook HMAC verification (OpenWA-Dev) + timestamp/replay window
- WP caps: view sessions, reconnect, send test, broadcast, view history
- Default DENY via policy bridge for `whatsapp.*`

## Child / parent rules

- Do not WhatsApp a known minor student number by default
- Prefer `ngc_parent_user_id` / Child_Learners guardian contacts
- Marketing WhatsApp requires explicit channel consent beyond POPIA if required by policy

## Anti-abuse

Rate limits, broadcast role gate, duplicate suppression, media size limits, opt-out/blacklist, cooldown.

## Data minimization

Audit stores recipient hash/E.164, template id, result, correlation — not full bodies when sensitive.

## Network

API `:2785` and dashboard `:2886` **private** Docker network by default; WP → OpenWA only.
