<?php
/**
 * Compile builder documents into HTML/CSS/Interactivity payloads.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Presentation compiler — never stores inline CSS as source of truth.
 */
class NGC_Builder_Compiler {

	/**
	 * @param array<string, mixed> $document Document.
	 * @return array{html: string, css: string, hash: string, interactivity: array, meta: array}
	 */
	public static function compile( array $document ) {
		$document = NGC_Builder_Document::normalize( $document );
		$tokens   = NGC_Builder_Tokens::all();
		$css_parts = [ NGC_Builder_Tokens::to_css( $tokens ) ];
		$ix        = [];
		$html      = self::compile_node( $document['rootId'], $document, $tokens, $css_parts, $ix );
		$css       = implode( "\n", array_filter( $css_parts ) );
		$hash      = substr( hash( 'sha256', $css . $html ), 0, 12 );

		$payload = [
			'html'          => $html,
			'css'           => $css,
			'hash'          => $hash,
			'interactivity' => $ix,
			'meta'          => [
				'documentId' => $document['id'],
				'kind'       => $document['kind'],
			],
		];

		/**
		 * Filter compiled builder output.
		 *
		 * @param array $payload Compiled payload.
		 * @param array $document Document.
		 */
		return apply_filters( 'ngc_builder_compile_document', $payload, $document );
	}

	/**
	 * @param string               $node_id   Node id.
	 * @param array<string, mixed> $document  Document.
	 * @param array<string, mixed> $tokens    Tokens.
	 * @param array<int, string>   $css_parts CSS accumulator.
	 * @param array                $ix        Interactivity accumulator.
	 * @return string
	 */
	private static function compile_node( $node_id, array $document, array $tokens, array &$css_parts, array &$ix ) {
		$node = $document['nodes'][ $node_id ] ?? null;
		if ( ! is_array( $node ) ) {
			return '';
		}

		$when = $node['visibility']['when'] ?? 'always';
		if ( 'never' === $when ) {
			return '';
		}

		/**
		 * Filter a single compiled node HTML.
		 *
		 * @param string|null $html Precomputed HTML or null to use default.
		 * @param array       $node Node.
		 * @param array       $document Document.
		 */
		$filtered = apply_filters( 'ngc_builder_compile_node', null, $node, $document );
		if ( is_string( $filtered ) ) {
			return $filtered;
		}

		$class = 'ngc-b ngc-b--' . sanitize_html_class( str_replace( '.', '-', (string) $node['type'] ) );
		if ( ! empty( $node['classes'] ) && is_array( $node['classes'] ) ) {
			foreach ( $node['classes'] as $c ) {
				$class .= ' ' . sanitize_html_class( (string) $c );
			}
		}
		$scope = 'ngc-b-' . sanitize_html_class( $node_id );
		$class .= ' ' . $scope;

		$rule = self::style_rules( $scope, $node, $tokens, $document['breakpoints'] ?? [] );
		if ( $rule ) {
			$css_parts[] = $rule;
		}
		if ( ! empty( $node['customCss'] ) && is_string( $node['customCss'] ) ) {
			$css_parts[] = '/* node ' . $scope . " */\n" . $node['customCss'];
		}

		if ( ! empty( $node['interactions'] ) && is_array( $node['interactions'] ) ) {
			$ix[ $node_id ] = $node['interactions'];
		}

		$type = (string) ( $node['type'] ?? 'container' );

		if ( 'theme.section' === $type ) {
			return self::compile_theme_section( $node, $class );
		}

		if ( 'ui.component' === $type ) {
			$cid = sanitize_text_field( (string) ( $node['component'] ?? $node['props']['componentId'] ?? '' ) );
			$inner = $cid ? do_shortcode( '[ng_ui_component id="' . esc_attr( $cid ) . '"]' ) : '';
			return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '">' . $inner . '</div>';
		}

		if ( 'primitive' === $type ) {
			return self::compile_primitive( $node, $class, $node_id );
		}

		if ( 'dynamic' === $type ) {
			return NGC_Builder_Dynamics::compile( $node, $class, $node_id );
		}

		$tag = sanitize_key( (string) ( $node['tag'] ?? 'div' ) );
		if ( ! in_array( $tag, [ 'div', 'section', 'main', 'header', 'footer', 'nav', 'article', 'aside', 'ul', 'ol', 'li' ], true ) ) {
			$tag = 'div';
		}

		$inner = '';
		foreach ( (array) ( $node['children'] ?? [] ) as $child_id ) {
			$inner .= self::compile_node( (string) $child_id, $document, $tokens, $css_parts, $ix );
		}

		$attrs = ' class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '"';
		if ( ! empty( $ix[ $node_id ] ) ) {
			$attrs .= ' data-wp-interactive="ngc/builder" data-wp-context="' . esc_attr( wp_json_encode( [ 'nodeId' => $node_id ] ) ) . '"';
		}

		return '<' . $tag . $attrs . '>' . $inner . '</' . $tag . '>';
	}

