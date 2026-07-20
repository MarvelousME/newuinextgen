<?php
/**
 * Fail build when package versions drift.
 *
 * Usage: php scripts/verify-versions.php
 *
 * @package NextGenCompanion
 */

$root  = dirname( __DIR__ );
$repo  = dirname( $root );
$errors = 0;

$plugin_main = file_get_contents( $root . '/nextgencompanion.php' );
preg_match( "/define\\(\\s*'NGC_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $plugin_main, $ngc_const );
preg_match( '/Version:\s*([0-9.]+)/', $plugin_main, $ngc_header );

$theme_functions = @file_get_contents( $repo . '/NextGenTutors-BeyondInfinity/functions.php' );
$theme_style     = @file_get_contents( $repo . '/NextGenTutors-BeyondInfinity/style.css' );
preg_match( "/define\\(\\s*'BI_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", (string) $theme_functions, $bi_const );
preg_match( '/Version:\s*([0-9.]+)/', (string) $theme_style, $bi_header );

$expected_ngc = $ngc_const[1] ?? '';
$expected_bi  = $bi_const[1] ?? '';

echo "Version verification\n";
echo str_repeat( '-', 40 ) . "\n";

if ( empty( $expected_ngc ) ) {
	echo "FAIL: NGC_VERSION constant not found\n";
	++$errors;
} else {
	echo "NGC_VERSION constant: {$expected_ngc}\n";
	if ( ( $ngc_header[1] ?? '' ) !== $expected_ngc ) {
		echo "FAIL: Plugin header version " . ( $ngc_header[1] ?? '?' ) . " != NGC_VERSION {$expected_ngc}\n";
		++$errors;
	}
}

if ( empty( $expected_bi ) ) {
	echo "WARN: BI_VERSION not found (theme optional in CI)\n";
} else {
	echo "BI_VERSION constant: {$expected_bi}\n";
	if ( ( $bi_header[1] ?? '' ) !== $expected_bi ) {
		echo "FAIL: style.css version " . ( $bi_header[1] ?? '?' ) . " != BI_VERSION {$expected_bi}\n";
		++$errors;
	}
}

echo str_repeat( '-', 40 ) . "\n";
echo $errors ? "FAILED with {$errors} version mismatch(es)\n" : "OK — versions synchronized\n";
exit( $errors ? 1 : 0 );
