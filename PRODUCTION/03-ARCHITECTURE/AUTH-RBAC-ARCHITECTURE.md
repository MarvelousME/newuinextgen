# Auth and RBAC

Companion `NGC_Roles` installs `parent`, `parent_guardian`, `tutor`, `student` with `ngc_*` capabilities. Theme `inc/roles.php` only adds `read` if the role is missing.

Dashboard REST requires login. Admin routes require `manage_options` or Companion admin caps. Theme `inc/security.php` redirects role dashboards.