	/**
	 * @param array<string, mixed> $node  Node.
	 * @param string               $class Class list.
	 * @return string
	 */
	private static function compile_theme_section( array $node, $class ) {
		$host = NGC_Visual_Builder::host();
		$sid  = (string) ( $node['component'] ?? ( 'home.' . ( $node['props']['sectionKey'] ?? '' ) ) );
		$props = is_array( $node['props'] ?? null ) ? $node['props'] : [];

		if ( ! empty( $node['contentRef'] ) && class_exists( 'NGC_Section_CMS' ) ) {
			$page    = sanitize_key( (string) ( $node['contentRef']['pageKey'] ?? 'home' ) );
			$section = sanitize_key( (string) ( $node['contentRef']['sectionKey'] ?? '' ) );
			if ( $section ) {
				$props['content'] = NGC_Section_CMS::get_section( $page, $section );
			}
		}

		$html = '';
		if ( $host instanceof NGC_Builder_Host ) {
			$html = $host->render_section( $sid, $props );
		} else {
			$html = '<!-- ngc-builder: missing host for ' . esc_html( $sid ) . ' -->';
		}

		return '<section class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node['id'] ) . '" data-ngc-section="' . esc_attr( $sid ) . '">' . $html . '</section>';
	}

	/**
	 * @param array<string, mixed> $node    Node.
	 * @param string               $class   Classes.
	 * @param string               $node_id Id.
	 * @return string
	 */
	private static function compile_primitive( array $node, $class, $node_id ) {
		$variant = sanitize_key( (string) ( $node['props']['variant'] ?? 'text' ) );
		$props   = is_array( $node['props'] ?? null ) ? $node['props'] : [];

		switch ( $variant ) {
			case 'image':
				$aid = (int) ( $props['attachmentId'] ?? 0 );
				$alt = esc_attr( (string) ( $props['alt'] ?? '' ) );
				if ( $aid ) {
					$img = wp_get_attachment_image( $aid, 'large', false, [ 'loading' => 'lazy', 'decoding' => 'async', 'alt' => $alt ] );
					return '<figure class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '">' . $img . '</figure>';
				}
				$url = esc_url( (string) ( $props['src'] ?? '' ) );
				return '<figure class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '"><img src="' . $url . '" alt="' . $alt . '" loading="lazy" decoding="async" /></figure>';

			case 'button':
				$label = esc_html( (string) ( $props['label'] ?? 'Button' ) );
				$href  = esc_url( (string) ( $props['href'] ?? '#' ) );
				return '<a class="' . esc_attr( $class ) . ' ngc-b-btn" data-ngc-node="' . esc_attr( $node_id ) . '" href="' . $href . '">' . $label . '</a>';

			case 'video':
				$url = esc_url( (string) ( $props['src'] ?? '' ) );
				return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '"><video src="' . $url . '" playsinline muted loop autoplay></video></div>';

			case 'lottie':
				$url = esc_url( (string) ( $props['src'] ?? '' ) );
				return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '" data-ngc-lottie="' . $url . '"></div>';

			case 'svg':
			case 'icon':
				$markup = (string) ( $props['svg'] ?? '' );
				return '<div class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '">' . wp_kses_post( $markup ) . '</div>';

			case 'divider':
				return '<hr class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '" />';

			case 'text':
			default:
				$tag  = sanitize_key( (string) ( $props['tag'] ?? 'p' ) );
				$tag  = in_array( $tag, [ 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span' ], true ) ? $tag : 'p';
				$text = wp_kses_post( (string) ( $props['text'] ?? '' ) );
				return '<' . $tag . ' class="' . esc_attr( $class ) . '" data-ngc-node="' . esc_attr( $node_id ) . '">' . $text . '</' . $tag . '>';
		}
	}

	/**
	 * @param string               $scope       CSS scope class.
	 * @param array<string, mixed> $node        Node.
	 * @param array<string, mixed> $tokens      Tokens.
	 * @param array<string, mixed> $breakpoints Breakpoints.
	 * @return string
	 */
	private static function style_rules( $scope, array $node, array $tokens, array $breakpoints ) {
		$decls = self::decls_from_maps( $node['layout'] ?? [], $node['style'] ?? [], $tokens );
		$css   = '';
		if ( $decls ) {
			$css .= '.' . $scope . '{' . $decls . '}';
		}
		$responsive = is_array( $node['responsive'] ?? null ) ? $node['responsive'] : [];
		foreach ( [ 'tablet', 'mobile' ] as $bp ) {
			if ( empty( $responsive[ $bp ] ) || ! is_array( $responsive[ $bp ] ) ) {
				continue;
			}
			$max   = (int) ( $breakpoints[ $bp ] ?? ( 'tablet' === $bp ? 768 : 480 ) );
			$inner = self::decls_from_maps(
				$responsive[ $bp ]['layout'] ?? [],
				$responsive[ $bp ]['style'] ?? [],
				$tokens
			);
			if ( $inner ) {
				$css .= '@media (max-width:' . $max . 'px){.' . $scope . '{' . $inner . '}}';
			}
		}
		return $css;
	}

	/**
	 * @param array<string, mixed> $layout Layout map.
	 * @param array<string, mixed> $style  Style map.
	 * @param array<string, mixed> $tokens Tokens.
	 * @return string
	 */
	private static function decls_from_maps( array $layout, array $style, array $tokens ) {
		$map = [
			'display'        => 'display',
			'direction'      => 'flex-direction',
			'justify'        => 'justify-content',
			'align'          => 'align-items',
			'gap'            => 'gap',
			'wrap'           => 'flex-wrap',
			'gridTemplate'   => 'grid-template-columns',
			'position'       => 'position',
			'top'            => 'top',
			'right'          => 'right',
			'bottom'         => 'bottom',
			'left'           => 'left',
			'zIndex'         => 'z-index',
			'width'          => 'width',
			'height'         => 'height',
			'padding'        => 'padding',
			'paddingBlock'   => 'padding-block',
			'paddingInline'  => 'padding-inline',
			'margin'         => 'margin',
			'background'     => 'background',
			'backgroundImage'=> 'background-image',
			'color'          => 'color',
			'opacity'        => 'opacity',
			'border'         => 'border',
			'borderRadius'   => 'border-radius',
			'boxShadow'      => 'box-shadow',
			'filter'         => 'filter',
			'backdropFilter' => 'backdrop-filter',
			'transform'      => 'transform',
			'perspective'    => 'perspective',
			'fontFamily'     => 'font-family',
			'fontSize'       => 'font-size',
			'fontWeight'     => 'font-weight',
			'lineHeight'     => 'line-height',
			'letterSpacing'  => 'letter-spacing',
			'textShadow'     => 'text-shadow',
		];

		$decls = [];
		$merged = array_merge( $layout, $style );
		foreach ( $merged as $k => $v ) {
			if ( ! isset( $map[ $k ] ) ) {
				continue;
			}
			$val = $v;
			if ( is_array( $v ) && isset( $v['value'] ) ) {
				$unit = isset( $v['unit'] ) ? (string) $v['unit'] : '';
				$val  = $v['value'] . $unit;
			} elseif ( is_string( $v ) && 0 === strpos( $v, 'token:' ) ) {
				$val = NGC_Builder_Tokens::resolve( $v, $tokens );
			}
			if ( $val === '' || $val === null ) {
				continue;
			}
			$decls[] = $map[ $k ] . ':' . $val;
		}

		// Flex shorthand helpers.
		if ( isset( $layout['display'] ) && 'flex' === $layout['display'] && empty( $layout['direction'] ) ) {
			$decls[] = 'flex-direction:column';
		}

		return $decls ? implode( ';', $decls ) . ';' : '';
	}
}
