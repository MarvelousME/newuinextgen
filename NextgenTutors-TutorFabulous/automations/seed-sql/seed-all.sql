-- =============================================================
-- NGT Seed SQL — Run in phpMyAdmin or via WP-CLI:
-- wp db query < seed-all.sql
-- NOTE: Adjust wp_ prefix if your install uses a different prefix.
-- =============================================================

-- -------------------------------------------------------------
-- FluentCRM: Lists
-- -------------------------------------------------------------
INSERT IGNORE INTO `wp_fc_lists` (`id`, `title`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'New Lead',         'new-lead',         NOW(), NOW()),
(2, 'Active Clients',  'active-clients',   NOW(), NOW()),
(3, 'Inactive',        'inactive',         NOW(), NOW()),
(4, 'Tutor Applicants','tutor-applicants', NOW(), NOW()),
(5, 'Approved Tutors', 'approved-tutors',  NOW(), NOW());

-- -------------------------------------------------------------
-- FluentCRM: Tags
-- -------------------------------------------------------------
INSERT IGNORE INTO `wp_fc_tags` (`id`, `title`, `slug`, `created_at`, `updated_at`) VALUES
(1,  'parent',          'parent',          NOW(), NOW()),
(2,  'tutor-applicant', 'tutor-applicant', NOW(), NOW()),
(3,  'approved-tutor',  'approved-tutor',  NOW(), NOW()),
(4,  'session-booked',  'session-booked',  NOW(), NOW()),
(5,  'paid-client',     'paid-client',     NOW(), NOW()),
(6,  'cancelled',       'cancelled',       NOW(), NOW()),
(7,  'matric-focus',    'matric-focus',    NOW(), NOW()),
(8,  'online-only',     'online-only',     NOW(), NOW()),
(9,  'in-person',       'in-person',       NOW(), NOW());

-- -------------------------------------------------------------
-- GamiPress: Points Type
-- -------------------------------------------------------------
INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('NGT Points', 'ngt-points', 'points-type', 'publish', 1, NOW(), NOW(), 'closed', 'closed', '', '', '', '', '', CONCAT('https://nextgentutors.co.za/?post_type=points-type&p=', LAST_INSERT_ID() + 1));

SET @gamipress_points_id = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@gamipress_points_id, '_gamipress_plural_name', 'NGT Points'),
(@gamipress_points_id, '_gamipress_slug', 'ngt-points');

-- GamiPress: Achievement Type — "Session Milestone"
INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('Session Milestone', 'session-milestone', 'achievement-type', 'publish', 1, NOW(), NOW(), 'closed', 'closed', '', '', '', '', '', '');

SET @achievement_type_id = LAST_INSERT_ID();

-- Achievement: First Session Complete
INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('First Session Complete', 'first-session-complete', 'session-milestone', 'publish', 1, NOW(), NOW(), 'closed', 'closed', 'Awarded after completing your first tutoring session with NextGen Tutors.', '', '', '', '', '');

SET @achievement_1_id = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@achievement_1_id, '_gamipress_points', '100'),
(@achievement_1_id, '_gamipress_points_type', 'ngt-points'),
(@achievement_1_id, '_gamipress_sequential', '0'),
(@achievement_1_id, '_gamipress_show_earners', '1');

-- Achievement: Committed Learner (10 sessions)
INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('Committed Learner', 'committed-learner', 'session-milestone', 'publish', 1, NOW(), NOW(), 'closed', 'closed', 'Awarded after completing 10 tutoring sessions — a true commitment to growth.', '', '', '', '', '');

SET @achievement_2_id = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@achievement_2_id, '_gamipress_points', '500'),
(@achievement_2_id, '_gamipress_points_type', 'ngt-points'),
(@achievement_2_id, '_gamipress_sequential', '0'),
(@achievement_2_id, '_gamipress_show_earners', '1');

-- Achievement: Matric Champion
INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('Matric Champion', 'matric-champion', 'session-milestone', 'publish', 1, NOW(), NOW(), 'closed', 'closed', 'Awarded for completing an Exam Prep intensive package ahead of Matric finals.', '', '', '', '', '');

SET @achievement_3_id = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@achievement_3_id, '_gamipress_points', '1000'),
(@achievement_3_id, '_gamipress_points_type', 'ngt-points'),
(@achievement_3_id, '_gamipress_sequential', '0'),
(@achievement_3_id, '_gamipress_show_earners', '1');

-- -------------------------------------------------------------
-- WooCommerce: Pricing Products
-- -------------------------------------------------------------
INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('Online Tutoring — Short Term (1–3 months)', 'online-tutoring-short-term', 'product', 'publish', 1, NOW(), NOW(), 'open', 'closed', 'Live video tutoring session. Billed per hour.', '', '', '', '', '');

SET @p_online_short = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@p_online_short, '_price', '320'),
(@p_online_short, '_regular_price', '320'),
(@p_online_short, '_sku', 'NGT-ONLINE-SHORT'),
(@p_online_short, '_sold_individually', 'yes'),
(@p_online_short, '_virtual', 'yes'),
(@p_online_short, '_downloadable', 'no'),
(@p_online_short, '_stock_status', 'instock');

INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('Online Tutoring — Long Term (3–12 months)', 'online-tutoring-long-term', 'product', 'publish', 1, NOW(), NOW(), 'open', 'closed', 'Live video tutoring session — long-term commitment rate.', '', '', '', '', '');

SET @p_online_long = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@p_online_long, '_price', '300'),
(@p_online_long, '_regular_price', '300'),
(@p_online_long, '_sku', 'NGT-ONLINE-LONG'),
(@p_online_long, '_sold_individually', 'yes'),
(@p_online_long, '_virtual', 'yes'),
(@p_online_long, '_downloadable', 'no'),
(@p_online_long, '_stock_status', 'instock');

INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('In-Person Tutoring — Short Term (1–3 months)', 'inperson-tutoring-short-term', 'product', 'publish', 1, NOW(), NOW(), 'open', 'closed', 'Tutor travels to learner\'s home. Billed per hour.', '', '', '', '', '');

SET @p_inperson_short = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@p_inperson_short, '_price', '350'),
(@p_inperson_short, '_regular_price', '350'),
(@p_inperson_short, '_sku', 'NGT-INPERSON-SHORT'),
(@p_inperson_short, '_sold_individually', 'yes'),
(@p_inperson_short, '_virtual', 'no'),
(@p_inperson_short, '_downloadable', 'no'),
(@p_inperson_short, '_stock_status', 'instock');

INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('In-Person Tutoring — Long Term (3–12 months)', 'inperson-tutoring-long-term', 'product', 'publish', 1, NOW(), NOW(), 'open', 'closed', 'Tutor travels to learner\'s home — long-term commitment rate.', '', '', '', '', '');

SET @p_inperson_long = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@p_inperson_long, '_price', '320'),
(@p_inperson_long, '_regular_price', '320'),
(@p_inperson_long, '_sku', 'NGT-INPERSON-LONG'),
(@p_inperson_long, '_sold_individually', 'yes'),
(@p_inperson_long, '_virtual', 'no'),
(@p_inperson_long, '_downloadable', 'no'),
(@p_inperson_long, '_stock_status', 'instock');

INSERT INTO `wp_posts`
  (`post_title`, `post_name`, `post_type`, `post_status`, `post_author`, `post_date`, `post_modified`, `comment_status`, `ping_status`, `post_content`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `guid`)
VALUES
  ('Tertiary Tutoring', 'tertiary-tutoring', 'product', 'publish', 1, NOW(), NOW(), 'open', 'closed', 'University-level specialist tutoring session. All formats available.', '', '', '', '', '');

SET @p_tertiary = LAST_INSERT_ID();

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
(@p_tertiary, '_price', '500'),
(@p_tertiary, '_regular_price', '500'),
(@p_tertiary, '_sku', 'NGT-TERTIARY'),
(@p_tertiary, '_sold_individually', 'yes'),
(@p_tertiary, '_virtual', 'yes'),
(@p_tertiary, '_downloadable', 'no'),
(@p_tertiary, '_stock_status', 'instock');

-- Mark all products as simple type
UPDATE `wp_term_relationships` tr
JOIN `wp_term_taxonomy` tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
JOIN `wp_terms` t ON tt.term_id = t.term_id
SET t.name = 'simple'
WHERE tt.taxonomy = 'product_type'
AND tr.object_id IN (@p_online_short, @p_online_long, @p_inperson_short, @p_inperson_long, @p_tertiary);

-- NGT WP Options: Pricing config (read by /ngt/v1/pricing endpoint)
INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('ngt_pricing_config', '{"online":{"short_term":{"client":320,"tutor":200,"commitment":"1-3 months"},"long_term":{"client":300,"tutor":200,"commitment":"3-12 months"}},"inperson":{"short_term":{"client":350,"tutor":250,"commitment":"1-3 months"},"long_term":{"client":320,"tutor":250,"commitment":"3-12 months"}},"tertiary":{"client":500,"tutor":350,"commitment":"Per session"},"high_frequency":{"client":300,"tutor":250,"note":"12+ lessons/month (3+ per week) — flat rate regardless of commitment"}}', 'yes')
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('ngt_currency', 'ZAR', 'yes'),
('ngt_timezone', 'Africa/Johannesburg', 'yes'),
('ngt_guarantee', 'First lesson free if unsatisfied. Full refund on first session.', 'yes')
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

SELECT 'NGT seed complete.' AS status;
