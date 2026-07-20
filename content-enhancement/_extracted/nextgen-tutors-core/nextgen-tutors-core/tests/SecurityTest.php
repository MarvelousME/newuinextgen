<?php
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase {

    public function test_encryption_decryption() {
        $security = NGT_Security::get_instance();
        $original = "Secret Data 123";
        
        $encrypted = $security->encrypt_data($original);
        $this->assertNotEquals($original, $encrypted);
        
        $decrypted = $security->decrypt_data($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_sanitization() {
        $security = NGT_Security::get_instance();
        
        $email = " TEST@EXAMPLE.com ";
        $this->assertEquals("TEST@EXAMPLE.com", $security->sanitize_email($email));
        
        $text = "<b>Hello</b> <script>alert(1)</script>";
        $sanitized = $security->sanitize_text($text);
        $this->assertStringNotContainsString('<script>', $sanitized);
    }

    public function test_validation() {
        $security = NGT_Security::get_instance();
        
        $this->assertTrue($security->validate_email('test@example.com'));
        $this->assertFalse($security->validate_email('invalid-email'));
    }
}
