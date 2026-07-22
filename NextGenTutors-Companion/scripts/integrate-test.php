<?php
/**
 * Integrate pack validation (no WordPress bootstrap required).
 *
 * Usage: php scripts/integrate-test.php
 *
 * @package NextGenCompanion
 */

$root   = dirname( __DIR__ );
$errors = 0;

echo "NextGen Companion — integrate pack tests\n";
echo str_repeat( '-', 48 ) . "\n";

$required_classes = [
	'includes/integrations/class-ngc-workflow-spec-registry.php' => 'NGC_Workflow_Spec_Registry',
	'includes/integrations/class-ngc-integrate-runtime.php'    => 'NGC_Integrate_Runtime',
	'includes/workflows/class-ngc-workflow-integrate-executor.php' => 'NGC_Workflow_Integrate_Executor',
	'includes/integrations/class-ngc-session-reminders.php'    => 'NGC_Session_Reminders',
	'includes/integrations/class-ngc-referrals.php'            => 'NGC_Referrals',
	'includes/integrations/class-ngc-payout-scheduler.php'       => 'NGC_Payout_Scheduler',
	'includes/integrations/class-ngc-payout-export.php'        => 'NGC_Payout_Export',
	'includes/integrations/class-ngc-woocommerce-catalog.php'  => 'NGC_WooCommerce_Catalog',
	'includes/class-ngc-uuid.php'                              => 'NGC_Uuid',
	'includes/class-ngc-child-learners.php'                    => 'NGC_Child_Learners',
	'includes/class-ngc-section-cms.php'                       => 'NGC_Section_CMS',
	'includes/rest/class-ngc-rest-section-cms.php'             => 'NGC_Rest_Section_Cms',
	'includes/rest/class-ngc-rest-legacy-alias.php'          => 'NGC_Rest_Legacy_Alias',
];

foreach ( $required_classes as $rel => $class ) {
	$path = $root . '/' . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( ! file_exists( $path ) ) {
		echo "FAIL: missing {$rel}\n";
		++$errors;
		continue;
	}
	$src = file_get_contents( $path );
	if ( false === strpos( $src, "class {$class}" ) ) {
		echo "FAIL: {$rel} does not define {$class}\n";
		++$errors;
	}
}

$bootstrap = file_get_contents( $root . '/includes/class-ngc-plugin.php' );
if ( false === strpos( $bootstrap, 'NGC_Integrate_Runtime' ) ) {
	echo "FAIL: NGC_Integrate_Runtime not in plugin bootstrap modules\n";
	++$errors;
}
if ( false === strpos( $bootstrap, 'NGC_Workflow_Integrate_Executor' ) ) {
	echo "FAIL: NGC_Workflow_Integrate_Executor not in plugin bootstrap modules\n";
	++$errors;
}

$registry = file_get_contents( $root . '/includes/integrations/class-ngc-workflow-spec-registry.php' );
foreach ( [ 'import_from_integrate_dir', 'create', 'update', 'delete', 'spec_for_event' ] as $fn ) {
	if ( false === strpos( $registry, "function {$fn}" ) && false === strpos( $registry, "public static function {$fn}" ) ) {
		echo "FAIL: NGC_Workflow_Spec_Registry missing {$fn}\n";
		++$errors;
	}
}

$orchestrator = file_get_contents( $root . '/includes/workflows/class-ngc-workflow-orchestrator.php' );
if ( false === strpos( $orchestrator, 'execute_integrate_event' ) ) {
	echo "FAIL: NGC_Workflow_Orchestrator::execute_integrate_event missing\n";
	++$errors;
}

$workflows = file_get_contents( $root . '/includes/class-ngc-workflows.php' );
$events = [
	'reminders.queued',
	'reminder.24h.sent',
	'referral.converted',
	'payout.calculated',
	'review.submitted',
];
foreach ( $events as $event ) {
	if ( false === strpos( $workflows, "'{$event}'" ) ) {
		echo "FAIL: workflow event map missing {$event}\n";
		++$errors;
	}
}

$templates = file_get_contents( $root . '/includes/workflows/class-ngc-workflow-email-templates.php' );
foreach ( [ 'session_reminder_24h', 'session_reminder_1h', 'session_reminder_15m' ] as $key ) {
	if ( false === strpos( $templates, "'{$key}'" ) ) {
		echo "FAIL: email template missing {$key}\n";
		++$errors;
	}
}

