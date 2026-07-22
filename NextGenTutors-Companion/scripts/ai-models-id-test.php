<?php
/**
 * Standalone regression test: AI model/agent id case-resolution and endpoint building.
 * Run: php scripts/ai-models-id-test.php
 *
 * @package NextGenCompanion
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['__opts'] = [];

function get_option( $k, $d = false ) { return $GLOBALS['__opts'][ $k ] ?? $d; }
function update_option( $k, $v, $a = false ) { $GLOBALS['__opts'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
function esc_url_raw( $u ) { return filter_var( $u, FILTER_VALIDATE_URL ) ? $u : ''; }
function untrailingslashit( $s ) { return rtrim( (string) $s, '/' ); }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function wp_generate_password( $l, $s = true, $e = false ) { return substr( str_shuffle( 'AbCdEfGh1234' ), 0, $l ); }
function current_time( $t ) { return gmdate( 'Y-m-d H:i:s' ); }
function __( $s, $d = null ) { return $s; }
function apply_filters( $t, $v ) { return $v; }

class WP_Error {
	public $code; public $message; public $data;
	public function __construct( $c = '', $m = '', $d = null ) { $this->code = $c; $this->message = $m; $this->data = $d; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $t ) { return $t instanceof WP_Error; }

class BIA_Policy {
	public static function can( $op, $ctx = [] ) { return true; }
	public static function allow_host( $u ) {}
	public static function audit( $op, $st, $d = [] ) {}
	public static function host_allowed( $u ) { return true; }
	public static function redact( $t ) { return $t; }
}

require __DIR__ . '/../includes/ai/class-ngc-ai-models.php';
require __DIR__ . '/../includes/ai/class-ngc-ai-agents.php';

$fail = 0;
function check( $label, $cond ) {
	global $fail;
	echo ( $cond ? 'PASS' : 'FAIL' ) . " {$label}\n";
	if ( ! $cond ) { $fail++; }
}

// Simulate a legacy model saved with an uppercase id suffix.
$GLOBALS['__opts']['ngc_ai_models'] = [
	'openai-Ab3D' => [ 'label' => 'OpenAi', 'base_url' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini', 'created' => '2026-01-01' ],
];
$GLOBALS['__opts']['ngc_ai_keys'] = [ 'openai-Ab3D' => 's:xxxx' ];

// 1. Lowercased id (what sanitize_key produced in REST) must resolve.
check( 'resolve_id lowercased legacy id', 'openai-Ab3D' === NGC_AI_Models::resolve_id( 'openai-ab3d' ) );
check( 'get() with lowercased id', null !== NGC_AI_Models::get( 'openai-ab3d' ) );
check( 'has_key() with lowercased id', NGC_AI_Models::has_key( 'openai-ab3d' ) );
check( 'resolve_id unknown id empty', '' === NGC_AI_Models::resolve_id( 'nope' ) );

// 2. New saves must produce lowercase ids and update instead of duplicate.
$res = NGC_AI_Models::save( [ 'label' => 'Test', 'base_url' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini' ] );
check( 'save returns id', is_array( $res ) && ! empty( $res['id'] ) );
check( 'new id is lowercase', is_array( $res ) && $res['id'] === strtolower( $res['id'] ) );

// 3. Re-saving with a lowercased legacy id updates the existing record.
$before = count( get_option( 'ngc_ai_models' ) );
NGC_AI_Models::save( [ 'id' => 'openai-ab3d', 'label' => 'OpenAi2', 'base_url' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini' ] );
check( 'update via lowercased id does not duplicate', count( get_option( 'ngc_ai_models' ) ) === $before );
$m = NGC_AI_Models::get( 'openai-Ab3D' );
check( 'update applied to legacy record', is_array( $m ) && 'OpenAi2' === $m['label'] );

// 4. Save validation errors are 400 with clear messages.
$err = NGC_AI_Models::save( [ 'label' => 'X', 'base_url' => '', 'model' => 'gpt-4o-mini' ] );
check( 'missing base_url is WP_Error 400', is_wp_error( $err ) && 400 === $err->get_error_data()['status'] );
$err = NGC_AI_Models::save( [ 'label' => 'X', 'base_url' => 'https://api.openai.com/v1', 'model' => '' ] );
check( 'missing model is WP_Error 400', is_wp_error( $err ) && 400 === $err->get_error_data()['status'] );

// 5. Endpoint builder appends /v1 for bare hosts (Anthropic OpenAI-compat).
$ref = new ReflectionMethod( 'NGC_AI_Models', 'endpoint' );
$ref->setAccessible( true );
check( 'bare host gains /v1', 'https://api.anthropic.com/v1/chat/completions' === $ref->invoke( null, 'https://api.anthropic.com' ) );
check( 'base with /v1 untouched', 'https://api.openai.com/v1/chat/completions' === $ref->invoke( null, 'https://api.openai.com/v1' ) );
check( 'full endpoint untouched', 'https://x.example/v1/chat/completions' === $ref->invoke( null, 'https://x.example/v1/chat/completions' ) );

// 6. Agents: legacy uppercase agent id resolves; model_id resolved to canonical form.
$GLOBALS['__opts']['ngc_ai_agents'] = [
	'helper-Qz9K' => [ 'id' => 'helper-Qz9K', 'name' => 'Helper', 'model_id' => 'openai-ab3d', 'rules' => '', 'role' => 'worker', 'commands' => [], 'skills' => [], 'created' => '2026-01-01' ],
];
check( 'agent get() with lowercased id', null !== NGC_AI_Agents::get( 'helper-qz9k' ) );
$res = NGC_AI_Agents::save( [ 'id' => 'helper-qz9k', 'name' => 'Helper', 'model_id' => 'openai-ab3d' ] );
check( 'agent update does not duplicate', 1 === count( get_option( 'ngc_ai_agents' ) ) );
$a = NGC_AI_Agents::get( 'helper-Qz9K' );
check( 'agent model_id canonicalised', is_array( $a ) && 'openai-Ab3D' === $a['model_id'] );

echo $fail ? "\n{$fail} FAILURES\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );
