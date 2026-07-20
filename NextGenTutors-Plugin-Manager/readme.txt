=== NextGenTutors Plugin Manager ===
Contributors: nextgentutors
Tags: plugins, dependencies, installer, woocommerce, health
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Detect required core plugins, report dependency health, and securely install or activate WordPress.org plugins.

== Description ==

NextGenTutors Plugin Manager scans a configurable registry of required plugins (WooCommerce, Amelia, MasterStudy, FluentCRM, FluentSMTP, AutomatorWP, GamiPress, User Role Editor, PayFast, Elementor, and optional premium builders), calculates system readiness, and provides one-click install/activate workflows for verified sources.

Premium plugins are never faked as installable from WordPress.org. Manual install is required unless a local or whitelisted remote zip is configured.

== Installation ==

1. Upload the `NextGenTutors-Plugin-Manager` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Open **NextGenTutors Plugins** in the admin menu (`admin.php?page=ui-ux-pro-max`).
4. Place premium zips in `wp-content/ngcpm-packages/` (or `docker/ngcpm-packages/` when using Docker). Plugin Manager detects them automatically.

== Frequently Asked Questions ==

= Can this install Amelia or PayFast automatically? =

Only if you provide a valid zip in the configured local packages directory or a whitelisted remote URL in settings.

= Is the frontend shortcode public? =

The `[ngc_plugin_manager]` shortcode is read-only for users without `install_plugins`. Write actions require admin capabilities and nonces.

== Changelog ==

= 1.1.3 =
* Fix plugin installs: load Plugin_Upgrader dependencies, safer AJAX responses, WordPress.org API diagnostics.
* Fix Discovery view navigation and NGCPM config load order for modular JS.

= 1.1.2 =
* Split admin UI JavaScript into modular scripts (queue, repair, diagnostics, command palette, actions).

= 1.1.1 =
* Renamed package to NextGenTutors Plugin Manager; registry-driven dependency graph and pipeline.
* Sequential install queue, repair center, lazy diagnostics, rate limiting, 16 admin views.
* Shared view model for admin page and shortcode; expanded validate and WP smoke tests.

= 1.0.0 =
* Initial release: registry, scanner, installer, health dashboard, logging, settings, shortcode.
