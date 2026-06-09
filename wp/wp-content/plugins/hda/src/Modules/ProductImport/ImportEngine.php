<?php
/**
 * Import Engine – Process parsed WXR items into WooCommerce products.
 *
 * Handles: product creation (simple/variable/grouped/external),
 * variations, taxonomy terms, product attributes (pa_*),
 * featured/gallery images (sideload), and ACF repeater tskt_rows.
 *
 * @package HDAddons\Modules\ProductImport
 */

namespace HDAddons\Modules\ProductImport;

defined( 'ABSPATH' ) || exit;

final class ImportEngine {

	/** @var array<int, int> Old ID → New ID map */
	private array $idMap = [];

	/**
	 * Run the import.
	 *
	 * @param array $items       Parsed items from ImportParser.
	 * @param bool  $importImgs  Whether to download remote images.
	 * @param bool  $skipExist   Skip products with matching slug.
	 * @return array{created: int, skipped: int, errors: string[], images: int, tskt: int, variations: int, log: string[]}
	 */
	public function run( array $items, bool $importImgs, bool $skipExist ): array {
		$results = [
			'created'    => 0,
			'skipped'    => 0,
			'errors'     => [],
			'images'     => 0,
			'tskt'       => 0,
			'variations' => 0,
			'log'        => [],
		];

		// Separate by type.
		$products    = [];
		$variations  = [];
		$attachments = [];

		foreach ( $items as $item ) {
			match ( $item['post_type'] ) {
				'product'           => $products[]    = $item,
				'product_variation' => $variations[]   = $item,
				'attachment'        => $attachments[]  = $item,
				default             => null,
			};
		}

		// Build old_id → attachment_url map.
		$attUrlMap = [];
		foreach ( $attachments as $att ) {
			$attUrlMap[ $att['post_id'] ] = $att['attachment_url'];
		}

		// ── Import Products ──
		foreach ( $products as $item ) {
			$result = $this->importProduct( $item, $attUrlMap, $importImgs, $skipExist );

			if ( 'created' === $result['status'] ) {
				++$results['created'];
				$this->idMap[ $item['post_id'] ] = $result['new_id'];
				$results['log'][]                = '✅ ' . $item['title'];
				$results['images']              += $result['images'];
				if ( $result['tskt'] > 0 ) {
					++$results['tskt'];
				}
			} elseif ( 'skipped' === $result['status'] ) {
				++$results['skipped'];
				$this->idMap[ $item['post_id'] ] = $result['existing_id'];
				$results['log'][]                = '⏭️ ' . $item['title'] . ' (đã tồn tại)';
			} else {
				$results['errors'][] = $item['title'] . ': ' . $result['error'];
				$results['log'][]    = '❌ ' . $item['title'] . ': ' . $result['error'];
			}
		}

		// ── Import Variations ──
		foreach ( $variations as $var ) {
			$parentNewId = $this->idMap[ $var['post_parent'] ] ?? 0;
			if ( ! $parentNewId ) {
				$results['log'][] = '⚠️ Biến thể "' . $var['title'] . '" – không tìm thấy SP cha';
				continue;
			}

			$result = $this->importVariation( $var, $parentNewId, $attUrlMap, $importImgs );
			if ( 'created' === $result['status'] ) {
				++$results['variations'];
				$results['log'][] = '  ↳ Biến thể: ' . $var['title'];
			} else {
				$results['log'][] = '  ⚠️ Biến thể lỗi: ' . ( $result['error'] ?? 'unknown' );
			}
		}

		return $results;
	}

	// ── Product Import ──────────────────────────────

	/**
	 * @return array{status: string, new_id?: int, existing_id?: int, tskt?: int, images?: int, error?: string}
	 */
	private function importProduct( array $item, array $attUrlMap, bool $importImgs, bool $skipExist ): array {
		// Check existing by slug.
		if ( $skipExist ) {
			$existing = get_page_by_path( $item['post_name'], OBJECT, 'product' );
			if ( $existing ) {
				return [ 'status' => 'skipped', 'existing_id' => $existing->ID ];
			}
		}

		// Determine product type.
		$productType = 'simple';
		foreach ( $item['terms'] as $t ) {
			if ( 'product_type' === $t['domain'] ) {
				$productType = $t['slug'];
				break;
			}
		}

		$product = match ( $productType ) {
			'variable' => new \WC_Product_Variable(),
			'grouped'  => new \WC_Product_Grouped(),
			'external' => new \WC_Product_External(),
			default    => new \WC_Product_Simple(),
		};

		$product->set_name( $item['title'] );
		$product->set_slug( $item['post_name'] );
		$product->set_status( $item['status'] );
		$product->set_description( $item['post_content'] );
		$product->set_short_description( $item['post_excerpt'] );
		$product->set_menu_order( $item['menu_order'] );

		if ( ! empty( $item['post_date'] ) ) {
			$product->set_date_created( $item['post_date'] );
		}

		$meta = $item['meta'];
		$this->applyProductMeta( $product, $meta );
		$this->applyTaxonomies( $product, $item['terms'], $productType );

		$newId = $product->save();
		if ( ! $newId ) {
			return [ 'status' => 'error', 'error' => 'Không thể tạo sản phẩm' ];
		}

		$imagesCount = 0;
		if ( $importImgs ) {
			$imagesCount = $this->importImages( $newId, $meta, $attUrlMap );
		}

		$tsktCount = $this->importTskt( $newId, $meta );
		$this->importCustomMeta( $newId, $meta );

		return [
			'status' => 'created',
			'new_id' => $newId,
			'tskt'   => $tsktCount,
			'images' => $imagesCount,
		];
	}

	// ── Variation Import ────────────────────────────

	/**
	 * @return array{status: string, new_id?: int, error?: string}
	 */
	private function importVariation( array $item, int $parentId, array $attUrlMap, bool $importImgs ): array {
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parentId );
		$variation->set_status( $item['status'] );
		$variation->set_menu_order( $item['menu_order'] );

		if ( ! empty( $item['post_date'] ) ) {
			$variation->set_date_created( $item['post_date'] );
		}

		$meta = $item['meta'];

		if ( isset( $meta['_regular_price'][0] ) ) {
			$variation->set_regular_price( $meta['_regular_price'][0] );
		}
		if ( isset( $meta['_sale_price'][0] ) ) {
			$variation->set_sale_price( $meta['_sale_price'][0] );
		}
		if ( ! empty( $meta['_sku'][0] ) && ! wc_get_product_id_by_sku( $meta['_sku'][0] ) ) {
			$variation->set_sku( $meta['_sku'][0] );
		}
		if ( isset( $meta['_stock_status'][0] ) ) {
			$variation->set_stock_status( $meta['_stock_status'][0] );
		}
		if ( isset( $meta['_manage_stock'][0] ) && 'yes' === $meta['_manage_stock'][0] ) {
			$variation->set_manage_stock( true );
			if ( isset( $meta['_stock'][0] ) ) {
				$variation->set_stock_quantity( (int) $meta['_stock'][0] );
			}
		}

		// Variation attributes (attribute_pa_*).
		$attrs = [];
		foreach ( $meta as $key => $vals ) {
			if ( str_starts_with( $key, 'attribute_' ) ) {
				$attrs[ $key ] = $vals[0];
			}
		}
		if ( ! empty( $attrs ) ) {
			$variation->set_attributes( $attrs );
		}

		$variation->set_description( $item['post_excerpt'] );

		$newId = $variation->save();
		if ( ! $newId ) {
			return [ 'status' => 'error', 'error' => 'Không thể tạo biến thể' ];
		}

		// Featured image for variation.
		if ( $importImgs && ! empty( $meta['_thumbnail_id'][0] ) ) {
			$oldThumbId = (int) $meta['_thumbnail_id'][0];
			$imgUrl     = $attUrlMap[ $oldThumbId ] ?? '';
			if ( $imgUrl ) {
				$newAttId = $this->sideloadImage( $imgUrl, $newId );
				if ( $newAttId ) {
					set_post_thumbnail( $newId, $newAttId );
				}
			}
		}

		$this->importCustomMeta( $newId, $meta );

