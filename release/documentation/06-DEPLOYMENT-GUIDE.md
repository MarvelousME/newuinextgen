# 06 — Deployment Guide

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

> **Production deploy is NOT authorized in this session.** Do not push live until IN-020 is explicitly approved and backups are proven.

## 1. Artifacts

| Artifact | Notes |
|----------|-------|
| Theme zip | BeyondInfinity 1.9.17 |
| Companion zip | 1.9.5 |
| Side plugins | AI-Integration, Mission-Control, Plugin-Manager, Html-Importer |
| SHA-256 | `.agent-audit/evidence/release/SHA256-BI-1.9.17-NGC-1.9.5-2026-08-02.txt` (and prior) |
| Secrets | **Never** in zips — see `release/INPUTS-REQUIRED.md` |

## 2. Local (Docker)

| Item | Value |
|------|-------|
| URL | http://localhost:8900 |
| Compose | `docker/docker-compose.yml` (path may vary by operator checkout) |
| WP-CLI | `docker compose … exec -T wordpress wp … --allow-root` |

Recommended order: inspect → preflight → provision dry-run → provision/configure → verify → optional demo seed.

## 3. Staging / production path (authorized operators only)

| Phase | Actions |
|-------|---------|
| Pre | Backup DB + files; prove restore (step `backups` is awareness only) |
| Install | Upload/activate theme + plugins; Hello Elementor parent present |
| Baseline | `wp ngt provision run` or Setup Wizard; timezone Johannesburg |
| Secrets | PayFast, FluentSMTP, AI keys via UI — not CLI paste into repo |
| Commerce | Products/prices only after IN-008 business approval |
| Hardening | Provision step `hardening`; disable demo mode; file permissions |
| Verify | `wp ngt system verify` + smoke journeys |
| Go-live | Explicit authorization; DNS/SSL host-specific (UNVERIFIED here) |
| Rollback | Prior zip + DB restore; `wp ngt provision rollback --step=<id>` for step-level where implemented |

## 4. Environment flags

| Flag / option | Intent |
|---------------|--------|
| `--force-safe` | Prefer safe/idempotent configure paths |
| `--allow-demo` | Permit demo seed |
| `--dry-run` | Plan only |
| Demo mode | Must stay **off** in production |

## 5. Post-deploy checklist

- [ ] Site URL / home match intended host  
- [ ] `Africa/Johannesburg` timezone  
- [ ] Permalinks `/%postname%/`  
- [ ] Companion tables present  
- [ ] PayFast ITN URL reachable (staging/live as approved)  
- [ ] SMTP test message received  
- [ ] Demo mode off; no demo selectors in prod UI  
- [ ] Evidence exported and filed under `.agent-audit/evidence/`  

Diagram: [`../diagrams/13-deployment-topology.svg`](../diagrams/13-deployment-topology.svg)
