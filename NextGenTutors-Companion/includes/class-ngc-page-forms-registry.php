<?php
/**
 * Page ↔ form ↔ shortcode registry with verification and safe repair.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical mapping between launch pages and required ngc_* shortcodes.
 */
class NGC_Page_Forms_Registry {

	const OPTION_REPORT = 'ngc_page_forms_registry_report';
	const INJECT_MARKER = '<!-- ngc-registry:';

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Loaded via bootstrap; admin/REST register separately.
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		$defs = [];

		if ( function_exists( 'bi_pages_registry' ) ) {
			foreach ( bi_pages_registry() as $slug => $meta ) {
				$shortcodes = (array) ( $meta['shortcodes'] ?? [] );
				if ( empty( $shortcodes ) ) {
					continue;
				}
				$defs[ $slug ] = [
					'slug'       => $slug,
					'title'      => self::title_for_slug( $slug ),
					'shortcodes' => $shortcodes,
					'forms'      => self::forms_for_shortcodes( $shortcodes ),
					'type'       => (string) ( $meta['type'] ?? 'public' ),
					'template'   => (string) ( $meta['template'] ?? '' ),
				];
			}
		} else {
			$defs = self::fallback_definitions();
		}

		/**
		 * Filter page/form registry definitions.
		 *
		 * @param array<string, array<string, mixed>> $defs Definitions.
		 */
		return apply_filters( 'ngc_page_forms_registry_definitions', $defs );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function fallback_definitions() {
		$map = [
			'find-a-tutor'      => [ 'ngc_find_tutor_form' ],
			'become-a-tutor'    => [ 'ngc_become_tutor_form' ],
			'register'          => [ 'ngc_parent_register_child_form', 'ngc_student_register_form' ],
			'login'             => [ 'ngc_login_form', 'ngc_forgot_password_form' ],
			'contact'           => [ 'ngc_contact_support_form' ],
			'support'           => [ 'ngc_contact_support_form' ],
			'parent-dashboard'  => [ 'ngc_parent_dashboard' ],
			'student-dashboard' => [ 'ngc_student_dashboard' ],
			'tutor-dashboard'   => [ 'ngc_tutor_dashboard' ],
			'admin-dashboard'   => [ 'ngc_admin_dashboard' ],
		];
		$defs = [];
		foreach ( $map as $slug => $shortcodes ) {
			$defs[ $slug ] = [
				'slug'       => $slug,
				'title'      => self::title_for_slug( $slug ),
				'shortcodes' => $shortcodes,
				'forms'      => self::forms_for_shortcodes( $shortcodes ),
				'type'       => 'public',
				'template'   => '',
			];
		}
		return $defs;
	}

	/**
	 * @param string $slug Page slug.
	 * @return string
	 */
	private static function title_for_slug( $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			return (string) $page->post_title;
		}
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * @param string[] $shortcodes Shortcode tags.
	 * @return string[]
	 */
	private static function forms_for_shortcodes( $shortcodes ) {
		$form_map = [
			'ngc_find_tutor_form'            => 'find_tutor',
			'ngc_become_tutor_form'          => 'become_tutor',
			'ngc_contact_support_form'       => 'contact_support',
			'ngc_parent_register_child_form' => 'parent_register',
			'ngc_student_register_form'      => 'student_register',
			'ngc_login_form'                 => 'login',
			'ngc_forgot_password_form'       => 'forgot_password',
		];
		$forms = [];
		foreach ( $shortcodes as $tag ) {
			if ( isset( $form_map[ $tag ] ) ) {
				$forms[] = $form_map[ $tag ];
			}
		}
		return $forms;
	}

	/**
	 * Force theme production defaults + inject shortcodes on intake pages.
	 *
	 * @param bool $force_repair Append shortcodes even when post_content exists.
	 * @return array<string, mixed>
	 */
	public static function ensure_production_forms( $force_repair = true ) {
		$results = [];

		foreach ( self::definitions() as $slug => $def ) {
			if ( empty( $def['forms'] ) ) {
				continue;
			}

			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( ! $page ) {
				$results[ $slug ] = [ 'ok' => false, 'error' => 'page_missing' ];
				continue;
			}

			$meta = get_post_meta( $page->ID, 'bi_options', true );
			if ( ! is_array( $meta ) ) {
				$meta = [];
			}
			$meta['force_theme_default'] = 1;
			update_post_meta( $page->ID, 'bi_options', $meta );

			delete_post_meta( $page->ID, '_elementor_edit_mode' );
			delete_post_meta( $page->ID, '_elementor_data' );
			delete_post_meta( $page->ID, '_elementor_version' );

			$results[ $slug ] = [ 'ok' => true, 'page_id' => (int) $page->ID, 'forced_theme_default' => true ];
		}

		$repair = self::repair( '', $force_repair );
		$results['_repair'] = $repair;

		return $results;
	}

