# 09 — Configuration Register

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

Full open-secret register: [`../../INPUTS-REQUIRED.md`](../INPUTS-REQUIRED.md) (also `release/INPUTS-REQUIRED.md`).

## 1. Business SSOT (safe values)

| Key | Value | Source |
|-----|-------|--------|
| Legal / trading | Next Gen Tutors / NextGenTutors | `config/nextgentutors-business-profile.json` |
| Website | https://www.nextgentutors.co.za | SSOT |
| Phone | 0813340625 | SSOT |
| Support email | support@nextgentutors.co.za | SSOT |
| Admin email | admin@nextgentutors.co.za | SSOT |
| Notification email | marvin.saunders@gmail.com | SSOT — confirm for prod (IN-016) |
| Timezone | Africa/Johannesburg | SSOT + provision step `wordpress-baseline` |
| Currency | ZAR | SSOT |
| Learning modes | Online, In Person, Hybrid | SSOT |
| Grades | 1–12 + Tertiary | SSOT |
| ID prefixes | NGT-T / NGT-S / NGT-P | SSOT |

## 2. WordPress options (provision-touched)

| Option | Expected | Set by |
|--------|----------|--------|
| `timezone_string` | Africa/Johannesburg | `wordpress-baseline` |
| `permalink_structure` | `/%postname%/` | `wordpress-baseline` |
| `date_format` / `time_format` | Y-m-d / H:i | `wordpress-baseline` |
| `ngc_provisioning_state` | Engine state JSON | Provisioning engine |
| `ngc_provisioning_lock` | Lock or absent | Engine lock TTL 900s |
| `ngt_system_orchestrator_state` | Orchestrator shared state | `wp ngt system` / Mission Control |
| Demo options | e.g. `ngc_demo_password` | Demo suite only |

## 3. Secrets — never in repo or release zips

| ID | System | Status |
|----|--------|--------|
| IN-001–004 | PayFast | OPEN |
| IN-005 | FluentSMTP | OPEN |
| IN-006–007 | AI BYOK / HMAC | OPEN |
| IN-008–012 | Pricing / fees / booking windows | OPEN (business) |
| IN-013–015 | Legal / banking | OPEN |
| IN-016 | Notification mailbox | OPEN |
| IN-017–020 | Tax / safeguarding / CRM / deploy auth | OPEN |

## 4. Feature flags / modes

| Control | Production expectation |
|---------|------------------------|
| Demo mode | OFF |
| PayFast sandbox | OFF for live; ON for staging |
| CRM marketing send | false until IN-019 approved |
| AI live | Optional; requires keys |

## 5. Plugin matrix (first-party)

Companion · Mission Control · Plugin Manager · AI Integration · HTML Importer — paths as in `NGC_Provisioning_Engine::plugin_matrix()`.
