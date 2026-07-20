<?php
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

    public function test_singleton_instance() {
        $instance1 = NGT_Logger::get_instance();
        $instance2 = NGT_Logger::get_instance();
        $this->assertSame($instance1, $instance2);
    }

    public function test_logging_methods() {
        $logger = NGT_Logger::get_instance();
        
        // These calls should not throw exceptions even with mock DB
        $this->assertNull($logger->info('Test Info', ['context' => 'unit-test']));
        $this->assertNull($logger->error('Test Error', ['code' => 500]));
        $this->assertNull($logger->success('Test Success'));
    }
}
