<?php
/**
 * Static validation for NextGen Companion (no WordPress bootstrap required).
 *
 * Usage: php scripts/validate.php
 *
 * @package NextGenCompanion
 */

$root = dirname( __DIR__ );
$errors = 0;
$checked = 0;

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

echo "NextGen Companion validation\n";
echo str_repeat( '-', 40 ) . "\n";

foreach ( $iterator as $file ) {
	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$path = $file->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	++$checked;
	$out = [];
	$code = 0;
	exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1', $out, $code );
	if ( 0 !== $code ) {
		echo "FAIL: {$path}\n  " . implode( "\n  ", $out ) . "\n";
		++$errors;
	}
}

$required = [
	'nextgencompanion.php',
	'includes/class-ngc-loader.php',
	'includes/gamification/class-ngc-gamification.php',
	'includes/export/class-ngc-export-engine.php',
	'includes/audit/class-ngc-audit-service.php',
	'includes/diagnostics/class-ngc-ai-diagnostics.php',
	'includes/ai/class-ngc-crypto.php',
	'includes/ai/class-bia-policy.php',
	'includes/ai/class-ngc-ai-models.php',
	'includes/ai/class-ngc-ai-agents.php',
	'includes/ai/class-ngc-ai-chat.php',
	'includes/rest/class-ngc-rest-ai.php',
	'includes/admin/class-ngc-ai-admin.php',
	'assets/js/ngc-ai.js',
	'assets/css/ngc-ai.css',
	'includes/diagnostics/class-ngc-exception-log.php',
	'includes/matching/class-ngc-smart-matching.php',
	'includes/class-ngc-forms.php',
	'includes/class-ngc-tutor-seeder.php',
	'includes/rest/class-ngc-rest-platform-services.php',
	'includes/class-ngc-database.php',
	'includes/class-ngc-verification.php',
	'includes/class-ngc-rate-limiter.php',
	'includes/class-ngc-marketplace.php',
	'includes/class-ngc-page-forms-registry.php',
	'includes/class-ngc-dashboard-analytics.php',
	'includes/admin/class-ngc-page-forms-registry-admin.php',
	'includes/rest/class-ngc-rest-marketplace.php',
	'includes/rest/class-ngc-rest-page-forms-registry.php',
	'assets/js/ngc-marketplace.js',
	'assets/css/ngc-marketplace.css',
	'assets/js/dashboard-rest.js',
	'includes/matching/class-ngc-tutor-cpt-source.php',
	'includes/admin/class-ngc-tutor-demo-admin.php',
	'includes/cli/class-ngc-cli.php',
	'includes/class-ngc-plugin.php',
	'includes/class-ngc-registration.php',
	'includes/class-ngc-workflows.php',
	'includes/workflows/class-ngc-workflow-orchestrator.php',
	'includes/adapters/class-ngc-fluentcrm-adapter.php',
	'includes/adapters/interface-ngc-integration-adapter.php',
	'includes/workflows/class-ngc-workflow-email-templates.php',
	'includes/admin/class-ngc-workflow-admin.php',
	'includes/admin/class-ngc-platform-admin.php',
	'includes/rest/class-ngc-rest-dashboard.php',
	'includes/rest/class-ngc-rest-platform.php',
	'includes/class-ngc-platform-repository.php',
	'includes/class-ngc-platform-tracking.php',
	'includes/class-ngc-platform-analytics.php',
	'includes/class-ngc-platform-demo.php',
	'includes/class-ngc-tutor-availability-repository.php',
	'includes/class-ngc-tutor-calendar-service.php',
	'includes/adapters/class-ngc-amelia-availability-adapter.php',
	'includes/integrations/class-ngc-amelia-bootstrap.php',
	'scripts/amelia-e2e-docker.php',
	'scripts/ngc-e2e-guard.php',
	'includes/adapters/class-ngc-internal-booking-adapter.php',
	'includes/rest/class-ngc-rest-tutor-calendar.php',
	'includes/rest/class-ngc-rest-reviews.php',
	'includes/integrations/class-ngc-workflow-spec-registry.php',
	'includes/integrations/class-ngc-integrate-runtime.php',
	'includes/integrations/class-ngc-session-reminders.php',
	'includes/integrations/class-ngc-referrals.php',
	'includes/integrations/class-ngc-payout-scheduler.php',
	'includes/integrations/class-ngc-payout-export.php',
	'includes/class-ngc-uuid.php',
	'includes/class-ngc-child-learners.php',
	'includes/class-ngc-section-cms.php',
	'includes/rest/class-ngc-rest-section-cms.php',
	'scripts/uat-integrations.php',
	'includes/rest/class-ngc-rest-legacy-alias.php',
	'scripts/phase2-e2e-docker.php',
	'scripts/phase3-e2e-docker.php',
	'includes/integrations/class-ngc-woocommerce-catalog.php',
	'includes/workflows/class-ngc-workflow-integrate-executor.php',
	'includes/studio/class-ngc-studio.php',
	'includes/studio/class-ngc-studio-repository.php',
	'includes/studio/class-ngc-studio-compiler.php',
	'includes/studio/class-ngc-studio-runtime.php',
	'includes/studio/class-ngc-studio-engine.php',
	'includes/studio/class-ngc-studio-event-bus.php',
	'includes/studio/class-ngc-studio-triggers.php',
	'includes/studio/class-ngc-studio-templates.php',
	'includes/studio/class-ngc-studio-importer.php',
	'includes/studio/class-ngc-studio-simulator.php',
	'includes/studio/class-ngc-studio-verification.php',
	'includes/studio/class-ngc-studio-forms.php',
	'includes/studio/class-ngc-studio-email.php',
	'includes/studio/class-ngc-studio-notifications.php',
	'includes/studio/class-ngc-studio-dashboards.php',
	'includes/studio/class-ngc-studio-stream.php',
	'includes/rest/class-ngc-rest-studio.php',
	'includes/rest/class-ngc-rest-finance.php',
	'includes/admin/class-ngc-studio-admin.php',
	'includes/admin/framework/class-ngc-platform-version.php',
	'includes/admin/framework/class-ngc-admin-theme.php',
	'includes/admin/framework/class-ngc-admin-nav-layout.php',
	'includes/admin/framework/class-ngc-admin-entity-registry.php',
	'includes/admin/framework/class-ngc-admin-grid.php',
	'includes/rest/class-ngc-rest-admin-shell.php',
	'integrate/workflow-01-tutor-onboarding.json',
	'integrate/workflow-02-booking-payment.json',
	'integrate/workflow-03-reminder-notification.json',
	'integrate/workflow-04-review-rating.json',
	'integrate/workflow-05-tutor-payout.json',
	'integrate/nextgen-tutors-woocommerce-products.csv',
	'uninstall.php',
];

