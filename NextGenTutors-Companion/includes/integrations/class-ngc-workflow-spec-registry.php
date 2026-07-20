<?php
/**
 * Loads workflow JSON specifications from integrate/ with CRUD store.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical registry for WF-01..WF-05 integration specs.
 */
class NGC_Workflow_Spec_Registry {

	public const OPTION_KEY = 'ngc_workflow_spec_store';

	/** @var array<string, array<string, mixed>>|null */
	private static $cache = null;

	/**
	 * @return string
	 */
	public static function integrate_dir() {
		return trailingslashit( NGC_PLUGIN_DIR ) . 'integrate';
	}

	/**
	 * Content-pack catalog (Command Center v2 + Completion Suite).
	 *
	 * @return string
	 */
	public static function catalog_dir() {
		return trailingslashit( self::integrate_dir() ) . 'catalog';
	}

	/**
	 * Merged specs: bundled JSON files + persisted store (store wins).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$from_files = self::load_from_files();
		$from_store = self::load_from_store();
		self::$cache = array_merge( $from_files, $from_store );
		return self::$cache;
	}

	/**
	 * @param string $id Spec id e.g. workflow-03-reminder-notification.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		$id  = sanitize_key( (string) $id );
		$all = self::all();
		return $all[ $id ] ?? null;
	}

	/**
	 * Find first spec declaring an event.
	 *
	 * @param string $event Event slug.
	 * @return array<string, mixed>|null
	 */
	public static function spec_for_event( $event ) {
		$event = sanitize_text_field( (string) $event );
		foreach ( self::all() as $spec ) {
			$events = array_map( 'strval', (array) ( $spec['events'] ?? [] ) );
			if ( in_array( $event, $events, true ) ) {
				return $spec;
			}
		}
		return null;
	}

	/**
	 * @return array<int, string>
	 */
	public static function event_catalog() {
		$events = [];
		foreach ( self::all() as $spec ) {
			foreach ( (array) ( $spec['events'] ?? [] ) as $event ) {
				$events[] = (string) $event;
			}
		}
		return array_values( array_unique( $events ) );
	}

	/**
	 * Import workflow-*.json from integrate/ into the option store.
	 *
	 * @param bool $overwrite Replace existing stored specs.
	 * @return array{ok:bool,imported:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_from_integrate_dir( $overwrite = true ) {
		$dir = self::integrate_dir();
		if ( ! is_dir( $dir ) ) {
			return [
				'ok'       => false,
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => [ __( 'integrate/ directory missing.', 'nextgencompanion' ) ],
			];
		}

		$store     = self::get_store();
		$imported  = 0;
		$skipped   = 0;
		$errors    = [];

		foreach ( glob( $dir . '/workflow-*.json' ) as $path ) {
			$raw = file_get_contents( $path );
			if ( ! $raw ) {
				$errors[] = sprintf( 'Unreadable file: %s', basename( $path ) );
				continue;
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) || empty( $data['id'] ) ) {
				$errors[] = sprintf( 'Invalid JSON: %s', basename( $path ) );
				continue;
			}
			$id = sanitize_key( (string) $data['id'] );
			if ( ! $overwrite && isset( $store['specs'][ $id ] ) ) {
				++$skipped;
				continue;
			}
			$store['specs'][ $id ] = self::sanitize_spec( $data );
			++$imported;
		}

		$store['meta']['imported_at'] = current_time( 'mysql', true );
		$store['meta']['source']      = 'integrate_dir';
		self::save_store( $store );

		return [
			'ok'       => empty( $errors ),
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		];
	}

	/**
	 * Import workflow JSON from integrate/catalog (v2 + completion packs).
	 *
	 * @param bool $overwrite Replace existing stored specs.
	 * @return array{ok:bool,imported:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_from_catalog( $overwrite = true ) {
		$base = self::catalog_dir();
		if ( ! is_dir( $base ) ) {
			return [
				'ok'       => false,
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => [ __( 'integrate/catalog/ directory missing.', 'nextgencompanion' ) ],
			];
		}

		$store    = self::get_store();
		$imported = 0;
		$skipped  = 0;
		$errors   = [];
		$paths    = array_merge(
			(array) glob( $base . '/v2/*.json' ),
			(array) glob( $base . '/completion/*.json' )
		);

		foreach ( $paths as $path ) {
			$raw = file_get_contents( $path );
			if ( ! $raw ) {
				$errors[] = sprintf( 'Unreadable file: %s', basename( $path ) );
				continue;
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) || empty( $data['id'] ) ) {
				$errors[] = sprintf( 'Invalid JSON: %s', basename( $path ) );
				continue;
			}
			$data = self::normalize_catalog_spec( $data );
			$id   = sanitize_key( (string) $data['id'] );
			if ( ! $overwrite && isset( $store['specs'][ $id ] ) ) {
				++$skipped;
				continue;
			}
			$store['specs'][ $id ] = self::sanitize_spec( $data );
			++$imported;
		}

		$store['meta']['catalog_imported_at'] = current_time( 'mysql', true );
		self::save_store( $store );

		return [
			'ok'       => empty( $errors ),
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		];
	}

	/**
	 * Create a new stored spec.
	 *
	 * @param array<string, mixed> $spec Spec payload.
	 * @return array{ok:bool,spec?:array<string,mixed>,message?:string}
	 */
	public static function create( $spec ) {
		$spec = self::sanitize_spec( $spec );
		if ( empty( $spec['id'] ) ) {
			return [ 'ok' => false, 'message' => __( 'Spec id is required.', 'nextgencompanion' ) ];
		}
		$id    = $spec['id'];
		$store = self::get_store();
		if ( isset( $store['specs'][ $id ] ) ) {
			return [ 'ok' => false, 'message' => __( 'Spec already exists. Use update instead.', 'nextgencompanion' ) ];
		}
		$spec['updated_at']        = current_time( 'mysql', true );
		$store['specs'][ $id ]     = $spec;
		$store['meta']['updated']  = current_time( 'mysql', true );
		self::save_store( $store );
		return [ 'ok' => true, 'spec' => $spec ];
	}

