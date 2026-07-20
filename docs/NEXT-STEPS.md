# NextGen Tutors — Prioritized Next Steps

**Updated:** 2026-07-06 · Stack v1.9.0

---

## Completed (gap closure pass)

| Item | Status |
|------|--------|
| Demo roster gate | `ngt_demo_tutors_enabled()` |
| WF-09 auto-assign | `NGC_Matching::maybe_auto_accept()` |
| PayFast gateway | `NGC_PayFast_Gateway` + ITN |
| Elementor widgets | Hero, Tutor Cards, Pricing Cards |
| Integrate specs workflow-06…10 | Replaces `automations/` JSON |
| Playwright WF-08/11/12/14/25 | 8 specs · 28 tests |
| Docker phase2 stack | Woo + Elementor + FluentCRM + GamiPress |
| `wp ngc ui_seed_cms` | `scripts/seed-ui-cms-docker.ps1` |

---

## Immediate (this sprint)

| # | Task | Owner | Command / path |
|---|------|-------|----------------|
| 1 | Install integration stack on Docker | Ops | `powershell -File docker/scripts/install-phase2-stack.ps1` |
| 2 | Re-run full verify after deploy | Dev | `scripts/run-ui-library-audit.ps1` + `run-playwright.ps1` |
| 3 | Visual QA home + pricing + find-a-tutor on mobile | QA | Manual @ :8899 |

---

## Short term (1–2 weeks)

| # | Task | Why |
|---|------|-----|
| 4 | Amelia zip in Docker packages + activation | Unblock WF-10 Amelia sync E2E |
| 5 | Authenticated Playwright flows (match assign, checkout) | Move REST smoke → full journeys |
| 6 | Refresh flow audit after integrate import | Target ≥12 workflows **full** |
| 7 | Refresh `PAGES-AUDIT-REPORT.md` runtime section | Post-integration smoke |

---

## Medium term

| # | Task | Why |
|---|------|-----|
| 8 | PayFast production credentials + ITN URL whitelist | Go-live payments |
| 9 | PNG/PDF diagram export pipeline | Enterprise deliverable |
| 10 | POPIA consent audit sign-off on `NGC_Platform_Tracking` | Compliance sign-off |

---

## Verification commands (copy-paste)

```powershell
php NextGenTutors-Companion/scripts/validate.php
php NextGenTutors-Companion/scripts/verify-ui-library.php
powershell -File scripts/run-flow-audit.ps1
powershell -File docker/scripts/install-phase2-stack.ps1
powershell -File scripts/run-playwright.ps1
powershell -File scripts/verify-solution.ps1
```

---

## Definition of done (release candidate)

- [ ] All 28 Playwright tests pass on staging URL
- [ ] `verify-ui-library.php` PASS with zero hardcode issues
- [ ] CMS homepage sections editable and reflected on `/`
- [ ] Tutor CPT populated (no static roster on marketplace)
- [ ] Flow audit: ≥12 workflows **full**
- [ ] PayFast sandbox checkout completes on Docker
- [ ] `BI_VERSION` === `NGC_VERSION` === release tag
