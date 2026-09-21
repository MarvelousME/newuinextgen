NextGen Subjects Widget
=======================

Version: 1.1.0

Install
-------
1. Copy or mount `nextgen-subjects-widget/` into `wp-content/plugins/`.
2. Activate **NextGen Subjects Widget** in Plugins.
3. Use Appearance > Widgets > NextGen Subjects, or the shortcode.

Data sources (auto, in order)
-----------------------------
1. `subject` taxonomy (Companion)
2. `NGC_Subjects_CMS` option catalog
3. Theme `bi_get_subject_tracks()`
4. Built-in defaults

Manual lines in the widget/shortcode override live data when provided.

Shortcode
---------
[nextgen_subjects]

[nextgen_subjects source="auto"]

[nextgen_subjects source="manual" subjects="Mathematics|CAPS maths|calculator,English|Language skills|book"]

Attributes: title, subtitle, subjects, columns (2-6), show_search (yes|no), source (auto|manual)

Cards link to Find a Tutor with ?subject=slug (or taxonomy term archives when available).
