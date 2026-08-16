# TC matrix headed run — 2026-08-16

## Delivered

| File | Coverage |
|---|---|
| `e2e/helpers/tc-matrix.ts` | Evidence dirs, persona login, soft site gate |
| `e2e/workflows/tc-01-15-functional.spec.ts` | TC-01…15 |
| `e2e/workflows/tc-16-20-integration.spec.ts` | TC-16…20 |
| `e2e/workflows/tc-21-26-security.spec.ts` | TC-21…26 |
| `e2e/TC-MATRIX.md` | Honesty map + runbook |

## Run (when WP is up)

```powershell
cd docker; docker compose up -d
# wait until http://localhost:8890 responds
cd ..\e2e
$env:BASE_URL = 'http://localhost:8890'   # not 127.0.0.1 (WP 301 loop risk)
npm run test:tc-matrix-headed
```

## This session

`curl http://localhost:8890` timed out (exit 28). Stack **DOWN / hung** — headed execution **BLOCKED**. Specs are in repo; re-run after compose is healthy.

Demo seed required for TC-05/07/12–14/18/25 personas.