$cli = file_get_contents( $root . '/includes/cli/class-ngc-cli.php' );
foreach ( [ 'integrate_status', 'import_woocommerce_products', 'run_payout_batch', 'export_payouts', 'confirm_payout', 'process_reminders', 'workflow_import', 'workflow_list', 'workflow_execute', 'workflow_delete' ] as $cmd ) {
	if ( false === strpos( $cli, "function {$cmd}" ) ) {
		echo "FAIL: WP-CLI missing {$cmd}\n";
		++$errors;
	}
}

$reviews = file_get_contents( $root . '/includes/class-ngc-reviews.php' );
if ( false === strpos( $reviews, "do_action(\n\t\t\t'ngc_review_submitted'" ) && false === strpos( $reviews, "do_action( 'ngc_review_submitted'" ) ) {
	echo "FAIL: NGC_Reviews::create_review must fire ngc_review_submitted\n";
	++$errors;
}

$bookings = file_get_contents( $root . '/includes/class-ngc-bookings.php' );
if ( false === strpos( $bookings, 'function update_status' ) ) {
	echo "FAIL: NGC_Bookings::update_status missing (Amelia bridge)\n";
	++$errors;
}

$amelia = file_get_contents( $root . '/includes/integrations/class-ngc-amelia.php' );
if ( false === strpos( $amelia, 'sync_from_amelia' ) ) {
	echo "FAIL: Amelia bridge must sync via NGC_Bookings::sync_from_amelia\n";
	++$errors;
}
if ( false === strpos( $amelia, 'update_status_by_amelia_id' ) ) {
	echo "FAIL: Amelia status bridge must use update_status_by_amelia_id\n";
	++$errors;
}

$tracking = file_get_contents( $root . '/includes/class-ngc-platform-tracking.php' );
if ( false === strpos( $tracking, 'marketing_capture_allowed' ) ) {
	echo "FAIL: POPIA marketing_capture_allowed missing\n";
	++$errors;
}

$integrate_rt = file_get_contents( $root . '/includes/integrations/class-ngc-integrate-runtime.php' );
if ( false === strpos( $integrate_rt, 'store_pending_referral' ) ) {
	echo "FAIL: referral cookie must be POPIA gated\n";
	++$errors;
}

$finance_rest = $root . '/includes/rest/class-ngc-rest-finance.php';
if ( ! file_exists( $finance_rest ) ) {
	echo "FAIL: class-ngc-rest-finance.php missing\n";
	++$errors;
}

$matching_rest = file_get_contents( $root . '/includes/rest/class-ngc-rest-matching.php' );
if ( false === strpos( $matching_rest, "function list" ) && false === strpos( $matching_rest, 'public static function list' ) ) {
	echo "FAIL: GET /matches list route missing\n";
	++$errors;
}

$reminders = file_get_contents( $root . '/includes/integrations/class-ngc-session-reminders.php' );
if ( false !== strpos( $reminders, 'starts_at' ) && false === strpos( $reminders, 'scheduled_at' ) ) {
	echo "FAIL: session reminders should read booking scheduled_at\n";
	++$errors;
}

$integrate_dir = $root . '/integrate';
$expected_specs = [
	'workflow-01-tutor-onboarding.json',
	'workflow-02-booking-payment.json',
	'workflow-03-reminder-notification.json',
	'workflow-04-review-rating.json',
	'workflow-05-tutor-payout.json',
];
foreach ( $expected_specs as $file ) {
	$path = $integrate_dir . '/' . $file;
	if ( ! file_exists( $path ) ) {
		echo "FAIL: missing spec {$file}\n";
		++$errors;
		continue;
	}
	$data = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $data ) || empty( $data['id'] ) ) {
		echo "FAIL: invalid JSON in {$file}\n";
		++$errors;
	}
}

$csv = $integrate_dir . '/nextgen-tutors-woocommerce-products.csv';
if ( ! file_exists( $csv ) ) {
	echo "FAIL: WooCommerce CSV missing\n";
	++$errors;
} else {
	$lines = file( $csv, FILE_IGNORE_NEW_LINES );
	if ( count( $lines ) < 2 ) {
		echo "FAIL: WooCommerce CSV has no product rows\n";
		++$errors;
	}
	if ( false === strpos( (string) $lines[0], 'Categories' ) ) {
		echo "FAIL: WooCommerce CSV missing Categories column\n";
		++$errors;
	}
}

$wc_catalog = file_get_contents( $root . '/includes/integrations/class-ngc-woocommerce-catalog.php' );
if ( false === strpos( $wc_catalog, 'assign_product_categories' ) ) {
	echo "FAIL: WooCommerce catalog must assign Categories from CSV\n";
	++$errors;
}

$payout_export = file_get_contents( $root . '/includes/integrations/class-ngc-payout-export.php' );
if ( false === strpos( $payout_export, 'to_csv' ) || false === strpos( $payout_export, 'recipient_email' ) ) {
	echo "FAIL: PayFast payout export CSV builder missing\n";
	++$errors;
}

