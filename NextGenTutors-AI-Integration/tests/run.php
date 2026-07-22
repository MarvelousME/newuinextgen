<?php
/**
 * Lightweight unit test runner for NextGenTutors-AI-Integration.
 *
 * No WordPress bootstrap required. Usage:
 *   php tests/run.php   (or: composer test)
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$root = dirname( __DIR__ );

$load = static function ( string $relative ) use ( $root ): void {
	$path = $root . '/' . ltrim( $relative, '/' );
	if ( is_readable( $path ) ) {
		require_once $path;
	}
};

foreach (
	[
		'includes/class-ngtai-database.php',
		'includes/class-ngtai-config.php',
		'includes/class-ngtai-crypto.php',
		'includes/class-ngtai-signature.php',
		'includes/class-ngtai-nonce-store.php',
		'includes/class-ngtai-idempotency-store.php',
		'includes/class-ngtai-access.php',
		'includes/class-ngtai-logger.php',
		'includes/class-ngtai-audit.php',
		'includes/contracts/class-ngtai-event-envelope.php',
		'includes/contracts/class-ngtai-agent-result.php',
		'includes/class-ngtai-event-mapper.php',
		'includes/class-ngtai-redactor.php',
		'includes/class-ngtai-policy-gate.php',
		'includes/class-ngtai-api-client.php',
		'includes/class-ngtai-delivery-repository.php',
		'includes/class-ngtai-result-repository.php',
		'includes/class-ngtai-callback-controller.php',
		'includes/class-ngtai-outbox-bridge.php',
	] as $file
) {
	$load( $file );
}

foreach (
	[
		'test-signature.php',
		'test-signature-tamper.php',
		'test-signature-skew.php',
		'test-nonce-replay.php',
		'test-idempotency.php',
		'test-redaction.php',
		'test-minor-minimization.php',
		'test-callback-auth.php',
		'test-event-mapping.php',
		'test-outbox-delivery.php',
		'test-policy-gate.php',
		'test-result-versioning.php',
	] as $test_file
) {
	echo 'Running ' . $test_file . "\n";
	try {
		require __DIR__ . '/' . $test_file;
	} catch ( Throwable $error ) {
		echo 'ERROR ' . $test_file . ' — ' . $error->getMessage() . "\n";
		$failed++;
	}
}

echo "\n";
if ( $failed > 0 ) {
	echo "FAILED — {$failed} assertion(s), {$passed} passed\n";
	exit( 1 );
}
echo "OK — unit tests passed ({$passed} assertions)\n";
exit( 0 );
