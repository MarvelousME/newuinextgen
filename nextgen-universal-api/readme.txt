=== NextGen Universal API ===
Contributors: nextgentutors
Tags: rest-api, crud, plugin-scanner, developer-tools
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scans every active plugin, maps native REST APIs that are actually live, and generates secure CRUD REST endpoints for any plugin database tables with no exposed API.

== Description ==

NextGen Universal API gives you one place to see, and use, every API surface on your WordPress site:

* Scans all active plugins on demand
* Verifies which plugins expose a live REST namespace — checked against the real REST server, never guessed
* Discovers the database tables each plugin created
* Lets you selectively enable secure, schema-validated CRUD endpoints for tables with no native API
* Ships with an API key manager (read / write scopes, SHA-256 hashed storage, one-time reveal)
* Built-in test console — try every endpoint without leaving wp-admin
* Full audit log of every write operation

= Security model =

* Core WordPress tables (users, usermeta, options, etc.) can never be exposed, even if you try
* Every table must be explicitly enabled by an administrator before its endpoint exists
* Write access is a separate, explicit toggle from read access
* Every column and table name used in a SQL query is verified against a live DESCRIBE of the table — nothing is built from raw request input
* All queries use $wpdb->prepare() with typed placeholders
* Per-key rate limiting
* API keys are stored as SHA-256 hashes, never in plaintext; the raw key is shown once, at generation

== Installation ==

1. Plugins → Add New → Upload Plugin → choose the ZIP
2. Activate
3. Open "Universal API" in the admin sidebar
4. Click "Rescan Now" to build the plugin/table registry
5. Enable read/write access per table under "Tables & Permissions"
6. Generate an API key under "API Keys"
7. Try your endpoints under "Test Console"

== Frequently Asked Questions ==

= Will this expose my WordPress users table? =
No. wp_users, wp_usermeta, wp_options and other core tables are hard-blocked in code and can never be enabled, regardless of settings.

= What happens to plugins that already have a REST API? =
They are marked "Native API" and no generated CRUD is created for them — you use their own documented endpoints instead. Generated CRUD is offered only for tables belonging to plugins with no live REST coverage.

== Changelog ==

= 1.0.0 =
* Initial release
