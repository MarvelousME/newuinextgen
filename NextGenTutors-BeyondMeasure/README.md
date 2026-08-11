# NextGenTutors Beyond Measure

Control Plane admin OS for NextGen Tutors.

**Principle:** The Control Plane owns administration. Subsystems own business logic.

## What it is

- Separate WordPress plugin (`NextGenTutors-BeyondMeasure`)
- React/TypeScript admin SPA mounted in `wp-admin` (`#ngtbm-root`)
- PHP remains authority for auth, RBAC, REST (`nextgentutors-control/v1`), orchestration, persistence
- Metadata-driven Admin UI Runtime — subsystems register via:

```php
do_action( 'ngt_control_plane/register_subsystem', $definition );
```

## Coexistence

- **Mission Control** — unchanged (strangler)
- **Companion Kernel / Talent / Memory PHP screens** — remain; Beyond Measure is preferred entry and deep-links to legacy pages

## Develop

```bash
cd NextGenTutors-BeyondMeasure
composer dump-autoload   # optional
npm install
npm run build
php tests/run-unit.php
```

Without a webpack build, `build/fallback.js` loads a wp.element SPA.

## Activate

Enable the plugin in WordPress (Docker mounts it automatically). Open **NextGen → Beyond Measure**.