	/**
	 * Run full verification and persist report.
	 *
	 * @return array<string, mixed>
	 */
	public static function verify() {
		$items   = [];
		$summary = [ 'pass' => 0, 'warning' => 0, 'fail' => 0 ];

		foreach ( self::definitions() as $slug => $def ) {
			$item = self::verify_slug( $slug, $def );
			$items[ $slug ] = $item;
			++$summary[ strtolower( $item['status'] ) ];
		}

		$menu = self::verify_primary_menu();
		$items['_primary_menu'] = $menu;
		if ( 'PASS' !== $menu['status'] ) {
			++$summary[ strtolower( $menu['status'] ) ];
		} else {
			++$summary['pass'];
		}

		$report = [
			'verified_at' => gmdate( 'c' ),
			'summary'     => $summary,
			'items'       => $items,
			'ok'          => 0 === $summary['fail'],
		];

		update_option( self::OPTION_REPORT, $report, false );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'registry_verified',
				'system',
				0,
				[ 'summary' => $summary ],
				get_current_user_id(),
				[ 'workflow_key' => 'page_forms_registry' ]
			);
		}

		return $report;
	}

	/**
	 * @param string               $slug Page slug.
	 * @param array<string, mixed> $def  Definition.
	 * @return array<string, mixed>
	 */
	public static function verify_slug( $slug, $def = null ) {
		$def       = $def ?: ( self::definitions()[ $slug ] ?? [] );
		$shortcodes = (array) ( $def['shortcodes'] ?? [] );
		$page      = get_page_by_path( $slug, OBJECT, 'page' );
		$checks    = [];

		if ( ! $page ) {
			return [
				'slug'       => $slug,
				'title'      => (string) ( $def['title'] ?? $slug ),
				'status'     => 'FAIL',
				'page_id'    => 0,
				'message'    => __( 'Page does not exist.', 'nextgencompanion' ),
				'shortcodes' => [],
			];
		}

		$content = (string) $page->post_content;
		$sc_rows = [];
		$has_fail = false;
		$has_warn = false;

		foreach ( $shortcodes as $tag ) {
			$registered = shortcode_exists( $tag );
			$on_page    = self::content_has_shortcode( $content, $tag );
			$theme_ok   = self::theme_fallback_has_shortcode( $page, $tag );
			$present    = $on_page || $theme_ok;

			if ( ! $registered ) {
				$status = 'FAIL';
				$has_fail = true;
			} elseif ( ! $present ) {
				$status = 'WARNING';
				$has_warn = true;
			} else {
				$status = 'PASS';
			}

			$sc_rows[] = [
				'tag'        => $tag,
				'registered' => $registered,
				'on_page'    => $on_page,
				'theme_ok'   => $theme_ok,
				'status'     => $status,
			];
		}

		$status = $has_fail ? 'FAIL' : ( $has_warn ? 'WARNING' : 'PASS' );

		return [
			'slug'       => $slug,
			'title'      => (string) $page->post_title,
			'status'     => $status,
			'page_id'    => (int) $page->ID,
			'message'    => self::status_message( $status, $sc_rows ),
			'shortcodes' => $sc_rows,
		];
	}

	/**
	 * @param string $content   Post content.
	 * @param string $shortcode Tag.
	 * @return bool
	 */
	private static function content_has_shortcode( $content, $shortcode ) {
		if ( has_shortcode( $content, $shortcode ) ) {
			return true;
		}
		$marker = self::INJECT_MARKER . $shortcode . ' -->';
		return false !== strpos( $content, $marker );
	}

	/**
	 * Theme default templates render shortcodes without storing them in post_content.
	 *
	 * @param WP_Post $page      Page.
	 * @param string  $shortcode Tag.
	 * @return bool
	 */
	private static function theme_fallback_has_shortcode( $page, $shortcode ) {
		if ( ! function_exists( 'bi_should_show_theme_fallback' ) || ! function_exists( 'bi_pages_registry' ) ) {
			return false;
		}
		if ( ! bi_should_show_theme_fallback( $page->ID ) ) {
			return false;
		}
		$slug = (string) $page->post_name;
		$reg  = bi_pages_registry();
		if ( empty( $reg[ $slug ]['shortcodes'] ) ) {
			return false;
		}
		return in_array( $shortcode, (array) $reg[ $slug ]['shortcodes'], true );
	}

	/**
	 * @param string              $status Status.
	 * @param array<int, mixed>   $rows   Shortcode rows.
	 * @return string
	 */
	private static function status_message( $status, $rows ) {
		if ( 'PASS' === $status ) {
			return __( 'Page and shortcodes OK.', 'nextgencompanion' );
		}
		$missing = [];
		foreach ( $rows as $row ) {
			if ( 'PASS' !== ( $row['status'] ?? '' ) ) {
				$missing[] = (string) ( $row['tag'] ?? '' );
			}
		}
		return sprintf(
			/* translators: %s: comma-separated shortcode list */
			__( 'Issues with: %s', 'nextgencompanion' ),
			implode( ', ', $missing )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function verify_primary_menu() {
		$locs    = get_nav_menu_locations();
		$menu_id = (int) ( $locs['primary'] ?? 0 );
		if ( ! $menu_id ) {
			return [
				'slug'    => '_primary_menu',
				'title'   => __( 'Primary menu', 'nextgencompanion' ),
				'status'  => 'WARNING',
				'message' => __( 'Primary menu location is not assigned.', 'nextgencompanion' ),
			];
		}
		return [
			'slug'    => '_primary_menu',
			'title'   => __( 'Primary menu', 'nextgencompanion' ),
			'status'  => 'PASS',
			'message' => __( 'Primary menu assigned.', 'nextgencompanion' ),
			'menu_id' => $menu_id,
		];
	}

	/**
	 * Safe repair for one slug or all.
	 *
	 * @param string $slug  Page slug or empty for all.
	 * @param bool   $force Overwrite content when shortcodes missing (still never deletes).
	 * @return array<string, mixed>
	 */
	public static function repair( $slug = '', $force = false ) {
		$defs    = self::definitions();
		$targets = $slug ? [ $slug => ( $defs[ $slug ] ?? null ) ] : $defs;
		$results = [];

		foreach ( $targets as $key => $def ) {
			if ( null === $def ) {
				$results[ $key ] = [ 'ok' => false, 'error' => __( 'Unknown slug.', 'nextgencompanion' ) ];
				continue;
			}
			$results[ $key ] = self::repair_slug( $key, $def, $force );
		}

		if ( function_exists( 'bi_sync_grouped_primary_menu' ) ) {
			bi_sync_grouped_primary_menu();
			$results['_primary_menu'] = [ 'ok' => true, 'action' => 'menu_synced' ];
		}

		flush_rewrite_rules( false );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'registry_repair',
				'system',
				0,
				[ 'slug' => $slug ?: 'all', 'results' => $results ],
				get_current_user_id(),
				[ 'workflow_key' => 'page_forms_registry' ]
			);
		}

		self::verify();

		return $results;
	}

	/**
	 * @param string               $slug  Slug.
	 * @param array<string, mixed> $def   Definition.
	 * @param bool                 $force Force inject into content.
	 * @return array<string, mixed>
	 */
	private static function repair_slug( $slug, $def, $force ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		$created = false;

		if ( ! $page ) {
			$page_id = wp_insert_post(
				[
					'post_title'  => (string) ( $def['title'] ?? $slug ),
					'post_name'   => $slug,
					'post_status' => 'publish',
					'post_type'   => 'page',
					'post_content'=> '',
				],
				true
			);
			if ( is_wp_error( $page_id ) ) {
				return [ 'ok' => false, 'error' => $page_id->get_error_message() ];
			}
			$page    = get_post( $page_id );
			$created = true;
		}

		if ( ! empty( $def['template'] ) && 'default' !== $def['template'] ) {
			update_post_meta( $page->ID, '_wp_page_template', $def['template'] );
		}

		$injected = [];
		$content  = (string) $page->post_content;
		$changed  = false;

		foreach ( (array) ( $def['shortcodes'] ?? [] ) as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				continue;
			}
			if ( self::content_has_shortcode( $content, $tag ) || self::theme_fallback_has_shortcode( $page, $tag ) ) {
				continue;
			}
			$block = "\n\n" . self::INJECT_MARKER . $tag . " -->\n[" . $tag . "]\n";
			if ( false === strpos( $content, $block ) ) {
				$content .= $block;
				$injected[] = $tag;
				$changed    = true;
			}
		}

		if ( $changed ) {
			wp_update_post(
				[
					'ID'           => $page->ID,
					'post_content' => $content,
				]
			);
		}

		return [
			'ok'       => true,
			'page_id'  => (int) $page->ID,
			'created'  => $created,
			'injected' => $injected,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function last_report() {
		$report = get_option( self::OPTION_REPORT, [] );
		return is_array( $report ) ? $report : [];
	}
}
