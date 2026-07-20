# WPBakery guide (NGT UI)

See `documentation/gutenberg-guide.md` for shared architecture.

**Element base:** `ngt_ui_vc` · **Renderer:** `NGT_UI_Renderer` only.

## Status

| Check | Status |
|-------|--------|
| Adapter code (`NGT_UI_WPBakery`) | VERIFIED |
| Shortcode `[ngt_ui_vc]` without js_composer | VERIFIED — `ui-library/tests/integration-smoke.php` + CI WP-CLI smoke |
| `vc_map` backend element | PARTIAL — requires licensed `js_composer` zip |
| Docker optional profile | `WPBAKERY_ZIP_PATH` + `docker/init/install-wpbakery.sh` |

## Shortcode (works without WPBakery plugin)

```text
[ngt_ui_vc component="magic-card" title="Headline"]Inner content[/ngt_ui_vc]
```

CI asserts `VC_OK` via WP-CLI `do_shortcode()` after Docker bootstrap.

## Optional Docker install

1. Place licensed `js_composer.zip` on the host.
2. In `docker/.env`:

```env
WPBAKERY_ZIP_PATH=/path/to/js_composer.zip
```

3. Re-run setup:

```powershell
cd docker
docker compose --profile setup run --rm wpcli
```

4. In WP admin, add **NGT UI Component** in backend editor and confirm front-end HTML matches `[ngt_ui component="…"]` markers.

## Related

- [ADR-002 dual UI coexistence](./adr/ADR-002-dual-ui-coexistence.md)
