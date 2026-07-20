<?php
/**
 * Homepage section CMS — 11 editable blocks for BeyondInfinity kinetic home.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and serves homepage section content from ngc_page_sections.
 */
class NGC_Section_CMS {

	/** @var string */
	const PAGE_HOME = 'home';

	/**
	 * Canonical Phase 1 section keys.
	 *
	 * @return string[]
	 */
	public static function section_keys() {
		return [
			'hero',
			'trust_bar',
			'how_it_works',
			'learning_modes',
			'subject_explorer',
			'featured_tutors',
			'success_stories',
			'trust_safety',
			'pricing',
			'faq',
			'cta',
		];
	}

	/**
	 * Theme option keys managed by CMS (pricing rates).
	 *
	 * @return string[]
	 */
	public static function theme_option_keys() {
		return [ 'rate_online', 'rate_inperson', 'rate_tertiary' ];
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_admin' ] );
		add_filter( 'bi_theme_option', [ __CLASS__, 'filter_theme_option' ], 20, 3 );
		add_filter( 'bi_filter_home_sections', [ __CLASS__, 'filter_home_sections' ], 20, 1 );
	}

	/**
	 * Admin submenu.
	 */
	public static function register_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page(
			'ngc-operations',
			__( 'Homepage Sections', 'nextgencompanion' ),
			__( 'Home Sections', 'nextgencompanion' ),
			'manage_options',
			'ngc-home-sections',
			[ __CLASS__, 'render_admin' ]
		);
	}

	/** Marker value when a kinetic section is disabled via CMS. */
	const CMS_DISABLED_MARKER = '__cms_disabled__';

	/**
	 * CMS section key → theme kinetic section id.
	 *
	 * @return array<string, string>
	 */
	public static function cms_theme_section_map() {
		return [
			'trust_bar'        => 'trust',
			'subject_explorer' => 'subjects',
			'how_it_works'     => 'journey',
			'learning_modes'   => 'pathways',
			'featured_tutors'  => 'tutors',
			'success_stories'  => 'reviews',
			'trust_safety'     => 'proof',
			'pricing'          => 'pricing',
			'faq'              => 'faq',
		];
	}

	/**
	 * CMS section key → theme option that toggles visibility.
	 *
	 * @return array<string, string>
	 */
	public static function theme_section_option_map() {
		return [
			'trust_bar'        => 'home_section_trust',
			'subject_explorer' => 'home_section_subjects',
			'how_it_works'     => 'home_section_journey',
			'learning_modes'   => 'home_section_pathways',
			'featured_tutors'  => 'home_section_tutors',
			'success_stories'  => 'home_section_reviews',
			'trust_safety'     => 'home_section_proof',
			'pricing'          => 'home_section_pricing',
			'faq'              => 'home_section_faq',
		];
	}

	/**
	 * @param string $section_key CMS section key.
	 * @return bool
	 */
	public static function section_enabled( $section_key ) {
		$row = self::get_section_row( self::PAGE_HOME, sanitize_key( $section_key ) );
		return ! $row || ! empty( $row['is_enabled'] );
	}

	/**
	 * @param mixed  $rez     Current value.
	 * @param string $name    Option name.
	 * @param int    $post_id Post ID.
	 * @return mixed
	 */
	public static function filter_theme_option( $rez, $name, $post_id ) {
		if ( $post_id > 0 ) {
			return $rez;
		}

		if ( in_array( $name, self::theme_option_keys(), true ) ) {
			$pricing = self::get_section( self::PAGE_HOME, 'pricing' );
			if ( ! empty( $pricing['rates'][ $name ] ) ) {
				return $pricing['rates'][ $name ];
			}
		}

		foreach ( self::theme_section_option_map() as $cms_key => $option_name ) {
			if ( $name === $option_name && ! self::section_enabled( $cms_key ) ) {
				return 0;
			}
		}

		return $rez;
	}

	/**
	 * @param array<string, mixed> $sections Theme section registry.
	 * @return array<string, mixed>
	 */
	public static function filter_home_sections( $sections ) {
		if ( ! is_array( $sections ) ) {
			$sections = [];
		}

		foreach ( self::cms_theme_section_map() as $cms_key => $theme_id ) {
			if ( ! self::section_enabled( $cms_key ) ) {
				$sections[ $theme_id ] = self::CMS_DISABLED_MARKER;
			}
		}

		foreach ( [ 'hero', 'cta' ] as $cms_only ) {
			if ( ! self::section_enabled( $cms_only ) ) {
				$sections[ $cms_only ] = self::CMS_DISABLED_MARKER;
			}
		}

		return $sections;
	}

	/**
	 * @param string $page_key    Page key.
	 * @param string $section_key Section key.
	 * @return array<string, mixed>
	 */
	public static function get_section( $page_key, $section_key ) {
		$row = self::get_section_row( $page_key, $section_key );
		if ( ! $row ) {
			return self::default_content( $section_key );
		}
		$data = json_decode( (string) ( $row['content_json'] ?? '' ), true );
		return is_array( $data ) ? array_merge( self::default_content( $section_key ), $data ) : self::default_content( $section_key );
	}

	/**
	 * @param string $section_key Section key.
	 * @param string $field       Dot-notated field (optional).
	 * @param mixed  $default     Fallback.
	 * @return mixed
	 */
	public static function home_field( $section_key, $field = '', $default = '' ) {
		$data = self::get_section( self::PAGE_HOME, $section_key );
		if ( '' === $field ) {
			return $data;
		}
		if ( isset( $data[ $field ] ) ) {
			return $data[ $field ];
		}
		return $default;
	}

	/**
	 * @param string $page_key    Page key.
	 * @param string $section_key Section key.
	 * @return array<string, mixed>|null
	 */
	public static function get_section_row( $page_key, $section_key ) {
		global $wpdb;
		$table = NGC_Database::table( 'page_sections' );
		if ( ! $table ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE page_key = %s AND section_key = %s LIMIT 1",
				sanitize_key( $page_key ),
				sanitize_key( $section_key )
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * @param string               $page_key    Page key.
	 * @param string               $section_key Section key.
	 * @param array<string, mixed> $content     Content payload.
	 * @param bool                 $enabled     Enabled flag.
	 * @return int|WP_Error
	 */
	public static function save_section( $page_key, $section_key, $content, $enabled = true ) {
		global $wpdb;
		$table = NGC_Database::table( 'page_sections' );
		$now   = current_time( 'mysql', true );
		$row   = self::get_section_row( $page_key, $section_key );

		$payload = [
			'content_json' => wp_json_encode( $content ),
			'is_enabled'   => $enabled ? 1 : 0,
			'updated_at'   => $now,
		];

		if ( $row ) {
			$wpdb->update(
				$table,
				$payload,
				[ 'id' => (int) $row['id'] ],
				[ '%s', '%d', '%s' ],
				[ '%d' ]
			);
			return (int) $row['id'];
		}

		$sort = array_search( $section_key, self::section_keys(), true );
		$wpdb->insert(
			$table,
			[
				'uuid'         => NGC_Uuid::generate(),
				'page_key'     => sanitize_key( $page_key ),
				'section_key'  => sanitize_key( $section_key ),
				'title'        => sanitize_text_field( (string) ( $content['title'] ?? ucwords( str_replace( '_', ' ', $section_key ) ) ) ),
				'content_json' => $payload['content_json'],
				'is_enabled'   => $payload['is_enabled'],
				'sort_order'   => false !== $sort ? (int) $sort : 0,
				'created_at'   => $now,
				'updated_at'   => $now,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Seed defaults for all homepage sections.
	 */
	public static function install_defaults() {
		global $wpdb;
		$table = NGC_Database::table( 'page_sections' );
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM {$table} WHERE page_key = 'home' AND ( section_key = '' OR section_key = '0' )" );
		}
		foreach ( self::section_keys() as $index => $key ) {
			if ( self::get_section_row( self::PAGE_HOME, $key ) ) {
				continue;
			}
			self::save_section( self::PAGE_HOME, $key, self::default_content( $key ), true );
		}
	}

	/**
	 * @param string $section_key Section key.
	 * @return array<string, mixed>
	 */
	public static function default_content( $section_key ) {
		$defaults = [
			'hero' => [
				'badge'       => __( 'Premium online, in-person and hybrid tutoring', 'nextgencompanion' ),
				'title'       => __( 'Your Tutor. Your Pace.', 'nextgencompanion' ),
				'title_accent'=> __( 'Your Results.', 'nextgencompanion' ),
				'lead'        => __( 'Connect with background-checked tutors for CAPS, IEB and Cambridge — online or in-person, from Grade 1 to varsity.', 'nextgencompanion' ),
				'cta_primary' => __( 'Find My Tutor', 'nextgencompanion' ),
				'cta_secondary'=> __( 'Become a Tutor', 'nextgencompanion' ),
			],
			'trust_bar' => [
				'eyebrow'  => __( 'Trusted learning ecosystem', 'nextgencompanion' ),
				'heading'  => __( 'Everything for confident learners.', 'nextgencompanion' ),
				'subtitle' => __( 'Registration, tutor matching, booking, CRM follow-up, payment status, dashboards and verification — built for South African families.', 'nextgencompanion' ),
				'items'    => [
					__( 'Background-checked tutors', 'nextgencompanion' ),
					__( 'CAPS, IEB & Cambridge', 'nextgencompanion' ),
					__( 'Parent progress dashboards', 'nextgencompanion' ),
				],
			],
			'how_it_works' => [
				'eyebrow' => __( 'Learner journey', 'nextgencompanion' ),
				'title'   => __( 'A clear path from assessment to measurable improvement.', 'nextgencompanion' ),
				'steps'   => [
					[ 'title' => __( 'Assessment', 'nextgencompanion' ), 'copy' => __( 'Identify gaps.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Tutor Match', 'nextgencompanion' ), 'copy' => __( 'Assign fit.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Learning Plan', 'nextgencompanion' ), 'copy' => __( 'Set goals.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Weekly Lessons', 'nextgencompanion' ), 'copy' => __( 'Track work.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Reports', 'nextgencompanion' ), 'copy' => __( 'Show progress.', 'nextgencompanion' ) ],
				],
			],
			'learning_modes' => [
				'eyebrow' => __( 'Learning pathways', 'nextgencompanion' ),
				'title'   => __( 'Interactive discovery for every role.', 'nextgencompanion' ),
				'modes'   => [
					[ 'title' => __( 'Parent Journey', 'nextgencompanion' ), 'copy' => __( 'Book assessment, match tutor, track progress and manage payments.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Student Journey', 'nextgencompanion' ), 'copy' => __( 'View lessons, subjects, achievements and personal progress.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Tutor Journey', 'nextgencompanion' ), 'copy' => __( 'Manage bookings, learners, availability, reviews and earnings.', 'nextgencompanion' ) ],
					[ 'title' => __( 'Admin Journey', 'nextgencompanion' ), 'copy' => __( 'Monitor CRM, workflows, bookings and platform health.', 'nextgencompanion' ) ],
				],
			],
			'subject_explorer' => [
				'eyebrow' => __( 'Subject explorer', 'nextgencompanion' ),
				'title'   => __( 'Click a subject and watch the learning plan adapt.', 'nextgencompanion' ),
				'subtitle'=> __( 'Every track is mapped to CAPS, IEB and Cambridge outcomes.', 'nextgencompanion' ),
			],
			'featured_tutors' => [
				'eyebrow'  => __( 'Featured tutors', 'nextgencompanion' ),
				'title'    => __( 'Expert tutors.', 'nextgencompanion' ),
				'subtitle' => __( 'Verified educators from our directory — CAPS, IEB and Cambridge support.', 'nextgencompanion' ),
			],
			'success_stories' => [
				'eyebrow'  => __( 'Happy clients · real marks', 'nextgencompanion' ),
				'title'    => __( 'The Joy of Achievement', 'nextgencompanion' ),
				'subtitle' => __( 'Stories from families who found the right tutor.', 'nextgencompanion' ),
			],
			'trust_safety' => [
				'eyebrow' => __( 'Before / After', 'nextgencompanion' ),
				'title'   => __( 'Show the transformation parents want to see.', 'nextgencompanion' ),
				'copy'    => __( 'Every tutor passes ID verification, credential checks and background screening.', 'nextgencompanion' ),
			],
			'pricing' => [
				'eyebrow' => __( 'Transparent pricing', 'nextgencompanion' ),
				'title'   => __( 'Flat Monthly Packages.', 'nextgencompanion' ),
				'subtitle'=> __( 'Flexible online, in-person and tertiary options with commitment discounts.', 'nextgencompanion' ),
				'rates'   => [
					'rate_online'    => 320,
					'rate_inperson'  => 350,
					'rate_tertiary'  => 500,
				],
			],
			'faq' => [
				'eyebrow' => __( 'Questions', 'nextgencompanion' ),
				'title'   => __( 'Common Questions', 'nextgencompanion' ),
				'items'   => [
					[
						'q' => __( 'Can parents track progress?', 'nextgencompanion' ),
						'a' => __( 'Yes. The parent dashboard highlights attendance, homework and tutor notes.', 'nextgencompanion' ),
					],
					[
						'q' => __( 'Are tutors background-checked?', 'nextgencompanion' ),
						'a' => __( 'Every tutor passes ID verification and background clearance before listing.', 'nextgencompanion' ),
					],
				],
			],
			'cta' => [
				'title'         => __( 'Ready to find your tutor?', 'nextgencompanion' ),
				'subtitle'      => __( 'Book a free assessment — no commitment.', 'nextgencompanion' ),
				'button_text'   => __( 'Book Free Assessment', 'nextgencompanion' ),
				'become_title'  => __( 'Want to Become a Tutor?', 'nextgencompanion' ),
				'become_button' => __( 'Apply to Teach', 'nextgencompanion' ),
				'guarantee_line'=> __( 'Love the lesson — or your first hour is on us.', 'nextgencompanion' ),
			],
		];
		return $defaults[ $section_key ] ?? [ 'title' => ucwords( str_replace( '_', ' ', $section_key ) ) ];
	}

	/**
	 * Merge a single field into a section and persist.
	 *
	 * @param string $page_key    Page key.
	 * @param string $section_key Section key.
	 * @param string $field_key   Field key.
	 * @param mixed  $value       Value.
	 * @return bool
	 */
	public static function upsert_section_field( $page_key, $section_key, $field_key, $value ) {
		$page_key    = sanitize_key( $page_key );
		$section_key = sanitize_key( $section_key );
		$field_key   = sanitize_key( $field_key );
		if ( ! $page_key || ! $section_key || ! $field_key ) {
			return false;
		}

		$data              = self::get_section( $page_key, $section_key );
		$data[ $field_key ] = $value;
		$row               = self::get_section_row( $page_key, $section_key );
		$enabled           = $row ? (bool) ( $row['is_enabled'] ?? true ) : true;
		self::save_section( $page_key, $section_key, $data, $enabled );
		return true;
	}

	/**
	 * Seed research-normalized CMS copy when DB row is empty or matches legacy default.
	 *
	 * @param bool $force Overwrite existing rows.
	 * @return array<string, int>
	 */
	public static function seed_research_copy( $force = false ) {
		$counts = [ 'imported' => 0, 'skipped' => 0 ];
		foreach ( self::section_keys() as $key ) {
			$row = self::get_section_row( self::PAGE_HOME, $key );
			if ( $row && ! $force ) {
				++$counts['skipped'];
				continue;
			}
			self::save_section( self::PAGE_HOME, $key, self::default_content( $key ), true );
			++$counts['imported'];
		}
		update_option( 'ngc_ui_cms_research_seeded', gmdate( 'c' ), false );
		return $counts;
	}

	/**
	 * Simple admin list + JSON editor.
	 */
	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['ngc_section_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ngc_section_nonce'] ) ), 'ngc_save_section' ) ) {
			$key     = sanitize_key( wp_unslash( $_POST['section_key'] ?? '' ) );
			$json    = wp_unslash( $_POST['content_json'] ?? '{}' );
			$enabled = ! empty( $_POST['is_enabled'] );
			$data    = json_decode( $json, true );
			if ( $key && is_array( $data ) ) {
				self::save_section( self::PAGE_HOME, $key, $data, $enabled );
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Section saved.', 'nextgencompanion' ) . '</p></div>';
			}
		}

		$active = sanitize_key( wp_unslash( $_GET['section'] ?? self::section_keys()[0] ) );
		$data   = self::get_section( self::PAGE_HOME, $active );
		$row    = self::get_section_row( self::PAGE_HOME, $active );
		echo '<div class="wrap"><h1>' . esc_html__( 'Homepage Sections', 'nextgencompanion' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper">';
		foreach ( self::section_keys() as $key ) {
			$url = add_query_arg( [ 'page' => 'ngc-home-sections', 'section' => $key ], admin_url( 'admin.php' ) );
			$cls = $key === $active ? 'nav-tab nav-tab-active' : 'nav-tab';
			echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( ucwords( str_replace( '_', ' ', $key ) ) ) . '</a>';
		}
		echo '</nav><form method="post" style="margin-top:16px">';
		wp_nonce_field( 'ngc_save_section', 'ngc_section_nonce' );
		echo '<input type="hidden" name="section_key" value="' . esc_attr( $active ) . '" />';
		echo '<p><label><input type="checkbox" name="is_enabled" value="1" ' . checked( ! $row || ! empty( $row['is_enabled'] ), true, false ) . ' /> ' . esc_html__( 'Section enabled', 'nextgencompanion' ) . '</label></p>';
		echo '<textarea name="content_json" rows="18" style="width:100%;font-family:monospace">' . esc_textarea( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</textarea>';
		submit_button( __( 'Save Section', 'nextgencompanion' ) );
		echo '</form></div>';
	}
}

