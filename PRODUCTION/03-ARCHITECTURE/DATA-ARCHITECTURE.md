# Data architecture

Canonical prefix: `wp_ngc_*` (see DATABASE-SCHEMA.md). WordPress posts hold CPT `tutors` and taxonomies `subject`, `province`, `grade`, `learning_format`.

Theme demo arrays in `inc/tutor-data.php` are presentation fallbacks; live roster is Companion CPT + `NGC_Tutor_Cpt_Source`.

Do not treat prototype comments about `ngt_earnings` as schema.