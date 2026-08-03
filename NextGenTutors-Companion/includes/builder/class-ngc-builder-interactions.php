<?php
/**
 * Interaction + chrome document helpers (Phase 4).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interaction presets and chrome document factories.
 */
class NGC_Builder_Interactions {

	/**
	 * Interaction catalog for the editor.
	 *
	 * @return array<string, mixed>
	 */
	public static function catalog() {
		return [
			'triggers' => [ 'click', 'hover', 'scroll', 'load', 'mouseMove' ],
			'actions'  => [
				'animate',
				'toggleClass',
				'openPopup',
				'closePopup',
				'scrollTo',
				'setState',
				'parallax',
			],
			'presets'  => [
				[
					'id'     => 'fade_in',
					'label'  => 'Entrance Fade',
					'trigger'=> 'load',
					'action' => 'animate',
					'config' => [ 'opacity' => [ 0, 1 ], 'duration' => 400 ],
				],
				[
					'id'     => 'hover_lift',
					'label'  => 'Hover Lift',
					'trigger'=> 'hover',
					'action' => 'animate',
					'config' => [ 'y' => -8, 'scale' => 1.02 ],
				],
				[
					'id'     => 'scroll_parallax',
					'label'  => 'Scroll Parallax',
					'trigger'=> 'scroll',
					'action' => 'parallax',
					'config' => [ 'speed' => 0.25 ],
				],
				[
					'id'     => 'open_popup',
					'label'  => 'Open Popup',
					'trigger'=> 'click',
					'action' => 'openPopup',
					'config' => [ 'popupId' => '' ],
				],
			],
			'chromeKinds' => [ 'header', 'footer', 'popup', 'mega_menu', 'template' ],
		];
	}

	/**
	 * Create a chrome document shell.
	 *
	 * @param string $kind  header|footer|popup|mega_menu|template.
	 * @param string $title Title.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_chrome( $kind, $title = '' ) {
		$kind = sanitize_key( $kind );
		if ( ! in_array( $kind, [ 'header', 'footer', 'popup', 'mega_menu', 'template', 'reusable' ], true ) ) {
			return new WP_Error( 'ngc_builder_kind', __( 'Invalid chrome kind.', 'nextgencompanion' ) );
		}
		$id  = 'doc_' . $kind . '_' . wp_generate_password( 6, false, false );
		$doc = NGC_Builder_Document::blank( $id, $title ?: ucfirst( $kind ) );
		$doc['kind'] = $kind;
		$doc['nodes']['root']['type'] = 'chrome';
		$doc['nodes']['root']['tag']  = in_array( $kind, [ 'header', 'footer', 'nav' ], true ) ? $kind : 'div';
		$doc['nodes']['root']['props']['chromeKind'] = $kind;
		return NGC_Builder_Repository::save( $doc, [ 'title' => $doc['meta']['title'], 'status' => 'draft' ] );
	}
}