foreach ( $required as $rel ) {
	$full = $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( ! file_exists( $full ) ) {
		echo "MISSING: {$rel}\n";
		++$errors;
	}
}

$tables = [
	'matches', 'bookings', 'invoices', 'wallet_ledger', 'payouts',
	'reviews', 'audit_log', 'tutor_applications', 'session_logs', 'earnings', 'ratings', 'workflow_runs',
	'analytics_events', 'visitor_profiles', 'user_profiles', 'acquisition_sources', 'affiliate_clicks',
	'attribution_links', 'user_sessions', 'device_profiles', 'conversion_events', 'metric_snapshots',
	'demo_seed_log', 'consent_log',
	'gamification_scores', 'gamification_achievements', 'gamification_events',
	'leaderboard_entries', 'export_jobs', 'export_templates',
	'repair_snapshots', 'ai_diagnostics_log',
	'referrals', 'reminder_schedules',
	'studio_workflows', 'studio_versions', 'studio_triggers', 'studio_forms',
	'studio_emails', 'studio_notifications', 'studio_executions', 'studio_dashboards',
	'child_learners', 'page_sections',
];
$db = file_get_contents( $root . '/includes/class-ngc-database.php' );
foreach ( $tables as $table ) {
	if ( false === strpos( $db, "'{$table}'" ) ) {
		echo "TABLE DEF MISSING: {$table}\n";
		++$errors;
	}
}

echo str_repeat( '-', 40 ) . "\n";
echo "PHP files linted: {$checked}\n";

$smoke = $root . '/scripts/smoke-verification.php';
if ( file_exists( $smoke ) ) {
	passthru( 'php ' . escapeshellarg( $smoke ), $smoke_code );
	if ( 0 !== $smoke_code ) {
		++$errors;
	}
}

$versions = $root . '/scripts/verify-versions.php';
if ( file_exists( $versions ) ) {
	passthru( 'php ' . escapeshellarg( $versions ), $ver_code );
	if ( 0 !== $ver_code ) {
		++$errors;
	}
}

$integrate = $root . '/scripts/integrate-test.php';
if ( file_exists( $integrate ) ) {
	passthru( 'php ' . escapeshellarg( $integrate ), $int_code );
	if ( 0 !== $int_code ) {
		++$errors;
	}
}

$unit = $root . '/tests/run.php';
if ( file_exists( $unit ) ) {
	passthru( 'php ' . escapeshellarg( $unit ), $unit_code );
	if ( 0 !== $unit_code ) {
		++$errors;
	}
}

$phpunit = $root . '/scripts/run-phpunit.php';
if ( file_exists( $phpunit ) ) {
	passthru( 'php ' . escapeshellarg( $phpunit ), $phpunit_code );
	if ( 0 !== $phpunit_code ) {
		++$errors;
	}
}

echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — all checks passed\n";
exit( $errors ? 1 : 0 );
