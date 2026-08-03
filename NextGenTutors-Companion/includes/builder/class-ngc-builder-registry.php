<?php
/**
 * Extensible node-type registry for Visual Builder.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers built-in + third-party node types and inspector controls.
 */
class NGC_Builder_Registry {

	/** @var array<string, array<string, mixed>> */
	private static $types = [];

	/** @var array<string, array<string, mixed>> */
	private static $controls = [];

	/**
	 * Bootstrap defaults.
	 */
	public static function init() {
		self::register_defaults();
		/**
		 * Allow plugins to register node types.
		 */
		do_action( 'ngc_builder_register' );
	}

	/**
	 * @param string               $type Type id.
	 * @param array<string, mixed> $def  Definition.
	 */
	public static function register_node_type( $type, array $def ) {
		self::$types[ sanitize_key( $type ) ] = $def;
	}

	/**
	 * @param string               $name Control name.
	 * @param array<string, mixed> $def  Definition (handle, label, schema).
	 */
	public static function register_control( $name, array $def ) {
		self::$controls[ sanitize_key( $name ) ] = $def;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function node_types() {
		return self::$types;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function controls() {
		return self::$controls;
	}

	/**
	 * Built-in types covering Phases 1–5 taxonomy.
	 */
	private static function register_defaults() {
		self::register_node_type(
			'container',
			[
				'label'   => __( 'Container', 'nextgencompanion' ),
				'phase'   => 1,
				'accepts' => [ 'container', 'theme.section', 'ui.component', 'primitive', 'dynamic', 'chrome' ],
				'layout'  => [ 'flex', 'grid', 'absolute' ],
			]
		);
		self::register_node_type(
			'theme.section',
			[
				'label' => __( 'Theme Section', 'nextgencompanion' ),
				'phase' => 1,
			]
		);
		self::register_node_type(
			'ui.component',
			[
				'label' => __( 'UI Component', 'nextgencompanion' ),
				'phase' => 2,
			]
		);
		self::register_node_type(
			'primitive',
			[
				'label'    => __( 'Primitive', 'nextgencompanion' ),
				'phase'    => 2,
				'variants' => [ 'text', 'image', 'button', 'video', 'svg', 'icon', 'lottie', 'divider' ],
			]
		);
		self::register_node_type(
			'dynamic',
			[
				'label'    => __( 'Dynamic', 'nextgencompanion' ),
				'phase'    => 5,
				'variants' => [ 'query_loop', 'acf_field', 'meta_box', 'woo_product', 'cpt', 'repeater' ],
			]
		);
		self::register_node_type(
			'chrome',
			[
				'label'    => __( 'Chrome', 'nextgencompanion' ),
				'phase'    => 4,
				'variants' => [ 'header', 'footer', 'mega_menu', 'popup' ],
			]
		);

		self::register_control( 'layout', [ 'label' => 'Layout', 'phase' => 2 ] );
		self::register_control( 'typography', [ 'label' => 'Typography', 'phase' => 1 ] );
		self::register_control( 'fills', [ 'label' => 'Fills', 'phase' => 3 ] );
		self::register_control( 'effects', [ 'label' => 'Effects', 'phase' => 3 ] );
		self::register_control( 'motion', [ 'label' => 'Motion', 'phase' => 3 ] );
		self::register_control( 'interactions', [ 'label' => 'Interactions', 'phase' => 4 ] );
		self::register_control( 'bindings', [ 'label' => 'Dynamic Data', 'phase' => 5 ] );
		self::register_control( 'visibility', [ 'label' => 'Visibility', 'phase' => 4 ] );
	}
}

/**
 * Procedural helpers matching the architecture API surface.
 *
 * @param string               $type Type.
 * @param array<string, mixed> $def  Definition.
 */
function ngc_builder_register_node_type( $type, array $def ) {
	NGC_Builder_Registry::register_node_type( $type, $def );
}

/**
 * @param string               $name Name.
 * @param array<string, mixed> $def  Definition.
 */
function ngc_builder_register_control( $name, array $def ) {
	NGC_Builder_Registry::register_control( $name, $def );
}