/**
 * Theme helper — safe when Companion inactive.
 *
 * @param string $section_key Section key.
 * @param string $field       Field name.
 * @param mixed  $default     Default.
 * @return mixed
 */
function ngc_home_section( $section_key, $field = '', $default = '' ) {
	if ( ! class_exists( 'NGC_Section_CMS' ) ) {
		return $default;
	}
	return NGC_Section_CMS::home_field( $section_key, $field, $default );
}

/**
 * Whether a homepage CMS block is enabled.
 *
 * @param string $section_key Section key.
 * @return bool
 */
function ngc_home_section_enabled( $section_key ) {
	if ( ! class_exists( 'NGC_Section_CMS' ) ) {
		return true;
	}
	return NGC_Section_CMS::section_enabled( $section_key );
}

/**
 * Pricing tier rows for UI library / REST consumers.
 *
 * @param array<string, mixed> $args Unused.
 * @return array<int, array<string, mixed>>
 */
function ngc_get_pricing_tiers( $args = [] ) {
	if ( ! class_exists( 'NGC_Section_CMS' ) ) {
		return [];
	}

	$data  = NGC_Section_CMS::get_section( NGC_Section_CMS::PAGE_HOME, 'pricing' );
	$rates = is_array( $data['rates'] ?? null ) ? $data['rates'] : [];
	$map   = [
		'rate_online'   => [
			'title' => __( 'Online Classroom', 'nextgencompanion' ),
			'mode'  => 'online',
		],
		'rate_inperson' => [
			'title' => __( 'In-Person at Home', 'nextgencompanion' ),
			'mode'  => 'in-person',
		],
		'rate_tertiary' => [
			'title' => __( 'Tertiary Subjects', 'nextgencompanion' ),
			'mode'  => 'tertiary',
		],
	];

	$rows = [];
	foreach ( $map as $key => $meta ) {
		if ( empty( $rates[ $key ] ) ) {
			continue;
		}
		$rows[] = [
			'title' => $meta['title'],
			'price' => (int) $rates[ $key ],
			'mode'  => $meta['mode'],
			'url'   => home_url( '/pricing/' ),
		];
	}

	return apply_filters( 'ngc_pricing_tiers', $rows, $args );
}
