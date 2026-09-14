<?php
/**
 * Filmstrip data providers — Subjects / Tutors from existing NextGen sources.
 *
 * @package NextGen_3D_Filmstrip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize cards for the filmstrip renderer.
 */
final class NGTFS_Data {

	/**
	 * @param array<string, mixed> $args source, limit, subject filter.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_cards( array $args = [] ): array {
		$source = sanitize_key( (string) ( $args['source'] ?? 'tutors' ) );
		$limit  = max( 1, min( 24, absint( $args['limit'] ?? 8 ) ) );

		if ( 'subjects' === $source ) {
			$cards = self::subjects( $limit );
		} elseif ( 'manual' === $source ) {
			$cards = self::manual( (string) ( $args['manual'] ?? '' ), $limit );
		} else {
			$cards = self::tutors( $limit, (string) ( $args['subject'] ?? '' ) );
		}

		/**
		 * Filter filmstrip cards after resolution.
		 *
		 * @param array<int, array<string, mixed>> $cards Cards.
		 * @param array<string, mixed>             $args  Args.
		 */
		return apply_filters( 'ngtfs_cards', array_values( array_slice( $cards, 0, $limit ) ), $args );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function tutors( int $limit = 8, string $subject = '' ): array {
		$tutors = [];

		if ( function_exists( 'bi_get_carousel_tutors' ) ) {
			$tutors = bi_get_carousel_tutors( $subject ? $limit * 3 : $limit );
		}

		if ( empty( $tutors ) && class_exists( 'NGC_UI_Tutor_Data_Provider' ) ) {
			$provider = new NGC_UI_Tutor_Data_Provider();
			if ( $provider->is_available() ) {
				$tutors = $provider->list( [ 'limit' => $limit ] );
			}
		}

		if ( $subject && function_exists( 'bi_filter_tutors_by_subject' ) ) {
			$tutors = bi_filter_tutors_by_subject( $tutors, sanitize_title( $subject ) );
		}

		$cards = [];
		foreach ( (array) $tutors as $tutor ) {
			$name = sanitize_text_field( (string) ( $tutor['name'] ?? $tutor['title'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$image = (string) ( $tutor['imageUrl'] ?? $tutor['avatar'] ?? $tutor['photo'] ?? '' );
			$url   = (string) ( $tutor['permalink'] ?? $tutor['url'] ?? '' );
			if ( '' === $url ) {
				$url = home_url( '/find-a-tutor/' );
			}
			$subjects = array_map( 'sanitize_text_field', (array) ( $tutor['subjects'] ?? [] ) );
			$rate     = (int) ( $tutor['hourlyRate'] ?? $tutor['rate'] ?? 0 );
			$rating   = (float) ( $tutor['rating'] ?? 0 );

			$cards[] = [
				'type'       => 'tutor',
				'title'      => $name,
				'subtitle'   => sanitize_text_field( (string) ( $tutor['degree'] ?? '' ) ),
				'body'       => sanitize_text_field( wp_trim_words( (string) ( $tutor['bio'] ?? '' ), 18 ) ),
				'image'      => esc_url_raw( $image ),
				'url'        => esc_url_raw( $url ),
				'tags'       => array_slice( $subjects, 0, 3 ),
				'meta'       => $rate > 0 ? 'R' . $rate . '/hr' : '',
				'rating'     => $rating > 0 ? number_format( $rating, 1 ) : '',
				'badge'      => ! empty( $tutor['vetted'] ) ? __( 'Verified', 'nextgen-3d-filmstrip' ) : '',
				'accent'     => self::accent_from_string( $name ),
			];
		}

		return $cards;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function subjects( int $limit = 8 ): array {
		$rows = [];

		if ( class_exists( 'NGSW_Catalog' ) && is_callable( [ 'NGSW_Catalog', 'resolve' ] ) ) {
			$rows = NGSW_Catalog::resolve( '', true );
		} elseif ( taxonomy_exists( 'subject' ) ) {
			$terms = get_terms(
				[
					'taxonomy'   => 'subject',
					'hide_empty' => false,
					'number'     => $limit,
				]
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$rows[] = [
						'name'        => $term->name,
						'description' => $term->description,
						'slug'        => $term->slug,
						'url'         => get_term_link( $term ),
						'icon'        => 'book',
						'count'       => (int) $term->count,
					];
				}
			}
		} elseif ( class_exists( 'NGC_Subjects_CMS' ) ) {
			foreach ( NGC_Subjects_CMS::catalog() as $slug => $label ) {
				$rows[] = [
					'name'        => $label,
					'description' => '',
					'slug'        => $slug,
					'url'         => add_query_arg( 'subject', sanitize_title( (string) $slug ), home_url( '/find-a-tutor/' ) ),
					'icon'        => 'book',
				];
			}
		} elseif ( function_exists( 'bi_get_subject_tracks' ) ) {
			foreach ( bi_get_subject_tracks() as $track ) {
				$rows[] = [
					'name'        => $track['name'] ?? '',
					'description' => $track['desc'] ?? '',
					'slug'        => sanitize_title( (string) ( $track['name'] ?? '' ) ),
					'url'         => add_query_arg( 'subject', sanitize_title( (string) ( $track['name'] ?? '' ) ), home_url( '/find-a-tutor/' ) ),
					'icon'        => 'book',
				];
			}
		}

		$cards = [];
		foreach ( $rows as $row ) {
			$name = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$slug = sanitize_title( (string) ( $row['slug'] ?? $name ) );
			$url  = (string) ( $row['url'] ?? '' );
			if ( '' === $url || is_wp_error( $url ) ) {
				$url = add_query_arg( 'subject', $slug, home_url( '/find-a-tutor/' ) );
			}
			$count = isset( $row['count'] ) ? (int) $row['count'] : 0;
			$cards[] = [
				'type'     => 'subject',
				'title'    => $name,
				'subtitle' => $count > 0
					? sprintf(
						/* translators: %d: tutor count */
						_n( '%d tutor', '%d tutors', $count, 'nextgen-3d-filmstrip' ),
						$count
					)
					: __( 'Explore tutors', 'nextgen-3d-filmstrip' ),
				'body'     => sanitize_text_field( (string) ( $row['description'] ?? $row['desc'] ?? '' ) ),
				'image'    => '',
				'icon'     => sanitize_key( (string) ( $row['icon'] ?? self::subject_icon( $name ) ) ),
				'url'      => esc_url_raw( $url ),
				'tags'     => [],
				'meta'     => '',
				'rating'   => '',
				'badge'    => __( 'Subject', 'nextgen-3d-filmstrip' ),
				'accent'   => self::accent_from_string( $name ),
			];
		}

		return $cards;
	}

	/**
	 * Manual lines: Title|Subtitle|ImageURL|LinkURL
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function manual( string $input, int $limit = 8 ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $input ) ?: [];
		$cards = [];
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 4 ) );
			$title = sanitize_text_field( $parts[0] ?? '' );
			if ( '' === $title ) {
				continue;
			}
			$cards[] = [
				'type'     => 'manual',
				'title'    => $title,
				'subtitle' => sanitize_text_field( $parts[1] ?? '' ),
				'body'     => '',
				'image'    => esc_url_raw( $parts[2] ?? '' ),
				'url'      => esc_url_raw( $parts[3] ?? home_url( '/find-a-tutor/' ) ),
				'tags'     => [],
				'meta'     => '',
				'rating'   => '',
				'badge'    => '',
				'accent'   => self::accent_from_string( $title ),
				'icon'     => self::subject_icon( $title ),
			];
		}
		return array_slice( $cards, 0, $limit );
	}

	public static function subject_icon( string $name ): string {
		$n = strtolower( $name );
		$map = [
			'math' => 'calculator', 'physical' => 'atom', 'physics' => 'atom', 'chem' => 'flask',
			'life' => 'leaf', 'bio' => 'leaf', 'english' => 'book', 'afrikaans' => 'message',
			'geo' => 'globe', 'history' => 'landmark', 'account' => 'chart', 'econ' => 'chart',
			'code' => 'code', 'computer' => 'code', 'program' => 'code', 'python' => 'code',
		];
		foreach ( $map as $k => $icon ) {
			if ( str_contains( $n, $k ) ) {
				return $icon;
			}
		}
		return 'book';
	}

	public static function accent_from_string( string $s ): string {
		$palette = [ '#059669', '#0ea5e9', '#f59e0b', '#6366f1', '#14b8a6', '#e11d48' ];
		return $palette[ abs( crc32( strtolower( $s ) ) ) % count( $palette ) ];
	}
}
