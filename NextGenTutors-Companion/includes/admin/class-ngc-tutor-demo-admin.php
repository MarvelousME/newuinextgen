<?php
/**
 * Demo tutor admin actions and list badges.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UX for demo vs real tutors.
 */
class NGC_Tutor_Demo_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'manage_tutors_posts_columns', [ __CLASS__, 'columns' ] );
		add_action( 'manage_tutors_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
		add_filter( 'post_row_actions', [ __CLASS__, 'row_actions' ], 10, 2 );
		add_action( 'admin_post_ngc_clear_demo_tutors', [ __CLASS__, 'clear_demo_tutors' ] );
		add_action( 'admin_post_ngc_convert_demo_tutor', [ __CLASS__, 'convert_demo_tutor' ] );
		add_action( 'admin_notices', [ __CLASS__, 'action_notices' ] );
		add_action( 'admin_notices', [ __CLASS__, 'render_list_tools' ] );
	}

	/**
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public static function columns( $columns ) {
		$columns['ngc_demo_badge'] = __( 'Data source', 'nextgencompanion' );
		return $columns;
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'ngc_demo_badge' !== $column ) {
			return;
		}
		$is_demo = class_exists( 'NGC_Tutor_Cpt_Source' ) && NGC_Tutor_Cpt_Source::is_demo_tutor( $post_id );
		if ( $is_demo ) {
			echo '<span class="ngc-badge ngc-badge--demo" style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700">' . esc_html__( 'DEMO SEED', 'nextgencompanion' ) . '</span>';
		} else {
			echo '<span class="ngc-badge ngc-badge--real" style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700">' . esc_html__( 'REAL', 'nextgencompanion' ) . '</span>';
		}
	}

	/**
	 * @param string[] $actions Row actions.
	 * @param WP_Post  $post    Post.
	 * @return string[]
	 */
	public static function row_actions( $actions, $post ) {
		if ( 'tutors' !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}
		if ( class_exists( 'NGC_Tutor_Cpt_Source' ) && NGC_Tutor_Cpt_Source::is_demo_tutor( $post->ID ) ) {
			$url = wp_nonce_url(
				admin_url( 'admin-post.php?action=ngc_convert_demo_tutor&post_id=' . (int) $post->ID ),
				'ngc_convert_demo_' . (int) $post->ID
			);
			$actions['ngc_convert'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Convert to real tutor', 'nextgencompanion' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Bulk delete demo tutors.
	 */
	public static function clear_demo_tutors() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_clear_demo_tutors' );

		$posts = get_posts(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => 'ngc_demo_seed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
			]
		);

		$deleted = 0;
		foreach ( $posts as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				++$deleted;
			}
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'demo_tutors_cleared', 'tutors', 0, [ 'deleted' => $deleted ] );
		}

		wp_safe_redirect( add_query_arg( 'ngc_demo_cleared', $deleted, admin_url( 'edit.php?post_type=tutors' ) ) );
		exit;
	}

	/**
	 * Convert single demo tutor to real.
	 */
	public static function convert_demo_tutor() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$post_id = (int) ( $_GET['post_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'ngc_convert_demo_' . $post_id );

		update_post_meta( $post_id, 'ngc_demo_seed', 0 );
		delete_post_meta( $post_id, 'ngc_demo_seed' );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'demo_tutor_converted', 'tutors', $post_id, [] );
		}

		wp_safe_redirect( add_query_arg( 'ngc_demo_converted', 1, get_edit_post_link( $post_id, 'raw' ) ) );
		exit;
	}

	/**
	 * Admin notices after demo actions.
	 */
	public static function action_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ngc_demo_cleared'] ) ) {
			$n = (int) $_GET['ngc_demo_cleared'];
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Removed %d demo tutor(s).', 'nextgencompanion' ), $n ) ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ngc_demo_converted'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Tutor marked as real (demo flag removed).', 'nextgencompanion' ) . '</p></div>';
		}
	}

	/**
	 * Render clear-demo button on tutors list screen.
	 */
	public static function render_list_tools() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-tutors' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$demo_count = class_exists( 'NGC_Tutor_Cpt_Source' ) ? NGC_Tutor_Cpt_Source::count_demo() : 0;
		if ( $demo_count < 1 ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=ngc_clear_demo_tutors' ), 'ngc_clear_demo_tutors' );
		echo '<div class="notice notice-warning" style="margin-top:12px"><p>';
		echo esc_html( sprintf( __( '%d demo-seeded tutors are included in marketplace counts.', 'nextgencompanion' ), $demo_count ) );
		echo ' <a class="button" href="' . esc_url( $url ) . '" onclick="return confirm(\'' . esc_js( __( 'Permanently delete all demo tutors?', 'nextgencompanion' ) ) . '\');">' . esc_html__( 'Clear demo tutors', 'nextgencompanion' ) . '</a>';
		echo '</p></div>';
	}
}
