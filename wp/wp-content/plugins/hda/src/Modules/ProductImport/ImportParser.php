<?php
/**
 * WXR XML Parser – Extract products, variations, attachments from WordPress eXtended RSS.
 *
 * @package HDAddons\Modules\ProductImport
 */

namespace HDAddons\Modules\ProductImport;

defined( 'ABSPATH' ) || exit;

final class ImportParser {

	/**
	 * Parse WXR XML content into a structured array.
	 *
	 * @param string $xml_content Raw XML string.
	 * @return array<int, array{
	 *     post_id: int,
	 *     title: string,
	 *     post_type: string,
	 *     post_name: string,
	 *     status: string,
	 *     post_parent: int,
	 *     post_date: string,
	 *     post_content: string,
	 *     post_excerpt: string,
	 *     menu_order: int,
	 *     attachment_url: string,
	 *     meta: array<string, list<string>>,
	 *     terms: array<int, array{domain: string, slug: string, name: string}>
	 * }>
	 */
	public static function parse( string $xml_content ): array {
		$internal = libxml_use_internal_errors( true );

		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $xml_content );

		$xpath = new \DOMXPath( $dom );
		$xpath->registerNamespace( 'wp', 'http://wordpress.org/export/1.2/' );
		$xpath->registerNamespace( 'dc', 'http://purl.org/dc/elements/1.1/' );
		$xpath->registerNamespace( 'content', 'http://purl.org/rss/1.0/modules/content/' );
		$xpath->registerNamespace( 'excerpt', 'http://wordpress.org/export/1.2/excerpt/' );

		$items = [];
		$nodes = $xpath->query( '//channel/item' );

		if ( ! $nodes ) {
			libxml_use_internal_errors( $internal );
			return [];
		}

		foreach ( $nodes as $node ) {
			$item = [
				'post_id'        => (int) self::xpathValue( $xpath, 'wp:post_id', $node ),
				'title'          => self::xpathValue( $xpath, 'title', $node ),
				'post_type'      => self::xpathValue( $xpath, 'wp:post_type', $node ),
				'post_name'      => self::xpathValue( $xpath, 'wp:post_name', $node ),
				'status'         => self::xpathValue( $xpath, 'wp:status', $node ),
				'post_parent'    => (int) self::xpathValue( $xpath, 'wp:post_parent', $node ),
				'post_date'      => self::xpathValue( $xpath, 'wp:post_date', $node ),
				'post_content'   => self::xpathValue( $xpath, 'content:encoded', $node ),
				'post_excerpt'   => self::xpathValue( $xpath, 'excerpt:encoded', $node ),
				'menu_order'     => (int) self::xpathValue( $xpath, 'wp:menu_order', $node ),
				'attachment_url' => self::xpathValue( $xpath, 'wp:attachment_url', $node ),
				'meta'           => [],
				'terms'          => [],
			];

			// Post meta.
			$meta_nodes = $xpath->query( 'wp:postmeta', $node );
			if ( $meta_nodes ) {
				foreach ( $meta_nodes as $meta_node ) {
					$key = self::xpathValue( $xpath, 'wp:meta_key', $meta_node );
					$val = self::xpathValue( $xpath, 'wp:meta_value', $meta_node );
					if ( $key ) {
						$item['meta'][ $key ][] = $val;
					}
				}
			}

			// Taxonomy terms (category elements).
			$term_nodes = $xpath->query( 'category', $node );
			if ( $term_nodes ) {
				foreach ( $term_nodes as $term_node ) {
					$item['terms'][] = [
						'domain' => $term_node->getAttribute( 'domain' ),
						'slug'   => $term_node->getAttribute( 'nicename' ),
						'name'   => $term_node->textContent,
					];
				}
			}

			$items[] = $item;
		}

		libxml_use_internal_errors( $internal );

		return $items;
	}

	/**
	 * Get single XPath text value.
	 */
	private static function xpathValue( \DOMXPath $xpath, string $query, \DOMNode $context ): string {
		$nodes = $xpath->query( $query, $context );
		return ( $nodes && $nodes->length ) ? $nodes->item( 0 )->textContent : '';
	}
}
