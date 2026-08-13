<?php
/**
 * NGT Session Classroom — MasterStudy Course Player shell + live meeting hop.
 *
 * Target flow: JOIN → authorize → Course Player (learning) → Live Meeting (realtime).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end classroom surface for orchestrated sessions.
 */
class NGC_Session_Classroom {

	public const QUERY_VAR = 'ngt_classroom';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'query_vars', [ __CLASS__, 'query_vars' ] );
		add_action( 'template_redirect', [ __CLASS__, 'maybe_render' ], 1 );
		add_shortcode( 'ngt_session_classroom', [ __CLASS__, 'shortcode' ] );
	}

	/**
	 * @param array<int, string> $vars Vars.
	 * @return array<int, string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Public classroom URL for a session (auth enforced on render).
	 *
	 * @param int $session_id Session ID.
	 * @return string
	 */
	public static function url( $session_id ) {
		$session_id = (int) $session_id;
		if ( $session_id <= 0 ) {
			return '';
		}
		return add_query_arg( self::QUERY_VAR, $session_id, home_url( '/' ) );
	}

	/**
	 * Render classroom when ?ngt_classroom={id} present.
	 */
	public static function maybe_render() {
		$id = (int) get_query_var( self::QUERY_VAR );
		if ( $id <= 0 && isset( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = (int) $_GET[ self::QUERY_VAR ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( $id <= 0 ) {
			return;
		}
		status_header( 200 );
		nocache_headers();
		echo self::render_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		exit;
	}

	/**
	 * Shortcode: [ngt_session_classroom id="123"]
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'ngt_session_classroom' );
		return self::render_html( (int) $atts['id'] );
	}

	/**
	 * @param int $session_id Session.
	 * @return string
	 */
	public static function render_html( $session_id ) {
		$session_id = (int) $session_id;
		if ( ! is_user_logged_in() ) {
			$login = wp_login_url( self::url( $session_id ) );
			return '<div class="ngt-classroom ngt-classroom--denied"><p>' .
				esc_html__( 'Please sign in to enter this lesson.', 'nextgencompanion' ) .
				'</p><p><a class="ngt-btn ngt-btn--primary" href="' . esc_url( $login ) . '">' .
				esc_html__( 'Log in', 'nextgencompanion' ) . '</a></p></div>';
		}

		if ( ! class_exists( 'NGC_Session_Orchestrator' ) || ! class_exists( 'NGC_Sessions' ) ) {
			return '<div class="ngt-classroom"><p>' . esc_html__( 'Session module unavailable.', 'nextgencompanion' ) . '</p></div>';
		}

		$auth = NGC_Session_Orchestrator::authorize_launch( $session_id, get_current_user_id() );
		if ( is_wp_error( $auth ) ) {
			$code = (int) ( $auth->get_error_data()['status'] ?? 403 );
			status_header( $code > 0 ? $code : 403 );
			return '<div class="ngt-classroom ngt-classroom--denied" data-code="' . esc_attr( $auth->get_error_code() ) . '"><p>' .
				esc_html( $auth->get_error_message() ) . '</p></div>';
		}

		$player  = (string) ( $auth['player_url'] ?? '' );
		$meeting = (string) ( $auth['meeting_url'] ?? '' );
		$corr    = (string) ( $auth['correlation_id'] ?? '' );
		$role    = (string) ( $auth['role'] ?? 'student' );

		$title = __( 'NextGen Tutors — Lesson classroom', 'nextgencompanion' );
		ob_start();
		?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		:root { --ngt-ink:#14213d; --ngt-accent:#1b6b4a; --ngt-bg:#f4f7f5; }
		body { margin:0; font-family: Georgia, "Times New Roman", serif; background:linear-gradient(160deg,#e8f0ea,#f7f3ea 55%,#eef2f7); color:var(--ngt-ink); }
		.ngt-classroom { max-width:1100px; margin:0 auto; padding:1.25rem; }
		.ngt-classroom__brand { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight:700; letter-spacing:-0.02em; margin:0 0 .35rem; }
		.ngt-classroom__meta { opacity:.8; margin:0 0 1rem; font-size:.95rem; }
		.ngt-classroom__grid { display:grid; gap:1rem; }
		@media (min-width:900px){ .ngt-classroom__grid { grid-template-columns: 1.4fr .8fr; } }
		.ngt-classroom__panel { background:rgba(255,255,255,.72); border:1px solid rgba(20,33,61,.08); padding:1rem; }
		.ngt-classroom__panel h2 { margin:0 0 .75rem; font-size:1.1rem; }
		.ngt-classroom__frame { width:100%; min-height:62vh; border:0; background:#fff; }
		.ngt-btn { display:inline-block; padding:.7rem 1.1rem; background:var(--ngt-accent); color:#fff; text-decoration:none; border:0; cursor:pointer; font:inherit; }
		.ngt-btn--ghost { background:transparent; color:var(--ngt-ink); border:1px solid rgba(20,33,61,.25); }
		.ngt-classroom__actions { display:flex; flex-wrap:wrap; gap:.6rem; margin-top:.75rem; }
		.ngt-classroom__hint { font-size:.9rem; opacity:.85; }
	</style>
</head>
<body class="ngt-classroom-body">
	<main class="ngt-classroom" data-session-id="<?php echo esc_attr( (string) $session_id ); ?>" data-role="<?php echo esc_attr( $role ); ?>" data-correlation="<?php echo esc_attr( $corr ); ?>">
		<p class="ngt-classroom__brand"><?php echo esc_html( get_bloginfo( 'name' ) ?: 'NextGen Tutors' ); ?></p>
		<p class="ngt-classroom__meta"><?php echo esc_html( sprintf( /* translators: 1: role 2: correlation */ __( 'Role: %1$s · Session %2$s', 'nextgencompanion' ), $role, $corr ?: (string) $session_id ) ); ?></p>
		<div class="ngt-classroom__grid">
			<section class="ngt-classroom__panel" aria-label="<?php esc_attr_e( 'Course player', 'nextgencompanion' ); ?>">
				<h2><?php esc_html_e( 'Learning — Course Player', 'nextgencompanion' ); ?></h2>
				<?php if ( $player ) : ?>
					<iframe class="ngt-classroom__frame" title="<?php esc_attr_e( 'MasterStudy course player', 'nextgencompanion' ); ?>" src="<?php echo esc_url( $player ); ?>" allow="camera; microphone; display-capture; autoplay; fullscreen"></iframe>
					<p class="ngt-classroom__hint"><?php esc_html_e( 'Materials and lesson context load here first.', 'nextgencompanion' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'Course player is not linked for this session yet. You can still enter the live meeting.', 'nextgencompanion' ); ?></p>
				<?php endif; ?>
			</section>
			<section class="ngt-classroom__panel" aria-label="<?php esc_attr_e( 'Live meeting', 'nextgencompanion' ); ?>">
				<h2><?php esc_html_e( 'Live — Audio & video', 'nextgencompanion' ); ?></h2>
				<p class="ngt-classroom__hint"><?php esc_html_e( 'After reviewing materials, enter the live lesson with your tutor or student.', 'nextgencompanion' ); ?></p>
				<div class="ngt-classroom__actions">
					<?php if ( $meeting ) : ?>
						<a class="ngt-btn bi-dash-join-live" id="ngt-enter-live-meeting" href="<?php echo esc_url( $meeting ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Enter live meeting', 'nextgencompanion' ); ?></a>
					<?php else : ?>
						<p><?php esc_html_e( 'Live meeting is not ready for this session.', 'nextgencompanion' ); ?></p>
					<?php endif; ?>
					<?php if ( $player ) : ?>
						<a class="ngt-btn ngt-btn--ghost" href="<?php echo esc_url( $player ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open course in new tab', 'nextgencompanion' ); ?></a>
					<?php endif; ?>
				</div>
			</section>
		</div>
	</main>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}
}
