<?php
/**
 * AI integration capability documentation.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'manage'  => [
		'manage_options',
		'ngc_ai_ops',
	],
	'approve' => [
		'manage_options',
		'ngc_ai_ops',
		'ngc_manage_matches',
	],
	'notes'   => 'Management controls require an administrator or AI operations capability; approvals additionally permit authorized match managers.',
];
