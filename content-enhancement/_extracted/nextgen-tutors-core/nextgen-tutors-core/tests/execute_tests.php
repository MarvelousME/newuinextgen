<?php
require_once __DIR__ . '/bootstrap.php';

echo "[*] Starting NextGen Tutors Core Unit Tests...\n";
$tests_passed = 0;
$tests_failed = 0;

function run_test($name, $callable) {
    global $tests_passed, $tests_failed;
    echo str_pad("Testing: $name ", 60, ".");
    try {
        $callable();
        echo "[ PASS ]\n";
        $tests_passed++;
    } catch (Throwable $e) {
        echo "[ FAIL ]\n";
        echo "   -> ERROR: " . $e->getMessage() . "\n";
        echo "   -> IN: " . $e->getFile() . " on line " . $e->getLine() . "\n";
        $tests_failed++;
    }
}

// TEST 1: Core Instantiation
run_test("Core Instantiation & Singleton Resolution", function() {
    $ngt = ngt();
    if (!$ngt instanceof NextGen_Tutors_Core) {
        throw new Exception("ngt() did not return an instance of NextGen_Tutors_Core");
    }
    if (!$ngt->logger instanceof NGT_Logger) {
        throw new Exception("Logger module failed to initialize");
    }
    if (!$ngt->verifier instanceof NGT_Verifier) {
        throw new Exception("Verifier module failed to initialize");
    }
});

// TEST 2: Run System Check (Testing the previous fatal error)
run_test("run_system_check() executes without fatal errors", function() {
    // Mock current_user_can for this test
    if (!function_exists('current_user_can')) {
        function current_user_can($cap) { return true; }
    }
    $ngt = ngt();
    // This previously crashed due to undefined method NGT_Detector::run_full_detection()
    $ngt->run_system_check();
});

// TEST 3: Path Constant Resolution
run_test("Path constants do not collide with legacy plugin", function() {
    if (!defined('NGT_CORE_PLUGIN_DIR')) {
        throw new Exception("NGT_CORE_PLUGIN_DIR is not defined");
    }
    if (defined('NGT_PLUGIN_DIR')) {
        // Technically not a fail if it's defined elsewhere, but in our core it shouldn't be
        $content = file_get_contents(NGT_CORE_PLUGIN_DIR . 'nextgen-tutors-core.php');
        if (strpos($content, "define('NGT_PLUGIN_DIR'") !== false) {
            throw new Exception("Legacy constant NGT_PLUGIN_DIR is still being defined!");
        }
    }
});

// TEST 4: API Routes Registration
run_test("REST API routes register safely", function() {
    $api = NGT_API::get_instance();
    // In our mock, register_rest_route doesn't exist, so let's mock it
    if (!function_exists('register_rest_route')) {
        function register_rest_route() {}
    }
    $api->register_routes();
});

// TEST 5: Queue Add Job
run_test("Queue handles background jobs insertion", function() {
    $queue = NGT_Queue::get_instance();
    $job_id = $queue->add_job('test_job', ['foo' => 'bar']);
    if ($job_id !== 1) {
        throw new Exception("Job insertion returned unexpected result: " . print_r($job_id, true));
    }
});

echo "\n-------------------------------------------------\n";
echo "Tests Completed: " . ($tests_passed + $tests_failed) . "\n";
echo "Passed: $tests_passed \n";
echo "Failed: $tests_failed \n";
echo "-------------------------------------------------\n";
exit($tests_failed > 0 ? 1 : 0);
