# Companion Domain Codemap

**Last Updated:** 2026-09-16  
**Entry Points:**
- `NextGenTutors-Companion/nextgencompanion.php`
- `NextGenTutors-Companion/includes/class-ngc-plugin.php`
- `NextGenTutors-Companion/includes/rest/` (namespace `ngc/v1`, legacy mirror `ngt/v1`)

## Architecture

```
Theme / admin / REST client
        │
        ▼
 NGC_Plugin + NGC_Module_Registry
        │
        ├─ Domain services (Matching, Bookings, Payments, Marketplace, …)
        │         │
        │         └─ Policy Bridge (privileged mutate)
        ├─ REST controllers (includes/rest/class-ngc-rest-*.php)
        ├─ Shortcodes (includes/shortcodes/)
        ├─ Adapters (includes/adapters/, includes/integrations/)
        └─ Persistence (class-ngc-database.php → wp_ngc_* tables + CPTs)
```

## Key Modules

| Module | Purpose | Exports / entry | Dependencies |
|--------|---------|-----------------|--------------|
| `NGC_Matching` | Propose / score / assign | `create_from_find_tutor`, auto-accept | Policy Bridge `matching.propose` |
| `NGC_Bookings` | Session lifecycle | `create`, query helpers | Policy Bridge `booking.create`, Amelia adapter |
| `NGC_Payments` | Settle / payouts | `settle_order` | Policy Bridge `payment.authorize`, WC / PayFast |
| `NGC_PayFast_Gateway` | ITN settlement | WC gateway + ITN | `trusted_system` settle path |
| `NGC_Section_CMS` | Homepage sections | `ngc_page_sections` | Theme kinetic home |
| `NGC_UI_*_Data_Provider` | UI Library data | list/map_to_component | Theme partials only |
| `NGC_Workflow_Orchestrator` | Event workflows | integrate pack | Studio / hooks |
| `NGC_AI_*` / `BIA_*` | BYOK models + agents | AI Suite admin + REST | Vault for keys |
| `NGC_Database` | Schema / migrations | `wp_ngc_*` | WordPress `$wpdb` |

## REST surface (groups)

Namespace: **`ngc/v1`** (legacy alias **`ngt/v1`** — do not activate old Core plugins).

| Group | Controller area | Notes |
|-------|-----------------|-------|
| Dashboards | `class-ngc-rest-dashboard.php` | Role KPIs |
| Bookings | `class-ngc-rest-bookings.php` | CRUD / status |
| AI suite | `class-ngc-rest-ai.php` | models, agents, chat (admin) |
| Talent | `class-ngc-rest-talent.php` | evaluate / rank |
| Memory | `class-ngc-rest-memory.php` | agent memory bridge |
| Builder / Studio | `class-ngc-rest-builder.php` | visual builder APIs |
| System log | `class-ngc-rest-system-log.php` | ops |

Public calendar (separate): `nextgen/v1/tutors/{id}/calendar`.

## Data ownership

| Store | Owner | Theme access |
|-------|-------|--------------|
| `wp_ngc_*` | Companion | shortcode / REST / UI providers only |
| `tutors` CPT + meta | Companion | read via providers / carousel helpers |
| Theme templates / theme_mods | BeyondInfinity | — |
| Secret vault | Companion | never browser |

See [DATA-OWNERSHIP-MATRIX.md](../../architecture/current-state/DATA-OWNERSHIP-MATRIX.md).

## Data Flow (example: find tutor → book → pay)

1. Theme form / shortcode → Companion handler  
2. `NGC_Matching::create_from_find_tutor` → `authorize_domain('matching.propose')`  
3. `NGC_Bookings::create` → `authorize_domain('booking.create')`  
4. WooCommerce checkout → PayFast ITN / WC hook → `NGC_Payments::settle_order(..., { trusted_system: true })`  
5. Theme dashboards read via shortcodes / REST (no direct table writes)

## Related Areas

- [platform.md](platform.md) — Policy Bridge / modules  
- [agentic.md](agentic.md) — agents over domain tools  
- [theme.md](theme.md) — presentation contracts  
