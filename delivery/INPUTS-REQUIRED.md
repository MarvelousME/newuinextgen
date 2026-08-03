# INPUTS-REQUIRED.md

External credentials and approvals required before production social/MCP/A2A use:

| ID | Input | Where |
| --- | --- | --- |
| IN-AG-001 | Meta App ID + Secret | `NGC_META_APP_ID`, `NGC_META_APP_SECRET` in wp-config (not committed) |
| IN-AG-002 | X Client ID + Secret | `NGC_X_CLIENT_ID`, `NGC_X_CLIENT_SECRET` |
| IN-AG-003 | LinkedIn Client ID + Secret + product access | `NGC_LINKEDIN_CLIENT_ID`, `NGC_LINKEDIN_CLIENT_SECRET` |
| IN-AG-004 | Approved first-party MCP endpoint + allowlist | Admin MCP registry |
| IN-AG-005 | Agent Gateway base URL + signing keys | Separate service hosting `a2a-js` |
| IN-AG-006 | FluentCRM active with REST/Contact API | Plugin install |
| IN-AG-007 | Lawful basis / POPIA notice for outreach | Legal review |

Never paste access tokens, refresh tokens, or passwords into source, screenshots, or agent memory.
