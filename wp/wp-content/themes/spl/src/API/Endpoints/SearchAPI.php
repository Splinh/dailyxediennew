<?php
/**
 * Search REST API Endpoint — AJAX live search for products.
 *
 * @package SPL\API\Endpoints
 * @author  HD
 */

namespace SPL\API\Endpoints;

use SPL\API\AbstractAPI;

defined( 'ABSPATH' ) || exit;

final class SearchAPI extends AbstractAPI {
	public const BYPASS_NONCE = true;

	/**
	 * Register REST routes for search.
	 *
	 * @return void
	 */
	protected function registerRoutes(): void {
		register_rest_route(
			REST_NAMESPACE,
			'/search',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'search' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'q' => [
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Search products and return formatted array for live search dropdown.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function search( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_query_params();
		$raw_q  = $params['q'] ?? $params['s'] ?? $request->get_param( 'q' ) ?? $request->get_param( 's' ) ?? '';
		$query  = sanitize_text_field( (string) $raw_q );
		// var_dump($query);
		if ( mb_strlen( $query ) < 2 ) {
			return $this->sendResponse(
				[
					'products' => [],
					'total'    => 0,
					'query'    => $query,
					'all_url'  => '',
				]
			);
		}

		$products = [];
		$matching_products = [];

		if ( function_exists( 'wc_get_products' ) ) {
			$matching_products = wc_get_products(
				[
					'status' => 'publish',
					'limit'  => 8,
					's'      => $query,
				]
			);
		}

		if ( empty( $matching_products ) ) {
			$wp_query = new \WP_Query(
				[
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 8,
					's'              => $query,
				]
			);
			$matching_products = array_filter( array_map( 'wc_get_product', wp_list_pluck( $wp_query->posts, 'ID' ) ) );
		}

		$total = count( $matching_products );

		foreach ( $matching_products as $product ) {
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$product_id = $product->get_id();

			// Thumbnail image
			$image_id  = $product->get_image_id();
			$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'thumbnail' ) : '' );

			// Category name
			$terms    = get_the_terms( $product_id, 'product_cat' );
			$cat_name = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';

			// Price & sale logic
			$price_html    = $product->get_price_html();
			$is_on_sale    = $product->is_on_sale();
			$discount_text = '';
			if ( $is_on_sale && (float) $product->get_regular_price() > 0 && (float) $product->get_sale_price() > 0 ) {
				$perc          = round( ( ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) / (float) $product->get_regular_price() ) * 100 );
				$discount_text = "-{$perc}%";
			}

			$products[] = [
				'id'            => $product_id,
				'title'         => $product->get_name(),
				'permalink'     => get_permalink( $product_id ),
				'image'         => $image_url,
				'price_html'    => $price_html,
				'is_on_sale'    => $is_on_sale,
				'discount_text' => $discount_text,
				'category'      => $cat_name,
			];
		}

		return $this->sendResponse(
			[
				'products' => $products,
				'total'    => count( $products ),
				'query'    => $query,
				'all_url'  => home_url( '/?s=' . rawurlencode( $query ) . '&post_type=product', 'relative' ),
			]
		);
	}
}
