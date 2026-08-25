<?php
/**
 * WooCommerce Single Product Template.
 *
 * Overrides default single-product.php — matches website/single-product.html (sp-* markup).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Render a 5-star block (full / half) from an average rating.
 */
if ( ! function_exists( 'spl_render_stars' ) ) {
	function spl_render_stars( float $rating, string $extra_class = '' ): void {
		$star = '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>';
		echo '<div class="sp-stars ' . esc_attr( $extra_class ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$is_half = ( $rating >= $i - 0.75 && $rating < $i - 0.25 );
			$is_full = ( $rating >= $i - 0.25 );
			$cls     = $is_half ? ' class="sp-star--half"' : '';
			// An "empty" star still uses the same polygon; CSS colours it via fill defaults.
			echo '<svg viewBox="0 0 24 24"' . ( $is_full ? '' : $cls ) . '>' . $star . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
	}
}

/**
 * Render a themed product review form (matches sp-* design).
 *
 * Posts through the standard WP comment system with WooCommerce's `rating`
 * field so reviews are saved exactly like wc default review submissions.
 */
if ( ! function_exists( 'spl_render_review_form' ) ) {
	function spl_render_review_form( \WC_Product $product ): void {
		// Respect WooCommerce review settings.
		if ( 'no' === get_option( 'woocommerce_enable_reviews' ) ) {
			return;
		}

		$commenter      = wp_get_current_commenter();
		$rating_required = wc_review_ratings_enabled() && wc_review_ratings_required();
		$must_log_in     = get_option( 'comment_registration' ) && ! is_user_logged_in();

		?>
		<div class="sp-review-form" id="review_form_wrapper">
			<h3 class="sp-review-form__title"><?php esc_html_e( 'Viết đánh giá của bạn', 'spl' ); ?></h3>

			<?php if ( $must_log_in ) : ?>
				<p class="sp-review-form__login">
					<?php
					printf(
						/* translators: %s login URL */
						wp_kses_post( __( 'Bạn phải <a href="%s">đăng nhập</a> để viết đánh giá.', 'spl' ) ),
						esc_url( wp_login_url( get_permalink( $product->get_id() ) ) )
					);
					?>
				</p>
			<?php else : ?>
				<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" class="sp-review-form__form" id="commentform">

					<?php if ( wc_review_ratings_enabled() ) : ?>
						<div class="sp-review-form__field sp-review-form__rating">
							<label><?php esc_html_e( 'Đánh giá của bạn', 'spl' ); ?><?php echo $rating_required ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="sp-rating-input" role="radiogroup" aria-label="<?php esc_attr_e( 'Chọn số sao', 'spl' ); ?>">
								<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
									<input type="radio" id="rating-<?php echo (int) $i; ?>" name="rating" value="<?php echo (int) $i; ?>" <?php echo $rating_required ? 'required' : ''; ?> />
									<label for="rating-<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( _n( '%d sao', '%d sao', $i, 'spl' ), $i ) ); ?>">
										<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
									</label>
								<?php endfor; ?>
							</div>
						</div>
					<?php endif; ?>

					<div class="sp-review-form__field">
						<label for="comment"><?php esc_html_e( 'Nội dung đánh giá', 'spl' ); ?> <span class="required">*</span></label>
						<textarea id="comment" name="comment" rows="5" required placeholder="<?php esc_attr_e( 'Chia sẻ cảm nhận của bạn về sản phẩm...', 'spl' ); ?>"></textarea>
					</div>

					<?php if ( ! is_user_logged_in() ) : ?>
						<div class="sp-review-form__row">
							<div class="sp-review-form__field">
								<label for="author"><?php esc_html_e( 'Họ tên', 'spl' ); ?> <span class="required">*</span></label>
								<input id="author" name="author" type="text" value="<?php echo esc_attr( $commenter['comment_author'] ); ?>" required />
							</div>
							<div class="sp-review-form__field">
								<label for="email"><?php esc_html_e( 'Email', 'spl' ); ?> <span class="required">*</span></label>
								<input id="email" name="email" type="email" value="<?php echo esc_attr( $commenter['comment_author_email'] ); ?>" required />
							</div>
						</div>
						<p class="sp-review-form__note"><?php esc_html_e( 'Email của bạn sẽ không được hiển thị công khai.', 'spl' ); ?></p>
					<?php endif; ?>

					<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $product->get_id() ); ?>" id="comment_post_ID" />
					<input type="hidden" name="comment_parent" id="comment_parent" value="0" />

					<button type="submit" class="btn btn--primary sp-review-form__submit" name="submit">
						<svg class="icon" viewBox="0 0 24 24"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2z"/></svg>
						<?php esc_html_e( 'Gửi đánh giá', 'spl' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}

get_header();

while ( have_posts() ) :
	the_post();
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		continue;
	}

	$image_id   = $product->get_image_id();
	$image_url  = wp_get_attachment_image_url( $image_id, 'large' ) ?: wc_placeholder_img_src( 'large' );
	$full_src   = wp_get_attachment_image_src( $image_id, 'full' );
	$full_url   = ! empty( $full_src[0] ) ? $full_src[0] : $image_url;
	$gallery    = $product->get_gallery_image_ids();
	$is_sale     = $product->is_on_sale();
	$is_in_stock = $product->is_in_stock() && $product->get_stock_status() !== 'outofstock';
	$cats        = wp_get_post_terms( get_the_ID(), 'product_cat' );
	$cat_name    = ! empty( $cats ) ? $cats[0]->name : '';
	$cat_link    = ! empty( $cats ) ? get_term_link( $cats[0] ) : '';
	$hotline     = Helper::getField( 'hotline', 'option' ) ?: '0933 505 222';
	$hotline_tel = preg_replace( '/[^\d+]/', '', $hotline ) ?: '0933505222';

	$avg_rating   = (float) $product->get_average_rating();
	$review_count = (int) $product->get_review_count();
	$total_sales  = (int) get_post_meta( get_the_ID(), 'total_sales', true );

	// Retrieve ACF specifications & fallbacks from legacy/active fields
	$pid   = get_the_ID();
	$specs = Helper::getField( 'tskt_specs', $pid )
		?: Helper::getField( 'tskt_rows', $pid )
		?: Helper::getField( 'thong_so_ky_thuat', $pid )
		?: Helper::getField( 'bang_tskt', $pid )
		?: Helper::getField( 'tskt_list', $pid );

	$tskt_image = Helper::getField( 'tskt_image', $pid )
		?: Helper::getField( 'anh_tskt', $pid )
		?: Helper::getField( 'bang_tskt_image', $pid )
		?: Helper::getField( 'hda_tskt_image', $pid )
		?: Helper::getField( 'tskt_img', $pid )
		?: get_post_meta( $pid, 'tskt_image', true );

	$tskt_image_url = '';
	if ( is_array( $tskt_image ) ) {
		$tskt_image_url = $tskt_image['url'] ?? ( $tskt_image['sizes']['large'] ?? '' );
	} elseif ( is_numeric( $tskt_image ) && (int) $tskt_image > 0 ) {
		$tskt_image_url = wp_get_attachment_image_url( (int) $tskt_image, 'full' ) ?: '';
	} elseif ( is_string( $tskt_image ) && '' !== trim( $tskt_image ) ) {
		$tskt_image_url = trim( $tskt_image );
	}

	$tskt_content = Helper::getField( 'tskt_content', $pid )
		?: Helper::getField( 'noi_dung_tskt', $pid )
		?: Helper::getField( 'tskt_html', $pid );

	// Retrieve Product Video (YouTube / TikTok / Vimeo / MP4)
	$video_url_raw = Helper::getField( 'product_video', $pid )
		?: Helper::getField( 'video_youtube', $pid )
		?: Helper::getField( 'video_tiktok', $pid )
		?: Helper::getField( 'url_video', $pid )
		?: Helper::getField( 'video_url', $pid )
		?: Helper::getField( 'tskt_video', $pid )
		?: Helper::getField( 'video_tab', $pid )
		?: Helper::getField( 'video_review', $pid )
		?: get_post_meta( $pid, '_product_video_url', true )
		?: get_post_meta( $pid, 'video_url', true );

	$video_type      = '';
	$video_embed_url = '';
	$video_id        = '';

	if ( is_string( $video_url_raw ) && '' !== trim( $video_url_raw ) ) {
		$vurl = trim( $video_url_raw );
		if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $vurl, $v_matches ) ) {
			$video_type      = 'youtube';
			$video_id        = $v_matches[1];
			$video_embed_url = 'https://www.youtube.com/embed/' . $video_id;
		} elseif ( preg_match( '/tiktok\.com\/(?:@[^\/]+\/video\/|v\/)(\d+)/i', $vurl, $v_matches ) ) {
			$video_type      = 'tiktok';
			$video_id        = $v_matches[1];
			$video_embed_url = 'https://www.tiktok.com/embed/v2/' . $video_id;
		} elseif ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/i', $vurl, $v_matches ) ) {
			$video_type      = 'vimeo';
			$video_id        = $v_matches[1];
			$video_embed_url = 'https://player.vimeo.com/video/' . $video_id;
		} elseif ( preg_match( '/\.(mp4|webm)($|\?)/i', $vurl ) ) {
			$video_type      = 'video';
			$video_embed_url = $vurl;
		} elseif ( filter_var( $vurl, FILTER_VALIDATE_URL ) ) {
			$video_type      = 'iframe';
			$video_embed_url = $vurl;
		}
	}

	// Search specs or attributes for strip values
	$spec_power   = '';
	$spec_range   = '';
	$spec_speed   = '';
	$spec_battery = '';

	if ( ! empty( $specs ) && is_array( $specs ) ) {
		foreach ( $specs as $row ) {
			$label = strtolower( trim( (string) ( $row['tskt_label'] ?? $row['label'] ?? '' ) ) );
			$val   = trim( (string) ( $row['tskt_value'] ?? $row['value'] ?? '' ) );
			if ( strpos( $label, 'công suất' ) !== false || strpos( $label, 'động cơ' ) !== false ) {
				$spec_power = $val;
			} elseif ( strpos( $label, 'quãng đường' ) !== false || strpos( $label, 'đi được' ) !== false ) {
				$spec_range = $val;
			} elseif ( strpos( $label, 'vận tốc' ) !== false || strpos( $label, 'tốc độ' ) !== false ) {
				$spec_speed = $val;
			} elseif ( strpos( $label, 'ắc quy' ) !== false || strpos( $label, 'pin' ) !== false ) {
				$spec_battery = $val;
			}
		}
	}

	// Search in WC attributes if still empty
	if ( empty( $spec_power ) ) {
		$spec_power = $product->get_attribute( 'pa_dong-co' ) ?: $product->get_attribute( 'pa_cong-suat' );
	}
	if ( empty( $spec_range ) ) {
		$spec_range = $product->get_attribute( 'pa_quang-duong' ) ?: $product->get_attribute( 'pa_quang-duong-di-chuyen' );
	}
	if ( empty( $spec_speed ) ) {
		$spec_speed = $product->get_attribute( 'pa_van-toc' ) ?: $product->get_attribute( 'pa_toc-do-toi-da' );
	}
	if ( empty( $spec_battery ) ) {
		$spec_battery = $product->get_attribute( 'pa_ac-quy-pin' ) ?: $product->get_attribute( 'pa_ac-quy' ) ?: $product->get_attribute( 'pa_pin' );
	}

	// Fallback to mockup defaults
	$spec_power   = $spec_power ?: '1200 W';
	$spec_range   = $spec_range ?: '60-80 KM';
	$spec_speed   = $spec_speed ?: '50 KM/H';
	$spec_battery = $spec_battery ?: '60V – 22Ah';

	// Sale discount.
	$reg_price  = (float) $product->get_regular_price();
	$cur_price  = (float) $product->get_price();
	$sale_pct   = ( $is_sale && $reg_price > 0 ) ? round( ( ( $reg_price - $cur_price ) / $reg_price ) * 100 ) : 0;
	$saving_amt = ( $is_sale && $reg_price > $cur_price ) ? ( $reg_price - $cur_price ) : 0;

	$available_variations       = [];
	$variation_attributes       = [];
	$default_variation          = [];
	$default_variation_attrs    = [];
	$default_variation_id       = 0;
	$default_variation_price    = '';
	$default_variation_oldprice = '';

	if ( $product->is_type( 'variable' ) ) {
		/** @var WC_Product_Variable $product */
		$available_variations = $product->get_available_variations();
		$variation_attributes = $product->get_variation_attributes();

		foreach ( $available_variations as &$variation_data ) {
			if ( array_key_exists( 'display_price', $variation_data ) ) {
				$variation_data['spl_price_html'] = wc_price( (float) $variation_data['display_price'] );
			}

			$variation_data['spl_old_price_html'] = (
				isset( $variation_data['display_regular_price'], $variation_data['display_price'] )
				&& (float) $variation_data['display_regular_price'] > (float) $variation_data['display_price']
			)
				? wc_price( (float) $variation_data['display_regular_price'] )
				: '';
		}
		unset( $variation_data );

		$default_variation          = $available_variations[0] ?? [];
		$default_variation_attrs    = $default_variation['attributes'] ?? [];
		$default_variation_id       = absint( $default_variation['variation_id'] ?? 0 );
		$default_variation_price    = $default_variation['spl_price_html'] ?? '';
		$default_variation_oldprice = $default_variation['spl_old_price_html'] ?? '';

		// Retrieve default variation battery option
		if ( ! empty( $default_variation_attrs ) ) {
			foreach ( $default_variation_attrs as $attr_key => $attr_value ) {
				if ( strpos( $attr_key, 'ac-quy' ) !== false || strpos( $attr_key, 'pin' ) !== false ) {
					$taxonomy = str_replace( 'attribute_', '', $attr_key );
					if ( taxonomy_exists( $taxonomy ) ) {
						$term = get_term_by( 'slug', $attr_value, $taxonomy );
						if ( $term ) {
							$spec_battery = $term->name;
						}
					} else {
						$spec_battery = $attr_value;
					}
					break;
				}
			}
		}
	}

	// If battery contains commas (multiple options), take the first one for initial display
	if ( strpos( $spec_battery, ',' ) !== false ) {
		$battery_parts = explode( ',', $spec_battery );
		$spec_battery = trim( $battery_parts[0] );
	}
	// Keep battery name concise by stripping prefixes
	$spec_battery = preg_replace( '/^(Ắc-quy:|Pin Lithium:|Ắc quy:|Pin:)\s*/iu', '', $spec_battery );
	?>

	<!-- ===== BREADCRUMB ===== -->
	<div class="breadcrumb-bar">
		<div class="container">
			<nav class="breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
					<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
				</a>
				<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Sản phẩm', 'spl' ); ?></a>
				<?php if ( $cat_name && ! is_wp_error( $cat_link ) ) : ?>
					<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
					<a href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $cat_name ); ?></a>
				<?php endif; ?>
				<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<span class="breadcrumb__current"><?php echo esc_html( $product->get_name() ); ?></span>
			</nav>
		</div>
	</div>

	<!-- ===== PRODUCT DETAIL ===== -->
	<section class="sp-detail">
		<div class="container">
			<div class="sp-detail__grid">

				<!-- Gallery -->
				<?php
				$main_img_src = wp_get_attachment_image_src( $image_id, 'full' );
				$main_w       = ! empty( $main_img_src[1] ) ? $main_img_src[1] : 1920;
				$main_h       = ! empty( $main_img_src[2] ) ? $main_img_src[2] : 1280;
				?>
				<div class="sp-gallery reveal" data-fx-lightbox>
					<div class="sp-gallery__main" id="sp-gallery-main">
						<?php if ( $sale_pct > 0 ) : ?>
							<span class="sp-gallery__badge">-<?php echo (int) $sale_pct; ?>%</span>
						<?php endif; ?>
						<a href="<?php echo esc_url( $full_url ); ?>" id="sp-main-link" data-pswp-width="<?php echo (int) $main_w; ?>" data-pswp-height="<?php echo (int) $main_h; ?>">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" id="sp-main-img" data-large_image="<?php echo esc_url( $full_url ); ?>" data-large_image_width="<?php echo (int) $main_w; ?>" data-large_image_height="<?php echo (int) $main_h; ?>" />
						</a>
						<button class="sp-gallery__zoom" aria-label="<?php esc_attr_e( 'Phóng to ảnh', 'spl' ); ?>" id="sp-zoom-btn">
							<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
						</button>
						<button type="button" class="sp-gallery__nav sp-gallery__nav--prev" aria-label="<?php esc_attr_e( 'Ảnh trước', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
						</button>
						<button type="button" class="sp-gallery__nav sp-gallery__nav--next" aria-label="<?php esc_attr_e( 'Ảnh sau', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
						</button>
					</div>
					<?php
					$valid_gallery = [];
					if ( ! empty( $gallery ) && is_array( $gallery ) ) {
						foreach ( $gallery as $gal_id ) {
							$t_url = wp_get_attachment_image_url( $gal_id, 'thumbnail' );
							$l_url = wp_get_attachment_image_url( $gal_id, 'large' );
							$f_src = wp_get_attachment_image_src( $gal_id, 'full' );
							if ( $t_url && ! empty( $f_src[0] ) ) {
								$valid_gallery[] = [
									'id'          => $gal_id,
									'thumb_url'   => $t_url,
									'display_url' => $l_url ?: $f_src[0],
									'full_url'    => $f_src[0],
									'w'           => ! empty( $f_src[1] ) ? $f_src[1] : 1920,
									'h'           => ! empty( $f_src[2] ) ? $f_src[2] : 1280,
								];
							}
						}
					}
					$total_images = 1 + count( $valid_gallery );
					$use_slider   = $total_images > 6;
					
					if ( $use_slider ) :
						$swiper_options = [
							'slidesPerView' => 4,
							'spaceBetween'  => 8,
							'freeMode'      => true,
							'sm'            => [ 'slidesPerView' => 5 ],
							'lg'            => [ 'slidesPerView' => 6 ],
						];
						?>
						<div class="sp-gallery__thumbs swiper closest-swiper" id="sp-gallery-thumbs" data-fx-slider>
							<div class="swiper-wrapper" data-swiper-options="<?php echo esc_attr( wp_json_encode( $swiper_options ) ); ?>">
								<a href="<?php echo esc_url( $full_url ); ?>" class="sp-gallery__thumb swiper-slide active" data-img="<?php echo esc_url( $image_url ); ?>" data-pswp-width="<?php echo (int) $main_w; ?>" data-pswp-height="<?php echo (int) $main_h; ?>">
									<img src="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'thumbnail' ) ?: $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" data-large_image="<?php echo esc_url( $full_url ); ?>" data-large_image_width="<?php echo (int) $main_w; ?>" data-large_image_height="<?php echo (int) $main_h; ?>" onerror="this.closest('.sp-gallery__thumb')?.remove()" />
								</a>
								<?php foreach ( $valid_gallery as $i => $item ) : ?>
									<a href="<?php echo esc_url( $item['full_url'] ); ?>" class="sp-gallery__thumb swiper-slide" data-img="<?php echo esc_url( $item['display_url'] ); ?>" data-pswp-width="<?php echo (int) $item['w']; ?>" data-pswp-height="<?php echo (int) $item['h']; ?>">
										<img src="<?php echo esc_url( $item['thumb_url'] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Ảnh %d', 'spl' ), $i + 2 ) ); ?>" data-large_image="<?php echo esc_url( $item['full_url'] ); ?>" data-large_image_width="<?php echo (int) $item['w']; ?>" data-large_image_height="<?php echo (int) $item['h']; ?>" onerror="this.closest('.sp-gallery__thumb')?.remove()" />
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php else : ?>
						<div class="sp-gallery__thumbs" id="sp-gallery-thumbs">
							<a href="<?php echo esc_url( $full_url ); ?>" class="sp-gallery__thumb active" data-img="<?php echo esc_url( $image_url ); ?>" data-pswp-width="<?php echo (int) $main_w; ?>" data-pswp-height="<?php echo (int) $main_h; ?>">
								<img src="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'thumbnail' ) ?: $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" data-large_image="<?php echo esc_url( $full_url ); ?>" data-large_image_width="<?php echo (int) $main_w; ?>" data-large_image_height="<?php echo (int) $main_h; ?>" onerror="this.closest('.sp-gallery__thumb')?.remove()" />
							</a>
							<?php foreach ( $valid_gallery as $i => $item ) : ?>
								<a href="<?php echo esc_url( $item['full_url'] ); ?>" class="sp-gallery__thumb" data-img="<?php echo esc_url( $item['display_url'] ); ?>" data-pswp-width="<?php echo (int) $item['w']; ?>" data-pswp-height="<?php echo (int) $item['h']; ?>">
									<img src="<?php echo esc_url( $item['thumb_url'] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Ảnh %d', 'spl' ), $i + 2 ) ); ?>" data-large_image="<?php echo esc_url( $item['full_url'] ); ?>" data-large_image_width="<?php echo (int) $item['w']; ?>" data-large_image_height="<?php echo (int) $item['h']; ?>" onerror="this.closest('.sp-gallery__thumb')?.remove()" />
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<!-- Product Info -->
				<div class="sp-info reveal">
					<div class="sp-info__meta-row">
						<?php if ( $cat_name ) : ?>
							<span class="sp-info__category-badge"><?php echo esc_html( $cat_name ); ?></span>
						<?php endif; ?>
						<span class="sp-info__sku">SKU: <?php echo esc_html( $product->get_sku() ?: 'DXD-' . get_the_ID() ); ?></span>
					</div>

					<h1 class="sp-info__title"><?php echo esc_html( $product->get_name() ); ?></h1>

					<div class="sp-info__rating">
						<?php spl_render_stars( $avg_rating ?: 4.8 ); ?>
						<span class="sp-info__rating-text"><?php echo esc_html( number_format( $avg_rating ?: 4.8, 1 ) ); ?> (<?php echo (int) ( $review_count ?: 126 ); ?> <?php esc_html_e( 'đánh giá', 'spl' ); ?>)</span>
						<span class="sp-info__rating-sep">|</span>
						<span class="sp-info__sold">
							<svg class="icon text-emerald-500" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
							<?php echo esc_html( sprintf( __( 'Đã bán %s', 'spl' ), number_format_i18n( $total_sales ?: 1200 ) ) ); ?>
						</span>
					</div>

				<div class="sp-info__price-box" id="sp-price-box">
					<div class="price-row">
						<?php if ( ! $is_in_stock || empty( $cur_price ) || 0 == $cur_price ) : ?>
							<span class="sp-info__price sp-info__price--contact text-red-600 font-extrabold text-2xl md:text-3xl"><?php esc_html_e( 'Liên hệ', 'spl' ); ?></span>
						<?php elseif ( $product->is_type( 'variable' ) && $default_variation_price ) : ?>
							<span class="sp-info__price"><?php echo wp_kses_post( $default_variation_price ); ?></span>
							<?php if ( $default_variation_oldprice ) : ?>
								<span class="sp-info__old-price"><?php echo wp_kses_post( $default_variation_oldprice ); ?></span>
								<?php
								$reg_val = (float) preg_replace( '/[^\d]/', '', wp_strip_all_tags( $default_variation_oldprice ) );
								$cur_val = (float) preg_replace( '/[^\d]/', '', wp_strip_all_tags( $default_variation_price ) );
								if ( $reg_val > $cur_val ) {
									$savings = $reg_val - $cur_val;
									$savings_formatted = $savings >= 1000000 
										? number_format( $savings / 1000000, 1, '.', '' ) . 'tr'
										: number_format( $savings, 0, '', ',' ) . 'đ';
									$savings_formatted = str_replace( '.0', '', $savings_formatted );
									echo '<span class="sp-info__discount-tag">' . esc_html__( 'Tiết kiệm', 'spl' ) . ' ' . esc_html( $savings_formatted ) . '</span>';
								}
								?>
							<?php endif; ?>
						<?php elseif ( $product->is_type( 'variable' ) ) : ?>
							<span class="sp-info__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						<?php elseif ( $is_sale && $reg_price > 0 ) : ?>
							<span class="sp-info__price"><?php echo wp_kses_post( wc_price( $cur_price ) ); ?></span>
							<span class="sp-info__old-price"><?php echo wp_kses_post( wc_price( $reg_price ) ); ?></span>
							<?php if ( $saving_amt > 0 ) : ?>
								<?php
								$savings_formatted = $saving_amt >= 1000000 
									? number_format( $saving_amt / 1000000, 1, '.', '' ) . 'tr'
									: number_format( $saving_amt, 0, '', ',' ) . 'đ';
								$savings_formatted = str_replace( '.0', '', $savings_formatted );
								?>
								<span class="sp-info__discount-tag"><?php echo esc_html__( 'Tiết kiệm', 'spl' ) . ' ' . esc_html( $savings_formatted ); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<span class="sp-info__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! $is_in_stock ) : ?>
						<div class="sp-info__stock-alert flex items-center gap-2.5 p-3 bg-rose-50/90 border border-rose-200/90 rounded-xl text-rose-800 text-xs md:text-sm font-medium mt-3">
							<span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-rose-600 text-white text-[11px] font-black uppercase tracking-wider shadow-sm shrink-0"><?php esc_html_e( 'Hết hàng', 'spl' ); ?></span>
							<span><?php esc_html_e( 'Sản phẩm hiện đang tạm hết hàng hoặc ngừng kinh doanh. Quý khách vui lòng liên hệ hotline để được tư vấn sản phẩm tương đương.', 'spl' ); ?></span>
						</div>
					<?php else : ?>
						<!-- Promo strip matching mockup -->
						<div class="price-promo">
							<span class="price-promo__item price-promo__item--delivery">
								<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
								<strong class="text-emerald-600"><?php esc_html_e( 'Miễn phí giao hàng', 'spl' ); ?></strong>
							</span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Quick Specs Strip -->
				<div class="sp-specs-strip">
					<div class="sp-specs-strip__item">
						<div class="sp-specs-strip__icon sp-specs-strip__icon--blue">
							<svg class="icon" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
						</div>
						<span class="sp-specs-strip__value"><?php echo esc_html( $spec_power ); ?></span>
					</div>
					<div class="sp-specs-strip__item">
						<div class="sp-specs-strip__icon sp-specs-strip__icon--amber">
							<svg class="icon" viewBox="0 0 24 24"><path d="M2 22H22M8 22 10 2M16 22 14 2M12 2v3M12 9v4M12 17v3"/></svg>
						</div>
						<span class="sp-specs-strip__value"><?php echo esc_html( $spec_range ); ?></span>
					</div>
					<div class="sp-specs-strip__item">
						<div class="sp-specs-strip__icon sp-specs-strip__icon--red">
							<svg class="icon" viewBox="0 0 24 24"><path d="m12 14 4-4M3.34 19a10 10 0 1 1 17.32 0"/></svg>
						</div>
						<span class="sp-specs-strip__value"><?php echo esc_html( $spec_speed ); ?></span>
					</div>
					<div class="sp-specs-strip__item">
						<div class="sp-specs-strip__icon sp-specs-strip__icon--emerald">
							<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="6" width="18" height="12" rx="2" ry="2"/><line x1="23" y1="11" x2="23" y2="13"/></svg>
						</div>
						<span class="sp-specs-strip__value" id="spec-battery-val"><?php echo esc_html( $spec_battery ); ?></span>
					</div>
				</div>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="sp-info__short-desc">
						<?php
						$cleaned_desc = \SPL\Features\Optimizer\FlatsomeShortcodeCleaner::cleanShortcodes( $product->get_short_description() );
						echo wp_kses_post( wpautop( $cleaned_desc ) );
						?>
					</div>
				<?php endif; ?>

				<?php
				// ── Variation Selector ──────────────────────────
				if ( $product->is_type( 'variable' ) ) :
					?>
					<div class="sp-variations" id="sp-variations"
						data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
						data-variations="<?php echo esc_attr( wp_json_encode( $available_variations ) ); ?>">
						<?php foreach ( $variation_attributes as $attribute_name => $options ) :
							$attr_label = wc_attribute_label( $attribute_name );
							$attr_key   = 'attribute_' . sanitize_title( $attribute_name );
							?>
							<div class="sp-variations__field">
								<label class="sp-variations__label" for="<?php echo esc_attr( $attr_key ); ?>">
									<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
									<?php echo esc_html( $attr_label ); ?>: 
									<span class="sp-variations__label-value"></span>
								</label>
								<div class="sp-variations__options" data-attribute="<?php echo esc_attr( $attr_key ); ?>">
									<?php
									// Check if it's a taxonomy attribute.
									// Mặc định chọn biến thể đầu tiên của mỗi thuộc tính.
									if ( taxonomy_exists( $attribute_name ) ) {
										$terms          = wc_get_product_terms( $product->get_id(), $attribute_name, [ 'fields' => 'all' ] );
										$first_selected = false;
										$default_value  = (string) ( $default_variation_attrs[ $attr_key ] ?? '' );
										foreach ( $terms as $term ) {
											$in_options = in_array( $term->slug, $options, true );
											$is_active  = $in_options && ( $default_value ? $term->slug === $default_value : ! $first_selected );
											if ( $is_active ) {
												$first_selected = true;
											}
											echo '<button type="button" class="sp-variations__btn' . ( $is_active ? ' active' : '' ) . '" data-value="' . esc_attr( $term->slug ) . '">'
												. esc_html( $term->name )
												. '</button>';
										}
									} else {
										$first_selected = false;
										$default_value  = (string) ( $default_variation_attrs[ $attr_key ] ?? '' );
										foreach ( $options as $option ) {
											$is_active = ( $default_value ? (string) $option === $default_value : ! $first_selected );
											if ( $is_active ) {
												$first_selected = true;
											}
											echo '<button type="button" class="sp-variations__btn' . ( $is_active ? ' active' : '' ) . '" data-value="' . esc_attr( $option ) . '">'
												. esc_html( $option )
												. '</button>';
										}
									}
									?>
								</div>
							</div>
						<?php endforeach; ?>
						<input type="hidden" name="variation_id" id="sp-variation-id" value="<?php echo esc_attr( $default_variation_id ); ?>" />
						<div class="sp-variations__reset" id="sp-variations-reset" style="display:none;">
							<button type="button" class="sp-variations__clear">
								<svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
								<?php esc_html_e( 'Xóa lựa chọn', 'spl' ); ?>
							</button>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $is_in_stock ) : ?>
					<!-- Quantity -->
					<div class="sp-info__quantity">
						<label><?php esc_html_e( 'Số lượng:', 'spl' ); ?></label>
						<div class="sp-qty">
							<button class="sp-qty__btn" id="qty-minus" aria-label="<?php esc_attr_e( 'Giảm', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
							</button>
							<input type="number" class="sp-qty__input" value="1" min="1" max="99" id="qty-input" />
							<button class="sp-qty__btn" id="qty-plus" aria-label="<?php esc_attr_e( 'Tăng', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
							</button>
						</div>
					</div>

					<!-- Actions -->
					<div class="sp-info__actions">
						<button class="btn btn--primary btn--lg sp-add-cart" id="sp-add-cart"
							data-product-id="<?php echo esc_attr( get_the_ID() ); ?>"
							data-product-type="<?php echo esc_attr( $product->get_type() ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
							<?php esc_html_e( 'Thêm vào giỏ', 'spl' ); ?>
						</button>
						<button class="btn btn--accent btn--lg sp-buy-now" id="sp-buy-now"
							data-product-id="<?php echo esc_attr( get_the_ID() ); ?>"
							data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
							data-checkout="<?php echo esc_url( wc_get_checkout_url() ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
							<?php esc_html_e( 'Mua ngay', 'spl' ); ?>
						</button>
					</div>
				<?php else : ?>
					<!-- Out of stock Actions -->
					<div class="sp-info__actions sp-info__actions--outofstock flex flex-wrap gap-2.5 mt-4">
						<a href="tel:<?php echo esc_attr( $hotline_tel ); ?>" class="btn btn--primary btn--lg flex-1 min-h-[48px] rounded-xl flex items-center justify-center font-bold text-sm md:text-base shadow-md shadow-primary/20">
							<svg class="icon mr-2 w-5 h-5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
							<?php esc_html_e( 'Liên hệ tư vấn', 'spl' ); ?> (<?php echo esc_html( $hotline ); ?>)
						</a>
						<a href="https://zalo.me/<?php echo esc_attr( $hotline_tel ); ?>" target="_blank" rel="noopener noreferrer" class="btn btn--accent btn--lg px-6 min-h-[48px] rounded-xl flex items-center justify-center font-bold text-sm md:text-base bg-[#0068ff] hover:bg-[#0052cc] text-white shadow-md">
							<?php esc_html_e( 'Chat Zalo', 'spl' ); ?>
						</a>
					</div>
				<?php endif; ?>

					<!-- Trust badges (ACF options, with fallback) -->
					<?php
					$trust_items = Helper::getField( 'product_trust', 'option' );
					if ( ! is_array( $trust_items ) || empty( $trust_items ) ) {
						$trust_items = [
							[ 'icon' => 'truck', 'text' => __( 'Miễn phí ship từ 500K', 'spl' ) ],
							[ 'icon' => 'clock', 'text' => __( 'Giao hàng 1-3 ngày', 'spl' ) ],
							[ 'icon' => 'return', 'text' => __( 'Đổi trả trong 7 ngày', 'spl' ) ],
						];
					}
					$trust_icons = [
						'truck'  => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
						'clock'  => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M12 7v5l3 3"/>',
						'return' => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
					];
					?>
					<!-- Trust Badges Grid -->
					<div class="sp-trust-grid">
						<div class="sp-trust-card">
							<div class="sp-trust-card__icon sp-trust-card__icon--emerald">
								<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
							</div>
							<div class="sp-trust-card__content">
								<p class="sp-trust-card__title"><?php esc_html_e( 'Giao hàng miễn phí', 'spl' ); ?></p>
								<p class="sp-trust-card__desc"><?php esc_html_e( 'Toàn quốc trong 3-5 ngày', 'spl' ); ?></p>
							</div>
						</div>
						<div class="sp-trust-card">
							<div class="sp-trust-card__icon sp-trust-card__icon--blue">
								<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
							</div>
							<div class="sp-trust-card__content">
								<p class="sp-trust-card__title"><?php esc_html_e( 'Bảo hành 3 năm', 'spl' ); ?></p>
								<p class="sp-trust-card__desc"><?php esc_html_e( 'Chính hãng toàn quốc', 'spl' ); ?></p>
							</div>
						</div>
						<div class="sp-trust-card">
							<div class="sp-trust-card__icon sp-trust-card__icon--amber">
								<svg class="icon" viewBox="0 0 24 24"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
							</div>
							<div class="sp-trust-card__content">
								<p class="sp-trust-card__title"><?php esc_html_e( 'Đổi trả 7 ngày', 'spl' ); ?></p>
								<p class="sp-trust-card__desc"><?php esc_html_e( 'Miễn phí, không lý do', 'spl' ); ?></p>
							</div>
						</div>
						<div class="sp-trust-card">
							<div class="sp-trust-card__icon sp-trust-card__icon--primary">
								<svg class="icon" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
							</div>
							<div class="sp-trust-card__content">
								<p class="sp-trust-card__title"><?php esc_html_e( 'Trả góp 0%', 'spl' ); ?></p>
								<p class="sp-trust-card__desc"><?php esc_html_e( 'Duyệt nhanh trong 15 phút', 'spl' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<?php
			$has_tskt  = ( ! empty( $specs ) && is_array( $specs ) ) || ! empty( $tskt_image_url ) || ! empty( trim( (string) $tskt_content ) );
			$has_video = ! empty( $video_embed_url );
			?>
			<div class="sp-tabs reveal">
				<div class="sp-tabs__nav" role="tablist">
					<button class="sp-tabs__tab active" role="tab" aria-selected="true" data-tab="desc">
						<svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
						<?php esc_html_e( 'Mô tả sản phẩm', 'spl' ); ?>
					</button>
					<?php if ( $has_tskt ) : ?>
						<button class="sp-tabs__tab" role="tab" aria-selected="false" data-tab="tskt">
							<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
							<?php esc_html_e( 'Thông số kỹ thuật', 'spl' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( $has_video ) : ?>
						<button class="sp-tabs__tab" role="tab" aria-selected="false" data-tab="video">
							<svg class="icon" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
							<?php esc_html_e( 'Video thực tế', 'spl' ); ?>
						</button>
					<?php endif; ?>
					<button class="sp-tabs__tab" role="tab" aria-selected="false" data-tab="reviews">
						<svg class="icon" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
						<?php printf( esc_html__( 'Đánh giá (%d)', 'spl' ), (int) $review_count ); ?>
					</button>
				</div>

				<!-- Panel: Description -->
				<div class="sp-tabs__panel active" id="tab-desc">
					<div class="sp-desc-wrapper" id="sp-desc-wrapper">
						<div class="sp-desc" id="sp-desc-content">
							<?php
							$content = get_the_content();
							if ( trim( $content ) ) {
								echo wp_kses_post( apply_filters( 'the_content', $content ) );
							} else {
								echo '<p>' . esc_html( $product->get_short_description() ?: __( 'Đang cập nhật mô tả sản phẩm.', 'spl' ) ) . '</p>';
							}

							// Spec table from product attributes.
							$attributes = $product->get_attributes();
							if ( ! empty( $attributes ) ) :
								?>
								<h3><?php esc_html_e( 'Thông số sản phẩm', 'spl' ); ?></h3>
								<table class="sp-spec-table">
									<?php foreach ( $attributes as $attribute ) :
										$label = wc_attribute_label( $attribute->get_name() );
										$value = $product->get_attribute( $attribute->get_name() );
										if ( ! $value ) { continue; }
										?>
										<tr>
											<td><?php echo esc_html( $label ); ?></td>
											<td><?php echo esc_html( $value ); ?></td>
										</tr>
									<?php endforeach; ?>
								</table>
							<?php endif; ?>
						</div>
					</div>
					<div class="sp-desc-toggle" id="sp-desc-toggle-btn" style="display: none;">
						<button type="button" class="btn-show-more"><?php esc_html_e( 'Xem thêm', 'spl' ); ?></button>
					</div>
				</div>

				<!-- Panel: TSKT Specs -->
				<?php if ( $has_tskt ) : ?>
					<div class="sp-tabs__panel" id="tab-tskt">
						<div class="sp-desc">
							<h3>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: product name */
										__( 'Thông số kỹ thuật %s', 'spl' ),
										$product->get_name()
									)
								);
								?>
							</h3>

							<?php if ( ! empty( $specs ) && is_array( $specs ) ) : ?>
								<table class="sp-spec-table mb-6">
									<tbody>
										<?php foreach ( $specs as $row ) :
											$label = trim( (string) ( $row['tskt_label'] ?? $row['label'] ?? '' ) );
											$value = trim( (string) ( $row['tskt_value'] ?? $row['value'] ?? '' ) );

											if ( '' === $label && '' === $value ) {
												continue;
											}
											?>
											<tr>
												<td><?php echo esc_html( $label ); ?></td>
												<td><?php echo esc_html( $value ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>

							<?php if ( ! empty( $tskt_image_url ) ) : ?>
								<div class="sp-spec-image my-6 text-center">
									<img loading="lazy" decoding="async" src="<?php echo esc_url( $tskt_image_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Bảng thông số kỹ thuật %s', 'spl' ), $product->get_name() ) ); ?>" class="max-w-full h-auto mx-auto rounded-xl shadow-sm border border-slate-100" />
								</div>
							<?php endif; ?>

							<?php if ( ! empty( trim( (string) $tskt_content ) ) ) : ?>
								<div class="sp-spec-custom-content my-6">
									<?php echo wp_kses_post( apply_filters( 'the_content', $tskt_content ) ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Panel: Video Review / Video sản phẩm -->
				<?php if ( $has_video ) : ?>
					<div class="sp-tabs__panel" id="tab-video">
						<div class="sp-desc">
							<h3 class="mb-4"><?php esc_html_e( 'Video thực tế sản phẩm', 'spl' ); ?></h3>
							<div class="sp-video-container max-w-4xl mx-auto rounded-2xl overflow-hidden shadow-lg border border-slate-100 bg-slate-950 aspect-video flex items-center justify-center relative min-h-[300px] md:min-h-[480px]">
								<?php if ( 'youtube' === $video_type && ! empty( $video_id ) ) : ?>
									<div class="w-full h-full relative" data-fx-video data-fx-video-url="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>" data-fx-video-type="youtube">
										<img src="<?php echo esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" class="w-full h-full object-cover" loading="lazy" />
										<span class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors cursor-pointer">
											<svg class="w-16 h-16 text-white drop-shadow-md transition-transform hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21"/></svg>
										</span>
									</div>
								<?php elseif ( 'tiktok' === $video_type && ! empty( $video_id ) ) : ?>
									<iframe src="<?php echo esc_url( 'https://www.tiktok.com/embed/v2/' . $video_id ); ?>" class="w-full h-full border-0 min-h-[500px]" allowfullscreen allow="encrypted-media"></iframe>
								<?php elseif ( 'video' === $video_type ) : ?>
									<video src="<?php echo esc_url( $video_embed_url ); ?>" controls playsinline class="w-full h-full object-contain"></video>
								<?php else : ?>
									<iframe src="<?php echo esc_url( $video_embed_url ); ?>" class="w-full h-full border-0 min-h-[350px]" allowfullscreen></iframe>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Panel: Reviews -->
				<div class="sp-tabs__panel" id="tab-reviews">
					<div class="sp-reviews">
						<?php
						$review_comments = get_comments( [
							'post_id' => get_the_ID(),
							'status'  => 'approve',
							'type'    => 'review',
						] );

						// Rating distribution.
						$dist = [ 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 ];
						foreach ( $review_comments as $rc ) {
							$r = (int) get_comment_meta( $rc->comment_ID, 'rating', true );
							if ( $r >= 1 && $r <= 5 ) {
								$dist[ $r ]++;
							}
						}
						?>
						<?php if ( $review_count > 0 ) : ?>
							<div class="sp-reviews__summary">
								<div class="sp-reviews__score">
									<span class="sp-reviews__number"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></span>
									<?php spl_render_stars( $avg_rating, 'sp-stars--lg' ); ?>
									<span><?php echo (int) $review_count; ?> <?php esc_html_e( 'đánh giá', 'spl' ); ?></span>
								</div>
								<div class="sp-reviews__bars">
									<?php for ( $s = 5; $s >= 1; $s-- ) :
										$pct = $review_count > 0 ? round( ( $dist[ $s ] / $review_count ) * 100 ) : 0;
										?>
										<div class="sp-bar">
											<span><?php echo (int) $s; ?> ★</span>
											<div class="sp-bar__track"><div class="sp-bar__fill" style="width:<?php echo (int) $pct; ?>%"></div></div>
											<span><?php echo (int) $dist[ $s ]; ?></span>
										</div>
									<?php endfor; ?>
								</div>
							</div>

							<div class="sp-reviews__list">
								<?php foreach ( $review_comments as $rc ) :
									$rating  = (int) get_comment_meta( $rc->comment_ID, 'rating', true );
									$initials = mb_strtoupper( mb_substr( $rc->comment_author, 0, 2 ) );
									?>
									<div class="sp-review">
										<div class="sp-review__avatar"><?php echo esc_html( $initials ); ?></div>
										<div class="sp-review__content">
											<div class="sp-review__header">
												<strong><?php echo esc_html( $rc->comment_author ); ?></strong>
												<?php spl_render_stars( (float) $rating, 'sp-stars--sm' ); ?>
												<span class="sp-review__date"><?php echo esc_html( get_comment_date( 'd/m/Y', $rc->comment_ID ) ); ?></span>
											</div>
											<p><?php echo esc_html( $rc->comment_content ); ?></p>
											<?php if ( wc_review_is_from_verified_owner( $rc->comment_ID ) ) : ?>
												<div class="sp-review__verified">
													<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
													<?php esc_html_e( 'Đã mua hàng', 'spl' ); ?>
												</div>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="sp-reviews__empty"><?php esc_html_e( 'Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!', 'spl' ); ?></p>
						<?php endif; ?>

						<?php
						// Always render the review form below the list.
						spl_render_review_form( $product );
						?>
					</div>
				</div>
			</div>

			<!-- Related Products -->
			<?php
			$related_ids = wc_get_related_products( get_the_ID(), 5 );
			if ( ! empty( $related_ids ) ) :
				?>
				<section class="mt-12 md:mt-16 reveal">
					<div class="container">
						<div class="flex items-center gap-3 mb-6">
							<span class="w-1.5 h-6 bg-primary rounded-full"></span>
							<h2 class="text-xl font-bold text-slate-900 tracking-tight"><?php esc_html_e( 'Sản phẩm tương tự', 'spl' ); ?></h2>
						</div>
						<div class="relative closest-swiper">
							<div class="swiper" data-fx-slider>
								<div class="swiper-wrapper" data-swiper-options='<?php echo esc_attr( wp_json_encode( [
									'slidesPerView'       => 2,
									'spaceBetween'        => 16,
									'navigation'          => true,
									'watchSlidesProgress' => true,
									'observer'            => true,
									'observeParents'      => true,
									'breakpoints'         => [
										640  => [ 'slidesPerView' => 3, 'spaceBetween' => 20 ],
										1024 => [ 'slidesPerView' => 5, 'spaceBetween' => 20 ],
									],
								] ) ); ?>'>
									<?php
									foreach ( $related_ids as $rid ) :
										get_template_part( 'parts/product-card', null, [
											'id'    => $rid,
											'class' => 'swiper-slide',
										] );
									endforeach;
									?>
								</div>
							</div>
							<div class="swiper-controls"></div>
						</div>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</section>

	<?php
endwhile;

get_footer();
