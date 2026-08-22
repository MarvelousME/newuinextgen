# 12 — Test Plan

## Unit

Provider interface mocks; phone normalizer; template variable resolve; idempotency key; policy deny.

## Contract

OpenWA-Dev REST fixtures for send-text, health, session QR, webhook HMAC.

## Integration

Queue → worker → provider (testcontainer or staging OpenWA).

## Security

Invalid API key; bad HMAC; replay; unauthorized WP role; bulk denied; oversized media; malformed phone; provider disabled.

## Failure / degradation

Stop OpenWA → booking/payment still succeed; WA job → DLQ; circuit opens.

## E2E (headed, when infra available)

1. WP admin → Communications → WhatsApp  
2. Health + session connect/QR  
3. Trigger booking.confirmed (demo)  
4. Queue → worker → OpenWA request  
5. Status in WP + audit + DB rows  
6. Inbound signed webhook → contact resolve → event  

## Regression

Registration, tutor approval, find tutor, booking, PayFast, invoices, lesson join, CRM, email, agents.

## Evidence

DB before/after for messages, sessions, webhooks, templates; scrub secrets.
