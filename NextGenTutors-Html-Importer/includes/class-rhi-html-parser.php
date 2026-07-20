<?php
/**
 * HTML parser — extracts main page content from static files.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses HTML files and strips chrome.
 */
class RHI_Html_Parser {

	/** @var string */
	private $file_path;

	/** @var string */
	private $base_dir;

	/**
	 * @param string $file_path Absolute path to HTML file.
	 */
	public function __construct( $file_path ) {
		$this->file_path = wp_normalize_path( $file_path );
		$this->base_dir  = wp_normalize_path( dirname( $file_path ) );
	}

	/**
	 * Parse file and return metadata + content.
	 *
	 * @return array<string, mixed>
	 */
	public function parse() {
		$raw = file_get_contents( $this->file_path );
		if ( false === $raw ) {
			return [
				'title'       => '',
				'h1'          => '',
				'content'     => '',
				'hero_image'  => '',
				'image_count' => 0,
				'has_form'    => false,
				'has_dynamic_js' => false,
				'parse_error' => 'Could not read file.',
			];
		}

		$hash = hash( 'sha256', $raw );

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$loaded = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $raw, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		if ( ! $loaded ) {
			return [
				'title'       => $this->extract_title_regex( $raw ),
				'h1'          => '',
				'content'     => '',
				'hero_image'  => '',
				'image_count' => 0,
				'has_form'    => (bool) preg_match( '/<form\b/i', $raw ),
				'has_dynamic_js' => (bool) preg_match( '/getElementById|dir-grid|hero-canvas/i', $raw ),
				'source_hash' => $hash,
				'parse_error' => 'DOM parse failed — used regex fallback.',
			];
		}

		$xpath = new DOMXPath( $dom );

		$title = '';
		$titles = $dom->getElementsByTagName( 'title' );
		if ( $titles->length ) {
			$title = trim( $titles->item( 0 )->textContent );
		}

		$h1 = '';
		$h1_nodes = $xpath->query( '//h1' );
		if ( $h1_nodes && $h1_nodes->length ) {
			$h1 = trim( $h1_nodes->item( 0 )->textContent );
		}

		$this->remove_chrome( $dom, $xpath );

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$content = '';
		if ( $body ) {
			$content = $this->inner_html( $body );
		}

		$content = $this->clean_content( $content );
		$images  = $this->extract_images( $content );
		$hero    = $images[0]['src'] ?? '';

		return [
			'title'          => $title,
			'h1'             => $h1,
			'meta_description' => $this->meta_content( $dom, 'description' ),
			'content'        => $content,
			'hero_image'     => $hero,
			'image_count'    => count( $images ),
			'images'         => $images,
			'has_form'       => (bool) preg_match( '/<form\b/i', $content ),
			'has_dynamic_js' => (bool) preg_match( '/id="(dir-grid|hero-canvas|filter-subjects)"/i', $content ),
			'source_hash'    => $hash,
			'parse_error'    => '',
		];
	}

	/**
	 * @param DOMDocument $dom   DOM.
	 * @param DOMXPath    $xpath XPath.
	 */
	private function remove_chrome( DOMDocument $dom, DOMXPath $xpath ) {
		$remove_queries = [
			'//script',
			'//noscript',
			'//header[contains(@class,"site-header")]',
			'//footer[contains(@class,"site-footer")]',
			'//nav[contains(@class,"main-nav")]',
		];
		foreach ( $remove_queries as $query ) {
			$nodes = $xpath->query( $query );
			if ( ! $nodes ) {
				continue;
			}
			$to_remove = [];
			foreach ( $nodes as $node ) {
				$to_remove[] = $node;
			}
			foreach ( $to_remove as $node ) {
				if ( $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}
	}

	/**
	 * @param string $html Raw inner HTML.
	 * @return string
	 */
	private function clean_content( $html ) {
		// Remove animation/data attributes.
		$html = preg_replace( '/\s+data-[a-z0-9_-]+="[^"]*"/i', '', $html ) ?: $html;
		$html = preg_replace( '/\s+data-[a-z0-9_-]+=\'[^\']*\'/i', '', $html ) ?: $html;
		$html = preg_replace( '/\s+id="(hero-canvas|dir-grid|filter-subjects|hero-search|contact-form)"/i', '', $html ) ?: $html;

		// Empty dynamic grids (populated by JS).
		$html = preg_replace( '/<div[^>]*class="[^"]*dir-grid[^"]*"[^>]*>\s*<\/div>/i', '<p class="rhi-review-note"><em>[Tutor directory loads dynamically — add ngc shortcode or keep theme template.]</em></p>', $html ) ?: $html;

		// Replace static forms with shortcode hints where known.
		$shortcode_map = [
			'contact-form'        => '[ngc_contact_support_form]',
			'hero-search'         => '[ngc_find_tutor_form]',
			'become-tutor-form'   => '[ngc_become_tutor_form]',
		];
		foreach ( $shortcode_map as $form_id => $shortcode ) {
			if ( false !== strpos( $html, 'id="' . $form_id . '"' ) ) {
				$html = preg_replace(
					'/<form[^>]*id="' . preg_quote( $form_id, '/' ) . '"[^>]*>[\s\S]*?<\/form>/i',
					'<div class="ngt-shortcode-region">' . $shortcode . '</div>',
					$html
				) ?: $html;
			}
		}

		return trim( $html );
	}

	/**
	 * @param string $html HTML.
	 * @return array<int, array<string, string>>
	 */
	private function extract_images( $html ) {
		$images = [];
		if ( preg_match_all( '/<img[^>]+src=(["\'])([^"\']+)\1[^>]*>/i', $html, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $match ) {
				$tag = $match[0];
				$src = $match[2];
				$alt = '';
				if ( preg_match( '/alt=(["\'])([^"\']*)\1/i', $tag, $am ) ) {
					$alt = $am[2];
				}
				$images[] = [
					'src' => $src,
					'alt' => $alt,
					'alt_inferred' => '' === $alt ? '1' : '0',
				];
			}
		}
		return $images;
	}

	/**
	 * @param DOMDocument $dom  DOM.
	 * @param string      $name Meta name.
	 * @return string
	 */
	private function meta_content( DOMDocument $dom, $name ) {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( '//meta[@name="' . $name . '"]' );
		if ( $nodes && $nodes->length ) {
			$node = $nodes->item( 0 );
			if ( $node instanceof DOMElement ) {
				return trim( (string) $node->getAttribute( 'content' ) );
			}
		}
		return '';
	}

	/**
	 * @param string $raw Raw HTML.
	 * @return string
	 */
	private function extract_title_regex( $raw ) {
		if ( preg_match( '/<title[^>]*>([^<]+)<\/title>/i', $raw, $m ) ) {
			return trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		}
		return '';
	}

	/**
	 * @param DOMNode $node Node.
	 * @return string
	 */
	private function inner_html( DOMNode $node ) {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return $html;
	}
}