		return [ 'status' => 'created', 'new_id' => $newId ];
	}

	// ── WC Meta Helpers ─────────────────────────────

	private function applyProductMeta( \WC_Product $product, array $meta ): void {
		if ( ! empty( $meta['_sku'][0] ) && ! wc_get_product_id_by_sku( $meta['_sku'][0] ) ) {
			$product->set_sku( $meta['_sku'][0] );
		}
		if ( isset( $meta['_regular_price'][0] ) ) {
			$product->set_regular_price( $meta['_regular_price'][0] );
		}
		if ( isset( $meta['_sale_price'][0] ) ) {
			$product->set_sale_price( $meta['_sale_price'][0] );
		}
		if ( isset( $meta['_weight'][0] ) ) {
			$product->set_weight( $meta['_weight'][0] );
		}
		if ( isset( $meta['_stock_status'][0] ) ) {
			$product->set_stock_status( $meta['_stock_status'][0] );
		}
		if ( isset( $meta['_manage_stock'][0] ) && 'yes' === $meta['_manage_stock'][0] ) {
			$product->set_manage_stock( true );
			if ( isset( $meta['_stock'][0] ) ) {
				$product->set_stock_quantity( (int) $meta['_stock'][0] );
			}
		}
		if ( isset( $meta['_virtual'][0] ) ) {
			$product->set_virtual( 'yes' === $meta['_virtual'][0] );
		}
		if ( isset( $meta['_downloadable'][0] ) ) {
			$product->set_downloadable( 'yes' === $meta['_downloadable'][0] );
		}
	}

	private function applyTaxonomies( \WC_Product $product, array $terms, string $productType ): void {
		$catIds  = [];
		$tagIds  = [];
		$attrMap = [];

		foreach ( $terms as $t ) {
			$domain = $t['domain'];

			if ( 'category' === $domain || 'product_cat' === $domain ) {
				$term = get_term_by( 'slug', $t['slug'], 'product_cat' );
				if ( ! $term ) {
					$result = wp_insert_term( $t['name'], 'product_cat', [ 'slug' => $t['slug'] ] );
					if ( ! is_wp_error( $result ) ) {
						$catIds[] = $result['term_id'];
					}
				} else {
					$catIds[] = $term->term_id;
				}
			} elseif ( 'product_tag' === $domain ) {
				$term = get_term_by( 'slug', $t['slug'], 'product_tag' );
				if ( ! $term ) {
					$result = wp_insert_term( $t['name'], 'product_tag', [ 'slug' => $t['slug'] ] );
					if ( ! is_wp_error( $result ) ) {
						$tagIds[] = $result['term_id'];
					}
				} else {
					$tagIds[] = $term->term_id;
				}
			} elseif ( str_starts_with( $domain, 'pa_' ) ) {
				$this->ensureAttribute( $domain );
				$term = get_term_by( 'slug', $t['slug'], $domain );
				if ( ! $term ) {
					$result = wp_insert_term( $t['name'], $domain, [ 'slug' => $t['slug'] ] );
					if ( ! is_wp_error( $result ) ) {
						$attrMap[ $domain ][] = $result['term_id'];
					}
				} else {
					$attrMap[ $domain ][] = $term->term_id;
				}
			}
		}

		if ( $catIds ) {
			$product->set_category_ids( $catIds );
		}
		if ( $tagIds ) {
			$product->set_tag_ids( $tagIds );
		}

		if ( ! empty( $attrMap ) ) {
			$attributes = [];
			$position   = 0;

			foreach ( $attrMap as $taxonomy => $termIds ) {
				$attr = new \WC_Product_Attribute();
				$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
				$attr->set_name( $taxonomy );
				$attr->set_options( $termIds );
				$attr->set_position( $position++ );
				$attr->set_visible( true );
				$attr->set_variation( 'variable' === $productType );
				$attributes[] = $attr;
			}

			$product->set_attributes( $attributes );
		}
	}

	// ── Image Import ────────────────────────────────

	private function importImages( int $postId, array $meta, array $attUrlMap ): int {
		$count = 0;

		// Featured image.
		if ( ! empty( $meta['_thumbnail_id'][0] ) ) {
			$oldId  = (int) $meta['_thumbnail_id'][0];
			$imgUrl = $attUrlMap[ $oldId ] ?? '';
			if ( $imgUrl ) {
				$newAttId = $this->sideloadImage( $imgUrl, $postId );
				if ( $newAttId ) {
					set_post_thumbnail( $postId, $newAttId );
					++$count;
				}
			}
		}

		// Gallery.
		if ( ! empty( $meta['_product_image_gallery'][0] ) ) {
			$oldGalleryIds = array_map( 'intval', explode( ',', $meta['_product_image_gallery'][0] ) );
			$newGalleryIds = [];

			foreach ( $oldGalleryIds as $oldGid ) {
				$imgUrl = $attUrlMap[ $oldGid ] ?? '';
				if ( $imgUrl ) {
					$newAttId = $this->sideloadImage( $imgUrl, $postId );
					if ( $newAttId ) {
						$newGalleryIds[] = $newAttId;
						++$count;
					}
				}
			}

			if ( ! empty( $newGalleryIds ) ) {
				update_post_meta( $postId, '_product_image_gallery', implode( ',', $newGalleryIds ) );
			}
		}

		return $count;
	}

	/**
	 * Download and attach a remote image.
	 *
	 * @return int|false New attachment ID or false on failure.
	 */
	private function sideloadImage( string $url, int $postId ): int|false {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Avoid duplicate downloads.
		$existing = get_posts( [
			'post_type'   => 'attachment',
			'meta_key'    => '_spl_original_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => $url,                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'numberposts' => 1,
			'fields'      => 'ids',
		] );

		if ( ! empty( $existing ) ) {
			return $existing[0];
		}

		$attId = media_sideload_image( $url, $postId, null, 'id' );
		if ( is_wp_error( $attId ) ) {
			return false;
		}

		update_post_meta( $attId, '_spl_original_url', $url );

		return $attId;
	}

	// ── ACF TSKT ────────────────────────────────────

	/**
	 * Import ACF repeater tskt_rows.
	 *
	 * @return int Number of rows imported.
	 */
	private function importTskt( int $postId, array $meta ): int {
		$rows = [];
		$idx  = 0;

		while ( isset( $meta[ "tskt_rows_{$idx}_tskt_label" ] ) ) {
			$rows[] = [
				'tskt_label' => $meta[ "tskt_rows_{$idx}_tskt_label" ][0] ?? '',
				'tskt_value' => $meta[ "tskt_rows_{$idx}_tskt_value" ][0] ?? '',
			];
			++$idx;
		}

		if ( empty( $rows ) ) {
			return 0;
		}

		// ACF API preferred.
		if ( function_exists( 'update_field' ) ) {
			update_field( 'tskt_rows', $rows, $postId );
			return count( $rows );
		}

		// Fallback: raw meta (ACF-compatible structure).
		$count = count( $rows );
		update_post_meta( $postId, 'tskt_rows', $count );
		update_post_meta( $postId, '_tskt_rows', 'field_tskt_rows' );

		foreach ( $rows as $i => $row ) {
			update_post_meta( $postId, "tskt_rows_{$i}_tskt_label", $row['tskt_label'] );
			update_post_meta( $postId, "tskt_rows_{$i}_tskt_value", $row['tskt_value'] );
			update_post_meta( $postId, "_tskt_rows_{$i}_tskt_label", 'field_tskt_label' );
			update_post_meta( $postId, "_tskt_rows_{$i}_tskt_value", 'field_tskt_value' );
		}

		return $count;
	}

	// ── Custom Meta ─────────────────────────────────

	/**
	 * Import non-WC custom meta (ACF fields, etc.).
	 * Skips WC internal keys and already-processed keys.
	 */
	private function importCustomMeta( int $postId, array $meta ): void {
		$skipPrefixes = [
			'_sku', '_regular_price', '_sale_price', '_price', '_weight',
			'_stock', '_stock_status', '_manage_stock', '_virtual', '_downloadable',
			'_thumbnail_id', '_product_image_gallery', '_product_attributes',
			'_variation_description', '_children', '_default_attributes',
			'attribute_', 'tskt_rows', '_tskt_rows',
			'_edit_lock', '_edit_last', '_wp_',
			'_wc_', 'total_sales', '_product_version',
		];

		foreach ( $meta as $key => $values ) {
			$skip = false;
			foreach ( $skipPrefixes as $prefix ) {
				if ( str_starts_with( $key, $prefix ) ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}

			// Skip ACF reference keys that pair with a data key.
			if ( str_starts_with( $key, '_' ) && isset( $meta[ ltrim( $key, '_' ) ] ) ) {
				continue;
			}

			foreach ( $values as $val ) {
				update_post_meta( $postId, $key, maybe_unserialize( $val ) );
			}

			// Preserve ACF reference key.
			$refKey = '_' . $key;
			if ( isset( $meta[ $refKey ] ) ) {
				foreach ( $meta[ $refKey ] as $refVal ) {
					update_post_meta( $postId, $refKey, $refVal );
				}
			}
		}
	}

	// ── Attribute Helper ────────────────────────────

	/**
	 * Register a product attribute taxonomy if it doesn't exist.
	 */
	private function ensureAttribute( string $taxonomy ): void {
		$slug  = str_replace( 'pa_', '', $taxonomy );
		$label = ucfirst( str_replace( '-', ' ', $slug ) );

		global $wpdb;
		$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
				$slug
			)
		);

		if ( ! $exists ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				[
					'attribute_name'    => $slug,
					'attribute_label'   => $label,
					'attribute_type'    => 'select',
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 0,
				]
			);
			delete_transient( 'wc_attribute_taxonomies' );
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, 'product', [
				'label'        => $label,
				'hierarchical' => false,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => [ 'slug' => $slug ],
			] );
		}
	}
}
