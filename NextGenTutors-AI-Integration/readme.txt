=== NextGenTutors-AI-Integration ===
Contributors: beyondinfinity
Requires at least: 6.0
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed, signed, asynchronous bridge between NextGenTutors Companion and agents-api.

== Description ==

This plugin transports minimized Companion domain events to agents-api and receives signed, replay-protected results. It owns no tutoring-domain records and never writes Companion domain tables directly.

The agents service provides recommendations and integration outcomes, not domain authority. WordPress and Companion remain the source of truth. This plugin does not run an LLM and does not send synchronous AI requests from user-facing domain workflows.

== Installation ==

1. Install and activate NextGenTutors Companion.
2. Upload this plugin and activate it.
3. Open AI Integration Settings and configure the agents-api URL, key ID, and write-only secret.
4. Test connectivity and confirm the Health page reports ready.
5. Keep WP-Cron or a real cron runner enabled so queued events can be delivered.

If Companion is unavailable, the plugin enters a safe degraded mode: delivery pauses while health and configuration remain accessible.

== Frequently Asked Questions ==

= What happens when agents-api is offline? =

Events remain durable. Delivery uses bounded retries and exposes failed and dead-letter records to administrators. Offline service is never reported as success.

= Can AI approve tutors? =

No. Tutor approval is a Companion domain decision requiring the appropriate human capability and workflow.

= Can AI change lesson prices or create paid bookings? =

No. Agent results are validated and policy-gated. Pricing, payment, tutor approval, and booking authority stay within Companion and its governed domain services.

= Are callbacks protected? =

Yes. Machine callbacks require HMAC signatures, timestamp validation, nonce replay protection, and business idempotency.

== Changelog ==

= 1.1.0 =
* Added governed result handling, human approvals, health, audit, REST, admin, cron, and WP-CLI operations.
