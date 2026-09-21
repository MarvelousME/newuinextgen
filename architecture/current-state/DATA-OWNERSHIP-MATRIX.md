# DATA-OWNERSHIP-MATRIX

| Entity / store | Owner | Consumers | Access mode |
|----------------|-------|-----------|-------------|
| `ngc_*` tables | companion | theme (read via API/shortcode only) | owner write; others via command/API |
| `wp_posts` tutor CPT | companion | theme | owner write |
| Theme templates / theme_mods | beyondinfinity | — | owner |
| Imported page content | html-importer (write once) then WP | — | importer writes posts only |
| Plugin Manager options `ngcpm_*` | plugin-manager | — | owner |
| AI transport logs | ai-integration | ops | owner |
| Secret vault (`ngc_vault_*` / `env:`) | companion | agentic/MCP/memory (server-side reveal only) | owner; never browser |
| Agent Gateway task SQLite | ngt-agent-gateway | Companion HMAC client | owner |
| WP users | wordpress/identity | companion | shared platform |

**Rule:** A subsystem MUST NOT directly modify data owned by another subsystem.

**Last Updated:** 2026-09-16
