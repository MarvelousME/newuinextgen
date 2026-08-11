# DATA-OWNERSHIP-MATRIX

| Entity / store | Owner | Consumers | Access mode |
|----------------|-------|-----------|-------------|
| `ngc_*` tables | companion | theme (read via API/shortcode only) | owner write; others via command/API |
| `wp_posts` tutor CPT | companion | theme | owner write |
| Theme templates / theme_mods | beyondinfinity | — | owner |
| Imported page content | html-importer (write once) then WP | — | importer writes posts only |
| Plugin Manager options `ngcpm_*` | plugin-manager | — | owner |
| AI transport logs | ai-integration | ops | owner |
| WP users | wordpress/identity | companion | shared platform |

**Rule:** A subsystem MUST NOT directly modify data owned by another subsystem.
