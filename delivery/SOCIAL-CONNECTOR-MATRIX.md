# SOCIAL-CONNECTOR-MATRIX

| Platform | Method | Password fields | Status | Notes |
| --- | --- | --- | --- | --- |
| Facebook Pages | OAuth dialog + vault token refs | **Forbidden** | PARTIAL | Needs `NGC_META_APP_ID/SECRET` |
| Instagram professional | Meta OAuth scopes | **Forbidden** | PARTIAL | Requires professional/Page config |
| X | OAuth 2 + PKCE | **Forbidden** | PARTIAL | Needs `NGC_X_CLIENT_ID/SECRET` |
| LinkedIn | OAuth 2 | **Forbidden** | PARTIAL | Product/scopes may block messaging |

## Contract surface

`NGC_Social_Connections` + `NGC_Social_Oauth` (PKCE state in transients).  
Publish path: `NGC_Content_Studio::publish_approved` — **sandbox** without live tokens.

## Prohibited (enforced in code)

- Username/password collection for social platforms.
- Browser-login / headless harvest automation for social sites.

**Social connectors verified (live OAuth round-trip): 0/4 — INPUTS REQUIRED.**
