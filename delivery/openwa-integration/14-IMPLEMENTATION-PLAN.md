# 14 — Implementation Plan

**Do not start Stage 2 until this plan is explicitly approved.**

## Recommendation (executive)

**PROCEED WITH CONDITIONS**

### Decision on OpenWA-Dev

| Mode | Apply |
|------|--------|
| USED AS A PROVIDER | **Yes** (primary candidate) |
| USED AS A FALLBACK | Legacy `@open-wa/wa-automate` during cutover |
| CONSOLIDATED | Theme OpenWA + Studio WA stubs → Companion communication layer |
| NOT embed / NOT second plugin | Mandatory |

### Conditions / blockers

1. Stakeholder acceptance of **unofficial WhatsApp Web** ban/ToS/session risk (staging first; no “official WABA” claims).  
2. Build **Notification + WhatsAppProviderInterface** in Companion **before** wiring OpenWA-Dev.  
3. Secrets leave theme Customizer plaintext.  
4. Child/parent recipient policy enforced before any student WA.  
5. OpenWA failure must not affect booking/payment.  
6. Prefer Postgres profile for OpenWA-Dev in shared infra; avoid duplicate Redis unless needed.  
7. OpenWA React dashboard = diagnostics deep-link only.

## Phased work

| Phase | Change | Files/Services | Risk | Tests | Rollback |
| ----- | ------ | -------------- | ---- | ----- | -------- |
| 0 | Discovery (this package) | `delivery/openwa-integration/*` | None | — | — |
| 1 | `NGC_Notification_Service` + channel policy + templates | `NextGenTutors-Companion/includes/communications/*` (new) | Med | unit | unused if flags off |
| 1 | `WhatsAppProviderInterface` + Noop | same | Low | unit | — |
| 1 | Phone E.164 helper | `includes/communications/class-ngc-phone.php` | Low | unit SA numbers | — |
| 2 | `OpenWaDevProvider` HTTP client | `includes/communications/providers/class-ngc-openwa-dev-provider.php` | Med | contract | provider flag |
| 2 | `WaAutomateProvider` wrap theme API | `.../class-ngc-wa-automate-provider.php` | Low | compat | — |
| 3 | Settings, secrets, health, session state | admin + options/vault | Med | health | flags off |
| 3 | Docker profile `whatsapp` | `docker/docker-compose.yml`, `docker/openwa/*` | Med | compose health | stop profile |
| 4 | Outbound transactional via queue | worker + hook booking/reminder events | High | integration | transactional flag off |
| 5 | Idempotency, retry, DLQ, statuses | message table + durable queue | Med | failure tests | — |
| 6 | Companion webhook receiver | `includes/rest/class-ngc-rest-whatsapp.php` | High | HMAC/replay | inbound flag off |
| 7 | FluentCRM phone resolve + tags | fluentcrm adapter hooks | Med | CRM e2e | — |
| 8 | Agent draft/approval modes | policy + agent control plane | High | security | ai flag off |
| 9 | Admin Communications → WhatsApp UI | admin shell catalog | Med | headed e2e | hide menu |
| 10 | E2E + regression + evidence | `FINAL-OPENWA-E2E-EVIDENCE-REPORT.md` | — | full | — |
| * | RAD manifest/capabilities | `architecture/manifests/*`, `capabilities/*` | Low | gate | — |
| * | Deprecate theme OpenWA paths | `inc/openwa.php` → thin delegate or disable | Med | regression | re-enable theme |

## Exact planned paths (Stage 2)

### New

| Path | Reason |
|------|--------|
| `NextGenTutors-Companion/includes/communications/class-ngc-notification-service.php` | Orchestrator |
| `NextGenTutors-Companion/includes/communications/interface-ngc-whatsapp-provider.php` | Port |
| `NextGenTutors-Companion/includes/communications/providers/class-ngc-openwa-dev-provider.php` | Adapter |
| `NextGenTutors-Companion/includes/communications/providers/class-ngc-wa-automate-provider.php` | Legacy adapter |
| `NextGenTutors-Companion/includes/communications/class-ngc-phone.php` | Normalization |
| `NextGenTutors-Companion/includes/communications/class-ngc-comm-templates.php` | Templates |
| `NextGenTutors-Companion/includes/rest/class-ngc-rest-whatsapp.php` | Webhook + admin REST |
| `NextGenTutors-Companion/includes/admin/class-ngc-communications-admin.php` | Control plane UI |
| `architecture/manifests/communication-whatsapp-openwa.json` | RAD |
| `architecture/capabilities/whatsapp.json` | Capabilities |
| `docker/openwa/README.md` | Deploy notes |

### Modify

| Path | Change |
|------|--------|
| `NextGenTutors-Companion/includes/class-ngc-plugin.php` | Register communications modules |
| `NextGenTutors-Companion/includes/class-ngc-database.php` | Message/session tables |
| `NextGenTutors-Companion/includes/workflows/class-ngc-transactional-mail.php` | Optional WA via orchestrator |
| `NextGenTutors-Companion/includes/integrations/class-ngc-session-reminders.php` | Optional WA channel |
| `NextGenTutors-Companion/includes/studio/class-ngc-studio-notifications.php` | Delegate WA to provider |
| `NextGenTutors-Companion/includes/admin/framework/class-ngc-admin-catalog.php` | Communications menu |
| `docker/docker-compose.yml` | profile whatsapp |
| `NextGenTutors-BeyondInfinity/inc/openwa.php` | Delegate or feature-flag off |
| `.github/workflows/ci.yml` | communications unit tests |

### Do not modify for WA

Payment/booking core success paths beyond async notify hooks; theme `wa.me` UX.

## Success criteria reminder

Governed capability, replaceable provider, degradation-safe, parent/child policy, evidence-backed — never PRODUCTION READY on unofficial transport without explicit risk acceptance.
