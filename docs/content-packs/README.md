# Content Packs

Operational extensions shipped as WordPress plugins in `content/` zips. They complement the four core packages and integrate with Companion via `NGC_Content_Pack_Bridge`.

| Pack | Doc |
|------|-----|
| Command Center | [COMMAND-CENTER.md](COMMAND-CENTER.md) |
| Completion Suite | [COMPLETION-SUITE.md](COMPLETION-SUITE.md) |

## Deployment

Extract zips to `wp-content/plugins/` or use Docker bind mounts from `content/_extracted/`.

## Integration with Companion

| Bridge feature | Class |
|----------------|-------|
| RTM mirror (theme → Command Center) | `NGC_Content_Pack_Bridge::mirror_rtm_to_command_center()` |
| Completion CPT → workflow dispatch | `NGC_Content_Pack_Bridge::on_operational_post()` |
| Catalog spec import | `NGC_Workflow_Spec_Registry::import_from_catalog()` |
| AutomatorWP seed | `NGC_AutomatorWP_Importer::import_from_v2_catalog()` |

Admin: **Companion → Workflows → Integrate Specs**
