<?php
/**
 * Payload minimization and redaction rules.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blocked = [
	'password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey',
	'authorization', 'cookie', 'session', 'salt', 'private_key', 'bank',
	'iban', 'account_number', 'card', 'cvv', 'sa_id', 'id_number',
	'national_id', 'passport', 'address', 'identity_document',
	'birth_certificate', 'guardian_phone',
];

return [
	'profiles'         => [
		'default'      => [
			'blocked_key_patterns' => $blocked,
			'minimized_keys'       => [ 'email', 'phone', 'mobile', 'first_name', 'last_name', 'full_name' ],
		],
		'minor'        => [
			'blocked_key_patterns' => $blocked,
			'minimized_keys'       => [ 'email', 'phone', 'mobile', 'first_name', 'last_name', 'full_name' ],
			'learner_identifier_keys' => [ 'learner_id', 'student_id', 'child_id', 'child_name', 'learner', 'student', 'child' ],
			'never_send'           => [ 'sa_id', 'id_number', 'address', 'guardian_phone', 'guardian_mobile' ],
		],
		'finance'      => [
			'blocked_key_patterns' => $blocked,
			'minimized_keys'       => [ 'email', 'phone', 'mobile', 'first_name', 'last_name', 'full_name' ],
		],
		'safeguarding' => [
			'blocked_key_patterns' => $blocked,
			'minimized_keys'       => [ 'email', 'phone', 'mobile', 'first_name', 'last_name', 'full_name' ],
		],
	],
	'event_allowlists' => [
		'match.requested' => [
			'candidates' => [ 'user_id', 'tutor_id', 'display_name', 'subjects', 'grade', 'province', 'score', 'verified', 'eligible' ],
		],
	],
];