if ( false === strpos( $reviews, 'function create_payout' ) && false === strpos( $reviews, 'public static function create_payout' ) ) {
	echo "FAIL: NGC_Reviews::create_payout missing (gateway pending flow)\n";
	++$errors;
}
if ( false === strpos( $reviews, 'function confirm_payout' ) && false === strpos( $reviews, 'public static function confirm_payout' ) ) {
	echo "FAIL: NGC_Reviews::confirm_payout missing\n";
	++$errors;
}

$legacy = file_get_contents( $integrate_dir . '/nextgentutors-workflows.php' );
if ( false === strpos( $legacy, 'DEPRECATED' ) ) {
	echo "FAIL: legacy orchestrator must be marked DEPRECATED\n";
	++$errors;
}

$db = file_get_contents( $root . '/includes/class-ngc-database.php' );
foreach ( [ 'referrals', 'reminder_schedules', 'studio_dashboards', 'child_learners', 'page_sections' ] as $table ) {
	if ( false === strpos( $db, "'{$table}'" ) ) {
		echo "FAIL: database schema missing {$table}\n";
		++$errors;
	}
}
if ( false === strpos( $db, 'ensure_uuid_columns' ) ) {
	echo "FAIL: database missing ensure_uuid_columns migration\n";
	++$errors;
}
if ( false === strpos( $db, 'amelia_booking_id' ) ) {
	echo "FAIL: bookings table missing amelia_booking_id column\n";
	++$errors;
}
if ( false === strpos( $db, 'booking_reminder' ) ) {
	echo "FAIL: reminder_schedules missing booking_reminder unique key\n";
	++$errors;
}

$payout_sched = file_get_contents( $root . '/includes/integrations/class-ngc-payout-scheduler.php' );
if ( false === strpos( $payout_sched, 'CRON_HOOK_BIWEEKLY' ) || false === strpos( $payout_sched, 'ngc_biweekly' ) ) {
	echo "FAIL: bi-weekly payout cron missing\n";
	++$errors;
}

$roles = file_get_contents( $root . '/includes/class-ngc-roles.php' );
if ( false === strpos( $roles, "'child_learner'" ) ) {
	echo "FAIL: child_learner role missing\n";
	++$errors;
}

$registration = file_get_contents( $root . '/includes/class-ngc-registration.php' );
if ( false === strpos( $registration, 'NGC_Child_Learners::create' ) ) {
	echo "FAIL: registration must persist child learners table\n";
	++$errors;
}

$rest = file_get_contents( $root . '/includes/rest/class-ngc-rest.php' );
if ( false === strpos( $rest, 'NGC_Rest_Section_Cms::register' ) ) {
	echo "FAIL: section CMS REST not registered\n";
	++$errors;
}

$bootstrap = file_get_contents( $root . '/includes/class-ngc-plugin.php' );
if ( false === strpos( $bootstrap, 'NGC_Section_CMS' ) || false === strpos( $bootstrap, 'NGC_Child_Learners' ) ) {
	echo "FAIL: Phase 1 modules missing from bootstrap\n";
	++$errors;
}
if ( false === strpos( $bootstrap, 'NGC_Integrations_Bootstrap' ) ) {
	echo "FAIL: NGC_Integrations_Bootstrap not in plugin bootstrap modules\n";
	++$errors;
}

$section_cms = file_get_contents( $root . '/includes/class-ngc-section-cms.php' );
if ( false === strpos( $section_cms, 'CMS_DISABLED_MARKER' ) || false === strpos( $section_cms, 'cms_theme_section_map' ) ) {
	echo "FAIL: section CMS filter_home_sections not wired to kinetic registry\n";
	++$errors;
}

$gamipress = file_get_contents( $root . '/includes/gamification/class-ngc-gamipress-adapter.php' );
if ( false === strpos( $gamipress, 'ensure_achievements' ) ) {
	echo "FAIL: GamiPress adapter missing ensure_achievements\n";
	++$errors;
}

$payments = file_get_contents( $root . '/includes/class-ngc-payments.php' );
if ( false === strpos( $payments, 'settle_order' ) || false === strpos( $payments, 'ngc_payment_settled' ) ) {
	echo "FAIL: NGC_Payments missing idempotent settle_order\n";
	++$errors;
}

$registry = file_get_contents( $root . '/includes/class-ngc-page-forms-registry.php' );
if ( false === strpos( $registry, 'ensure_production_forms' ) ) {
	echo "FAIL: page forms registry missing ensure_production_forms\n";
	++$errors;
}

