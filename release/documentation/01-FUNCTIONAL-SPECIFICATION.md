# 01 — Functional Specification

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Purpose

NextGen Tutors is a South African tutoring marketplace: parents/guardians enrol learners (including minors), tutors apply and get approved, matching connects demand to supply, bookings and payments settle lessons, and ops staff govern safety, finance, and AI-assisted workflows.

**Business SSOT:** `config/nextgentutors-business-profile.json`  
Currency ZAR · Timezone Africa/Johannesburg · Phone 0813340625 · support@nextgentutors.co.za · https://www.nextgentutors.co.za

## 2. Actors and portals

| Role (WP) | Portal / surface | Primary jobs |
|-----------|------------------|--------------|
| Guest | Public theme (BeyondInfinity) | Browse tutors, start registration |
| `ngt_parent` | Parent dashboard / forms | Register household, book, pay, message |
| `ngt_student` | Student views (guardian-linked) | Lesson history, learning progress |
| `ngt_tutor_applicant` | Application flows | Submit credentials / documents |
| `ngt_tutor` | Tutor dashboard | Accept matches, calendar, payouts |
| Ops roles (`ngc_*` via role map) | WP Admin + Mission Control | Approve tutors, safeguarding, finance, AI ops |
| Administrator | WP Admin + Setup Wizard | Provision, configure, verify |

Role map is defined in the business profile (`role_map`). Capability details: see Companion roles provisioning step (`roles`).

## 3. Core capabilities

| Capability | Status | Owner package | Notes |
|------------|--------|---------------|-------|
| Public site / kinetic UI | VERIFIED | BeyondInfinity 1.9.17 | Presentation only; no domain tables |
| Domain rules + `wp_ngc_*` tables | VERIFIED | Companion 1.9.5 | Matching, bookings, payments, workflows |
| Parent/minor registration | VERIFIED (code) | Companion forms + roles | Safeguarding constraints apply |
| Tutor application + approval | VERIFIED (code) | Companion | Ops approval queue |
| Smart matching | VERIFIED (code) | Companion matching | AI assist optional via AI-Integration |
| Booking + commerce | PARTIAL | Companion + Woo + Amelia (if present) | Live PayFast secrets OPEN |
| Wallet / ledger / payouts | VERIFIED (code) | Companion finance | External payouts gated in demo |
| CRM / email | PARTIAL | FluentCRM + FluentSMTP | SMTP secrets OPEN |
| AI matching / agents | PARTIAL | AI-Integration + Companion AI | BYOK keys never packaged |
| Mission Control orchestrator | VERIFIED | Mission-Control | Shares state with `wp ngt system` |
| Plugin fleet health | VERIFIED | Plugin-Manager | Scan / repair console |
| HTML import | VERIFIED | Html-Importer | Migration tool, not day-2 required |
| UI Library components | VERIFIED | ngt-ui-library + bridges | Theme/Companion consumers |
| Versioned 32-step provision | VERIFIED | `NGC_Provisioning_Engine` | Admin `ngc-setup-wizard` + CLI |
| Phase 14 relational demo | COMPLETE WITH LIMITATIONS | Companion demo suite | See doc 13 |

## 4. Functional journeys (summary)

| Journey ID | Actor | Outcome |
|------------|-------|---------|
| JOURNEY-PARENT-001 | Parent | Register → link minor → request match |
| JOURNEY-TUTOR-001 | Tutor applicant → tutor | Apply → ops approve → go live |
| MATCH-* | Parent/ops/AI | Produce ranked tutor shortlist |
| BOOK-* | Parent/tutor | Schedule lesson; payment path |
| FIN-* | Finance/parent | Checkout, ITN, ledger, refund/payout |
| JOURNEY-OPS-001 | Ops | Demo Control / provision / verify |

Catalogue files: `.agent-audit/demo/journeys/`. Runtime completeness varies — treat Phase 14 as **COMPLETE WITH LIMITATIONS**.

## 5. Explicit non-goals (this release)

- Production host configuration or live deploy (NOT authorized).
- Packaging PayFast / SMTP / AI secrets into zips.
- Claiming PDF manuals exist.
- Inventing lesson prices, commission %, or legal addresses (see INPUTS-REQUIRED).

## 6. Acceptance criteria (operator)

| Gate | Criterion |
|------|-----------|
| Identity | Business profile applied; timezone Johannesburg; currency ZAR |
| Stack | Theme + Companion active; first-party plugins detected |
| Provision | `wp ngt provision catalogue` lists 32 steps; run succeeds or PARTIAL with documented warnings |
| Verify | `wp ngt system verify` (or provision step `verification`) produces evidence |
| Secrets | PayFast/SMTP/AI entered only in their settings screens |
| Demo (staging) | Demo mode on; seed labelled `is_demo`; resettable |
