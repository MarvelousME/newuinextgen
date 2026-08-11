<?php
/**
 * Memory settings + write policy unit tests.
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * NGC_Memory_Settings / NGC_Memory_Service policy.
 */
class MemorySettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['ngc_test_options'] = [];
		NGC_Memory_Service::reset_provider();
	}

	public function test_defaults_are_safe() {
		$d = NGC_Memory_Settings::defaults();
		$this->assertFalse( $d['enabled'] );
		$this->assertFalse( $d['retrieve_enabled'] );
		$this->assertFalse( $d['write_enabled'] );
		$this->assertFalse( $d['skills_enabled'] );
		$this->assertFalse( $d['wiki_enabled'] );
		$this->assertFalse( $d['codegraph_enabled'] );
		$this->assertFalse( $d['proxy_enabled'] );
		$this->assertFalse( $d['allow_long_term_minors'] );
		$this->assertSame( 'DISABLED', $d['mode'] );
	}

	public function test_proxy_cannot_be_enabled() {
		NGC_Memory_Settings::update( [ 'proxy_enabled' => true, 'enabled' => true, 'mode' => 'REMOTE' ] );
		$cfg = NGC_Memory_Settings::get();
		$this->assertFalse( $cfg['proxy_enabled'] );
	}

	public function test_inactive_when_disabled() {
		$this->assertFalse( NGC_Memory_Settings::is_active() );
		$this->assertFalse( NGC_Memory_Settings::retrieve_allowed() );
		$this->assertFalse( NGC_Memory_Settings::write_allowed() );
	}

	public function test_classify_forbidden_credentials() {
		$this->assertSame( 'FORBIDDEN', NGC_Memory_Service::classify( [ 'text' => 'here is my api_key sk-abc' ] ) );
	}

	public function test_classify_minor_linked() {
		$this->assertSame( 'MINOR_LINKED', NGC_Memory_Service::classify( [ 'text' => 'lesson notes', 'minor_linked' => true ] ) );
	}

	public function test_write_policy_denies_minors_by_default() {
		$gate = NGC_Memory_Service::write_policy_gate( 'MINOR_LINKED', [] );
		$this->assertFalse( $gate['allow'] );
		$this->assertSame( 'deny_long_term_minors', $gate['reason'] );
	}

	public function test_write_policy_denies_tutoring_without_explicit_allow() {
		$gate = NGC_Memory_Service::write_policy_gate( 'ROUTINE', [ 'tutoring_data' => true ] );
		$this->assertFalse( $gate['allow'] );
	}

	public function test_retrieve_safe_when_disabled() {
		$out = NGC_Memory_Service::retrieve_safe( [ 'query' => 'hello' ] );
		$this->assertTrue( $out['ok'] );
		$this->assertSame( '', $out['context_text'] );
	}

	public function test_write_safe_when_disabled() {
		$out = NGC_Memory_Service::write_safe( [ 'text' => 'hello', 'async' => false ] );
		$this->assertTrue( $out['ok'] );
		$this->assertFalse( $out['written'] );
		$this->assertSame( 'write_disabled', $out['reason'] );
	}

	public function test_noop_provider_health() {
		$p = new NGC_Memory_Noop_Provider();
		$h = $p->health();
		$this->assertTrue( $h['ok'] );
		$this->assertSame( 'noop', $p->slug() );
	}
}