$checkout = $root . '/includes/integrations/class-ngc-parent-checkout.php';
if ( ! is_file( $checkout ) ) {
	echo "FAIL: NGC_Parent_Checkout missing\n";
	++$errors;
}

$main = file_get_contents( $root . '/nextgencompanion.php' );
preg_match( "/define\\(\\s*'NGC_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $main, $version_constant );
preg_match( '/Version:\s*([0-9.]+)/', $main, $version_header );
if ( empty( $version_constant[1] ) || ( $version_header[1] ?? '' ) !== $version_constant[1] ) {
	echo "FAIL: Companion header and NGC_VERSION must match\n";
	++$errors;
}

$legacy = file_get_contents( $root . '/includes/rest/class-ngc-rest-legacy-alias.php' );
if ( false === strpos( $legacy, 'ngt/v1' ) || false === strpos( $legacy, 'register_alias_routes' ) ) {
	echo "FAIL: ngt/v1 REST legacy alias missing\n";
	++$errors;
}

$rest = file_get_contents( $root . '/includes/rest/class-ngc-rest.php' );
if ( false === strpos( $rest, 'NGC_Rest_Legacy_Alias::register_alias_routes' ) ) {
	echo "FAIL: legacy alias not bootstrapped in NGC_Rest\n";
	++$errors;
}

$child = file_get_contents( $root . '/includes/class-ngc-child-learners.php' );
if ( false === strpos( $child, 'provision_wp_user' ) ) {
	echo "FAIL: child learner WP provisioning missing\n";
	++$errors;
}

$cms = file_get_contents( $root . '/includes/class-ngc-section-cms.php' );
if ( false === strpos( $cms, 'theme_section_option_map' ) || false === strpos( $cms, 'ngc_home_section_enabled' ) ) {
	echo "FAIL: section CMS theme bridge incomplete\n";
	++$errors;
}

$home_paths = [
	dirname( $root ) . '/inc/defaults-production/home.php',
	dirname( $root ) . '/inc/defaults/home.php',
	dirname( $root ) . '/NextGenTutors-BeyondInfinity/inc/defaults-production/home.php',
	dirname( $root ) . '/NextGenTutors-BeyondInfinity/inc/defaults/home.php',
];
$home = '';
foreach ( $home_paths as $home_path ) {
	$home = @file_get_contents( $home_path );
	if ( $home && false !== strpos( $home, 'cms_trust' ) ) {
		break;
	}
}
if ( ! $home || false === strpos( $home, 'cms_trust' ) ) {
	echo "FAIL: home.php CMS wiring missing\n";
	++$errors;
}

$amelia_boot = file_get_contents( $root . '/includes/integrations/class-ngc-amelia-bootstrap.php' );
if ( false === strpos( $amelia_boot, 'allows_elevated_sync' ) || false === strpos( $amelia_boot, 'begin_trusted_sync' ) ) {
	echo "FAIL: Amelia bootstrap must gate elevated sync\n";
	++$errors;
}
if ( false === strpos( $amelia_boot, 'allows_schema_seed' ) ) {
	echo "FAIL: Amelia bootstrap must gate schema seeding\n";
	++$errors;
}

$amelia_adapter = file_get_contents( $root . '/includes/adapters/class-ngc-amelia-adapter.php' );
if ( false === strpos( $amelia_adapter, 'allows_elevated_sync' ) || false === strpos( $amelia_adapter, 'redact_sensitive' ) ) {
	echo "FAIL: Amelia adapter must enforce elevated sync and redact secrets\n";
	++$errors;
}

$orchestrator = file_get_contents( $root . '/includes/workflows/class-ngc-workflow-orchestrator.php' );
if ( false === strpos( $orchestrator, 'begin_trusted_sync' ) ) {
	echo "FAIL: workflow orchestrator must scope Amelia trusted sync\n";
	++$errors;
}

$amelia_e2e = file_get_contents( $root . '/scripts/amelia-e2e-docker.php' );
if ( false === strpos( $amelia_e2e, 'ngc_e2e_require_demo_stack' ) ) {
	echo "FAIL: Amelia E2E must use demo stack guard\n";
	++$errors;
}

$payfast_e2e = file_get_contents( $root . '/scripts/payfast-e2e-docker.php' );
if ( false === strpos( $payfast_e2e, 'ngc_e2e_require_demo_stack' ) ) {
	echo "FAIL: PayFast E2E must use demo stack guard\n";
	++$errors;
}

echo str_repeat( '-', 48 ) . "\n";
echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — integrate pack tests passed\n";
exit( $errors ? 1 : 0 );
