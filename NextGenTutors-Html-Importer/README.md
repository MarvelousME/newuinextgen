# REVAMP HTML Content Importer

WordPress plugin that imports static HTML page content from `webpages-content` into WordPress pages with mapping, dry-run preview, media sideloading, theme class adoption, rollback, and QA reporting.

## Installation

1. Copy the `revamp-html-importer` folder to `wp-content/plugins/`.
2. Activate **REVAMP HTML Content Importer** in **Plugins**.
3. Ensure **BeyondInfinity** theme and **NextGen Companion** are active for shortcode regions and layout classes.
4. Go to **Tools → REVAMP HTML Importer**.

## Usage

1. Set the **HTML directory path** (default: `C:\Users\marvi\Music\REVAMP\webpages-content` on your dev machine; on the server use the uploaded copy path).
2. Leave **Dry run** checked and click **Scan Directory**.
3. Review the mapping table (confidence, action, notes).
4. Run **Import All Confident Matches** or select rows and **Import Selected**.
5. Uncheck **Dry run** only when ready to write pages (status: **draft** by default).
6. Check **Publish pages** only when QA is complete.
7. Use **Rollback All Backups** to restore previous `post_content` from meta.

## Page Mapping Table (Source HTML → WordPress)

| HTML File | Detected Title (typical) | WP Slug | Confidence | Action | Notes |
|-----------|--------------------------|---------|------------|--------|-------|
| `index.html` | NextGen Tutors — … | `home` | 95% | UPDATE | Sets front page if published |
| `about.html` | About Us — … | `about` | 95% | UPDATE | |
| `become-a-tutor.html` | Become a Tutor — … | `become-a-tutor` | 95% | UPDATE | Form → `[ngc_become_tutor_form]` |
| `find-a-tutor.html` | Find a Tutor — … | `find-a-tutor` | 95% | UPDATE | Directory grid is dynamic — review |
| `contact.html` | Contact — … | `contact` | 95% | UPDATE | Form → `[ngc_contact_support_form]` |
| `pricing.html` | Pricing — … | `pricing` | 95% | UPDATE | |
| `blog.html` | Blog — … | `blog` | 95% | UPDATE | |
| `guarantee.html` | Guarantee — … | `guarantee` | 95% | UPDATE | |
| `safety-guide.html` | Safety Guide — … | `safety-guide` | 95% | UPDATE | |
| `tutor-vetting.html` | Tutor Vetting — … | `tutor-vetting` | 95% | UPDATE | |
| `privacy.html` | Privacy — … | `privacy-policy` | 85% | UPDATE | Slug differs from filename |
| `terms.html` | Terms — … | `terms` | 95% | UPDATE | |
| `onboarding.html` | Onboarding — … | `onboarding` | 95% | UPDATE | |
| `wordpress-setup.html` | WordPress Setup — … | `wordpress-setup` | 95% | UPDATE | |
| `tutor-dashboard.html` | Tutor Dashboard — … | `tutor-dashboard` | 95% | UPDATE | Dashboard shell — may need shortcode |
| `dashboard.html` | My Dashboard — … | `student-dashboard` | 75% | REVIEW_REQUIRED | Ambiguous vs parent-dashboard |
| `tutor-profile.html` | Tutor Profile — … | — | 30% | REVIEW_REQUIRED | No page-map entry |

## Post Meta (Rollback & Tracking)

| Meta Key | Purpose |
|----------|---------|
| `_revamp_source_html_file` | Relative source path |
| `_revamp_source_hash` | SHA-256 of source file (skip if unchanged) |
| `_revamp_last_imported_at` | ISO timestamp |
| `_revamp_mapping_confidence` | Matcher score |
| `_revamp_previous_post_content` | Rollback content |
| `_revamp_previous_post_modified` | Rollback modified time |

## QA Checklist

- [ ] Scan completes with 17 HTML files
- [ ] Every file has an action (CREATE / UPDATE / SKIP / REVIEW_REQUIRED)
- [ ] Dry-run report shows expected create/update counts
- [ ] No raw `C:\` paths in imported content
- [ ] Images sideloaded or remote URLs preserved
- [ ] Internal `.html` links rewritten to permalinks
- [ ] Shortcode regions preserved on contact / find-a-tutor / become-a-tutor
- [ ] Page builder pages skipped unless **Force update** checked
- [ ] Draft pages reviewed in frontend before publish
- [ ] Mobile layout checked on about, contact, pricing
- [ ] Rollback restores prior content

## Rollback Instructions

1. **Single page:** Restore `_revamp_previous_post_content` via **Rollback All** (restores all backed-up pages) or manually in post meta.
2. **Bulk:** Click **Rollback All Backups** on the admin screen.
3. Rollback does **not** delete uploaded media.

## Architecture

```
revamp-html-importer/
├── revamp-html-importer.php      # Bootstrap
├── admin/class-rhi-admin.php     # Admin UI + AJAX
├── includes/
│   ├── class-rhi-scanner.php     # Directory scan
│   ├── class-rhi-html-parser.php # DOM extraction
│   ├── class-rhi-page-matcher.php# WP page matching
│   ├── class-rhi-css-adoption.php# Theme class mapping
│   ├── class-rhi-sanitizer.php   # wp_kses sanitization
│   ├── class-rhi-media-importer.php
│   ├── class-rhi-importer.php    # Import orchestrator
│   ├── class-rhi-rollback.php
│   └── class-rhi-logger.php
└── assets/                       # Admin + scoped content CSS
```

## VERIFIED LIMITATION

- **Dynamic JS pages:** `find-a-tutor.html` tutor directory grid and `index.html` hero canvas are populated by JavaScript; only static HTML sections import. Dynamic regions are replaced with review notes or shortcodes.
- **Server path:** The HTML directory must exist on the **WordPress server** filesystem. Local Windows paths are not readable from a remote host unless synced.
- **Page builder:** Elementor/WPBakery pages are skipped without **Force update** to avoid destroying builder metadata.
- **tutor-profile.html:** No matching page in `page-map.json` — manual mapping required.
