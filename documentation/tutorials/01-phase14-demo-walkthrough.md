# Tutorial: Phase 14 Relational Demo Walkthrough

> **What you'll learn**
> - Enable demo mode safely
> - Seed relational demo data through domain services
> - Log in as real personas (not URL role fakes)
> - Verify matches, bookings, fraud/safeguarding, and notifications
> - Export evidence and reset
>
> **Prerequisites:** Docker WordPress stack (or local WP with Companion active); `manage_options` admin account  
> **Time:** 25–40 minutes | **Level:** Intermediate  
> **Automated E2E (headed):** from `e2e/`: `npm run test:phase14`

---

## Automated headed demonstration

```powershell
cd e2e
npm install
npx playwright install chrome
# Site must be up at http://localhost:8900
npm run test:phase14
```

This runs `workflows/phase14-demo-walkthrough.spec.ts` headed (slow-mo) covering enable → seed → verify → parent/tutor login → fraud/safeguarding → journeys/evidence → reset/reseed.

Override admin credentials if needed:

```powershell
$env:WP_ADMIN_USER='admin'
$env:WP_ADMIN_PASSWORD='NextGenAdmin!2026'
$env:BASE_URL='http://localhost:8900'
npm run test:phase14
```

---

## Setup (5 minutes)

1. Start the stack so `http://localhost:8900` responds.
2. Log into WP Admin.
3. Confirm Companion is active (Plugins).
4. Open a second browser profile or incognito for persona logins.

**Checkpoint:** You can open **Platform** in the admin menu.

---

## Section 1 — Enable demo mode

### Concept

Demo mode turns on sandbox email capture, PayFast sandbox forcing, and payout side-effect blocks. Without it, seed/reset/login-as are refused.

### Guided practice

1. Go to **Platform → Demo Control Centre**.
2. Click **Enable demo mode**.
3. Confirm the status card shows **Demo mode: ON**.

<details>
<summary>Solution / expected</summary>

Option `ngc_demo_mode_enabled` = `1`. Flags include `email_mode=sandbox`, `payment_mode=sandbox`, `external_side_effects=false`.

</details>

---

## Section 2 — Seed the relational graph

### Concept

Seeding calls the same services production uses (`NGC_Child_Learners`, `NGC_Matching`, `NGC_Bookings`, etc.). It does **not** invent dashboard JSON.

### Guided practice (UI)

1. On Demo Control Centre, click **Seed all**.
2. Wait for redirect flash: `Seed complete`.
3. Click **Verify** — aim for **PASS**.

### Guided practice (CLI)

```bash
docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_seed --allow-root

docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_verify --allow-root
```

**Checkpoint:** Seed graph preview shows `MATCH-001`, `BOOK-001`, `BOOK-COMPLETED`, fraud + safeguarding cases.

<details>
<summary>If Verify FAIL</summary>

- Re-run **Seed all** (idempotent).
- Confirm disk/DB healthy.
- Read failure list on the Control Centre page.
- Host unit tests: `php NextGenTutors-Companion/tests/run.php` (does not replace WP seed).

</details>

---

## Section 3 — Persona login (real auth)

### Concept

Each persona is a WordPress user with a real role. The Control Centre shows the shared demo password **only while demo mode is on**.

### Guided practice

1. Copy row **NGT-DEMO-P0001** (`demo.parent@nextgen.local`) email + password.
2. In incognito, open `/wp-login.php` and sign in.
3. Confirm you land on a parent-facing area (or home with parent capabilities).
4. Optionally use **Switch to user** from Control Centre (admin session).

**Try it yourself:** Log in as `NGT-DEMO-T0001` (approved tutor) and confirm bookings appear for that tutor.

<details>
<summary>Solution</summary>

Parent should see children Lerato + Kagiso (child learner records). Tutor should see Mathematics bookings from seed notes `BOOK-001` / `BOOK-COMPLETED`.

</details>

---

## Section 4 — Trace a booking journey

### Concept

BOOK-001 was created then transitioned to `confirmed`. BOOK-COMPLETED was transitioned to `completed`, which fires `lesson.completed` and enables a demo review.

### Guided practice

1. As admin, inspect seed graph JSON on Control Centre (bookings keys).
2. Open **Operations** surfaces (bookings/matches if available) or query via REST while logged in.
3. Confirm sandbox notifications include `booking-confirmed`, `session-completed`, `review-request`.

```bash
# Optional: advance time to exercise schedulers
wp ngc demo_advance_time --seconds=86400 --allow-root
wp ngc demo_process_queues --allow-root
```

**Checkpoint:** You can explain why BOOK-PENDING-PAY stays unconfirmed (payment-pending demo path).

---

## Section 5 — Ops cases & AI policy

### Guided practice

1. Open **Operations → Fraud cases** — demo payout-detail case present.
2. Open **Operations → Safeguarding** — high-priority demo case.
3. Note agent entries in seed graph (`AI-001`, `AI-008`, `AI-009`, kill-switch flags).

**Try it yourself:** As `demo.fraud@nextgen.local` / `demo.safeguarding@nextgen.local` (same demo password), confirm those admin menus are reachable for support-capable roles.

---

## Section 6 — Evidence pack

### Guided practice

1. Control Centre → **Run all journeys** then **Export evidence**.  
   Or: `wp ngc demo_export_evidence --allow-root`
2. Open `.agent-audit/evidence/demo/all-journeys/evidence.json`.

**Expected fields:** `seed_graph`, `notifications`, `verify`, `personas`, timestamps.

---

## Section 7 — Reset (safe)

> **Warning:** Deletes demo-tagged users/rows only. Requires demo mode + allow_reset.

```bash
wp ngc demo_reset --yes --allow-root
wp ngc demo_seed --allow-root
```

**Checkpoint:** Verify PASS again after reseed (idempotent loop).

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Seed blocked | Production env / demo off | Enable demo mode; set `NGC_ALLOW_DEMO_SEED` on local |
| Login fails | Wrong password / demo off | Re-enable demo; copy password from Control Centre |
| Booking conflict on seed | Slot collision | Seeder retries shifted times; re-run seed |
| No notifications | Seed incomplete | Re-seed; check `NGC_Demo_Notifications` option |
| Docker CLI hangs | Docker Desktop stalled | Use Control Centre UI instead |

---

## Summary

- Demo mode sandboxes side effects.
- Seed goes through domain services → real workflows/audit where wired.
- Personas use real WP auth.
- Verify + evidence prove claims; reset is reversible for demo data.

## Next steps

1. Read [platform-architecture.md](../platform-architecture.md)
2. Wire Prometheus to `/ngc/v1/metrics` — [ops-privacy-observability.md](../ops-privacy-observability.md)
3. Follow evaluator script: [../../.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md](../../.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md)
