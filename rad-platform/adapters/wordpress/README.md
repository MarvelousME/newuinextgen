# WordPress adapter binder

This folder documents how the portable RAD kit binds to a WordPress modular monolith.

## Contracts

| Concern | Kit artifact | WP implementation |
|---------|--------------|-------------------|
| Subsystem registration | `architecture/manifests/*.json` | `NGC_Subsystem_Registry` |
| Capabilities | `architecture/capabilities/*.json` | `NGC_Capability_Registry` |
| Policy | `architecture/policies/` + schema | `NGC_Policy_Bridge` → `NGC_Agent_Policy_Engine` / `NGC_Authz_Matrix` |
| Admin visibility | Control plane | Platform Kernel admin section |

## Path resolution

Companion resolves the monorepo `architecture/` directory relative to the plugin (three levels up from `includes/platform` when developed in-repo). Override with filter `ngc_rad_architecture_root`.

## Rules

- Do not duplicate policy decisions outside `NGC_Agent_Policy_Engine` / `NGC_Authz_Matrix`.
- Theme must consume Companion via shortcodes / `ngc/v1` REST only.
- Invalid manifests must not register (fail closed for new registrations).
