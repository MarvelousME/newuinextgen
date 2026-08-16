# NGT Provider Responsibility Matrix

**Date:** 2026-08-16

| Provider | Allowed role | Forbidden role | Verification level today |
|---|---|---|---|
| FluentCRM | CRM, segmentation, nurture, marketing automation | Booking, payment, verification, safety adjudication | CONTRACT / PARTIAL runtime |
| AutomatorWP | Optional compatibility adapter for non-core | Core booking/payment/safety/payout | Deprecated recipes; **DISABLE** after migrate |
| GamiPress | Points, badges, gamification projection | Rating SoR, verification, marketplace visibility | Adapter present; FAKE/CONTRACT |
| Amelia | Availability, appointment/calendar, optional retained notices | Payment, earnings, CRM, safety | Adapter + sync; SANDBOX/PARTIAL |
| WooCommerce | Order authority | Journey orchestration | VERIFIED hooks |
| PayFast | Payment capture gateway | Card data storage in NGT | SANDBOX verified in tests; REAL = UNVERIFIED unless exercised |
| MasterStudy | Course, enrollment, progress, onboarding learning | Independent learner/tutor identity | Adapter PARTIAL |
| Fluent Support | Support/safety case **transport** | Incident semantics, severity, restriction policy | Adapter loaded; runtime UNVERIFIED |
| Jitsi | Meeting room / join URL | Attendance business rules alone | Adapter class VERIFIED; observer role UNVERIFIED |
| Fluent Forms | Presentation/input | Application SoR | Optional stack |
| Notifier / SMS | Transport | Booking state | UNVERIFIED provider |
| Bank/EFT | Payout execution | — | **ABSENT** / UNVERIFIED |
| Background screening vendor | Evidence feed | Auto-verify without policy | UNVERIFIED |
| AI models | Flag/score/summarize | Auto-punish / close case | Signal-only path PARTIAL |

## Target ports (to introduce — thin wrappers)

| Port | Default adapter |
|---|---|
| `BookingProviderInterface` | `AmeliaBookingAdapter` |
| `CommerceProviderInterface` | `WooCommerceAdapter` |
| `PaymentGatewayInterface` | `PayFast` (via Woo) |
| `GamificationProviderInterface` | `GamiPressAdapter` |
| `CrmProjectionInterface` | `FluentCrmAdapter` |
| `LearningProviderInterface` | `MasterStudyAdapter` |
| `SupportCaseProviderInterface` | `FluentSupportAdapter` |
| `MeetingProviderInterface` | `JitsiMeetingAdapter` |
| `PayoutProviderInterface` | Manual/ops until bank adapter |
| `DocumentProviderInterface` | Fluent PDF or internal |

Do not create a second orchestration engine inside any adapter.