	/**
	 * Update an existing stored spec.
	 *
	 * @param string               $id   Spec id.
	 * @param array<string, mixed> $spec Partial or full spec.
	 * @return array{ok:bool,spec?:array<string,mixed>,message?:string}
	 */
	public static function update( $id, $spec ) {
		$id = sanitize_key( (string) $id );
		if ( ! $id ) {
			return [ 'ok' => false, 'message' => __( 'Invalid spec id.', 'nextgencompanion' ) ];
		}
		$store = self::get_store();
		$base  = $store['specs'][ $id ] ?? self::load_from_files()[ $id ] ?? [];
		if ( empty( $base ) && empty( $spec['id'] ) ) {
			return [ 'ok' => false, 'message' => __( 'Spec not found.', 'nextgencompanion' ) ];
		}
		$merged = self::sanitize_spec( array_merge( $base, $spec, [ 'id' => $id ] ) );
		$merged['updated_at']     = current_time( 'mysql', true );
		$store['specs'][ $id ]    = $merged;
		$store['meta']['updated'] = current_time( 'mysql', true );
		self::save_store( $store );
		return [ 'ok' => true, 'spec' => $merged ];
	}

	/**
	 * Delete a stored spec (bundled file specs remain readable).
	 *
	 * @param string $id Spec id.
	 * @return array{ok:bool,deleted?:bool,message?:string}
	 */
	public static function delete( $id ) {
		$id = sanitize_key( (string) $id );
		$store = self::get_store();
		if ( empty( $store['specs'][ $id ] ) ) {
			return [ 'ok' => false, 'message' => __( 'Stored spec not found.', 'nextgencompanion' ) ];
		}
		unset( $store['specs'][ $id ] );
		$store['meta']['updated'] = current_time( 'mysql', true );
		self::save_store( $store );
		return [ 'ok' => true, 'deleted' => true ];
	}

