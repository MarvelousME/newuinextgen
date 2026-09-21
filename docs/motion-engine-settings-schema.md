# Motion Engine Settings Schema

**schemaVersion:** 2 (additive; v1 rules remain valid)

## Rule record (DB / export)

Legacy fields unchanged. New optional fields live inside `animation_options` JSON:

```json
{
  "schemaVersion": 2,
  "duration": 0.85,
  "ease": "power3.out",
  "effects": [
    {
      "preset": "gsapify-fade-up-on-scroll",
      "trigger": { "type": "viewport", "start": "top 80%" },
      "settings": { "duration": 0.9, "stagger": 0.04 }
    },
    {
      "preset": "magnetic-button",
      "trigger": { "type": "hover" },
      "settings": { "strength": 0.35, "radius": 120 }
    }
  ]
}
```

### Compatibility

| Mode | Condition | Runtime behavior |
|------|-----------|------------------|
| Legacy | `effects` absent | Use `animation_names` + shared options |
| Stack | `effects[]` present | Apply each preset with its own settings; ignore shared-only collisions via TransformComposer |

## Global settings (`ngt_3d_settings`)

Existing keys retained. Planned additive keys (safe defaults):

| Key | Type | Default | Notes |
|-----|------|---------|-------|
| `motion_intensity` | int 0–100 | 50 | Scales distances/stagger subtly |
| `reduced_motion_policy` | string | `respect` | respect \| force-off \| force-on (dev only) |
| `mobile_motion_level` | string | `reduced` | full \| reduced \| disabled |
| `global_performance_mode` | string | `balanced` | balanced \| performance \| cinematic |

## Source tags

`GSAPIFY-VERIFIED` | `GSAP-OFFICIAL` | `SYSTEM-ENHANCEMENT` | `CUSTOM` | `UNVERIFIED`
