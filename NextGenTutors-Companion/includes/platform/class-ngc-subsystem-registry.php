<?php
/**
 * RAD Subsystem Registry — loads architecture/manifests/*.json.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers and validates subsystem manifests (fail closed on invalid).
 */
final class NGC_Subsystem_Registry {

	public const OPTION_LAST_LOAD = 'ngc_rad_subsystem_registry';

	/**
	 * @var array<string, array<string, mixed>>
	 */
	private static $subsystems = [];

	/**
	 * @var array<int, string>
	 */
	private static $errors = [];

	/**
	 * @var bool
	 */
	private static $loaded = false;

	/**
	 * Bootstrap.
	 */
	public static function init() {
		self::load();
	}

	/**
	 * Resolve architecture/ root (monorepo or filter override).
	 *
	 * @return string Absolute path with trailing slash semantics via trailingslashit when WP available.
	 */
	public static function architecture_root() {
		$override = apply_filters( 'ngc_rad_architecture_root', '' );
		if ( is_string( $override ) && $override !== '' && is_dir( $override ) ) {
			return rtrim( $override, '/\\' );
		}

		// Companion lives at <repo>/NextGenTutors-Companion — architecture at <repo>/architecture.
		$candidate = dirname( NGC_PLUGIN_DIR ) . DIRECTORY_SEPARATOR . 'architecture';
		if ( is_dir( $candidate ) ) {
			return $candidate;
		}

		// Fallback: WP content sibling monorepo checkout.
		$alt = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'architecture';
		if ( is_dir( $alt ) ) {
			return $alt;
		}

		return $candidate;
	}

	/**
	 * Load and validate manifests.
	 */
	public static function load() {
		self::$subsystems = [];
		self::$errors     = [];
		$dir              = self::architecture_root() . DIRECTORY_SEPARATOR . 'manifests';
		if ( ! is_dir( $dir ) ) {
			self::$errors[] = 'Manifests directory missing: ' . $dir;
			self::$loaded   = true;
			update_option( self::OPTION_LAST_LOAD, self::snapshot(), false );
			return;
		}

		$files = glob( $dir . DIRECTORY_SEPARATOR . '*.json' ) ?: [];
		foreach ( $files as $file ) {
			$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $raw ) {
				self::$errors[] = 'Unreadable manifest: ' . $file;
				continue;
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				self::$errors[] = 'Invalid JSON: ' . $file;
				continue;
			}
			$err = self::validate_manifest( $data );
			if ( $err ) {
				self::$errors[] = basename( $file ) . ': ' . $err . ' (registration refused)';
				continue;
			}
			$id = (string) $data['system']['id'];
			if ( isset( self::$subsystems[ $id ] ) ) {
				self::$errors[] = 'Duplicate subsystem id: ' . $id;
				continue;
			}
			self::$subsystems[ $id ] = $data;
		}

		self::$loaded = true;
		update_option( self::OPTION_LAST_LOAD, self::snapshot(), false );
	}

	/**
	 * Minimal required-field validation (PHP-side fail-closed).
	 *
	 * @param array<string, mixed> $data Manifest.
	 * @return string Empty if OK.
	 */
	private static function validate_manifest( array $data ) {
		if ( ( $data['bridgeManifestVersion'] ?? '' ) !== '1.0' ) {
			return 'bridgeManifestVersion must be 1.0';
		}
		foreach ( [ 'system', 'runtime', 'lifecycle', 'capabilities', 'contracts', 'permissions', 'data', 'dependencies', 'health', 'observability', 'configuration', 'compatibility' ] as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				return 'missing object: ' . $key;
			}
		}
		foreach ( [ 'id', 'name', 'version', 'description', 'owner' ] as $key ) {
			if ( empty( $data['system'][ $key ] ) ) {
				return 'missing system.' . $key;
			}
		}
		if ( empty( $data['health']['readiness'] ) || empty( $data['health']['liveness'] ) ) {
			return 'missing health readiness/liveness';
		}
		return '';
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		if ( ! self::$loaded ) {
			self::load();
		}
		return self::$subsystems;
	}

	/**
	 * @param string $id Subsystem id.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		$all = self::all();
		return $all[ $id ] ?? null;
	}

	/**
	 * @return array<int, string>
	 */
	public static function errors() {
		if ( ! self::$loaded ) {
			self::load();
		}
		return self::$errors;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function snapshot() {
		return [
			'loaded_at'   => gmdate( 'c' ),
			'count'       => count( self::$subsystems ),
			'subsystem_ids' => array_keys( self::$subsystems ),
			'errors'      => self::$errors,
			'root'        => self::architecture_root(),
		];
	}
}
