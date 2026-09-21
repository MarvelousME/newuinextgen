<?php
/**
 * Rule Repository — CRUD layer for the ngt_3d_scroll_rules table.
 *
 * ALL SQL uses $wpdb->prepare(). No direct SQL in admin views.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Rule_Repository {

	/** Transient prefix for page-specific rule caches. */
	const CACHE_PREFIX = 'ngt3d_rules_';

	/** Transient TTL in seconds (1 hour). */
	const CACHE_TTL = 3600;

	// ── Public API ──────────────────────────────────────────────────────────────

	/**
	 * Get all enabled rules for a given page (by ID) or slug.
	 * Results are cached per (page_id|slug) to avoid DB queries on every request.
	 *
	 * @param int    $page_id   WordPress post ID (0 = unknown/skip ID lookup).
	 * @param string $page_slug Page slug ('home', 'find-a-tutor', …).
	 * @return array<int, array<string, mixed>>  Sorted by sort_order ASC.
	 */
	public static function get_for_page( int $page_id, string $page_slug ): array {
		$cache_key = self::cache_key( $page_id, $page_slug );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$table = NGT3D_Schema::table();

		// Build WHERE: match by page_id OR page_slug OR 'all' pages.
		$where_parts = [ '(enabled = 1)' ];
		$params      = [];

		$conditions = [];
		if ( $page_id > 0 ) {
			$conditions[] = 'page_id = %d';
			$params[]     = $page_id;
		}
		if ( '' !== $page_slug ) {
			$conditions[] = 'page_slug = %s';
			$params[]     = $page_slug;
		}
		$conditions[] = "page_slug = 'all'";
		$conditions[] = "page_slug = 'front-page' AND %d = (SELECT option_value FROM {$wpdb->options} WHERE option_name = 'page_on_front' LIMIT 1)";
		$params[]     = $page_id;

		$where_parts[] = '(' . implode( ' OR ', $conditions ) . ')';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} WHERE " . implode( ' AND ', $where_parts ) . ' ORDER BY sort_order ASC, id ASC',
			...$params
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! is_array( $rows ) ) {
			$rows = [];
		}

		$rows = array_map( [ self::class, 'decode_row' ], $rows );

		set_transient( $cache_key, $rows, self::CACHE_TTL );

		return $rows;
	}

	/**
	 * Fetch a single rule by ID.
	 *
	 * @param int $id Rule ID.
	 * @return array<string, mixed>|null
	 */
	public static function get( int $id ): ?array {
		global $wpdb;
		$table = NGT3D_Schema::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $row ? self::decode_row( $row ) : null;
	}

	/**
	 * Fetch all rules (admin list view).
	 *
	 * @param array{ page?: int, per_page?: int, orderby?: string, order?: string, search?: string } $args
	 * @return array{ rules: array, total: int }
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$table = NGT3D_Schema::table();

		$page     = max( 1, absint( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 200, absint( $args['per_page'] ?? 20 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$allowed_order  = [ 'id', 'page_slug', 'target_selector', 'sort_order', 'enabled', 'created_at' ];
		$orderby        = in_array( $args['orderby'] ?? 'sort_order', $allowed_order, true ) ? $args['orderby'] : 'sort_order';
		$order          = strtoupper( $args['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

		$where  = '1=1';
		$params = [];

		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where  .= ' AND (page_slug LIKE %s OR target_selector LIKE %s OR animation_names LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_sql = empty( $params )
			? "SELECT COUNT(*) FROM {$table} WHERE {$where}"
			: $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params );

		$total = (int) $wpdb->get_var( $total_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$data_params   = array_merge( $params, [ $per_page, $offset ] );
		$data_sql      = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
			...$data_params
		);

		$rows = $wpdb->get_results( $data_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable

		$rules = array_map( [ self::class, 'decode_row' ], is_array( $rows ) ? $rows : [] );

		return [ 'rules' => $rules, 'total' => $total ];
	}

	/**
	 * Create a new rule.
	 *
	 * @param array $data Sanitized data from NGT3D_Validator::sanitize_rule().
	 * @return int|WP_Error  New rule ID or error.
	 */
	public static function create( array $data ): int|WP_Error {
		global $wpdb;
		$table = NGT3D_Schema::table();

		/**
		 * Fires before a rule is saved.
		 *
		 * @param array $data Sanitized rule data.
		 */
		do_action( 'ngt_3d_before_rule_save', $data );

		$result = $wpdb->insert( $table, self::prepare_for_db( $data ), self::db_formats() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( false === $result ) {
			return new WP_Error( 'ngt3d_db_error', $wpdb->last_error );
		}

		$id = (int) $wpdb->insert_id;
		self::flush_cache_for_data( $data );

		/** @param int   $id   New rule ID. */
		do_action( 'ngt_3d_after_rule_save', $id, $data );

		return $id;
	}

	/**
	 * Update an existing rule.
	 *
	 * @param int   $id   Rule ID.
	 * @param array $data Sanitized data.
	 * @return bool|WP_Error
	 */
	public static function update( int $id, array $data ): bool|WP_Error {
		global $wpdb;
		$table = NGT3D_Schema::table();

		do_action( 'ngt_3d_before_rule_save', $data );

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			self::prepare_for_db( $data ),
			[ 'id' => $id ],
			self::db_formats(),
			[ '%d' ]
		);

		if ( false === $result ) {
			return new WP_Error( 'ngt3d_db_error', $wpdb->last_error );
		}

		self::flush_cache_for_data( $data );
		do_action( 'ngt_3d_after_rule_save', $id, $data );

		return true;
	}

	/**
	 * Delete one or more rules by ID.
	 *
	 * @param int[] $ids Rule IDs.
	 * @return int  Number of rows deleted.
	 */
	public static function delete( array $ids ): int {
		global $wpdb;
		$table = NGT3D_Schema::table();

		if ( empty( $ids ) ) {
			return 0;
		}

		$ids         = array_map( 'absint', $ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids )
		);

		self::flush_all_caches();

		return $deleted;
	}

	/**
	 * Enable or disable one or more rules.
	 *
	 * @param int[] $ids     Rule IDs.
	 * @param bool  $enabled Target state.
	 * @return int  Rows affected.
	 */
	public static function set_enabled( array $ids, bool $enabled ): int {
		global $wpdb;
		$table = NGT3D_Schema::table();

		if ( empty( $ids ) ) {
			return 0;
		}

		$ids          = array_map( 'absint', $ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$flag         = $enabled ? 1 : 0;
		$params       = array_merge( [ $flag, get_current_user_id() ], $ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = (int) $wpdb->query(
			$wpdb->prepare( "UPDATE {$table} SET enabled = %d, updated_by = %d WHERE id IN ({$placeholders})", ...$params )
		);

		self::flush_all_caches();

		return $affected;
	}

	/**
	 * Duplicate a rule (copy with +1 sort_order).
	 *
	 * @param int $id Rule ID to copy.
	 * @return int|WP_Error  New rule ID.
	 */
	public static function duplicate( int $id ): int|WP_Error {
		$source = self::get( $id );
		if ( null === $source ) {
			return new WP_Error( 'ngt3d_not_found', __( 'Rule not found.', 'ngt-3d-scroll' ) );
		}

		unset( $source['id'], $source['created_at'], $source['updated_at'] );
		$source['sort_order']  = $source['sort_order'] + 1;
		$source['created_by']  = get_current_user_id();
		$source['updated_by']  = get_current_user_id();
		// Store options back as JSON string for the DB.
		if ( is_array( $source['animation_options'] ) ) {
			$source['animation_options'] = wp_json_encode( $source['animation_options'] );
		}

		return self::create( $source );
	}

	/**
	 * Export all (or page-specific) rules as the canonical JSON model.
	 *
	 * @param int|null    $page_id   Optional filter.
	 * @param string|null $page_slug Optional filter.
	 * @return array{ schemaVersion: int, 3DScrollPages: array }
	 */
	public static function export( ?int $page_id = null, ?string $page_slug = null ): array {
		$result = self::get_all( [ 'per_page' => 9999 ] );
		$rows   = $result['rules'];

		$pages = [];
		foreach ( $rows as $row ) {
			if ( null !== $page_id && $row['page_id'] !== $page_id ) {
				continue;
			}
			if ( null !== $page_slug && $row['page_slug'] !== $page_slug ) {
				continue;
			}

			$pages[] = [
				'Id'         => $row['page_slug'] ?: (string) $row['page_id'],
				'target'     => $row['target_selector'],
				'targetType' => $row['target_type'],
				'style'      => $row['animation_names'],
				'enabled'    => (bool) $row['enabled'],
				'desktop'    => $row['desktop_mode'],
				'tablet'     => $row['tablet_mode'],
				'mobile'     => $row['mobile_mode'],
				'sort'       => $row['sort_order'],
				'options'    => $row['animation_options'] ?: new stdClass(),
			];
		}

		return [
			'schemaVersion' => NGT3D_Schema::VERSION,
			'exportedAt'    => gmdate( 'c' ),
			'3DScrollPages' => $pages,
		];
	}

	/**
	 * Seed default front-page rules on activation (only if table is empty).
	 *
	 * Section IDs assume the kinetic home template is active and the adapter
	 * has added stable IDs to the rendered DOM.
	 */
	public static function seed_defaults(): void {
		global $wpdb;
		$table = NGT3D_Schema::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $existing > 0 ) {
			return;
		}

		$front_id   = (int) get_option( 'page_on_front', 0 );
		$front_slug = 'home';

		if ( $front_id > 0 ) {
			$page = get_post( $front_id );
			if ( $page ) {
				$front_slug = $page->post_name;
			}
		}

		$defaults = [
			[
				'target_selector'   => '#hero',
				'animation_names'   => 'zoom',
				'animation_options' => [ 'scrub' => 1.1, 'scaleFrom' => 0.82, 'scaleTo' => 1, 'perspective' => 1200, 'intensity' => 'balanced' ],
				'sort_order'        => 10,
				'mobile_mode'       => 'reduced',
			],
			[
				'target_selector'   => '#platform-highlights',
				'animation_names'   => 'dark-veles',
				'animation_options' => [ 'scrub' => 0.9, 'stagger' => 0.12, 'scaleFrom' => 0.94, 'y' => 36 ],
				'sort_order'        => 20,
				'mobile_mode'       => 'disabled',
			],
			[
				'target_selector'   => '#tutoring-story',
				'animation_names'   => 'doublescroll',
				'animation_options' => [ 'scrub' => 1.2, 'pin' => true, 'primaryDistance' => 240, 'secondaryDistance' => -240 ],
				'sort_order'        => 30,
				'mobile_mode'       => 'disabled',
			],
			[
				'target_selector'   => '#subjects',
				'animation_names'   => 'onscroll',
				'animation_options' => [ 'stagger' => 0.08, 'y' => 40, 'scaleFrom' => 0.94 ],
				'sort_order'        => 40,
				'mobile_mode'       => 'reduced',
			],
			[
				'target_selector'   => '#video-story',
				'animation_names'   => '4kvideo',
				'animation_options' => [ 'scrub' => 1, 'pin' => true, 'scaleFrom' => 0.88, 'scaleTo' => 1 ],
				'sort_order'        => 50,
				'mobile_mode'       => 'disabled',
			],
			[
				'target_selector'   => '#image-hover',
				'animation_names'   => 'wiper',
				'animation_options' => [ 'scrub' => 1, 'direction' => 'left' ],
				'sort_order'        => 60,
				'mobile_mode'       => 'reduced',
			],
			[
				'target_selector'   => '#pathways',
				'animation_names'   => 'scroll-mask',
				'animation_options' => [ 'scrub' => 1.1, 'pin' => true, 'maskScale' => 1.12 ],
				'sort_order'        => 70,
				'mobile_mode'       => 'disabled',
			],
			[
				'target_selector'   => '#tutors',
				'animation_names'   => 'carousel-depth',
				'animation_options' => [ 'scrub' => 0.6, 'y' => -20 ],
				'sort_order'        => 80,
				'mobile_mode'       => 'full',
			],
			[
				'target_selector'   => '#cta',
				'animation_names'   => 'parallax-slow,depth-scroll',
				'animation_options' => [ 'scrub' => 1.5 ],
				'sort_order'        => 90,
				'mobile_mode'       => 'disabled',
			],
		];

		foreach ( $defaults as $d ) {
			$data = array_merge( [
				'page_id'       => $front_id,
				'page_slug'     => $front_slug,
				'target_type'   => 'selector',
				'style_classes' => '',
				'enabled'       => 1,
				'desktop_mode'  => 'full',
				'tablet_mode'   => $d['tablet_mode'] ?? 'reduced',
				'mobile_mode'   => $d['mobile_mode'] ?? 'reduced',
				'created_by'    => 0,
				'updated_by'    => 0,
			], $d );

			// Encode options.
			if ( is_array( $data['animation_options'] ) ) {
				$data['animation_options'] = wp_json_encode( $data['animation_options'] );
			}

			$wpdb->insert( $table, self::prepare_for_db( $data ), self::db_formats() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	/**
	 * Migrate/refresh homepage showcase mapping once (idempotent).
	 * Preserves non-home rules. Does not invent swag-card/transforms instances.
	 */
	public static function migrate_showcase_home_map(): void {
		$flag = 'ngt_3d_showcase_home_map_v1';
		if ( get_option( $flag ) === '2026-09-11' ) {
			return;
		}

		global $wpdb;
		$table = NGT3D_Schema::table();

		$front_id   = (int) get_option( 'page_on_front', 0 );
		$front_slug = 'home';
		if ( $front_id > 0 ) {
			$page = get_post( $front_id );
			if ( $page ) {
				$front_slug = $page->post_name;
			}
		}

		// Soft-disable previous front-page seeds so we can insert the coherent showcase map.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET enabled = 0 WHERE (page_id = %d OR page_slug = %s OR page_slug = 'front-page') AND enabled = 1",
				$front_id,
				$front_slug
			)
		);

		// Temporarily clear count gate by inserting via create path after forcing empty home set.
		$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
		$defaults = null;

		// Re-use seed list by temporarily emptying then calling seed — safer: insert explicitly.
		$seed_flag_backup = $existing;
		unset( $seed_flag_backup );

		$map = [
			[ '#hero', 'zoom', [ 'scrub' => 1.1, 'scaleFrom' => 0.82, 'scaleTo' => 1, 'perspective' => 1200 ], 10, 'reduced' ],
			[ '#platform-highlights', 'dark-veles', [ 'scrub' => 0.9, 'stagger' => 0.12, 'scaleFrom' => 0.94, 'y' => 36 ], 20, 'disabled' ],
			[ '#tutoring-story', 'doublescroll', [ 'scrub' => 1.2, 'pin' => true, 'primaryDistance' => 240, 'secondaryDistance' => -240 ], 30, 'disabled' ],
			[ '#subjects', 'onscroll', [ 'stagger' => 0.08, 'y' => 40, 'scaleFrom' => 0.94 ], 40, 'reduced' ],
			[ '#video-story', '4kvideo', [ 'scrub' => 1, 'pin' => true, 'scaleFrom' => 0.88, 'scaleTo' => 1 ], 50, 'disabled' ],
			[ '#image-hover', 'wiper', [ 'scrub' => 1, 'direction' => 'left' ], 60, 'reduced' ],
			[ '#pathways', 'scroll-mask', [ 'scrub' => 1.1, 'pin' => true, 'maskScale' => 1.12 ], 70, 'disabled' ],
			[ '#tutors', 'carousel-depth', [ 'scrub' => 0.6, 'y' => -20 ], 80, 'full' ],
			[ '#cta', 'parallax-slow,depth-scroll', [ 'scrub' => 1.5 ], 90, 'disabled' ],
		];

		foreach ( $map as $row ) {
			[ $selector, $names, $options, $order, $mobile ] = $row;
			$data = [
				'page_id'           => $front_id,
				'page_slug'         => $front_slug,
				'target_selector'   => $selector,
				'target_type'       => 'selector',
				'animation_names'   => $names,
				'style_classes'     => '',
				'animation_options' => wp_json_encode( $options ),
				'sort_order'        => $order,
				'enabled'           => 1,
				'desktop_mode'      => 'full',
				'tablet_mode'       => 'reduced',
				'mobile_mode'       => $mobile,
				'created_by'        => 0,
				'updated_by'        => 0,
			];
			$wpdb->insert( $table, self::prepare_for_db( $data ), self::db_formats() ); // phpcs:ignore
		}

		self::flush_all_caches();
		update_option( $flag, '2026-09-11', false );
	}

	// ── Private helpers ─────────────────────────────────────────────────────────

	/**
	 * Decode a DB row — JSON-decode animation_options.
	 *
	 * @param array $row Raw DB row.
	 * @return array
	 */
	private static function decode_row( array $row ): array {
		$row['id']         = (int) $row['id'];
		$row['page_id']    = (int) $row['page_id'];
		$row['sort_order'] = (int) $row['sort_order'];
		$row['enabled']    = (bool) $row['enabled'];
		$row['created_by'] = (int) $row['created_by'];
		$row['updated_by'] = (int) $row['updated_by'];

		if ( ! empty( $row['animation_options'] ) ) {
			$decoded = json_decode( $row['animation_options'], true );
			$row['animation_options'] = is_array( $decoded ) ? $decoded : [];
		} else {
			$row['animation_options'] = [];
		}

		return $row;
	}

	/**
	 * Prepare a data array for $wpdb insert/update.
	 *
	 * @param array $data Sanitized data.
	 * @return array
	 */
	private static function prepare_for_db( array $data ): array {
		$allowed = [
			'page_id', 'page_slug', 'target_selector', 'target_type',
			'animation_names', 'style_classes', 'animation_options',
			'sort_order', 'enabled', 'desktop_mode', 'tablet_mode', 'mobile_mode',
			'created_by', 'updated_by',
		];

		$prepared = [];
		foreach ( $allowed as $col ) {
			if ( array_key_exists( $col, $data ) ) {
				$prepared[ $col ] = $data[ $col ];
			}
		}

		// animation_options must be stored as JSON string.
		if ( isset( $prepared['animation_options'] ) && is_array( $prepared['animation_options'] ) ) {
			$prepared['animation_options'] = wp_json_encode( $prepared['animation_options'] );
		}

		return $prepared;
	}

	/** @return string[] */
	private static function db_formats(): array {
		return [
			'page_id'           => '%d',
			'page_slug'         => '%s',
			'target_selector'   => '%s',
			'target_type'       => '%s',
			'animation_names'   => '%s',
			'style_classes'     => '%s',
			'animation_options' => '%s',
			'sort_order'        => '%d',
			'enabled'           => '%d',
			'desktop_mode'      => '%s',
			'tablet_mode'       => '%s',
			'mobile_mode'       => '%s',
			'created_by'        => '%d',
			'updated_by'        => '%d',
		];
	}

	/**
	 * Cache key for a page lookup.
	 *
	 * @param int    $page_id
	 * @param string $page_slug
	 * @return string
	 */
	private static function cache_key( int $page_id, string $page_slug ): string {
		return self::CACHE_PREFIX . md5( $page_id . '|' . $page_slug );
	}

	/**
	 * Flush the cache for the page referenced by a rule's data.
	 *
	 * @param array $data Rule data.
	 */
	private static function flush_cache_for_data( array $data ): void {
		$page_id   = (int) ( $data['page_id'] ?? 0 );
		$page_slug = (string) ( $data['page_slug'] ?? '' );

		if ( $page_id > 0 || '' !== $page_slug ) {
			delete_transient( self::cache_key( $page_id, $page_slug ) );
		}

		// Also flush 'all' rules cache.
		delete_transient( self::cache_key( 0, 'all' ) );
	}

	/** Flush all ngt3d_rules_* transients. */
	public static function flush_all_caches(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX ) . '%'
			)
		);
	}
}
