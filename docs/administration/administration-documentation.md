# Administration Documentation

## Admin Screen Catalog

| Screen | Purpose | Access Role | Key Actions | Verification/Recovery | Status |
|---|---|---|---|---|---|
| NextGen Operations | operations overview | admin | monitor checks, tutor ops links | repair trigger | VERIFIED |
| Applications | tutor vetting queue | reviewer/admin | approve/reject | state transitions in logs | VERIFIED |
| Matches | match monitoring | support/admin | inspect/manual assign | match status checks | VERIFIED |
| Payouts | payout processing | finance/admin | process payouts | payout+earnings reconciliation | VERIFIED |
| Health | health + audit | admin | run self-healing | verification report | VERIFIED |
| Workflows Trigger Manager | workflow inventory | admin | inspect runs | logs + stats | VERIFIED |
| FluentCRM Status | CRM integration verify | admin | bootstrap lists/tags | verify status output | PARTIAL |
| Amelia Status | calendar integration verify | admin | set API key/service id | fallback when unavailable | PARTIAL |
| MasterStudy Status | LMS integration verify | admin | check mapping readiness | adapter verify | BLOCKED |
| Email Templates | template management | admin | test send | send/fail signal | VERIFIED |
| Workflow Logs | run history | admin | inspect latest runs | diagnose failures | VERIFIED |
| Retry Queue | failed-step recovery | admin | retry workflow steps | queue drains | VERIFIED |
| Verification Dashboard | integration health report | admin | run checks | issue detection | VERIFIED |
| Platform: Data Source Verification | strict source mapping | admin | inspect source matrix | detect static/fallback misuse | VERIFIED |
| Platform: Demo Journey Manager | demo toggle/seed/clear | admin | seed/clear demo users | demo isolation checks | VERIFIED |
| Platform: Analytics Dashboard | KPI monitoring | admin | inspect metrics | matrix alignment | VERIFIED |
| Platform: User Profiling | profile intelligence | admin | inspect user timelines | profile completeness checks | VERIFIED |
| Platform: Acquisition | attribution monitoring | admin | inspect acquisition rows | tracking checks | VERIFIED |
| Platform: Affiliate Tracking | affiliate performance | admin | inspect affiliate rows | attribution checks | VERIFIED |
| Platform: Cookie Tracking Settings | consent/tracking settings | admin | enable/disable/reset cookies | privacy validation | VERIFIED |
| Platform: Privacy/Consent Settings | consent logs and policy support | admin | inspect consent state | compliance checks | VERIFIED |
| Platform: Data Health Checks | comprehensive checks | admin | review check report | identify breakage | VERIFIED |
| Platform: Self-Healing Repair Tools | automated repair | admin | run full repair | post-repair verification | VERIFIED |

## Admin Recovery Procedure

1. Open data health + verification screens.
2. Confirm failed checks and impacted modules.
3. Run repair tools where safe.
4. Re-verify and inspect audit/workflow logs.
5. Escalate external plugin/runtime blockers.

