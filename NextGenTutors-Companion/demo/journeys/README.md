# Bundled Phase 14 journey catalogue

These JSON definitions are copied from the monorepo authoring tree:

`.agent-audit/demo/journeys/`

They are bundled inside Companion so Docker / plugin installs can resolve the
catalogue without mounting the monorepo `.agent-audit` directory.

`NGC_Demo_Journeys::catalogue_dir()` prefers this folder, then falls back to
`.agent-audit/demo/journeys` when present.

When authoring new journeys, update `.agent-audit/demo/journeys/` and re-copy
into this directory before release packaging.
