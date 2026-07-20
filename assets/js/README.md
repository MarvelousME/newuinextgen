# /assets/js — the compiled React app bundle goes here

The interactive dashboards, Simulation Hub, login/registration, GamiPress widget,
and the localStorage-backed "database" of the original app are a stateful React
SPA. To preserve that behavior exactly, the theme **re-mounts the compiled React
bundle** on app/dashboard routes (and optionally on Home).

After you build it (see README.md → "Re-mounting the React app"), two files land here:

- `app.bundle.js`  ← enqueued as an ES module by `inc/enqueue.php`
- `../css/app.bundle.css`  ← enqueued alongside it

`theme.js` (already present) handles the static marketing pages' interactions:
mobile menu, sticky header, hero carousel, scroll reveal, animated counters,
and 3D tilt — all vanilla JS, no dependencies.
