# Integration Documentation

## Integration Matrix

| Integration | Adapter/Layer | Inbound/Outbound Data | Failure Handling | Status |
|---|---|---|---|---|
| FluentCRM | `NGC_Fluentcrm_Adapter` | contact upsert, tags, lists | workflow failure + email + retry | BLOCKED (plugin runtime) |
| Amelia | `NGC_Amelia_Adapter`, `NGC_Amelia_Availability_Adapter` | employee sync, appointment busy slots | fallback to internal booking adapter | PARTIAL |
| MasterStudy | `NGC_Masterstudy_Adapter` | instructor/student role mapping | workflow failure + retry | BLOCKED (plugin runtime) |
| WooCommerce | hooks in payment/invoice classes | payment complete/failed/refund | notices + partial finance mode | PARTIAL |
| Internal booking fallback | `NGC_Internal_Booking_Adapter` | booking occupancy slots | source metadata fallback | VERIFIED |

## Integration Verification

- Workflows verification dashboard
- Platform health and route checks
- Audit and workflow run telemetry

