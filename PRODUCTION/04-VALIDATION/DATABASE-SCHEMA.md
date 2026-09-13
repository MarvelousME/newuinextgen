# Database schema (Companion)

Created by `NGC_Database::table_names()` / `create_tables()` via dbDelta. Prefix is `{wpdb->prefix}ngc_`.

Matches, bookings, sessions, invoices, wallet_ledger, payouts, reviews, audit_log, tutor_applications, session_logs, earnings, ratings, workflow_runs, analytics_events, visitor_profiles, user_profiles, acquisition_sources, affiliate_clicks, attribution_links, user_sessions, device_profiles, conversion_events, metric_snapshots, demo_seed_log, consent_log, gamification_*, leaderboard_entries, export_*, repair_snapshots, ai_diagnostics_log, referrals, reminder_schedules, studio_*, child_learners, page_sections, builder_*, system_log, intel_*, memory_identity_map, talent_*.

Additional: platform kernel (`NGC_Platform_Schema`), safeguarding/fraud/agent control-plane tables, 3D plugin `ngt_3d_scroll_rules`.

Theme creates **no** custom tables. Migrations are idempotent dbDelta + `ngc_db_version`.