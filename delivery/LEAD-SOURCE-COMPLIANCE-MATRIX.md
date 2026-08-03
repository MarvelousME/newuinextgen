# LEAD-SOURCE-COMPLIANCE-MATRIX

| Source slug | Allowed | Method | Notes |
| --- | --- | --- | --- |
| first_party_referral | Yes | api | Inbound |
| job_board_api | Yes | api | Partner API required |
| public_directory_api | Yes | api | ToS permitting only |
| google_job_posting | Yes | structured_data | Attract applicants — not scrape |
| linkedin_official_api | Yes | api | Granted products/scopes only |
| consented_import | Yes | import | Documented lawful basis |
| manual_entry | Yes | manual | Operator + consent |
| linkedin_scrape | **No** | scrape | PROHIBITED |
| google_serp_scrape | **No** | scrape | PROHIBITED |
| bing_search_api | **No** | retired | Retired 2025-08-11 |
| bing_serp_scrape | **No** | scrape | PROHIBITED |
| maps_people_harvest | **No** | scrape | Places ≠ people DB |
| browser_login_harvest | **No** | automation | PROHIBITED |
| social_profile_scrape | **No** | scrape | PROHIBITED |

## Protected-trait exclusion

Implemented in `NGC_Lead_Criteria` — ethnicity, gender, age, etc. rejected.  
**Protected-trait exclusion tests: PASS** (`tests/agentic-governance.php`, 12/12).

## FluentCRM

List title `Tutor Leads` (slug `tutor-leads`) + pipeline tags via `NGC_Fluentcrm_Adapter::upsert_tutor_lead`.  
**FluentCRM synchronization: UNVERIFIED in live runtime** (adapter code present; requires FluentCRM active + email on lead).
