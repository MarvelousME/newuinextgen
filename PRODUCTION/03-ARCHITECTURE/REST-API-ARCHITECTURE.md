# REST API architecture

Canonical: `ngc/v1` (`NGC_Rest::NAMESPACE`). Legacy alias: `ngt/v1` (same handlers). **No `ngtbi_*` routes exist.**

Theme `bi_rest_namespace()` returns `ngc/v1` when `NGC_Plugin` exists, else `ngt/v1` for old clients (those routes will 404 without Companion).

Other namespaces: `ngt3d/v1` (motion), `ngtai/v1` (optional AI plugin), `bi/v1` (OpenWA theme), `nextgentutors-control/v1` (optional BeyondMeasure).

See REST-ENDPOINTS.csv for the scanned `register_rest_route` inventory.