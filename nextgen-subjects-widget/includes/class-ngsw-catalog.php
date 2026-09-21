<?php
/**
 * Subject catalogue resolver — taxonomy → Companion CMS → theme tracks → defaults.
 *
 * @package NextGen_Subjects_Widget
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves subject rows for widget / shortcode rendering.
 */
final class NGSW_Catalog {

	/**
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	public static function resolve( string $manual_input = '', bool $use_live = true ): array {
		$manual = self::parse_lines( $manual_input );

		if ( ! $use_live && $manual ) {
			return $manual;
		}

		if ( $use_live ) {
			$live = self::from_taxonomy();
			if ( ! $live ) {
				$live = self::from_cms();
			}
			if ( ! $live ) {
				$live = self::from_theme_tracks();
			}
			if ( $live ) {
				return $live;
			}
		}

		if ( $manual ) {
			return $manual;
		}

		return self::defaults();
	}

	/**
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	public static function parse_lines( string $input ): array {
		$lines    = preg_split( '/\r\n|\r|\n/', $input ) ?: [];
		$subjects = [];

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line, 3 ) );
			$name  = sanitize_text_field( $parts[0] ?? '' );
			if ( '' === $name ) {
				continue;
			}

			$slug = sanitize_title( $name );
			$subjects[] = self::normalize_row(
				[
					'name'        => $name,
					'description' => sanitize_text_field(
						$parts[1] ?? sprintf(
							/* translators: %s: subject name */
							__( 'Expert tutoring and personalised support in %s.', 'nextgen-subjects-widget' ),
							$name
						)
					),
					'icon'        => sanitize_key( $parts[2] ?? self::guess_icon( $name ) ),
					'slug'        => $slug,
				]
			);
		}

		return $subjects;
	}

	/**
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	private static function from_taxonomy(): array {
		if ( ! taxonomy_exists( 'subject' ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'subject',
				'hide_empty' => false,
				'number'     => 48,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		$rows = [];
		foreach ( $terms as $term ) {
			$desc = trim( (string) $term->description );
			$rows[] = self::normalize_row(
				[
					'name'        => $term->name,
					'description' => $desc !== ''
						? $desc
						: sprintf(
							/* translators: %s: subject name */
							__( 'Find vetted tutors for %s.', 'nextgen-subjects-widget' ),
							$term->name
						),
					'icon'        => self::guess_icon( $term->name ),
					'slug'        => $term->slug,
					'url'         => get_term_link( $term ),
				]
			);
		}

		return $rows;
	}

	/**
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	private static function from_cms(): array {
		if ( ! class_exists( 'NGC_Subjects_CMS' ) || ! is_callable( [ 'NGC_Subjects_CMS', 'catalog' ] ) ) {
			return [];
		}

		$catalog = NGC_Subjects_CMS::catalog();
		if ( ! is_array( $catalog ) || empty( $catalog ) ) {
			return [];
		}

		$rows = [];
		foreach ( $catalog as $slug => $label ) {
			$label = (string) $label;
			$slug  = sanitize_title( (string) $slug );
			if ( '' === $label || '' === $slug ) {
				continue;
			}
			$rows[] = self::normalize_row(
				[
					'name'        => $label,
					'description' => sprintf(
						/* translators: %s: subject name */
						__( 'Expert tutoring and personalised support in %s.', 'nextgen-subjects-widget' ),
						$label
					),
					'icon'        => self::guess_icon( $label ),
					'slug'        => $slug,
				]
			);
		}

		return $rows;
	}

	/**
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	private static function from_theme_tracks(): array {
		if ( ! function_exists( 'bi_get_subject_tracks' ) ) {
			return [];
		}

		$tracks = bi_get_subject_tracks();
		if ( ! is_array( $tracks ) || empty( $tracks ) ) {
			return [];
		}

		$rows = [];
		foreach ( $tracks as $track ) {
			$name = sanitize_text_field( (string) ( $track['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$rows[] = self::normalize_row(
				[
					'name'        => $name,
					'description' => sanitize_text_field( (string) ( $track['desc'] ?? '' ) ),
					'icon'        => self::guess_icon( $name ),
					'slug'        => sanitize_title( $name ),
				]
			);
		}

		return $rows;
	}

	/**
	 * @return array<int, array{name:string,description:string,icon:string,slug:string,url:string}>
	 */
	public static function defaults(): array {
		return self::parse_lines(
			implode(
				PHP_EOL,
				[
					'Mathematics|Master concepts from arithmetic to advanced mathematics.|calculator',
					'English|Improve language, literature, comprehension and writing skills.|book',
					'Physical Science|Build confidence in physics and chemistry.|atom',
					'Life Sciences|Understand biology and the science of living systems.|leaf',
					'Accounting|Develop strong financial and accounting skills.|chart',
					'Geography|Explore physical and human geography with expert guidance.|globe',
					'History|Understand events, people and ideas that shaped our world.|landmark',
					'Computer Science|Learn coding, computing and digital technology.|code',
				]
			)
		);
	}

	/**
	 * @param array<string, mixed> $row Raw row.
	 * @return array{name:string,description:string,icon:string,slug:string,url:string}
	 */
	private static function normalize_row( array $row ): array {
		$name = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
		$slug = sanitize_title( (string) ( $row['slug'] ?? $name ) );
		$url  = isset( $row['url'] ) ? (string) $row['url'] : '';

		if ( '' === $url || is_wp_error( $url ) ) {
			$url = self::marketplace_url( $slug );
		}

		return [
			'name'        => $name,
			'description' => sanitize_text_field( (string) ( $row['description'] ?? '' ) ),
			'icon'        => sanitize_key( (string) ( $row['icon'] ?? self::guess_icon( $name ) ) ),
			'slug'        => $slug,
			'url'         => esc_url_raw( $url ),
		];
	}

	public static function marketplace_url( string $slug ): string {
		$slug = sanitize_title( $slug );
		$page = get_page_by_path( 'find-a-tutor' );
		$base = $page ? get_permalink( $page ) : home_url( '/find-a-tutor/' );
		return add_query_arg( 'subject', $slug, $base );
	}

	public static function guess_icon( string $subject ): string {
		$subject = strtolower( $subject );
		$map     = [
			'math'        => 'calculator',
			'mathematics' => 'calculator',
			'english'     => 'book',
			'afrikaans'   => 'message',
			'physics'     => 'atom',
			'physical'    => 'atom',
			'science'     => 'flask',
			'chemistry'   => 'flask',
			'biology'     => 'leaf',
			'life'        => 'leaf',
			'geography'   => 'globe',
			'history'     => 'landmark',
			'accounting'  => 'chart',
			'economics'   => 'chart',
			'coding'      => 'code',
			'computer'    => 'code',
			'program'     => 'code',
			'python'      => 'code',
			'technology'  => 'cpu',
		];

		foreach ( $map as $keyword => $icon ) {
			if ( str_contains( $subject, $keyword ) ) {
				return $icon;
			}
		}

		return 'book';
	}
}
