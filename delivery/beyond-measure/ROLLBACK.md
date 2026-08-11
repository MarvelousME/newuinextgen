# Beyond Measure — Rollback Plan

1. `wp plugin deactivate NextGenTutors-BeyondMeasure`
2. Confirm Companion Talent/Memory/Kernel and Mission Control still reachable
3. Optional: drop `wp_ngtbm_*` tables only after confirming no required audit retention
4. Revert Docker volume mount if packaging without the plugin
