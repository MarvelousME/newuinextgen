# Regression tests

Reuse existing Playwright specs under `e2e/` against the clean-install URL (default http://localhost:8891):

- homepage display
- login role paths
- tutor profile / booking
- five-minute booking journey (needs Woo/PayFast to fully pass)

`scripts/run-playwright.ps1` is the host runner. Bind-mount developer Docker is not a substitute.