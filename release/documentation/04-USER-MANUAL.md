# 04 — User Manual

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Parents / guardians

| Task | How |
|------|-----|
| Create account | Use site registration / parent form on public pages |
| Add learner (minor) | Parent dashboard / student linking flows — guardian remains accountable |
| Find a tutor | Matching widget / Find a Tutor journey |
| Book a lesson | Booking flow (Amelia/Companion adapter when configured) |
| Pay | WooCommerce checkout → PayFast (sandbox or live per env) |
| Get help | support@nextgentutors.co.za · phone 0813340625 |

## 2. Tutors

| Task | How |
|------|-----|
| Apply | Tutor application form → status `ngt_tutor_applicant` |
| Approval | Wait for ops; then role becomes `ngt_tutor` |
| Manage availability | Tutor calendar / dashboard |
| Payouts | Finance surfaces after verified earnings (policy OPEN — IN-015) |

## 3. Students

Student accounts are typically guardian-linked. Learning progress may surface via LMS (MasterStudy) when that plugin is active — PARTIAL if LMS not installed.

## 4. Operators (WP Admin)

| Task | Screen / command |
|------|------------------|
| First-time setup | **Setup Wizard** (`ngc-setup-wizard`) or `wp ngt provision run` |
| System inspect | `wp ngt system inspect` |
| Safe configure | `wp ngt system configure --force-safe` |
| Demo seed (non-prod) | Platform → Demo Control Centre or `wp ngt system seed --allow-demo` |
| Fleet health | NextGenTutors Plugin Manager |
| Mission Control | Mission Control app (orchestrator actions) |
| AI keys | AI Integration / Companion AI settings — never in zip |
| PayFast / SMTP | Woo Payments · FluentSMTP settings |

## 5. Demo personas (staging only)

Demo mode required. Credentials shown only in Demo Control Centre. Do **not** enable demo mode in production. Reset via CLI/`demo_reset` with gates.

## 6. Support contacts (SSOT)

| Channel | Value |
|---------|-------|
| Website | https://www.nextgentutors.co.za |
| Support email | support@nextgentutors.co.za |
| Phone / WhatsApp | 0813340625 / +27 81 334 0625 |
| Admin email (profile) | admin@nextgentutors.co.za |

Notification mailbox confirmation is **OPEN** (IN-016).
