<?php
/**
 * Staging/prod SMTP from environment — never commit FluentSMTP UI secrets.
 * Set SMTP_HOST in docker/.env (empty = leave wp_mail / FluentSMTP alone).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		$host = getenv( 'SMTP_HOST' );
		if ( ! is_string( $host ) || '' === $host ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host = $host;
		$phpmailer->Port = (int) ( getenv( 'SMTP_PORT' ) ?: 587 );
		$user            = getenv( 'SMTP_USER' );
		$pass            = getenv( 'SMTP_PASS' );
		if ( is_string( $user ) && '' !== $user ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $user;
			$phpmailer->Password = is_string( $pass ) ? $pass : '';
		}
		$secure = getenv( 'SMTP_SECURE' );
		if ( is_string( $secure ) && '' !== $secure ) {
			$phpmailer->SMTPSecure = $secure;
		}
		$from = getenv( 'SMTP_FROM' );
		if ( is_string( $from ) && is_email( $from ) ) {
			$name = getenv( 'SMTP_FROM_NAME' );
			$phpmailer->setFrom( $from, is_string( $name ) && '' !== $name ? $name : 'NextGen Tutors' );
		}
	}
);