	/**
	 * Export stored spec back to integrate/workflow file.
	 *
	 * @param string $id Spec id.
	 * @return array{ok:bool,path?:string,message?:string}
	 */
	public static function export_to_integrate_file( $id ) {
		$spec = self::get( $id );
		if ( ! $spec ) {
			return [ 'ok' => false, 'message' => __( 'Spec not found.', 'nextgencompanion' ) ];
		}
		$filename = $id . '.json';
		if ( 0 !== strpos( $filename, 'workflow-' ) ) {
			$filename = 'workflow-' . $filename;
		}
		$path = self::integrate_dir() . '/' . $filename;
		$json = wp_json_encode( $spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( ! $json || false === file_put_contents( $path, $json . "\n" ) ) {
			return [ 'ok' => false, 'message' => __( 'Failed to write integrate file.', 'nextgencompanion' ) ];
		}
		self::flush_cache();
		return [ 'ok' => true, 'path' => $path ];
	}

	/**
	 * @return array{specs:array<string,array<string,mixed>>,meta:array<string,mixed>}
	 */
	public static function get_store() {
		$stored = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		if ( empty( $stored['specs'] ) || ! is_array( $stored['specs'] ) ) {
			$stored['specs'] = [];
		}
		if ( empty( $stored['meta'] ) || ! is_array( $stored['meta'] ) ) {
			$stored['meta'] = [];
		}
		return $stored;
	}

	/**
	 * Validate integrate pack is present and parseable.
	 *
	 * @return array{ok:bool,specs:int,events:int,missing:array<int,string>,stored:int}
	 */
	public static function verify() {
		$expected = [
			'workflow-01-tutor-onboarding',
			'workflow-02-booking-payment',
			'workflow-03-reminder-notification',
			'workflow-04-review-rating',
			'workflow-05-tutor-payout',
		];
		$all     = self::all();
		$missing = [];
		foreach ( $expected as $id ) {
			if ( empty( $all[ $id ] ) ) {
				$missing[] = $id;
			}
		}
		$store = self::get_store();
		return [
			'ok'      => empty( $missing ),
			'specs'   => count( $all ),
			'events'  => count( self::event_catalog() ),
			'missing' => $missing,
			'stored'  => count( $store['specs'] ),
		];
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function load_from_files() {
		$specs = [];
		$dir   = self::integrate_dir();
		if ( is_dir( $dir ) ) {
			foreach ( glob( $dir . '/workflow-*.json' ) as $path ) {
				$raw = file_get_contents( $path );
				if ( ! $raw ) {
					continue;
				}
				$data = json_decode( $raw, true );
				if ( ! is_array( $data ) || empty( $data['id'] ) ) {
					continue;
				}
				$specs[ sanitize_key( (string) $data['id'] ) ] = self::sanitize_spec( $data );
			}
		}

		$catalog = self::catalog_dir();
		if ( is_dir( $catalog ) ) {
			$paths = array_merge(
				(array) glob( $catalog . '/v2/*.json' ),
				(array) glob( $catalog . '/completion/*.json' )
			);
			foreach ( $paths as $path ) {
				$raw = file_get_contents( $path );
				if ( ! $raw ) {
					continue;
				}
				$data = json_decode( $raw, true );
				if ( ! is_array( $data ) || empty( $data['id'] ) ) {
					continue;
				}
				$data = self::normalize_catalog_spec( $data );
				$specs[ sanitize_key( (string) $data['id'] ) ] = self::sanitize_spec( $data );
			}
		}

		return $specs;
	}

	/**
	 * Normalize Command Center v2 / Completion Suite JSON into integrate spec shape.
	 *
	 * @param array<string, mixed> $spec Raw catalog JSON.
	 * @return array<string, mixed>
	 */
	private static function normalize_catalog_spec( $spec ) {
		if ( ! empty( $spec['schema'] ) && 'nextgen.workflow.v2' === $spec['schema'] ) {
			$trigger = $spec['trigger']['event'] ?? '';
			$steps   = [];
			foreach ( (array) ( $spec['actions'] ?? [] ) as $action ) {
				if ( is_array( $action ) && ! empty( $action['name'] ) ) {
					$steps[] = (string) $action['name'];
				}
			}
			$spec['events']        = array_filter( [ (string) $trigger ] );
			$spec['trigger']       = (string) $trigger;
			$spec['steps']         = $steps;
			$spec['description']   = (string) ( $spec['notes'] ?? '' );
			$spec['business_goal'] = (string) ( $spec['name'] ?? '' );
		} elseif ( is_string( $spec['trigger'] ?? null ) && empty( $spec['events'] ) ) {
			$trigger         = (string) $spec['trigger'];
			$spec['events']  = [ $trigger ];
			$spec['steps']   = array_values(
				array_map(
					static function ( $action ) {
						return is_string( $action ) ? $action : (string) ( $action['name'] ?? '' );
					},
					(array) ( $spec['actions'] ?? [] )
				)
			);
			$spec['description'] = sprintf(
				/* translators: %s: import target plugin name */
				__( 'Imported from %s content pack.', 'nextgencompanion' ),
				(string) ( $spec['import_target'] ?? 'Completion Suite' )
			);
		}
		return $spec;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function load_from_store() {
		$store = self::get_store();
		$specs = [];
		foreach ( $store['specs'] as $id => $spec ) {
			if ( is_array( $spec ) && ! empty( $spec['id'] ) ) {
				$specs[ sanitize_key( (string) $spec['id'] ) ] = $spec;
			}
		}
		return $specs;
	}

	/**
	 * @param array<string, mixed> $store Store payload.
	 */
	private static function save_store( $store ) {
		update_option( self::OPTION_KEY, $store, false );
		self::flush_cache();
	}

	/**
	 * @param array<string, mixed> $spec Raw spec.
	 * @return array<string, mixed>
	 */
	private static function sanitize_spec( $spec ) {
		$clean = [
			'id'            => sanitize_key( (string) ( $spec['id'] ?? '' ) ),
			'name'          => sanitize_text_field( (string) ( $spec['name'] ?? '' ) ),
			'description'   => sanitize_textarea_field( (string) ( $spec['description'] ?? '' ) ),
			'business_goal' => sanitize_text_field( (string) ( $spec['business_goal'] ?? '' ) ),
			'trigger'       => sanitize_text_field( (string) ( $spec['trigger'] ?? '' ) ),
		];
		if ( ! empty( $spec['events'] ) && is_array( $spec['events'] ) ) {
			$clean['events'] = array_values(
				array_filter(
					array_map(
						static function ( $event ) {
							return sanitize_text_field( (string) $event );
						},
						$spec['events']
					)
				)
			);
		}
		foreach ( [ 'actors', 'steps', 'seed_data' ] as $list_key ) {
			if ( ! empty( $spec[ $list_key ] ) && is_array( $spec[ $list_key ] ) ) {
				$clean[ $list_key ] = array_values(
					array_map(
						static function ( $item ) {
							return sanitize_text_field( (string) $item );
						},
						$spec[ $list_key ]
					)
				);
			}
		}
		if ( ! empty( $spec['updated_at'] ) ) {
			$clean['updated_at'] = sanitize_text_field( (string) $spec['updated_at'] );
		}
		return $clean;
	}

	/**
	 * Clear in-memory cache.
	 */
	public static function flush_cache() {
		self::$cache = null;
	}
}
